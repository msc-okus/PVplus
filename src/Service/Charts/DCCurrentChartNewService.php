<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use PDO;
use Symfony\Component\Security\Core\Security;

class DCCurrentChartNewService
{
    use G4NTrait;

    public function __construct(
        private Security                $security,
        private AnlagenStatusRepository $statusRepository,
        private InvertersRepository     $invertersRepo,
        private IrradiationChartService $irradiationChart,
        private FunctionsService        $functions)
    {
    }

    /**
     * Current Diagramme
     * Erzeugt Daten für das normale Soll/Ist DC Diagramm.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param bool $hour
     * @return array|null
     * @throws \Exception
     *
     * [Curr1]
     */
    public function getCurr1(Anlage $anlage, $from, $to, bool $hour = false): ?array
    {
        $conn = self::getPdoConnection();
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        $sqlDcSoll = 'SELECT a.stamp as stamp, sum(b.dc_exp_current) as soll
                  FROM (db_dummysoll a left JOIN ' . $anlage->getDbNameDcSoll() . " b ON a.stamp = b.stamp) 
                  WHERE a.stamp BETWEEN '$from' AND '$to' 
                  GROUP by date_format(a.stamp, '$form')";

        $resultExpected = $conn->query($sqlDcSoll);
        $actSum = $expSum = $irrSum = 0;

        // add Irradiation
        if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false || $anlage->getUseCustPRAlgorithm() == 'Groningen') {
            #$dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
        } else {
            #$dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
        }
        $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);

