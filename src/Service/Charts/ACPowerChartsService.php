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
     * @param $hour
     * @return array|false
     */
    public function getAC1(Anlage $anlage, $from, $to,?bool $hour = false)
    {


        $conn = self::getPdoConnection();
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';


        $sql = "SELECT a.stamp as stamp, sum(b.ac_exp_power) as soll, sum(b.ac_exp_power_evu) as soll_evu, sum(b.ac_exp_power_no_limit) as soll_nolimit
                    FROM (db_dummysoll a left JOIN " . $anlage->getDbNameDcSoll() . " b ON a.stamp = b.stamp)
                    WHERE a.stamp >= '$from' AND a.stamp < '$to'
                    GROUP by date_format(a.stamp, '$form')";
        $res = $conn->query($sql);
        $actSum = 0;
        $expSum = $expEvuSum = $expNoLimitSum = 0;
        $evuSum = 0;
        $cosPhiSum = 0;
        $dataArray = [];
        if ($res->rowCount() > 0) {
            $counter = 0;
            // add Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            }
            // add Temperature
            // $panelTemparray = $this->getAirAndPanelTemp($anlage, $from, $to);
            while ($rowExp = $res->fetch(PDO::FETCH_ASSOC)) {
                ($rowExp["soll"] > 0)                                               ? $expectedInvOut = round($rowExp["soll"], 2) : $expectedInvOut = 0; // neagtive Werte auschließen
                ($rowExp['soll_evu'] == null || $rowExp['soll_evu'] < 0)            ? $expectedEvu      = 0 : $expectedEvu      = round($rowExp['soll_evu'],2);
                ($rowExp['soll_nolimit'] == null || $rowExp['soll_nolimit'] < 0 )   ? $expectedNoLimit  = 0 : $expectedNoLimit  = round($rowExp['soll_nolimit'],2);
                $expDiffInvOut  = round($expectedInvOut - $expectedInvOut * 10 / 100, 2);   // Minus 10 % Toleranz Invberter Out.
                $expDiffEvu     = round($expectedEvu - $expectedEvu * 10 / 100, 2);         // Minus 10 % Toleranz Grid (EVU).

                $stamp = $rowExp["stamp"];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                $acIst = 0;
                $eZEvu = 0;
                $cosPhi = 0;
                if($hour) {
                    $sql_b = "SELECT stamp, sum(wr_pac) as acIst, wr_cos_phi_korrektur as cosPhi
                            FROM " . $anlage->getDbNameIst() .
                        " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'  and wr_pac >= 0  GROUP by  date_format(stamp, '$form') LIMIT 1";

                    $sql_b1 = "SELECT sum(e_z_evu) as eZEvu
                            FROM " . $anlage->getDbNameIst() .
                        " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' GROUP by  date_format(stamp, '$form') LIMIT 1";
                }
                else {
                    $sql_b = "SELECT stamp, sum(wr_pac) as acIst, e_z_evu as eZEvu, wr_cos_phi_korrektur as cosPhi 
                             FROM " . $anlage->getDbNameIst() .
                            " WHERE stamp = '$stampAdjust' and wr_pac >= 0 GROUP by stamp LIMIT 1";
                    $sql_b1 = "SELECT e_z_evu as eZEvu
                               FROM " . $anlage->getDbNameIst() .
                             " WHERE stamp >= '$stampAdjust' GROUP by  stamp LIMIT 1";
                }
     //           $sql_b = "SELECT stamp, sum(wr_pac) as acIst, e_z_evu as eZEvu, wr_cos_phi_korrektur as cosPhi FROM " . $anlage->getDbNameIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' and wr_pac > 0 GROUP by date_format(stamp, '$form') LIMIT 1";
                $resultB = $conn->query($sql_b);
                $ResultB1 = $conn->query($sql_b1);
                if ($resultB->rowCount() == 1) {
                    $row = $resultB->fetch(PDO::FETCH_ASSOC);

                    $cosPhi = abs($row["cosPhi"]);

                    $cosPhiSum += $cosPhi * $acIst;
                    $acIst = $row["acIst"];
                }
                if($resultB->rowCount()==1){
                    $row1 = $ResultB1->fetch(PDO::FETCH_ASSOC);
                    ($hour) ? $eZEvu = $row1["eZEvu"]/($anlage->getAnzInverterFromGroupsAC()): $eZEvu = $row1["eZEvu"];
                    $evuSum += $eZEvu;
                }
                $acIst = self::checkUnitAndConvert($acIst, $anlage->getAnlDbUnit());
                ($acIst > 0) ? $actout = round($acIst, 2) : $actout = 0; // neagtive Werte auschließen

                $actSum += $actout;
                $expSum += $expectedInvOut;
                $expEvuSum += $expectedEvu;
                $expNoLimitSum += $expectedNoLimit;
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $stamp = self::timeShift($anlage, $rowExp["stamp"]);

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
                if (!($actout == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    if ($anlage->getShowInverterOutDiag())$dataArray['chart'][$counter]['InvOut'] = $actout;
                    if ($anlage->getShowEvuDiag()) $dataArray['chart'][$counter]['eZEvu'] = $eZEvu;
                    if ($anlage->getShowCosPhiPowerDiag()) $dataArray['chart'][$counter]['cosPhi'] = $cosPhi * $actout;
                    if ($anlage->getShowCosPhiDiag()) $dataArray['cosPhi'] = $cosPhi;
                }

                // add Irradiation
                if (isset($dataArrayIrradiation['chart'][$counter]['val1'])) {
                    if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
                        $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                    } else {
                        $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                    }
                }
                // add Temperature
                // $dataArray['chart'][$counter]['panelTemp'] = $panelTemparray['chart'][$counter]["val2"];
                $counter++;
            }


            $dataArray['actSum'] = round($actSum, 2);
            $dataArray['expSum'] = round($expSum, 2);
            $dataArray['expEvuSum'] = round($expEvuSum, 2);
            $dataArray['expNoLimitSum'] = round($expNoLimitSum, 2);
            $dataArray['evuSum'] = round($evuSum, 2);
            $dataArray['cosPhiSum'] = round($cosPhiSum, 2);
            $conn = null;

            return $dataArray;

        } else {
            $conn = null;

            return false;
        }
    }


    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     *
     * @return array
     */
    public function getAC2(Anlage $anlage, $from, $to, int $group, bool $hour): array
    {
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';


        $sqlExpected = "SELECT a.stamp , sum(b.ac_exp_power) as soll
                    FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp)
                    WHERE a.stamp BETWEEN '$from' AND '$to'
                    GROUP by date_format(a.stamp, '$form')";

        $conn = self::getPdoConnection();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        $nameArray = $this->functions->getNameArray($anlage , 'ac');
        $dataArray['inverterArray'] = $nameArray;
        $acGroups = $anlage->getGroupsAc();
        $maxInverter = 0;
       // $sqlExpected = "SELECT a.stamp, sum(b.ac_exp_power) as soll
       //                 FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp)
        //                WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";
        $result = $conn->query($sqlExpected);
        if ($result->rowCount() > 0) {
            $counter = 0;

            switch ($anlage->getConfigType()) {
                case 3: // Groningen
                    break;
                default:
                    $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
            }
            $dataArray['label'] = $acGroups[$group]['GroupName'];

            while ($rowExp = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowExp["stamp"];
                $type = "";

                ($rowExp['soll'] == null) ? $expected = 0 : $expected = $rowExp['soll'];

                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone()); // Adjust Time differenve between weather station and plant data (only nessesary if weather data comes from externel weather station)
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp); // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                switch ($anlage->getConfigType()) {
                    case 1:
                        $type .= "AND group_ac = '$group'";
                        break;
                    default:
                        $type .= "AND group_dc = '$group'";
                }

                if($hour) $sqlIst = "SELECT stamp, sum(wr_pac) as actPower, wr_cos_phi_korrektur FROM " . $anlage->getDbNameIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'  and wr_pac > 0 ".$type." GROUP BY unit ";

                else $sqlIst = "SELECT sum(wr_pac) as actPower, wr_cos_phi_korrektur FROM " . $anlage->getDbNameIst() . " WHERE stamp = '$stampAdjust' AND wr_pac > 0 ".$type."  GROUP BY unit";

               // $sqlIst .= " GROUP BY unit";
                $resultIst = $conn->query($sqlIst);
                $counterInv = 1;

                // add Irradiation
                // Todo: Gewichtet Strahlung bei Ost West Anlagen.
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
                    $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper',$hour);
                } else {
                    $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
                }

                if ($resultIst->rowCount() > 0) {
                    while ($rowIst = $resultIst->fetch(PDO::FETCH_ASSOC)) {
                        if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                        $actPower = $rowIst['actPower'];
                        if($hour) $actPower=$actPower;
                        ($actPower > 0) ? $actPower = round(self::checkUnitAndConvert($actPower, $anlage->getAnlDbUnit()), 2) : $actPower = 0; // neagtive Werte auschließen
                        if (!($actPower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            switch ($anlage->getConfigType()) {

                                case 3: // Groningen
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
                        if ($anlage->getShowCosPhiDiag()) $dataArray['chart'][$counter]['cosPhi'] = abs($rowIst['wr_cos_phi_korrektur']);
                    }
                } else {
                    for ($counterInv = 1; $counterInv <= $maxInverter; $counterInv++) {
                        switch ($anlage->getConfigType()) {

                            case 3: // Groningen
                                $dataArray['chart'][$counter][$nameArray[$group]] = 0;
                                break;
                            default:
                                $dataArray['chart'][$counter][$nameArray[$counterInv+$dataArray['offsetLegend']]] = 0;
                        }
                    }
                }
                $counterInv--;
                ($counterInv > 0) ? $dataArray['chart'][$counter]['expected'] = $expected / $counterInv : $dataArray['chart'][$counter]['exp'] = $expected;

                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                }

                $counter++;
            }
        }

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das Soll/Ist AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     */
    public function getAC3(Anlage $anlage, $from, $to, int $group = 1, bool $hour): array
    {
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $groupq = "";
        $conn = self::getPdoConnection();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        switch ($anlage->getConfigType()) {
            case 1 :
                $groupq .= "group_dc";
                $groups = $anlage->getGroupsDc();
                $nameArray = $this->functions->getNameArray($anlage , 'dc');
                break;
            default:
                $groupq .= "group_ac";
                $groups = $anlage->getGroupsAc();
                $nameArray = $this->functions->getNameArray($anlage , 'ac');
        }
        $groupq .= " = '$group') b ON a.stamp = b.stamp)  ";
        $sqlExpected = "SELECT a.stamp, sum(b.ac_exp_power) as soll
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE ".$groupq." WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by date_format(a.stamp, '$form')";

        $dataArray['inverterArray'] = $nameArray;

        $result = $conn->query($sqlExpected);
        $maxInverter = 0;

        // add Irradiation
        // Todo: Gewichtet Strahlung bei Ost West Anlagen.
        if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false){
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
        } else {
            $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to,'all', $hour);
        }

        if ($result->rowCount() > 0) {
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

            while ($rowExp = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowExp["stamp"];
                ($rowExp['soll'] == null) ? $expected = 0 : $expected = $rowExp['soll'];

                $groupd = "";
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                switch ($anlage->getConfigType()) {
                    case 1:
                        $groupd .= "AND group_dc = '$group'";
                        break;
                    default:
                        $groupd .= "AND group_ac = '$group'";
                }
                if($hour)$sql = "SELECT  sum(wr_pac) as actPower, avg(wr_temp) as temp, wr_cos_phi_korrektur FROM " . $anlage->getDbNameIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' ".$groupd."  GROUP BY unit";
                else $sql="SELECT sum(wr_pac) as actPower, wr_temp as temp, wr_cos_phi_korrektur FROM " . $anlage->getDbNameIst() . " WHERE stamp = '$stampAdjust' ".$groupd."  GROUP BY unit";
                // removed from query  AND wr_pac > '0'
                $resultIst = $conn->query($sql);
                $counterInv = 1;

                if ($resultIst->rowCount() > 0) {
                    $dataArray['maxSeries'] = $resultIst->rowCount();
                    while ($rowIst = $resultIst->fetch(PDO::FETCH_ASSOC)) {

                        if ($counterInv > $maxInverter) $maxInverter = $counterInv;

                        if ($rowIst['temp'] == null) $temperature = 0;
                        else $temperature = $rowIst['temp'];
                        $dataArray['chart'][$counter]['temperature'] = $temperature;


                        $actPower = $rowIst['actPower'];
                        ($actPower > 0) ? $actPower = round(self::checkUnitAndConvert($actPower, $anlage->getAnlDbUnit()), 2) : $actPower = 0; // neagtive Werte auschließen
                        if (!($actPower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            switch ($anlage->getConfigType()) {

                                case 3: // Groningen
                                case 4:
                                    $dataArray['chart'][$counter][$nameArray[$group]] = $actPower;

                                    break;
                                default:
                                    $dataArray['chart'][$counter][$nameArray[$counterInv+$dataArray['offsetLegend']]] = $actPower;
                                    $counterInv++;
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
                        if ($anlage->getShowCosPhiDiag()) $dataArray['chart'][$counter]['cosPhi'] = abs($rowIst['wr_cos_phi_korrektur']);
                    }
                } else {
                    for($counterInv = 1; $counterInv <= $maxInverter; $counterInv++) {
                        switch ($anlage->getConfigType()) {

                            case 3: // Groningen
                            case 4:
                                $dataArray['chart'][$counter][$nameArray[$group]] = 0;
                                break;
                            default:
                                $dataArray['chart'][$counter][$nameArray[$counterInv+$dataArray['offsetLegend']]] = 0;
                        }
                    }
                }

                $counterInv--;
                ($counterInv > 0) ? $dataArray['chart'][$counter]['expected'] = $expected / $counterInv : $dataArray['chart'][$counter]['expected'] = $expected;

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
        $result = $conn->query($sql_soll);
        $counter = 0;
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
                $sqlInv = "SELECT sum(wr_pac) as acinv, group_ac as inv_group FROM " . $anlage->getDbNameIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$invGroupSoll';";
                $resultInv = $conn->query($sqlInv);
                if ($resultInv->rowCount() > 0) {
                    $wrcounter = 0;
                    while ($rowInv = $resultInv->fetch(PDO::FETCH_ASSOC)) {
                        $wrcounter++;
                        $dataArray['chart'][$counter]['act'] = self::checkUnitAndConvert($rowInv['acinv'], $anlage->getAnlDbUnit());
                        if ($wrcounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $wrcounter;
                    }
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
    public function getActVoltageGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour):?array
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
    public function getActCurrentGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour):?array
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
    public function getActFrequncyGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour):?array
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
    public function getReactivePowerGroupAC(Anlage $anlage, $from, $to, int $group = 1, bool $hour): array
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