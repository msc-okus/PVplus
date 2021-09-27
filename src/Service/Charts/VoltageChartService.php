<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use Symfony\Component\Security\Core\Security;
use PDO;

class VoltageChartService
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
     * Erzeugt Daten für das DC Spannung Diagram Diagramm, eine Linie je Inverter gruppiert nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $set
     * @return array
     *  // dc_current_inverter
     */
    public function getVoltageGroups(Anlage $anlage, $from, $to, int $set = 1): array
    {
        $conn = self::connectToDatabase();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];
        // Spannung für diesen Zeitraum und diese Gruppe
        $sql_time = "SELECT stamp FROM db_dummysoll WHERE stamp BETWEEN '$from' AND '$to'";
        $result = $conn->query($sql_time);
        if ($result->num_rows > 0) {
            $counter = 0;
            while ($rowSoll = $result->fetch_assoc()) {
                $stamp = $rowSoll['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                $gruppenProSet = 1;
                foreach ($dcGroups as $dcGroupKey => $dcGroup) {
                    if($dcGroupKey > (($set - 1) * 10) && $dcGroupKey <= ($set * 10) ) {
                        // ermittle Spannung für diese Zeit und diese Gruppe
                        if ($anlage->getUseNewDcSchema()) {
                            $sql ="SELECT AVG(wr_udc) as actVoltage FROM " . $anlage->getDbNameDcIst() . " WHERE stamp = '$stampAdjust' AND wr_group = '$dcGroupKey'";
                        } else {
                            $sql ="SELECT AVG(wr_udc) as actVoltage FROM " . $anlage->getDbNameAcIst() . " WHERE stamp = '$stampAdjust' AND group_ac = '$dcGroupKey'";
                        }
                        $resultIst = $conn->query($sql);
                        if ($resultIst->num_rows == 1) {
                            $rowIst = $resultIst->fetch_assoc();
                            $voltageAct = round($rowIst['actVoltage'], 2);
                            if (!($voltageAct == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$gruppenProSet"] = $voltageAct;
                            }
                        }
                        $dataArray['label'][$dcGroupKey] = $dcGroup['GroupName'];
                        $dataArray['maxSeries'] = $gruppenProSet; //count($dcGroups);
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
     * Erzeugt Daten für das DC Spannungs Diagram Diagramm, eine Linie je MPP gruppiert nach Inverter
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $inverter
     * @return array|false
     *  // dc_voltage_mpp
     */
    public function getVoltageMpp(Anlage $anlage, $from, $to, int $inverter = 1): array
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;

        // Strom für diesen Zeitraum und diesen Inverter
        // ACHTUNG Strom und Spannungs Werte werden im Moment (Sep2020) immer in der AC TAbelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
        $sql_voltage = "SELECT a.stamp as stamp, b.wr_mpp_voltage AS mpp_voltage FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE unit = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
        $result = $conn->query($sql_voltage);
        if ($result != false) {
            if ($result->num_rows > 0) {
                $counter = 0;
                while ($row = $result->fetch_assoc()) {
                    $stamp = self::timeAjustment($row['stamp'], (int)$anlage->getAnlZeitzone(), true);
                    $mppVoltageJson = $row['mpp_voltage'];
                    if ($mppVoltageJson != '') {
                        $mppvoltageArray = json_decode($mppVoltageJson);
                        //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                        $mppCounter = 1;
                        foreach ($mppvoltageArray as $mppVoltageItem => $mppVoltageValue) {
                            if (!($mppVoltageValue == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$mppCounter"] = $mppVoltageValue;
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