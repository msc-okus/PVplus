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
     * @return array|null
     * [DC1]
     */
    public function getDC1(Anlage $anlage, $from, $to,  bool $hour = false): ?array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        $sqlDcSoll = "SELECT a.stamp as stamp, sum(b.soll_pdcwr) as soll
                  FROM (db_dummysoll a left JOIN " . $anlage->getDbNameDcSoll() . " b ON a.stamp = b.stamp) 
                  WHERE a.stamp BETWEEN '$from' AND '$to' 
                  GROUP by date_format(a.stamp, '$form')";

        $resultExpected = $conn->query($sqlDcSoll);
        $actSum = $expSum = 0;

        // add Irradiation
        if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false || $anlage->getUseCustPRAlgorithm() == "Groningen") {
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
        } else {
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
        }

        if ($resultExpected->rowCount() > 0) {
            $counter = 0;
            while ($rowExp = $resultExpected->fetch(PDO::FETCH_ASSOC)) {
                $stamp = self::timeShift($anlage, $rowExp["stamp"]);
                $stampAdjust = self::timeAjustment($stamp, $anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                $soll = round($rowExp["soll"], 2);
                if ($soll !== null) {
                    $soll = $soll > 0 ? $soll : 0;
                    $expdiff = round($soll - $soll * 10 / 100, 2); //-10% good
                } else {
                    $expdiff = null;
                }
                // start query actual
                $whereQueryPart1 = $hour ? "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'" : "stamp = '$stampAdjust'";
                if ($anlage->getUseNewDcSchema()) {
                    $sqlActual = "SELECT sum(wr_pdc) AS dcIst FROM " . $anlage->getDbNameDCIst() . " 
                        WHERE $whereQueryPart1 GROUP BY date_format(stamp, '$form')";
                } else {
                    $sqlActual = "SELECT sum(wr_pdc) AS dcIst FROM " . $anlage->getDbNameIst() . " 
                        WHERE $whereQueryPart1 GROUP BY date_format(stamp, '$form')";
                }
                $resActual = $conn->query($sqlActual);
                if ($resActual->rowCount() == 1) {
                    $rowActual = $resActual->fetch(PDO::FETCH_ASSOC);
                    $dcIst = $rowActual["dcIst"];
                    $dcIst = $dcIst > 0 ? round($dcIst, 2) :  0; // neagtive Werte auschließen

                    $actSum += $dcIst;
                } else {
                    $dcIst = null;
                }
                $expSum += $soll;

                $dataArray['chart'][$counter]['date'] = $stamp;
                if (!($soll == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['expected'] = $soll;
                    $dataArray['chart'][$counter]['expgood'] = $expdiff;
                }
                if (!(($dcIst === 0 || $dcIst === null)&& self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['InvOut'] = $dcIst;
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
            if ($hour) $form = '%y%m%d%H';
            else $form = '%y%m%d%H%i';

            $conn = self::getPdoConnection();
            $groups = $anlage->getGroupsAc();
            $dataArray = [];
            $inverterNr = 0;
            switch ($anlage->getConfigType()) {
                case 1: // Andjik
                case 3:
                case 4:
                    $nameArray = $this->functions->getNameArray($anlage, 'dc');
                    break;
                default:
                    $nameArray = $this->functions->getNameArray($anlage, 'ac');
            }
            $sqlExp = "SELECT a.stamp as stamp, sum(b.dc_exp_power) as expected
                        FROM (db_dummysoll a LEFT JOIN (SELECT stamp, dc_exp_power, group_ac FROM " . $anlage->getDbNameDcSoll() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp)
                        WHERE a.stamp BETWEEN '$from' AND '$to' 
                        GROUP BY date_format(a.stamp, '$form')";

                if ($anlage->getUseNewDcSchema()) {

                            $sql = "SELECT sum(wr_pdc) as istCurrent 
                                    FROM (db_dummysoll a LEFT JOIN (SELECT * FROM " . $anlage->getDbNameDCIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp)
                                    WHERE a.stamp BETWEEN '$from' AND '$to' AND group_ac = '$group' 
                                    GROUP BY date_format(a.stamp, '$form'), wr_group ";

                } else {
                            $sql = "SELECT sum(wr_pdc) as istCurrent 
                                    FROM (db_dummysoll a LEFT JOIN (SELECT * FROM " . $anlage->getDbNameACIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp)
                                    WHERE a.stamp BETWEEN '$from' AND '$to' 
                                    GROUP BY date_format(a.stamp, '$form'), group_dc ";
                }

            $resultExp = $conn->query($sqlExp);
            $resultActual = $conn->query($sql);

            $maxInverter = $resultActual->rowCount() / $resultExp->rowCount();

            $dataArray['inverterArray'] = $nameArray;


            if ($resultExp->rowCount() > 0) {
                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false || $anlage->getUseCustPRAlgorithm() == "Groningen") {
                    $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper');
                } else {
                    $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to);
                }
                // SOLL Strom für diesen Zeitraum und diese Gruppe

                $dataArray['maxSeries'] = 0;
                $legend= $groups[$group]['GMIN'] - 1;
                $counter = 0;
                while ($rowExp = $resultExp->fetch(PDO::FETCH_ASSOC)) {

                    $stamp = $rowExp["stamp"];
                    ($rowExp['soll'] == null) ? $expected = 0 : $expected = $rowExp['soll'];
                    $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                    $counterInv = 1;
                    $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                    $dataArray['chart'][$counter]['expected'] = $groups[$group]['GMAX'] - $groups[$group]['GMIN'] == 0 ? $rowExp['expected'] : $rowExp['expected'] / ($groups[$group]['GMAX'] - $groups[$group]['GMIN']);

                    while($counterInv <= $maxInverter) {
                        $rowActual = $resultActual->fetch(PDO::FETCH_ASSOC);

                        $currentIst = $rowActual['istCurrent'];
                       if($currentIst != null) $currentIst = round($rowActual['istCurrent'], 2);
                       else $currentIst = 0;

                        if (!( self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            switch ($anlage->getConfigType()) {
                                case 3:

                                    break;
                                default:
                                    $dataArray['chart'][$counter][$nameArray[$counterInv + $legend]] = $currentIst;
                            }
                        }
                        $counterInv++;
                    }
                    $dataArray['maxSeries'] =  $maxInverter;
                    // add Irradiation
                    if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                        $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                    } else {
                        $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                    }
                    $counter++;
                }
                $dataArray['offsetLegend'] = $legend;
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
     * @param bool $hour
     * @return array
     * [DC3]
     */
    public function getDC3(Anlage $anlage, $from, $to, int $group = 1,  bool $hour = false):array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        $nameArray = $this->functions->getNameArray($anlage , 'dc');
        $groups = $anlage->getGroupsDc();
        switch ($anlage->getConfigType()) {
            case 3:
            case 4:
                # z.B. Gronningen
                $groupQuery = "group_ac = '$group' ";
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                break;
            default:
                $groupQuery = "group_dc = '$group' ";
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
        }
        $sqlExpected = "SELECT a.stamp, sum(b.soll_pdcwr) as soll 
            FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE $groupQuery) b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";

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
                case 4:
                    $dataArray['offsetLegend'] = $group - 1;
                    break;
                default:
                    $dataArray['offsetLegend'] = $groups[$group]['GMIN'] - 1;
            }
            foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $rowExp){
                $stamp = $rowExp['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                $anzInvPerGroup = $groups[$group]['GMAX'] - $groups[$group]['GMIN'] + 1;

                $expected = $rowExp['soll'] ;
                if ($expected !== null) {
                    $expected = $expected > 0 ? $expected : 0;
                    switch ($anlage->getConfigType()) {
                        case 1:
                        case 2:
                            $expected = $anzInvPerGroup > 0 ? $expected / $anzInvPerGroup : $expected;
                            break;
                    }
                    if ($expected < 0) $expected = 0;
                }

                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                if (!($expected == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['expected'] = $expected;
                }
                $whereQueryPart1 = $hour ? "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'" : "stamp = '$stampAdjust'";
                if ($anlage->getUseNewDcSchema()) {
                    $sql = "SELECT sum(wr_pdc) as actPower, wr_temp as temp FROM " . $anlage->getDbNameDCIst() . " WHERE $whereQueryPart1 AND $groupQuery GROUP BY group_ac;";
                } else {
                    $sql = "SELECT sum(wr_pdc) as actPower, wr_temp as temp FROM " . $anlage->getDbNameAcIst() . " WHERE $whereQueryPart1 AND $groupQuery GROUP BY unit;";
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