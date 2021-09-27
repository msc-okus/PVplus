<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use Symfony\Component\Security\Core\Security;
use PDO;

class CurrentChartService
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

    public function getCurrentOverviewDc(Anlage $anlage, $from, $to): array
    {
        $conn = self::getPdoConnection();
        $acGroups = $anlage->getGroupsAc();
        $dataArray = [];
        $nameArray = $this->functions->getNameArray($anlage , 'ac');
        $dataArray['inverterArray'] = $nameArray;

        // Strom für diesen Zeitraum und diese Gruppe
        $sql_time = "SELECT stamp FROM db_dummysoll WHERE stamp BETWEEN '$from' AND '$to'";
        $result = $conn->query($sql_time);
        if ($result->rowCount() > 0) {
            $counter = 0;
            while ($rowSoll = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowSoll['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlDbUnit());
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                foreach ($acGroups as $acGroupKey => $acGroup) {

                    // ermittle SOLL Strom nach Gruppen für diesen Zeitraum
                    // ACHTUNG Strom und Spannungswerte werden im Moment (Sep2020) immer in der AC TAbelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
                    if ($anlage->getUseNewDcSchema()) {
                        $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND group_ac = '$acGroupKey'";
                    } else {
                        $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameACIst() . " WHERE stamp = '$stampAdjust' AND group_ac = '$acGroupKey'";
                    }
                    $resultIst = $conn->query($sql);
                    if ($resultIst->rowCount() > 0) {
                        $rowIst = $resultIst->fetch(PDO::FETCH_ASSOC);
                        $currentIst = round($rowIst['istCurrent'], 2);
                        if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            switch ($anlage->getConfigType()) {

                                case 3: // Groningen
                                    break;
                                default:
                                    $dataArray['chart'][$counter][$nameArray[$acGroupKey]] = $currentIst;
                            }
                        }
                    }
                    $dataArray['label'][$acGroupKey] = $nameArray[$acGroupKey];
                }
                $counter++;
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
     * @return array
     * dc_current_group
     */
    public function getCurrentGroupDc(Anlage $anlage, $from, $to, int $set = 1): array
    {
        $conn = self::connectToDatabase();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];

        // Strom für diesen Zeitraum und diese Gruppe
        $sql_time = "SELECT stamp FROM db_dummysoll WHERE stamp BETWEEN '$from' AND '$to'";
        $result = $conn->query($sql_time);
        if ($result->num_rows > 0) {
            $counter = 0;
            while ($rowSoll = $result->fetch_assoc()) {
                $stamp = $rowSoll['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlDbUnit());
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                $gruppenProSet = 1;
                foreach ($dcGroups as $dcGroupKey => $dcGroup) {
                    if($dcGroupKey > (($set - 1) * 10) && $dcGroupKey <= ($set * 10) ) {
                        // ermittle SOLL Strom nach Gruppen für diesen Zeitraum
                        // ACHTUNG Strom und Spannungswerte werden im Moment (Sep2020) immer in der AC TAbelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
                        if ($anlage->getUseNewDcSchema()) {
                            $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_group = '$dcGroupKey'";
                        } else {
                            $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameACIst() . " WHERE stamp = '$stampAdjust' AND group_dc = '$dcGroupKey'";
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
        $conn->close();

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das DC Strom Diagram Diagramm, eine Linie je Inverter gruppiert nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     *  // dc_current_inverter
     */
    public function getCurrentInverter(Anlage $anlage, $from, $to, int $group = 1): array
    {
        $conn = self::getPdoConnection();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        switch ($anlage->getConfigType()) {

            case 3: // Groningen
                $nameArray = $this->functions->getNameArray($anlage , 'scb');
                break;
            default:
                $nameArray = $this->functions->getNameArray($anlage , 'dc');
        }
        $dataArray['inverterArray'] = $nameArray;

        // Strom für diesen Zeitraum und diesen Inverter
        $sql_strom = "SELECT a.stamp as stamp, b.soll_imppwr as sollCurrent FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE wr_num = '$group') b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY a.stamp ORDER BY a.stamp";
        $result = $conn->query($sql_strom);
        if ($result->rowCount() > 0) {
            $counter = 0;
            $dataArray['offsetLegend'] = $dcGroups[$group]['GMIN'] - 1;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $row['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                $currentExp = round($row['sollCurrent'], 2);
                if($currentExp === null) $currentExp = 0;
                if (!($currentExp == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]["soll"] = $currentExp;
                }
                $mppCounter = 0;

                for ($inverter = $dcGroups[$group]['GMIN']; $inverter <= $dcGroups[$group]['GMAX']; $inverter++) {
                    $mppCounter++;
                    if ($anlage->getUseNewDcSchema()) {
                        $sql = "SELECT wr_idc as istCurrent FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_num = '$inverter'";
                    } else {
                        $sql ="SELECT wr_idc as istCurrent FROM " . $anlage->getDbNameAcIst() . " WHERE stamp = '$stampAdjust' AND unit = '$inverter'";
                    }
                    $resultIst = $conn->query($sql);
                    if ($resultIst->rowCount() > 0) {
                        $rowIst = $resultIst->fetch(PDO::FETCH_ASSOC);
                        $currentIst = round($rowIst['istCurrent'], 2);
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
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das DC Strom Diagram Diagramm, eine Linie je MPP gruppiert nach Inverter
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $inverter
     * @return array|false
     *  // dc_current_mpp
     */
    public function getCurrentMpp(Anlage $anlage, $from, $to, int $inverter = 1): array
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;

        // Strom für diesen Zeitraum und diesen Inverter
        // ACHTUNG Strom und Spannungs Werte werden im Moment (Sep2020) immer in der AC Tabelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
        if ($anlage->getUseNewDcSchema()) {
            $sql_strom = "SELECT a.stamp as stamp, b.wr_mpp_current AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDCIst() . " WHERE wr_num = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
        } else {
            $sql_strom = "SELECT a.stamp as stamp, b.wr_mpp_current AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE unit = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
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
                                $dataArray['chart'][$counter]["val$mppCounter"] = $mppCurrentValue;
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