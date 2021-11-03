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
     * @return array
     * [DC1]
     */
    public function getDC1(Anlage $anlage, $from, $to, bool $hour):?array
    {
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        $sqlDcSoll = "SELECT a.stamp as stamp, sum(b.soll_pdcwr) as soll
                      FROM (db_dummysoll a left JOIN " . $anlage->getDbNameDcSoll() . " b ON a.stamp = b.stamp) 
                      WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP by date_format(a.stamp, '$form')";

        $resulta = $conn->query($sqlDcSoll);
        $actSum = 0;
        $expSum = 0;
        // add Irradiation
        if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to,'upper', $hour);
        } else {
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to,'all', $hour);
        }

        if ($resulta->rowCount() > 0) {
            $counter = 0;
            while ($roa = $resulta->fetch(PDO::FETCH_ASSOC)){
                $dcist = 0;
                $stamp = $roa["stamp"];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);

                if($hour)$soll = round($roa["soll"], 2);
                else $soll = round($roa["soll"], 2);
                $expdiff = round($soll - $soll * 10 / 100, 2); //-10% good
                if($hour) {
                    if ($anlage->getUseNewDcSchema()) {
                        $sql_b = "SELECT stamp, sum(wr_pdc) as dcist 
                              FROM " . $anlage->getDbNameDCIst() . " 
                              WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' GROUP by date_format(stamp, '$form') LIMIT 1";
                    } else {

                        $sql_b = "SELECT stamp, sum(wr_pdc) as dcist 
                              FROM " . $anlage->getDbNameIst() . " 
                              WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' GROUP by date_format(stamp, '$form') LIMIT 1";
                    }
                }else {
                    if ($anlage->getUseNewDcSchema()) {
                        $sql_b = "SELECT stamp, sum(wr_pdc) as dcist 
                              FROM " . $anlage->getDbNameDCIst() . " 
                              WHERE stamp = '$stampAdjust' GROUP by stamp LIMIT 1";
                    } else {
                        $sql_b = "SELECT stamp, sum(wr_pdc) as dcist 
                              FROM " . $anlage->getDbNameIst() . " 
                              WHERE stamp = '$stampAdjust' GROUP by stamp LIMIT 1";
                    }

                }
                $resultb = $conn->query($sql_b);
                if ($resultb->rowCount() > 0) {
                    while ($rob = $resultb->fetch(PDO::FETCH_ASSOC)) {
                        $dcist = self::checkUnitAndConvert($rob["dcist"], $anlage->getAnlDbUnit());

                    }
                }

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
                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                }
                $counter++;
            }
            $dataArray['actSum'] = round($actSum, 2);
            $dataArray['expSum'] = round($expSum, 2);
        }
        $conn = null;

        return $dataArray;
    }


    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * [DC2]
     */
    public function getDC2(Anlage $anlage, $from, $to, int $group = 1, $hour): array
    {
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';

        $conn = self::getPdoConnection();
        $groups = $anlage->getGroupsAc();
        $dataArray = [];
        $inverterNr = 0;
        $sqlExp = "SELECT a.stamp as stamp, sum(b.dc_exp_power) as expected
                    FROM (db_dummysoll a LEFT JOIN (SELECT stamp, dc_exp_power, group_ac FROM " . $anlage->getDbNameDcSoll() . " WHERE ";
        switch ($anlage->getConfigType()) {
            case 1: // Andjik
            case 3:
            case 4:
            $sqlExp .= "group_ac";
            $nameArray = $this->functions->getNameArray($anlage , 'dc');
                break;
            default:
                $sqlExp .= "group_ac";
                $nameArray = $this->functions->getNameArray($anlage , 'ac');
        }
        $sqlExp .= " = '$group') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";
        $dataArray['inverterArray'] = $nameArray;
        // add Irradiation
        if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper');
        } else {
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to);
        }
        // SOLL Strom für diesen Zeitraum und diese Gruppe
        $result = $conn->query($sqlExp);
        if ($result->rowCount() > 0) {
            $dataArray['maxSeries'] = 0;
            $counter = 0;
            while ($rowSoll = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowSoll['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                $dataArray['chart'][$counter]['expected'] = $rowSoll['expected'] / ($groups[$group]['GMAX'] - $groups[$group]['GMIN']);

                if($hour) {
                    if ($anlage->getUseNewDcSchema()) {
                        switch ($anlage->getConfigType()) {

                            default:
                                $sql = "SELECT sum(wr_pdc) as istCurrent 
                                    FROM " . $anlage->getDbNameDCIst() . " 
                                    WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND group_ac = '$group' GROUP BY wr_group ";
                        }
                    }
                    else {
                        switch ($anlage->getConfigType()) {

                            default:
                                $sql = "SELECT sum(wr_pdc) as istCurrent 
                                    FROM " . $anlage->getDbNameACIst() . " 
                                    WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND group_ac = '$group' GROUP BY group_dc ";
                        }
                    }
                }
                else{
                    if ($anlage->getUseNewDcSchema()) {
                        switch ($anlage->getConfigType()) {

                            default:
                                $sql = "SELECT sum(wr_pdc) as istCurrent 
                                    FROM " . $anlage->getDbNameDCIst() . " 
                                    WHERE stamp = '$stampAdjust' AND group_ac = '$group' group by wr_group";
                        }
                    }
                    else {
                        switch ($anlage->getConfigType()) {

                            default:
                                $sql = "SELECT sum(wr_pdc) as istCurrent 
                                    FROM " . $anlage->getDbNameACIst() . " 
                                    WHERE stamp = '$stampAdjust' AND group_ac = '$group' group by group_dc";
                        }
                    }
                }

                $resultIst = $conn->query($sql);
                if ($resultIst->rowCount() > 0) {
                    $rowsIst = $resultIst->fetchAll(PDO::FETCH_ASSOC);
                    $inverterNr = $groups[$group]['GMIN'];
                    foreach ($rowsIst as $rowIst) {
                        $currentIst = round($rowIst['istCurrent'], 2);
                        if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            switch ($anlage->getConfigType()) {
                                case 3:

                                    break;
                                default:
                                    $dataArray['chart'][$counter][$nameArray[$inverterNr]] = $currentIst;
                            }
                        }
                        $inverterNr++;
                    }
                }
                // Finde den höchsten Wert für 'maxSeries', das entspricht der Anzahl der liniene im Diagramm.
                if ($dataArray['maxSeries'] < $inverterNr - $groups[$group]['GMIN']) $dataArray['maxSeries'] = $inverterNr - $groups[$group]['GMIN'];;
                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                }
                $counter++;
            }
            $dataArray['offsetLegend'] = $groups[$group]['GMIN'] - 1;
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das Soll/Ist AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * [DC3]
     */
    public function getDC3(Anlage $anlage, $from, $to, int $group = 1, bool $hour):array
    {
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        switch ($anlage->getConfigType()) {
            case 1:
                $nameArray = $this->functions->getNameArray($anlage , 'dc');
                $groups = $anlage->getGroupsDc();
                $sqlExpected = "SELECT a.stamp, sum(b.soll_pdcwr) as soll 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                break;
            default:
                $nameArray = $this->functions->getNameArray($anlage , 'dc');
                $groups = $anlage->getGroupsDc();
                $sqlExpected = "SELECT a.stamp, sum(b.soll_pdcwr) as soll 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
        }

        $dataArray['inverterArray'] = $nameArray;
        $result = $conn->query($sqlExpected);
        $maxInverter = 0;
        // add Irradiation
        if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper');
        } else {
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to);
        }
        if ($result->rowCount() > 0) {
            $dataArray['maxSeries'] = 0;
            $counter = 0;
            switch ($anlage->getConfigType()) {

                case 3: // Groningen
                    $dataArray['offsetLegend'] = $group - 1;
                    break;
                default:
                    $dataArray['offsetLegend'] = $groups[$group]['GMIN'] - 1;
            }
            while ($rowExp = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowExp['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                $anzInvPerGroup = $groups[$group]['GMAX'] - $groups[$group]['GMIN'] + 1;
                ($anzInvPerGroup > 0) ? $expected = $rowExp['soll'] / $anzInvPerGroup : $expected = $rowExp['soll'];
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                if (!($expected == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['expected'] = $expected;
                }
                if($hour) {
                    $sql = "SELECT sum(wr_pdc) as avg(actPower), wr_temp as temp FROM ";
                    if ($anlage->getUseNewDcSchema()) {
                        $sql .= $anlage->getDbNameDCIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND wr_group = '$group' GROUP BY wr_num;";
                    } else {
                        $sql .= $anlage->getDbNameAcIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND group_dc = '$group' GROUP BY unit;";
                    }
                }
                else{
                    $sql = "SELECT sum(wr_pdc) as actPower, wr_temp as temp FROM ";
                    if ($anlage->getUseNewDcSchema()) {
                        $sql .= $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_group = '$group' GROUP BY wr_num;";
                    } else {
                        $sql .= $anlage->getDbNameAcIst() . " WHERE stamp = '$stampAdjust' AND group_dc = '$group' GROUP BY unit;";
                    }
                }

                $resultIst = $conn->query($sql);
                $counterInv = 1;
                if ($resultIst->rowCount() > 0) {
                    while ($rowIst = $resultIst->fetch(PDO::FETCH_ASSOC)) {
                        if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                        if ($rowIst['temp'] == null) $temperature = 0;
                        else $temperature = $rowIst['temp'];
                        $dataArray['chart'][$counter]['temperature'] = $temperature;

                        $actPower = self::checkUnitAndConvert($rowIst['actPower'], $anlage->getAnlDbUnit());

                        if (!($actPower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            switch ($anlage->getConfigType()) {

                                case 3: // Groningen, Saran
                                    $dataArray['chart'][$counter][$nameArray[$group]] = $actPower;
                                    break;
                                default:
                                    $dataArray['chart'][$counter][$nameArray[$counterInv+$dataArray['offsetLegend']]] = $actPower;
                                    $counterInv++;
                            }
                        }
                        switch ($anlage->getConfigType()) {
                            case 3:
                                if ($counterInv > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counterInv;
                                break;
                            default:
                                if ($counterInv > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counterInv - 1;
                        }
                    }
                } else {
                    for($counterInv = 1; $counterInv <= $maxInverter; $counterInv++) {
                        switch ($anlage->getConfigType()) {
                            case 3: // Groningen
                                $dataArray['chart'][$counter][$nameArray[$group]] = 0;
                                break;
                            default:
                                $dataArray['chart'][$counter][$nameArray[$counterInv+$dataArray['offsetLegend']]] = 0;
                        }
                    }
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

        return $dataArray;
    }

    /**
     * erzeugt Daten für Gruppen Leistungs Unterschiede Diagramm (Group Power Difference)
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     * DC - Inverter / DC - Inverter Group // dc_grp_power_diff Bar Chart
     */
    public function getGroupPowerDifferenceDC(Anlage $anlage, $from, $to): array
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $istGruppenArray = [];
        $dcGroups = $anlage->getGroupsDc();
        // IST Strom für diesen Zeitraum nach Gruppen gruppiert
        if ($anlage->getUseNewDcSchema()) {
            $sqlIst = "SELECT sum(wr_pdc) as power_dc_ist, wr_group as inv_group FROM " . $anlage->getDbNameDCIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY wr_group ;";
        } else {
            $sqlIst = "SELECT sum(wr_pdc) as power_dc_ist, group_dc as inv_group FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_dc ;";
        }
        $resultIst = $conn->query($sqlIst);
        while ($rowIst = $resultIst->fetch_assoc()) { // Speichern des SQL ergebnisses in einem Array, Gruppe ist assosiativer Array Index
            $istGruppenArray[$rowIst['inv_group']] = $rowIst['power_dc_ist'];
        }
        // SOLL Strom für diesen Zeitraum nach Gruppen gruppiert
        $sql_soll = "SELECT stamp, sum(soll_pdcwr) as soll, wr_num as inv_group FROM " . $anlage->getDbNameDcSoll() . " 
                         WHERE stamp BETWEEN '$from' AND '$to' GROUP BY wr_num ORDER BY wr_num * 1"; // 'wr_num * 1' damit die Sortierung als Zahl und nicht als Text erfolgt

        $result = $conn->query($sql_soll);
        $counter = 0;
        if ($result->num_rows > 0) {
            $dataArray['maxSeries'] = 0;
            while ($row = $result->fetch_assoc()) {
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
        $conn->close();

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