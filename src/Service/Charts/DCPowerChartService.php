<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use Symfony\Component\Security\Core\Security;
use PDO;

class DCPowerChartService
{
    use G4NTrait;

    private Security $security;
    private AnlagenStatusRepository $statusRepository;
    private InvertersRepository $invertersRepo;
    public functionsService $functions;
    private IrradiationChartService $irradiationChart;

    public function __construct(Security                $security,
                                AnlagenStatusRepository $statusRepository,
                                InvertersRepository     $invertersRepo,
                                IrradiationChartService $irradiationChart,
                                FunctionsService        $functions)
    {
        $this->security = $security;
        $this->statusRepository = $statusRepository;
        $this->invertersRepo = $invertersRepo;
        $this->functions = $functions;
        $this->irradiationChart = $irradiationChart;
    }

    /**
     * DC Diagramme
     * Erzeugt Daten für das normale Soll/Ist DC Diagramm.
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param bool $hour
     * @return array|null [DC1]
     * [DC1]
     */
    public function getDC1(Anlage $anlage, $from, $to,  bool $hour = false): ?array
    {
        if(true){
            ($hour) ? $form = '%y%m%d%H' : $form = '%y%m%d%H%i';
            $conn = self::getPdoConnection();
            $dataArray = [];
            $sqlDcSoll = "SELECT a.stamp as stamp, sum(b.soll_pdcwr) as soll
                      FROM (db_dummysoll a left JOIN " . $anlage->getDbNameDcSoll() . " b ON a.stamp = b.stamp) 
                      WHERE a.stamp BETWEEN '$from' AND '$to' 
                      GROUP by date_format(a.stamp, '$form')";

            if ($anlage->getUseNewDcSchema()) {
                $sql_b = "SELECT sum(wr_pdc) as dcist 
                          FROM (db_dummysoll a left JOIN " . $anlage->getDbNameDCIst() . " b ON a.stamp = b.stamp) 
                          WHERE a.stamp BETWEEN '$from' AND '$to' 
                          GROUP by date_format(a.stamp, '$form')";
            }
            else {
                $sql_b = "SELECT sum(wr_pdc) as dcist 
                          FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b ON a.stamp = b.stamp) 
                          WHERE a.stamp BETWEEN '$from' AND '$to' 
                          GROUP by date_format(a.stamp, '$form')";
            }

            $resultActual = $conn->query($sql_b);
            $resultExpected = $conn->query($sqlDcSoll);
            $actSum = 0;
            $expSum = 0;
            // add Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false || $anlage->getUseCustPRAlgorithm() == "Groningen") {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            }

            if ($resultExpected->rowCount() > 0) {
                $counter = 0;
                while (($roa = $resultExpected->fetch(PDO::FETCH_ASSOC)) && ($rob = $resultActual->fetch(PDO::FETCH_ASSOC))) {
                    $stamp = $roa["stamp"];
                    ((float)$roa["soll"] > 0) ? $soll = round($roa["soll"], 2) : $soll = 0;
                    $expdiff = round($soll - $soll * 10 / 100, 2); //-10% good
                    $dcist = self::checkUnitAndConvert($rob["dcist"], $anlage->getAnlDbUnit());

                ($dcist > 0) ? $dcist = round($dcist, 2) : $dcist = 0; // neagtive Werte auschließen
                $actSum += $dcist;
                $expSum += $soll;
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);

                if (!($soll == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['expected'] = $soll;
                    $dataArray['chart'][$counter]['expgood'] = $expdiff;
                }

                if (!($dcist == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['InvOut'] = $dcist;
                }
                //Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                }
                $counter++;
                }
                $dataArray['actSum'] = round($actSum, 2);
                $dataArray['expSum'] = round($expSum, 2);
            }
        }

        $conn = null;

        return $dataArray;
    }


    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @param bool $hour
     * @return array
     * [DC2]
     */
    public function getDC2(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $acGroups = $anlage->getGroupsAc();
        $dataArray = [];
        switch ($anlage->getConfigType()) {
            case 1:
                $groupQuery = "group_ac = '$group' ";
                $groupByNewDC = ", wr_num";
                $groupByOldDC = ", group_dc";
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                $invertersInGroup = ($acGroups[$group]['GMAX'] - $acGroups[$group]['GMIN']) + 1;
                break;
            case 3:
                $groupQuery = "group_ac = '$group' ";
                $groupByNewDC = "";
                $groupByOldDC = ", group_dc";
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                $invertersInGroup = 1;
                break;
            default:
                $groupQuery = "group_dc = '$group' ";
                $groupByNewDC = ", group_dc";
                $groupByOldDC = ", group_dc";
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
                $invertersInGroup = ($acGroups[$group]['GMAX'] - $acGroups[$group]['GMIN']) + 1;
        }
        $dataArray['inverterArray'] = $nameArray;

        // SOLL Strom für diesen Zeitraum und diese Gruppe
        $sql = "SELECT a.stamp as stamp, sum(b.dc_exp_power) as expected
                   FROM (db_dummysoll a LEFT JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE $groupQuery) b ON a.stamp = b.stamp)
                   WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";
        #dd($sql);
        $result = $conn->query($sql);
        $expectedResult = $result->fetchAll(PDO::FETCH_ASSOC);

