<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use PDO;
use App\Service\GetPdoService;

class ACPowerChartsService
{
    use G4NTrait;

    public function __construct(
private GetPdoService $getPdoService,
        private AnlagenStatusRepository $statusRepository,
        private InvertersRepository $invertersRepo,
        private IrradiationChartService $irradiationChart,
        private FunctionsService $functions)
    {
    }

    /**
     * Erzeugt Daten für das normale Soll/Ist AC Diagramm.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param bool $hour
     * @return array
     * @throws \Exception
     */
    public function getAC1(Anlage $anlage, $from, $to, bool $hour = false): array
    {
        ini_set('memory_limit', '3G');
        set_time_limit(500);
        $conn = $this->getPdoService->getPdoPlant();
        $formExp = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $form = $hour ? '%y%m%d%' : '%y%m%d%H%i';

        if ($anlage->getHasPPC()) {
            $sqlExp = 'SELECT a.stamp as stamp, 
                        sum(b.ac_exp_power) as soll, 
                        sum(b.ac_exp_power_evu) as soll_evu, 
                        sum(b.ac_exp_power_no_limit) as soll_nolimit,
                        c.p_ac_inv,c.pf_set,c.p_set_gridop_rel,c.p_set_rel,c.p_set_rpc_rel,c.q_set_rel,c.p_set_ctrl_rel,c.p_set_ctrl_rel_mean
                        FROM db_dummysoll a 
                        LEFT JOIN '.$anlage->getDbNameDcSoll().' b ON a.stamp = b.stamp
                        LEFT JOIN '.$anlage->getDbNamePPC()." c ON a.stamp = c.stamp
                        WHERE a.stamp >= '$from' AND a.stamp < '$to' 
                        GROUP by date_format(a.stamp, '$formExp')";
        } else {
            $sqlExp = 'SELECT a.stamp as stamp, 
                        sum(b.ac_exp_power) as soll, 
                        sum(b.ac_exp_power_evu) as soll_evu, 
                        sum(b.ac_exp_power_no_limit) as soll_nolimit
                        FROM db_dummysoll a 
                        LEFT JOIN '.$anlage->getDbNameDcSoll()." b ON a.stamp = b.stamp                     
                        WHERE a.stamp >= '$from' AND a.stamp <= '$to' 
                        GROUP by date_format(a.stamp, '$formExp')";
        }
        $resExp = $conn->query($sqlExp);
        $actSum = $expSum = $expEvuSum = $expNoLimitSum = $evuSum = $cosPhiSum = $theoPowerSum = $irrSum = 0;
        $dataArray = [];

        if ($resExp->rowCount() > 0) {
            $counter = 0;
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            while ($rowExp = $resExp->fetch(PDO::FETCH_ASSOC)) {
                $stamp = self::timeShift($anlage, $rowExp['stamp']);
                $stampAdjust = self::timeAjustment($rowExp['stamp'], $anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);

                $expectedInvOut     = $rowExp['soll'] > 0 ? round($rowExp['soll'], 2) : 0; // neagtive Werte auschließen
                $expectedEvu        = $rowExp['soll_evu'] == null || $rowExp['soll_evu'] < 0 ? 0 : round($rowExp['soll_evu'], 2);
                $expectedNoLimit    = $rowExp['soll_nolimit'] == null || $rowExp['soll_nolimit'] < 0 ? 0 : round($rowExp['soll_nolimit'], 2);
                $expDiffInvOut      = round($expectedInvOut - $expectedInvOut * 10 / 100, 2);   // Minus 10 % Toleranz Invberter Out.
                $expDiffEvu         = round($expectedEvu - $expectedEvu * 10 / 100, 2);         // Minus 10 % Toleranz Grid (EVU).

                $whereQueryPart1 = $hour ? "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'" : "stamp = '$stampAdjust'";
                $sqlActual = 'SELECT sum(wr_pac) as acIst, wr_cos_phi_korrektur as cosPhi, sum(theo_power) as theoPower FROM '.$anlage->getDbNameIst()." 
                        WHERE wr_pac >= 0 AND $whereQueryPart1 GROUP by date_format(stamp, '$form')";

                $sqlEvu = 'SELECT sum(e_z_evu) as eZEvu FROM '.$anlage->getDbNameIst()." WHERE $whereQueryPart1 and unit = 1 GROUP by date_format(stamp, '$form')";

                $resActual = $conn->query($sqlActual);
                $resEvu = $conn->query($sqlEvu);

                if ($resActual->rowCount() == 1) {
                    $rowActual = $resActual->fetch(PDO::FETCH_ASSOC);
                    $cosPhi = abs((float) $rowActual['cosPhi']);
                    $acIst = $rowActual['acIst'];
                    $acIst > 0 ? $actout = round($acIst, 2) : $actout = 0; // neagtive Werte auschließen
                    $theoPower = $rowActual['theoPower'];
                    $cosPhiSum += $cosPhi * $acIst;
                    $actSum += $actout;
                    $theoPowerSum += $theoPower;
                } else {
                    $cosPhi = $actout = $theoPower = null;
                }
                if ($resEvu->rowCount() == 1) {
                    $rowEvu = $resEvu->fetch(PDO::FETCH_ASSOC);
                    $eZEvu = $rowEvu['eZEvu']; # / $anlage->getAnzInverterFromGroupsAC();
                    $eZEvu = max($eZEvu, 0);
                    $evuSum += $eZEvu;
                } else {
                    $eZEvu = null;
                }
                $expSum += $expectedInvOut;
                $expEvuSum += $expectedEvu;
                $expNoLimitSum += $expectedNoLimit;
                $dataArray['chart'][$counter]['date'] = $stamp;
                if ($anlage->getHasPPC()) {

                    // Hack für Olli um Duerrenried richtig anzuzeigen, muss durch generiche PPC Lösung erstetzt werden
                    // 'else Zweig' funktioniert für Bavelse
                    if ($anlage->getAnlId() == 111) {
                        $dataArray['chart'][$counter]['p_set_rpc_rel'] = $rowExp['p_set_rel'];
                        $dataArray['chart'][$counter]['p_set_gridop_rel'] = null;
                    } else {
                        $dataArray['chart'][$counter]['p_set_rpc_rel'] = $rowExp['p_set_rpc_rel'];
                        $dataArray['chart'][$counter]['p_set_gridop_rel'] = $rowExp['p_set_gridop_rel'];
                    }
                }

                if (!($expectedInvOut == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['expected'] = $expectedInvOut;
                    $dataArray['chart'][$counter]['expgood'] = $expDiffInvOut;
                    if ($anlage->getShowEvuDiag()) {
                        $dataArray['chart'][$counter]['expexted_evu'] = $expectedEvu;
                        $dataArray['chart'][$counter]['expexted_evu_good'] = $expDiffEvu;
                    }
                    $dataArray['chart'][$counter]['expexted_no_limit'] = $expectedNoLimit;
                }
                if (!(($actout === 0 || $actout === null) && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    if ($anlage->getShowInverterOutDiag()) {
                        $dataArray['chart'][$counter]['InvOut'] = $actout;
                    }
                    if ($anlage->getShowEvuDiag()) {
                        $dataArray['chart'][$counter]['eZEvu'] = $eZEvu;
                    }
                    if ($anlage->getShowCosPhiPowerDiag()) {
                        $dataArray['chart'][$counter]['cosPhi'] = $cosPhi * $actout;
                    }
                    $dataArray['chart'][$counter]['theoPower'] = $theoPower;
                    if ($anlage->getShowCosPhiDiag()) {
                        $dataArray['chart'][$counter]['cosPhi'] = $cosPhi * 100;
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
                    $irrSum += $hour ? $dataArray['chart'][$counter]['irradiation'] : $dataArray['chart'][$counter]['irradiation'] / 4;
                }
                ++$counter;
            }
            $dataArray['irrSum'] = round($irrSum , 2);
            $dataArray['actSum'] = round($actSum, 2);
            $dataArray['expSum'] = round($expSum, 2);
            $dataArray['expEvuSum'] = round($expEvuSum, 2);
            $dataArray['theoPowerSum'] = round($theoPowerSum, 2);
            $dataArray['expNoLimitSum'] = round($expNoLimitSum, 2);
            $dataArray['evuSum'] = round($evuSum, 2);
            $dataArray['cosPhiSum'] = round($cosPhiSum, 2);
            $conn = null;

            return $dataArray;
        } else {
            $conn = null;

            return [];
        }
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
    public function getAC2(Anlage $anlage, $from, $to, int $group, bool $hour = false): array
    {
        ini_set('memory_limit', '3G');
        set_time_limit(500);
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        $nameArray = $this->functions->getNameArray($anlage, 'ac');
        $dataArray['inverterArray'] = $nameArray;
        $acGroups = $anlage->getGroupsAc();
        $type = '';
        $hour ? $form = '%y%m%d%H' : $form = '%y%m%d%H%i';

        switch ($anlage->getConfigType()) {
            case 1:
                $type .= " group_ac = '$group' AND";
                break;
            default:
                $type .= " group_dc = '$group' AND";
        }

        $sqlExpected = 'SELECT a.stamp , sum(b.ac_exp_power) as soll
                            FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameDcSoll()." WHERE group_ac = '$group') b ON a.stamp = b.stamp)
                            WHERE a.stamp BETWEEN '$from' AND '$to'
                            GROUP by date_format(a.stamp, '$form')";

        $conn = $this->getPdoService->getPdoPlant();
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
                $rowExp['soll'] == null || $rowExp['soll'] < 0 ? $expected = 0 : $expected = $rowExp['soll'];
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $rowExp['stamp']);
                $counterInv = 1;
                if ($hour) {
                    $endStamp = date('Y-m-d H:i', strtotime($stamp) + 3600);
                    $sqlIst = 'SELECT sum(wr_pac) as actPower, wr_cos_phi_korrektur as cosPhi FROM '.$anlage->getDbNameIst().' WHERE '.$type." stamp >= '$stamp' AND  stamp < '$endStamp' group by unit ORDER BY unit";
                } else {
                    $sqlIst = 'SELECT wr_pac as actPower, wr_cos_phi_korrektur as cosPhi FROM '.$anlage->getDbNameIst().' WHERE '.$type." stamp = '$stamp' ORDER BY unit";
                }
                $resultActual = $conn->query($sqlIst);
                while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {
                    $actPower = $rowActual['actPower'];
                    ($actPower > 0) ? $actPower = self::checkUnitAndConvert($actPower, $anlage->getAnlDbUnit()) : $actPower = 0;

                    if (!($actPower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                        switch ($anlage->getConfigType()) {
                            case 3: // Groningen
                                $dataArray['chart'][$counter][$nameArray[$group]] = $actPower;
                                break;
                            default:
                                $dataArray['chart'][$counter][$nameArray[$counterInv + $dataArray['offsetLegend']]] = $actPower;
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

                    if ($anlage->getShowCosPhiDiag()) {
                        $dataArray['chart'][$counter]['cosPhi'] = abs((float) $rowActual['wr_cos_phi_korrektur']);
                    }
                    ++$counterInv;
                }

                // and here
                --$counterInv;
                ($counterInv > 0) ? $dataArray['chart'][$counter]['expected'] = $expected / $counterInv : $dataArray['chart'][$counter]['exp'] = $expected;
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
     * @throws \Exception
     */
    public function getAC3(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        ini_set('memory_limit', '3G');
        set_time_limit(500);
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';

        $conn = $this->getPdoService->getPdoPlant();
        $groupID = 1;
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        switch ($anlage->getConfigType()) {
            case 1 :
                $groupQuery = "group_dc = '$group'";
                $groups = $anlage->getGroupsDc();
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
                break;

            default:
                $groupQuery = "group_ac = '$group'";
                $groups = $anlage->getGroupsAc();
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
        }

        $sqlIst = 'SELECT a.stamp, sum(c.wr_pac) as actPower, avg(c.wr_temp) as temp, c.wr_cos_phi_korrektur FROM ( `db_dummysoll` a 
                 LEFT JOIN (SELECT * FROM '.$anlage->getDbNameIst().' WHERE '.$groupQuery."  ) c ON a.stamp = c.stamp ) WHERE a.stamp 
                 BETWEEN '$from' AND '$to' GROUP BY date_format(a.stamp, '$form')";

        $dataArray['inverterArray'] = $nameArray;
        $resultIst = $conn->query($sqlIst);

        if ($resultIst->rowCount() > 0) {
            $counter = 0;
            switch ($anlage->getConfigType()) {
                case 1:
                    $dataArray['offsetLegend'] = $groups[$group]['GMIN'] - 1;
                    $groupID = $dataArray['maxSeries'] + $dataArray['offsetLegend'];
                    break;
                case 2 :
                    $dataArray['offsetLegend'] = $groups[$group]['GMIN'] - 1 ;
                    $groupID = $dataArray['maxSeries'] + $dataArray['offsetLegend'] + 1;
                    break;
                case 3: // Groningen
                case 4:
                    $dataArray['offsetLegend'] = $group - 1;
                    break;
                default:
                    $dataArray['offsetLegend'] = $groups[$group]['GMIN'] - 1;
            }
            // add Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() === false) {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            }

            $dataArray['label'] = $groups[$group]['GroupName'];

            while ($rowIst = $resultIst->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowIst['stamp'];
                $stampAdjust = self::timeAjustment($stamp, $anlage->getAnlZeitzone() * (-1));
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);

                $dataArray['chart'][$counter]['date'] = self::timeAjustment(self::timeShift($anlage, $rowIst['stamp']), $anlage->getAnlZeitzone() * (-1));

                $queryf = $hour ? "stamp BETWEEN '$stampAdjust' AND '$stampAdjust2'" : "stamp = '$stampAdjust'";
                $sqlSoll = "SELECT stamp, sum(ac_exp_power) as soll FROM ".$anlage->getDbNameDcSoll()." WHERE $queryf AND $groupQuery GROUP BY date_format(stamp, '$form')";

                $result = $conn->query($sqlSoll);
                if ($hour) {
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        if ($rowIst['stamp'] == $row['stamp']) {
                            $row['soll'] == null ? $expected = 0 : $expected = $row['soll'];
                        }
                    }
                } else {
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        ($row['soll'] == null || $row['soll'] < 0) ? $expected = 0 : $expected = $row['soll'];
                    }
                }

                $dataArray['maxSeries'] = 1;
                $dataArray['chart'][$counter]['temperature'] = $rowIst['temp'] == null ? null : $rowIst['temp'];
                $actPower = $rowIst['actPower'];
                if ($actPower !== null) {
                    if ($actPower > 0){
                        $actPower =  round($actPower, ($actPower > 1) ? 2 : 6);
                    } else {
                        $actPower = 0;
                    }
                    #$actPower = $actPower > 0 ? round($actPower, 6) : 0; // neagtive Werte auschließen
                }

                switch ($anlage->getConfigType()) {
                    case 2:
                        $dataArray['chart'][$counter][$nameArray[$groupID]] = $actPower;
                        break;
                    case 3: // Groningen
                    case 4:
                        $dataArray['chart'][$counter][$nameArray[$group]] = $actPower;
                        break;
                    default:
                        $dataArray['chart'][$counter][$nameArray[$group]] = $actPower;
                }

                if ($anlage->getShowCosPhiDiag()) {
                    $dataArray['chart'][$counter]['cosPhi'] = abs((float) $rowIst['wr_cos_phi_korrektur']);
                }

                $dataArray['chart'][$counter]['expected'] = (float) $expected;

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
     * erzeugt Daten für Gruppen Leistungsunterschiede Diagramm (Group Power Difference)
     * AC - Inverter.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     *
     * @return array|null AC4
     *
     * AC4
     */
    public function getGroupPowerDifferenceAC(Anlage $anlage, $from, $to): ?array
    {
        ini_set('memory_limit', '3G');
        set_time_limit(500);
        $conn = $this->getPdoService->getPdoPlant();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();

        // Strom für diesen Zeitraum und diese Gruppe
        $sql_soll = 'SELECT stamp, sum(ac_exp_power) as soll, group_ac as inv_group FROM '.$anlage->getDbNameDcSoll()." WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_ac ORDER BY group_ac * 1"; // 'wr_num * 1' damit die Sortierung als Zahl und nicht als Text erfolgt
        $sqlInv = 'SELECT sum(wr_pac) as acinv, group_ac as inv_group FROM '.$anlage->getDbNameIst()." WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_ac ORDER BY group_ac * 1;";
        $result = $conn->query($sql_soll);
        $resultInv = $conn->query($sqlInv);
        $counter = 0;
        $wrcounter = 0;
        if ($result->rowCount() > 0) {
            $dataArray['maxSeries'] = 0;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $dataArray['rangeValue'] = round($row['soll'], 2);
                $invGroupSoll = $row['inv_group'];
                $dataArray['chart'][$counter] = [
                    'category' => $acGroups[$invGroupSoll]['GroupName'],
                    'link' => $invGroupSoll,
                    'exp' => round($row['soll'], 2),
                ];
                if ($rowInv = $resultInv->fetch(PDO::FETCH_ASSOC)) {
                    ++$wrcounter;
                    $dataArray['chart'][$counter]['act'] = self::checkUnitAndConvert($rowInv['acinv'], $anlage->getAnlDbUnit());
                    if ($wrcounter > $dataArray['maxSeries']) {
                        $dataArray['maxSeries'] = $wrcounter;
                    }
                }

                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für Ist Spannung AC Diagramm nach Gruppen.
     *
     * @param $from
     * @param $to
     *
     * @return array
     *               AC - Actual, Groups
     */
    public function getActVoltageGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): ?array
    {
        ini_set('memory_limit', '3G');
        set_time_limit(500);
        if ($hour) {
            $form = '%y%m%d%H';
        } else {
            $form = '%y%m%d%H%i';
        }
        $conn = $this->getPdoService->getPdoPlant();
        $dataArray = [];
        switch ($anlage->getConfigType()) {
            case 1:
                $acGroups = $anlage->getGroupsDc();
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
                // Spannung für diesen Zeitraum und diese Gruppe
                $sql = 'SELECT a.stamp, sum(b.u_ac) as uac_ist, sum(b.u_ac_p1) as u_ac_p1, sum(b.u_ac_p2) as u_ac_p2,  sum(b.u_ac_p3) as u_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY  date_format(a.stamp, '$form')";
                break;
            default:
                $acGroups = $anlage->getGroupsAc();
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                // Spannung für diesen Zeitraum und diese Gruppe
                $sql = 'SELECT a.stamp, sum(b.u_ac) as uac_ist, sum(b.u_ac_p1) as u_ac_p1, sum(b.u_ac_p2) as u_ac_p2,  sum(b.u_ac_p3) as u_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY  date_format(a.stamp, '$form')";
        }

        if ($hour) {
            switch ($anlage->getConfigType()) {
                case 1:
                    $acGroups = $anlage->getGroupsDc();
                    $nameArray = $this->functions->getNameArray($anlage, 'dc');
                    // Spannung für diesen Zeitraum und diese Gruppe
                    $sql = 'SELECT a.stamp, sum(b.u_ac) as uac_ist, sum(b.u_ac_p1) as u_ac_p1, sum(b.u_ac_p2) as u_ac_p2,  sum(b.u_ac_p3) as u_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by  date_format(a.stamp, '$form')";
                    break;
                default:
                    $acGroups = $anlage->getGroupsAc();
                    $nameArray = $this->functions->getNameArray($anlage, 'ac');
                    // Spannung für diesen Zeitraum und diese Gruppe
                    $sql = 'SELECT a.stamp, sum(b.u_ac) as uac_ist, sum(b.u_ac_p1) as u_ac_p1, sum(b.u_ac_p2) as u_ac_p2,  sum(b.u_ac_p3) as u_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by  date_format(a.stamp, '$form')";
            }
        }

        $dataArray['inverterArray'] = $nameArray;
        $result = $conn->query($sql);
        $counter = 0;
        $counterInv = 0;
        $dataArray['maxSeries'] = 0;
        $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
        $dataArray['label'] = $acGroups[$group]['GroupName'];

        if ($result->rowCount() > 0) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                ++$counterInv;
                if ($counterInv > $maxInverter) {
                    $maxInverter = $counterInv;
                }
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $stamp = $row['stamp'];

                if ($hour) {
                    $dataArray['chart'][$counter] = [
                        // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        'date' => self::timeShift($anlage, $stamp),
                        'u_ac' => round($row['uac_ist'], 2) / 4,
                        'u_ac_phase1' => round($row['u_ac_p1'], 2) / 4,
                        'u_ac_phase2' => round($row['u_ac_p2'], 2) / 4,
                        'u_ac_phase3' => round($row['u_ac_p3'], 2) / 4,
                    ];
                } else {
                    $dataArray['chart'][$counter] = [
                        // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        'date' => self::timeShift($anlage, $stamp),
                        'u_ac' => round($row['uac_ist'], 2),
                        'u_ac_phase1' => round($row['u_ac_p1'], 2),
                        'u_ac_phase2' => round($row['u_ac_p2'], 2),
                        'u_ac_phase3' => round($row['u_ac_p3'], 2),
                    ];
                }
                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für Ist Strom AC Diagramm nach Gruppen.
     *
     * @param $from
     * @param $to
     *
     * @return array
     *               AC - Actual, Groups
     */
    public function getActCurrentGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): ?array
    {
        ini_set('memory_limit', '3G');
        set_time_limit(500);
        if ($hour) {
            $form = '%y%m%d%H';
        } else {
            $form = '%y%m%d%H%i';
        }
        $conn = $this->getPdoService->getPdoPlant();
        $dataArray = [];
        switch ($anlage->getConfigType()) {
            case 1:
                $acGroups = $anlage->getGroupsDc();
                // Strom für diesen Zeitraum und diese Gruppe
                $sql = 'SELECT a.stamp, sum(b.i_ac) as iac_sum, sum(b.i_ac_p1) as i_ac_p1, sum(b.i_ac_p2) as i_ac_p2,  sum(b.i_ac_p3) as i_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                break;
            default:
                $acGroups = $anlage->getGroupsAc();
                // Strom für diesen Zeitraum und diese Gruppe
                $sql = 'SELECT a.stamp, sum(b.i_ac) as iac_sum, sum(b.i_ac_p1) as i_ac_p1, sum(b.i_ac_p2) as i_ac_p2,  sum(b.i_ac_p3) as i_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
        }
        if ($hour) {
            switch ($anlage->getConfigType()) {
                case 1:
                    $acGroups = $anlage->getGroupsDc();
                    // Strom für diesen Zeitraum und diese Gruppe
                    $sql = 'SELECT a.stamp, sum(b.i_ac) as iac_sum, sum(b.i_ac_p1) as i_ac_p1, sum(b.i_ac_p2) as i_ac_p2,  sum(b.i_ac_p3) as i_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                    break;
                default:
                    $acGroups = $anlage->getGroupsAc();
                    // Strom für diesen Zeitraum und diese Gruppe
                    $sql = 'SELECT a.stamp, sum(b.i_ac) as iac_sum, sum(b.i_ac_p1) as i_ac_p1, sum(b.i_ac_p2) as i_ac_p2,  sum(b.i_ac_p3) as i_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
            }
        }

        $result = $conn->query($sql);
        $counter = 0;
        $dataArray['maxSeries'] = 0;
        $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
        $dataArray['label'] = $acGroups[$group]['GroupName'];
        $counterInv = 1;
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if ($counterInv > $maxInverter) {
                    $maxInverter = $counterInv;
                }
                $stamp = $row['stamp'];
                if ($hour) {
                    $dataArray['chart'][$counter] = [
                        // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        'date' => self::timeShift($anlage, $stamp),

                        'i_ac_sum' => round($row['iac_sum'], 2) / 4,
                        'i_ac_phase1' => round($row['i_ac_p1'], 2) / 4,
                        'i_ac_phase2' => round($row['i_ac_p2'], 2) / 4,
                        'i_ac_phase3' => round($row['i_ac_p3'], 2) / 4,
                    ];
                } else {
                    $dataArray['chart'][$counter] = [
                        // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        'date' => self::timeShift($anlage, $stamp),

                        'i_ac_sum' => round($row['iac_sum'], 2),
                        'i_ac_phase1' => round($row['i_ac_p1'], 2),
                        'i_ac_phase2' => round($row['i_ac_p2'], 2),
                        'i_ac_phase3' => round($row['i_ac_p3'], 2),
                    ];
                }
                ++$counterInv;
                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für Ist Frequenz AC Diagramm nach Gruppen.
     *
     * @param $from
     * @param $to
     *
     * @return array
     *               AC - Actual, Groups
     */
    public function getActFrequncyGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): ?array
    {
        ini_set('memory_limit', '3G');
        set_time_limit(500);
        if ($hour) {
            $form = '%y%m%d%H';
        } else {
            $form = '%y%m%d%H%i';
        }
        $conn = $this->getPdoService->getPdoPlant();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        if ($hour) {
            switch ($anlage->getConfigType()) {
                case 1:
                    $acGroups = $anlage->getGroupsDc();
                    // Frequenz für diesen Zeitraum und diese Gruppe
                    $sql = 'SELECT a.stamp, sum(b.frequency) as frequency 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                    break;
                default:
                    $acGroups = $anlage->getGroupsAc();
                    // Frequenz für diesen Zeitraum und diese Gruppe
                    $sql = 'SELECT a.stamp, sum(b.frequency) as frequency 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
            }
        } else {
            switch ($anlage->getConfigType()) {
                case 1:
                    $acGroups = $anlage->getGroupsDc();
                    // Frequenz für diesen Zeitraum und diese Gruppe
                    $sql = 'SELECT a.stamp, b.frequency as frequency 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                    break;
                default:
                    $acGroups = $anlage->getGroupsAc();
                    // Frequenz für diesen Zeitraum und diese Gruppe
                    $sql = 'SELECT a.stamp, b.frequency as frequency 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
            }
        }
        $result = $conn->query($sql);
        $counter = 0;
        $counterInv = 0;
        $dataArray['maxSeries'] = 0;
        $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
        $dataArray['label'] = $acGroups[$group]['GroupName'];
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                ++$counterInv;
                if ($counterInv > $maxInverter) {
                    $maxInverter = $counterInv;
                }
                if ($hour) {
                    $frequency = round($row['frequency'], 1) / 4;
                } else {
                    $frequency = round($row['frequency'], 1);
                }
                $stamp = $row['stamp'];
                if (!($frequency == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter] = [
                        // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        'date' => self::timeShift($anlage, $stamp),
                        'frequency' => $frequency,
                    ];
                }

                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für Blindleistung.
     *
     * @param $from
     * @param $to
     *
     * @return array
     *               AC - Actual, Groups
     */
    public function getReactivePowerGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        ini_set('memory_limit', '3G');
        set_time_limit(500);
        if ($hour) {
            $form = '%y%m%d%H';
        } else {
            $form = '%y%m%d%H%i';
        }
        $conn = $this->getPdoService->getPdoPlant();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        switch ($anlage->getConfigType()) {
            case 1:
                $acGroups = $anlage->getGroupsDc();
                // Blindleistung für diesen Zeitraum und diese Gruppe
                $sql = 'SELECT a.stamp, sum(b.p_ac_blind) as p_ac_blind 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                break;
            default:
                $acGroups = $anlage->getGroupsAc();
                // Blindleistung für diesen Zeitraum und diese Gruppe
                $sql = 'SELECT a.stamp, sum(b.p_ac_blind) as p_ac_blind 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
        }

        $result = $conn->query($sql);
        $counter = 0;
        $counterInv = 0;
        $dataArray['maxSeries'] = 0;
        $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
        $dataArray['label'] = $acGroups[$group]['GroupName'];
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                ++$counterInv;
                if ($counterInv > $maxInverter) {
                    $maxInverter = $counterInv;
                }
                $invGroupIst = $row['inv_group'];
                $stamp = $row['stamp'];
                $dataArray['chart'][$counter] = [
                    // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    'date' => self::timeShift($anlage, $stamp),
                    'reactive_power' => round($row['p_ac_blind'], 2),
                ];

                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }
    /**
     * Erzeugt die Daten für den Pnom Power Chart auf der AC Seite
     * MS 02/23 update 03/29
     * @param $from
     * @param $to
     *
     * @return array
     * Pnom AC Seite
     */
    public function getNomPowerGroupAC(Anlage $anlage, $from, $to, $sets = 0, int $group = 1, bool $hour = false): array {
        ini_set('memory_limit', '3G');
        set_time_limit(500);
        $conn = $this->getPdoService->getPdoPlant();
        $dataArray = [];
        $pnominverter = $anlage->getPnomInverterArray();
        $counter = 0;$counterInv = 0;

        // make the difference in time format
        $from = self::timeAjustment($from, $anlage->getAnlZeitzone());
        $to = self::timeAjustment($to, 1);

        switch ($anlage->getConfigType()) {
            case 1:
                $group = 'group_dc';
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
                $groupct = count($nameArray);
                break;
            default:
                $group = 'group_ac';
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                $groupct = count($nameArray);
        }

        if ($groupct) {
            if ($sets == null) {
                $min = 1;
                $max = (($groupct > 100) ? (int)ceil($groupct / 10) : (int)ceil($groupct / 2));
                $max = (($max > 50) ? '50' : $max);
                $sqladd = "AND $group BETWEEN '$min' AND '$max'";
              } else {
                $res = explode(',', $sets);
                $min = (int)ltrim($res[0], "[");
                $max = (int)rtrim($res[1], "]");
                (($max > $groupct) ? $max = $groupct : $max = $max);
                (($groupct > $min) ? $min = $min : $min = 1);
                $sqladd = "AND $group BETWEEN " . (empty($min) ? '1' : $min) . " AND " . (empty($max) ? '5' : $max) . "";
            }
        } else {
            $min = 1;$max = 5;
            $sqladd = "AND $group BETWEEN '$min' AND ' $max'";
        }
        // the array for range slider min max
        $dataArray['minSeries'] = $min;
        $dataArray['maxSeries'] = $max;
        $dataArray['sumSeries'] = $groupct;
        // build the Sql Query
        $sql = "SELECT c.stamp as ts, c.wr_idc as istCurrent ,c.wr_pac as istPower, c.$group as inv FROM
                 " . $anlage->getDbNameACIst() . " c WHERE c.stamp 
                 BETWEEN '$from' AND '$to' 
                 $sqladd
                 GROUP BY c.stamp,c.$group ORDER BY NULL";
        // process the Query result
        $resultActual = $conn->query($sql);
        $dataArray['SeriesNameArray'] = $nameArray;
        if ($resultActual->rowCount() > 0) {
            while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowActual['ts'];
                $e = explode(' ', $stamp);
                $dataArray['chart'][$counter]['ydate'] = $e[1];
                $dataArray['chart'][$counter]['date'] = $stamp;
                $powerist = $rowActual['istPower'];

                if ($powerist != null) {
                    $poweristkwh =  $powerist;
                   } else {
                    $poweristkwh = 0;
                }

                $pnomkwh = $pnominverter[$rowActual['inv']];

                if($pnomkwh != 0) {
                  $value_acpnom = round(($poweristkwh / $pnomkwh) * 4,2);
                 } else {
                  $value_acpnom = 0;
                }

                // $value_expac = round(($powersollkwh / $pnomkwh) * 4, 2);
                $dataArray['chart'][$counter]['xinv'] = $nameArray[$rowActual['inv']]; #array startet by zero
                $dataArray['chart'][$counter]['pnomac'] = $value_acpnom;
                // $dataArray['chart'][$counter]['pnomexpac'] = $value_expac;
                ++$counter;
                ++$counterInv;
            }
            $dataArray['offsetLegend'] = 0;
        }
        // The generated data Array for the range slider and Chart
        return $dataArray;
   }
}