        if ($resultExpected->rowCount() > 0) {
            $counter = 0;
            while ($rowExp = $resultExpected->fetch(PDO::FETCH_ASSOC)) {
                $stamp = self::timeShift($anlage, $rowExp['stamp']);
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
                $whereQueryPart1 = $hour ? "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'" : "stamp = '$stampAdjust'";
                if ($anlage->getUseNewDcSchema()) {
                    $sqlActual = 'SELECT sum(wr_pdc) AS dcIst FROM ' . $anlage->getDbNameDCIst() . " 
                        WHERE $whereQueryPart1 GROUP BY date_format(stamp, '$form')";
                } else {
                    $sqlActual = 'SELECT sum(wr_pdc) AS dcIst FROM ' . $anlage->getDbNameIst() . " 
                        WHERE $whereQueryPart1 GROUP BY date_format(stamp, '$form')";
                }
                dump($sqlActual);
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
                if (!($soll == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['expected'] = $soll;
                    $dataArray['chart'][$counter]['expgood'] = $expdiff;
                }
                if (!(($dcIst === 0 || $dcIst === null) && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
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
                    $irrSum += $hour ? $dataArray['chart'][$counter]['irradiation'] * 4 : $dataArray['chart'][$counter]['irradiation'];
                }
                ++$counter;
            }
            $dataArray['irrSum'] = round($irrSum, 2);
            $dataArray['actSum'] = round($actSum, 2);
            $dataArray['expSum'] = round($expSum, 2);
        }

        $conn = null;
        dump($dataArray);
        return $dataArray;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @param bool $hour
     * @return array
     *               [Curr2]
     * @throws \Exception
     */
    public function getCurr2(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        $nameArray = $this->functions->getNameArray($anlage, 'ac');
        $dataArray['inverterArray'] = $nameArray;
        $acGroups = $anlage->getGroupsAc();
        $type = '';
        $hour ? $form = '%y%m%d%H' : $form = '%y%m%d%H%i';

        $type .= match ($anlage->getConfigType()) {
            1 => " group_ac = '$group' AND",
            default => " group_dc = '$group' AND",
        };

        $sqlExpected = 'SELECT a.stamp as stamp , sum(b.dc_exp_current) as expected
                            FROM (db_dummysoll a LEFT JOIN (SELECT stamp, dc_exp_power, group_ac FROM ' . $anlage->getDbNameDcSoll() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp)
                            WHERE a.stamp BETWEEN '$from' AND '$to'
                            GROUP by date_format(a.stamp, '$form')";

        $conn = self::getPdoConnection();
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
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            }

            $expectedArray = $resultExp->fetchAll(PDO::FETCH_ASSOC);
            foreach ($expectedArray as $rowExp) {
                $stamp = $rowExp['stamp'];
                $rowExp['expected'] === null || $rowExp['expected'] < 0 ? $expected = 0 : $expected = $rowExp['expected'];
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $rowExp['stamp']);
                $counterInv = 1;
                if ($hour) {
                    $endStamp = date('Y-m-d H:i', strtotime($stamp) + 3600);
                    $sqlIst = 'SELECT sum(wr_idc) as istCurrent FROM ' . $anlage->getDbNameIst() . ' WHERE ' . $type . " stamp >= '$stamp' AND  stamp < '$endStamp' group by unit ORDER BY unit";
                } else {
                    $sqlIst = 'SELECT wr_idc as istCurrent FROM ' . $anlage->getDbNameIst() . ' WHERE ' . $type . " stamp = '$stamp' ORDER BY unit";
                }
                $resultActual = $conn->query($sqlIst);
                while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {
                    $actCurrent = (float)$rowActual['istCurrent'] > 0 ? (float)$rowActual['istCurrent'] : 0;

                    if (!($actCurrent == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
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
                ($counterInv > 0) ? $dataArray['chart'][$counter]['expected'] = $expected / $counterInv : $dataArray['chart'][$counter]['expected'] = $expected;

                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
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
     * Erzeugt Daten für das Soll/Ist AC Diagramm nach Gruppen.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @param bool $hour
     * @return array
     * @throws \Exception [Curr3]
     */
    public function getCurr3(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        if ($group == -1) {
            if ($hour) {
                $form = '%y%m%d%H';
            } else {
                $form = '%y%m%d%H%i';
            }

            $conn = self::getPdoConnection();
            $groups = $anlage->getGroupsAc();
            $dataArray = [];
            $inverterNr = 0;
            switch ($anlage->getConfigType()) {
                case 3:
                case 4:
                    $nameArray = $this->functions->getNameArray($anlage, 'ac');
                    break;
                default:
                    $nameArray = $this->functions->getNameArray($anlage, 'dc');
            }

            $sqlExp = 'SELECT a.stamp as stamp, sum(b.dc_exp_current) as expected
                        FROM (db_dummysoll a LEFT JOIN (SELECT stamp, dc_exp_power, group_ac FROM ' . $anlage->getDbNameDcSoll() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp)
                        WHERE a.stamp BETWEEN '$from' AND '$to' 
                        GROUP BY date_format(a.stamp, '$form')";

            if ($anlage->getUseNewDcSchema()) {
                $sql = 'SELECT sum(wr_idc) as istCurrent 
                                    FROM (db_dummysoll a LEFT JOIN ' . $anlage->getDbNameDCIst() . " b ON a.stamp = b.stamp)
                                    WHERE a.stamp BETWEEN '$from' AND '$to' 
                                    GROUP BY date_format(a.stamp, '$form'), b.group_ac ";
            } else {
                $sql = 'SELECT sum(wr_idc) as istCurrent 
                                    FROM (db_dummysoll a LEFT JOIN  ' . $anlage->getDbNameACIst() . " b ON a.stamp = b.stamp)
                                    WHERE a.stamp BETWEEN '$from' AND '$to' 
                                    GROUP BY date_format(a.stamp, '$form'), group_dc ";
            }

            $resultExp = $conn->query($sqlExp);
            $resultActual = $conn->query($sql);

            $maxInverter = $resultActual->rowCount() / $resultExp->rowCount();

            $dataArray['inverterArray'] = $nameArray;

            if ($resultExp->rowCount() > 0) {
                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false || $anlage->getUseCustPRAlgorithm() == 'Groningen') {
                    $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper');
                } else {
                    $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to);
                }
                // SOLL Strom für diesen Zeitraum und diese Gruppe

                $dataArray['maxSeries'] = 0;
                $legend = $groups[$group]['GMIN'] - 1;
                $counter = 0;
                while ($rowExp = $resultExp->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = $rowExp['stamp'];
                    ($rowExp['soll'] == null) ? $expected = 0 : $expected = $rowExp['soll'];
                    $counterInv = 1;
                    $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                    $dataArray['chart'][$counter]['expected'] = $expected;
                    while ($counterInv <= $maxInverter) {
                        $rowActual = $resultActual->fetch(PDO::FETCH_ASSOC);

                        $currentIst = $rowActual['istCurrent'];
                        if ($currentIst != null) {
                            $currentIst = round($rowActual['istCurrent'], 2);
                        } else {
                            $currentIst = 0;
                        }

                        if (!(self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            $dataArray['chart'][$counter][$nameArray[$counterInv]] = $currentIst;
                        }
                        ++$counterInv;
                    }
                    $dataArray['maxSeries'] = $maxInverter;
                    // add Irradiation
                    if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                        $dataArray['chart'][$counter]['irradiation'] = $dataArrayIrradiation['chart'][$counter]['val1'];
                    } else {
                        $dataArray['chart'][$counter]['irradiation'] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                    }
                    ++$counter;
                }
                $dataArray['offsetLegend'] = 0;
            }
        } else {
            $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
            $conn = self::getPdoConnection();
            $dataArray = [];
            $nameArray = $this->functions->getNameArray($anlage, 'dc');

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
            $sqlExpected = 'SELECT a.stamp, sum(b.soll_pdcwr) as soll 
            FROM (db_dummysoll a left JOIN (SELECT * FROM ' . $anlage->getDbNameDcSoll() . " WHERE $groupQuery) b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
            $dataArray['inverterArray'] = $nameArray;
            $result = $conn->query($sqlExpected);
            $maxInverter = 0;
            // add Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
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
                foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $rowExp) {
                    $stamp = $rowExp['stamp'];
                    $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                    $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                    $anzInvPerGroup = $groups[$group]['GMAX'] - $groups[$group]['GMIN'] + 1;

                    $expected = $rowExp['soll'];
                    if ($expected !== null) {
                        $expected = $expected > 0 ? $expected : 0;
                        switch ($anlage->getConfigType()) {
                            case 1:
                            case 2:
                                $expected = $anzInvPerGroup > 0 ? $expected / $anzInvPerGroup : $expected;
                                break;
                        }
                        if ($expected < 0) {
                            $expected = 0;
                        }
                    }

                    // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                    if (!($expected == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                        $dataArray['chart'][$counter]['expected'] = $expected;
                    }
                    $whereQueryPart1 = $hour ? "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'" : "stamp = '$stampAdjust'";
                    if ($anlage->getUseNewDcSchema()) {
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
                            if ($rowIst['temp'] == null) {
                                $temperature = 0;
                            } else {
                                $temperature = $rowIst['temp'];
                            }
                            $dataArray['chart'][$counter]['temperature'] = $temperature;
                            $actPower = self::checkUnitAndConvert($rowIst['actPower'], $anlage->getAnlDbUnit());
                            if (!($actPower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
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

}