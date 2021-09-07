<?php

namespace App\Service;

use App\Repository\InvertersRepository;
use App\Service\Charts\ACChartsService;
use App\Service\Charts\ForecastChartService;
use App\Service\Charts\IrradiationChartService;
use PDO;
use DateTime;
use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenStatusRepository;
use App\Repository\ForecastRepository;
use App\Repository\PVSystDatenRepository;
use App\Repository\PRRepository;
use Symfony\Component\Security\Core\Security;


class ChartService
{
    use G4NTrait;

    private Security $security;
    private AnlagenStatusRepository $statusRepository;
    private AnlageAvailabilityRepository $availabilityRepository;
    private PRRepository $prRepository;
    private PVSystDatenRepository $pvSystRepository;
    private InvertersRepository $invertersRepo;
    public functionsService $functions;
    private ForecastChartService $forecastChart;
    private ACChartsService $acCharts;
    private IrradiationChartService $irradiationChart;

    public function __construct(Security $security,
                                AnlagenStatusRepository $statusRepository,
                                AnlageAvailabilityRepository $availabilityRepository,
                                PRRepository $prRepository,
                                PVSystDatenRepository $pvSystRepository,
                                InvertersRepository $invertersRepo,
                                FunctionsService $functions,
                                ForecastChartService $forecastChart,
                                ACChartsService $acCharts,
                                IrradiationChartService $irradiationChart)
    {
        $this->security = $security;
        $this->statusRepository = $statusRepository;
        $this->availabilityRepository = $availabilityRepository;
        $this->prRepository = $prRepository;
        $this->pvSystRepository = $pvSystRepository;
        $this->invertersRepo = $invertersRepo;
        $this->functions = $functions;
        $this->forecastChart = $forecastChart;
        $this->acCharts = $acCharts;
        $this->irradiationChart = $irradiationChart;
    }