        if ($result->rowCount() > 0) {
            $dataArray['maxSeries'] = $invertersInGroup;
            $counter = 0;
            // add Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper');
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to);
            }
            foreach ($expectedResult as $rowSoll) {
                $stamp = $rowSoll['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                switch ($anlage->getConfigType()) {
                    case 3:
                        $dataArray['offsetLegend'] = $group - 1;
                        break;
                    default:
                        $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
                }

                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stampAdjust);
                if (!(($rowSoll['expected'] == 0) && (self::isDateToday($stampAdjust) && self::getCetTime() - strtotime($stampAdjust) < 7200))) {
                    switch ($anlage->getConfigType()) {
                        case 1:
                        case 2:
                            $dataArray['chart'][$counter]['expected'] = $rowSoll['expected'] > 0 ? $rowSoll['expected'] / $invertersInGroup : 0;
                            $dataArray['chart'][$counter]['expected'] = $hour ? $dataArray['chart'][$counter]['expected'] / 4 : $dataArray['chart'][$counter]['expected'];
                            break;
                        default:
                            $dataArray['chart'][$counter]['expected'] = $hour ? $rowSoll['expected'] / $invertersInGroup / 4 : $rowSoll['expected'] / $invertersInGroup;
                    }
                    $dataArray['chart'][$counter]['expected'] = round($dataArray['chart'][$counter]['expected'], 2);
                }

                if ($hour) {
                    $stampAdjustTo = date('Y-m-d H:m:s', strtotime($stampAdjust) + 3600);
                    $wherePart1 = "stamp >= '$stampAdjust' AND stamp < '$stampAdjustTo'";
                } else {
                    $wherePart1 = "stamp = '$stampAdjust' ";
                }
                if ($anlage->getUseNewDcSchema()) {
                    $sql = "SELECT sum(wr_pdc) as istPower FROM " . $anlage->getDbNameDCIst() . " WHERE " . $wherePart1 . " AND $groupQuery group by date_format(stamp, '$form') $groupByNewDC;";
                } else {
                    $sql = "SELECT sum(wr_pdc) as istPower FROM " . $anlage->getDbNameACIst() . " WHERE " . $wherePart1 . " AND $groupQuery group by date_format(stamp, '$form') $groupByOldDC;";
                }
                #if ($counter > 40) dd($sql);
                $resultAct = $conn->query($sql);
                $inverterCount = 1;
                while ($rowAct = $resultAct->fetch(PDO::FETCH_ASSOC)){
                    $currentAct = $hour ? $rowAct['istPower'] / 4 : $rowAct['istPower'];
                    $currentAct = round($currentAct, 2);
                    if (!($currentAct == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                        $dataArray['chart'][$counter][$nameArray[$inverterCount+$dataArray['offsetLegend']]] = $currentAct;
                    }
                    $inverterCount++;
                }
                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                }
                $counter++;
            }
        }
        $conn = null;
