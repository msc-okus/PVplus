<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Entity\AnlageGroupModules;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use PDO;
use App\Service\PdoService;

class DCCurrentChartService
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly AnlagenStatusRepository $statusRepository,
        private readonly InvertersRepository     $invertersRepo,
        private readonly IrradiationChartService $irradiationChart,
        private readonly FunctionsService        $functions)
    {
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @param bool $hour
     * @return array
     * @throws \Exception
     */
    public function getCurr1(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $acGroups = $anlage->getGroupsAc();
        $groupQuery = "group_ac = '$group' ";
        $nameArray = $this->functions->getNameArray($anlage, 'dc');
        $dataArray['inverterArray'] = $nameArray;
        if ($hour) {
            $exppart1 = "DATE_FORMAT(DATE_ADD(a.stamp, INTERVAL 45 MINUTE), '%Y-%m-%d %H:%i:00') AS stamp,";
            $exppart2 = "GROUP by date_format(DATE_SUB(a.stamp, INTERVAL 15 MINUTE), '$form')";
        } else {
            $exppart1 = 'a.stamp as stamp, ';
            $exppart2 = "GROUP by date_format(a.stamp, '$form')";
        }
        // SOLL Strom für diesen Zeitraum und diese Gruppe
        $sqlExp = "SELECT 
                     $exppart1
                     sum(b.dc_exp_current) as expected
                   FROM (db_dummysoll a LEFT JOIN (SELECT stamp, dc_exp_current, group_ac FROM " . $anlage->getDbNameDcSoll() . " WHERE $groupQuery) b ON a.stamp = b.stamp)
                   WHERE a.stamp > '$from' AND a.stamp <= '$to' 
                   $exppart2";
        
        $result = $conn->query($sqlExp);
        $expectedResult = $result->fetchAll(PDO::FETCH_ASSOC);

        $invertersInGroup = ($acGroups[$group]['GMAX'] - $acGroups[$group]['GMIN']) + 1;
        $dataArray['minSeries'] = $acGroups[$group]['GMIN'];
        $dataArray['maxSeries'] = $acGroups[$group]['GMAX'];

        if ($result->rowCount() > 0) {
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            $dataArray['sumSeries'] = $invertersInGroup;
            $counter = 0;
            foreach ($expectedResult as $rowSoll) {
                $stamp = $rowSoll['stamp'];
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = $stamp; //self::timeShift($anlage, $stamp);
                #$dataArray['chart'][$counter]['expectedUR'] = $rowSoll['expected'];

                if (!(($rowSoll['expected'] == 0) && (self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200))) {
                    if ($rowSoll['expected'] > 0) {
                        switch ($anlage->getConfigType()) {
                            case 1:
                            case 2:
                                $dataArray['chart'][$counter]['expected'] = $rowSoll['expected'] / $invertersInGroup;
                                $dataArray['chart'][$counter]['expected'] = $hour ? $dataArray['chart'][$counter]['expected'] / 4 : $dataArray['chart'][$counter]['expected'];
                                break;
                            default:
                                $dataArray['chart'][$counter]['expected'] = $hour ? $rowSoll['expected'] / $invertersInGroup / 4 : $rowSoll['expected'] / $invertersInGroup;
                        }
                        $dataArray['chart'][$counter]['expected'] = round($dataArray['chart'][$counter]['expected'], 2);
                    }
                }

                if ($hour) {
                    $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone() - 1);
                    $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                    $wherePart1 = "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'";
                } else {
                    $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                    $wherePart1 = "stamp = '$stampAdjust' ";
                }
                switch ($anlage->getConfigType()) {
                    case 1:
                    case 2:
                        $sql = 'SELECT 
                                    sum(wr_idc) as istCurrent, 
                                    group_dc as dc_num 
                                FROM ' . $anlage->getDbNameACIst() . ' 
                                WHERE ' . $wherePart1 . " AND $groupQuery 
                                GROUP BY date_format(stamp, '$form'), group_dc;";
                        break;
                    case 3:
                        $sql = 'SELECT sum(wr_idc) as istCurrent, wr_num as dc_num FROM ' . $anlage->getDbNameDCIst() . ' WHERE ' . $wherePart1 . " AND $groupQuery group by date_format(stamp, '$form'), wr_num;";
                        break;
                    case 4:
                        $sql = 'SELECT sum(wr_idc) as istCurrent, wr_num as dc_num FROM ' . $anlage->getDbNameDCIst() . ' WHERE ' . $wherePart1 . " AND $groupQuery group by date_format(stamp, '$form');";
                        break;
                }

                $resultAct = $conn->query($sql);

                while ($rowAct = $resultAct->fetch(PDO::FETCH_ASSOC)) {
                    $currentAct = $hour ? $rowAct['istCurrent'] / 4 : $rowAct['istCurrent'];
                    $currentAct = round($currentAct, 2);
                    if (!($currentAct == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime((string) $stamp) < 7200)) {
                        $dataArray['chart'][$counter][$nameArray[$rowAct['dc_num']]] = $currentAct;
                    }
                }
                // Irradiation
                if (isset($dataArrayIrradiation['chart'][$counter]['val1'])) {
                    if ($anlage->getIsOstWestAnlage()) {
                        $dataArray['chart'][$counter]['irradiation'] = ($dataArrayIrradiation['chart'][$counter]['val1'] * $anlage->getPowerEast() + $dataArrayIrradiation['chart'][$counter]['val2'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
                    } else {
                        if ($anlage->getShowOnlyUpperIrr() || !$anlage->getWeatherStation()->getHasLower()) {
                            $dataArray['chart'][$counter]['irradiation'] = $dataArrayIrradiation['chart'][$counter]['val1'];
                        } else {
                            $dataArray['chart'][$counter]['irradiation'] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                        }
                    }
                }
                ++$counter;
                $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
            }
        }
        $conn = null;
        return $dataArray;
    }

    /**
     * Erzeugt Daten für das DC Strom Diagram Diagramm, eine Linie je Gruppe.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $set
     * @param bool $hour
     * @return array
     *
     */
    public function getCurr2(Anlage $anlage, $from, $to, int $set = 1, bool $hour = false): array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = $this->pdoService->getPdoPlant();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];

        // Strom für diesen Zeitraum und diese Gruppe
        $sql_time = "SELECT stamp FROM db_dummysoll WHERE stamp > '$from' AND stamp <= '$to' GROUP BY date_format(stamp, '$form')";
        $result = $conn->query($sql_time);
        if ($result->rowCount() > 0) {
            $counter = 0;
            while ($rowSoll = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowSoll['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = $stamp; //self::timeShift($anlage, $stamp);
                $gruppenProSet = 1;
                foreach ($dcGroups as $dcGroupKey => $dcGroup) {
                    if ($dcGroupKey > (($set - 1) * 10) && $dcGroupKey <= ($set * 10)) {
                        // ermittle SOLL Strom nach Gruppen für diesen Zeitraum
                        // ACHTUNG Strom und Spannungswerte werden im Moment (Sep2020) immer in der AC TAbelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
                        if ($hour) {
                            if ($anlage->getUseNewDcSchema()) {
                                $sql = 'SELECT sum(wr_idc) as istCurrent FROM ' . $anlage->getDbNameDCIst() . " WHERE stamp > '$stampAdjust' AND stamp =< '$stampAdjust2' AND wr_group = '$dcGroupKey' GROUP BY date_format(stamp, '$form')";
                            } else {
                                $sql = 'SELECT sum(wr_idc) as istCurrent FROM ' . $anlage->getDbNameACIst() . " WHERE stamp > '$stampAdjust' AND stamp =< '$stampAdjust2'  AND group_dc = '$dcGroupKey' GROUP BY date_format(stamp, '$form')";
                            }
                        } else {
                            if ($anlage->getUseNewDcSchema()) {
                                $sql = 'SELECT sum(wr_idc) as istCurrent FROM ' . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_group = '$dcGroupKey'";
                            } else {
                                $sql = 'SELECT sum(wr_idc) as istCurrent FROM ' . $anlage->getDbNameACIst() . " WHERE stamp = '$stampAdjust' AND group_dc = '$dcGroupKey'";
                            }
                        }
                        $resultIst = $conn->query($sql);

                        if ($resultIst->rowCount() > 0) {
                            $rowIst = $resultIst->fetch(PDO::FETCH_ASSOC);
                            $currentIst = round($rowIst['istCurrent'], 2);
                            if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime((string) $stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$gruppenProSet"] = $currentIst;
                            }
                        }
                        $dataArray['maxSeries'] = $gruppenProSet;
                        $dataArray['label'][$dcGroupKey] = $dcGroup['GroupName'];
                        ++$gruppenProSet;
                    }
                }
                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das DC Strom Diagram Diagramm, eine Linie je Inverter gruppiert nach Gruppen.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @param bool $hour
     * @return array
     *
     * @throws \Exception
     */
    public function getCurr3(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = $this->pdoService->getPdoPlant();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        $nameArray = match ($anlage->getConfigType()) {
            1 => $this->functions->getNameArray($anlage, 'dc'),
            default => $this->functions->getNameArray($anlage, 'ac'),
        };
        $dataArray['inverterArray'] = $nameArray;

        // Strom für diesen Zeitraum und diesen Inverter
        $sql_strom = 'SELECT a.stamp as stamp, b.soll_imppwr as sollCurrent 
                  FROM (db_dummysoll a left JOIN (SELECT * FROM ' . $anlage->getDbNameDcSoll() . " WHERE wr_num = '$group') b ON a.stamp = b.stamp) 
                  WHERE a.stamp > '$from' AND a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";
        $result = $conn->query($sql_strom);

        if ($result->rowCount() > 0) {
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() === false) {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            }
            $counter = 0;
            $dataArray['offsetLegend'] = $dcGroups[$group]['GMIN'] - 1;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $row['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);

                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = $stamp; //self::timeShift($anlage, $stamp);

                $currentExp = $row['sollCurrent'] > 0 ? round($row['sollCurrent'], 2) : 0.0;

                if (!($currentExp == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime((string) $stamp) < 7200)) {
                    $dataArray['chart'][$counter]['soll'] = $currentExp;
                }
                $mppCounter = 0;

                for ($inverter = $dcGroups[$group]['GMIN']; $inverter <= $dcGroups[$group]['GMAX']; ++$inverter) {
                    ++$mppCounter;
                    if ($hour) {
                        if ($anlage->getUseNewDcSchema()) {
                            $sql = 'SELECT sum(wr_idc) as istCurrent FROM ' . $anlage->getDbNameDCIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND wr_num = '$inverter' GROUP BY date_format(stamp, '$form')";
                        } else {
                            $sql = 'SELECT sum(wr_idc) as istCurrent FROM ' . $anlage->getDbNameAcIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND unit = '$inverter' GROUP BY date_format(stamp, '$form')";
                        }
                    } else {
                        if ($anlage->getUseNewDcSchema()) {
                            $sql = 'SELECT wr_idc as istCurrent FROM ' . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_num = '$inverter' GROUP BY date_format(stamp, '$form')";
                        } else {
                            $sql = 'SELECT wr_idc as istCurrent FROM ' . $anlage->getDbNameAcIst() . " WHERE stamp = '$stampAdjust' AND unit = '$inverter' GROUP BY date_format(stamp, '$form')";
                        }
                    }

                    $resultIst = $conn->query($sql);
                    if ($resultIst->rowCount() > 0) {
                        $rowIst = $resultIst->fetch(PDO::FETCH_ASSOC);

                        $currentIst = round($rowIst['istCurrent'], 2);
                        if ($hour) {
                            $currentIst = $currentIst / 4;
                        }
                        if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime((string) $stamp) < 7200)) {
                            // $dataArray['chart'][$counter]["val$mppCounter"] = $currentIst;
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
                if ($mppCounter > $dataArray['maxSeries']) {
                    $dataArray['maxSeries'] = $mppCounter;
                }

                if (isset($dataArrayIrradiation['chart'][$counter]['val1'])) {
                    if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                        $dataArray['chart'][$counter]['irradiation'] = $dataArrayIrradiation['chart'][$counter]['val1'];
                    } else {
                        $dataArray['chart'][$counter]['irradiation'] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                    }
                }
                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das DC Strom Diagram, eine Linie je MPP gruppiert nach Inverter.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int|null $inverter
     * @param bool $hour
     * @return bool|array // dc_current_mpp
     *                    // dc_current_mpp
     * @throws \JsonException
     */
    public function getCurr4(Anlage $anlage, $from, $to, ?int $inverter = 1, bool $hour = false): bool|array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;

        // Strom für diesen Zeitraum und diesen Inverter
        // ACHTUNG Strom und Spannungs Werte werden im Moment (Sep2020) immer in der AC Tabelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.

        if ($hour) {
            if ($anlage->getUseNewDcSchema()) {
                $sql_strom = 'SELECT a.stamp as stamp, sum(b.wr_mpp_current) AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM ' . $anlage->getDbNameDCIst() . " WHERE wr_num = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";
            } else {
                $sql_strom = 'SELECT a.stamp as stamp, sum(b.wr_mpp_current) AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM ' . $anlage->getDbNameAcIst() . " WHERE unit = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";
            }
        } else {
            if ($anlage->getUseNewDcSchema()) {
                $sql_strom = 'SELECT a.stamp as stamp, b.wr_mpp_current AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM ' . $anlage->getDbNameDCIst() . " WHERE wr_num = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
            } else {
                $sql_strom = 'SELECT a.stamp as stamp, b.wr_mpp_current AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM ' . $anlage->getDbNameAcIst() . " WHERE unit = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
            }
        }
        $result = $conn->query($sql_strom);
        if ($result) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = self::timeAjustment($row['stamp'], (int)$anlage->getAnlZeitzone(), true);
                    // $stamp = $row['stamp'];
                    $mppCurrentJson = $row['mpp_current'];
                    if ($mppCurrentJson != '') {
                        $mppCurrentArray = json_decode((string) $mppCurrentJson, null, 512, JSON_THROW_ON_ERROR);
                        // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        $dataArray['chart'][$counter]['date'] = $stamp; //self::timeShift($anlage, $stamp);
                        $mppCounter = 1;
                        foreach ($mppCurrentArray as $mppCurrentItem => $mppCurrentValue) {
                            if (!($mppCurrentValue == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$mppCounter"] = $hour ? $mppCurrentValue / 4 : $mppCurrentValue;
                            }
                            ++$mppCounter;
                        }
                        if ($mppCounter > $dataArray['maxSeries']) {
                            $dataArray['maxSeries'] = $mppCounter;
                        }
                        ++$counter;
                    }
                }
            }
            $conn = null;

            return $dataArray;
        } else {
            $conn = null;

            return false;
        }
    }

    /**
     * Erzeugt die Daten für den Pnom Power Chart auf der DC Seite
     * MS 02/23 update 03/29
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param string|null $sets
     * @param int $group
     * @param bool $hour
     * @return array
     * Pnom DC Seite
     *
     * DC
     */
    public function getNomCurrentGroupDC(Anlage $anlage, $from, $to, ?string $sets = '0', int $group = 1, bool $hour = false): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = $nameArray = [];
        $counter = 0;

        /*
         * ToDo: Macht das Sinn ? Mann kann dan nicht mehrer Tage auswählen
         *
        $gmt_offset = 1;   // Unterschied von GMT zur eigenen Zeitzone in Stunden.
        $zenith = 90 + 50 / 60;
        $current_date = strtotime(str_replace("T", "", (string) $from));
        $sunInfo = date_sun_info($current_date, (float)$anlage->getAnlGeoLat(), (float)$anlage->getAnlGeoLon());
        $sunset = $sunInfo['sunset'];
        $sunrise = $sunInfo['sunrise'];
        $from = date('Y-m-d H:00', $sunrise - 3600);
        $to = date('Y-m-d H:00', $sunset + 5400);
        */

        $from = self::timeAjustment($from, $anlage->getAnlZeitzone());
        $to = self::timeAjustment($to, 1);

        $dcGroups = $anlage->getGroupsDc();
        $groupct = count($dcGroups);

        $groupdc = match ($anlage->getConfigType()) {
            3, 4 => 'wr_group',
            default => 'group_dc',
        };

        /** @var AnlageGroupModules[] $modules */
        foreach ($anlage->getGroups() as $group) {
            $modules = $group->getModules();
            $mImppTemp = 0;
            foreach ($modules as $modul) {
                $mImppTemp += ($modul->getModuleType()->getMaxImpp() * $modul->getNumStringsPerUnit());
            }
            $mImpp[] = $mImppTemp;
        }

        if ($groupct) {
            if ($sets === null) {
                $min = 1;
                $max = ($groupct > 100 ? (int)ceil($groupct / 10) : (int)ceil(($groupct + 0.5) / 2));
                $max = (($max > 50) ? '50' : $max);
                $sqladd = "AND $groupdc BETWEEN '$min' AND '$max'";
            } else {
                $res = explode(',', (string) $sets);
                $min = (int)ltrim($res[0], "[");
                $max = (int)rtrim($res[1], "]");
                $max > $groupct ? $max = $groupct : $max = $max;
                $groupct > $min ? $min = $min : $min = 1;
                $sqladd = "AND $groupdc BETWEEN " . (empty($min) ? '1' : $min) . " AND " . (empty($max) ? '5' : $max) . " ";
            }
        } else {
            $min = 1;
            $max = 5;
            $sqladd = "AND $groupdc BETWEEN '$min' AND ' $max'";
        }
        //
        if ($anlage->getUseNewDcSchema()) {
            $sql = "SELECT stamp as ts, wr_idc as istCurrent, $groupdc as inv FROM " . $anlage->getDbNameDCIst() . " WHERE 
                        stamp > '$from' AND stamp <= '$to' 
                        $sqladd
                        GROUP BY stamp, $groupdc ORDER BY NULL";
        } else {
            $sql = "SELECT stamp as ts, wr_idc as istCurrent, $groupdc as inv FROM " . $anlage->getDbNameACIst() . " WHERE 
                        stamp > '$from' AND stamp <= '$to' 
                        $sqladd
                        GROUP BY stamp, $groupdc ORDER BY NULL";
        }
        $resultIst = $conn->query($sql);

        foreach ($dcGroups as $key => $group) {
            $nameArray[$key] = $group['GroupName'];
        }

        if ($resultIst->rowCount() > 0) {
            while ($rowCurrIst = $resultIst->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowCurrIst['ts'];
                $dataArray['chart'][$counter]['date'] = $stamp;
                $currentIst = $rowCurrIst['istCurrent'] ? round($rowCurrIst['istCurrent'], 2) : 0;
                $currentGroupName = $dcGroups[$rowCurrIst['inv']]['GroupName'];
                $currentImpp = $mImpp[$rowCurrIst['inv'] - 1]; // the array beginn at zero
                $value_dcpnom = $currentImpp > 0 ? round(($currentIst / $currentImpp), 2) : 0;
                $dataArray['chart'][$counter]['xinv'] = $currentGroupName;
                $dataArray['chart'][$counter]['pnomdc'] = $value_dcpnom;
                ++$counter;
            }
        }
        // the array for range slider min max
        $dataArray['minSeries'] = $min;
        $dataArray['maxSeries'] = $max;
        $dataArray['sumSeries'] = $groupct;
        $dataArray['SeriesNameArray'] = $nameArray;
        $dataArray['offsetLegend'] = 0;
        // The generated data Array for the range slider and Chart
        return $dataArray;
    }
}