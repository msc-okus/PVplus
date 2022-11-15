<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenStatusRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\InvertersRepository;
use App\Repository\PRRepository;
use App\Repository\PVSystDatenRepository;
use App\Service\Charts\ACPowerChartsService;
use App\Service\Charts\DCCurrentChartService;
use App\Service\Charts\DCPowerChartService;
use App\Service\Charts\ForecastChartService;
use App\Service\Charts\HeatmapChartService;
use App\Service\Charts\IrradiationChartService;
use App\Service\Charts\SollIstAnalyseChartService;
use App\Service\Charts\SollIstHeatmapChartService;
use App\Service\Charts\SollIstTempAnalyseChartService;
use App\Service\Charts\SollIstIrrAnalyseChartService;
use App\Service\Charts\TempHeatmapChartService;
use App\Service\Charts\VoltageChartService;
use DateTime;
use PDO;
use Symfony\Component\Security\Core\Security;

class ChartService
{
    use G4NTrait;

    public function __construct(
        private Security $security,
        private AnlagenStatusRepository $statusRepository,
        private AnlageAvailabilityRepository $availabilityRepository,
        private PRRepository $prRepository,
        private PVSystDatenRepository $pvSystRepository,
        private InvertersRepository $invertersRepo,
        private FunctionsService $functions,
        private ForecastChartService $forecastChart,
        private ACPowerChartsService $acCharts,
        private DCPowerChartService $dcChart,
        private DCCurrentChartService $currentChart,
        private VoltageChartService $voltageChart,
        private IrradiationChartService $irradiationChart,
        private GridMeterDayRepository $gridMeterDayRepository,
        private HeatmapChartService $heatmapChartService,
        private TempHeatmapChartService $tempheatmapChartService,
        private SollIstAnalyseChartService $sollistAnalyseChartService,
        private SollIstTempAnalyseChartService $sollisttempAnalyseChartService,
        private SollIstIrrAnalyseChartService $sollistirrAnalyseChartService,
        private SollIstHeatmapChartService $sollistheatmapChartService)
    {
    }