    /**
     * @param $form
     * @param Anlage|null $anlage
     * @return array
     */
    public function getGraphsAndControl($form, ?Anlage $anlage)
    {
        $resultArray = [];
        $resultArray['data'] = '';
        $resultArray['showEvuDiag'] = 0;
        $resultArray['showCosPhiDiag'] = 0;
        $resultArray['showCosPhiPowerDiag'] = 0;
        $resultArray['actSum'] = 0;
        $resultArray['expSum'] = 0;
        $resultArray['evuSum'] = 0;
        $resultArray['cosPhiSum'] = 0;
        $resultArray['headline'] = '';
        $resultArray['series1']['name'] = "";
        $resultArray['series1']['tooltipText'] = "";
        $resultArray['series2']['name'] = "";
        $resultArray['series2']['tooltipText'] = "";
        $resultArray['seriesx']['name'] = "";
        $resultArray['seriesx']['tooltipText'] = "";
        $resultArray['offsetLegende'] = 0;
        $resultArray['rangeValue'] = 0;
        $resultArray['maxSeries'] = 0;
        $resultArray['hasLink'] = false;
        $currentYear = date("Y");
        $currentMonth = date("m");
        $currentDay = date("d");

        //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
        if($form['backFromMonth']){
            $form['from'] =  date("Y-m-d 00:00", (strtotime($currentYear.'-'.$currentMonth.'-'.$currentDay) - (86400 * ($form['optionDate'] - 1))));
            $form['to'] =  date("Y-m-d 23:59", strtotime($currentYear.'-'.$currentMonth.'-'.$currentDay));
        }

        $from = self::timeShift($anlage, $form['from'],true);
        $to = self::timeShift($anlage, $form['to'],true);

        if ($anlage) {
            $showEvuDiag = $anlage->getShowEvuDiag();
            $showCosPhiPowerDiag = $anlage->getShowCosPhiPowerDiag();

            switch ($form['selectedChart']) {
                // AC Charts //
                case ("ac_single"):
                    $dataArray = $this->acCharts->getActExpAC($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['showEvuDiag'] = $showEvuDiag;
                        $resultArray['showCosPhiPowerDiag'] = $showCosPhiPowerDiag;
                        $resultArray['actSum'] = $dataArray['actSum'];
                        $resultArray['expSum'] = $dataArray['expSum'];
                        $resultArray['evuSum'] = $dataArray['evuSum'];
                        $resultArray['expEvuSum'] = $dataArray['expEvuSum'];
                        $resultArray['expNoLimitSum'] = $dataArray['expNoLimitSum'];
                        $resultArray['cosPhiSum'] = $dataArray['cosPhiSum'];
                        $resultArray['headline'] = 'AC production [kWh] – actual and expected';
                    }
                    break;
                case ("ac_act_group"):
                    $dataArray = $this->acCharts->getAcExpGroupAC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production by Group [kWh] – Actual and Expected';
                        $resultArray['series1']['name'] = "Expected";
                        $resultArray['series1']['tooltipText'] = "Expected ";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case ("ac_grp_power_diff"): // AC - Inverter
                    $dataArray = $this->acCharts->getGroupPowerDifferenceAC($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['hasLink'] = false;
                        $resultArray['rangeValue'] = $dataArray['rangeValue'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Inverter Production [kWh]';
                        $resultArray['series1']['name'] = "Actual Inverter ";
                        $resultArray['series1']['tooltipText'] = "Actual Inverter [kWh] Group ";
                    }
                    break;
                case ("ac_act_voltage"):
                    $dataArray = $this->acCharts->getActVoltageGroupAC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production Voltage [V] – Actual';
                        $resultArray['series0']['name'] = "Actual";
                        $resultArray['series0']['tooltipText'] = "Actual ";
                        $resultArray['series1']['name'] = "Actual_P1";
                        $resultArray['series1']['tooltipText'] = "Actual Phase 1";
                        $resultArray['series2']['name'] = "Actual_P2";
                        $resultArray['series2']['tooltipText'] = "Actual Phase 2";
                        $resultArray['series3']['name'] = "Actual_P3";
                        $resultArray['series3']['tooltipText'] = "Actual Phase 3";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                    }
                    break;
                case ("ac_act_current"):
                    $dataArray = $this->acCharts->getActCurrentGroupAC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production Current [A] – Actual';
                        $resultArray['series0']['name'] = "Actual";
                        $resultArray['series0']['tooltipText'] = "Actual ";
                        $resultArray['series1']['name'] = "Actual_P1";
                        $resultArray['series1']['tooltipText'] = "Actual Phase 1";
                        $resultArray['series2']['name'] = "Actual_P2";
                        $resultArray['series2']['tooltipText'] = "Actual Phase 2";
                        $resultArray['series3']['name'] = "Actual_P3";
                        $resultArray['series3']['tooltipText'] = "Actual Phase 3";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                    }
                    break;
                case ("ac_act_frequency"):
                    $dataArray = $this->acCharts->getActFrequncyGroupAC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production Frequency [HZ] – Actual';
                        $resultArray['series1']['name'] = "Actual";
                        $resultArray['series1']['tooltipText'] = "Actual ";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                    }
                    break;

                // DC Charts //
                case ("dc_single"):
                    $dataArray = $this->getActExpDC($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['actSum'] = $dataArray['actSum'];
                        $resultArray['expSum'] = $dataArray['expSum'];
                        $resultArray['headline'] = 'DC Production [kWh] – Actual and Expected';
                    }
                    break;
                case ("dc_act_group"):
                    $dataArray = $this->getActExpGroupDC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        //$resultArray['label'] = $dataArray['label'];
                        $resultArray['headline'] = 'DC Production by Group [kWh]';
                        $resultArray['series1']['name'] = "Expected";
                        $resultArray['series1']['tooltipText'] = "Expected ";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case ("dc_grp_power_diff"): // DC - Inverter (DC - Inverter Group)
                    $dataArray = $this->getGroupPowerDifferenceDC($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['hasLink'] = true;
                        $resultArray['rangeValue'] = $dataArray['rangeValue'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Inverter Production [kWh]';
                        $resultArray['series1']['name'] = "Expected";
                        $resultArray['series1']['tooltipText'] = "Expected [kWh]";
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Actual Inverter [kWh] Group ";
                    }
                    break;
                case ("dc_inv_power_diff"):
                    $dataArray = $this->getInverterPowerDifference($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['rangeValue'] = $dataArray['rangeValue'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Inverter Production [kWh]';
                        $resultArray['series1']['name'] = "Expected";
                        $resultArray['series1']['tooltipText'] = "Expected [kWh]";
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Actual Inverter [kWh] Group ";
                    }
                    break;
                case ("dc_current_group"):
                    $dataArray = $this->getCurrentGroupDc($anlage, $from, $to, $form['selectedSet']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['label'] = $dataArray['label'];
                        $resultArray['headline'] = 'DC Current [A] - all Groups';
                        $resultArray['series1']['name'] = "Expected Group";
                        $resultArray['series1']['tooltipText'] = "Expected Group ";
                        $resultArray['seriesx']['name'] = "Group ";
                        $resultArray['seriesx']['tooltipText'] = "Group ";
                    }
                    break;
                case ("dc_current_inverter"):
                    $dataArray = $this->getCurrentInverter($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Current [A]';
                        $resultArray['series1']['name'] = "Expected ";
                        $resultArray['series1']['tooltipText'] = "Expected current [[A]]";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Act current [A]";
                    }
                    break;
                case ("dc_current_mpp"):
                    $dataArray = $this->getCurrentMpp($anlage, $from, $to, $form['selectedInverter']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Current [A]';
                        $resultArray['seriesx']['name'] = "String ";
                        $resultArray['seriesx']['tooltipText'] = "Actuale current [A]";
                    }
                    break;
                case ("dc_voltage_groups"):
                    $dataArray = $this->getVoltageGroups($anlage, $from, $to, $form['selectedSet']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Group Electricity [V]';
                        $resultArray['seriesx']['name'] = "Group ";
                        $resultArray['seriesx']['tooltipText'] = "Group electricity [V]";
                    }
                    break;
                case ("dc_voltage_mpp"):
                    $dataArray = $this->getVoltageMpp($anlage, $from, $to, $form['selectedInverter']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Voltage [V]';
                        $resultArray['seriesx']['name'] = "String ";
                        $resultArray['seriesx']['tooltipText'] = "Voltage [V]";
                    }
                    break;
                case ("inverter_performance"):
                    $dataArray = $this->getInverterPerformance($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'Inverter Performance';
                        $resultArray['series1']['name'] = "";
                        $resultArray['series1']['tooltipText'] = "";
                        $resultArray['seriesx']['name'] = "";
                        $resultArray['seriesx']['tooltipText'] = "";
                    }
                    break;
                case ("irradiation"):
                    $dataArray = $this->irradiationChart->getIrradiation($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Irradiation [W/m²]';
                        $resultArray['series1']['name'] = ($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : "Incident upper table" ;
                        $resultArray['series1']['tooltipText'] = (($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : "Incident upper table") . " [W/m²]";
                        $resultArray['series2']['name'] = ($anlage->getWeatherStation()->getLabelLower() != '') ? $anlage->getWeatherStation()->getLabelLower() : "Incident lower table";
                        $resultArray['series2']['tooltipText'] = (($anlage->getWeatherStation()->getLabelLower() != '') ? $anlage->getWeatherStation()->getLabelLower() : "Incident lower table") . " [W/m²]";
                    }
                    break;
                case ("irradiation_one"):
                    $dataArray = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper');
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Irradiation [W/m²]';
                        $resultArray['series1']['name'] = ($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : "Incident" ;
                        $resultArray['series1']['tooltipText'] = (($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : "Incident") . " [W/m²]";
                    }
                    break;
                case ("irradiation_plant"):
                    $dataArray = $this->irradiationChart->getIrradiationPlant($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'Irradiation [w/m²]';
                        $resultArray['series1']['name'] = "Irr G4N";
                        $resultArray['series1']['tooltipText'] = "G4N";
                        $resultArray['seriesx']['name'] = "Irradiation ";
                        $resultArray['seriesx']['tooltipText'] = "Irradiation [w/m²]";
                        $resultArray["nameX"] = json_encode($dataArray["nameX"]);
                    }
                    break;
                case ("temp"):
                    $dataArray = $this->getAirAndPanelTemp($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Air and Panel Temperature [°C]';
                        $resultArray['series1']['name'] = "Air temperature";
                        $resultArray['series1']['tooltipText'] = "Air temperature [°C]";
                        $resultArray['series2']['name'] = "Panel temperature";
                        $resultArray['series2']['tooltipText'] = "Panel temperature [°C]";
                    }
                    break;
                case ("pr_and_av"):
                    $dataArray = $this->getPRandAV($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Performance Ratio and Availability';
                        $resultArray['series1']['name'] = "";
                        $resultArray['series1']['tooltipText'] = "";
                        $resultArray['series2']['name'] = "";
                        $resultArray['series2']['tooltipText'] = "";
                    }
                    break;
                case ("status_log"):
                    $resultArray['headline'] = 'Show status Log';
                    $resultArray['status'] = $this->statusRepository->findStatusAnlageDate($anlage, $from, $to);
                    break;
                case ("availability"):
                    $resultArray['headline'] = 'Show availability';
                    $resultArray['availability'] = $this->availabilityRepository->findAvailabilityAnlageDate($anlage, $from, $to);
                    break;
                case ("pvsyst"):
                    $resultArray['headline'] = 'Show PR & pvSyst';
                    $resultArray['pvSysts'] = $this->getpvSyst($anlage, $from, $to);
                    break;
                case ("forecast"):
                    if ($anlage->getUsePac()) {
                        $dataArray = $this->forecastChart->getForecastFac($anlage, $to);
                    } else {
                        $dataArray = $this->forecastChart->getForecastClassic($anlage, $to);
                    }
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Forecast';
                        $resultArray['series1']['name'] = "";
                        $resultArray['series1']['tooltipText'] = "";
                    }
                    break;
                case ("reactive_power"):
                    $dataArray = $this->getReactivePowerGroupAC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'Reactive power ';
                        $resultArray['series1']['name'] = "Actual";
                        $resultArray['series1']['tooltipText'] = "Actual ";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                    }
                    break;
                default:
                    $resultArray['headline'] = 'Something was wrong ' . $form['selectedChart'];
            }
        }

        return $resultArray;
    }

    ###########################################
    /**
     * DC Diagramme
     * Erzeugt Daten für das normale Soll/Ist DC Diagramm
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     * DC - Actual & Expected, Plant
     */
    public function getActExpDC(Anlage $anlage, $from, $to):?array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $sqlDcSoll = "SELECT a.stamp as stamp, sum(b.soll_pdcwr) as soll FROM (db_dummysoll a left JOIN " . $anlage->getDbNameDcSoll() . " b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP by a.stamp";

        $resulta = $conn->query($sqlDcSoll);
        $actSum = 0;
        $expSum = 0;
        // add Irradiation
        if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == true){
            $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to, 'upper');
        } else {
            $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to);
        }
        if ($resulta->rowCount() > 0) {
            $counter = 0;
            while ($roa = $resulta->fetch(PDO::FETCH_ASSOC)){
                $dcist = 0;
                $stamp = $roa["stamp"];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $soll = round($roa["soll"], 2);
                $expdiff = round($soll - $soll * 10 / 100, 2); //-10% good
                if ($anlage->getUseNewDcSchema()) {
                    $sql_b = "SELECT stamp, sum(wr_pdc) as dcist FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' GROUP by stamp LIMIT 1";
                } else {
                    $sql_b = "SELECT stamp, sum(wr_pdc) as dcist FROM " . $anlage->getDbNameIst() . " WHERE stamp = '$stampAdjust' GROUP by stamp LIMIT 1";
                }
                $resultb = $conn->query($sql_b);
                if ($resultb->rowCount() > 0) {
                    while ($rob = $resultb->fetch(PDO::FETCH_ASSOC)) {
                        $dcist = self::checkUnitAndConvert($rob["dcist"], $anlage->getAnlDbUnit());
                    }
                }

                ($dcist > 0) ? $dcist = round($dcist, 2) : $dcist = 0; // neagtive Werte auschließen
                $actSum += $dcist;
                $expSum += $soll;
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                if (!($soll == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['expected'] = $soll;
                    $dataArray['chart'][$counter]['expgood'] = $expdiff;
                }
                if (!($dcist == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['InvOut'] = $dcist;
                }
                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == true){
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                }
                $counter++;
            }
            $dataArray['actSum'] = round($actSum, 2);
            $dataArray['expSum'] = round($expSum, 2);
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
     * @return array
     * DC- Actual & Expected, Groups
     */
    public function getActExpGroupDC(Anlage $anlage, $from, $to, int $group = 1):array
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        $nameArray = $this->functions->getInverterNameArray($anlage, 'dc');
        $dataArray['inverterArray'] = $nameArray;
        $dcGroups = $anlage->getGroupsDc();
        $sqlExpected = "SELECT a.stamp, sum(b.soll_pdcwr) as soll 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE group_dc = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";
        $result = $conn->query($sqlExpected);
        $maxInverter = 0;
        // add Irradiation
        if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == true){
            $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to, 'upper');
        } else {
            $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to);
        }
        if ($result->num_rows > 0) {
            $counter = 0;
            $dataArray['offsetLegend'] = $dcGroups[$group]['GMIN'] - 1;
            while ($rowExp = $result->fetch_assoc()) {
                $stamp = $rowExp['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $anzInvPerGroup = $dcGroups[$group]['GMAX'] - $dcGroups[$group]['GMIN'] + 1;
                ($anzInvPerGroup > 0) ? $expected = $rowExp['soll'] / $anzInvPerGroup : $expected = $rowExp['soll'];
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                if (!($expected == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['exp'] = $expected;
                }
                if ($anlage->getUseNewDcSchema()) {
                    $sqlIst = "SELECT sum(wr_pdc) as actPower FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_group = '$group' GROUP BY wr_num";
                } else {
                    $sqlIst = "SELECT sum(wr_pdc) as actPower FROM " . $anlage->getDbNameAcIst() . " WHERE stamp = '$stampAdjust' AND group_dc = '$group' GROUP BY unit";
                }
                $resultIst = $conn->query($sqlIst);
                $counterInv = 1;
                if ($resultIst->num_rows > 0) {
                    while ($rowIst = $resultIst->fetch_assoc()) {
                        if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                        $actPower = self::checkUnitAndConvert($rowIst['actPower'], $anlage->getAnlDbUnit());
                        if (!($actPower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            $dataArray['chart'][$counter][$nameArray[$counterInv+$dataArray['offsetLegend']]] = $actPower;
                        }
                        if ($counterInv > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counterInv;
                        $counterInv++;
                    }
                } else {
                    for($counterInv = 1; $counterInv <= $maxInverter; $counterInv++) {
                        $dataArray['chart'][$counter][$nameArray[$counterInv+$dataArray['offsetLegend']]] = 0;
                    }
                }
                // add Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == true){
                    $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                } else {
                    $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                }
                $counter++;
            }
        }
        $conn->close();

        return $dataArray;
    }

    /**
     * erzeugt Daten für Gruppen Leistungs Unterschiede Diagramm (Group Power Difference)
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     * DC - Inverter / DC - Inverter Group // dc_grp_power_diff
     */
    public function getGroupPowerDifferenceDC(Anlage $anlage, $from, $to): array
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $istGruppenArray = [];
        $dcGroups = $anlage->getGroupsDc();
        // IST Strom für diesen Zeitraum nach Gruppen gruppiert
        if ($anlage->getUseNewDcSchema()) {
            $sqlIst = "SELECT sum(wr_pdc) as power_dc_ist, wr_group as inv_group FROM " . $anlage->getDbNameDCIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY wr_group ;";
        } else {
            $sqlIst = "SELECT sum(wr_pdc) as power_dc_ist, group_dc as inv_group FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_dc ;";
        }
        $resultIst = $conn->query($sqlIst);
        while ($rowIst = $resultIst->fetch_assoc()) { // Speichern des SQL ergebnisses in einem Array, Gruppe ist assosiativer Array Index
            $istGruppenArray[$rowIst['inv_group']] = $rowIst['power_dc_ist'];
        }
        // SOLL Strom für diesen Zeitraum nach Gruppen gruppiert
        $sql_soll = "SELECT stamp, sum(soll_pdcwr) as soll, wr_num as inv_group FROM " . $anlage->getDbNameDcSoll() . " 
                         WHERE stamp BETWEEN '$from' AND '$to' GROUP BY wr_num ORDER BY wr_num * 1"; // 'wr_num * 1' damit die Sortierung als Zahl und nicht als Text erfolgt

        $result = $conn->query($sql_soll);
        $counter = 0;
        if ($result->num_rows > 0) {
            $dataArray['maxSeries'] = 0;
            while ($row = $result->fetch_assoc()) {
                $dataArray['rangeValue'] = round($row["soll"], 2);
                $invGroupSoll = $row["inv_group"];
                $dataArray['chart'][$counter] = [
                    "category" => $dcGroups[$invGroupSoll]['GroupName'],
                    "link" => $invGroupSoll,
                    "exp" => round($row["soll"], 2),
                ];
                $dataArray['chart'][$counter]['act'] = round(self::checkUnitAndConvert($istGruppenArray[$invGroupSoll], $anlage->getAnlDbUnit()), 2);
                if ($counter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counter;
                $counter++;
            }
        }
        $conn->close();

        return $dataArray;
    }

    /**
     * erzeugt Daten für Inverter Leistungs Unterschiede Diagramm (Inverter Power Difference)
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $group
     * @return array
     * DC - Inverter // dc_inv_power_diff
     */
    public function getInverterPowerDifference(Anlage $anlage, $from, $to, $group): array
    {
        $conn = self::connectToDatabase();
        $dataArray = [];

        if (self::isDateToday($to)) {
            // letzten Eintrag in IST DB ermitteln
            $res = $conn->query("SELECT stamp FROM " . $anlage->getDbNameIst() . " WHERE stamp > '$from' ORDER BY stamp DESC LIMIT 1");
            if ($res) {
                $rowTemp = $res->fetch_assoc();
                $lastRecStampAct = strtotime($rowTemp['stamp']);
                $res->free();


                // letzten Eintrag in  Weather DB ermitteln
                $res = $conn->query("SELECT stamp FROM " . $anlage->getDbNameDcSoll() . " WHERE stamp > '$from' ORDER BY stamp DESC LIMIT 1");
                if ($res) {
                    $rowTemp = $res->fetch_assoc();
                    $lastRecStampExp = strtotime($rowTemp['stamp']);
                    $res->free();
                    ($lastRecStampAct <= $lastRecStampExp) ? $toLastBoth = self::formatTimeStampToSql($lastRecStampAct) : $toLastBoth = self::formatTimeStampToSql($lastRecStampExp);
                    $to = $toLastBoth;
                }
            }
        }

        // Leistung für diesen Zeitraum und diese Gruppe
        $sql_soll = "SELECT stamp, sum(soll_pdcwr) as soll FROM " . $anlage->getDbNameDcSoll() . " WHERE stamp BETWEEN '$from' AND '$to' AND wr_num = '$group' GROUP BY wr LIMIT 1";
        $result = $conn->query($sql_soll);
        $counter = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dataArray['rangeValue'] = round($row["soll"], 2);
                $dataArray['chart'][] = [
                    "category" => 'expected',
                    "val" => round($row["soll"], 2),
                    "color" => '#fdd400',
                ];
                if ($anlage->getUseNewDcSchema()) {
                    $sqlInv = "SELECT sum(wr_pdc) as dcinv, wr_num AS inverter FROM " . $anlage->getDbNameDCIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND wr_group = '$group' GROUP BY wr_num";
                } else {
                    $sqlInv = "SELECT sum(wr_pdc) as dcinv, unit AS inverter FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$group' GROUP BY unit";
                }
                $resultInv = $conn->query($sqlInv);
                if ($resultInv->num_rows > 0) {
                    $wrcounter = 0;
                    while ($rowInv = $resultInv->fetch_assoc()) {
                        $wrcounter++;
                        $inverter = $rowInv['inverter'];
                        $dataArray['chart'][] = [
                            "category" => "Inverter #$inverter",
                            "val" => self::checkUnitAndConvert($rowInv['dcinv'], $anlage->getAnlDbUnit()),
                            "link" => "$inverter",
                        ];
                        if ($wrcounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $wrcounter;
                    }
                }
                $counter++;
            }
        }
        $conn->close();

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
                        // ACHTUNG Strom und Spannungs Werte werden im Moment (Sep2020) immer in der AC TAbelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
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
        $conn = self::connectToDatabase();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;

        // Strom für diesen Zeitraum und diesen Inverter
        $sql_strom = "SELECT a.stamp as stamp, b.soll_imppwr as sollCurrent FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE wr_num = '$group') b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY a.stamp ORDER BY a.stamp";
        $result = $conn->query($sql_strom);
        if ($result->num_rows > 0) {
            $counter = 0;
            $dataArray['offsetLegend'] = $dcGroups[$group]['GMIN'] - 1;
            while ($row = $result->fetch_assoc()) {
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
                    if ($resultIst->num_rows > 0) {
                        $rowIst = $resultIst->fetch_assoc();
                        $currentIst = round($rowIst['istCurrent'], 2);
                        if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            $dataArray['chart'][$counter]["val$mppCounter"] = $currentIst;
                        }
                    }
                }
                if ($mppCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $mppCounter;
                $counter++;
            }
        }
        $conn->close();

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

    /**
     * erzeugt Daten für Inverter Performance Diagramm (DC vs AC Leistung der Inverter)
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $group
     * @return array
     *  // inverter_performance
     */
    public function getInverterPerformance(Anlage $anlage, $from, $to, $group): array
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $sql = "SELECT stamp, sum(wr_pac) AS power_ac, sum(wr_pdc) AS power_dc, unit AS inverter  FROM " . $anlage->getDbNameIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$group' GROUP by unit";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $inverter = $row['inverter'];
                $powerDc = self::checkUnitAndConvert($row['power_dc'], $anlage->getAnlDbUnit());
                $powerAc = self::checkUnitAndConvert($row['power_ac'], $anlage->getAnlDbUnit());
                $dataArray['chart'][] = [
                    "inverter" => "Inverter #$inverter",
                    "valDc" => $powerDc,
                    "valAc" => $powerAc,
                ];
            }
            $dataArray['maxSeries'] = 0;
            $dataArray['startCounterInverter'] = 10;
        }
        $conn->close();

        return $dataArray;
    }

    /**
     * Erzeugt Daten für Temperatur Diagramm
     * @param $anlage
     * @param $from
     * @param $to
     * @return array
     *  //
     */
    public function getAirAndPanelTemp(Anlage $anlage, $from, $to): array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $counter = 0;
        $sql2 = "SELECT a.stamp, b.at_avg , b.pt_avg FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' ORDER BY a.stamp";
        $res = $conn->query($sql2);
        while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
            $atavg = $ro["at_avg"];
            if (!$atavg) {
                $atavg = 0;
            }
            $ptavg = $ro["pt_avg"];
            if (!$ptavg) {
                $ptavg = 0;
            }
            $atavg = str_replace(',', '.', $atavg);
            $ptavg = str_replace(',', '.', $ptavg);

            $stamp = $ro["stamp"];  #utc_date($stamp,$anintzzws);
            if ($ptavg != "#") {
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]["date"] = self::timeShift($anlage, $stamp);
                if (!($atavg + $ptavg == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]["val1"] = $atavg; // upper pannel
                    $dataArray['chart'][$counter]["val2"] = $ptavg; // lower pannel
                }
            }
            $counter++;
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
    public function getReactivePowerGroupAC(Anlage $anlage, $from, $to, int $group = 1): array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        // Blindleistung für diesen Zeitraum und diese Gruppe
        $sql_ist = "SELECT a.stamp, sum(b.p_ac_blind) as p_ac_blind 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '2') b ON a.stamp = b.stamp) 
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
                    "act" => round($row["p_ac_blind"], 2),
                ];

                $counter++;
            }
        }
        $conn = null;
        return $dataArray;
    }


    /**
     * Erzeuge Daten für PR und AV
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     *  // pr_and_av
     */
    public function getPRandAV(Anlage $anlage, $from, $to): array
    {
        $prs = $this->prRepository->findPrAnlageDate($anlage, $from, $to);
        $dataArray = [];
        $counter = 0;
        /** @var AnlagenPR $pr */
        foreach ($prs as $pr) {
            $stamp = $pr->getstamp()->format('Y-m-d');
            //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
            $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
            if($anlage->getShowEvuDiag()) {
                $dataArray['chart'][$counter]['pr_act'] = $pr->getPrEvu();
            } else {
                $dataArray['chart'][$counter]['pr_act'] = $pr->getPrAct();
            }
            $av = $this->availabilityRepository->sumAvailabilityPerDay($anlage->getAnlId(), $stamp);
            $dataArray['chart'][$counter]['av'] = round($av, 2);
            $counter++;
        }

        return $dataArray;
    }

    /**
     * PV SystWerte als Diagramm ausgeben
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     * @deprecated
     */
    public function getpvSyst(Anlage $anlage, $from, $to): array
    {
        $dataArray = [];
        $prs = $this->prRepository->findPrAnlageDate($anlage, $from, $to);
        $counter = 0;
        /** @var AnlagenPR $pr */
        foreach ($prs as $pr) {
            $stamp = $pr->getstamp()->format('Y-m-d');
            $dataArray[$counter]['date'] = $stamp;
            $dataArray[$counter]['pr'] = $pr;
            $pvSyst = $this->pvSystRepository->sumByStamp($anlage, $stamp);
            $dataArray[$counter]['electricityGrid'] = round($pvSyst[0]['eGrid'] / 1000, 2); // durch 100 um auf kWh zu kommen
            $dataArray[$counter]['electricityInverter'] = round($pvSyst[0]['eInverter'] / 1000, 2); // durch 100 um auf kWh zu kommen
            $counter++;
        }

        return $dataArray;
    }


}