#dd($dataArray);
        return $dataArray;
    }

    /**
     * Erzeugt Daten für das Soll/Ist AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @param bool $hour
     * @return array
     * [DC3]
     */
    public function getDC3(Anlage $anlage, $from, $to, int $group = 1,  bool $hour = false):array
    {

        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        switch ($anlage->getConfigType()) {

            case 3: // Groningen
                $nameArray = $this->functions->getNameArray($anlage, 'scb');
                break;
            default:
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
        }
        $dataArray['inverterArray'] = $nameArray;

        $groups = $anlage->getGroupsDc();
        $sqlExpected = "SELECT a.stamp, sum(b.soll_pdcwr) as soll 
            FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
        $result = $conn->query($sqlExpected);
        if ($result->rowCount() > 0) {
            $maxInverter = 0;
            // add Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper');
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to);
            }

            $counter = 0;
            $dataArray['offsetLegend'] = $dcGroups[$group]['GMIN'] - 1;

            foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $rowExp){
                $stamp = $rowExp['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                $anzInvPerGroup = $groups[$group]['GMAX'] - $groups[$group]['GMIN'] + 1;
                $expected = $anzInvPerGroup > 0 ? $rowExp['soll'] / $anzInvPerGroup :  $rowExp['soll'];
                if ($expected < 0) $expected = 0;
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                if (!($expected == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['expected'] = $expected;
                }
                $mppCounter = 0;
                for ($inverter = $dcGroups[$group]['GMIN']; $inverter <= $dcGroups[$group]['GMAX']; $inverter++) {

                    $mppCounter++;
                    $whereQueryPart1 = $hour ? "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'" : "stamp = '$stampAdjust'";
                    if ($anlage->getUseNewDcSchema()) {
                        $sql = "SELECT sum(wr_pdc) as actPower, wr_temp as temp FROM " . $anlage->getDbNameDCIst() . " WHERE $whereQueryPart1 AND wr_num = '$inverter' GROUP BY date_format(stamp, '$form')";
                    } else {
                        $sql = "SELECT sum(wr_pdc) as actPower, wr_temp as temp FROM " . $anlage->getDbNameAcIst() . " WHERE $whereQueryPart1 AND unit = '$inverter' GROUP BY date_format(stamp, '$form')";
                    }
                    $resultIst = $conn->query($sql);
                    if ($resultIst->rowCount() > 0) {
                        $rowIst = $resultIst->fetch(PDO::FETCH_ASSOC);
                        $currentIst = round((float)$rowIst['actPower'], 2);
                        if ($rowIst['temp'] == null) $temperature = 0;
                        else $temperature = $rowIst['temp'];
                        $dataArray['chart'][$counter]['temperature'] = $temperature;
                        if ($hour) $currentIst = $currentIst / 4;
                        if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            //$dataArray['chart'][$counter]["val$mppCounter"] = $currentIst;
                            switch ($anlage->getConfigType()) {
                                case 3: // Groningen
                                    $dataArray['chart'][$counter][$nameArray[$inverter]] = $currentIst;
                                    break;
                                default:
                                    $dataArray['chart'][$counter][$nameArray[$inverter]] = $currentIst;
                            }
                        }
                    }
                    $dataArray['label'][$inverter] = $nameArray[$inverter];
                }
                if ($mppCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $mppCounter;

                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                }
                $counter++;
            }
        }
        $conn = null;

        return $dataArray;

    }

    /**
     * erzeugt Daten für Gruppen Leistungs Unterschiede Diagramm (Group Power Difference)
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     * [DC4] DC - Inverter / DC - Inverter Group // dc_grp_power_diff Bar Chart
     */
    public function getGroupPowerDifferenceDC(Anlage $anlage, $from, $to): array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $istGruppenArray = [];
        $dcGroups = $anlage->getGroupsDc();
        // IST Leistung für diesen Zeitraum nach Gruppen gruppiert
        if ($anlage->getUseNewDcSchema()) {
            $sqlIst = "SELECT sum(wr_pdc) as power_dc_ist, wr_group as inv_group FROM " . $anlage->getDbNameDCIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY wr_group ;";
        } else {
            $sqlIst = "SELECT sum(wr_pdc) as power_dc_ist, group_dc as inv_group FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_dc ;";
        }
        $resultIst = $conn->query($sqlIst);
        while ($rowIst = $resultIst->fetch(PDO::FETCH_ASSOC)) { // Speichern des SQL ergebnisses in einem Array, Gruppe ist assosiativer Array Index
            $istGruppenArray[$rowIst['inv_group']] = $rowIst['power_dc_ist'];
        }
        // SOLL Leistung für diesen Zeitraum nach Gruppen gruppiert
        $sql_soll = "SELECT stamp, sum(soll_pdcwr) as soll, group_dc as inv_group FROM " . $anlage->getDbNameDcSoll() . " 
                         WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_dc ORDER BY group_dc * 1"; // 'wr_num * 1' damit die Sortierung als Zahl und nicht als Text erfolgt

        $result = $conn->query($sql_soll);
        $counter = 0;
        if ($result->rowCount() > 0) {
            $dataArray['maxSeries'] = 0;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $dataArray['rangeValue'] = round($row["soll"], 2);
                $invGroupSoll = $row["inv_group"];
                $dataArray['chart'][$counter] = [
                    "category" => $dcGroups[$invGroupSoll]['GroupName'],
                    "link" => $invGroupSoll,
                    "exp" => round($row["soll"], 2),
                ];
                $dataArray['chart'][$counter]['act'] = round(self::checkUnitAndConvert($istGruppenArray[$invGroupSoll], $anlage->getAnlDbUnit()), 2);
                if ($counter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counter;
                $counter++;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * erzeugt Daten für Inverter Leistungs Unterschiede Diagramm (Inverter Power Difference)
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $group
     * @return array
     * DC - Inverter // dc_inv_power_diff
     */
    public function getInverterPowerDifference(Anlage $anlage, $from, $to, $group): array
    {
        $conn = self::connectToDatabase();
        $dataArray = [];

        if (self::isDateToday($to)) {
            // letzten Eintrag in IST DB ermitteln
            $res = $conn->query("SELECT stamp FROM " . $anlage->getDbNameIst() . " WHERE stamp > '$from' ORDER BY stamp DESC LIMIT 1");
            if ($res) {
                $rowTemp = $res->fetch_assoc();
                $lastRecStampAct = strtotime($rowTemp['stamp']);
                $res->free();


                // letzten Eintrag in  Weather DB ermitteln
                $res = $conn->query("SELECT stamp FROM " . $anlage->getDbNameDcSoll() . " WHERE stamp > '$from' ORDER BY stamp DESC LIMIT 1");
                if ($res) {
                    $rowTemp = $res->fetch_assoc();
                    $lastRecStampExp = strtotime($rowTemp['stamp']);
                    $res->free();
                    ($lastRecStampAct <= $lastRecStampExp) ? $toLastBoth = self::formatTimeStampToSql($lastRecStampAct) : $toLastBoth = self::formatTimeStampToSql($lastRecStampExp);
                    $to = $toLastBoth;
                }
            }
        }

        // Leistung für diesen Zeitraum und diese Gruppe
        $sql_soll = "SELECT stamp, sum(soll_pdcwr) as soll FROM " . $anlage->getDbNameDcSoll() . " WHERE stamp BETWEEN '$from' AND '$to' AND wr_num = '$group' GROUP BY wr LIMIT 1";
        $result = $conn->query($sql_soll);
        $counter = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dataArray['rangeValue'] = round($row["soll"], 2);
                $dataArray['chart'][] = [
                    "category" => 'expected',
                    "val" => round($row["soll"], 2),
                    "color" => '#fdd400',
                ];
                if ($anlage->getUseNewDcSchema()) {
                    $sqlInv = "SELECT sum(wr_pdc) as dcinv, wr_num AS inverter FROM " . $anlage->getDbNameDCIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND wr_group = '$group' GROUP BY wr_num";
                } else {
                    $sqlInv = "SELECT sum(wr_pdc) as dcinv, unit AS inverter FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$group' GROUP BY unit";
                }
                $resultInv = $conn->query($sqlInv);
                if ($resultInv->num_rows > 0) {
                    $wrcounter = 0;
                    while ($rowInv = $resultInv->fetch_assoc()) {
                        $wrcounter++;
                        $inverter = $rowInv['inverter'];
                        $dataArray['chart'][] = [
                            "category" => "Inverter #$inverter",
                            "val" => self::checkUnitAndConvert($rowInv['dcinv'], $anlage->getAnlDbUnit()),
                            "link" => "$inverter",
                        ];
                        if ($wrcounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $wrcounter;
                    }
                }
                $counter++;
            }
        }
        $conn->close();

        return $dataArray;
    }

}