<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use PDO;
use App\Service\PdoService;
use Symfony\Bundle\SecurityBundle\Security;

class VoltageChartService
{
    use G4NTrait;

    public function __construct(
private readonly PdoService $pdoService,
        private readonly Security $security,
        private readonly AnlagenStatusRepository $statusRepository,
        private readonly InvertersRepository $invertersRepo,
        private readonly IrradiationChartService $irradiationChart,
        private readonly FunctionsService $functions)
    {
    }

    /**
     * Erzeugt Daten für das DC Spannung Diagram Diagramm, eine Linie je Inverter gruppiert nach Gruppen.
     *
     * @param $from
     * @param $to
     * @return array
     * @throws \Exception
     */
    public function getVoltage1(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = $this->pdoService->getPdoPlant();
        $acGroups = $anlage->getGroupsAc();
        $dataArray = [];
        switch ($anlage->getConfigType()) {
            case 1:
            case 3:
            case 4:
                // z.B. Gronningen
                $groupQuery = "group_ac = '$group' ";
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
                break;
            default:
                $groupQuery = "group_dc = '$group' ";
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
        }

        $dataArray['inverterArray'] = $nameArray;
        // SOLL Strom für diesen Zeitraum und diese Gruppe
        $sqlExp = 'SELECT a.stamp as stamp, AVG(b.dc_exp_voltage) as expected
                   FROM (db_dummysoll a LEFT JOIN (SELECT stamp, dc_exp_voltage, group_ac FROM '.$anlage->getDbNameDcSoll()." WHERE $groupQuery) b ON a.stamp = b.stamp)
                   WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";
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
                $stamp = $rowSoll['stamp']; // self::timeShift($anlage, $rowSoll['stamp']);
                $stampAdjust = self::timeAjustment($rowSoll['stamp'], $anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);

                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = $stamp; //self::timeShift($anlage, $stamp);

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
                    $wherePart1 = "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'";
                } else {
                    $wherePart1 = "stamp = '$stampAdjust' ";
                }
                switch ($anlage->getConfigType()) {
                    case 1:
                    case 2:
                        $sql = 'SELECT AVG(wr_udc) as istCurrent, group_dc as dc_num FROM '.$anlage->getDbNameACIst().' WHERE '.$wherePart1." AND $groupQuery group by date_format(stamp, '$form'), group_dc;";
                        break;
                    case 3:
                        $sql = 'SELECT AVG(wr_udc) as istCurrent, wr_num as dc_num FROM '.$anlage->getDbNameDCIst().' WHERE '.$wherePart1." AND $groupQuery group by date_format(stamp, '$form'), wr_num;";
                        break;
                    case 4:
                        $sql = 'SELECT AVG(wr_udc) as istCurrent, wr_num as dc_num FROM '.$anlage->getDbNameDCIst().' WHERE '.$wherePart1." AND $groupQuery group by date_format(stamp, '$form');";
                        break;
                }

                #echo "$sql <br>";

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
     * Erzeugt Daten für das DC Spannung Diagram Diagramm, eine Linie je Inverter gruppiert nach Gruppen.
     *
     * @param $from
     * @param $to
     * @return array
     * @throws \Exception
     */
    public function getVoltageGroups(Anlage $anlage, $from, $to, int $set = 1, bool $hour = false): array
    {
        if ($hour) {
            $form = '%y%m%d%H';
        } else {
            $form = '%y%m%d%H%i';
        }
        $conn = $this->pdoService->getPdoPlant();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];
        // Spannung für diesen Zeitraum und diese Gruppe
        $sql_time = "SELECT stamp FROM db_dummysoll WHERE stamp BETWEEN '$from' AND '$to' GROUP BY date_format(stamp, '$form')";
        $result = $conn->query($sql_time);
        if ($result->rowCount() > 0) {
            $counter = 0;
            while ($rowSoll = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowSoll['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float) $anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = $stamp; // self::timeShift($anlage, $stamp);
                $gruppenProSet = 1;
                foreach ($dcGroups as $dcGroupKey => $dcGroup) {
                    if ($dcGroupKey > (($set - 1) * 10) && $dcGroupKey <= ($set * 10)) {
                        // ermittle Spannung für diese Zeit und diese Gruppe
                        if ($hour) {
                            if ($anlage->getUseNewDcSchema()) {
                                $sql = 'SELECT sum(AVG(wr_udc)) as actVoltage FROM '.$anlage->getDbNameDcIst()." WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND wr_group = '$dcGroupKey' GROUP BY date_format(stamp, '$form')";
                            } else {
                                $sql = 'SELECT sum(AVG(wr_udc)) as actVoltage FROM '.$anlage->getDbNameAcIst()." WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND group_ac = '$dcGroupKey' GROUP BY date_format(stamp, '$form')";
                            }
                        } else {
                            if ($anlage->getUseNewDcSchema()) {
                                $sql = 'SELECT AVG(wr_udc) as actVoltage FROM '.$anlage->getDbNameDcIst()." WHERE stamp = '$stampAdjust' AND wr_group = '$dcGroupKey'";
                            } else {
                                $sql = 'SELECT AVG(wr_udc) as actVoltage FROM '.$anlage->getDbNameAcIst()." WHERE stamp = '$stampAdjust' AND group_ac = '$dcGroupKey'";
                            }
                        }
                        $resultIst = $conn->query($sql);
                        if ($resultIst->rowCount() == 1) {
                            $rowIst = $resultIst->fetch(PDO::FETCH_ASSOC);
                            if ($hour) {
                                $voltageAct = round($rowIst['actVoltage'], 2) / 4;
                            } else {
                                $voltageAct = round($rowIst['actVoltage'], 2);
                            }
                            if (!($voltageAct == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime((string) $stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$gruppenProSet"] = $voltageAct;
                            }
                        }
                        $dataArray['label'][$dcGroupKey] = $dcGroup['GroupName'];
                        $dataArray['maxSeries'] = $gruppenProSet; // count($dcGroups);
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
     * Erzeugt Daten für das DC Spannungs Diagram Diagramm, eine Linie je MPP gruppiert nach Inverter.
     *
     * @param $from
     * @param $to
     * @return array
     * @throws \Exception
     */
    public function getVoltageMpp(Anlage $anlage, $from, $to, int $inverter = 1, bool $hour = false): array
    {
        if ($hour) {
            $form = '%y%m%d%H';
        } else {
            $form = '%y%m%d%H%i';
        }
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;

        // Strom für diesen Zeitraum und diesen Inverter
        // ACHTUNG Strom und Spannungs Werte werden im Moment (Sep2020) immer in der AC Tabelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
        if ($hour) {
            $sql_voltage = 'SELECT a.stamp as stamp, sum(b.wr_mpp_voltage) AS mpp_voltage FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE unit = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP BY date_format(stamp, '$form')";
        } else {
            $sql_voltage = 'SELECT a.stamp as stamp, b.wr_mpp_voltage AS mpp_voltage FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE unit = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
        }
        $result = $conn->query($sql_voltage);

        if ($result) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = self::timeAjustment($row['stamp'], (int) $anlage->getAnlZeitzone(), true);
                    if ($hour) {
                        $mppVoltageJson = $row['mpp_voltage'] / 4;
                    } else {
                        $mppVoltageJson = $row['mpp_voltage'];
                    }
                    if ($mppVoltageJson != '') {
                        $mppvoltageArray = json_decode((string) $mppVoltageJson, null, 512, JSON_THROW_ON_ERROR);
                        // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        $dataArray['chart'][$counter]['date'] = $stamp; // self::timeShift($anlage, $stamp);
                        $mppCounter = 1;
                        foreach ($mppvoltageArray as $mppVoltageItem => $mppVoltageValue) {
                            if (!($mppVoltageValue == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$mppCounter"] = $mppVoltageValue;
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

        }
        $conn = null;
        return $dataArray;
    }
}
