<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use PDO;
use App\Service\PdoService;

class DCPowerChartService
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly AnlagenStatusRepository $statusRepository,
        private readonly InvertersRepository $invertersRepo,
        private readonly IrradiationChartService $irradiationChart,
        private readonly FunctionsService $functions)
    {
    }

    /**
     * DC Diagramme
     * Erzeugt Daten für das normale Soll/Ist DC Diagramm.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param bool $hour
     * @return array|null
     * @throws \Exception
     *
     * [DC1]
     */
    public function getDC1(Anlage $anlage, $from, $to, bool $hour = false): ?array
    {
        $conn = $this->pdoService->getPdoPlant();
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        if ($hour) {
            $exppart1 = "DATE_FORMAT(DATE_ADD(a.stamp, INTERVAL 45 MINUTE), '%Y-%m-%d %H:%i:00') AS stamp,";
            $exppart2 = "GROUP by date_format(DATE_SUB(a.stamp, INTERVAL 15 MINUTE), '$form')";
        } else {
            $exppart1 = 'a.stamp as stamp, ';
            $exppart2 = "GROUP by date_format(a.stamp, '$form')";
        }
        $sqlExpected = "SELECT 
                            $exppart1
                            sum(b.soll_pdcwr) as soll
                        FROM (db_dummysoll a left JOIN ".$anlage->getDbNameDcSoll()." b ON a.stamp = b.stamp) 
                        WHERE a.stamp > '$from' AND a.stamp <= '$to' 
                        $exppart2";

        $resultExpected = $conn->query($sqlExpected);
        $actSum = $expSum = $irrSum = 0;

        $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);

        if ($resultExpected->rowCount() > 0) {
            $counter = 0;
            while ($rowExp = $resultExpected->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowExp['stamp']; //self::timeShift($anlage, $rowExp['stamp']);
                $stampAdjust = self::timeAjustment($rowExp['stamp'], $anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                $soll = round($rowExp['soll'], 2);
                if ($rowExp['soll'] !== null) {
                    $soll = $soll > 0 ? $soll : 0;
                    $expdiff = round($soll - $soll * 10 / 100, 2); // -10% good
                } else {
                    $expdiff = null;
                }
                // start query actual
                if ($hour) {
                    $stampAdjust = self::timeAjustment($rowExp['stamp'], $anlage->getAnlZeitzone()-1);
                    $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                    $whereQueryPart1 = "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'";
                } else {
                    $stampAdjust = self::timeAjustment($rowExp['stamp'], $anlage->getAnlZeitzone());
                    $whereQueryPart1 = "stamp = '$stampAdjust'";
                }
                if ($anlage->getUseNewDcSchema()) {
                    $sqlActual = 'SELECT 
                                    sum(wr_pdc) AS dcIst 
                                  FROM '.$anlage->getDbNameDCIst()." 
                                  WHERE $whereQueryPart1 
                                  GROUP BY date_format(stamp, '$form')";
                } else {
                    $sqlActual = 'SELECT 
                                    sum(wr_pdc) AS dcIst 
                                  FROM '.$anlage->getDbNameIst()." 
                                  WHERE $whereQueryPart1 
                                  GROUP BY date_format(stamp, '$form')";
                }
                $resActual = $conn->query($sqlActual);
                if ($resActual->rowCount() == 1) {
                    $rowActual = $resActual->fetch(PDO::FETCH_ASSOC);
                    $dcIst = $rowActual['dcIst'];
                    $dcIst = $dcIst > 0 ? round($dcIst, 2) : 0; // neagtive Werte auschließen

                    $actSum += $dcIst;
                } else {
                    $dcIst = null;
                }
                $expSum += $soll;

                $dataArray['chart'][$counter]['date'] = $stamp;
                if (!($soll == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime((string) $stamp) < 7200)) {
                    $dataArray['chart'][$counter]['expected'] = $soll;
                    $dataArray['chart'][$counter]['expgood'] = $expdiff;
                }
                if (!(($dcIst === 0 || $dcIst === null) && self::isDateToday($stamp) && self::getCetTime() - strtotime((string) $stamp) < 7200)) {
                    $dataArray['chart'][$counter]['InvOut'] = $dcIst;
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
                    $irrSum += $hour ? $dataArray['chart'][$counter]['irradiation'] : $dataArray['chart'][$counter]['irradiation'] / 4;
                }
                ++$counter;
            }
            $dataArray['irrSum'] = round($irrSum, 2);
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
     * @throws \Exception
     *
     *  [DC2]
     */
    public function getDC2(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        $nameArray = $this->functions->getNameArray($anlage, 'ac');
        $dataArray['inverterArray'] = $nameArray;
        $acGroups = $anlage->getGroupsAc();
        $hour ? $form = '%y%m%d%H' : $form = '%y%m%d%H%i';

        $type = match ($anlage->getConfigType()) {
            1 => " group_ac = '$group' AND ",
            default => " group_dc = '$group' AND ",
        };
        if ($hour) {
            $part1 = "DATE_FORMAT(DATE_ADD(a.stamp, INTERVAL 45 MINUTE), '%Y-%m-%d %H:%i:00') AS stamp,";
            $part2 = "GROUP by date_format(DATE_SUB(a.stamp, INTERVAL 15 MINUTE), '$form')";
        } else {
            $part1 = 'a.stamp as stamp, ';
            $part2 = "GROUP by date_format(a.stamp, '$form')";
        }
        $sqlExpected = "SELECT 
                            $part1 
                            sum(b.dc_exp_power) as expected
                        FROM (db_dummysoll a LEFT JOIN (SELECT stamp, dc_exp_power, group_ac FROM ".$anlage->getDbNameDcSoll()." WHERE group_ac = '$group') b ON a.stamp = b.stamp)
                        WHERE a.stamp > '$from' AND a.stamp <= '$to'
                        $part2";

        $conn = $this->pdoService->getPdoPlant();
        $resultExp = $conn->query($sqlExpected);
        if ($resultExp->rowCount() > 0) {
            $counter = 0;

            switch ($anlage->getConfigType()) {
                case 3: // Groningen
                    break;
                default:
                    $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
            }
            $dataArray['label'] = $acGroups[$group]['GroupName'];

            // get Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() === false) {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            }

            $expectedArray = $resultExp->fetchAll(PDO::FETCH_ASSOC);
            foreach ($expectedArray as $rowExp) {
                $stamp = $rowExp['stamp'];
                $rowExp['expected'] === null || $rowExp['expected'] < 0 ? $expected = 0 : $expected = $rowExp['expected'];
                $dataArray['chart'][$counter]['date'] = $rowExp['stamp']; //self::timeShift($anlage, $rowExp['stamp']);
                $counterInv = 1;
                if ($hour) {
                    $stampAdjust = self::timeAjustment($rowExp['stamp'], $anlage->getAnlZeitzone()-1);
                    $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                    $queryf = "stamp > '$stampAdjust' AND stamp <= '$stampAdjust2'";
                } else {
                    $stampAdjust = self::timeAjustment($rowExp['stamp'], $anlage->getAnlZeitzone());
                    $queryf = "stamp = '$stampAdjust'";
                }
                $sqlIst = "SELECT 
                              wr_pdc as istCurrent 
                           FROM ".$anlage->getDbNameIst()."
                           WHERE $type $queryf
                           ORDER BY unit";
                $resultActual = $conn->query($sqlIst);
                while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {
                    $actCurrent = max((float)$rowActual['istCurrent'], 0);

                    if (!($actCurrent == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime((string) $stamp) < 7200)) {
                        switch ($anlage->getConfigType()) {
                            case 3: // Groningen
                                $dataArray['chart'][$counter][$nameArray[$group]] = $actCurrent;
                                break;
                            default:
                                $dataArray['chart'][$counter][$nameArray[$counterInv + $dataArray['offsetLegend']]] = $actCurrent;
                        }
                    }

                    switch ($anlage->getConfigType()) {
                        case 3:
                        case 1:
                            if ($counterInv > $dataArray['maxSeries']) {
                                $dataArray['maxSeries'] = $counterInv;
                            }
                            break;
                        default:
                            if ($counterInv > $dataArray['maxSeries']) {
                                $dataArray['maxSeries'] = $counterInv - 1;
                            }
                    }

                    ++$counterInv;
                }

                // and here
                --$counterInv;
                $dataArray['chart'][$counter]['expected'] = $counterInv > 0 ? $expected / $counterInv : $expected;

                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() === false) {
                    $dataArray['chart'][$counter]['irradiation'] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]['irradiation'] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                }
                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das Soll/Ist AC Diagramm nach Inverter (Typ 1 und 2) / Gruppen (Typ 3 und 4).
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @param bool $hour
     * @return array
     * @throws \Exception
     *
     * [DC3]
     */
    public function getDC3(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];

        $groups = $anlage->getGroupsDc();
        switch ($anlage->getConfigType()) {
            case 3:
            case 4:
                // z.B. Gronningen
                $groupQuery = "group_ac = '$group' ";
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                break;
            default:
                $groupQuery = "group_dc = '$group' ";
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
        }
        if ($hour) {
            $exppart1 = "DATE_FORMAT(DATE_ADD(a.stamp, INTERVAL 45 MINUTE), '%Y-%m-%d %H:%i:00') AS stamp,";
            $exppart2 = "GROUP by date_format(DATE_SUB(a.stamp, INTERVAL 15 MINUTE), '$form')";
        } else {
            $exppart1 = 'a.stamp as stamp, ';
            $exppart2 = "GROUP by date_format(a.stamp, '$form')";
        }

        if ($group === -1) { // Select all Inverter
            $sqlExp = "SELECT 
                          $exppart1
                          sum(b.dc_exp_power) as expected
                       FROM (db_dummysoll a LEFT JOIN (SELECT stamp, dc_exp_power, group_ac FROM ".$anlage->getDbNameDcSoll().") b ON a.stamp = b.stamp)
                       WHERE a.stamp > '$from' AND a.stamp <= '$to' 
                       $exppart2";

            if ($anlage->getConfigType() === 3 || $anlage->getConfigType() === 4) {
                $sql = 'SELECT sum(wr_pdc) as istDc 
                                    FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameDCIst()." b ON a.stamp = b.stamp)
                                    WHERE a.stamp > '$from' AND a.stamp <= '$to' 
                                    GROUP BY date_format(a.stamp, '$form'), b.group_ac ";
            } else {
                $sql = 'SELECT sum(wr_pdc) as istDc 
                                    FROM (db_dummysoll a LEFT JOIN  '.$anlage->getDbNameACIst()." b ON a.stamp = b.stamp)
                                    WHERE a.stamp > '$from' AND a.stamp <= '$to' 
                                    GROUP BY date_format(a.stamp, '$form'), group_dc ";
            }

            $resultExp = $conn->query($sqlExp);
            $resultActual = $conn->query($sql);

            $maxInverter = $resultActual->rowCount() / $resultExp->rowCount();

            $dataArray['inverterArray'] = $nameArray;

            if ($resultExp->rowCount() > 0) {
                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() === false) {
                    $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
                } else {
                    $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
                }
                // SOLL Strom für diesen Zeitraum und diese Gruppe

                $dataArray['maxSeries'] = 0;
                $legend = $groups[$group]['GMIN'] - 1;
                $counter = 0;

                while ($rowExp = $resultExp->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = $rowExp['stamp'];
                    $dataArray['chart'][$counter]['date'] = $stamp;
                    // expected hidden, because make no sense to show a single expectewd for all inverter
                    // $dataArray['chart'][$counter]['expected'] = $rowExp['expected'] == null ? 0 : $rowExp['expected'];

                    if ($hour) {
                        $stampAdjust = self::timeAjustment($rowExp['stamp'], $anlage->getAnlZeitzone() - 1);
                        $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                        $whereQueryPart1 = "stamp > '$stampAdjust' AND stamp <= '$stampAdjust2'";
                        $groupBy = "date_format(DATE_SUB(stamp, INTERVAL 15 MINUTE), '$form'), ";
                    } else {
                        $stampAdjust = self::timeAjustment($rowExp['stamp'], $anlage->getAnlZeitzone());
                        $whereQueryPart1 = "stamp = '$stampAdjust'";
                        $groupBy = "";
                    }
                    if ($anlage->getConfigType() === 3 || $anlage->getConfigType() === 4) {
                        $sql = 'SELECT stamp, sum(wr_pdc) as actPower, avg(wr_temp) as temp FROM ' . $anlage->getDbNameDCIst() . " WHERE $whereQueryPart1 GROUP BY $groupBy group_ac ;";
                    } else {
                        $sql = 'SELECT stamp, sum(wr_pdc) as actPower, avg(wr_temp) as temp FROM ' . $anlage->getDbNameAcIst() . " WHERE $whereQueryPart1 GROUP BY $groupBy unit;";
                    }


                    $resultIst = $conn->query($sql);
                    if ($resultIst->rowCount() > 0) {
                        $counterInv = 0;
                        $sumTemp = 0;
                        while ($rowIst = $resultIst->fetch(PDO::FETCH_ASSOC)) {
                            ++$counterInv;
                            $sumTemp += (float)$rowIst['temp'];
                            $actPower = $rowIst['actPower'];
                            if (!($actPower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime((string)$stamp) < 7200)) {
                                switch ($anlage->getConfigType()) {
                                    case 3: // Groningen, Saran
                                        $dataArray['chart'][$counter][$nameArray[$group]] = $actPower;
                                        break;
                                    default:
                                        $dataArray['chart'][$counter][$nameArray[$counterInv]] = $actPower;
                                }
                            }
                            switch ($anlage->getConfigType()) {
                                case 3:
                                case 4:
                                    if ($counterInv > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counterInv;
                                    break;
                                default:
                                    if ($counterInv > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counterInv - 1;
                            }
                        }
                        $dataArray['chart'][$counter]['temperature'] = $sumTemp / $counterInv;

                    }

                    $dataArray['maxSeries'] = $counterInv;
                    // add Irradiation
                    if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() === false) {
                        $dataArray['chart'][$counter]['irradiation'] = $dataArrayIrradiation['chart'][$counter]['val1'];
                    } else {
                        $dataArray['chart'][$counter]['irradiation'] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                    }
                    ++$counter;
                }
                $dataArray['offsetLegend'] = 0;
            }
        }
        else {
            $sqlExpected = "SELECT 
                                $exppart1 
                                sum(b.soll_pdcwr) as expected 
                            FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE $groupQuery) b ON a.stamp = b.stamp) 
                            WHERE a.stamp > '$from' AND a.stamp <= '$to' 
                            $exppart2";

            $dataArray['inverterArray'] = $nameArray;
            $result = $conn->query($sqlExpected);
            $maxInverter = 0;
            // add Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() === false) {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            }
            if ($result->rowCount() > 0) {
                $dataArray['maxSeries'] = 0;
                $counter = 0;
                $dataArray['offsetLegend'] = match ($anlage->getConfigType()) {
                    3, 4 => $group - 1,
                    default => $groups[$group]['GMIN'] - 1,
                };
                foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $rowExp) {
                    $stamp = $rowExp['stamp'];
                    $anzInvPerGroup = $groups[$group]['GMAX'] - $groups[$group]['GMIN'] + 1;
                    $expected = $rowExp['expected'];
                    if ($expected !== null) {
                        $expected = max($expected, 0);
                        if ($anlage->getConfigType() === 1 || $anlage->getConfigType() === 2) {
                            $expected = $anzInvPerGroup > 0 ? $expected / $anzInvPerGroup : $expected;
                        }
                        if ($expected < 0) $expected = 0;
                    }

                    // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    $dataArray['chart'][$counter]['date'] = $stamp; // self::timeShift($anlage, $stamp);
                    if (!($expected == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime((string)$stamp) < 7200)) {
                       $dataArray['chart'][$counter]['expected'] = $expected;
                    }
                    if ($hour) {
                        $stampAdjust = self::timeAjustment($rowExp['stamp'], $anlage->getAnlZeitzone() - 1);
                        $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                        $whereQueryPart1 = "stamp > '$stampAdjust' AND stamp <= '$stampAdjust2'";
                    } else {
                        $stampAdjust = self::timeAjustment($rowExp['stamp'], $anlage->getAnlZeitzone());
                        $whereQueryPart1 = "stamp = '$stampAdjust'";
                    }
                    if ($anlage->getConfigType() === 3 || $anlage->getConfigType() === 4) {
                        $sql = 'SELECT sum(wr_pdc) as actPower, wr_temp as temp FROM ' . $anlage->getDbNameDCIst() . " WHERE $whereQueryPart1 AND $groupQuery GROUP BY group_ac;";
                    } else {
                        $sql = 'SELECT sum(wr_pdc) as actPower, wr_temp as temp FROM ' . $anlage->getDbNameAcIst() . " WHERE $whereQueryPart1 AND $groupQuery GROUP BY unit;";
                    }

                    $resultIst = $conn->query($sql);
                    $counterInv = 1;
                    if ($resultIst->rowCount() > 0) {
                        while ($rowIst = $resultIst->fetch(PDO::FETCH_ASSOC)) {
                            if ($counterInv > $maxInverter) {
                                $maxInverter = $counterInv;
                            }
                            $dataArray['chart'][$counter]['temperature'] = $rowIst['temp'] === null ? 0 : (float)$rowIst['temp'];

                            $actPower = $rowIst['actPower'];
                            if (!($actPower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime((string)$stamp) < 7200)) {
                                switch ($anlage->getConfigType()) {
                                    case 3: // Groningen, Saran
                                        $dataArray['chart'][$counter][$nameArray[$group]] = $actPower;
                                        break;
                                    default:
                                        $dataArray['chart'][$counter][$nameArray[$counterInv + $dataArray['offsetLegend']]] = $actPower;
                                        ++$counterInv;
                                }
                            }
                            switch ($anlage->getConfigType()) {
                                case 3:
                                case 4:
                                    if ($counterInv > $dataArray['maxSeries']) {
                                        $dataArray['maxSeries'] = $counterInv;
                                    }
                                    break;
                                default:
                                    if ($counterInv > $dataArray['maxSeries']) {
                                        $dataArray['maxSeries'] = $counterInv - 1;
                                    }
                            }
                        }
                    } else {
                        for ($counterInv = 1; $counterInv <= $maxInverter; ++$counterInv) {
                            switch ($anlage->getConfigType()) {
                                case 3: // Groningen
                                    $dataArray['chart'][$counter][$nameArray[$group]] = 0;
                                    break;
                                default:
                                    $dataArray['chart'][$counter][$nameArray[$counterInv + $dataArray['offsetLegend']]] = 0;
                            }
                        }
                    }
                    // add Irradiation
                    if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                        $dataArray['chart'][$counter]['irradiation'] = $dataArrayIrradiation['chart'][$counter]['val1'];
                    } else {
                        $dataArray['chart'][$counter]['irradiation'] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                    }
                    ++$counter;
                }
            }
        }

        $conn = null;

        return $dataArray;
    }

    /**
     * erzeugt Daten für Gruppen Leistungs Unterschiede Diagramm (Group Power Difference).
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     *
     * @return array
     *
     * [DC4] DC - Inverter / DC - Inverter Group // dc_grp_power_diff Bar Chart
     */
    public function getGroupPowerDifferenceDC(Anlage $anlage, $from, $to): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];
        $istGruppenArray = [];
        $dcGroups = $anlage->getGroupsDc();
        // IST Leistung für diesen Zeitraum nach Gruppen gruppiert
        if ($anlage->getUseNewDcSchema()) {
            $sqlIst = 'SELECT sum(wr_pdc) as power_dc_ist, wr_group as inv_group FROM '.$anlage->getDbNameDCIst()." WHERE stamp > '$from' AND stamp <= '$to' GROUP BY wr_group ;";
        } else {
            $sqlIst = 'SELECT sum(wr_pdc) as power_dc_ist, group_dc as inv_group FROM '.$anlage->getDbNameAcIst()." WHERE stamp > '$from' AND stamp <= '$to' GROUP BY group_dc ;";
        }
        $resultIst = $conn->query($sqlIst);
        while ($rowIst = $resultIst->fetch(PDO::FETCH_ASSOC)) { // Speichern des SQL ergebnisses in einem Array, Gruppe ist assosiativer Array Index
            $istGruppenArray[$rowIst['inv_group']] = $rowIst['power_dc_ist'];
        }
        // SOLL Leistung für diesen Zeitraum nach Gruppen gruppiert
        $sql_soll = 'SELECT stamp, sum(soll_pdcwr) as soll, group_dc as inv_group FROM '.$anlage->getDbNameDcSoll()." 
                         WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_dc ORDER BY group_dc * 1"; // 'wr_num * 1' damit die Sortierung als Zahl und nicht als Text erfolgt

        $result = $conn->query($sql_soll);
        $counter = 0;
        if ($result->rowCount() > 0) {
            $dataArray['maxSeries'] = 0;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $dataArray['rangeValue'] = round($row['soll'], 2);
                $invGroupSoll = $row['inv_group'];
                $dataArray['chart'][$counter] = [
                    'category' => $dcGroups[$invGroupSoll]['GroupName'],
                    'link' => $invGroupSoll,
                    'exp' => round($row['soll'], 2),
                ];
                $dataArray['chart'][$counter]['act'] = round($istGruppenArray[$invGroupSoll], 2);
                if ($counter > $dataArray['maxSeries']) {
                    $dataArray['maxSeries'] = $counter;
                }
                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * erzeugt Daten für Inverter Leistungs Unterschiede Diagramm (Inverter Power Difference).
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $group
     * @return array
     *
     * DC - Inverter // dc_inv_power_diff
     */
    public function getInverterPowerDifference(Anlage $anlage, $from, $to, $group): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];

        if (self::isDateToday($to)) {
            // letzten Eintrag in IST DB ermitteln
            $res = $conn->query('SELECT stamp FROM '.$anlage->getDbNameIst()." WHERE stamp > '$from' ORDER BY stamp DESC LIMIT 1");
            if ($res) {
                $rowTemp = $res->fetch(PDO::FETCH_ASSOC);
                $lastRecStampAct = strtotime((string) $rowTemp['stamp']);
                $res = null;

                // letzten Eintrag in  Weather DB ermitteln
                $res = $conn->query('SELECT stamp FROM '.$anlage->getDbNameDcSoll()." WHERE stamp > '$from' ORDER BY stamp DESC LIMIT 1");
                if ($res) {
                    $rowTemp = $res->fetch(PDO::FETCH_ASSOC);
                    $lastRecStampExp = strtotime((string) $rowTemp['stamp']);
                    $res = null;
                    ($lastRecStampAct <= $lastRecStampExp) ? $toLastBoth = self::formatTimeStampToSql($lastRecStampAct) : $toLastBoth = self::formatTimeStampToSql($lastRecStampExp);
                    $to = $toLastBoth;
                }
            }
        }

        // Leistung für diesen Zeitraum und diese Gruppe
        $sql_soll = 'SELECT stamp, sum(soll_pdcwr) as soll FROM '.$anlage->getDbNameDcSoll()." WHERE stamp > '$from' AND stamp <= '$to' AND wr_num = '$group' GROUP BY wr LIMIT 1";
        $result = $conn->query($sql_soll);
        $counter = 0;
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $dataArray['rangeValue'] = round($row['soll'], 2);
                $dataArray['chart'][] = [
                    'category' => 'expected',
                    'val' => round($row['soll'], 2),
                    'color' => '#fdd400',
                ];
                if ($anlage->getUseNewDcSchema()) {
                    $sqlInv = 'SELECT sum(wr_pdc) as dcinv, wr_num AS inverter FROM '.$anlage->getDbNameDCIst()." WHERE stamp > '$from' AND stamp <= '$to' AND wr_group = '$group' GROUP BY wr_num";
                } else {
                    $sqlInv = 'SELECT sum(wr_pdc) as dcinv, unit AS inverter FROM '.$anlage->getDbNameAcIst()." WHERE stamp > '$from' AND stamp <= '$to' AND group_ac = '$group' GROUP BY unit";
                }
                $resultInv = $conn->query($sqlInv);
                if ($resultInv->rowCount() > 0) {
                    $wrcounter = 0;
                    while ($rowInv = $resultInv->fetch(PDO::FETCH_ASSOC)) {
                        ++$wrcounter;
                        $inverter = $rowInv['inverter'];
                        $dataArray['chart'][] = [
                            'category' => "Inverter #$inverter",
                            'val' => $rowInv['dcinv'],
                            'link' => "$inverter",
                        ];
                        if ($wrcounter > $dataArray['maxSeries']) {
                            $dataArray['maxSeries'] = $wrcounter;
                        }
                    }
                }
                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }
}
