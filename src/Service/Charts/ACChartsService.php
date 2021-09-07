<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenStatusRepository;
use App\Repository\ForecastRepository;
use App\Repository\InvertersRepository;
use App\Repository\PRRepository;
use App\Repository\PVSystDatenRepository;
use App\Service\FunctionsService;
use PDO;
use Symfony\Component\Security\Core\Security;

class ACChartsService
{
    use G4NTrait;

    private Security $security;
    private AnlagenStatusRepository $statusRepository;
    private InvertersRepository $invertersRepo;
    public functionsService $functions;

    public function __construct(Security                $security,
                                AnlagenStatusRepository $statusRepository,
                                InvertersRepository     $invertersRepo,
                                FunctionsService        $functions)
    {
        $this->security = $security;
        $this->statusRepository = $statusRepository;
        $this->invertersRepo = $invertersRepo;
        $this->functions = $functions;
    }

    /**
     * Erzeugt Daten für das normale Soll/Ist AC Diagramm
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array|false
     * AC - Actual & Expected, Plant
     */
    public function getActExpAC(Anlage $anlage, $from, $to)
    {
        $conn = self::getPdoConnection();
        $sql_a = "SELECT a.stamp as stamp, sum(b.ac_exp_power) as soll, sum(b.ac_exp_power_evu) as soll_evu, sum(b.ac_exp_power_no_limit) as soll_nolimit 
                    FROM (db_dummysoll a left JOIN " . $anlage->getDbNameDcSoll() . " b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP by a.stamp";
        $res = $conn->query($sql_a);
        $actSum = 0;
        $expSum = $expEvuSum = $expNoLimitSum = 0;
        $evuSum = 0;
        $cosPhiSum = 0;
        $dataArray = [];
        if ($res->rowCount() > 0) {
            $counter = 0;
            // add Irradiation
            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == true){
                $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to, 'upper');
            } else {
                $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to);
            }
            // add Temperature
            // $panelTemparray = $this->getAirAndPanelTemp($anlage, $from, $to);
            while ($rowExp = $res->fetch(PDO::FETCH_ASSOC)) {
                $soll = round($rowExp["soll"], 2);
                ($rowExp['soll_evu'] == null)       ? $expectedEvu      = 0 : $expectedEvu      = round($rowExp['soll_evu'],2);
                ($rowExp['soll_nolimit'] == null)   ? $expectedNoLimit  = 0 : $expectedNoLimit  = round($rowExp['soll_nolimit'],2);
                $expdiff = $soll - $soll * 10 / 100;# -10% good
                $expdiff = round($expdiff, 2);

                $stamp = $rowExp["stamp"];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $acIst = 0;
                $eZEvu = 0;
                $cosPhi = 0;
                $sql_b = "SELECT stamp, sum(wr_pac) as acIst, e_z_evu as eZEvu, wr_cos_phi_korrektur as cosPhi FROM " . $anlage->getDbNameIst() . " WHERE stamp = '$stampAdjust' and wr_pac > 0 GROUP by stamp LIMIT 1";
                $resultB = $conn->query($sql_b);
                if ($resultB->rowCount() == 1) {
                    $row = $resultB->fetch(PDO::FETCH_ASSOC);
                    $eZEvu = $row["eZEvu"];
                    $cosPhi = abs($row["cosPhi"]);
                    $evuSum += $eZEvu;
                    $cosPhiSum += $cosPhi * $acIst;
                    $acIst = $row["acIst"];
                }
                $acIst = self::checkUnitAndConvert($acIst, $anlage->getAnlDbUnit());
                ($acIst > 0) ? $actout = round($acIst, 2) : $actout = 0; // neagtive Werte auschließen
                ($soll > 0) ?: $soll = 0; // neagtive Werte auschließen
                $actSum += $actout;
                $expSum += $soll;
                $expEvuSum += $expectedEvu;
                $expNoLimitSum += $expectedNoLimit;
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $stamp = self::timeShift($anlage, $rowExp["stamp"]);

                $dataArray['chart'][$counter]['date'] = $stamp;
                if (!($soll == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['expected'] = $soll;
                    $dataArray['chart'][$counter]['expgood'] = $expdiff;
                    if ($anlage->getShowEvuDiag()) $dataArray['chart'][$counter]['expexted_evu'] = $expectedEvu;
                    $dataArray['chart'][$counter]['expexted_no_limit'] = $expectedNoLimit;
                }
                if (!($actout == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    if ($anlage->getShowInverterOutDiag())$dataArray['chart'][$counter]['InvOut'] = $actout;
                    if ($anlage->getShowEvuDiag()) $dataArray['chart'][$counter]['eZEvu'] = $eZEvu;
                    if ($anlage->getShowCosPhiPowerDiag()) $dataArray['chart'][$counter]['cosPhi'] = $cosPhi * $actout;
                }

                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == true){
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
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
     * Erzeugt Daten für das Soll/Ist AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * AC - Actual & Expected, Groups
     */
    public function getAcExpGroupAC(Anlage $anlage, $from, $to, int $group = 1) : array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        $nameArray = $this->functions->getInverterNameArray($anlage , 'ac');
        $dataArray['inverterArray'] = $nameArray;
        $acGroups = $anlage->getGroupsAc();
        $sqlExpected = "SELECT a.stamp, sum(b.ac_exp_power) as soll
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";
        $result = $conn->query($sqlExpected);
        $maxInverter = 0;
        // add Irradiation
        if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == true){
            $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to, 'upper');
        } else {
            $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to);
        }