    /**
     * @param $form
     */
    public function getGraphsAndControl($form, ?Anlage $anlage, ?bool $hour): array
    {
        $resultArray = [];
        $resultArray['data'] = '';
        $resultArray['showEvuDiag'] = 0;
        $resultArray['showCosPhiDiag'] = 0;
        $resultArray['showCosPhiPowerDiag'] = 0;
        $resultArray['actSum'] = 0;
        $resultArray['expSum'] = 0;
        $resultArray['evuSum'] = 0;
        $resultArray['expEvuSum'] = 0;
        $resultArray['expNoLimitSum'] = 0;
        $resultArray['irrSum'] = 0;
        $resultArray['cosPhiSum'] = 0;
        $resultArray['headline'] = '';
        $resultArray['series1']['name'] = '';
        $resultArray['series1']['tooltipText'] = '';
        $resultArray['series2']['name'] = '';
        $resultArray['series2']['tooltipText'] = '';
        $resultArray['seriesx']['name'] = '';
        $resultArray['seriesx']['tooltipText'] = '';
        $resultArray['offsetLegende'] = 0;
        $resultArray['rangeValue'] = 0;
        $resultArray['maxSeries'] = 0;
        $resultArray['hasLink'] = false;
        $currentYear = date('Y');
        $currentMonth = date('m');
        $currentDay = date('d');

        // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
        if (isset($form['backFromMonth'])) {
            if ($form['backFromMonth'] === true) {
                $form['from'] = date('Y-m-d 00:00', strtotime($currentYear.'-'.$currentMonth.'-'.$currentDay) - (86400 * ($form['optionDate'] - 1)));
                $form['to'] = date('Y-m-d 23:59', strtotime($currentYear.'-'.$currentMonth.'-'.$currentDay));
            }
        }
        if ($form['selectedGroup'] == '-1') {
            $form['selectedGroup'] = -1;
        }
        $from = self::timeShift($anlage, $form['from'], true);
        $to = self::timeShift($anlage, $form['to'], true);
        if ($anlage) {
            switch ($form['selectedChart']) {
                // AC Charts //
                // AC1 //

                case 'ac_single':
                    $dataArray = $this->acCharts->getAC1($anlage, $from, $to, $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['showEvuDiag'] = $anlage->getShowEvuDiag();
                        $resultArray['showCosPhiPowerDiag'] = $anlage->getShowCosPhiPowerDiag();
                        $resultArray['actSum'] = $dataArray['actSum'];
                        $resultArray['expSum'] = $dataArray['expSum'];
                        $resultArray['evuSum'] = $dataArray['evuSum'];
                        $resultArray['irrSum'] = $dataArray['irrSum'];
                        $resultArray['expEvuSum'] = $dataArray['expEvuSum'];
                        $resultArray['theoPowerSum'] = $dataArray['theoPowerSum'];
                        $resultArray['expNoLimitSum'] = $dataArray['expNoLimitSum'];
                        $resultArray['cosPhiSum'] = $dataArray['cosPhiSum'];
                        $resultArray['headline'] = 'AC production [kWh] – actual and expected';
                    }
                    break;
                    // AC2 //
                case 'ac_act_overview':
                    $dataArray = $this->acCharts->getAC2($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production by Group [kWh] – Actual and Expected';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected ';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Inverter ';
                        $resultArray['seriesx']['tooltipText'] = 'Inverter ';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                    // AC3 //
                case 'ac_act_group':
                    $dataArray = $this->acCharts->getAC3($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production by Group [kWh] – Actual and Expected';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected ';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Inverter ';
                        $resultArray['seriesx']['tooltipText'] = 'Inverter ';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                // AC4 //
                case 'ac_grp_power_diff': // AC - Inverter
                    $dataArray = $this->acCharts->getGroupPowerDifferenceAC($anlage, $from, $to);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['hasLink'] = false;
                        $resultArray['rangeValue'] = $dataArray['rangeValue'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Inverter Production [kWh]';
                        $resultArray['series1']['name'] = 'Actual Inverter ';
                        $resultArray['series1']['tooltipText'] = 'Actual Inverter [kWh] Group ';
                    }
                    break;
                case 'ac_act_voltage':
                    $dataArray = $this->acCharts->getActVoltageGroupAC($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production Voltage [V]';
                        $resultArray['series1']['name'] = 'Voltage Phase 1';
                        $resultArray['series1']['tooltipText'] = 'Voltage Phase 1';
                        $resultArray['series2']['name'] = 'Voltage Phase 2';
                        $resultArray['series2']['tooltipText'] = 'Voltage Phase 2';
                        $resultArray['series3']['name'] = 'Voltage Phase 3';
                        $resultArray['series3']['tooltipText'] = 'Voltage Phase 3';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Actual Inverter ';
                        $resultArray['seriesx']['tooltipText'] = 'Inverter ';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case 'ac_act_current':
                    $dataArray = $this->acCharts->getActCurrentGroupAC($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production Current [A]';
                        $resultArray['series0']['name'] = 'Current (Sum Phase 1-3)';
                        $resultArray['series0']['tooltipText'] = 'Current (Sum Phase 1-3)';
                        $resultArray['series1']['name'] = 'Current Phase 1';
                        $resultArray['series1']['tooltipText'] = 'Current Phase 1';
                        $resultArray['series2']['name'] = 'Current Phase 2';
                        $resultArray['series2']['tooltipText'] = 'Current Phase 2';
                        $resultArray['series3']['name'] = 'Current Phase 3';
                        $resultArray['series3']['tooltipText'] = 'Current Phase 3';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                    }
                    break;
                case 'ac_act_frequency':
                    $dataArray = $this->acCharts->getActFrequncyGroupAC($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Frequency [Hz]';
                        $resultArray['series1']['name'] = 'Frequency';
                        $resultArray['series1']['tooltipText'] = 'Frequency ';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                    }
                    break;
                case 'reactive_power':
                    $dataArray = $this->acCharts->getReactivePowerGroupAC($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'Reactive power [kVAr]';
                        $resultArray['series1']['name'] = 'Reactive power';
                        $resultArray['series1']['tooltipText'] = 'Reactive power ';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                    }
                    break;

                    // DC Charts //
                case 'dc_single':
                    $dataArray = $this->dcChart->getDC1($anlage, $from, $to, $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['actSum'] = $dataArray['actSum'];
                        $resultArray['expSum'] = $dataArray['expSum'];
                        $resultArray['irrSum'] = $dataArray['irrSum']; // Einstrahlung in kW/m²
                        $resultArray['theoPowerSum'] = 0;
                        $resultArray['headline'] = 'DC Production [kWh] – Actual and Expected';
                    }
                    break;
                case 'dc_act_overview':
                    $dataArray = $this->dcChart->getDC2($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Production [kWh]';
                        $resultArray['series1']['name'] = 'Expected ';
                        $resultArray['series1']['tooltipText'] = 'Expected [[kWh]]';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Inverter ';
                        $resultArray['seriesx']['tooltipText'] = 'Act [kWh]';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case 'dc_act_group': // [DC 3]
                    $dataArray = $this->dcChart->getDC3($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Production by Group [kWh]';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected ';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Inverter ';
                        $resultArray['seriesx']['tooltipText'] = 'Inverter ';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case 'dc_grp_power_diff': // [DC4] DC - Inverter (DC - Inverter Group)
                    $dataArray = $this->dcChart->getGroupPowerDifferenceDC($anlage, $from, $to);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['hasLink'] = true;
                        $resultArray['rangeValue'] = $dataArray['rangeValue'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Inverter Production [kWh]';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected [kWh]';
                        $resultArray['seriesx']['name'] = 'Actual Inverter ';
                        $resultArray['seriesx']['tooltipText'] = 'Actual Inverter [kWh] Group ';
                    }
                    break;
                case 'dc_inv_power_diff': // ?????????????
                    $dataArray = $this->dcChart->getInverterPowerDifference($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['rangeValue'] = $dataArray['rangeValue'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Inverter Production [kWh]';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected [kWh]';
                        $resultArray['seriesx']['name'] = 'Actual Inverter ';
                        $resultArray['seriesx']['tooltipText'] = 'Actual Inverter [kWh] Group ';
                    }
                    break;

                    // Current Charts DC //
                    // Übersicht Strom auf Basis der AC Gruppe
                //
                case 'dc_current_overview':
                    $dataArray = $this->currentChart->getCurr1($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['headline'] = 'DC Current [A] - overview';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected';
                        $resultArray['seriesx']['name'] = '';
                        $resultArray['seriesx']['tooltipText'] = '';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case 'dc_current_group':
                    $dataArray = $this->currentChart->getCurr2($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['label'] = $dataArray['label'];
                        $resultArray['headline'] = 'DC Current [A] - all Groups';
                        $resultArray['series1']['name'] = 'Expected Group';
                        $resultArray['series1']['tooltipText'] = 'Expected Group ';
                        $resultArray['seriesx']['name'] = 'Group ';
                        $resultArray['seriesx']['tooltipText'] = 'Group ';
                    }
                    break;
                case 'dc_current_inverter':
                    $dataArray = $this->currentChart->getCurr3($anlage, $from, $to, $form['selectedGroup'], $hour);

                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Current [A]';
                        $resultArray['series1']['name'] = 'Expected ';
                        $resultArray['series1']['tooltipText'] = 'Expected current [[A]]';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Inverter ';
                        $resultArray['seriesx']['tooltipText'] = 'Act current [A]';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case 'dc_current_mpp':
                    $dataArray = $this->currentChart->getCurr4($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Current [A]';
                        $resultArray['seriesx']['name'] = 'String ';
                        $resultArray['seriesx']['tooltipText'] = 'Actuale current [A]';
                    }
                    break;

                    // Voltage Charts DC //
                case 'dc_voltage_groups':
                    $dataArray = $this->voltageChart->getVoltageGroups($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Group Electricity [V]';
                        $resultArray['seriesx']['name'] = 'Group ';
                        $resultArray['seriesx']['tooltipText'] = 'Group electricity [V]';
                    }
                    break;
                case 'dc_voltage_mpp':
                    $dataArray = $this->voltageChart->getVoltageMpp($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Voltage [V]';
                        $resultArray['seriesx']['name'] = 'String ';
                        $resultArray['seriesx']['tooltipText'] = 'Voltage [V]';
                    }
                    break;

                case 'irradiation':
                    $dataArray = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Irradiation [W/m²]';
                        $resultArray['series1']['name'] = ($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : 'Incident upper table';
                        $resultArray['series1']['tooltipText'] = (($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : 'Incident upper table').' [W/m²]';
                        $resultArray['series2']['name'] = ($anlage->getWeatherStation()->getLabelLower() != '') ? $anlage->getWeatherStation()->getLabelLower() : 'Incident lower table';
                        $resultArray['series2']['tooltipText'] = (($anlage->getWeatherStation()->getLabelLower() != '') ? $anlage->getWeatherStation()->getLabelLower() : 'Incident lower table').' [W/m²]';
                    }
                    break;
                case 'irradiation_one':
                    $dataArray = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Irradiation [W/m²]';
                        $resultArray['series1']['name'] = ($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : 'Incident';
                        $resultArray['series1']['tooltipText'] = (($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : 'Incident').' [W/m²]';
                    }
                    break;
                case 'irradiation_plant':
                    $dataArray = $this->irradiationChart->getIrradiationPlant($anlage, $from, $to, $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'Irradiation w/m²';
                        $resultArray['series1']['name'] = 'Irr G4N';
                        $resultArray['series1']['tooltipText'] = 'G4N';
                        $resultArray['seriesx']['name'] = 'Irradiation ';
                        $resultArray['seriesx']['tooltipText'] = 'Irradiation w/m²';
                        $resultArray['nameX'] = json_encode($dataArray['nameX']);
                    }
                    break;
                case 'temp':
                    $dataArray = $this->getAirAndPanelTemp($anlage, $from, $to, $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Air and Panel Temperature °C';
                        $resultArray['series1']['name'] = 'Air temperature °C';
                        $resultArray['series1']['tooltipText'] = 'Air temperature °C';
                        $resultArray['series2']['name'] = 'Panel temperature °C';
                        $resultArray['series2']['tooltipText'] = 'Panel temperature °C';
                        $resultArray['series3']['name'] = 'Panel temperature corrected °C';
                        $resultArray['series3']['tooltipText'] = 'Panel temperature corrected °C';
                    }
                    break;
                case 'pr_and_av':
                    $dataArray = $this->getPRandAV($anlage, $from, $to);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Performance Ratio and Availability';
                        $resultArray['series1']['name'] = '';
                        $resultArray['series1']['tooltipText'] = '';
                        $resultArray['series2']['name'] = '';
                        $resultArray['series2']['tooltipText'] = '';
                    }
                    break;
                case 'status_log':
                    $resultArray['headline'] = 'Show status Log';
                    $resultArray['status'] = $this->statusRepository->findStatusAnlageDate($anlage, $from, $to);
                    break;
                case 'availability':
                    $dataArray = self::getPlantAvailability($anlage, new DateTime($from), new DateTime($to));
                    $resultArray['headline'] = 'Show availability';
                    $resultArray['availability'] = $dataArray['availability'];
                    break;
                case 'pvsyst':
                    $resultArray['headline'] = 'Show PR & pvSyst';
                    $resultArray['pvSysts'] = $this->getpvSyst($anlage, $from, $to);
                    break;
                case 'grid':
                    $resultArray['headline'] = 'Show Grid';
                    $resultArray['grid'] = $this->getGrid($anlage, $from, $to);
                    break;
                case 'forecast':
                    if ($anlage->getUseDayForecast()) {
                        $dataArray = $this->forecastChart->getForecastDayClassic($anlage, $to);
                    } else {
                        if ($anlage->getUsePac()) {
                            $dataArray = $this->forecastChart->getForecastFac($anlage, $to);
                        } else {
                            $dataArray = $this->forecastChart->getForecastClassic($anlage, $to);
                        }
                    }
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Forecast';
                        $resultArray['series1']['name'] = '';
                        $resultArray['series1']['tooltipText'] = '';
                    }
                    break;
                case 'heatmap':
                    $dataArray = $this->heatmapChartService->getHeatmap($anlage, $from, $to);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'Inverter PR Heatmap [%]';
                    break;
                case 'tempheatmap':
                    $dataArray = $this->tempheatmapChartService->getTempHeatmap($anlage, $from, $to);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'Inverter Temperature Heatmap [C°]';
                    break;
                case 'sollistheatmap':
                    $dataArray = $this->sollistheatmapChartService->getSollIstHeatmap($anlage, $from, $to);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'DC Current Heatmap';
                    break;
                case 'sollistanalyse':
                    $dataArray = $this->sollistAnalyseChartService->getSollIstDeviationAnalyse($anlage, $from, $to);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'AC differnce between actual and expected power';
                    break;
                case 'sollisttempanalyse':
                    $dataArray = $this->sollisttempAnalyseChartService->getSollIstTempDeviationAnalyse($anlage, $from, $to);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'Performance Categories vs. Module Temperatures';
                    break;
                case 'sollistirranalyse':
                    $dataArray = $this->sollistirrAnalyseChartService->getSollIstIrrDeviationAnalyse($anlage, $from, $to, $form['optionIrrVal']);
                    $resultArray['data'] = json_encode($dataArray['0']['chart']);
                    $resultArray['tabel'] = $dataArray['1']['tabel'];
                    $resultArray['headline'] = 'Performance Categories vs. Irradiation';
                    break;
                default:
                    $resultArray['headline'] = 'Something was wrong '.$form['selectedChart'];
            }
        }

        return $resultArray;
    }

    // ##########################################

    private function getPlantAvailability(Anlage $anlage, DateTime $from, DateTime $to): array
    {
        $dataArray = [];
        $dataArray['availability'] = $this->availabilityRepository->findAvailabilityAnlageDate($anlage, $from->format('Y-m-d H:i'), $to->format('Y-m-d H:i'));

        return $dataArray;
    }

    /**
     * erzeugt Daten für Inverter Performance Diagramm (DC vs AC Leistung der Inverter)
     * darf nur für Anlagen mit 'configType' 2 angezeigt werden.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $group
     *
     * @return array
     * @deprecated
     *  // inverter_performance
     */
    public function getInverterPerformance(Anlage $anlage, $from, $to, $group): array
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $sql = 'SELECT stamp, sum(wr_pac) AS power_ac, sum(wr_pdc) AS power_dc, unit AS inverter  FROM '.$anlage->getDbNameIst()." WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$group' GROUP by unit";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $inverter = $row['inverter'];
                $powerDc = self::checkUnitAndConvert($row['power_dc'], $anlage->getAnlDbUnit());
                $powerAc = self::checkUnitAndConvert($row['power_ac'], $anlage->getAnlDbUnit());
                $dataArray['chart'][] = [
                    'inverter' => "Inverter #$inverter",
                    'valDc' => $powerDc,
                    'valAc' => $powerAc,
                ];
            }
            $dataArray['maxSeries'] = 0;
            $dataArray['startCounterInverter'] = 10;
        }
        $conn->close();

        return $dataArray;
    }

    /**
     * Erzeugt Daten für Temperatur Diagramm.
     *
     * @param $from
     * @param $to
     *  //
     */
    public function getAirAndPanelTemp(Anlage $anlage, $from, $to, bool $hour): array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        $counter = 0;
        /*
        if ($hour) $sql2 = "SELECT a.stamp, sum(b.at_avg) as at_avg, sum(b.pt_avg) as pt_avg FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP BY date_format(a.stamp, '$form')";
        else $sql2 = "SELECT a.stamp, b.at_avg as at_avg, b.pt_avg as pt_avg FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP BY date_format(a.stamp, '$form')";
        */
        if ($hour) {
            $sql2 = 'SELECT a.stamp, avg(b.temp_ambient) as tempAmbient, avg(b.temp_pannel) as tempPannel, avg(b.temp_cell_corr) as tempCellCorr, avg(b.wind_speed) as windSpeed FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP BY date_format(a.stamp, '$form')";
        } else {
            $sql2 = 'SELECT a.stamp, sum(b.temp_ambient) as tempAmbient, sum(b.temp_pannel) as tempPannel, b.temp_cell_corr as tempCellCorr, sum(b.wind_speed) as windSpeed FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP BY date_format(a.stamp, '$form')";
        }

        $res = $conn->query($sql2);
        while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
            $tempAmbient = $ro['tempAmbient'];
            $tempPannel = $ro['tempPannel'];
            $tempCellCorr = $ro['tempCellCorr'];
            $windSpeed = $ro['windSpeed'];
            $stamp = $ro['stamp'];  // utc_date($stamp,$anintzzws);

            // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
            $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
            if (!($tempAmbient + $tempPannel == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                $dataArray['chart'][$counter]['tempAmbient'] = $tempAmbient; // Temp. ambient
                $dataArray['chart'][$counter]['tempCellMeasuerd'] = $tempPannel; // Temp. cell measuerd
                $dataArray['chart'][$counter]['tempCellCorr'] = $tempCellCorr; // Temp cell corrected
                $dataArray['chart'][$counter]['windSpeed'] = $windSpeed; // Wind Speed
            }

            ++$counter;
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeuge Daten für PR und AV.
     *
     * @param $from
     * @param $to
     *
     * @return array
     *               // pr_and_av
     */
    public function getPRandAV(Anlage $anlage, $from, $to): array
    {
        $prs = $this->prRepository->findPrAnlageDate($anlage, $from, $to);
        $dataArray = [];
        $counter = 0;
        /** @var AnlagenPR $pr */
        foreach ($prs as $pr) {
            $stamp = $pr->getstamp()->format('Y-m-d');
            // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
            $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
            if ($anlage->getShowEvuDiag()) {
                $dataArray['chart'][$counter]['pr_act'] = $pr->getPrEvu();
                $dataArray['chart'][$counter]['pr_default'] = $pr->getPrDefaultEvu();
            } else {
                $dataArray['chart'][$counter]['pr_act'] = $pr->getPrAct();
                $dataArray['chart'][$counter]['pr_default'] = $pr->getPrDefaultAct();
            }
            $av = $this->availabilityRepository->sumAvailabilityPerDay($anlage->getAnlId(), $stamp);
            $dataArray['chart'][$counter]['av'] = round($av, 3);
            ++$counter;
        }

        return $dataArray;
    }

    /**
     * PV SystWerte als Diagramm ausgeben.
     *
     * @param $from
     * @param $to
     *
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
            ++$counter;
        }

        return $dataArray;
    }

    public function getGrid(Anlage $anlage, $from, $to): array
    {
        $dataArray = [];
        $repo = $this->gridMeterDayRepository;
        $counter = 0;
        $Grids = $this->gridMeterDayRepository->getDateRange($anlage, $from, $to);
        foreach ($Grids as $Grid) {
            $dataArray[$counter]['date'] = $Grid['stamp'];
            $dataArray[$counter]['electricityGrid'] = $Grid['eGrid'];
            ++$counter;
        }

        return $dataArray;
    }
}
