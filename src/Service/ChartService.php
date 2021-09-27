<?php

namespace App\Service;

use App\Repository\InvertersRepository;
use App\Service\Charts\ACChartsService;
use App\Service\Charts\CurrentChartService;
use App\Service\Charts\DCChartService;
use App\Service\Charts\ForecastChartService;
use App\Service\Charts\IrradiationChartService;
use App\Service\Charts\VoltageChartService;
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
    private DCChartService $dcChart;
    private CurrentChartService $currentChart;
    private VoltageChartService $voltageChart;

    public function __construct(Security $security,
                                AnlagenStatusRepository $statusRepository,
                                AnlageAvailabilityRepository $availabilityRepository,
                                PRRepository $prRepository,
                                PVSystDatenRepository $pvSystRepository,
                                InvertersRepository $invertersRepo,
                                FunctionsService $functions,
                                ForecastChartService $forecastChart,
                                ACChartsService $acCharts,
                                DCChartService $dcChart,
                                CurrentChartService $currentChart,
                                VoltageChartService $voltageChart,
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
        $this->dcChart = $dcChart;
        $this->currentChart = $currentChart;
        $this->voltageChart = $voltageChart;
    }

    /**
     * @param $form
     * @param Anlage|null $anlage
     * @return array
     */
    public function getGraphsAndControl($form, ?Anlage $anlage): array
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
        if (isset($form['backFromMonth'])) {
            if ($form['backFromMonth'] === true) {
                $form['from']   = date("Y-m-d 00:00", (strtotime($currentYear.'-'.$currentMonth.'-'.$currentDay) - (86400 * ($form['optionDate'] - 1))));
                $form['to']     = date("Y-m-d 23:59", strtotime($currentYear.'-'.$currentMonth.'-'.$currentDay));
            }
        }

        $from   = self::timeShift($anlage, $form['from'],true);
        $to     = self::timeShift($anlage, $form['to'],true);

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
                    $dataArray = $this->acCharts->getActExpGroupAC($anlage, $from, $to, $form['selectedGroup']);
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
                case ("ac_act_overview"):
                    $dataArray = $this->acCharts->getActExpOverview($anlage, $from, $to, $form['selectedGroup']);
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

                case ("reactive_power"):
                    $dataArray = $this->acCharts->getReactivePowerGroupAC($anlage, $from, $to, $form['selectedGroup']);
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

                // DC Charts //
                case ("dc_single"):
                    $dataArray = $this->dcChart->getActExpDC($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['actSum'] = $dataArray['actSum'];
                        $resultArray['expSum'] = $dataArray['expSum'];
                        $resultArray['headline'] = 'DC Production [kWh] – Actual and Expected';
                    }
                    break;
                case ("dc_act_overview"):
                    $dataArray = $this->dcChart->getActExpOverviewDc($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Production [kWh]';
                        $resultArray['series1']['name'] = "Expected ";
                        $resultArray['series1']['tooltipText'] = "Expected [[kWh]]";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Act [kWh]";
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case ("dc_act_group"):
                    $dataArray = $this->dcChart->getActExpGroupDC($anlage, $from, $to, $form['selectedGroup']);
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
                    $dataArray = $this->dcChart->getGroupPowerDifferenceDC($anlage, $from, $to);
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
                    $dataArray = $this->dcChart->getInverterPowerDifference($anlage, $from, $to, $form['selectedGroup']);
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

                // Current Charts DC //
                // Übersicht Strom auf Basis der AC Gruppe
                //
                case ('dc_current_overview'):
                    $dataArray = $this->currentChart->getCurrentOverviewDc($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['headline'] = 'DC Current [A] - overview';
                        $resultArray['series1']['name'] = "Expected";
                        $resultArray['series1']['tooltipText'] = "Expected";
                        $resultArray['seriesx']['name'] = "";
                        $resultArray['seriesx']['tooltipText'] = "";
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case ("dc_current_group"):
                    $dataArray = $this->currentChart->getCurrentGroupDc($anlage, $from, $to, $form['selectedSet']);
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
                    $dataArray = $this->currentChart->getCurrentInverter($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Current [A]';
                        $resultArray['series1']['name'] = "Expected ";
                        $resultArray['series1']['tooltipText'] = "Expected current [[A]]";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Act current [A]";
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case ("dc_current_mpp"):
                    $dataArray = $this->currentChart->getCurrentMpp($anlage, $from, $to, $form['selectedInverter']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Current [A]';
                        $resultArray['seriesx']['name'] = "String ";
                        $resultArray['seriesx']['tooltipText'] = "Actuale current [A]";
                    }
                    break;

                // Voltage Charts DC //
                case ("dc_voltage_groups"):
                    $dataArray = $this->voltageChart->getVoltageGroups($anlage, $from, $to, $form['selectedSet']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Group Electricity [V]';
                        $resultArray['seriesx']['name'] = "Group ";
                        $resultArray['seriesx']['tooltipText'] = "Group electricity [V]";
                    }
                    break;
                case ("dc_voltage_mpp"):
                    $dataArray = $this->voltageChart->getVoltageMpp($anlage, $from, $to, $form['selectedInverter']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Voltage [V]';
                        $resultArray['seriesx']['name'] = "String ";
                        $resultArray['seriesx']['tooltipText'] = "Voltage [V]";
                    }
                    break;

                //   //
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
                default:
                    $resultArray['headline'] = 'Something was wrong ' . $form['selectedChart'];
            }
        }

        return $resultArray;
    }

    ###########################################




    /**
     * erzeugt Daten für Inverter Performance Diagramm (DC vs AC Leistung der Inverter)
     * darf nur für Anlagen mit 'configType' 2 angezeigt werden
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