        // add Temperature
        // $panelTemparray = $this->getAirAndPanelTemp($anlage, $from, $to);

        if ($result->rowCount() > 0) {
            $counter = 0;
            switch ($anlage->getConfigType()) {

                case 3: // Groningen
                    $dataArray['offsetLegend'] = $group - 1;
                    break;
                default:
                    $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
            }
            $dataArray['label'] = $acGroups[$group]['GroupName'];

            while ($rowExp = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowExp["stamp"];
                ($rowExp['soll'] == null) ? $expected = 0 : $expected = $rowExp['soll'];

                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);

                $sqlIst = "SELECT sum(wr_pac) as actPower FROM " . $anlage->getDbNameIst() . " WHERE stamp = '$stampAdjust' AND wr_pac > 0 AND group_ac = '$group' GROUP BY unit";
                $resultIst = $conn->query($sqlIst);
                $counterInv = 1;
                if ($resultIst->rowCount() > 0) {
                    while ($rowIst = $resultIst->fetch(PDO::FETCH_ASSOC)) {
                        if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                        $actPower = $rowIst['actPower'];
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
                $counterInv--;
                ($counterInv > 0) ? $dataArray['chart'][$counter]['exp'] = $expected / $counterInv : $dataArray['chart'][$counter]['exp'] = $expected;

                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == true){
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                }

                // add Temperature
                // $dataArray['chart'][$counter]['panelTemp'] = $panelTemparray['chart'][$counter]["val2"];
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
     * AC - Inverter
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
                $sqlInv = "SELECT sum(wr_pac) as acinv, group_ac as inv_group FROM " . $anlage->getDbNameIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$invGroupSoll' GROUP BY inv";
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
    public function getActVoltageGroupAC(Anlage $anlage, $from, $to, int $group = 1):?array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        // Spannung für diesen Zeitraum und diese Gruppe
        #$sql_ist = "SELECT stamp, u_ac as uac_ist, group_ac as inv_group FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_ac ORDER BY group_ac * 1"; // 'wr_num * 1' damit die Sortierung als Zahl und nicht als Text erfolgt
        $sql_ist = "SELECT a.stamp, b.u_ac as uac_ist, u_ac_p1, u_ac_p2, u_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";

        $result = $conn->query($sql_ist);
        $counter = 0;
        $counterInv = 0;
        $dataArray['maxSeries'] = 0;
        $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
        $dataArray['label'] = $acGroups[$group]['GroupName'];
        #dd($dataArray);
        if ($result->rowCount() > 0) {

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $counterInv++;
                if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                $invGroupIst = $row["inv_group"];
                $stamp = $row["stamp"];

                $dataArray['chart'][$counter] = [
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    "date" => self::timeShift($anlage, $stamp),
                    "act" => round($row["uac_ist"], 2),
                    "u_ac_p1" => round($row["u_ac_p1"], 2),
                    "u_ac_p2" => round($row["u_ac_p2"], 2),
                    "u_ac_p3" => round($row["u_ac_p3"], 2),
                ];

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
    public function getActCurrentGroupAC(Anlage $anlage, $from, $to, int $group = 1):?array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        // Strom für diesen Zeitraum und diese Gruppe
        $sql_ist = "SELECT a.stamp, b.i_ac as iac_ist, i_ac_p1, i_ac_p2, i_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";

        $result = $conn->query($sql_ist);
        $counter = 0;
        $dataArray['maxSeries'] = 0;
        $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
        $dataArray['label'] = $acGroups[$group]['GroupName'];
        $counterInv = 1;
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                $stamp = $row["stamp"];
                $dataArray['chart'][$counter] = [
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    "date" => self::timeShift($anlage, $stamp),
                    "act" => round($row["iac_ist"], 2),
                    "i_ac_p1" => round($row["i_ac_p1"], 2),
                    "i_ac_p2" => round($row["i_ac_p2"], 2),
                    "i_ac_p3" => round($row["i_ac_p3"], 2),
                ];
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
    public function getActFrequncyGroupAC(Anlage $anlage, $from, $to, int $group = 1):?array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        // Frequenz für diesen Zeitraum und diese Gruppe
        $sql_ist = "SELECT a.stamp, b.frequency as frequency_ist 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";

        $result = $conn->query($sql_ist);
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
                    "act" => round($row["frequency_ist"], 2),
                ];

                $counter++;
            }
        }
        $conn = null;
        return $dataArray;
    }
}