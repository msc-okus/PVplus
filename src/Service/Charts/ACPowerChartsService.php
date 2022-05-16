<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use ContainerXGGeorm\getConsole_ErrorListenerService;
use PDO;
use Symfony\Component\Security\Core\Security;

class ACPowerChartsService
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
     * Erzeugt Daten für das normale Soll/Ist AC Diagramm
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param bool $hour
     * @return array
     */
    public function getAC1(Anlage $anlage, $from, $to, bool $hour = false): array
    {
            $conn = self::getPdoConnection();
            $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';

            $sqlExp = "SELECT a.stamp as stamp, sum(b.ac_exp_power) as soll, sum(b.ac_exp_power_evu) as soll_evu, sum(b.ac_exp_power_no_limit) as soll_nolimit
                    FROM (db_dummysoll a left JOIN " . $anlage->getDbNameDcSoll() . " b ON a.stamp = b.stamp)
                    WHERE a.stamp >= '$from' AND a.stamp < '$to' 
                    GROUP by date_format(a.stamp, '$form')";


            $resExp = $conn->query($sqlExp);
            $actSum = $expSum = $expEvuSum = $expNoLimitSum = $evuSum = $cosPhiSum = $theoPowerSum = 0;
            $dataArray = [];

            if ($resExp->rowCount() > 0) {
                $counter = 0;
                //we must move this code to the constructor function and use a property
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false || $anlage->getUseCustPRAlgorithm() == "Groningen") {
                    $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
                } else {
                    $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
                }
                while ($rowExp = $resExp->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = self::timeShift($anlage, $rowExp["stamp"]);
                    $stampAdjust = self::timeAjustment($rowExp["stamp"], $anlage->getAnlZeitzone());
                    $stampAdjust2 = self::timeAjustment($stampAdjust, 1);

                    $rowExp["soll"] > 0 ? $expectedInvOut = round($rowExp["soll"], 2) : $expectedInvOut = 0; // neagtive Werte auschließen
                    $rowExp['soll_evu'] == null || $rowExp['soll_evu'] < 0 ? $expectedEvu = 0 : $expectedEvu = round($rowExp['soll_evu'], 2);
                    $rowExp['soll_nolimit'] == null || $rowExp['soll_nolimit'] < 0 ? $expectedNoLimit = 0 : $expectedNoLimit = round($rowExp['soll_nolimit'], 2);
                    $expDiffInvOut = round($expectedInvOut - $expectedInvOut * 10 / 100, 2);   // Minus 10 % Toleranz Invberter Out.
                    $expDiffEvu = round($expectedEvu - $expectedEvu * 10 / 100, 2);         // Minus 10 % Toleranz Grid (EVU).

                    $whereQueryPart1 = $hour ? "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'" : "stamp = '$stampAdjust'";
                    $sqlActual = "SELECT sum(wr_pac) as acIst, wr_cos_phi_korrektur as cosPhi, sum(theo_power) as theoPower FROM " . $anlage->getDbNameIst() . " 
                        WHERE wr_pac >= 0 AND $whereQueryPart1 GROUP by date_format(stamp, '$form')";

                    $sqlEvu = "SELECT sum(e_z_evu) as eZEvu FROM " .  $anlage->getDbNameIst() . " WHERE $whereQueryPart1 GROUP by date_format(stamp, '$form')";

                    $resActual = $conn->query($sqlActual);
                    $resEvu = $conn->query($sqlEvu);

                    if ($resActual->rowCount() == 1) {
                        $rowActual = $resActual->fetch(PDO::FETCH_ASSOC);
                        $cosPhi = abs((float)$rowActual["cosPhi"]);
                        $acIst = $rowActual["acIst"];
                        $acIst = self::checkUnitAndConvert($acIst, $anlage->getAnlDbUnit());
                        $acIst > 0 ? $actout = round($acIst, 2) : $actout = 0; // neagtive Werte auschließen
                        $theoPower = $rowActual["theoPower"];
                        $cosPhiSum += $cosPhi * $acIst;
                        $actSum += $actout;
                        $theoPowerSum += $theoPower;
                    } else {
                        $cosPhi = $actout = $theoPower = null;
                    }
                    if ($resEvu->rowCount() == 1) {
                        $rowEvu = $resEvu->fetch(PDO::FETCH_ASSOC);
                        $eZEvu = $rowEvu["eZEvu"] / ($anlage->getAnzInverterFromGroupsAC());
                        $evuSum += $eZEvu;
                    } else {
                        $eZEvu = null;
                    }
                    $expSum += $expectedInvOut;
                    $expEvuSum += $expectedEvu;
                    $expNoLimitSum += $expectedNoLimit;
                    $dataArray['chart'][$counter]['date'] = $stamp;
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
                        if ($anlage->getShowInverterOutDiag()) $dataArray['chart'][$counter]['InvOut'] = $actout;
                        if ($anlage->getShowEvuDiag()) $dataArray['chart'][$counter]['eZEvu'] = $eZEvu;
                        if ($anlage->getShowCosPhiPowerDiag()) $dataArray['chart'][$counter]['cosPhi'] = $cosPhi * $actout;
                        $dataArray['chart'][$counter]['theoPower'] = $theoPower;
                        if ($anlage->getShowCosPhiDiag()) $dataArray['cosPhi'] = $cosPhi;
                    }

                    if (isset($dataArrayIrradiation['chart'][$counter]['val1'])) {
                        if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                            $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                            $irrSum += $dataArray['chart'][$counter]["irradiation"];
                        } else {
                            $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                            $irrSum += $dataArray['chart'][$counter]["irradiation"];
                        }
                    }
                    $counter++;
                }
                $dataArray['irrSum'] = round($irrSum, 2);
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
     */
    public function getAC2(Anlage $anlage, $from, $to, int $group, bool $hour = false): array
    {
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        $nameArray = $this->functions->getNameArray($anlage , 'ac');
        $dataArray['inverterArray'] = $nameArray;
        $acGroups = $anlage->getGroupsAc();
        $type = "";
        $hour ? $form = '%y%m%d%H' : $form = '%y%m%d%H%i';

        switch ($anlage->getConfigType()) {
            case 1:
                $type .= " group_ac = '$group' AND";
                break;
            default:
                $type .= " group_dc = '$group' AND";
        }

        $sqlExpected = "SELECT a.stamp , sum(b.ac_exp_power) as soll
                            FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp)
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

            //get Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            }

            $expectedArray = $resultExp->fetchAll(PDO::FETCH_ASSOC);
            foreach ($expectedArray as $rowExp) {
                $stamp = $rowExp["stamp"];
                $rowExp['soll'] == null || $rowExp['soll'] < 0 ? $expected = 0 : $expected = $rowExp['soll'];
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $rowExp["stamp"]);
                $counterInv = 1;
                if ($hour) {
                    $endStamp = date('Y-m-d H:i', strtotime($stamp) + 3600);
                    $sqlIst = "SELECT sum(wr_pac) as actPower, wr_cos_phi_korrektur as cosPhi FROM " . $anlage->getDbNameIst() . " WHERE " . $type . " stamp >= '$stamp' AND  stamp < '$endStamp' group by unit ORDER BY unit";
                }
                else {
                    $sqlIst = "SELECT wr_pac as actPower, wr_cos_phi_korrektur as cosPhi FROM " . $anlage->getDbNameIst() . " WHERE " . $type . " stamp = '$stamp' ORDER BY unit";
                }
                $resultActual = $conn->query($sqlIst);
                while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)){

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
                            if ($counterInv > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counterInv;
                            break;
                        default:
                            if ($counterInv > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counterInv - 1;
                    }

                    if ($anlage->getShowCosPhiDiag()) $dataArray['chart'][$counter]['cosPhi'] = abs((float)$rowActual['wr_cos_phi_korrektur']);
                    $counterInv++;
                }

                //and here
                $counterInv--;
                ($counterInv > 0) ? $dataArray['chart'][$counter]['expected'] = $expected / $counterInv : $dataArray['chart'][$counter]['exp'] = $expected;
                //add Irradiation

                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                }
                $counter++;

            }
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
     */
    public function getAC3(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
            $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
            $conn = self::getPdoConnection();
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

            $sqlExpected = "SELECT a.stamp, sum(b.ac_exp_power) as soll
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE " . $groupQuery . " ) b ON a.stamp = b.stamp)  
                        WHERE a.stamp BETWEEN '$from' AND '$to' 
                        GROUP by date_format(a.stamp, '$form')";

            $dataArray['inverterArray'] = $nameArray;
            $resultExpected = $conn->query($sqlExpected);
            $maxInverter = 0;

            // add Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            }

            if ($resultExpected->rowCount() > 0) {
                $counter = 0;
                switch ($anlage->getConfigType()) {
                    case 3: // Groningen
                    case 4:
                        $dataArray['offsetLegend'] = $group - 1;
                        break;
                    default:
                        $dataArray['offsetLegend'] = $groups[$group]['GMIN'] - 1;
                }
                $dataArray['label'] = $groups[$group]['GroupName'];

                while ($rowExp = $resultExpected->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = self::timeShift($anlage, $rowExp["stamp"]);
                    $stampAdjust = self::timeAjustment($rowExp["stamp"], $anlage->getAnlZeitzone());
                    $stampAdjust2 = self::timeAjustment($stampAdjust, 1);

                    $dataArray['chart'][$counter]['date'] = $stampAdjust;
                    ($rowExp['soll'] == null) ? $expected = 0 : $expected = $rowExp['soll'];
                    $dataArray['maxSeries'] = 1;
                    $whereQueryPart1 = $hour ? "stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'" : "stamp = '$stampAdjust'";
                    $sql = "SELECT  sum(wr_pac) as actPower, avg(wr_temp) as temp, wr_cos_phi_korrektur 
                        FROM " . $anlage->getDbNameIst() . " WHERE $groupQuery and $whereQueryPart1 GROUP BY date_format(stamp, '$form')";

                    $resultActual = $conn->query($sql);
                    if($resultActual->rowCount() == 1) {
                        $rowAct = $resultActual->fetch(PDO::FETCH_ASSOC);

                        $dataArray['chart'][$counter]['temperature'] = $rowAct['temp'] == null ?  null : $rowAct['temp'];
                        $actPower = $rowAct['actPower'];
                        $actPower = $actPower > 0 ? round(self::checkUnitAndConvert($actPower, $anlage->getAnlDbUnit()), 2) : 0; // neagtive Werte auschließen

                        switch ($anlage->getConfigType()) {
                            case 3: // Groningen
                            case 4:
                                $dataArray['chart'][$counter][$nameArray[$group]] = $actPower;
                                break;
                            default:
                                $dataArray['chart'][$counter][$nameArray[$group]] = $actPower;
                        }

                        if ($anlage->getShowCosPhiDiag()) $dataArray['chart'][$counter]['cosPhi'] = abs((float)$rowAct['wr_cos_phi_korrektur']);
                    }


                     $dataArray['chart'][$counter]['expected'] = (float)$expected;

                    // add Irradiation
                    if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                        $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                    } else {
                        $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2']) / 2;
                    }
                    $counter++;
                }
            }

        $conn = null;

        return $dataArray;
    }


    /**
     * erzeugt Daten für Gruppen Leistungsunterschiede Diagramm (Group Power Difference)
     * AC - Inverter
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     *
     * AC4
     */
    public function getGroupPowerDifferenceAC(Anlage $anlage, $from, $to):?array
    {

            $conn = self::getPdoConnection();
            $dataArray = [];
            $acGroups = $anlage->getGroupsAc();

            // Strom für diesen Zeitraum und diese Gruppe
            $sql_soll = "SELECT stamp, sum(ac_exp_power) as soll, group_ac as inv_group FROM " . $anlage->getDbNameDcSoll() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_ac ORDER BY group_ac * 1"; // 'wr_num * 1' damit die Sortierung als Zahl und nicht als Text erfolgt
            $sqlInv = "SELECT sum(wr_pac) as acinv, group_ac as inv_group FROM " . $anlage->getDbNameIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_ac ORDER BY group_ac * 1;";
            $result = $conn->query($sql_soll);
            $resultInv = $conn->query($sqlInv);
            $counter = 0;
            $wrcounter = 0;
            if ($result->rowCount() > 0) {

                $dataArray['maxSeries'] = 0;
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $dataArray['rangeValue'] = round($row["soll"], 2);
                    $invGroupSoll = $row["inv_group"];
                    $dataArray['chart'][$counter] = [
                        "category" => $acGroups[$invGroupSoll]['GroupName'],
                        "link" => $invGroupSoll,
                        "exp" => round($row["soll"], 2),
                    ];
                     if($rowInv = $resultInv->fetch(PDO::FETCH_ASSOC)) {
                         $wrcounter++;
                         $dataArray['chart'][$counter]['act'] = self::checkUnitAndConvert($rowInv['acinv'], $anlage->getAnlDbUnit());
                         if ($wrcounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $wrcounter;
                     }

                    $counter++;
                }
            }
            $conn = null;

            return $dataArray;

    }

    /**
     * Erzeugt Daten für Ist Spannung AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * AC - Actual, Groups
     */
    public function getActVoltageGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false):?array
    {

        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        switch ($anlage->getConfigType()) {
            case 1:
                $acGroups = $anlage->getGroupsDc();
                $nameArray = $this->functions->getNameArray($anlage , 'dc');
                // Spannung für diesen Zeitraum und diese Gruppe
                $sql = "SELECT a.stamp, sum(b.u_ac) as uac_ist, sum(b.u_ac_p1) as u_ac_p1, sum(b.u_ac_p2) as u_ac_p2,  sum(b.u_ac_p3) as u_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY  date_format(a.stamp, '$form')";
                break;
            default:
                $acGroups = $anlage->getGroupsAc();
                $nameArray = $this->functions->getNameArray($anlage , 'ac');
                // Spannung für diesen Zeitraum und diese Gruppe
                $sql = "SELECT a.stamp, sum(b.u_ac) as uac_ist, sum(b.u_ac_p1) as u_ac_p1, sum(b.u_ac_p2) as u_ac_p2,  sum(b.u_ac_p3) as u_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY  date_format(a.stamp, '$form')";
        }

        if($hour){
            switch ($anlage->getConfigType()) {
                case 1:
                    $acGroups = $anlage->getGroupsDc();
                    $nameArray = $this->functions->getNameArray($anlage , 'dc');
                    // Spannung für diesen Zeitraum und diese Gruppe
                    $sql = "SELECT a.stamp, sum(b.u_ac) as uac_ist, sum(b.u_ac_p1) as u_ac_p1, sum(b.u_ac_p2) as u_ac_p2,  sum(b.u_ac_p3) as u_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by  date_format(a.stamp, '$form')";
                    break;
                default:
                    $acGroups = $anlage->getGroupsAc();
                    $nameArray = $this->functions->getNameArray($anlage , 'ac');
                    // Spannung für diesen Zeitraum und diese Gruppe
                    $sql = "SELECT a.stamp, sum(b.u_ac) as uac_ist, sum(b.u_ac_p1) as u_ac_p1, sum(b.u_ac_p2) as u_ac_p2,  sum(b.u_ac_p3) as u_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
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
                $counterInv++;
                if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $stamp = $row["stamp"];

        if($hour) {
         $dataArray['chart'][$counter] = [
             //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                "date" => self::timeShift($anlage, $stamp),
                "u_ac" => round($row["uac_ist"], 2) / 4,
                "u_ac_phase1" => round($row["u_ac_p1"], 2) / 4,
                "u_ac_phase2" => round($row["u_ac_p2"], 2) / 4,
                "u_ac_phase3" => round($row["u_ac_p3"], 2) / 4,
         ];
        }
        else{         $dataArray['chart'][$counter] = [
            //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
            "date" => self::timeShift($anlage, $stamp),
            "u_ac" => round($row["uac_ist"], 2) ,
            "u_ac_phase1" => round($row["u_ac_p1"], 2),
            "u_ac_phase2" => round($row["u_ac_p2"], 2),
            "u_ac_phase3" => round($row["u_ac_p3"], 2),
        ];}
                $counter++;
            }
        }
        $conn = null;
        return $dataArray;
    }

    /**
     * Erzeugt Daten für Ist Strom AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * AC - Actual, Groups
     */
    public function getActCurrentGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false):?array
    {
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        switch ($anlage->getConfigType()) {
            case 1:
                $acGroups = $anlage->getGroupsDc();
                // Strom für diesen Zeitraum und diese Gruppe
                $sql = "SELECT a.stamp, sum(b.i_ac) as iac_sum, sum(b.i_ac_p1) as i_ac_p1, sum(b.i_ac_p2) as i_ac_p2,  sum(b.i_ac_p3) as i_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                break;
            default:
                $acGroups = $anlage->getGroupsAc();
                // Strom für diesen Zeitraum und diese Gruppe
                $sql = "SELECT a.stamp, sum(b.i_ac) as iac_sum, sum(b.i_ac_p1) as i_ac_p1, sum(b.i_ac_p2) as i_ac_p2,  sum(b.i_ac_p3) as i_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
        }
        if($hour){
            switch ($anlage->getConfigType()) {
                case 1:
                    $acGroups = $anlage->getGroupsDc();
                    // Strom für diesen Zeitraum und diese Gruppe
                    $sql = "SELECT a.stamp, sum(b.i_ac) as iac_sum, sum(b.i_ac_p1) as i_ac_p1, sum(b.i_ac_p2) as i_ac_p2,  sum(b.i_ac_p3) as i_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                    break;
                default:
                    $acGroups = $anlage->getGroupsAc();
                    // Strom für diesen Zeitraum und diese Gruppe
                    $sql = "SELECT a.stamp, sum(b.i_ac) as iac_sum, sum(b.i_ac_p1) as i_ac_p1, sum(b.i_ac_p2) as i_ac_p2,  sum(b.i_ac_p3) as i_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
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
                if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                $stamp = $row["stamp"];
                if($hour) {
                    $dataArray['chart'][$counter] = [
                        //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        "date" => self::timeShift($anlage, $stamp),

                        "i_ac_sum" => round($row["iac_sum"], 2) / 4,
                        "i_ac_phase1" => round($row["i_ac_p1"], 2) / 4,
                        "i_ac_phase2" => round($row["i_ac_p2"], 2) / 4,
                        "i_ac_phase3" => round($row["i_ac_p3"], 2) / 4,

                    ];
                }
                else{
                    $dataArray['chart'][$counter] = [
                        //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        "date" => self::timeShift($anlage, $stamp),

                        "i_ac_sum" => round($row["iac_sum"], 2) ,
                        "i_ac_phase1" => round($row["i_ac_p1"], 2),
                        "i_ac_phase2" => round($row["i_ac_p2"], 2),
                        "i_ac_phase3" => round($row["i_ac_p3"], 2),

                    ];
                }
                $counterInv++;
                $counter++;
            }
        }
        $conn = null;
        return $dataArray;
    }

    /**
     * Erzeugt Daten für Ist Frequenz AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * AC - Actual, Groups
     */
    public function getActFrequncyGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false):?array
    {
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        if($hour) {
            switch ($anlage->getConfigType()) {
                case 1:
                    $acGroups = $anlage->getGroupsDc();
                    // Frequenz für diesen Zeitraum und diese Gruppe
                    $sql = "SELECT a.stamp, sum(b.frequency) as frequency 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                    break;
                default:
                    $acGroups = $anlage->getGroupsAc();
                    // Frequenz für diesen Zeitraum und diese Gruppe
                    $sql = "SELECT a.stamp, sum(b.frequency) as frequency 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
            }
        }
        else{
            switch ($anlage->getConfigType()) {
                case 1:
                    $acGroups = $anlage->getGroupsDc();
                    // Frequenz für diesen Zeitraum und diese Gruppe
                    $sql = "SELECT a.stamp, b.frequency as frequency 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                    break;
                default:
                    $acGroups = $anlage->getGroupsAc();
                    // Frequenz für diesen Zeitraum und diese Gruppe
                    $sql = "SELECT a.stamp, b.frequency as frequency 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
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
                $counterInv++;
                if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                if($hour)$frequency = round($row["frequency"],1)/4;
                else $frequency=round($row["frequency"],1);
                $stamp = $row["stamp"];
                if (!($frequency == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter] = [
                        //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        "date" => self::timeShift($anlage, $stamp),
                        "frequency" => $frequency,
                    ];
                }

                $counter++;
            }
        }
        $conn = null;
        return $dataArray;
    }

    /**
     * Erzeugt Daten für Blindleistung
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * AC - Actual, Groups
     */
    public function getReactivePowerGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour = false): array
    {
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        switch ($anlage->getConfigType()) {
            case 1:
                $acGroups = $anlage->getGroupsDc();
                // Blindleistung für diesen Zeitraum und diese Gruppe
                $sql = "SELECT a.stamp, sum(b.p_ac_blind) as p_ac_blind 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";
                break;
            default:
                $acGroups = $anlage->getGroupsAc();
                // Blindleistung für diesen Zeitraum und diese Gruppe
                $sql = "SELECT a.stamp, sum(b.p_ac_blind) as p_ac_blind 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
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
                $counterInv++;
                if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                $invGroupIst = $row["inv_group"];
                $stamp = $row["stamp"];
                $dataArray['chart'][$counter] = [
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    "date" => self::timeShift($anlage, $stamp),
                    "reactive_power" => round($row["p_ac_blind"], 2),
                ];

                $counter++;
            }
        }
        $conn = null;
        return $dataArray;
    }
}