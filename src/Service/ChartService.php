<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Entity\AnlagePVSystDaten;
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
use Exception;
use PDO;
use App\Service\PdoService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;


class ChartService
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly Security $security,
        private readonly AnlagenStatusRepository $statusRepository,
        private readonly AnlageAvailabilityRepository $availabilityRepository,
        private readonly PRRepository $prRepository,
        private readonly PVSystDatenRepository $pvSystRepository,
        private readonly InvertersRepository $invertersRepo,
        private readonly FunctionsService $functions,
        private readonly ForecastChartService $forecastChart,
        private readonly ACPowerChartsService $acCharts,
        private readonly DCPowerChartService $dcChart,
        private readonly DCCurrentChartService $currentChart,
        private readonly VoltageChartService $voltageChart,
        private readonly IrradiationChartService $irradiationChart,
        private readonly GridMeterDayRepository $gridMeterDayRepository,
        private readonly HeatmapChartService $heatmapChartService,
        private readonly TempHeatmapChartService $tempheatmapChartService,
        private readonly SollIstAnalyseChartService $sollistAnalyseChartService,
        private readonly SollIstTempAnalyseChartService $sollisttempAnalyseChartService,
        private readonly SollIstIrrAnalyseChartService $sollistirrAnalyseChartService,
        private readonly SollIstHeatmapChartService $sollistheatmapChartService)
    {
    }

    /**
     * @param $form
     * @param Anlage|null $anlage
     * @param bool|null $hour
     * @return array
     * @throws Exception|InvalidArgumentException
     */
    public function getGraphsAndControl($form, ?Anlage $anlage, ?bool $hour): array
    {

        /*
        $request = Request::createFromGlobals();
        $request->getPathInfo();
        $request = new Request(
            $_GET,
            $_POST,
            array(),
            $_COOKIE,
            $_FILES,
            $_SERVER
        );

        $RURI = $request->getRequestUri();
        */

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

        /*
        $from = self::timeShift($anlage, $form['from'], true);
        $to = self::timeShift($anlage, $form['to'], true);
        */

        $from =  $form['from'];
        $to =  $form['to'];

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
                        $resultArray['headline'] = 'AC production [[kWh]] – actual and expected';
                        $resultArray['seriesx']['tooltipText'] = '[[kWh]]';
                    }
                    break;
                    // AC2 //
                case 'ac_act_overview':
                    $dataArray = $this->acCharts->getAC2($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production by Group [[kWh]] – Actual and Expected';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected ';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Inverter ';
                        $resultArray['seriesx']['tooltipText'] = '[[kWh]]';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                    // AC3 //
                case 'ac_act_group':
                    $dataArray = $this->acCharts->getAC3($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production by Group [[kWh]] – Actual and Expected';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected ';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Inverter ';
                        $resultArray['seriesx']['tooltipText'] = '[[kWh]]';
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
                        $resultArray['headline'] = 'AC Inverter Production [[kWh]]';
                        $resultArray['series1']['name'] = 'Actual Inverter ';
                        $resultArray['series1']['tooltipText'] = '[[kWh]]';
                    }
                    break;
                case 'ac_act_voltage':
                    $dataArray = $this->acCharts->getActVoltageGroupAC($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production Voltage [[V]]';
                        $resultArray['series1']['name'] = 'Voltage Phase 1';
                        $resultArray['series1']['tooltipText'] = 'Voltage Phase 1 [[V]]';
                        $resultArray['series2']['name'] = 'Voltage Phase 2';
                        $resultArray['series2']['tooltipText'] = 'Voltage Phase 2 [[V]]';
                        $resultArray['series3']['name'] = 'Voltage Phase 3';
                        $resultArray['series3']['tooltipText'] = 'Voltage Phase 3 [[V]]';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Actual Inverter ';
                        $resultArray['seriesx']['tooltipText'] = '[[V]]';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case 'ac_act_current':
                    $dataArray = $this->acCharts->getActCurrentGroupAC($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production Current [[A]]';
                        $resultArray['series0']['name'] = 'Current (Sum Phase 1-3)';
                        $resultArray['series0']['tooltipText'] = 'Current (Sum Phase 1-3) [[A]]';
                        $resultArray['series1']['name'] = 'Current Phase 1';
                        $resultArray['series1']['tooltipText'] = 'Current Phase 1 [[A]]';
                        $resultArray['series2']['name'] = 'Current Phase 2';
                        $resultArray['series2']['tooltipText'] = 'Current Phase 2 [[A]]';
                        $resultArray['series3']['name'] = 'Current Phase 3';
                        $resultArray['series3']['tooltipText'] = 'Current Phase 3 [[A]]';
                        $resultArray['seriesx']['tooltipText'] = '[[A]]';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                    }
                    break;
                case 'ac_act_frequency':
                    $dataArray = $this->acCharts->getActFrequncyGroupAC($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Frequency [[Hz]]';
                        $resultArray['series1']['name'] = 'Frequency';
                        $resultArray['series1']['tooltipText'] = 'Frequency ';
                        $resultArray['seriesx']['tooltipText'] = '[[Hz]]';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                    }
                    break;
                case 'reactive_power':
                    $dataArray = $this->acCharts->getReactivePowerGroupAC($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'Reactive power [[kVAr]]';
                        $resultArray['series1']['name'] = 'Reactive power';
                        $resultArray['series1']['tooltipText'] = 'Reactive power ';
                        $resultArray['seriesx']['tooltipText'] = '[[kVAr]]';
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
                        $resultArray['headline'] = 'DC Production [[kWh]] – Actual and Expected';
                        $resultArray['seriesx']['tooltipText'] = '[[kWh]]';
                    }
                    break;
                case 'dc_act_overview':
                    $dataArray = $this->dcChart->getDC2($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Production [[kWh]]';
                        $resultArray['series1']['name'] = 'Expected ';
                        $resultArray['series1']['tooltipText'] = 'Expected [[kWh]]';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Inverter ';
                        $resultArray['seriesx']['tooltipText'] = '[[kWh]]';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case 'dc_act_group': // [DC 3]
                    $dataArray = $this->dcChart->getDC3($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Production by Group [[kWh]]';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected ';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Inverter ';
                        $resultArray['seriesx']['tooltipText'] = '[[kWh]]';
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
                        $resultArray['headline'] = 'DC Inverter Production [[kWh]]';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected [[kWh]]';
                        $resultArray['seriesx']['name'] = 'Actual Inverter ';
                        $resultArray['seriesx']['tooltipText'] = '[[kWh]]';
                    }
                    break;
                case 'dc_inv_power_diff': // ?????????????
                    $dataArray = $this->dcChart->getInverterPowerDifference($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['rangeValue'] = $dataArray['rangeValue'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Inverter Production [[kWh]]';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected [[kWh]]';
                        $resultArray['seriesx']['name'] = 'Actual Inverter ';
                        $resultArray['seriesx']['tooltipText'] = '[[kWh]]';
                    }
                    break;
                    // Current Charts DC //
                    // Übersicht Strom auf Basis der AC Gruppe
                case 'dc_current_overview':
                    $dataArray = $this->currentChart->getCurr1($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['minSeries'] = $dataArray['minSeries'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['sumSeries'] = $dataArray['sumSeries'];
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['headline'] = 'DC Current [[A]] - overview';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected';
                        $resultArray['seriesx']['name'] = 'Group ';
                        $resultArray['seriesx']['tooltipText'] = '[[A]]';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case 'dc_current_group':
                    $dataArray = $this->currentChart->getCurr2($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['label'] = $dataArray['label'];
                        $resultArray['headline'] = 'DC Current [[A]] - all Groups';
                        $resultArray['series1']['name'] = 'Expected Group';
                        $resultArray['series1']['tooltipText'] = 'Expected Group ';
                        $resultArray['seriesx']['name'] = 'Group ';
                        $resultArray['seriesx']['tooltipText'] = '[[A]] ';
                    }
                    break;
                case 'dc_current_inverter':
                    $dataArray = $this->currentChart->getCurr3($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Current [[A]]';
                        $resultArray['series1']['name'] = 'Expected ';
                        $resultArray['series1']['tooltipText'] = 'Expected current [[A]]';
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = 'Inverter ';
                        $resultArray['seriesx']['tooltipText'] = '[[A]]';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case 'dc_current_mpp':
                    $dataArray = $this->currentChart->getCurr4($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Current [[A]]';
                        $resultArray['seriesx']['name'] = 'String ';
                        $resultArray['seriesx']['tooltipText'] = '[[A]]';
                    }
                    break;
                // Voltage Charts DC //
                case 'dc_voltage_1':
                    $dataArray = $this->voltageChart->getVoltage1($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['minSeries'] = $dataArray['minSeries'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['sumSeries'] = $dataArray['sumSeries'];
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['headline'] = 'DC Voltage Overview [[V]]';
                        $resultArray['series1']['name'] = 'Expected';
                        $resultArray['series1']['tooltipText'] = 'Expected';
                        $resultArray['seriesx']['name'] = 'Group ';
                        $resultArray['seriesx']['tooltipText'] = '[[V]]';
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                    // Voltage Charts DC //
                case 'dc_voltage_groups':
                    $dataArray = $this->voltageChart->getVoltageGroups($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'Group Electricity [[V]]';
                        $resultArray['seriesx']['name'] = 'Group ';
                        $resultArray['seriesx']['tooltipText'] = '[[V]]';
                    }
                    break;
                case 'dc_voltage_mpp':
                    $dataArray = $this->voltageChart->getVoltageMpp($anlage, $from, $to, $form['selectedGroup'], $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Voltage [[V]]';
                        $resultArray['seriesx']['name'] = 'String ';
                        $resultArray['seriesx']['tooltipText'] = '[[V]]';
                    }
                    break;
                case 'irradiation':
                    if($anlage->getSettings()->isUseSensorsData()){
                        $dataArray = $this->irradiationChart->getIrradiationFromSensorsData($anlage, $from, $to, 'all', $hour);
                    }else{
                        $dataArray = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
                    }
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Irradiation [[W/m²]]';
                        $resultArray['series1']['name'] = ($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : 'Incident upper table';
                        $resultArray['series1']['tooltipText'] = (($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : 'Incident upper table').' [[W/m²]]';
                        $resultArray['series2']['name'] = ($anlage->getWeatherStation()->getLabelLower() != '') ? $anlage->getWeatherStation()->getLabelLower() : 'Incident lower table';
                        $resultArray['series2']['tooltipText'] = (($anlage->getWeatherStation()->getLabelLower() != '') ? $anlage->getWeatherStation()->getLabelLower() : 'Incident lower table').' [[W/m²]]';
                    }
                    break;
                case 'irradiation_one':
                    $dataArray = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Irradiation [[W/m²]]';
                        $resultArray['series1']['name'] = ($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : 'Incident';
                        $resultArray['series1']['tooltipText'] = (($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : 'Incident').' [[W/m²]]';
                    }
                    break;
                case 'irradiation_plant':
                    if($anlage->getSettings()->isUseSensorsData()){
                        $dataArray = $this->irradiationChart->getIrradiationPlantFromSensorsData($anlage, $from, $to, $hour);
                    }else{
                        $dataArray = $this->irradiationChart->getIrradiationPlant($anlage, $from, $to, $hour);
                    }
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'Irradiation [[W/m²]]';
                        $resultArray['series1']['name'] = 'Irr G4N';
                        $resultArray['series1']['tooltipText'] = 'G4N';
                        $resultArray['seriesx']['name'] = 'Irradiation ';
                        $resultArray['seriesx']['tooltipText'] = '[[W/m²]]';
                        $resultArray['nameX'] = json_encode($dataArray['nameX']);
                    }
                    break;
                case 'temp':
                    if($anlage->getSettings()->isUseSensorsData()) {
                        $dataArray = $this->getAirAndPanelTempFromSensorsData($anlage, $from, $to, $hour);
                    }else{
                        $dataArray = $this->getAirAndPanelTemp($anlage, $from, $to, $hour);
                    }
                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Air and Panel Temperature [[°C]]';
                        $resultArray['series1']['name'] = 'Air temperature [[°C]]';
                        $resultArray['series1']['tooltipText'] = '[[°C]]';
                        $resultArray['series2']['name'] = 'Panel temperature [[°C]]';
                        $resultArray['series2']['tooltipText'] = '[[°C]]';
                        $resultArray['series3']['name'] = 'Panel temperature corrected [[°C]]';
                        $resultArray['series3']['tooltipText'] = ' [[°C]]';
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
                        $resultArray['headline'] = 'Forecast Ertrag';
                        $resultArray['series1']['name'] = '';
                        $resultArray['series1']['tooltipText'] = '';
                    }
                    break;
                case 'forecast_pr':
                    $dataArray = $this->forecastChart->getForecastDayPr($anlage, $to);

                    if ($dataArray) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Forecast PR';
                        $resultArray['series1']['name'] = '';
                        $resultArray['series1']['tooltipText'] = '';
                    }
                    break;
                case 'acpnom':
                    $dataArray = $this->acCharts->getNomPowerGroupAC($anlage, $from, $to, $form['selectedSet']);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'AC Power Inverter normalized';
                    $resultArray['maxSeries'] = $dataArray['maxSeries'];
                    $resultArray['minSeries'] = $dataArray['minSeries'];
                    $resultArray['sumSeries'] = $dataArray['sumSeries'];
                    $resultArray['SeriesNameArray'] = json_encode($dataArray['SeriesNameArray']);
                    break;
                case 'dcpnomcurr':
                    $dataArray = $this->currentChart->getNomCurrentGroupDC($anlage, $from, $to, $form['selectedSet']);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'DC Current Inverter normalized';
                    $resultArray['maxSeries'] = $dataArray['maxSeries'];
                    $resultArray['minSeries'] = $dataArray['minSeries'];
                    $resultArray['sumSeries'] = $dataArray['sumSeries'];
                    $resultArray['SeriesNameArray'] = json_encode($dataArray['SeriesNameArray']);
                    break;
                case 'heatmap':
                    $dataArray = $this->heatmapChartService->getHeatmap($anlage, $from, $to, $form['selectedSet']);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'Inverter PR Heatmap [[%]]';
                    $resultArray['maxSeries'] = $dataArray['maxSeries'];
                    $resultArray['minSeries'] = $dataArray['minSeries'];
                    $resultArray['sumSeries'] = $dataArray['sumSeries'];
                    break;
                case 'tempheatmap':
                    $dataArray = $this->tempheatmapChartService->getTempHeatmap($anlage, $from, $to, $form['selectedSet']);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'Inverter Temperature Heatmap [[°C]]';
                    $resultArray['maxSeries'] = $dataArray['maxSeries'];
                    $resultArray['minSeries'] = $dataArray['minSeries'];
                    $resultArray['sumSeries'] = $dataArray['sumSeries'];
                    break;
                case 'sollistheatmap':
                    $dataArray = $this->sollistheatmapChartService->getSollIstHeatmap($anlage, $from, $to, $form['selectedSet']);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'DC Current Heatmap';
                    $resultArray['maxSeries'] = $dataArray['maxSeries'];
                    $resultArray['minSeries'] = $dataArray['minSeries'];
                    $resultArray['sumSeries'] = $dataArray['sumSeries'];
                    break;
                case 'sollistanalyse':
                    $dataArray = $this->sollistAnalyseChartService->getSollIstDeviationAnalyse($anlage, $from, $to ,$form['selectedGroup']);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'AC differnce between actual and expected power';
                    break;
                case 'sollisttempanalyse':
                    $dataArray = $this->sollisttempAnalyseChartService->getSollIstTempDeviationAnalyse($anlage, $from, $to, $form['selectedGroup']);
                    $resultArray['data'] = json_encode($dataArray['chart']);
                    $resultArray['headline'] = 'Performance Categories vs. Temperatures';
                    break;
                case 'sollistirranalyse':
                    $dataArray = $this->sollistirrAnalyseChartService->getSollIstIrrDeviationAnalyse($anlage, $from, $to, $form['selectedGroup'], $form['optionIrrVal']);
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
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];
        $sql = 'SELECT stamp, sum(wr_pac) AS power_ac, sum(wr_pdc) AS power_dc, unit AS inverter  FROM '.$anlage->getDbNameIst()." WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$group' GROUP by unit";
        $result = $conn->query($sql);
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $inverter = $row['inverter'];
                $powerDc = $row['power_dc'];
                $powerAc = $row['power_ac'];
                $dataArray['chart'][] = [
                    'inverter' => "Inverter #$inverter",
                    'valDc' => $powerDc,
                    'valAc' => $powerAc,
                ];
            }
            $dataArray['maxSeries'] = 0;
            $dataArray['startCounterInverter'] = 10;
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für Temperatur Diagramm.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     *  //
     * @param bool $hour
     * @return array
     * @throws Exception
     */
    public function getAirAndPanelTemp(Anlage $anlage, $from, $to, bool $hour): array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = $this->pdoService->getPdoPlant();
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
     * Erzeugt Daten für Temperatur Diagramm from new Sensor data table.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     *  //
     * @param bool $hour
     * @return array
     * @throws Exception
     */
    public function getAirAndPanelTempFromSensorsData(Anlage $anlage, $from, $to, bool $hour): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $anlageSensors = $anlage->getSensors()->toArray();
        $length = is_countable($anlageSensors) ? count($anlageSensors) : 0;
        $sensorsArray = self::getSensorsData($anlageSensors, $length);
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        $dataArray['chart'] = [];
        if ($hour) {
            //zu from eine Stunde + da sonst Diagrammm nicht erscheint
            $fromPlusOneHour = strtotime($from)+3600;
            $from = date('Y-m-d H:i', $fromPlusOneHour);
            $sql_irr_plant = "SELECT stamp, id_sensor, avg(value) as value, avg(gmo) as gmo FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to'  group by id_sensor, date_format(stamp, '$form') order by stamp, id_sensor;";
            $timeStepp = 3600;
        }else{
            $sql_irr_plant = "SELECT stamp, id_sensor, avg(value) as value, avg(gmo) as gmo FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to'  group by id_sensor, date_format(stamp, '$form') order by stamp, id_sensor;";
            $timeStepp = 900;
        }

        $result = $conn->query($sql_irr_plant);

        if ($result) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                $tempAmbientArray = $tempModuleArray = $windSpeedArray = [];
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    if($counter == 0){
                        $stampTemp = $row['stamp'];
                    }

                    if($stampTemp != $row['stamp']){
                        $dataArray['chart'][] = [
                            'date' =>               $stampTemp,
                            'tempAmbient' =>         $this->mittelwert($tempAmbientArray),
                            'tempCellMeasuerd' =>   $this->mittelwert($tempModuleArray),
                            'tempCellCorr' =>       null,
                            'windSpeed' =>         $this->mittelwert($windSpeedArray)
                        ];
                        unset($tempAmbientArray);
                        unset($tempModuleArray);
                        unset($windSpeedArray);
                        $tempAmbientArray = $tempModuleArray = $windSpeedArray = [];

                    }

                    if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'temp-ambient'){
                        array_push($tempAmbientArray, $row['value']);
                    }
                    if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'temp-modul'){
                        array_push($tempModuleArray, $row['value']);
                    }

                    if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'wind-speed'){
                        array_push($windSpeedArray, $row['value']);
                    }

                    #if($sensorsArray[$row['id_sensor']]['type_sensor'] == 'temp-ambient' || $sensorsArray[$row['id_sensor']]['type_sensor'] == 'temp-modul' || $sensorsArray[$row['id_sensor']]['type_sensor'] == 'wind-speed') {
                        $stampTemp = $row['stamp'];
                        $counter++;
                    #}
                }
            }
        }

        $conn = null;
        $from = substr($stampTemp, 0, -3);

        $fromObj = date_create($from);
        $endObj  = date_create($to);

        //fil up rest of day
        if(is_array($dataArray) && count($dataArray) > 0) {
            for ($dayStamp = $fromObj->getTimestamp(); $dayStamp <= $endObj->getTimestamp(); $dayStamp += $timeStepp) {

                #echo "$dayStamp <br>";
                $date = date('Y-m-d H:i', $dayStamp);
                $dataArray['chart'][count($dataArray['chart'])] = [
                    'date' => $date
                ];
            }
        }

        if(is_array($dataArray) && count($dataArray) == 0){
            $x = [];
            $from = $date = date('Y-m-d 00:00', time());;

            $fromObj = date_create($from);
            $endObj  = date_create($to);

            //fil up rest of day
            $i = 0;
            for ($dayStamp = $fromObj->getTimestamp(); $dayStamp <= $endObj->getTimestamp(); $dayStamp += $timeStepp) {
                $date = date('Y-m-d H:i', $dayStamp);
                $dataArray['chart'][$i] = [
                    'date'              =>  $date,
                    'val1'=>0
                ];
                $i++;
            }

        }
        return $dataArray;
    }

    /**
     * Erzeuge Daten für PR und AV.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     *
     * @return array
     * @throws Exception
     *
     * pr_and_av
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
     * @param Anlage $anlage
     * @param $from
     * @param $to
     *
     * @return array
     */
    public function getpvSyst(Anlage $anlage, $from, $to): array
    {
        $dataArray = [];
        $pvsysts= $this->pvSystRepository->allGreateZero($anlage, $from, $to);
        dump($pvsysts);

        $conn = $this->pdoService->getPdoPlant();
        /** @var AnlagePVSystDaten $pvsyst */
        foreach ($pvsysts as $key => $pvsyst) {
            $stampAdjust = self::timeAjustment($pvsyst->getStamp(), 0.25);
            $stampAdjust2 = self::timeAjustment($stampAdjust, 1.25);
            $sqlEvu = 'SELECT sum(e_z_evu) as eZEvu FROM '.$anlage->getDbNameIst()." WHERE stamp >= '$stampAdjust' AND stamp < '$stampAdjust2' and unit = 1 GROUP by date_format(stamp, '%y%m%d%')";
            $resEvu = $conn->query($sqlEvu);
            $eZEvu = 0;
            if ($resEvu->rowCount() == 1) {
                $rowEvu = $resEvu->fetch(PDO::FETCH_ASSOC);
                if ($rowEvu['eZEvu'] == "") {
                    $eZEvu = null;
                } else {
                    $eZEvu = max($rowEvu['eZEvu'], 0);
                }
            }
            $dataArray[$key]['date'] = $pvsyst->getStamp();
            $dataArray[$key]['evu'] = $eZEvu;
            $dataArray[$key]['electricityGrid'] = round($pvsyst->getElectricityGrid()); // durch 100 um auf kWh zu kommen
            $dataArray[$key]['electricityInverter'] = round($pvsyst->getElectricityInverterOut()); // durch 100 um auf kWh zu kommen

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
