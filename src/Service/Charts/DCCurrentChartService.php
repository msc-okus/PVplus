<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use Symfony\Component\Security\Core\Security;
use PDO;

class DCCurrentChartService
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
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @param bool $hour
     * @return array
     */
    public function getCurr1(Anlage $anlage, $from, $to, $group = 1,  bool $hour = false): array
    {
        if ($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $acGroups = $anlage->getGroupsAc();
        $dataArray = [];
        $inverterNr = 0;
        switch ($anlage->getConfigType()) {
            case 1: // Andjik
            case 3: // Groningen
            case 4: //
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
                break;
            default:
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
        }
        $dataArray['inverterArray'] = $nameArray;

        // SOLL Strom für diesen Zeitraum und diese Gruppe
        $sqlExp = "SELECT a.stamp as stamp, sum(b.dc_exp_current) as expected
                FROM (db_dummysoll a LEFT JOIN (SELECT stamp, dc_exp_current, group_ac FROM " . $anlage->getDbNameDcSoll() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";

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
                if (!($rowSoll['expected'] == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    if (!$hour) $dataArray['chart'][$counter]['expected'] = $rowSoll['expected'] / ($acGroups[$group]['GMAX'] - $acGroups[$group]['GMIN']);
                    else $dataArray['chart'][$counter]['expected'] = ($rowSoll['expected'] / ($acGroups[$group]['GMAX'] - $acGroups[$group]['GMIN'])) / 4;
                }
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das DC Strom Diagram Diagramm, eine Linie je Gruppe
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $set
     * @param bool $hour
     * @return array
     * dc_current_group
     */
    public function getCurr2(Anlage $anlage, $from, $to, int $set = 1,  bool $hour = false): array
    {

        if ($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];

        // Strom für diesen Zeitraum und diese Gruppe
        $sql_time = "SELECT stamp FROM db_dummysoll WHERE stamp BETWEEN '$from' AND '$to' GROUP BY date_format(stamp, '$form')";
        $result = $conn->query($sql_time);
        if ($result->rowCount() > 0) {
            $counter = 0;
            while ($rowSoll = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowSoll['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                $gruppenProSet = 1;
                foreach ($dcGroups as $dcGroupKey => $dcGroup) {

                    if ($dcGroupKey > (($set - 1) * 10) && $dcGroupKey <= ($set * 10)) {
                        // ermittle SOLL Strom nach Gruppen für diesen Zeitraum
                        // ACHTUNG Strom und Spannungswerte werden im Moment (Sep2020) immer in der AC TAbelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
                        if ($hour) {
                            if ($anlage->getUseNewDcSchema()) {
                                $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameDCIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND wr_group = '$dcGroupKey' GROUP BY date_format(stamp, '$form')";
                            } else {
                                $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameACIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2'  AND group_dc = '$dcGroupKey' GROUP BY date_format(stamp, '$form')";
                            }
                        } else {
                            if ($anlage->getUseNewDcSchema()) {
                                $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_group = '$dcGroupKey'";
                            } else {
                                $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameACIst() . " WHERE stamp = '$stampAdjust' AND group_dc = '$dcGroupKey'";
                            }
                        }
                        $resultIst = $conn->query($sql);

                        if ($resultIst->num_rows > 0) {
                            $rowIst = $resultIst->fetch_assoc();
                            $currentIst = round($rowIst['istCurrent'], 2);
                            if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$gruppenProSet"] = $currentIst;
                            }
                        }
                        $dataArray['maxSeries'] = $gruppenProSet;
                        $dataArray['label'][$dcGroupKey] = $dcGroup['GroupName'];
                        $gruppenProSet++;
                    }

                }
                $counter++;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das DC Strom Diagram Diagramm, eine Linie je Inverter gruppiert nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @param bool $hour
     * @return array
     *  // dc_current_inverter
     */
    public function getCurr3(Anlage $anlage, $from, $to, int $group = 1,  bool $hour = false): array
    {
        if(false) {
            if ($hour) $form = '%y%m%d%H';
            else $form = '%y%m%d%H%i';
            $conn = self::getPdoConnection();
            $dcGroups = $anlage->getGroupsDc();
            $dataArray = [];
            $dataArray['maxSeries'] = 0;
            switch ($anlage->getConfigType()) {

                case 3: // Groningen
                    $nameArray = $this->functions->getNameArray($anlage, 'scb');
                    break;
                default:
                    $nameArray = $this->functions->getNameArray($anlage, 'dc');
            }
            $dataArray['inverterArray'] = $nameArray;

            $sql_strom = "SELECT a.stamp as stamp, b.soll_imppwr as sollCurrent 
                      FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE wr_num = '$group') b ON a.stamp = b.stamp) 
                      WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY date_format(a.stamp, '$form')";

            if ($anlage->getUseNewDcSchema()) {

                $sql = "SELECT sum(wr_idc) as istCurrent
                    FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDCIst() . " WHERE wr_group ='$group') b ON a.stamp = b.stamp) 
                    WHERE a.stamp BETWEEN '$from' AND '$to' 
                    GROUP BY date_format(a.stamp, '$form'), wr_num";
            } else {

                $sql = "SELECT sum(wr_idc) as istCurrent 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_dc ='$group') b ON a.stamp = b.stamp) 
                         WHERE a.stamp BETWEEN '$from' AND '$to' 
                         GROUP BY date_format(a.stamp, '$form'), unit";
            }


            $resultIst = $conn->query($sql);
            $result = $conn->query($sql_strom);
            if ($result->rowCount() > 0) {
                $counter = 0;
                $dataArray['offsetLegend'] = $dcGroups[$group]['GMIN'] - 1;
                while ($rowExp = $result->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = $rowExp['stamp'];
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                    $currentExp = round($rowExp['sollCurrent'], 2);
                    if ($currentExp === null) $currentExp = 0;
                    if (!($currentExp == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                        $dataArray['chart'][$counter]["soll"] = $currentExp;
                    }
                    $mppCounter = 0;

                    for ($inverter = $dcGroups[$group]['GMIN']; $inverter <= $dcGroups[$group]['GMAX']; $inverter++) {
                        $mppCounter++;

                        $rowIst = $resultIst->fetch(PDO::FETCH_ASSOC);
                        $currentIst = round($rowIst['istCurrent'], 2);
                        if ($hour) $currentIst = $currentIst / 4;
                        if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            //$dataArray['chart'][$counter]["val$mppCounter"] = $currentIst;
                            switch ($anlage->getConfigType()) {

                                case 3: // Groningen
                                    $dataArray['chart'][$counter][$nameArray[$inverter]] = $currentIst;
                                    break;
                                default:
                                    $dataArray['chart'][$counter][$nameArray[$inverter]] = $currentIst;
                            }
                        }
                        $dataArray['label'][$inverter] = $nameArray[$inverter];
                    }
                    if ($mppCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $mppCounter;
                    $counter++;
                }
            }
        }
        else {
            ($hour) ? $form = '%y%m%d%H' : $form = '%y%m%d%H%i';
            $conn = self::getPdoConnection();
            $dcGroups = $anlage->getGroupsDc();
            $dataArray = [];
            $dataArray['maxSeries'] = 0;
            switch ($anlage->getConfigType()) {

                case 3: // Groningen
                    $nameArray = $this->functions->getNameArray($anlage, 'scb');
                    break;
                default:
                    $nameArray = $this->functions->getNameArray($anlage, 'ac');
            }
            $dataArray['inverterArray'] = $nameArray;

            // Strom für diesen Zeitraum und diesen Inverter
            $sql_strom = "SELECT a.stamp as stamp, b.soll_imppwr as sollCurrent 
                      FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE wr_num = '$group') b ON a.stamp = b.stamp) 
                      WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY date_format(a.stamp, '$form')";
            $result = $conn->query($sql_strom);
            if ($result->rowCount() > 0) {
                $counter = 0;
                $dataArray['offsetLegend'] = $dcGroups[$group]['GMIN'] - 1;
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = $row['stamp'];
                    $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                    $stampAdjust2 = self::timeAjustment($stampAdjust, 1);
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms

                    $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                    $currentExp = round($row['sollCurrent'], 2);
                    if ($currentExp === null) $currentExp = 0;
                    if (!($currentExp == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                        $dataArray['chart'][$counter]["soll"] = $currentExp;
                    }
                    $mppCounter = 0;

                    for ($inverter = $dcGroups[$group]['GMIN']; $inverter <= $dcGroups[$group]['GMAX']; $inverter++) {
                        $mppCounter++;
                        if ($hour) {
                            if ($anlage->getUseNewDcSchema()) {
                                $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameDCIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND wr_num = '$inverter' GROUP BY date_format(stamp, '$form')";
                            } else {
                                $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameAcIst() . " WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' AND unit = '$inverter' GROUP BY date_format(stamp, '$form')";
                            }
                        } else {
                            if ($anlage->getUseNewDcSchema()) {
                                $sql = "SELECT wr_idc as istCurrent FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_num = '$inverter' GROUP BY date_format(stamp, '$form')";
                            } else {
                                $sql = "SELECT wr_idc as istCurrent FROM " . $anlage->getDbNameAcIst() . " WHERE stamp = '$stampAdjust' AND unit = '$inverter' GROUP BY date_format(stamp, '$form')";
                            }
                        }
                        $resultIst = $conn->query($sql);
                        if ($resultIst->rowCount() > 0) {
                            $rowIst = $resultIst->fetch(PDO::FETCH_ASSOC);
                            $currentIst = round($rowIst['istCurrent'], 2);
                            if ($hour) $currentIst = $currentIst / 4;
                            if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                //$dataArray['chart'][$counter]["val$mppCounter"] = $currentIst;
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
                    if ($mppCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $mppCounter;
                    $counter++;
                }
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das DC Strom Diagram Diagramm, eine Linie je MPP gruppiert nach Inverter
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $inverter
     * @param bool $hour
     * @return bool|array // dc_current_mpp
     *  // dc_current_mpp
     */
    public function getCurr4(Anlage $anlage, $from, $to, int $inverter = 1,  bool $hour = false): bool|array
    {
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::connectToDatabase();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;

        // Strom für diesen Zeitraum und diesen Inverter
        // ACHTUNG Strom und Spannungs Werte werden im Moment (Sep2020) immer in der AC Tabelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.

        if($hour){if ($anlage->getUseNewDcSchema()) {
            $sql_strom = "SELECT a.stamp as stamp, sum(b.wr_mpp_current) AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDCIst() . " WHERE wr_num = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";
        } else {
            $sql_strom = "SELECT a.stamp as stamp, sum(b.wr_mpp_current) AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE unit = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";
        }
    }
    else{
        if ($anlage->getUseNewDcSchema()) {
            $sql_strom = "SELECT a.stamp as stamp, b.wr_mpp_current AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDCIst() . " WHERE wr_num = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
        } else {
            $sql_strom = "SELECT a.stamp as stamp, b.wr_mpp_current AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE unit = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
        }

    }
        $result = $conn->query($sql_strom);
        if ($result != false) {
            if ($result->num_rows > 0) {
                $counter = 0;
                while ($row = $result->fetch_assoc()) {
                    $stamp = self::timeAjustment($row['stamp'], (int)$anlage->getAnlZeitzone(), true);
                    //$stamp = $row['stamp'];
                    $mppCurrentJson = $row['mpp_current'];
                    if ($mppCurrentJson != '') {
                        $mppCurrentArray = json_decode($mppCurrentJson);
                        //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                        $mppCounter = 1;
                        foreach ($mppCurrentArray as $mppCurrentItem => $mppCurrentValue) {
                            if (!($mppCurrentValue == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                if($hour)$dataArray['chart'][$counter]["val$mppCounter"] = $mppCurrentValue/4;
                                else $dataArray['chart'][$counter]["val$mppCounter"] = $mppCurrentValue;
                            }
                            $mppCounter++;
                        }
                        if ($mppCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $mppCounter;
                        $counter++;
                    }
                }
            }
            $conn->close();

            return $dataArray;
        } else {
            $conn->close();

            return false;
        }
    }
}