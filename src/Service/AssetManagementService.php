<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\EconomicVarNamesRepository;
use App\Repository\EconomicVarValuesRepository;
use App\Repository\PvSystMonthRepository;
use App\Repository\ForcastDayRepository;
use App\Repository\ReportsRepository;
use App\Repository\TicketDateRepository;
use App\Service\Functions\SensorService;
use App\Service\Reports\ReportsMonthlyV2Service;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Hisune\EchartsPHP\ECharts;
use JetBrains\PhpStorm\ArrayShape;
use PDO;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment;

class AssetManagementService
{
    use G4NTrait;

    private PDO $conn;

    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private $userPlant,
        private $passwordPlant,
        private EntityManagerInterface $em,
        private PvSystMonthRepository $pvSystMonthRepo,
        private FunctionsService $functions,
        private NormalizerInterface $serializer,
        private DownloadAnalyseService $DownloadAnalyseService,
        private EconomicVarValuesRepository $ecoVarValueRepo,
        private PRCalulationService $PRCalulation,
        private EconomicVarNamesRepository $ecoVarNameRepo,
        private AvailabilityByTicketService $availability,
        private TicketDateRepository $ticketDateRepo,
        private ReportsRepository $reportRepo,
        private Environment $twig,
        private PdfService $pdf,
        private LogMessagesService $logMessages,
        private ReportsMonthlyV2Service $reportsMonthly,
        private AnlagenRepository $anlagenRepository,
        private SensorService $sensorService,
        private WeatherFunctionsService $weatherFunctions,
        private ForcastDayRepository $forecastDayRepo,
    )
    {
        $this->conn = self::getPdoConnection($this->host, $this->userPlant, $this->passwordPlant);
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     */
    public function createAmReport(Anlage $anlage, $reportMonth, $reportYear, ?string $userId = null, ?int $logId = null): AnlagenReports
    {

        $report = $this->reportRepo->findOneByAMY($anlage, $reportMonth, $reportYear)[0];
        $comment = '';
        if ($report) {
            $this->em->remove($report);
            $this->em->flush();
        }
        // then we generate our own report and try to persist it
        $output = $this->assetReport($anlage, $reportMonth, $reportYear, $logId);
        $data = [
            'Production' => true,
            'ProdCap' => true,
            'CumulatForecastPVSYS' => true,
            'CumulatForecastG4N' => true,
            'CumulatLosses' => true,
            'MonthlyProd' => true,
            'DailyProd' => true,
            'Availability' => true,
            'AvYearlyOverview' => true,
            'AvMonthlyOverview' => true,
            'AvInv' => true,
            'StringCurr' => true,
            'InvPow' => true,
            'Economics' => true, ];
        $output['data'] = $data;
        $fileroute = $anlage->getEigner()->getFirma()."/".$anlage->getAnlName() . '/AssetReport_' .$reportMonth . '_' . $reportYear ;
        $pdf = $this->pdf;
        $reportParts = [];
        $content = $output;
        $this->logMessages->updateEntry($logId, 'working', 95);
        //rendering the header
        $html = $this->twig->render('report/asset_report_header.html.twig', [
            'comments' => "",
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'pr0image' => $anlage->getPrFormular0Image(),
            'pr1image' => $anlage->getPrFormular1Image(),
            'pr2image' => $anlage->getPrFormular2Image(),
            'pr3image' => $anlage->getPrFormular3Image(),

        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['head'] = $pdf->createPage($html, $fileroute, "head", false);// we will store this later in the entity

        //Production vs Forecast vs Expected
        $html = $this->twig->render('report/asset_report_part_1.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'operations_right' => $content['operations_right'],
            'table_overview_monthly' => $content['table_overview_monthly']

        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['ProductionCapFactor'] = $pdf->createPage($html, $fileroute, "ProductionCapFactor", false);// we will store this later in the entity

        // Technical PR and Availability
        $html = $this->twig->render('report/asset_report_technicalPRPA.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'monthlyTableForPRAndPA' => $content['monthlyTableForPRAndPA'],
            'PA_MonthlyGraphic' => $content['PA_MonthlyGraphic'],
            'PR_MonthlyGraphic' => $content['PR_MonthlyGraphic'],

        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['PRPATable'] = $pdf->createPage($html, $fileroute, "PRPATable", false);// we will store this later in the entity

        //Monthly Production
        $html = $this->twig->render('report/production_with_Forecast.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'operations_right_withForecast' => $content['operations_right_withForecast'],
            'table_overview_monthly' => $content['table_overview_monthly']

        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['production_with_forecast'] = $pdf->createPage($html, $fileroute, "production_with_forecast", false);// we will store this later in the entity

        //Cummulative Forecast
        if($anlage->hasPVSYST()) {
            $html = $this->twig->render('report/asset_report_part_2.html.twig', [
                'anlage' => $anlage,
                'month' => $reportMonth,
                'monthName' => $output['month'],
                'year' => $reportYear,
                'dataCfArray' => $content['dataCfArray'],
                'reportmonth' => $content['reportmonth'],
                'monthArray' => $content['monthArray'],
                //until here all the parameters must be used in all the renders
                'forecast_PVSYST_table' => $content['forecast_PVSYST_table'],
                'table_overview_monthly' => $content['table_overview_monthly'],
                'forecast_PVSYST' => $content['forecast_PVSYST'],
            ]);
            $html = str_replace('src="//', 'src="https://', $html);
            $reportParts['CumForecastPVSYS'] = $pdf->createPage($html, $fileroute, "CumForecastPVSYS", false);// we will store this later in the entity
        }

        //Cummulative Forecast
        $html = $this->twig->render('report/asset_report_part_3.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'forecast_G4N_table' => $content['forecast_G4N_table'],
            'forecast_G4N' => $content['forecast_G4N'],

        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['CumForecastG4N'] = $pdf->createPage($html, $fileroute, "CumForecastG4N", false);// we will store this later in the entity

        //Cummulative Losses
        $html = $this->twig->render('report/asset_report_part_4.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'losses_t1' => $content['losses_t1'],
            'losses_year' => $content['losses_year'],
            'losses_t2' => $content['losses_t2'],
            'losses_monthly' => $content['losses_monthly'],

        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['CumLosses'] = $pdf->createPage($html, $fileroute, "CumLosses", false);// we will store this later in the entity

        //Waterfall diagram
        $html = $this->twig->render('report/waterfallProd.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'waterfallHelpTable' => $content['waterfallHelpTable'],
            'waterfallDiagram' => $content['waterfallDiagram']

        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['waterfallProd'] = $pdf->createPage($html, $fileroute, "waterfallProd", false);// we will store this later in the entity

        //Monthly Production
        $html = $this->twig->render('report/asset_report_part_5.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'operations_monthly_right_g4n_tr1' => $content['operations_monthly_right_g4n_tr1'],
            'operations_monthly_right_g4n_tr2' => $content['operations_monthly_right_g4n_tr2'],
            'operations_monthly_right_g4n_tr3' => $content['operations_monthly_right_g4n_tr3'],
            'operations_monthly_right_g4n_tr4' => $content['operations_monthly_right_g4n_tr4'],
            'operations_monthly_right_g4n_tr5' => $content['operations_monthly_right_g4n_tr5'],
            'operations_monthly_right_g4n_tr6' => $content['operations_monthly_right_g4n_tr6'],
            'operations_monthly_right_g4n_tr7' => $content['operations_monthly_right_g4n_tr7'],
            'operations_monthly_right_pvsyst_tr1' => $content['operations_monthly_right_pvsyst_tr1'],
            'operations_monthly_right_pvsyst_tr2' => $content['operations_monthly_right_pvsyst_tr2'],
            'operations_monthly_right_pvsyst_tr3' => $content['operations_monthly_right_pvsyst_tr3'],
            'operations_monthly_right_pvsyst_tr4' => $content['operations_monthly_right_pvsyst_tr4'],
            'operations_monthly_right_pvsyst_tr5' => $content['operations_monthly_right_pvsyst_tr5'],
            'operations_monthly_right_pvsyst_tr6' => $content['operations_monthly_right_pvsyst_tr6'],
            'operations_monthly_right_pvsyst_tr7' => $content['operations_monthly_right_pvsyst_tr7'],
            'operations_monthly_right_iout_tr1' => $content['operations_monthly_right_iout_tr1'],
            'operations_monthly_right_iout_tr2' => $content['operations_monthly_right_iout_tr2'],
            'operations_monthly_right_iout_tr3' => $content['operations_monthly_right_iout_tr3'],
            'operations_monthly_right_iout_tr4' => $content['operations_monthly_right_iout_tr4'],
            'operations_monthly_right_iout_trlosses_year5' => $content['operations_monthly_right_iout_tr5'],
            'operations_monthly_right_iout_tr6' => $content['operations_monthly_right_iout_tr6'],
            'operations_monthly_right_iout_tr7' => $content['operations_monthly_right_iout_tr7'],
            'production_monthly_chart' => $content['production_monthly_chart']
        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['MonthlyProd'] = $pdf->createPage($html, $fileroute, "MonthlyProd", false);// we will store this later in the entity
        $table = $this->reportsMonthly->buildTable($anlage, null, null, $reportMonth, $reportYear);

        //PR Table
        $html = $this->twig->render('report/asset_report_PRTable.html.twig', [
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            'anlage'        => $anlage,
            'days'        => $table,
        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['PRTable'] = $pdf->createPage($html, $fileroute, "PRTable", false);// we will store this later in the entity

        //Inverter Ranking
        $html = $this->twig->render('report/InverterRank.html.twig', [
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            'anlage'        => $anlage,
            'InverterPRRankTables' => $content['InverterPRRankTables'],
            'InverterPRRankGraphics' => $content['InverterPRRankGraphics'],
            'prSumaryTable' => $content['prSumaryTable'],
            'sumary_pie_graph' => $content['sumary_pie_graph']

        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['InverterRank'] = $pdf->createPage($html, $fileroute, "InverterRank", false);// we will store this later in the entity

        //inverter efficiency rank
        $html = $this->twig->render('report/_inverter_efficiency_rank.html.twig', [
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            'anlage'        => $anlage,
            'efficiencyRanking' => $content['efficiencyRanking'],

        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['InverterEfficiencyRank'] = $pdf->createPage($html, $fileroute, "InverterEfficiencyRank", false);// we will store this later in the entity

        //Expected vs actual
        $html = $this->twig->render('report/asset_report_part_6.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'table_overview_dayly' => $content['table_overview_dayly'],

        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['DailyProd'] = $pdf->createPage($html, $fileroute, "ProdExpvsAct", false);// we will store this later in the entity

        //String currents
        $html = $this->twig->render('report/asset_report_part_7.html.twig', [

            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'invNr' => count($content['plantAvailabilityMonth']),
            'operations_currents_dayly_table' => $content['operations_currents_dayly_table'],
            'acGroups' => $content['acGroups'],
        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['String'] = $pdf->createPage($html, $fileroute, "String", false);// we will store this later in the entity

        //Inverter power difference g4n
        $html = $this->twig->render('report/asset_report_part_8.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'invNr' => count($content['plantAvailabilityMonth']),
            'operations_currents_dayly_table' => $content['operations_currents_dayly_table'],
            'acGroups' => $content['acGroups'],
        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['Inverter'] = $pdf->createPage($html, $fileroute, "Inverter", false);// we will store this later in the entity

        //Availability year
        $html = $this->twig->render('report/asset_report_part_9.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'invNr' => count($content['plantAvailabilityMonth']),
            'operations_currents_dayly_table' => $content['operations_currents_dayly_table'],
            'acGroups' => $content['acGroups'],
            'plantAvailabilityCurrentYear' => $content['plantAvailabilityCurrentYear'],
        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['AvailabilityYearOverview'] = $pdf->createPage($html, $fileroute, "AvailabilityYearOverview", false);// we will store this later in the entity

        //Availability tickets
        $html = $this->twig->render('report/asset_report_part_10.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'invNr' => count($content['plantAvailabilityMonth']),
            'Availability_Year_To_Date_Table' => $content['Availability_Year_To_Date_Table'],
            'availability_Year_To_Date' => $content['availability_Year_To_Date'],
            'failures_Year_To_Date' => $content['failures_Year_To_Date'],
            'ticketCountTable' => $content['ticketCountTable'],
            'TicketAvailabilityYearTable' => $content['TicketAvailabilityYearTable'],
            'kwhLossesChartYear' => $content['kwhLossesChartYear'],
            'yearLossesHelpTable' => $content['yearLossesHelpTable'],
            'PercentageTableYear' => $content['PercentageTableYear'],
            'losseskwhchartYearMonthly' => $content['losseskwhchartYearMonthly']
        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['AvailabilityYear'] = $pdf->createPage($html, $fileroute, "AvailabilityYear", false);// we will store this later in the entity

        //Availability by tickets monthly
        $html =$this->twig->render('report/asset_report_part_11.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'availabilityMonthTable' => $content['availabilityMonthTable'],
            'plant_availability' => $content['plant_availability'],
            'fails_month' => $content['fails_month'],
            'ticketCountTableMonth' => $content['ticketCountTableMonth'],
            'Availability_Year_To_Date_Table' => $content['Availability_Year_To_Date_Table'],
            'TicketAvailabilityMonthTable' => $content['TicketAvailabilityMonthTable'],
            'wkhLossesChartMonth' => $content['wkhLossesChartMonth'],
            'monthlyLossesHelpTable' => $content['monthlyLossesHelpTable'],
            'percentageTableMonth' => $content['percentageTableMonth']
        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['AvailabilityMonth'] = $pdf->createPage($html, $fileroute, "AvailabilityMonth", false);// we will store this later in the entity

        //Availability heatmap
        $html = $this->twig->render('report/asset_report_part_12.html.twig', [
            'anlage' => $anlage,
            'month' => $reportMonth,
            'monthName' => $output['month'],
            'year' => $reportYear,
            'dataCfArray' => $content['dataCfArray'],
            'reportmonth' => $content['reportmonth'],
            'monthArray' => $content['monthArray'],
            //until here all the parameters must be used in all the renders
            'invNr' => count($content['plantAvailabilityMonth']),
            'plantAvailabilityMonth' => $content['plantAvailabilityMonth'],
            'acGroups' => $content['acGroups']
        ]);
        $html = str_replace('src="//', 'src="https://', $html);
        $reportParts['AvailabilityByInverter'] = $pdf->createPage($html, $fileroute, "AvailabilityByInverter", false);// we will store this later in the entity

        if ($anlage->getEconomicVarNames() !== null) {
            //Economics
            $html = $this->twig->render('report/asset_report_part_13.html.twig', [
                'anlage' => $anlage,
                'month' => $reportMonth,
                'monthName' => $output['month'],
                'year' => $reportYear,
                'dataCfArray' => $content['dataCfArray'],
                'reportmonth' => $content['reportmonth'],
                'monthArray' => $content['monthArray'],
                //until here all the parameters must be used in all the renders
                'invNr' => count($content['plantAvailabilityMonth']),
                'plantAvailabilityMonth' => $content['plantAvailabilityMonth'],
                'acGroups' => $content['acGroups'],
                'income_per_month' => $content['income_per_month'],
                'income_per_month_chart' => $content['income_per_month_chart'],
                'economicsMandy' => $content['economicsMandy'],
                'economicsMandy2' => $content['economicsMandy2'],
                'total_Costs_Per_Date' => $content['total_Costs_Per_Date'],
                'operating_statement_chart' => $content['operating_statement_chart'],
                'economicsCumulatedForecast' => $content['economicsCumulatedForecast'],
                'economicsCumulatedForecastChart' => $content['economicsCumulatedForecastChart'],
                'lossesComparedTable' => $content['lossesComparedTable'],
                'losses_compared_chart' => $content['losses_compared_chart'],
                'lossesComparedTableCumulated' => $content['lossesComparedTableCumulated'],
                'cumulated_losses_compared_chart' => $content['cumulated_losses_compared_chart'],

            ]);
            $html = str_replace('src="//', 'src="https://', $html);

            $reportParts['Economic'] = $pdf->createPage($html, $fileroute, "Economic", false);// we will store this later in the entity
        }

        $report = new AnlagenReports();
        $report
            ->setPdfParts($reportParts)
            ->setAnlage($anlage)
            ->setEigner($anlage->getEigner())
            ->setMonth($reportMonth)
            ->setYear($reportYear)
            ->setStartDate(date_create_from_format('d.m.y', date('d.m.y', strtotime('01.'.$reportMonth.'.'.$reportYear))))
            ->setEndDate(date_create_from_format('d.m.y', date('d.m.y', strtotime('30.'.$reportMonth.'.'.$reportYear))))
            ->setReportType('am-report')
            ->setContentArray($output)
            ->setRawReport('')
            ->setComments($comment);
        if ($userId) {
            $report->setCreatedBy($userId);
        }

        $this->em->persist($report);
        $this->em->flush();

        return $report; //$output;
    }

    /**
     * @param $anlage
     * @param $month
     * @param $year
     * @param int|null $logId
     * @return array
     * @throws NoResultException
     */
    public function assetReport($anlage, $month = 0, $year = 0, ?int $logId = null): array
    {
        $date = strtotime("$year-$month-01");
        $reportMonth = date('m', $date);
        $reportYear = date('Y', $date);
        $lastDayMonth = date('t', $date);
        $from = "$reportYear-$reportMonth-01 00:00";
        $to = "$reportYear-$reportMonth-$lastDayMonth 23:59";

        $report['reportMonth'] = $reportMonth;
        $report['from'] = $from;
        $report['to'] = $to;
        $report['reportYear'] = $reportYear;

        return $this->buildAssetReport($anlage, $report, $logId);
    }

    /**
     * @param Anlage $anlage
     * @param array $report
     * @param int|null $logId
     * @return array
     * @throws NoResultException
     */
    public function buildAssetReport(Anlage $anlage, array $report, ?int $logId = null): array
    {
        // Variables

        for ($tempMonth = 1; $tempMonth <= $report['reportMonth']; ++$tempMonth) {

            $startDate = new \DateTime($report['reportYear']."-$tempMonth-01 00:00");
            $daysInThisMonth = $startDate->format("t");
            $endDate = new \DateTime($report['reportYear']."-$tempMonth-$daysInThisMonth 00:00");

            $weather = $this->weatherFunctions->getWeather($anlage->getWeatherStation(), $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s'), true, $anlage);
            if (is_array($weather)) {
                $weather = $this->sensorService->correctSensorsByTicket($anlage, $weather, $startDate, $endDate);
            }


            // Strahlungen berechnen – (upper = Ost / lower = West)
            if ($anlage->getIsOstWestAnlage()) {
                $irradiation[] = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
            } else {
                $irradiation[] = $weather['upperIrr'] / 4 / 1000; // Umrechnung zu kWh
            }
        }

        $daysInReportMonth = cal_days_in_month(CAL_GREGORIAN, $report['reportMonth'], $report['reportYear']);
        $monthArray = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
        ];
        $plantSize = $anlage->getPnom();


        $inverterPRArray = $this->calcPRInvArray($anlage, $report['reportMonth'], $report['reportYear']);

        $invArray = $anlage->getInverterFromAnlage();
        $orderedArray = [];
        $pr_rank_graph = [];
        $index = 0;
        $index2 = 0;
        $sumPr = 0;
        $avgPr = $inverterPRArray['PRAvg'];
        $InverterOverAvgCount = 0;
        $prSumaryTable = [];

        while (count($inverterPRArray['powerYield']) !== 0){
            $keys = array_keys($inverterPRArray['powerYield'], min($inverterPRArray['powerYield']));

            foreach($keys as $key ){
                $orderedArray[$index2][$index]['name'] = $inverterPRArray['name'][$key];
                $orderedArray[$index2][$index]['powerYield'] = $inverterPRArray['powerYield'][$key];
                $orderedArray[$index2][$index]['Pnom'] = $inverterPRArray['Pnom'][$key];
                $orderedArray[$index2][$index]['power'] = $inverterPRArray['power'][$key];
                $orderedArray[$index2][$index]['avgPower'] = $inverterPRArray['powerAVG'];
                $orderedArray[$index2][$index]['avgIrr'] = $inverterPRArray['avgIrr'][$key];
                $orderedArray[$index2][$index]['theoPower'] = $inverterPRArray['theoPower'][$key];
                $orderedArray[$index2][$index]['invPR'] = $inverterPRArray['invPR'][$key];
                $orderedArray[$index2][$index]['calcPR'] = $inverterPRArray['calcPR'][$key];
                $graphDataPR[$index2]['name'][] = $inverterPRArray['name'][$key];
                $graphDataPR[$index2]['PR'][]= $inverterPRArray['invPR'][$key];

                $sumPr = $sumPr + $inverterPRArray['invPR'][$key];
                $graphDataPR[$index2]['power'][]= $inverterPRArray['power'][$key];

                if ($inverterPRArray['invPR'][$key] > $avgPr){
                    $InverterOverAvgCount = $InverterOverAvgCount + 1;
                }
                $sumPr = $sumPr + $inverterPRArray['invPR'][$key];
                $graphDataPR[$index2]['powerYield'][]= $inverterPRArray['powerYield'][$key];

                $graphDataPR[$index2]['yield'] = $inverterPRArray['calcPR'][$key];
                unset($inverterPRArray['powerYield'][$key]);
                $index = $index + 1;
                if ($index >= 30){
                    $index = 0;
                    $index2 = $index2 + 1;
                }
            }
        }

        $avgPr = round($sumPr / count($inverterPRArray['invPR']), 2);

        $invPercentage = $InverterOverAvgCount / count($invArray) * 100;
        $prSumaryTable[1]['InvCount'] = $InverterOverAvgCount;
        $prSumaryTable[1]['percentage'] = $invPercentage;
        $prSumaryTable[2]['InvCount'] = count($invArray) - $InverterOverAvgCount;
        $prSumaryTable[2]['percentage'] = 100 - $invPercentage;
        $chart = new ECharts(); // We must use AMCharts
        $chart->tooltip->show = false;

        $chart->series = [
            [
                'type' => 'pie',
                'data' => [
                    [
                        'value' =>   $prSumaryTable[1]['percentage'],
                        'name' => 'Inverters over average',
                    ],
                    [
                        'value' => $prSumaryTable[2]['percentage'] ,
                        'name' => 'Inverters under the average',
                    ],

                ],
                'visualMap' => 'false',
                'label' => [
                    'show' => true,
                ],
                'itemStyle' => [
                    'borderType' => 'solid',
                    'borderWidth' => 1,
                    'borderColor' => '#ffffff',
                ],
            ],
        ];

        $option = [
            'animation' => false,
            'color' => ['#00FF00', '#FF0000'],
            'title' => [
                'text' => '',
                'left' => 'center',
                'top' => 'top',
                'textStyle' => ['fontSize' => 10],
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'orient' => 'horizontal',
                'left' => 'left',
                'bottom' => 0,
                'padding' => 0, 90, 0, 0,
            ],
        ];

        $chart->setOption($option);

        $sumary_pie_graph = $chart->render('sumary_pie_graph'.$key, ['style' => 'height: 250px; width:500px;']);
        foreach($graphDataPR as $key => $data) {
            $chart = new ECharts(); // We must use AMCharts
            $chart->tooltip->show = false;
            $chart->tooltip->trigger = 'item';
            $chart->xAxis = [
                'type' => 'category',
                'axisLabel' => [
                    'show' => true,
                    'margin' => '10',
                    'rotate' => 45
                ],
                'splitArea' => [
                    'show' => true,
                ],
                'data' => $data['name'],
            ];
            $chart->yAxis = [
                [
                    'type' => 'value',
                    'name' => 'kWh/kWp',
                    'min' => 0,
                    'position' => 'left'
                ],
                [
                    'type' => 'value',
                    'name' => '[%]',
                    'min' => 0,
                    'max' => 105,
                    'position' => 'right',

                ]
            ];
            $chart->series =
                [
                    [
                        'name' => 'Power Yield',
                        'type' => 'bar',
                        'data' => $data['powerYield'],
                        'visualMap' => 'false',

                    ],
                    [
                        'name' => 'Inverter PR',
                        'type' => 'line',
                        'data' => $data['PR'],
                        'visualMap' => 'false',
                        'lineStyle' => [
                            'color' => 'green'
                        ],
                        'yAxisIndex' => 1,
                        'markLine' => [
                            'data' => [
                                [
                                    'name' => 'yield',
                                    'name' => 'Contractual PR',
                                    'yAxis' => $data['yield'],
                                    'lineStyle' => [
                                        'type' => 'solid',
                                        'width' => 3,
                                        'color' => 'red'
                                    ],
                                    'label' => [
                                        'formatter' => '{b}:{c}'
                                    ]
                                ],
                                [
                                    'name' => 'average PR:',

                                    'yAxis' => $avgPr,
                                    'lineStyle' => [
                                        'type' => 'solid',
                                        'width' => 3,
                                        'color' => 'yellow'
                                    ],
                                    'label' => [
                                        'formatter' => '{b}:{c}'
                                    ]
                                ]
                            ],
                            'symbol' => 'none',

                        ]
                    ],
                ];
            $option = [
                'animation' => false,
                'grid' => [
                    'height' => '70%',
                    'top' => 50,
                    'width' => '70%',
                    'right' => 100,
                    'left' => 100,
                    'bottom' => 100,
                ],
                'legend' => [
                    'show' => true,
                    'center' => 'top',
                    'top' => 10,
                ],
                'tooltip' => [
                    'show' => true,
                ],
            ];
            $chart->setOption($option);
            $pr_rank_graph[] = $chart->render('pr_graph_'.$key, ['style' => 'height: 550px; width:900px;']);
        }
        $this->logMessages->updateEntry($logId, 'working', 10);
        $month = $report['reportMonth'];
        for ($i = 0; $i < 12; ++$i) {
            $forecast[$i] = $this->functions->getForcastByMonth($anlage, $i);
        }
        $plantId = $anlage->getAnlId();
        $monthName = date('F', mktime(0, 0, 0, $report['reportMonth'], 10));
        $currentMonth = date('m');

        if ($report['reportMonth'] < 10) {
            $report['reportMonth'] = str_replace(0, '', $report['reportMonth']);
        }


        for ($i = 0; $i < 12; ++$i) {
            $monthExtendedArray[$i]['month'] = $monthArray[$i];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $i + 1, $report['reportYear']);
            $monthExtendedArray[$i]['days'] = $daysInMonth;
            $monthExtendedArray[$i]['hours'] = $daysInMonth * 24;
        }

        $acGroups = $anlage->getAcGroups()->toArray();
        for ($i = 0; $i < count($acGroups); ++$i) {
            $acGroupsCleaned[] = substr($acGroups[$i]->getacGroupName(), strpos($acGroups[$i]->getacGroupName(), 'INV'));
        }

        for ($i = 1; $i <= 12; ++$i) {
            if ($i < 10) {
                $month_transfer = "0$i";
            } else {
                $month_transfer = $i;
            }

            $start = $report['reportYear'].'-'.$month_transfer.'-01 00:00';

            $endDayOfMonth = cal_days_in_month(CAL_GREGORIAN, $month_transfer, $report['reportYear']);
            $end = $report['reportYear'].'-'.$month_transfer.'-'.$endDayOfMonth.' 23:59';
            $data1_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);

            if ($anlage->hasPVSYST()) {
                try {
                    $resultErtrag_design = $this->pvSystMonthRepo->findOneMonth($anlage, $i);
                } catch (NonUniqueResultException $e) {
                }
            } else {
                $resultErtrag_design = 0;
            }
            if ($resultErtrag_design) {
                $Ertrag_design = $resultErtrag_design->getErtragDesign();
            }
            else $Ertrag_design = 0;

            if ($i > $report['reportMonth']) {
                $data1_grid_meter['powerEvu'] = 0;
                $data1_grid_meter['powerAct'] = 0;
                $data1_grid_meter['powerExp'] = 0;
                $data1_grid_meter['powerExpEvu'] = 0;
                $data1_grid_meter['powerEGridExt'] = 0;

            }
            if ($data1_grid_meter['powerEvu'] > 0){
                (float) $powerEvu[] = $data1_grid_meter['powerEvu'];
            }
            else{
                (float) $powerEvu[] = $data1_grid_meter['powerAct'];
            }

            (float) $powerAct[] = $data1_grid_meter['powerAct']; // Inv out
            if ($anlage->getShowEvuDiag()) {
                (float) $powerExpEvu[] = $data1_grid_meter['powerExpEvu'];
            } else {
                (float) $powerExpEvu[] = $data1_grid_meter['powerExp'];
            }
            (float) $powerExp[] = $data1_grid_meter['powerExp'];
            (float) $powerExternal[] = $data1_grid_meter['powerEGridExt'];
            $expectedPvSyst[] = $Ertrag_design;
            if ($anlage->hasPVSYST()){
                $forecast = $expectedPvSyst;
            }
        }
        // fuer die Tabelle
        $tbody_a_production = [
            'powerEvu' => $powerEvu,
            'powerAct' => $powerAct,
            'powerExp' => $powerExp,
            //'expectedPvSyst' => $expectedPvSyst,
            'powerExpEvu' => $powerExpEvu,
            'powerExt' => $powerExternal,
            'forecast' => $forecast,
        ];

        $this->logMessages->updateEntry($logId, 'working', 20);
        for ($i = 0; $i < 12; ++$i) {
            $dataCfArray[$i]['month'] = $monthExtendedArray[$i]['month'];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $i + 1, $report['reportYear']);
            $dataCfArray[$i]['days'] = $daysInMonth;
            $dataCfArray[$i]['hours'] = $daysInMonth * 24;
            $dataCfArray[$i]['cf'] = ($tbody_a_production['powerEvu'][$i] / 1000) / (($plantSize / 1000) * ($daysInMonth * 24)) * 100;
        }
        // chart building, skip to line 950
        // begin chart
        $chart = new ECharts(); // We must use AMCharts
        $chart->tooltip->show = false;
        $chart->tooltip->trigger = 'item';

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => array_slice($monthArray, 0, $report['reportMonth']),
        ];
        $chart->yAxis = [
            'type' => 'value',
            'min' => 0,
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 80,
            'offset' => -20,
        ];
        $series[] =[
            'name' => 'Expected g4n',
            'type' => 'bar',
            'data' => $powerExp,
            'visualMap' => 'false',
        ];
            $series[] = [
                'name' => 'Yield',
                'type' => 'bar',
                'data' => $powerEvu,
                'visualMap' => 'false',
            ];

        $chart->series = $series;

        $option = [
            'textStyle' => [
                'fontFamily' => 'monospace',
                'fontsize' => '16'
            ],
            'animation' => false,
            'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000'],
            'title' => [
                'fontFamily' => 'monospace',
                'text' => 'Year '.$report['reportYear'],
                'left' => 'center',
                'top' => 10
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '80%',
                'left' => 100,
            ],
        ];

        $chart->setOption($option);

        $operations_right = $chart->render('operations_right', ['style' => 'height: 450px; width:700px;']);

        $chart = new ECharts(); // We must use AMCharts
        $chart->tooltip->show = false;
        $chart->tooltip->trigger = 'item';

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => array_slice($monthArray, 0, $report['reportMonth']),
        ];
        $chart->yAxis = [
            'type' => 'value',
            'min' => 0,
            'name' => 'kWh',
            'nameLocation' => 'middle',
            'nameGap' => 80,
            'offset' => -20,
        ];
        $series[] = [   'name' => 'Yield ',
            'type' => 'bar',
            'data' => $powerEvu,
            'visualMap' => 'false',
            ];
        $series[] = [
            'name' => 'Expected g4n',
            'type' => 'bar',
            'data' => $powerExp,
            'visualMap' => 'false',

        ];
            $series[] = [
                'name' => 'Forecast',
                'type' => 'bar',
                'data' => $forecast,
                'visualMap' => 'false',
            ];
        $chart->series = $series;

        $option = [
            'textStyle' => [
                'fontFamily' => 'monospace',
                'fontsize' => '16'
            ],
            'animation' => false,
            'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000'],
            'title' => [
                'fontFamily' => 'monospace',
                'text' => 'Year '.$report['reportYear'],
                'left' => 'center',
                'top' => 10
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '80%',
                'left' => 100,
            ],
        ];

        $chart->setOption($option);

        $operations_right_withForecast = $chart->render('operations_right_withForecast', ['style' => 'height: 450px; width:700px;']);


        $degradation = $anlage->getLossesForecast();
        // Cumulative Forecast
        $powerSum[0] = $powerEvu[0];
        for ($i = 0; $i < 12; ++$i) {
            if ($i + 1 > $report['reportMonth']) {
                $powerSum[$i] = $forecast[$i] + $powerSum[$i - 1];
            } else {
                $powerSum[$i] = $powerEvu[$i] + $powerSum[$i - 1];
            }

            $tbody_forecast_PVSYSTP50[] = $powerSum[$i];
            if ($i > (int)$month - 1) {
                $tbody_forecast_PVSYSTP90[] = $powerSum[$i] - ($powerSum[$i] * $degradation / 100);
            }else{
                $tbody_forecast_PVSYSTP90[] = $powerSum[$i];
            }
        }
        // Forecast / PVSYST - P90
        $PVSYSExpSum[0] = $forecast[0];
        for ($i = 0; $i < 12; ++$i) {

                $PVSYSExpSum[$i] = $forecast[$i] + $PVSYSExpSum[$i - 1];

            $tbody_forecast_plan_PVSYSTP50[] = $PVSYSExpSum[$i];

            $tbody_forecast_plan_PVSYSTP90[] = $PVSYSExpSum[$i] - ($PVSYSExpSum[$i] * $degradation / 100);
        }

        $forecast_PVSYST_table = [
            'forcast_PVSYSTP50' => $tbody_forecast_PVSYSTP50,
            'forcast_PVSYSTP90' => $tbody_forecast_PVSYSTP90,
            'forcast_plan_PVSYSTP50' => $tbody_forecast_plan_PVSYSTP50,
            'forcast_plan_PVSYSTP90' => $tbody_forecast_plan_PVSYSTP90,
        ];

        // begin chart
        $chart = new ECharts();
        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '0',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $monthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'min' => 0,
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 80,
        ];
        $chart->series =
            [
                [
                    'name' => 'Production ACT / Forecast - P50',
                    'type' => 'line',
                    'data' => $tbody_forecast_PVSYSTP50,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Production ACT / Forecast - P90',
                    'type' => 'line',
                    'data' => $tbody_forecast_PVSYSTP90,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Forecast - P50',
                    'type' => 'line',
                    'data' => $tbody_forecast_plan_PVSYSTP50,
                    'visualMap' => 'false',
                    'lineStyle' => [
                        'type' => 'dashed',
                    ],
                ],
                [
                    'name' => 'Forecast - P90',
                    'type' => 'line',
                    'data' => $tbody_forecast_plan_PVSYSTP90,
                    'visualMap' => 'false',
                    'lineStyle' => [
                        'type' => 'dashed',
                    ],
                ],
            ];

        $option = [
            'animation' => false,
            'color' => ['#c55a11', '#0070c0', '#70ad47', '#ff0000'],
            'title' => [
                'text' => '',
                'left' => 'center',
                'top' => 10,
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '85%',
                'left' => 100,
            ],
        ];

        $chart->setOption($option);

        $forecast_PVSYST = $chart->render('forecast_PVSYST', ['style' => 'height: 450px; width:28cm;']);

        // End Cumulative Forecast with PVSYST

        $PowerSum[0] = 0;
        for ($i = 0; $i < 12; ++$i) {
            if ($i + 1 <= $report['reportMonth']) {

                if ($powerExp[$i] > 0) {
                    $PowerSum[$i] = $powerExp[$i] + $PowerSum[$i - 1];
                } else {
                    $PowerSum[$i] = $forecast[$i] + $PowerSum[$i - 1];
                }
            } else {
                $PowerSum[$i] = $forecast[$i] + $PowerSum[$i - 1];
            }
            $tbody_forcast_G4NP50[] = $PowerSum[$i];
            if ($i > (int)$month - 1) $tbody_forcast_G4NP90[] = $PowerSum[$i] - ($PowerSum[$i] * $degradation / 100);
            else $tbody_forcast_G4NP90[] = $PowerSum[$i];
        }

        // Forecast / G4N
        $forecastSum[0] =  $forecast[0] ;
        $tbody_forcast_plan_G4NP50[0] = $forecastSum[0];
        $tbody_forcast_plan_G4NP90[0] = $forecastSum[0] - ($forecastSum[0] * $degradation / 100);
        for ($i = 1; $i < 12; ++$i) {
            $forecastSum[$i] = $forecast[$i] + $forecastSum[$i-1];
            $tbody_forcast_plan_G4NP50[] = $forecastSum[$i];
            $tbody_forcast_plan_G4NP90[] = $forecastSum[$i] - ($forecastSum[$i] * $degradation / 100);
        }

        $forecast_G4N_table = [
            'forcast_G4NP50' => $tbody_forcast_G4NP50,
            'forcast_G4NP90' => $tbody_forcast_G4NP90,
            'forcast_plan_G4NP50' => $tbody_forcast_plan_G4NP50,
            'forcast_plan_G4NP90' => $tbody_forcast_plan_G4NP90,
        ];

        $this->logMessages->updateEntry($logId, 'working', 30);
        $chart = new ECharts();
        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '0',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $monthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'min' => 0,
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 80,
        ];
        $chart->series =
            [
                [
                    'name' => 'Production ACT / g4n',
                    'type' => 'line',
                    'data' => $tbody_forcast_G4NP50,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Production ACT /  g4n - P90',
                    'type' => 'line',
                    'data' => $tbody_forcast_G4NP90,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Forecast - P50',
                    'type' => 'line',
                    'data' => $tbody_forcast_plan_G4NP50,
                    'visualMap' => 'false',
                    'lineStyle' => [
                        'type' => 'dashed',
                    ],
                ],
                [
                    'name' => 'Forecast - P90',
                    'type' => 'line',
                    'data' => $tbody_forcast_plan_G4NP90,
                    'visualMap' => 'false',
                    'lineStyle' => [
                        'type' => 'dashed',
                    ],
                ],
            ];

        $option = [
            'animation' => false,
            'color' => ['#c55a11', '#0070c0', '#70ad47', '#ff0000'],
            'title' => [
                'text' => '',
                'left' => 'center',
                'top' => 10,
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '85%',
                'left' => 100,
            ],
        ];

        $chart->setOption($option);

        $forecast_G4N = $chart->render('forecast_G4N', ['style' => 'height: 450px; width:28cm;']);


        for ($i = 0; $i < 12; ++$i) {
            if ($i < count($tbody_a_production['powerEvu'])) {
                if ($i + 1 > $report['reportMonth']) {
                    $diefference_prod_to_pvsyst[] = 0;
                } else {
                    if ($anlage->getShowEvuDiag()) {
                        if ($anlage->getUseGridMeterDayData()) {
                            $diefference_prod_to_pvsyst[] = $tbody_a_production['powerExt'][$i] - $tbody_a_production['forecast'][$i];
                        } else {
                            $diefference_prod_to_pvsyst[] = $tbody_a_production['powerEvu'][$i] - $tbody_a_production['forecast'][$i];
                        }
                    } else {
                        $diefference_prod_to_pvsyst[] = $tbody_a_production['powerAct'][$i] - $tbody_a_production['forecast'][$i];
                    }
                }
            } else {
                $diefference_prod_to_pvsyst[] = 0;
            }
        }

        for ($i = 0; $i < 12; ++$i) {
            if ($i < count($tbody_a_production['powerEvu'])) {
                if ($anlage->getShowEvuDiag()) {
                    if ($anlage->getUseGridMeterDayData()) {
                        $diefference_prod_to_expected_g4n[] = $tbody_a_production['powerExt'][$i] - $tbody_a_production['powerExpEvu'][$i];
                    } else {
                        $diefference_prod_to_expected_g4n[] = $tbody_a_production['powerEvu'][$i] - $tbody_a_production['powerExpEvu'][$i];
                    }
                } else {
                    $diefference_prod_to_expected_g4n[] = $tbody_a_production['powerAct'][$i] - $tbody_a_production['powerExpEvu'][$i];
                }
            } else {
                $diefference_prod_to_expected_g4n[] = 0;
            }
        }

        for ($i = 0; $i < 12; ++$i) {
            if ($i < count($tbody_a_production['powerEvu'])) {
                if ($anlage->getShowEvuDiag()) {
                    if ($anlage->getUseGridMeterDayData()) {
                        $diefference_prod_to_egrid[$i] = $tbody_a_production['powerExt'][$i] - $tbody_a_production['powerAct'][$i];
                    } else {
                        $diefference_prod_to_egrid[$i] = $tbody_a_production['powerEvu'][$i] - $tbody_a_production['powerAct'][$i];
                    }
                } else {
                    $diefference_prod_to_egrid[$i] = 0;
                }
            } else {
                $diefference_prod_to_egrid[$i] = 0;
            }
        }
        for ($i = 0; $i < 12; ++$i) {
            if ($i < $report['reportMonth'] ) {
                if ($anlage->getShowEvuDiag()) {
                    if ($anlage->getUseGridMeterDayData()) {
                        $difference_prod_to_forecast[$i] = $tbody_a_production['powerExt'][$i] - $forecast[$i];
                    } else {
                        $difference_prod_to_forecast[$i] = $tbody_a_production['powerEvu'][$i] - $forecast[$i];
                    }
                } else {
                    $difference_prod_to_forecast[$i] = $tbody_a_production['powerAct'][$i] - $forecast[$i];
                }
            } else {
                $difference_prod_to_forecast[$i] = 0;
            }
        }
        $losses_t2 = [
            'diefference_prod_to_pvsyst' => $diefference_prod_to_pvsyst,
            'diefference_prod_to_expected_g4n' => $diefference_prod_to_expected_g4n,
            'diefference_prod_to_egrid' => $diefference_prod_to_egrid,
            'difference_prod_to_forecast' => $difference_prod_to_forecast
        ];

        $diffProdPVSYSSum[0] = $diefference_prod_to_pvsyst[0];
        $diffProdG4NSum[0] = $diefference_prod_to_expected_g4n[0];
        $diffProdEgridSum[0] = $diefference_prod_to_egrid[0];
        $diffActForecastSum[0] =  $tbody_a_production['powerAct'][0] - $forecast[0];
        $difference_Egrid_to_PVSYST[0] = $diffProdPVSYSSum[0];
        $difference_Egrid_to_Expected_G4n[0] = $diffProdG4NSum[0];
        $difference_Inverter_to_Egrid[0] = $diffProdEgridSum[0];
        $difference_actual_forecast[0] = $diffActForecastSum[0];
        for ($i = 1; $i < 12; ++$i) {

            $diffProdPVSYSSum[$i] = $diefference_prod_to_pvsyst[$i] + $diffProdPVSYSSum[$i - 1];
            $diffProdG4NSum[$i] = $diefference_prod_to_expected_g4n[$i] + $diffProdG4NSum[$i - 1];
            $diffProdEgridSum[$i] = $diefference_prod_to_egrid[$i ] + $diffProdEgridSum[$i - 1];
            $diffActForecastSum[$i] = $diffActForecastSum[$i - 1]+($tbody_a_production['powerAct'][$i] - $forecast[$i]);


            if ($i +1  > $report['reportMonth']) {
                $difference_Egrid_to_PVSYST[$i] = 0;
                $difference_Egrid_to_Expected_G4n[$i] = 0;
                $difference_Inverter_to_Egrid[$i] = 0;
                $difference_actual_forecast[$i] = 0;
            } else {
                $difference_Egrid_to_PVSYST[$i] = $diffProdPVSYSSum[$i];
                $difference_Egrid_to_Expected_G4n[$i] = $diffProdG4NSum[$i];
                $difference_Inverter_to_Egrid[$i] = $diffProdEgridSum[$i];
                $difference_actual_forecast[$i] = $diffActForecastSum[$i];
            }
        }

        $losses_t1 = [
            'difference_Egrid_to_PVSYST' => $difference_Egrid_to_PVSYST,
            'difference_Egrid_to_Expected_G4n' => $difference_Egrid_to_Expected_G4n,
            'difference_Inverter_to_Egrid' => $difference_Inverter_to_Egrid,
            'difference_act_forecast' => $difference_actual_forecast,
        ];
        $chart = new ECharts();
        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $monthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => 'KWH',
        ];

                $chart->series =
                    [
                        [
                            'name' => 'Difference ACT to expected g4n',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_Expected_G4n,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference ACT to forecast',
                            'type' => 'line',
                            'data' => $difference_actual_forecast,
                            'visualMap' => 'false',
                        ]
                    ];

        $option = [
            'animation' => false,
            'grid' => [
                'height' => '70%',
                'top' => 50,
                'bottom' => 0,
                'width' => '85%',
            ],
        ];
        $chart->setOption($option);

        $losses_year = $chart->render('losses_yearly', ['style' => 'height: 450px; width: 27cm']);

        $this->logMessages->updateEntry($logId, 'working', 40);

        $chart = new ECharts();

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'data' => [],
            'scale' => true,
            'min' => 0,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => 'kWh',
            'nameLocation' => 'middle',
            'nameGap' => 80,
            'scale' => true,
            'min' => 0,
            'offset' => -20
        ];

        $series = [];
        $series = [
            [
                'name' => 'Yield ',
                'type' => 'bar',
                'data' => [
                    $powerAct[$report['reportMonth'] - 1],
                ],
                'visualMap' => 'false',
            ],
            [
                'name' => 'Expected g4n',
                'type' => 'bar',
                'data' => [
                    $powerExp[$report['reportMonth'] - 1],
                ],
                'visualMap' => 'false',
            ],
            [
                'name' => 'Forecast',
                'type' => 'bar',
                'data' => [
                    $forecast[$report['reportMonth'] - 1],
                ],
                'visualMap' => 'false',
            ]
        ];

        $chart->series = $series;


        $option = [
            'yaxis' => ['scale' => false, 'min' => 0],
            'animation' => false,
            'color' => ['#698ed0', '#f1975a', '#c5e0b4', '#ffc000'],
            'title' => [
                'text' => $monthName.' '.$report['reportYear'],
                'left' => 'center',
                'top' => 10,
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '100%',
                'top' => 80,
                'left' => 90,
                'width' => '80%',
            ],
        ];

        $chart->setOption($option);

        $production_monthly_chart = $chart->render('production_monthly_chart', ['style' => 'height: 310px; width:100%;']);


        if ($powerEvu[$report['reportMonth'] - 1] < 1){
            $var = 0;
        }
        else {
            $var = round((1 - $forecast[$report['reportMonth'] - 1] / $powerEvu[$report['reportMonth'] - 1]) * 100, 2);
        }

            $operations_monthly_right_pvsyst_tr1 = [
                $monthName . ' ' . $report['reportYear'],
                $powerEvu[$report['reportMonth'] - 1],
                $forecast[$report['reportMonth'] - 1],
                $powerEvu[$report['reportMonth'] - 1] - $forecast[$report['reportMonth'] - 1],
                $var,
            ];


        $start = $report['reportYear'].'-01-01 00:00';

        $end = $report['reportMonth'] >= '3' ? $report['reportYear'].'-03-31 23:59' : $report['to'];
        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        if ($anlage->getShowEvuDiag()) {
            $powerEvuQ1 = $data2_grid_meter['powerEvu'];
        } else {
            $powerEvuQ1 = $data2_grid_meter['powerAct'];
        }
        if ($powerEvuQ1 > 0 ) {
            $expectedPvSystQ1 = 0;


                if ($month >= 3) {
                    $expectedPvSystQ1 = $forecast[0] + $forecast[1] + $forecast[2];
                } else {
                    for ($i = 0; $i <= intval($report['reportMonth']); ++$i) {
                        $expectedPvSystQ1 += $forecast[$i];
                    }
                }
            $operations_monthly_right_pvsyst_tr2 = [
                $powerEvuQ1,
                $expectedPvSystQ1,
                abs($powerEvuQ1 - $expectedPvSystQ1),
                round((1 - $expectedPvSystQ1 / $powerEvuQ1) * 100, 2),
            ];
        }else{
            $operations_monthly_right_pvsyst_tr1 = [
                $powerEvuQ1,
                0.0,
                0.0,
                0.0,
            ];
        }

        $start = $report['reportYear'].'-04-01 00:00';
        $end = $report['reportMonth'] >= '6' ? $report['reportYear'].'-06-30 23:59' : $report['to'];

        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        if ($anlage->getShowEvuDiag()) {
            $powerEvuQ2 = $data2_grid_meter['powerEvu'];
        } else {
            $powerEvuQ2 = $data2_grid_meter['powerAct'];
        }

        if (( $powerEvuQ2 > 0)) {

                if ($month >= 6) {
                    $expectedPvSystQ2 = $forecast[3] + $forecast[3][4] + $forecast[3][5];
                } else {
                    for ($i = 3; $i <= intval($report['reportMonth']); ++$i) {
                        $expectedPvSystQ2 += $forecast[3][$i];
                    }
                }


            $operations_monthly_right_pvsyst_tr3 = [
                $powerEvuQ2,
                $expectedPvSystQ2,
                $powerEvuQ2 - $expectedPvSystQ2,
                round((1 - $expectedPvSystQ2 / $powerEvuQ2) * 100, 2),
            ];
        }else{
            $operations_monthly_right_pvsyst_tr3 = [
                $powerEvuQ2,
                0.0,
                0.0,
                0.0,
            ];
        }
        // Parameter fuer die Berechnung Q3
        $start = $report['reportYear'].'-07-01 00:00';
        $end = $report['reportMonth'] >= '9' ? $report['reportYear'].'-09-31 23:59' : $report['to'];

        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        if ($anlage->getShowEvuDiag()) {
            $powerEvuQ3 = $data2_grid_meter['powerEvu'];
        } else {
            $powerEvuQ3 = $data2_grid_meter['powerAct'];
        }
        if (( $powerEvuQ3 > 0) ) {

                if ($month >= 9) {
                    $expectedPvSystQ3 = $forecast[6] + $forecast[7] + $forecast[8];
                } else {
                    for ($i = 6; $i <= intval($report['reportMonth']); ++$i) {
                        $expectedPvSystQ3 += $forecast[$i];
                    }
                }


            $operations_monthly_right_pvsyst_tr4 = [
                $powerEvuQ3,
                $expectedPvSystQ3,
                $powerEvuQ3 - $expectedPvSystQ3,
                round((1 - $expectedPvSystQ3 / $powerEvuQ3) * 100, 2),
            ];
        }else{
            $operations_monthly_right_pvsyst_tr4 = [
                $powerEvuQ3,
                0,
                0,
                0,
            ];
        }

        // Parameter fuer die Berechnung Q4
        $start = $report['reportYear'].'-10-01 00:00';
        $end = $report['reportMonth'] == '12' ? $report['reportYear'].'-12-31 23:59' : $report['to'];

        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        if ($anlage->getShowEvuDiag()) {
            $powerEvuQ4 = $data2_grid_meter['powerEvu'];
        } else {
            $powerEvuQ4 = $data2_grid_meter['powerAct'];
        }
        if (($powerEvuQ4 > 0) ) {

                for ($i = 9; $i <= intval($report['reportMonth']); ++$i) {
                    $expectedPvSystQ4 += $forecast[$i];
                }


            $operations_monthly_right_pvsyst_tr5 = [
                $powerEvuQ4,
                $expectedPvSystQ4,
                $powerEvuQ4 - $expectedPvSystQ4,
                round((1 - $expectedPvSystQ4 / $powerEvuQ4) * 100, 2),
            ];
        }else{
            $operations_monthly_right_pvsyst_tr5 = [
            $powerEvuQ4,
            0.0,
            0.0,
            0.0,
        ];}
        // Year to date
        $pacDate =  $anlage->getPacDate();
        if ($anlage->getUsePac() && $pacDate != null){
            $monthPacDate = $pacDate->format('m');
            $yearPacDate = $pacDate->format('Y');
        }
        else{
            $monthPacDate = $anlage->getAnlBetrieb()->format('m');
            $yearPacDate = $anlage->getAnlBetrieb()->format('y');
        }

        $start = $report['reportYear'].'-01-01 00:00';
        $end = $report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth.' 23:59';
        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        $powerEvuYtoD = $data2_grid_meter['powerEvu'];

        if (($powerEvuYtoD > 0 && !($yearPacDate == $report['reportYear'] && $monthPacDate > $report['reportMonth'])) ) {
            // Part 1 Year to Date
            if ($yearPacDate == $report['reportYear']) {
                $month = $monthPacDate;
            } else {
                $month = '1';
            }

            $expectedPvSystYtoDFirst = 0;
                for ($i = 9; $i <= intval($report['reportMonth']); ++$i) {
                    $expectedPvSystYtoDFirst += $forecast[$i];
                }

            $operations_monthly_right_pvsyst_tr6 = [
                $powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4,
                $expectedPvSystYtoDFirst,
                $powerEvuYtoD - $expectedPvSystYtoDFirst,
                (1 - $expectedPvSystYtoDFirst / $powerEvuYtoD) * 100,
            ];
        }

        // Gesamte Laufzeit

        $operations_monthly_right_pvsyst_tr7 = [
            0.00,
            0.00,
            0.00,
            0.00,
        ];


        if ($powerEvu[$report['reportMonth'] - 1] > 0) {
            $var = (1 - $powerExpEvu[$report['reportMonth'] - 1] / $powerEvu[$report['reportMonth'] - 1]) * 100;
        }
        else $var = 0;
        $operations_monthly_right_g4n_tr1 = [
            $monthName.' '.$report['reportYear'],
            $powerEvu[$report['reportMonth'] - 1],
            $powerExpEvu[$report['reportMonth'] - 1],
            $powerEvu[$report['reportMonth'] - 1] - $powerExpEvu[$report['reportMonth'] - 1],
            $var,
        ];

        // Parameter fuer die Berechnung Q1
        if ( $powerEvuQ1 > 0) {
            $temp_q1 = $tbody_a_production['powerExpEvu'][0] + $tbody_a_production['powerExpEvu'][1] + $tbody_a_production['powerExpEvu'][2];
            $operations_monthly_right_g4n_tr2 = [
                $powerEvuQ1,
                $temp_q1,
                $powerEvuQ1 - $temp_q1,
                (($powerEvuQ1 - $temp_q1) * 100) / $powerEvuQ1,
            ];
        } else {
            $operations_monthly_right_g4n_tr2 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }

        // Parameter fuer die Berechnung Q2
        if ( $powerEvuQ2 > 0) {
            $temp_q2 = $tbody_a_production['powerExpEvu'][3] + $tbody_a_production['powerExpEvu'][4] + $tbody_a_production['powerExpEvu'][5];
            $operations_monthly_right_g4n_tr3 = [
                $powerEvuQ2,
                $temp_q2,
                $powerEvuQ2 - $temp_q2,
                (($powerEvuQ2 - $temp_q2) * 100) / $powerEvuQ2,
            ];
        } else {
            $operations_monthly_right_g4n_tr3 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }

        // Parameter fuer die Berechnung Q3
        if ( $powerEvuQ3 > 0) {
            $temp_q3 = $tbody_a_production['powerExpEvu'][6] + $tbody_a_production['powerExpEvu'][7] + $tbody_a_production['powerExpEvu'][8];
            $operations_monthly_right_g4n_tr4 = [
                $powerEvuQ3,
                $temp_q3,
                $powerEvuQ3 - $temp_q3,
                (($powerEvuQ3 - $temp_q3) * 100) / $powerEvuQ3,
            ];
        } else {
            $operations_monthly_right_g4n_tr4 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }

        // Parameter fuer die Berechnung Q4
        if ($powerEvuQ4 > 0) {
            $temp_q4 = $tbody_a_production['powerExpEvu'][9] + $tbody_a_production['powerExpEvu'][10] + $tbody_a_production['powerExpEvu'][11];
            $operations_monthly_right_g4n_tr5 = [
                $powerEvuQ4,
                $temp_q4,
                $powerEvuQ4 - $temp_q4,
                (($powerEvuQ4 - $temp_q4) * 100) / $powerEvuQ4,
            ];
        } else {
            $operations_monthly_right_g4n_tr5 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }

        // Parameter fuer Year to Date
        if (!($yearPacDate == $report['reportYear'] && $monthPacDate > $currentMonth)) {
            $x = $powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4;
            $y = ($powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4) - ($temp_q1 + $temp_q2 + $temp_q3 + $temp_q4);
            if ($x == 0) $difference = 100;
            else $difference = ($y * 100) / $x;
            $operations_monthly_right_g4n_tr6 = [
                $powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4,
                $temp_q1 + $temp_q2 + $temp_q3 + $temp_q4,
                ($powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4) - ($temp_q1 + $temp_q2 + $temp_q3 + $temp_q4),
                $difference,
            ];
        } else {
            $operations_monthly_right_g4n_tr6 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }

        // Parameter fuer total Runtime
        // El total runtime son los datos de toda la planta desde que abrio
        if (!($yearPacDate == $report['reportYear'] && $monthPacDate > $currentMonth)) {
            $operations_monthly_right_g4n_tr7 = [
                0.00,
                0.00,
                0.00,
                0.00,
            ];
        } else {
            $operations_monthly_right_g4n_tr7 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }
        if ($powerEvu[$report['reportMonth'] - 1] > 0) {
            $var = (1 - $powerExpEvu[$report['reportMonth'] - 1] / $powerEvu[$report['reportMonth'] - 1]) * 100;
        }
        else $var = 0;
        // Tabelle rechts unten
        $operations_monthly_right_iout_tr1 = [
            $monthName.' '.$report['reportYear'],
            $powerEvu[$report['reportMonth'] - 1],
            $powerAct[$report['reportMonth'] - 1],
            $powerEvu[$report['reportMonth'] - 1] - $powerAct[$report['reportMonth'] - 1],
            $var,
        ];

        // Parameter fuer die Berechnung Q1
        if ($powerEvuQ1 > 0) {
            $temp_q1 = $tbody_a_production['powerAct'][0] + $tbody_a_production['powerAct'][1] + $tbody_a_production['powerAct'][2];
            $operations_monthly_right_iout_tr2 = [
                $powerEvuQ1,
                $temp_q1,
                $powerEvuQ1 - $temp_q1,
                (($powerEvuQ1 - $temp_q1) * 100) / $powerEvuQ1,
            ];
        } else {
            $operations_monthly_right_iout_tr2 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }

        // Parameter fuer die Berechnung Q2
        if ($powerEvuQ2 > 0) {
            $temp_q2 = $tbody_a_production['powerAct'][3] + $tbody_a_production['powerAct'][4] + $tbody_a_production['powerAct'][5];
            $operations_monthly_right_iout_tr3 = [
                $powerEvuQ2,
                $temp_q2,
                $powerEvuQ2 - $temp_q2,
                (($powerEvuQ2 - $temp_q2) * 100) / $powerEvuQ2,
            ];
        } else {
            $operations_monthly_right_iout_tr3 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }

        // Parameter fuer die Berechnung Q3
        if ($powerEvuQ3 > 0) {
            $temp_q3 = $tbody_a_production['powerAct'][6] + $tbody_a_production['powerAct'][7] + $tbody_a_production['powerAct'][8];
            $operations_monthly_right_iout_tr4 = [
                $powerEvuQ3,
                $temp_q3,
                $powerEvuQ3 - $temp_q3,
                (($powerEvuQ3 - $temp_q3) * 100) / $powerEvuQ3,
            ];
        } else {
            $operations_monthly_right_iout_tr4 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }

        // Parameter fuer die Berechnung Q4
        if ($powerEvuQ4 > 0) {
            $temp_q4 = $tbody_a_production['powerAct'][9] + $tbody_a_production['powerAct'][10] + $tbody_a_production['powerAct'][11];
            $operations_monthly_right_iout_tr5 = [
                $powerEvuQ4,
                $temp_q4,
                $powerEvuQ4 - $temp_q4,
                (($powerEvuQ4 - $temp_q4) * 100) / $powerEvuQ4,
            ];
        } else {
            $operations_monthly_right_iout_tr5 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }
        $this->logMessages->updateEntry($logId, 'working', 50);
        // Parameter fuer Year to Date
        if (!($yearPacDate == $report['reportYear'] && $monthPacDate > $currentMonth)) {
            $x = $powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4;
            $y = ($powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4) - ($temp_q1 + $temp_q2 + $temp_q3 + $temp_q4);
            if ($x == 0) $difference = 100;
            else $difference = ($y * 100) / $x;
            $operations_monthly_right_iout_tr6 = [
                $powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4,
                $temp_q1 + $temp_q2 + $temp_q3 + $temp_q4,
                ($powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4) - ($temp_q1 + $temp_q2 + $temp_q3 + $temp_q4),
                $difference,
            ];
        } else {
            $operations_monthly_right_iout_tr6 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }

        // Parameter for total Runtime
        if (!($yearPacDate == $report['reportYear'] && $monthPacDate > $currentMonth)) {
            $operations_monthly_right_iout_tr7 = [
                0.00,
                0.00,
                0.00,
                0.00,
            ];
        } else {
            $operations_monthly_right_iout_tr7 = [
                '0',
                '0',
                '0',
                '0',
            ];
        }
        // End Operations month
        // End Monthley expected vs.actuals

        // Beginn Operations dayly
        // The Table
        $start = $report['reportYear'].'-'.$report['reportMonth'].'-01 00:00';
        $end = $report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth.' 23:59';

        //$output = $this->DownloadAnalyseService->getAllSingleSystemData($anlage, "2023",$report['reportMonth'] , 2);
        $dcData = $this->DownloadAnalyseService->getDcSingleSystemData($anlage, $start, $end, '%d.%m.%Y');
        $dcDataExpected = $this->DownloadAnalyseService->getEcpectedDcSingleSystemData($anlage, $start, $end, '%d.%m.%Y');

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $report['reportMonth'], $report['reportYear']);


        for ($i = 0; $i < $daysInMonth ; ++$i) {
            $year = $report['reportYear'];
            $month = $report['reportMonth'];
            $days = $i + 1;
            $day = new \DateTime("$year-$month-$days");
            $output2 = $this->PRCalulation->calcPR($anlage, $day);
            $table_overview_dayly[] =
                [
                    'date' => $day->format('M-d'),
                    'irradiation' => (float) $output2['irradiation'],
                    'powerEGridExtMonth' => (float) $output2['powerEGridExt'],
                    'PowerEvuMonth' => (float) $output2['powerEvu'],
                    'powerActMonth' => (float) $output2['powerAct'],
                    'powerDctMonth' => (float) $dcData[$i]['actdc'],
                    'powerExpMonth' => (float) $output2['powerExp'],
                    'powerExpDctMonth' => (float) $dcDataExpected[$i]['expdc'],
                    'prEGridExtMonth' => (float) $output2['prEGridExt'],
                    'prEvuMonth' => (float) $output2['prEvu'],
                    'prActMonth' => (float) $output2['prAct'],
                    'prExpMonth' => (float) $output2['prExp'],
                    'plantAvailability' => (float) $output2['availability'],
                    'plantAvailabilitySecond' => (float) $output2['availability2'],
                    'panneltemp' => 0,
                ];
        }

        if ($anlage->getConfigType() == 1) {
            // Type 1 is the only one where acGrops are NOT the Inverter
            $inverters = $anlage->getGroups()->count();
        } else {
            // use acGroups as Inverter
            $inverters = $anlage->getAcGroups()->count();
        }
        for ($inverter = 1; $inverter <= $inverters; ++$inverter) {
            $pa = [];
            for ($tempMonth = 1; $tempMonth <= $report['reportMonth']; ++$tempMonth) {
                $startDate = new \DateTime($report['reportYear']."-$tempMonth-01 00:00");
                $daysInThisMonth = $startDate->format("t");
                $endDate = new \DateTime($report['reportYear']."-$tempMonth-$daysInThisMonth 00:00");
                $pa[] = [
                    'form_date' => $tempMonth,
                    'pa' => $this->availability->calcAvailability($anlage, $startDate, $endDate, $inverter, 0),
                    'unit' => $inverter,
                ];
            }
            $outPaCY[] = $pa;
            unset($pa);
        }


        // we have to generate the overall values of errors for the year
        $daysInThisMonth = cal_days_in_month(CAL_GREGORIAN, $report['reportMonth'], $report['reportYear']);
        $endate = $report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInThisMonth." 23:59:00";
        $SOFErrors  = 0;
        $EFORErrors = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 20, $anlage)[0][1] + (int) $this->ticketDateRepo->countByIntervalNullPlant($report['reportYear'].'-01-01', $endate, $anlage, "10")[0][1];
        $OMCErrors  = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 30, $anlage)[0][1];
        $dataGaps   = (int) $this->ticketDateRepo->countByIntervalNullPlant($report['reportYear'].'-01-01', $endate, $anlage, "20")[0][1];
        $totalErrors = $SOFErrors + $EFORErrors + $OMCErrors + $dataGaps;
        // here we calculate the ammount of quarters to calculate the relative percentages
        $begin = $report['reportYear'].'-01-'.'01 00:00:00';
        $lastDayOfMonth = date('t', strtotime($begin));
        $end = $report['reportYear'].'-'.$report['reportMonth'].'-'.$lastDayOfMonth.' 23:55:00';
        $sqlw = 'SELECT count(db_id) as quarters
                    FROM  '.$anlage->getDbNameWeather()."  
                    WHERE stamp BETWEEN '$begin' AND '$end' AND (g_lower + g_upper)/2 > '".$anlage->getThreshold2PA()."'";// hay que cambiar aqui para que la radiacion sea mayor que un valor
        $resw = $this->conn->query($sqlw);
        $sumquarters = $resw->fetch(PDO::FETCH_ASSOC)['quarters'] * $anlage->getAnzInverter();

        $sumLossesYearSOR = 0;
        $sumLossesYearEFOR = 0;
        $sumLossesYearOMC = 0;
        $sumLossesYearGap = 0;
        for($i = $report['reportMonth'] - 1; $i >= 0 ; $i--){
            $invertedMonthArray[] = $monthArray[$i];
            $kwhLosses[$i] = $this->calculateLosses($report['reportYear']."-".($i + 1)."-01",$report['reportYear']."-".($i + 1)."-".cal_days_in_month(CAL_GREGORIAN, $i + 1, $report['reportYear']),$anlage);

            if ($anlage->getTotalKpi() < 100)$tempExp = $tbody_a_production['powerExp'][$i] * ((100-$anlage->getTotalKpi())/100);

            if ($tempExp > 0) {
                $table_percentage_monthly['Actual'][] = (int)($tbody_a_production['powerAct'][$i] * 100 / $tempExp);
                $table_percentage_monthly['ExpectedG4N'][] = 100;
                $table_percentage_monthly['Forecast'][] = (int)($tbody_a_production['forecast'][$i] * 100 / $tempExp);
                $table_percentage_monthly['SORLosses'][] = number_format(-($kwhLosses[$i]['SORLosses'] * 100 / $tempExp), 2);
                $table_percentage_monthly['EFORLosses'][] = number_format(-($kwhLosses[$i]['EFORLosses'] * 100 / $tempExp), 2);
                $table_percentage_monthly['OMCLosses'][] = number_format(-($kwhLosses[$i]['OMCLosses'] * 100 / $tempExp), 2);
                $table_percentage_monthly['DataGap'][] = number_format(-($kwhLosses[$i]['DataGapLosses'] * 100 / $tempExp), 2);
            }
            else {
                $table_percentage_monthly['Actual'][] = 0;
                $table_percentage_monthly['ExpectedG4N'][] = 0;
                $table_percentage_monthly['Forecast'][] = 0;
                $table_percentage_monthly['SORLosses'][] = 0;
                $table_percentage_monthly['EFORLosses'][] = 0;
                $table_percentage_monthly['OMCLosses'][] = 0;
                $table_percentage_monthly['DataGap'][] = 0;
            }
        }

        foreach($kwhLosses as $data){
            $sumLossesYearSOR = $sumLossesYearSOR + $data['SORLosses'];
            $sumLossesYearEFOR = $sumLossesYearEFOR + $data['EFORLosses'];
            $sumLossesYearOMC = $sumLossesYearOMC + $data['OMCLosses'];
            $sumLossesYearGap = $sumLossesYearGap + $data['DataGapLosses'];
        }

        if ($sumquarters > 0) {
            $actualAvailabilityPorcent = (($sumquarters - $totalErrors) / $sumquarters) * 100;
            $actualSOFPorcent = 100 - (($sumquarters - $SOFErrors) / $sumquarters) * 100;
            $actualEFORPorcent = 100 - (($sumquarters - $EFORErrors) / $sumquarters) * 100;
            $actualOMCPorcent = 100 - (($sumquarters - $OMCErrors) / $sumquarters) * 100;
            $actualGapPorcent = 100 - (($sumquarters - $dataGaps) / $sumquarters) * 100;
        }
        else{
            $actualAvailabilityPorcent = 0;
            $actualSOFPorcent = 0;
            $actualEFORPorcent = 0;
            $actualOMCPorcent = 0;
            $actualGapPorcent = 0;
        }


        $kwhLossesYearTable = [
            'SORLosses'     => $sumLossesYearSOR ,
            'EFORLosses'    => $sumLossesYearEFOR,
            'OMCLosses'     => $sumLossesYearOMC,
            'GapLosses'     => $sumLossesYearGap
        ];
        $availabilityYearToDateTable = [
            'expectedAvailability' => (int) $anlage->getContractualAvailability(),
            'expectedSOF' => 0, // this will be a variable in the future
            'expectedEFOR' => 0, // and this
            'expectedOMC' => 0, // and this
            'expectedGaps' => 0,
            'actualAvailability' => $actualAvailabilityPorcent,
            'actualSOF' => $actualSOFPorcent,
            'actualEFOR' => $actualEFORPorcent,
            'actualOMC' => $actualOMCPorcent,
            'actualGaps' => $actualGapPorcent,
        ];

        $ticketCountTable = [
            'SOFTickets'   => 0,
            'EFORTickets'  => (int) $this->ticketDateRepo->countTicketsByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 20, $anlage)[0][1] + (int) $this->ticketDateRepo->countGapsByIntervalPlantEv($report['reportYear'].'-01-01', $endate, "10", $anlage)[0][1],
            'OMCTickets'   => (int) $this->ticketDateRepo->countTicketsByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 30, $anlage)[0][1],
            'GapTickets'   => (int) $this->ticketDateRepo->countGapsByIntervalPlantEv($report['reportYear'].'-01-01', $endate, "20", $anlage)[0][1],
            'SOFQuarters'  => $SOFErrors,
            'EFORQuarters' => $EFORErrors,
            'OMCQuarters'  => $OMCErrors,
            'GapQuarters'  => $dataGaps,
        ];

        $availability_Year_To_Date = [];

        $failures_Year_To_Date = [];



        //$SOFErrorsMonth = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 10, $anlage)[0][1];
        $SOFErrorsMonth = 0;
        $EFORErrorsMonth = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 20, $anlage)[0][1] + (int) $this->ticketDateRepo->countByIntervalNullPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, $anlage, "10")[0][1];
        $OMCErrorsMonth = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 30, $anlage)[0][1];
        $dataGapsMonth = (int) $this->ticketDateRepo->countByIntervalNullPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, $anlage, "20")[0][1];
        $totalErrorsMonth = $SOFErrorsMonth + $EFORErrorsMonth + $OMCErrorsMonth + $dataGapsMonth;
        $EFORErrorsMonth = $EFORErrorsMonth + (int) $this->ticketDateRepo->countByIntervalNullPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, $anlage, "10")[0][1];
        $begin = $report['reportYear'].'-'.$report['reportMonth'].'-'.'01 00:00:00';
        $lastDayOfMonth = date('t', strtotime($begin));
        $end = $report['reportYear'].'-'.$report['reportMonth'].'-'.$lastDayOfMonth.' 23:55:00';

        $sqlw = 'SELECT count(db_id) as quarters
                    FROM  '.$anlage->getDbNameWeather()."  
                    WHERE stamp BETWEEN '$begin' AND '$end' 
                    AND g_lower + g_upper > 0";

        $resw = $this->conn->query($sqlw);

        $sumLossesMonthSOR = $kwhLosses[$report['reportMonth'] - 1]['SORLosses'];
        $sumLossesMonthEFOR = $kwhLosses[$report['reportMonth'] - 1]['EFORLosses'];
        $sumLossesMonthOMC = $kwhLosses[$report['reportMonth'] - 1]['OMCLosses'];
        $sumLossesMonthGap = $kwhLosses[$report['reportMonth'] - 1]['DataGapLosses'];
        $quartersInMonth = $resw->fetch(PDO::FETCH_ASSOC)['quarters'] * $anlage->getAnzInverter();
        if ($quartersInMonth > 0) {
            $actualAvailabilityPorcentMonth = (($quartersInMonth - $totalErrorsMonth) / $quartersInMonth) * 100;
            $actualSOFPorcentMonth = 100 - (($quartersInMonth - $SOFErrorsMonth) / $quartersInMonth) * 100;
            $actualEFORPorcentMonth = 100 - (($quartersInMonth - $EFORErrorsMonth) / $quartersInMonth) * 100;
            $actualOMCPorcentMonth = 100 - (($quartersInMonth - $OMCErrorsMonth) / $quartersInMonth) * 100;
            $actualGapPorcentMonth = 100 - (($quartersInMonth - $dataGapsMonth) / $quartersInMonth) * 100;
        }
        else{
            $actualAvailabilityPorcentMonth = 0;
            $actualSOFPorcentMonth = 0;
            $actualEFORPorcentMonth = 0;
            $actualOMCPorcentMonth = 0;
            $actualGapPorcentMonth = 0;
        }


        $kwhLossesMonthTable = [
            'SORLosses'     => $sumLossesMonthSOR,
            'EFORLosses'    => $sumLossesMonthEFOR,
            'OMCLosses'     => $sumLossesMonthOMC,
            'GapLosses'     => $sumLossesMonthGap
        ];

        $availabilityMonthTable = [
            'expectedAvailability' => (float) $anlage->getContractualAvailability(),
            'expectedSOF' => 0, // this will be a variable in the future
            'expectedEFOR' => 0, // and this
            'expectedOMC' => 0, // and this
            'expectedGaps' => 0,
            'actualAvailability' => $actualAvailabilityPorcentMonth,
            'actualSOF' => $actualSOFPorcentMonth,
            'actualEFOR' => $actualEFORPorcentMonth,
            'actualOMC' => $actualOMCPorcentMonth,
            'actualGaps' => $actualGapPorcentMonth,
        ];
        $ticketCountTableMonth = [
            'SOFTickets'    => 0,
            'EFORTickets'   => (int) $this->ticketDateRepo->countTicketsByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 20, $anlage)[0][1] + $this->ticketDateRepo->countGapsByIntervalPlantEv($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, "10", $anlage)[0][1],
            'OMCTickets'    => (int) $this->ticketDateRepo->countTicketsByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 30, $anlage)[0][1],
            'GapTickets'    => (int) $this->ticketDateRepo->countGapsByIntervalPlantEv($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate,  "20", $anlage)[0][1],
            'SOFQuarters'   => $SOFErrorsMonth,
            'EFORQuarters'  => $EFORErrorsMonth,
            'OMCQuarters'   => $OMCErrorsMonth,
            'DataGaps'      => $dataGapsMonth,
        ];
        if ($totalErrorsMonth != 0) {
            $failRelativeSOFPorcentMonth = 100 - (($totalErrorsMonth - $SOFErrorsMonth) / $totalErrorsMonth) * 100;
            $failRelativeEFORPorcentMonth = 100 - (($totalErrorsMonth - $EFORErrorsMonth) / $totalErrorsMonth) * 100;
            $failRelativeOMCPorcentMonth = 100 - (($totalErrorsMonth - $OMCErrorsMonth) / $totalErrorsMonth) * 100;
            $failRelativeGapsPorcentMonth = 100 - (($totalErrorsMonth - $dataGapsMonth) / $totalErrorsMonth) * 100;
        } else {
            $failRelativeSOFPorcentMonth = 0;
            $failRelativeEFORPorcentMonth = 0;
            $failRelativeOMCPorcentMonth = 0;
            $failRelativeGapsPorcentMonth = 0;
        }

        $plant_availability = [];


        //Tables for the kwh losses with bar graphs

        if ($anlage->hasPVSYST()){
            $PVSYSTyearExpected = 1;
            for($index = 0; $index < $month -1; $index++){
                $PVSYSTyearExpected = $PVSYSTyearExpected + $tbody_a_production['forecast'][$index];
            }
        }

        $G4NmonthExpected = $tbody_a_production['powerExp'][$month-2] * ((100 - $anlage->getTotalKpi())/100);
        $G4NyearExpected = 1;
        for($index = 0; $index < $month -1; $index++){
            $G4NyearExpected = $G4NyearExpected + ($tbody_a_production['powerExp'][$index] * ((100-$anlage->getTotalKpi())/100));
        }

        $ActualPower = $powerEvu[$month-2];
        $ActualPowerYear = 1;
        for($index = 0; $index < $month -1; $index++){
            $ActualPowerYear = $ActualPowerYear + $powerEvu[$index];
        }
        if ($G4NmonthExpected > 0) {
            $percentageTable = [
                'G4NExpected' => 100,
                'PVSYSExpected' => (int)($tbody_a_production['forecast'][$month - 2] * 100 / $G4NmonthExpected),
                'forecast' => (int)($forecast[$month - 2] * 100 / $G4NmonthExpected),
                'ActualPower' => (int)($ActualPower * 100 / $G4NmonthExpected),
                'SORLosses' => number_format(-($kwhLossesMonthTable['SORLosses'] * 100 / $G4NmonthExpected), 2),
                'EFORLosses' => number_format(-($kwhLossesMonthTable['EFORLosses'] * 100 / $G4NmonthExpected), 2),
                'OMCLosses' => number_format(-($kwhLossesMonthTable['OMCLosses'] * 100 / $G4NmonthExpected), 2),
                'GapLosses' => number_format(-($kwhLossesMonthTable['GapLosses'] * 100 / $G4NmonthExpected), 2)
            ];
        }else{
            $percentageTable = [
            'G4NExpected' => 0,
                'PVSYSExpected' => 0,
                'forecast' => 0,
                'ActualPower' => 0,
                'SORLosses' => 0,
                'EFORLosses' => 0,
                'OMCLosses' => 0,
                'GapLosses' => 0
                ];
        }


        $this->logMessages->updateEntry($logId, 'working', 60);
        $chart = new ECharts();
        $chart->yAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' =>[" "],
        ];
        $chart->xAxis = [
            'type' => 'value',
            'name' => '%',
            'nameLocation' => 'middle',
            'nameGap' => 80,
        ];


            $chart->series =[
                [
                    'name' => 'Expected G4N[%]',
                    'type' => 'bar',
                    'data' => [$percentageTable['G4NExpected']] ,
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                    'name' => 'Actual[%]',
                    'type' => 'bar',
                    'data' => [$percentageTable['ActualPower']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                    'name' => 'Forecast[%]',
                    'type' => 'bar',
                    'data' => [$percentageTable['forecast']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                    'name' => 'SOR Losses[%] - Planned Outage',
                    'type' => 'bar',
                    'data' => [$percentageTable['SORLosses']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],

                ],
                [
                    'name' => 'EFOR Losses[%] - Unplanned Outage',
                    'type' => 'bar',
                    'data' => [$percentageTable['EFORLosses']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                    'name' => 'OMC Losses[%] - Grid Error/Grid Off',
                    'type' => 'bar',
                    'data' => [$percentageTable['OMCLosses']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                'name' => 'Gap Losses[%] - Data Gaps',
                'type' => 'bar',
                'data' => [$percentageTable['GapLosses']],
                'visualMap' => 'false',
                'label' => [
                    'show' => true,
                    'position' => 'inside'
                ],

                ],
            ];


        $option = [
            'color' => ['#f1975a', '#698ed0', '#b7b7b7', '#ffc000', '#ea7ccc', '#9a60b4', '#b7b7a4'],
            'animation' => false,
            'title' => [
                'text' => 'Production and Losses in Percentage for the month',
                'left' => 'center',
                'top' => '10',
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'right' => 'right',
                'top' => 50,
                //'padding' => -10 ,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '70%',
                'left' => 50,

            ],
        ];

        $chart->setOption($option);

        $losseskwhchart = $chart->render('Month_losses', ['style' => 'height: 350px; width:28cm;']);


        $monthlyLossesHelpTable = [
            'ExpectedG4N' => $G4NmonthExpected,
            'ExpectedPVSYS' => $forecast[$report['reportMonth'] - 1],
            'Forecast' => $forecast[$month-2],
            'Actual' => $ActualPower,
            'SORLosses' => $kwhLossesMonthTable['SORLosses'],
            'EFORLosses' => $kwhLossesMonthTable['EFORLosses'],
            'OMCLosses' => $kwhLossesMonthTable['OMCLosses'],
            'GapLosses' =>  $kwhLossesMonthTable['GapLosses'],
        ];

        $percentageTableYear = [
            'G4NExpected' =>  100 ,
            'PVSYSExpected' => (int)($PVSYSTyearExpected * 100 / $G4NyearExpected),
            'forecast' =>  (int)($forecastSum[$month-2] * 100 / $G4NyearExpected),
            'ActualPower' => (int)($ActualPowerYear * 100 / $G4NyearExpected),
            'SORLosses' => number_format(-($kwhLossesYearTable['SORLosses']  * 100 / $G4NyearExpected), 2, '.', ','),
            'EFORLosses' => number_format(-($kwhLossesYearTable['EFORLosses']  * 100 / $G4NyearExpected), 2, '.', ','),
            'OMCLosses' => number_format(-($kwhLossesYearTable['OMCLosses']  * 100 / $G4NyearExpected), 2, '.', ','),
            'GapLosses' => number_format(-($kwhLossesYearTable['GapLosses']  * 100 / $G4NyearExpected), 2, '.', ','),
        ];

        $chart = new ECharts();
        $chart->yAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '0',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' =>[" "],
        ];
        $chart->xAxis = [
            'type' => 'value',
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 80,
            'offset' => -20,
        ];

            $chart->series =[
                [
                    'name' => 'Expected G4N[%]',
                    'type' => 'bar',
                    'data' => [$percentageTableYear['G4NExpected']] ,
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                    'name' => 'Actual[%]',
                    'type' => 'bar',
                    'data' => [$percentageTableYear['ActualPower']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                    'name' => 'Forecast[%]',
                    'type' => 'bar',
                    'data' => [$percentageTableYear['forecast']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                    'name' => 'SOR Losses[%] - Planned Outage',
                    'type' => 'bar',
                    'data' => [$percentageTableYear['SORLosses']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                    'name' => 'EFOR Losses[%] - Unplanned Outage',
                    'type' => 'bar',
                    'data' => [$percentageTableYear['EFORLosses']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                    'name' => 'OMC Losses[%] - Grid Error/Grid Off',
                    'type' => 'bar',
                    'data' => [$percentageTableYear['OMCLosses']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ],
                [
                    'name' => 'Data Gaps[%]',
                    'type' => 'bar',
                    'data' => [$percentageTableYear['GapLosses']],
                    'visualMap' => 'false',
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                ]
            ];



        $option = [
            'color' => ['#f1975a', '#698ed0', '#b7b7b7',  '#ffc000', '#ea7ccc', '#9a60b4', '#b7b7a4'],
            'animation' => false,
            'title' => [
                'text' => 'Production and Losses cummulative',
                'left' => 'center',
                'top' => '10',
            ],
            'tooltip' => [
                'show' => true,
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'right' => 'right',
                'top' => 50,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '70%',
                'left' => 60,
            ],
        ];

        $chart->setOption($option);
        $losseskwhchartyear = $chart->render('Year_losses', ['style' => 'height: 350px; width:28cm; ']);

        $yearLossesHelpTable = [
            'ExpectedG4N' => $G4NyearExpected,
            'ExpectedPVSYS' => $PVSYSTyearExpected,
            'Forecast' => $forecastSum[$month-2],
            'Actual' => $ActualPowerYear,
            'SORLosses' => $kwhLossesYearTable['SORLosses'],
            'EFORLosses' => $kwhLossesYearTable['EFORLosses'],
            'OMCLosses' => $kwhLossesYearTable['OMCLosses'],
            'GapLosses' => $kwhLossesYearTable['GapLosses']
        ];


        $this->logMessages->updateEntry($logId, 'working', 70);
        $chart = new ECharts();
        $chart->yAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
                'right' => '10'
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $invertedMonthArray,

        ];

        $chart->xAxis = [
            'type' => 'value',
            'name' => '%',
            'nameLocation' => 'middle',
            'nameGap' => 80,
        ];

            $chart->series =
                [

                    [
                        'name' => 'Expected g4n[%]',
                        'type' => 'bar',
                        'data' => $table_percentage_monthly['ExpectedG4N'],
                        'visualMap' => 'false',
                        'label' => [
                            'show' => true,
                            'position' => 'inside'
                        ],
                    ],
                    [
                        'name' => 'Actual[%]',
                        'type' => 'bar',
                        'data' => $table_percentage_monthly['Actual'],
                        'visualMap' => 'false',
                        'label' => [
                            'show' => true,
                            'position' => 'inside'
                        ],
                    ],
                    [
                        'name' => 'Forecast[%]',
                        'type' => 'bar',
                        'data' => $table_percentage_monthly['Forecast'],
                        'visualMap' => 'false',
                        'label' => [
                            'show' => true,
                            'position' => 'inside'
                        ],
                    ],
                    [
                        'name' => 'SOR Losses[%] - Planned outage',
                        'type' => 'bar',
                        'data' => $table_percentage_monthly['SORLosses'],
                        'visualMap' => 'false',
                        'label' => [
                            'show' => true,
                            'position' => 'inside'
                        ],
                    ],
                    [
                        'name' => 'EFOR Losses[%] - Unplanned Outage',
                        'type' => 'bar',
                        'data' => $table_percentage_monthly['EFORLosses'],
                        'visualMap' => 'false',
                        'label' => [
                            'show' => true,
                            'position' => 'inside'
                        ],
                    ],
                    [
                        'name' => 'OMC Losses[%] - Grid Error/Grid Off',
                        'type' => 'bar',
                        'data' => $table_percentage_monthly['OMCLosses'],
                        'visualMap' => 'false',
                        'label' => [
                            'show' => true,
                            'position' => 'inside'
                        ],
                    ],
                    [
                        'name' => 'Data Gaps',
                        'type' => 'bar',
                        'data' => $table_percentage_monthly['OMCLosses'],
                        'visualMap' => 'false',
                        'label' => [
                            'show' => true,
                            'position' => 'inside'
                        ],
                    ],
                ];


        $option = [
            'color' => ['#f1975a', '#698ed0', '#b7b7b7', '#ffc000', '#ea7ccc', '#9a60b4', '#b7b7a4'],
            'animation' => false,
            'title' => [
                'text' => 'Production and Losses in Percentage by Month',
                'left' => 'center',
                'top' => '10',
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'right' => 'right',
                'top' => 50,
                //'padding' => -10 ,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '70%',
                'left' => 60,
            ],
        ];

        $chart->setOption($option);
        $losseskwhchartYearMonthly = $chart->render('Year_losses_monthly', ['style' => 'height: 800px; width:28cm; ']);


        $chart = new ECharts();
        // Actual
        $chart->series =
            [
                [
                    'type' => 'pie',
                    'data' => [
                        [
                            'value' => $failRelativeSOFPorcentMonth,
                            'name' => 'SOF',
                        ],
                        [
                            'value' => $failRelativeEFORPorcentMonth,
                            'name' => 'EFOR',
                        ],
                        [
                            'value' => $failRelativeOMCPorcentMonth,
                            'name' => 'OMC',
                        ],
                        [
                            'value' => $failRelativeGapsPorcentMonth,
                            'name' => 'Data Gaps',
                        ],
                    ],

                    'visualMap' => 'false',
                    'label' => [
                        'show' => false,
                    ],
                    'itemStyle' => [
                        'borderType' => 'solid',
                        'borderWidth' => 1,
                        'borderColor' => '#ffffff',
                    ],
                ],
            ];

        $option = [
            'animation' => false,
            'color' => ['#ed7d31', '#941651', '#ffc000'],
            'title' => [
                'text' => 'Failures',
                'left' => 'center',
                'top' => 'top',
                'textStyle' => ['fontSize' => 10],
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'orient' => 'horizontal',
                'left' => 'left',
                'bottom' => 0,
                'padding' => 0, 90, 0, 0,
            ],
        ];

        $chart->setOption($option);
        $fails_month = $chart->render('fails_month', ['style' => 'height: 175px; width:300px; ']);

        // fuer PA Report Month
        if ($anlage->getConfigType() == 1) {
            // Type 1 is the only one where acGrops are NOT the Inveerter
            $inverters = $anlage->getGroups()->count();
        } else {
            // use acGroups as Inverter
            $inverters = $anlage->getAcGroups()->count();
        }
        for ($inverter = 1; $inverter <= $inverters; ++$inverter) {
            $pa = [];
            for ($day = 1; $day <= $daysInReportMonth; ++$day) {
                $tempFrom = new \DateTime($report['reportYear'].'-'.$report['reportMonth']."-$day 00:00");
                $tempTo = new \DateTime($report['reportYear'].'-'.$report['reportMonth']."-$day 23:59");
                $pa[] = [
                    'form_date' => $day,
                    'pa' => $this->availability->calcAvailability($anlage, $tempFrom, $tempTo, $inverter, 0),//TODO: add a parameter to change the dep
                    'unit' => $inverter,
                ];
            }
            $outPa[] = $pa;

            unset($pa);
        }
        // End PA

        // Beginn Operations string_dayly1
        switch ($anlage->getConfigType()) {
            case 1:
                $sql = "SELECT DATE_FORMAT( stamp,'%d.%m.%Y') AS form_date, sum(wr_pdc) AS act_power_dc, group_dc as invgroup
                        FROM ".$anlage->getDbNameIst()."  
                        WHERE stamp BETWEEN '".$report['reportYear'].'-'.$report['reportMonth']."-1 00:00' and '".$report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth." 23:59' and group_dc > 0
                        GROUP BY form_date,group_dc ORDER BY group_dc,form_date";

                $sqlc = "SELECT DATE_FORMAT( a.stamp, '%d.%m.%Y') AS form_date, sum(b.wr_idc) AS act_current_dc
                    FROM (db_dummysoll a left JOIN ".$anlage->getDbNameIst()." b ON a.stamp = b.stamp) 
                    WHERE a.stamp BETWEEN '".$report['reportYear'].'-'.$report['reportMonth']."-1 00:00' and '".$report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth." 23:59' and b.group_dc > 0 
                    GROUP BY form_date,b.group_dc ORDER BY b.group_dc,form_date";
                break;
            case 2:
                $sql = "SELECT DATE_FORMAT( a.stamp,'%d.%m.%Y') AS form_date, sum(b.wr_pdc) AS act_power_dc, b.group_ac as invgroup
                        FROM (db_dummysoll a left JOIN ".$anlage->getDbNameIst()." b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '".$report['reportYear'].'-'.$report['reportMonth']."-1 00:00' and '".$report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth." 23:59' and b.group_ac > 0
                        GROUP BY form_date,b.group_ac ORDER BY b.group_ac,form_date";
                $sqlc = "SELECT DATE_FORMAT( a.stamp, '%d.%m.%Y') AS form_date, sum(b.wr_idc) AS act_current_dc
                    FROM (db_dummysoll a left JOIN ".$anlage->getDbNameIst()." b ON a.stamp = b.stamp) 
                    WHERE a.stamp BETWEEN '".$report['reportYear'].'-'.$report['reportMonth']."-1 00:00' and '".$report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth." 23:59' and b.group_ac > 0 
                    GROUP BY form_date,b.group_ac ORDER BY b.group_ac,form_date";
                break;
            case 3:
            case 4:
                $sql = "SELECT DATE_FORMAT( a.stamp,'%d.%m.%Y') AS form_date, sum(b.wr_pdc) AS act_power_dc, b.group_ac as invgroup
                        FROM (db_dummysoll a left JOIN ".$anlage->getDbNameIst()." b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '".$report['reportYear'].'-'.$report['reportMonth']."-1 00:00' and '".$report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth." 23:59' and b.group_ac > 0
                        GROUP BY form_date,b.group_ac ORDER BY b.group_ac,form_date";
                $sqlc = "SELECT DATE_FORMAT( a.stamp, '%d.%m.%Y') AS form_date, sum(b.wr_idc) AS act_current_dc
                    FROM (db_dummysoll a left JOIN ".$anlage->getDbNameDcIst()." b ON a.stamp = b.stamp) 
                    WHERE a.stamp BETWEEN '".$report['reportYear'].'-'.$report['reportMonth']."-1 00:00' and '".$report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth." 23:59' and b.group_ac > 0 
                    GROUP BY form_date,b.group_ac ORDER BY b.group_ac,form_date";
                break;
            default:
                $sql = "SELECT DATE_FORMAT( a.stamp,'%d.%m.%Y') AS form_date, sum(b.wr_pdc) AS act_power_dc, b.group_ac as invgroup
                        FROM (db_dummysoll a left JOIN ".$anlage->getDbNameDcIst()." b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '".$report['reportYear'].'-'.$report['reportMonth']."-1 00:00' and '".$report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth." 23:59' and b.group_ac > 0
                        GROUP BY form_date,b.group_ac ORDER BY b.group_ac,form_date";

                $sqlc = "SELECT DATE_FORMAT( a.stamp, '%d.%m.%Y') AS form_date, sum(b.wr_idc) AS act_current_dc
                        FROM (db_dummysoll a left JOIN ".$anlage->getDbNameDcIst()." b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '".$report['reportYear'].'-'.$report['reportMonth']."-1 00:00' and '".$report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth." 23:59' and b.group_ac > 0 
                        GROUP BY form_date,b.group_ac ORDER BY b.group_ac,form_date";
                break;
        }
        $result = $this->conn->prepare($sql);
        $result->execute();
        $resultc = $this->conn->prepare($sqlc);
        $resultc->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $currentrow = $resultc->fetch(PDO::FETCH_ASSOC);
            $dcIst[] = [
                'form_date' => $value['form_date'],
                'group' => $value['invgroup'],
                'act_power_dc' => $value['act_power_dc'],
                'act_current_dc' => $currentrow['act_current_dc'],
            ];
        }

        if ($anlage->getConfigType() == 1) {
            $sql = "SELECT DATE_FORMAT( a.stamp, '%d.%m.%Y') AS form_date, sum(b.ac_exp_power) AS exp_power_dc, sum(b.dc_exp_current) AS exp_current_dc,  b.group_dc as invgroup
            FROM (db_dummysoll a left JOIN ".$anlage->getDbNameDcSoll()." b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '".$report['reportYear'].'-'.$report['reportMonth']."-1 00:00' and '".$report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth." 23:59' and b.group_dc > 0 
            GROUP BY form_date,b.group_dc ORDER BY b.group_dc,form_date";
        } else {
            $sql = "SELECT DATE_FORMAT( a.stamp, '%d.%m.%Y') AS form_date, sum(b.ac_exp_power) AS exp_power_dc, sum(b.dc_exp_current) AS exp_current_dc,  b.group_ac as invgroup
            FROM (db_dummysoll a left JOIN ".$anlage->getDbNameDcSoll()." b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '".$report['reportYear'].'-'.$report['reportMonth']."-1 00:00' and '".$report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth." 23:59' and b.group_ac > 0 
            GROUP BY form_date,b.group_ac ORDER BY b.group_ac,form_date";
        }
        $result = $this->conn->prepare($sql);
        $result->execute();
        if ($result->rowCount() > 0) {
            $value = $result->fetchAll(PDO::FETCH_ASSOC);
            $i = 0;
            $j = 0;
            if (count($dcIst) < count($value)) {
                while ($i < count($value)) {
                    if ($dcIst[$j]['form_date'] > $value[$i]['form_date']) {
                        if ($dcIst[$j]['group'] == $value[$i]['invgroup']) {
                            $dcExpDcIst[] = [
                                'group' => $value[$i]['invgroup'],
                                'form_date' => date('d', strtotime($value['form_date'])),
                                'exp_power_dc' => $value[$i]['exp_power_dc'],
                                'exp_current_dc' => $value[$i]['exp_current_dc'],
                                'act_power_dc' => 0,
                                'act_current_dc' => 0,
                                'diff_current_dc' => -101,
                                'diff_power_dc' => -101,
                            ];
                            if (date('d', strtotime($value[$i]['form_date'])) >= $daysInReportMonth) {
                                $outTableCurrentsPower[] = $dcExpDcIst;
                                unset($dcExpDcIst);
                            }
                            ++$i;
                        } else {
                            $dcExpDcIst[] = [
                                'group' => $dcIst[$j]['invgroup'],
                                'form_date' => date('d', strtotime($dcIst[$j]['form_date'])),
                                'exp_power_dc' => 0,
                                'exp_current_dc' => 0,
                                'act_power_dc' => $dcIst[$j]['act_power_dc'],
                                'act_current_dc' => $dcIst[$j]['act_current_dc'],
                                'diff_current_dc' => -101,
                                'diff_power_dc' => -101,
                            ];
                            if (date('d', strtotime($dcIst[$j]['form_date'])) >= $daysInReportMonth) {
                                $outTableCurrentsPower[] = $dcExpDcIst;
                                unset($dcExpDcIst);
                            }
                            ++$j;
                        }
                    } elseif ($dcIst[$j]['form_date'] < $value[$i]['form_date']) {
                        if ($dcIst[$j]['group'] == $value[$i]['invgroup']) {
                            $dcExpDcIst[] = [
                                'group' => $dcIst[$j]['invgroup'],
                                'form_date' => date('d', strtotime($dcIst[$j]['form_date'])),
                                'exp_power_dc' => 0,
                                'exp_current_dc' => 0,
                                'act_power_dc' => $dcIst[$j]['act_power_dc'],
                                'act_current_dc' => $dcIst[$j]['act_current_dc'],
                                'diff_current_dc' => -101,
                                'diff_power_dc' => -101,
                            ];
                            if (date('d', strtotime($dcIst[$j]['form_date'])) >= $daysInReportMonth) {
                                $outTableCurrentsPower[] = $dcExpDcIst;
                                unset($dcExpDcIst);
                            }
                            ++$j;
                        } else {
                            if ($dcIst[$j]['group'] == $value[$i]['invgroup']) {
                                $dcExpDcIst[] = [
                                    'group' => $value[$i]['invgroup'],
                                    'form_date' => date('d', strtotime($value['form_date'])),
                                    'exp_power_dc' => $value[$i]['exp_power_dc'],
                                    'exp_current_dc' => $value[$i]['exp_current_dc'],
                                    'act_power_dc' => 0,
                                    'act_current_dc' => 0,
                                    'diff_current_dc' => -101,
                                    'diff_power_dc' => -101,
                                ];
                                if (date('d', strtotime($value[$i]['form_date'])) >= $daysInReportMonth) {
                                    $outTableCurrentsPower[] = $dcExpDcIst;
                                    unset($dcExpDcIst);
                                }
                                ++$i;
                            }
                        }
                    } else {
                        $dcExpDcIst[] = [
                            'group' => $value[$i]['invgroup'],
                            'form_date' => date('d', strtotime($dcIst[$j]['form_date'])),
                            'exp_power_dc' => $value[$i]['exp_power_dc'],
                            'exp_current_dc' => $value[$i]['exp_current_dc'],
                            'act_power_dc' => $dcIst[$j]['act_power_dc'],
                            'act_current_dc' => $dcIst[$j]['act_current_dc'],
                            'diff_current_dc' => ($dcIst[$j]['act_current_dc'] != 0) ? (($dcIst[$j]['act_current_dc'] - $value[$i]['exp_current_dc']) / $value[$i]['exp_current_dc']) * 100 : 0,
                            'diff_power_dc' => ($dcIst[$j]['act_power_dc'] != 0) ? (($dcIst[$j]['act_power_dc'] - $value[$i]['exp_power_dc']) / $value[$i]['exp_power_dc']) * 100 : 0,
                        ];
                        if (date('d', strtotime($value[$i]['form_date'])) >= $daysInReportMonth) {
                            $outTableCurrentsPower[] = $dcExpDcIst;
                            unset($dcExpDcIst);
                        }
                        ++$i;
                        ++$j;
                    }
                }
            } else {
                while ($j < count($dcIst)) {
                    if ($dcIst[$j]['form_date'] > $value[$i]['form_date']) {
                        if ($dcIst[$j]['group'] == $value[$i]['invgroup']) {
                            $dcExpDcIst[] = [
                                'group' => $value[$i]['invgroup'],
                                'form_date' => date('d', strtotime($value['form_date'])),
                                'exp_power_dc' => $value[$i]['exp_power_dc'],
                                'exp_current_dc' => $value[$i]['exp_current_dc'],
                                'act_power_dc' => 0,
                                'act_current_dc' => 0,
                                'diff_current_dc' => -101,
                                'diff_power_dc' => -101,
                            ];
                            if (date('d', strtotime($value[$i]['form_date'])) >= $daysInReportMonth) {
                                $outTableCurrentsPower[] = $dcExpDcIst;
                                unset($dcExpDcIst);
                            }
                            ++$i;
                        } else {
                            $dcExpDcIst[] = [
                                'group' => $dcIst[$j]['invgroup'],
                                'form_date' => date('d', strtotime($dcIst[$j]['form_date'])),
                                'exp_power_dc' => 0,
                                'exp_current_dc' => 0,
                                'act_power_dc' => $dcIst[$j]['act_power_dc'],
                                'act_current_dc' => $dcIst[$j]['act_current_dc'],
                                'diff_current_dc' => -101,
                                'diff_power_dc' => -101,
                            ];
                            if (date('d', strtotime($dcIst[$j]['form_date'])) >= $daysInReportMonth) {
                                $outTableCurrentsPower[] = $dcExpDcIst;
                                unset($dcExpDcIst);
                            }
                            ++$j;
                        }
                    } elseif ($dcIst[$j]['form_date'] < $value[$i]['form_date']) {
                        if ($dcIst[$j]['group'] == $value[$i]['invgroup']) {
                            $dcExpDcIst[] = [
                                'group' => $dcIst[$j]['invgroup'],
                                'form_date' => date('d', strtotime($dcIst[$j]['form_date'])),
                                'exp_power_dc' => 0,
                                'exp_current_dc' => 0,
                                'act_power_dc' => $dcIst[$j]['act_power_dc'],
                                'act_current_dc' => $dcIst[$j]['act_current_dc'],
                                'diff_current_dc' => -101,
                                'diff_power_dc' => -101,
                            ];
                            if (date('d', strtotime($dcIst[$j]['form_date'])) >= $daysInReportMonth) {
                                $outTableCurrentsPower[] = $dcExpDcIst;
                                unset($dcExpDcIst);
                            }
                            ++$j;
                        } else {
                            if ($dcIst[$j]['group'] == $value[$i]['invgroup']) {
                                $dcExpDcIst[] = [
                                    'group' => $value[$i]['invgroup'],
                                    'form_date' => date('d', strtotime($value['form_date'])),
                                    'exp_power_dc' => $value[$i]['exp_power_dc'],
                                    'exp_current_dc' => $value[$i]['exp_current_dc'],
                                    'act_power_dc' => 0,
                                    'act_current_dc' => 0,
                                    'diff_current_dc' => -101,
                                    'diff_power_dc' => -101,
                                ];
                                if (date('d', strtotime($value[$i]['form_date'])) >= $daysInReportMonth) {
                                    $outTableCurrentsPower[] = $dcExpDcIst;
                                    unset($dcExpDcIst);
                                }
                                ++$i;
                            }
                        }
                    } else {
                        $dcExpDcIst[] = [
                            'group' => $value[$i]['invgroup'],
                            'form_date' => date('d', strtotime($dcIst[$j]['form_date'])),
                            'exp_power_dc' => $value[$i]['exp_power_dc'],
                            'exp_current_dc' => $value[$i]['exp_current_dc'],
                            'act_power_dc' => $dcIst[$j]['act_power_dc'],
                            'act_current_dc' => $dcIst[$j]['act_current_dc'],
                            'diff_current_dc' => (($dcIst[$j]['act_current_dc'] - $value[$i]['exp_current_dc']) / $value[$i]['exp_current_dc']) * 100 ,
                            'diff_power_dc' =>  (($dcIst[$j]['act_power_dc'] - $value[$i]['exp_power_dc']) / $value[$i]['exp_power_dc']) * 100 ,
                        ];

                        if (date('d', strtotime($value[$i]['form_date'])) >= $daysInReportMonth) {
                            $outTableCurrentsPower[] = $dcExpDcIst;
                            unset($dcExpDcIst);
                        }
                        ++$i;
                        ++$j;
                    }
                }
            }
        } else {
            $actualCounter = 0;
            for ($j = 0; $j < $daysInReportMonth; ++$j) {
                if ($j == $actualCounter) {
                    $dcExpDcIst[] = [
                        'group' => $dcIst[$actualCounter]['group'],
                        'form_date' => date('d', strtotime($dcIst[$actualCounter]['form_date'])),
                        'exp_power_dc' => 0,
                        'exp_current_dc' => 0,
                        'act_power_dc' => $dcIst[$actualCounter]['act_power_dc'],
                        'act_current_dc' => $dcIst[$actualCounter]['act_current_dc'],
                        'diff_current_dc' => -101,
                        'diff_power_dc' => -101,
                    ];
                    ++$actualCounter;
                } else {
                    $dcExpDcIst[] = [
                        'group' => $dcIst[$actualCounter]['group'],
                        'form_date' => $j,
                        'exp_power_dc' => 0,
                        'exp_current_dc' => 0,
                        'act_power_dc' => 0,
                        'act_current_dc' => 0,
                        'diff_current_dc' => -101,
                        'diff_power_dc' => -101,
                    ];
                }
            }
        }
        if ($dcExpDcIst) {
            $outTableCurrentsPower[] = $dcExpDcIst;
        }
        $monthlyTableForPRAndPA = [];
        $graphArrayPR = [];
        for($index = 1; $index <= $month ; $index++){
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $index , (int)$report['reportYear']);
            $result = $this->PRCalulation->calcPR($anlage, new \DateTime($report['reportYear']."-".$index."-"."01"), new \DateTime($report['reportYear']."-".$index."-".$daysInMonth));
            $monthlyTableForPRAndPA[$index]['Dep0PA'] = round($result['pa0'], 2);
            $monthlyTableForPRAndPA[$index]['Dep1PA'] = round($result['pa1'], 2);
            $monthlyTableForPRAndPA[$index]['Dep2PA'] = round($result['pa2'], 2);
            $monthlyTableForPRAndPA[$index]['Dep3PA'] = round($result['pa3'], 2);
            $graphArrayPA['Dep0'][] = round($result['pa0'], 2);
            $graphArrayPA['Dep1'][] = round($result['pa1'], 2);
            $graphArrayPA['Dep2'][] = round($result['pa2'], 2);
            $graphArrayPA['Dep3'][] = round($result['pa3'], 2);

            if ($anlage->getUseGridMeterDayData())
            {
                $monthlyTableForPRAndPA[$index]['Dep0PR'] = round($result['prDep0EGridExt'], 2);
                $monthlyTableForPRAndPA[$index]['Dep1PR'] = round($result['prDep1EGridExt'], 2);
                $monthlyTableForPRAndPA[$index]['Dep2PR'] = round($result['prDep2EGridExt'], 2);
                $monthlyTableForPRAndPA[$index]['Dep3PR'] = round($result['prDep3EGridExt'], 2);
                $graphArrayPR['Dep0'][] = round($result['prDep0EGridExt'], 2);
                $graphArrayPR['Dep1'][] = round($result['prDep1EGridExt'], 2);
                $graphArrayPR['Dep2'][] = round($result['prDep2EGridExt'], 2);
                $graphArrayPR['Dep3'][] = round($result['prDep3EGridExt'], 2);
            }
            else if ($anlage->getShowEvuDiag()){
                $monthlyTableForPRAndPA[$index]['Dep0PR'] = round($result['prDep0Evu'], 2);
                $monthlyTableForPRAndPA[$index]['Dep1PR'] = round($result['prDep1Evu'], 2);
                $monthlyTableForPRAndPA[$index]['Dep2PR'] = round($result['prDep2Evu'], 2);
                $monthlyTableForPRAndPA[$index]['Dep3PR'] = round($result['prDep3Evu'], 2);
                $graphArrayPR['Dep0'][] = round($result['prDep0Evu'], 2);
                $graphArrayPR['Dep1'][] = round($result['prDep1Evu'], 2);
                $graphArrayPR['Dep2'][] = round($result['prDep2Evu'], 2);
                $graphArrayPR['Dep3'][] = round($result['prDep3Evu'], 2);
            }else{
                $monthlyTableForPRAndPA[$index]['Dep0PR'] = round($result['prDep0Act'], 2);
                $monthlyTableForPRAndPA[$index]['Dep1PR'] = round($result['prDep1Act'], 2);
                $monthlyTableForPRAndPA[$index]['Dep2PR'] = round($result['prDep2Act'], 2);
                $monthlyTableForPRAndPA[$index]['Dep3PR'] = round($result['prDep3Act'], 2);
                $graphArrayPR['Dep0'][] = round($result['prDep0Act'], 2);
                $graphArrayPR['Dep1'][] = round($result['prDep1Act'], 2);
                $graphArrayPR['Dep2'][] = round($result['prDep2Act'], 2);
                $graphArrayPR['Dep3'][] = round($result['prDep3Act'], 2);
            }

        }

        $chart = new ECharts(); // We must use AMCharts
        $chart->tooltip->show = false;

        $chart->xAxis = [
            'type' => 'category',
            'data' => array_slice($monthArray, 0, $report['reportMonth']),
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => '%',
            'min' => 0,
            'max' => 100,
        ];
        $series = [];
        $series[] =  [
            'name' => 'Open Book',
            'type' => 'bar',
            'data' =>  $graphArrayPR['Dep0'],
            'label' => [
                'show' => true,
                'rotate' => 90
            ],
            'markLine' => [
                'data' => [
                    [
                        'yAxis' => $anlage-> getContractualPR(),
                        'lineStyle' => [
                            'type'  => 'solid',
                            'width' => 3,
                            'color' => 'green'
                        ]
                    ]
                ],
                'symbol' => 'none',
        ]
        ];
        if (!$anlage->getSettings()->isDisableDep1()) $series[] =
            [
                'name' => 'O&M',
                'type' => 'bar',
                'data' =>  $graphArrayPR['Dep1'],
                'label' => [
                    'show' => true,
                    'rotate' => 90
                ],
            ];
        if( !$anlage->getSettings()->isDisableDep2()) $series[] = [
            'name' => 'EPC',
            'type' => 'bar',
            'data' =>  $graphArrayPR['Dep2'],
            'label' => [
                'show' => true,
                'rotate' => 90
            ],
        ];
        if( !$anlage->getSettings()->isDisableDep3()) $series[] =   [
            'name' => 'AM',
            'type' => 'bar',
            'data' =>  $graphArrayPR['Dep3'],
            'label' => [
                'show' => true,
                'rotate' => 90
            ],
        ];
        $chart->series = $series;


        $option = [
            'animation' => false,
            'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000'],
            'title' => [
                'fontFamily' => 'monospace',
                'text' => 'Plant PR',
                'left' => 'center',
                'top' => 10
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '80%',
                'left' => 100,
            ],
        ];

        $chart->setOption($option);

        $PR_MonthlyGraphic = $chart->render('PR_MonthlyGraphic', ['style' => 'height: 550px; width:900px;']);

        $chart = new ECharts(); // We must use AMCharts
        $chart->tooltip->show = false;

        $chart->xAxis = [
            'type' => 'category',
            'data' => array_slice($monthArray, 0, $report['reportMonth']),
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => '%',
            'min' => 0,
            'max' => 100,
        ];

        $series = [];
        $series[] =  [
            'name' => 'Open Book',
            'type' => 'bar',
            'data' =>  $graphArrayPA['Dep0'],
            'label' => [
                'show' => true,
                'rotate' => 90
            ],
            'markLine' => [
                'data' => [
                    [
                        'yAxis' => $anlage->getContractualAvailability(),
                        'lineStyle' => [
                        'name' => '*',
                        'type'  => 'solid',
                        'width' => 3,
                        'color' => 'green',
                        'label' => [
                            'formatter' => '{c} {b} *'
                        ]
                    ],
                    ]
                ],
                'symbol' => 'none'
            ]
        ];
        if (!$anlage->getSettings()->isDisableDep1()) $series[] =
            [
                'name' => 'O&M',
                'type' => 'bar',
                'data' =>  $graphArrayPA['Dep1'],
                'label' => [
                    'show' => true,
                    'rotate' => 90
                ],
            ];
        if( !$anlage->getSettings()->isDisableDep2()) $series[] = [
            'name' => 'EPC',
            'type' => 'bar',
            'data' =>  $graphArrayPA['Dep2'],
            'label' => [
                'show' => true,
                'rotate' => 90
            ],
        ];
        if( !$anlage->getSettings()->isDisableDep3()) $series[] =   [
            'name' => 'AM',
            'type' => 'bar',
            'data' =>  $graphArrayPA['Dep3'],
            'label' => [
                'show' => true,
                'rotate' => 90
            ],
        ];
        $chart->series = $series;



        $option = [
            'animation' => false,
            'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000'],
            'title' => [
                'fontFamily' => 'monospace',
                'text' => 'Plant PA',
                'left' => 'center',
                'top' => 10
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '80%',
                'left' => 100,
            ],
        ];

        $chart->setOption($option);

        $PA_MonthlyGraphic = $chart->render('PA_MonthlyGraphic', ['style' => 'height: 550px; width:900px;']);





        $resultEconomicsNames = $this->ecoVarNameRepo->findOneByAnlage($anlage);

        if ($resultEconomicsNames) {


            $ecoVarValues = $this->ecoVarValueRepo->findByAnlageYear($anlage, $report['reportYear']);
            $var1['name'] = $resultEconomicsNames->getVar1();
            $var2['name'] = $resultEconomicsNames->getVar2();
            $var3['name'] = $resultEconomicsNames->getVar3();
            $var4['name'] = $resultEconomicsNames->getVar4();
            $var5['name'] = $resultEconomicsNames->getVar5();
            $var6['name'] = $resultEconomicsNames->getVar6();
            $var7['name'] = $resultEconomicsNames->getVar7();
            $var8['name'] = $resultEconomicsNames->getVar8();
            $var9['name'] = $resultEconomicsNames->getVar9();
            $var10['name'] = $resultEconomicsNames->getVar10();
            $counter = 0;
            $sumvar1 = 0;
            $sumvar2 = 0;
            $sumvar3 = 0;
            $sumvar4 = 0;
            $sumvar5 = 0;
            $sumvar6 = 0;
            $sumvar7 = 0;
            $sumvar8 = 0;
            $sumvar9 = 0;
            $sumvar10 = 0;

            for ($i = 0; $i < 12; $i++){
                if ($ecoVarValues[$counter]) {
                    if (($ecoVarValues[$counter]->getMonth() == $i + 1)) {
                        $var1[$i] = (float)$ecoVarValues[$counter]->getVar1();
                        $var2[$i] = (float)$ecoVarValues[$counter]->getVar2();
                        $var3[$i] = (float)$ecoVarValues[$counter]->getVar3();
                        $var4[$i] = (float)$ecoVarValues[$counter]->getVar4();
                        $var5[$i] = (float)$ecoVarValues[$counter]->getVar5();
                        $var6[$i] = (float)$ecoVarValues[$counter]->getVar6();
                        $var7[$i] = (float)$ecoVarValues[$counter]->getVar7();
                        $var8[$i] = (float)$ecoVarValues[$counter]->getVar8();
                        $var9[$i] = (float)$ecoVarValues[$counter]->getVar9();
                        $var10[$i] = (float)$ecoVarValues[$counter]->getVar10();
                        $sumvar1 = $sumvar1 + $var1[$i];
                        $sumvar2 = $sumvar2 + $var2[$i];
                        $sumvar3 = $sumvar3 + $var3[$i];
                        $sumvar4 = $sumvar4 + $var4[$i];
                        $sumvar5 = $sumvar5 + $var5[$i];
                        $sumvar6 = $sumvar6 + $var6[$i];
                        $sumvar7 = $sumvar7 + $var7[$i];
                        $sumvar8 = $sumvar8 + $var8[$i];
                        $sumvar9 = $sumvar9 + $var9[$i];
                        $sumvar10 = $sumvar10 + $var10[$i];
                        $economicsMandy [$i] =
                            $var1[$i] +
                            $var2[$i] +
                            $var3[$i] +
                            $var4[$i] +
                            $var5[$i] +
                            $var6[$i] +
                            $var7[$i] +
                            $var8[$i] +
                            $var9[$i] +
                            $var10[$i];
                        (float)$kwhPrice[$i] = $ecoVarValues[$counter]->getKwHPrice();
                        if ($counter < count($ecoVarValues) - 1) $counter++;
                    }       else{
                        $economicsMandy [$i] = 0;
                        $var1[$i] = 0.0;
                        $var2[$i] = 0.0;
                        $var3[$i] = 0.0;
                        $var4[$i] = 0.0;
                        $var5[$i] = 0.0;
                        $var6[$i] = 0.0;
                        $var7[$i] = 0.0;
                        $var8[$i] = 0.0;
                        $var9[$i] = 0.0;
                        $var10[$i] = 0.0;
                        //what should we do when the kwh pricev is not set, by now = 0
                        (float) $kwhPrice[$i] = 0;
                    }
                }
                else{
                    $economicsMandy [$i] = 0;
                    $var1[$i] = 0.0;
                    $var2[$i] = 0.0;
                    $var3[$i] = 0.0;
                    $var4[$i] = 0.0;
                    $var5[$i] = 0.0;
                    $var6[$i] = 0.0;
                    $var7[$i] = 0.0;
                    $var8[$i] = 0.0;
                    $var9[$i] = 0.0;
                    $var10[$i] = 0.0;
                    //what should we do when the kwh pricev is not set, by now = 0
                    (float) $kwhPrice[$i] = 0;
                }

            }
        }

        if ($var1['name'] != "") {
            $graphData[0]['value'] = $sumvar1;
            $graphData[0]['name'] = $var1['name'];
        }
        if ($var2['name'] != "")  {
            $graphData[1]['value'] = $sumvar2;
            $graphData[1]['name'] = $var2['name'];
        }
        if ($var3['name'] != "")  {
            $graphData[2]['value'] = $sumvar3;
            $graphData[2]['name'] = $var3['name'];
        }
        if ($var4['name'] != "")  {
            $graphData[3]['value'] = $sumvar4;
            $graphData[3]['name'] = $var4['name'];
        }
        if ($var5['name'] != "")  {
            $graphData[4]['value'] = $sumvar5;
            $graphData[4]['name'] = $var5['name'];
        }
        if ($var6['name'] != "")  {
            $graphData[5]['value'] = $sumvar6;
            $graphData[5]['name'] = $var6['name'];
        }
        if ($var7['name'] != "")  {
            $graphData[6]['value'] = $sumvar7;
            $graphData[6]['name'] = $var7['name'];
        }
        if ($var8['name'] != "")  {
            $graphData[7]['value'] = $sumvar8;
            $graphData[7]['name'] = $var8['name'];
        };
        if ($var9['name'] != "")  {
            $graphData[8]['value'] = $sumvar9;
            $graphData[8]['name'] = $var9['name'];
        }
        if ($var10['name'] != "")  {
            $graphData[9]['value'] = $sumvar10;
            $graphData[9]['name'] = $var10['name'];
        }

        $economicsMandy2 = [
            'var1'  => $var1,
            'var2'  => $var2,
            'var3'  => $var3,
            'var4'  => $var4,
            'var5'  => $var5,
            'var6'  => $var6,
            'var7'  => $var7,
            'var8'  => $var8,
            'var9'  => $var9,
            'var10' => $var10
        ];
        // beginn Operating statement
        for ($i = 0; $i < 12; ++$i) {
            if ($i < 9) {
                $j= $i + 1;
                $month_transfer = "0$j";
            } else {
                $j= $i + 1;
                $month_transfer = $j;
            }
            $start = $report['reportYear'].'-'.$month_transfer.'-01 00:00';
            $endDayOfMonth = cal_days_in_month(CAL_GREGORIAN, $month_transfer, $report['reportYear']);
            $end = $report['reportYear'].'-'.$month_transfer.'-'.$endDayOfMonth.' 23:59';
            $data1_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);

            if ($anlage->hasPVSYST()) {
                try {
                    $resultErtrag_design = $this->pvSystMonthRepo->findOneMonth($anlage, $i + 1);
                } catch (NonUniqueResultException $e) {
                }
            } else {
                $resultErtrag_design = 0;
            }
            if ($resultErtrag_design) {
                $Ertrag_design = $resultErtrag_design->getErtragDesign();
            }
            else $Ertrag_design = 0;
            $monthleyFeedInTarif = $kwhPrice[$i];

            if ($anlage->getShowEvuDiag()) {
                (float) $power = $data1_grid_meter['powerEvu'];
            } else if ($anlage->getUseGridMeterDayData()){
                (float) $power = $data1_grid_meter['powerEGridExt'];
            }else{
                (float) $power = $data1_grid_meter['powerAct']; // Inv out
            }
            if (((float)$data1_grid_meter['powerAct'] > 0 ) && ($i < $month -1)) $incomePerMonth['revenues_act'][$i] = $power * $monthleyFeedInTarif;
            else $incomePerMonth['revenues_act'][$i] = 0;

            if ((float)$Ertrag_design > 0) $incomePerMonth['PVSYST_plan_proceeds_EXP'][$i] = (float)$Ertrag_design * $monthleyFeedInTarif;
            else $incomePerMonth['PVSYST_plan_proceeds_EXP'][$i] = 0;

            //PARA ESTO USAR FORECAST EN VEZ DE G4N EXPECTED
            if ((float)$forecast[$i] > 0) $incomePerMonth['gvn_plan_proceeds_EXP'][$i] = (float)$forecast[$i] * $monthleyFeedInTarif;
            else $incomePerMonth['gvn_plan_proceeds_EXP'][$i] = 0;

            if ((float)$data1_grid_meter['powerExp'] > 0 ) {
                (float)$incomePerMonth['powerExp'][$i] = (float)$data1_grid_meter['powerExp'] * $monthleyFeedInTarif;
            }
            else{
                $incomePerMonth['powerExp'][$i] = 0;
            }

            if ($incomePerMonth['revenues_act'][$i] == 0) $incomePerMonth['revenues_act_minus_totals'][$i] = 0;
            else $incomePerMonth['revenues_act_minus_totals'][$i] = $incomePerMonth['revenues_act'][$i] - $economicsMandy[$i];

            if ($incomePerMonth['PVSYST_plan_proceeds_EXP'][$i] == 0) $incomePerMonth['PVSYST_plan_proceeds_EXP_minus_totals'][$i] = 0;
            else $incomePerMonth['PVSYST_plan_proceeds_EXP_minus_totals'][$i] = $incomePerMonth['PVSYST_plan_proceeds_EXP'][$i] - $economicsMandy[$i];

            if ($incomePerMonth['gvn_plan_proceeds_EXP'][$i] == 0) $incomePerMonth['gvn_plan_proceeds_EXP_minus_totals'][$i] = 0;
            else $incomePerMonth['gvn_plan_proceeds_EXP_minus_totals'][$i] = $incomePerMonth['gvn_plan_proceeds_EXP'][$i] - $economicsMandy[$i];

            if ($incomePerMonth['powerExp'] == 0) $incomePerMonth['powerExpTotal'][$i] = 0;
            else $incomePerMonth['powerExpTotal'][$i] = $incomePerMonth['powerExp'][$i] - $economicsMandy[$i];
            $incomePerMonth['monthley_feed_in_tarif'][$i] = $monthleyFeedInTarif;
        }
        $this->logMessages->updateEntry($logId, 'working', 80);
        $revenuesSumPVSYST[0] = $incomePerMonth['revenues_act'][0];
        $revenuesSumG4N[0] = $incomePerMonth['revenues_act'][0];
        $revenuesSumForecast[0] = $incomePerMonth['powerExp'][0];
        $P50SumPVSYS[0] = $incomePerMonth['PVSYST_plan_proceeds_EXP'][0];
        $P50SumG4N[0] = $incomePerMonth['gvn_plan_proceeds_EXP'][0];
        $costSum[0] = $economicsMandy[0];
        for ($i = 1; $i < 12; ++$i) {
            $costSum[$i] = $costSum[$i - 1] + $economicsMandy[$i];

            if (($incomePerMonth['revenues_act'][$i] > 0) && ($i < $month - 1)) {
                $revenuesSumG4N[$i] = ($revenuesSumG4N[$i-1] + $incomePerMonth['revenues_act'][$i]) - $costSum[$i];
                $revenuesSumPVSYST[$i] = ($revenuesSumPVSYST[$i-1] + $incomePerMonth['revenues_act'][$i]) - $costSum[$i];
                $revenuesSumForecast[$i] = $revenuesSumForecast[$i - 1] + $incomePerMonth['powerExp'][$i] - $costSum[$i];
            }
            else{
                $revenuesSumG4N[$i] = ($revenuesSumG4N[$i-1] + $incomePerMonth['gvn_plan_proceeds_EXP'][$i]) - $costSum[$i];
                $revenuesSumPVSYST[$i] = ($revenuesSumPVSYST[$i-1] + $incomePerMonth['PVSYST_plan_proceeds_EXP'][$i]) - $costSum[$i];
                $revenuesSumForecast[$i] = $revenuesSumForecast[$i - 1] + $incomePerMonth['gvn_plan_proceeds_EXP'][$i] - $costSum[$i];
            }
            $P50SumPVSYS[$i] = $incomePerMonth['PVSYST_plan_proceeds_EXP'][$i] + $P50SumPVSYS[$i - 1];
            $P50SumG4N[$i] = $incomePerMonth['gvn_plan_proceeds_EXP'][$i] + $P50SumG4N[$i - 1];
        }

        $economicsCumulatedForecast = [
            'revenues_ACT_and_Revenues_Plan_PVSYT' => $revenuesSumPVSYST,
            'revenues_ACT_and_Revenues_Plan_G4N' => $revenuesSumG4N,
            'revenues_EXP_and_Revenues_Plan_Forecast' =>  $revenuesSumForecast,
            'PVSYST_plan_proceeds_P50' => $P50SumPVSYS,
            'g4n_plan_proceeds_EXP_P50' => $P50SumG4N,
        ];

        $chart = new ECharts();
        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $monthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => 'EUR',
            'nameLocation' => 'middle',
            'nameGap' => 70,
        ];
        $chart->series =
            [
                [
                    'name' => 'Actual plus Forecast',
                    'type' => 'line',
                    'data' => $economicsCumulatedForecast['revenues_ACT_and_Revenues_Plan_G4N'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Actual plus Plan Simulation',
                    'type' => 'line',
                    'data' => $economicsCumulatedForecast['revenues_ACT_and_Revenues_Plan_PVSYT'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Expected g4n plus Forecast',
                    'type' => 'line',
                    'data' => $economicsCumulatedForecast['revenues_ACT_and_Revenues_Plan_Forecast'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Forecast P50',
                    'type' => 'line',
                    'data' => $economicsCumulatedForecast['g4n_plan_proceeds_EXP_P50'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Plan Simulation P50',
                    'type' => 'line',
                    'data' => $economicsCumulatedForecast['PVSYST_plan_proceeds_P50'],
                    'visualMap' => 'false',
                ],

            ];

        $option = [
            'animation' => false,
            'color' => ['#4472c4', '#ed7d31', '#a5a5a5', '#ffc000'],
            'title' => [
                'text' => 'Cumulated Forecast',
                'left' => 'center',
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '70%',
                'top' => 50,
                'width' => '80%',
            ],
        ];
        $chart->setOption($option);

        $economicsCumulatedForecastChart = $chart->render('economicsCumulatedForecastChart', ['style' => 'height: 380px; width:26cm;']);

        // end Chart economics Cumulated Forecast

        $chart = new ECharts();
        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $monthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 70,
        ];


            $chart->series =
                [
                    [
                        'name' => 'Difference ACT to expected g4n',
                        'type' => 'line',
                        'data' => $diefference_prod_to_expected_g4n,
                        'visualMap' => 'false',
                    ],
                    [
                        'name' => 'Difference ACT to forecast',
                        'type' => 'line',
                        'data' => $difference_prod_to_forecast,
                        'visualMap' => 'false',
                    ],
                ];



        $option = [
            'animation' => false,
            'color' => ['#0070c0', '#c55a11', '#a5a5a5'],
            'title' => [
                'text' => '',
                'left' => 'center',
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '70%',
                'top' => 50,
                'bottom' => 0,
                'width' => '90%',
            ],
        ];
        $chart->setOption($option);
        $losses_monthly = $chart->render('losses_monthly', ['style' => 'height: 450px; width:28cm;']);


        $chart = new ECharts();
        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $monthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'min' => 0,
            'name' => 'EUR',
            'nameLocation' => 'middle',
            'nameGap' => 70,
        ];

        $chart->series =
            [
                [
                    'name' => 'Actual',
                    'type' => 'bar',
                    'data' => $incomePerMonth['revenues_act'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Plan simulation',
                    'type' => 'bar',
                    'data' => $incomePerMonth['PVSYST_plan_proceeds_EXP_minus_totals'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Expected g4n',
                    'type' => 'bar',
                    'data' => $incomePerMonth['powerExp'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Forecast',
                    'type' => 'bar',
                    'data' => $incomePerMonth['gvn_plan_proceeds_EXP'],
                    'visualMap' => 'false',
                ],
            ];

        $option = [
            'animation' => false,
            'color' => ['#9dc3e6', '#f4b183', '#92d050'],
            'title' => [
                'text' => 'Income per month '.$report['reportYear'],
                'left' => 'center',
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '90%',
            ],
        ];
        $chart->setOption($option);

        $income_per_month_chart = $chart->render('income_per_month_chart', ['style' => 'height: 350px; width:950px;']);

        $chart = new ECharts();
        $chart->series =
            [
                [
                    'type' => 'pie',
                    'data' =>$graphData,
                    'visualMap' => 'false',
                    'label' => [
                        'show' => false,
                    ],
                    'center' => [
                        90, 120,
                    ],
                    'top' => -10,
                    'itemStyle' => [
                        'borderType' => 'solid',
                        'borderWidth' => 1,
                        'borderColor' => '#ffffff',
                    ],
                ],
            ];

        $option = [
            'animation' => false,
            'color' => [
                '#5e85cc', '#f4ad7d', '#c6c6c6',
                '#ffd966', '#8fbae2', '#9dc97f',
                '#4669a7', '#d87735', '#909090',
                '#cc9f15', '#4e8abf', '#6a994b',
                '#8ba7db', '#f4ae7f', '#cecece',
            ],
            'title' => [
                'text' => 'TOTAL Costs per Date - '.$report['reportYear'],
                'left' => 'center',
                'top' => 5,
            ],

            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'orient' => 'vertical',
                'top' => 30,
                'right' => 40,
            ],
        ];

        $chart->setOption($option);
        $total_Costs_Per_Date = $chart->render('total_Costs_Per_Date', ['style' => 'height: 210px; width:26cm; margin-left:80px;']);


        $chart = new ECharts();

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $monthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'min' => 0,
            'name' => '',
            'nameLocation' => 'middle',
            'nameGap' => 70,
        ];
        $chart->series =
            [
                [
                    'name' => 'Actual - Profit',
                    'type' => 'bar',
                    'data' => $incomePerMonth['revenues_act_minus_totals'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Plan simulation - proceeds',
                    'type' => 'bar',
                    'data' => $incomePerMonth['PVSYST_plan_proceeds_EXP_minus_totals'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Forecast - proceeds',
                    'type' => 'bar',
                    'data' => $incomePerMonth['gvn_plan_proceeds_EXP_minus_totals'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Expected g4n - proceeds',
                    'type' => 'bar',
                    'data' => $incomePerMonth['powerExpTotal'],
                    'visualMap' => 'false',
                ],
            ];

        $option = [
            'animation' => false,
            'color' => ['#9dc3e6', '#f4b183', '#92d050'],
            'title' => [
                'text' => 'Operating statement - '.$report['reportYear'].' [EUR]',
                'left' => 'center',
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '90%',
            ],
        ];

        $chart->setOption($option);

        $operating_statement_chart = $chart->render('operating_statement_chart', ['style' => 'height: 350px; width:950px;']);

        $this->logMessages->updateEntry($logId, 'working', 90);
        for ($i = 0; $i < 12; ++$i) {
            if ($i < $month - 1) {
                $Difference_Profit_ACT_to_PVSYST_plan[] = $incomePerMonth['revenues_act_minus_totals'][$i] - $incomePerMonth['PVSYST_plan_proceeds_EXP_minus_totals'][$i];
                $Difference_Profit_ACT_to_g4n_plan[] = $incomePerMonth['revenues_act_minus_totals'][$i] - $incomePerMonth['gvn_plan_proceeds_EXP_minus_totals'][$i];
                $Difference_Profit_to_EXP[] = $incomePerMonth['revenues_act_minus_totals'][$i] - $incomePerMonth['powerExpTotal'][$i];
            }
            else{
                $Difference_Profit_ACT_to_PVSYST_plan[] = 0;
                $Difference_Profit_ACT_to_g4n_plan[] =  0;
                $Difference_Profit_to_EXP[] = 0;
            }
        }

        $lossesComparedTable = [
            'Difference_Profit_ACT_to_PVSYST_plan' => $Difference_Profit_ACT_to_PVSYST_plan,
            'Difference_Profit_ACT_to_g4n_plan' => $Difference_Profit_ACT_to_g4n_plan,
            'Difference_Profit_to_expected' => $Difference_Profit_to_EXP
        ];

        $chart = new ECharts();
        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $monthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => 'EUR',
            'nameLocation' => 'middle',
            'nameGap' => 70,
        ];
        $chart->series =
            [
                [
                    'name' => 'Diff ACT - Profit to plan simulation',
                    'type' => 'bar',
                    'data' => $lossesComparedTable['Difference_Profit_ACT_to_PVSYST_plan'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Diff. ACT - Profit to g4n forecast',
                    'type' => 'bar',
                    'data' => $lossesComparedTable['Difference_Profit_ACT_to_g4n_plan'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Diff. ACT- Profit to EXP g4n',
                    'type' => 'bar',
                    'data' => $lossesComparedTable['Difference_Profit_to_expected'],
                    'visualMap' => 'false',
                ],
            ];

        $option = [
            'animation' => false,
            'color' => ['#9dc3e6', '#92d050'],
            'title' => [
                'text' => 'Losses Compared',
                'left' => 'center',
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '90%',
            ],
        ];

        $chart->setOption($option);

        $losses_compared_chart = $chart->render('lossesCompared', ['style' => 'height: 350px; width:950px;']);


        // end Chart Losses compared

        // beginn Table Losses compared cummulated
        $PVSYSDiffSum[0] = $lossesComparedTable['Difference_Profit_ACT_to_PVSYST_plan'][0];
        $G4NDiffSum[0] = $lossesComparedTable['Difference_Profit_ACT_to_g4n_plan'][0];
        $G4NEXPDiffSum[0] = $lossesComparedTable['Difference_Profit_to_expected'][0];
        for ($i = 0; $i < 12; ++$i) {
            if ($i < $month - 1) {
                $PVSYSDiffSum[$i] = $lossesComparedTable['Difference_Profit_ACT_to_PVSYST_plan'][$i] + $PVSYSDiffSum[$i - 1];
                $G4NDiffSum[$i] = $lossesComparedTable['Difference_Profit_ACT_to_g4n_plan'][$i] + $G4NDiffSum[$i - 1];
                $G4NEXPDiffSum[$i] = $lossesComparedTable['Difference_Profit_to_expected'][$i] + $G4NEXPDiffSum[$i - 1];
            }
            else{
                $PVSYSDiffSum[$i] = 0;
                $G4NDiffSum[$i] = 0;
                $G4NEXPDiffSum[$i] = 0;
            }
        }

        $lossesComparedTableCumulated = [
            'Difference_Profit_ACT_to_PVSYST_plan_cum' => $PVSYSDiffSum,
            'Difference_Profit_ACT_to_g4n_plan_cum' => $G4NDiffSum,
            'Difference_Profit_to_expected' => $G4NEXPDiffSum
        ];

        // end Table Losses compared cummulated

        // beginn Chart Losses compared cummulated
        $chart = new ECharts();
          $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $monthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => 'EUR',
            'nameLocation' => 'middle',
            'nameGap' => 70,
        ];
        $chart->series =
            [
                [
                    'name' => 'Diff. ACT - Profit to plan simulation',
                    'type' => 'line',
                    'data' => $lossesComparedTableCumulated['Difference_Profit_ACT_to_PVSYST_plan_cum'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Diff. ACT - Profit to g4n forecast',
                    'type' => 'line',
                    'data' => $lossesComparedTableCumulated['Difference_Profit_ACT_to_g4n_plan_cum'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Diff. ACT- Profit to EXP g4n',
                    'type' => 'line',
                    'data' => $lossesComparedTableCumulated['Difference_Profit_to_expected'],
                    'visualMap' => 'false',
                ],
            ];

        $option = [
            'animation' => false,
            'color' => ['#9dc3e6', '#92d050'],
            'title' => [
                'text' => 'Cummulative Losses Operating statement [EUR] ',
                'left' => 'center',
            ],
            'tooltip' => [
                'show' => true,
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 50,
                'width' => '90%',
            ],
        ];

        $chart->setOption($option);
        if ($anlage->getConfigType() == 1) {
            $acGroupsCleaned = $this->functions->getNameArray($anlage, 'dc', false);
        }
        $cumulated_losses_compared_chart = $chart->render('cumulatedlossesCompared', ['style' => 'height: 350px; width:950px;']);


        $TicketAvailabilityMonthTable =$this->PRCalulation->calcPR( $anlage, date_create(date("Y-m-d ",strtotime($report['from']))), date_create(date("Y-m-d ",strtotime($report['to']))));
        $TicketAvailabilityYearTable = $this->PRCalulation->calcPR( $anlage, date_create(date("Y-m-d ",strtotime($report['from']))), date_create(date("Y-m-d ",strtotime($report['to']))), "year");

        $efficiencyArray= $this->calcPRInvArrayDayly($anlage, "01", "2023");
        $orderedEfficiencyArray = [];
        $index = 0;
        $index2 = 0;
        $index3 = 0;
        while (count($efficiencyArray['avg']) !== 0){
            $keys = array_keys($efficiencyArray['avg'], min($efficiencyArray['avg']));
            foreach($keys as $key ){
                $orderedEfficiencyArray[$index2]['avg'][$index] = $efficiencyArray['avg'][$key];
                $orderedEfficiencyArray[$index2]['names'][$index] = $invArray[$key];
                foreach ($efficiencyArray['values'][$key] as $value){
                    $orderedEfficiencyArray[$index2]['value'][$index3] = [$invArray[$key], $value];

                    $index3 = $index3 + 1;
                }

                unset($efficiencyArray['values'][$key]);
                unset($efficiencyArray['avg'][$key]);
                $index = $index + 1;
                if ($index >= 30){
                    $index = 0;
                    $index2 = $index2 + 1;
                    $index3 = 0;
                }

            }
        }
        $efficiencyRanking[] = [];
        foreach($orderedEfficiencyArray as $key => $data) {

            $chart = new ECharts(); // We must use AMCharts
            $chart->tooltip->show = false;
            $chart->tooltip->trigger = 'item';
            $chart->xAxis = [
                'type' => 'category',
                'axisLabel' => [
                    'show' => true,
                    'margin' => '10',
                    'rotate' => 45
                ],
                'splitArea' => [
                    'show' => true,
                ],
                'data' => $data['names'],
            ];
            $chart->yAxis = [
                [
                    'type' => 'value',
                    'min' => 50,
                    'max' => 100,
                    'name' => '[%]',

                ],

            ];
            $chart->series =
                [
                    [
                        'name' => 'Daily Efficiency',
                        'simbolSize' => 1,
                        'type' => 'scatter',
                        'data' => $data['value'],
                        'visualMap' => 'false',
                    ],

                    [
                        'name' => 'Average Efficiency',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $data['avg'],
                        'lineStyle' => [
                            'color' => 'green'
                        ],
                    ],
                ];
            $option = [
                'textStyle' => [
                    'fontFamily' => 'monospace',
                    'fontsize' => '16'
                ],
                'animation' => false,
                'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000'],
                'title' => [
                    'fontFamily' => 'monospace',
                    'text' => 'Inverter efficiency ranking',
                    'left' => 'center',
                    'top' => 10
                ],
                'tooltip' => [
                    'show' => true,
                ],
                'legend' => [
                    'show' => true,
                    'left' => 'center',
                    'top' => 20,
                ],
                'grid' => [
                    'height' => '80%',
                    'top' => 50,
                    'width' => '80%',
                    'left' => 100,
                ],
            ];
            $chart->setOption($option);
            $efficiencyRanking[$key] = $chart->render('efficiency_rank_'.$key, ['style' => 'height: 550; width:900px;']);

        }

        //Waterfall page generation
        $sumForecast = 0;
        $sumForecastIrr = 0;
        $sumActual = 0;
        $sumExp = 0;
        $sumIrr = 0;
        $sumSOR = 0;
        $sumEFOR = 0;
        $sumOMC = 0;
        $sumGapLosses = 0;
        $sumPPCLosses = 0;
        $sumOthers = 0;
        $sumCorrectedForecast = 0;
        $sumTotalLosses = 0;
        for($i = 0; $i < $report['reportMonth'] ; $i++){
            if ($anlage->hasPVSYST()) {
                $resultErtrag_design = $this->pvSystMonthRepo->findOneMonth($anlage, $i + 1);
                $forecastIrr = $resultErtrag_design->getIrrDesign();
            }
            else {
                if ($anlage->getUseDayForecast()) {
                    $forecastIrr = $this->getForecastIrr($anlage, $i + 1);
                    if ($forecastIrr == null) $forecastIrr = $irradiation[$i];
                } else {
                    $forecastIrr = $irradiation[$i];
                }
            }
            if( $irradiation[$i] > 0 && $forecastIrr > 0) {
                $irrCorrection = $forecastIrr / $irradiation[$i];
            }
            else{
                $irrCorrection = 1 ;
            }

            $waterfallDiagramHelpTable[$i]['forecast'] = round($forecast[$i], 2);
            $sumForecast = $sumForecast + $waterfallDiagramHelpTable[$i]['forecast'];

            $waterfallDiagramHelpTable[$i]['correctedForecast'] = round($waterfallDiagramHelpTable[$i]['forecast'] / $irrCorrection, 2);
            $sumCorrectedForecast = $sumCorrectedForecast + $waterfallDiagramHelpTable[$i]['correctedForecast'];

            $waterfallDiagramHelpTable[$i]['forecastIrr'] = round($forecastIrr, 2);
            $sumForecastIrr = $sumForecastIrr + $waterfallDiagramHelpTable[$i]['forecastIrr'];
            
            $waterfallDiagramHelpTable[$i]['actual'] = round($tbody_a_production['powerAct'][$i], 2);
            $sumActual = $sumActual + $waterfallDiagramHelpTable[$i]['actual'];

            $waterfallDiagramHelpTable[$i]['irradiation'] = round( $irradiation[$i], 2);
            $sumIrr = $sumIrr + $waterfallDiagramHelpTable[$i]['forecastIrr'];

            $waterfallDiagramHelpTable[$i]['SORLosses'] = round($kwhLosses[$i]['SORLosses'], 2);
            $sumSOR = $sumSOR + $waterfallDiagramHelpTable[$i]['SORLosses'];
            
            $waterfallDiagramHelpTable[$i]['EFORLosses'] = round($kwhLosses[$i]['EFORLosses'], 2);
            $sumEFOR = $sumEFOR + $waterfallDiagramHelpTable[$i]['EFORLosses'];
            
            $waterfallDiagramHelpTable[$i]['OMCLosses'] = round($kwhLosses[$i]['OMCLosses'], 2);
            $sumOMC = $sumOMC + $waterfallDiagramHelpTable[$i]['OMCLosses'];


            $waterfallDiagramHelpTable[$i]['PPCLosses'] = round($kwhLosses[$i]['PPCLosses'], 2);
            $sumPPCLosses = $sumPPCLosses + $waterfallDiagramHelpTable[$i]['PPCLosses'];


            $waterfallDiagramHelpTable[$i]['GapLosses'] = round($kwhLosses[$i]['DataGapLosses'], 2);
            $sumGapLosses = $sumGapLosses + $waterfallDiagramHelpTable[$i]['GapLosses'];

            $sumLosses = $waterfallDiagramHelpTable[$i]['SORLosses'] + $waterfallDiagramHelpTable[$i]['EFORLosses'] + $waterfallDiagramHelpTable[$i]['OMCLosses'] + $waterfallDiagramHelpTable[$i]['GapLosses'] + $waterfallDiagramHelpTable[$i]['PPCLosses'];

            $waterfallDiagramHelpTable[$i]['otherLosses'] = round($tbody_a_production['powerExp'][$i] - $tbody_a_production['powerAct'][$i] - $sumLosses, 2);
            $sumOthers = $sumOthers + $waterfallDiagramHelpTable[$i]['otherLosses'];

            $waterfallDiagramHelpTable[$i]['totalLosses'] = round($waterfallDiagramHelpTable[$i]['otherLosses'] + $sumLosses, 2);
            $sumTotalLosses = $sumTotalLosses + $waterfallDiagramHelpTable[$i]['totalLosses'];

            $waterfallDiagramHelpTable[$i]['expected'] = round($tbody_a_production['powerExp'][$i], 2);
            $sumExp = $sumExp + $waterfallDiagramHelpTable[$i]['expected'];
        }
        $waterfallDiagramHelpTable[$i + 1]['correctedForecast'] = $sumCorrectedForecast;
        $waterfallDiagramHelpTable[$i + 1]['forecast'] = $sumForecast;
        $waterfallDiagramHelpTable[$i + 1]['forecastIrr'] = $sumForecastIrr;
        $waterfallDiagramHelpTable[$i + 1]['actual'] = $sumActual;
        $waterfallDiagramHelpTable[$i + 1]['irradiation'] = $sumIrr;
        $waterfallDiagramHelpTable[$i + 1]['SORLosses'] = $sumSOR;
        $waterfallDiagramHelpTable[$i + 1]['EFORLosses'] = $sumEFOR;
        $waterfallDiagramHelpTable[$i + 1]['OMCLosses'] = $sumOMC;
        $waterfallDiagramHelpTable[$i + 1]['PPCLosses'] = $sumPPCLosses;
        $waterfallDiagramHelpTable[$i + 1]['GapLosses'] = $sumGapLosses;
        $waterfallDiagramHelpTable[$i + 1]['otherLosses'] = $sumOthers;
        $waterfallDiagramHelpTable[$i + 1]['expected'] = $sumExp;
        $waterfallDiagramHelpTable[$i + 1]['totalLosses'] = $sumTotalLosses;

        unset($data);
        foreach ($waterfallDiagramHelpTable[(int)$report['reportMonth'] - 1] as $key => $value){
            if ($key != "forecastIrr" && $key != "irradiation" && $key != "forecast" && $key != "totalLosses")$data[] = round($value, 2);
        }
        $positive = [];
        $negative = [];
        $help = [];
        $sum = 0;

        foreach ($data as $key => $item){
            if ($item >= 0 ){
                $positive[] = $item;

                $negative[] = 0;
            }
            else{
                $negative[] = -$item;
                $positive[] = 0;
            }

            if ($key <= 1) $help[$key] = 0;

            else if ($key === count($data)-1) $help[$key] = 0;
            else{
                $sum += $data[$key - 1];
                if ($item < 0){
                    $help[] = $sum + $item;
                }
                else{
                    $help[] = $sum;
                }
            }
        }
        $chart = new ECharts();

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
                'right' => '10',
                'interval' => '0'
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' =>['Forecast', 'Actual', 'SOR Losses', ' EFOR Losses', 'OMC Losses', 'Regulatory', 'Data Gap Losses', 'Other Losses', 'Expected'],
        ];
        $chart->yAxis = [
            'type' => 'value',
        ];
        $chart->series =
            [
                [
                    'type' => 'bar',
                    'stack' => 'x',
                    'itemStyle' => [
                        'normal' => [
                            'barBorderColor' => 'rgba(0,0,0,0)',
                            'color' => 'rgba(0,0,0,0)'
                        ],
                        'emphasis' => [
                            'barBorderColor' => 'rgba(0,0,0,0)',
                            'color' => 'rgba(0,0,0,0)'
                        ]
                    ],
                    'data' => $help,
                ],
                [
                    'name' => 'positive',
                    'type' => 'bar',
                    'stack' => 'x',
                    'data' => $positive,
                ],
                [
                    'name' => 'negative',
                    'type' => 'bar',
                    'stack' => 'x',
                    'data' => $negative,
                    'itemStyle'=>[
                        'color'=>'#f33'
                    ],

                ],

            ];

        $option =[
            'animation' => false,

        ];
        $chart->setOption($option);
        $waterfallDiagram = $chart->render('waterfall', ['style' => 'height: 450px; width:28cm;']);

        // end Chart Losses compared cummulated
        $output = [
            'plantId' => $plantId,
            'owner' => $anlage->getEigner()->getFirma(),
            'plantSize' => $plantSize,
            'plantName' => $anlage->getAnlName(),
            'anlGeoLat' => $anlage->getAnlGeoLat(),
            'anlGeoLon' => $anlage->getAnlGeoLon(),
            'month' => $monthName,
            'reportmonth' => $report['reportMonth'],
            'year' => $report['reportYear'],
            'monthArray' => $monthArray,
            'dataCfArray' => $dataCfArray,
            'operations_right' => $operations_right,
            'operations_right_withForecast'=>$operations_right_withForecast,
            'degradation' => $degradation,
            'forecast_PVSYST_table' => $forecast_PVSYST_table,
            'forecast_G4N_table' => $forecast_G4N_table,
            'forecast_PVSYST' => $forecast_PVSYST,
            'forecast_G4N' => $forecast_G4N,
            'table_overview_monthly' => $tbody_a_production,
            'losses_t1' => $losses_t1,
            'losses_t2' => $losses_t2,
            'losses_year' => $losses_year,
            'losses_monthly' => $losses_monthly,
            'production_monthly_chart' => $production_monthly_chart,
            'operations_monthly_right_pvsyst_tr1' => $operations_monthly_right_pvsyst_tr1,
            'operations_monthly_right_pvsyst_tr2' => $operations_monthly_right_pvsyst_tr2,
            'operations_monthly_right_pvsyst_tr3' => $operations_monthly_right_pvsyst_tr3,
            'operations_monthly_right_pvsyst_tr4' => $operations_monthly_right_pvsyst_tr4,
            'operations_monthly_right_pvsyst_tr5' => $operations_monthly_right_pvsyst_tr5,
            'operations_monthly_right_pvsyst_tr6' => $operations_monthly_right_pvsyst_tr6,
            'operations_monthly_right_pvsyst_tr7' => $operations_monthly_right_pvsyst_tr7,
            'operations_monthly_right_g4n_tr1' => $operations_monthly_right_g4n_tr1,
            'operations_monthly_right_g4n_tr2' => $operations_monthly_right_g4n_tr2,
            'operations_monthly_right_g4n_tr3' => $operations_monthly_right_g4n_tr3,
            'operations_monthly_right_g4n_tr4' => $operations_monthly_right_g4n_tr4,
            'operations_monthly_right_g4n_tr5' => $operations_monthly_right_g4n_tr5,
            'operations_monthly_right_g4n_tr6' => $operations_monthly_right_g4n_tr6,
            'operations_monthly_right_g4n_tr7' => $operations_monthly_right_g4n_tr7,
            'operations_monthly_right_iout_tr1' => $operations_monthly_right_iout_tr1,
            'operations_monthly_right_iout_tr2' => $operations_monthly_right_iout_tr2,
            'operations_monthly_right_iout_tr3' => $operations_monthly_right_iout_tr3,
            'operations_monthly_right_iout_tr4' => $operations_monthly_right_iout_tr4,
            'operations_monthly_right_iout_tr5' => $operations_monthly_right_iout_tr5,
            'operations_monthly_right_iout_tr6' => $operations_monthly_right_iout_tr6,
            'operations_monthly_right_iout_tr7' => $operations_monthly_right_iout_tr7,
            'useGridMeterDayData' => $anlage->getUseGridMeterDayData(),
            'availabilityMonthTable' => $availabilityMonthTable,
            'showAvailability' => $anlage->getShowAvailability(),
            'showAvailabilitySecond' => $anlage->getShowAvailabilitySecond(),
            'table_overview_dayly' => $table_overview_dayly,
            'plantAvailabilityCurrentYear' => $outPaCY,
            'daysInReportMonth' => $daysInReportMonth,
            'tableColsLimit' => 10,
            'acGroups' => $acGroupsCleaned,
            'Availability_Year_To_Date_Table' => $availabilityYearToDateTable,
            'availability_Year_To_Date' => $availability_Year_To_Date,
            'failures_Year_To_Date' => $failures_Year_To_Date,
            'plant_availability' => $plant_availability,
            'fails_month' => $fails_month,
            'plantAvailabilityMonth' => $outPa,
            'operations_currents_dayly_table' => $outTableCurrentsPower,
            'income_per_month' => $incomePerMonth,
            'income_per_month_chart' => $income_per_month_chart,
            'economicsMandy' => $economicsMandy,
            'total_Costs_Per_Date' => $total_Costs_Per_Date,
            'operating_statement_chart' => $operating_statement_chart,
            'economicsCumulatedForecast' => $economicsCumulatedForecast,
            'economicsCumulatedForecastChart' => $economicsCumulatedForecastChart,
            'lossesComparedTable' => $lossesComparedTable,
            'losses_compared_chart' => $losses_compared_chart,
            'lossesComparedTableCumulated' => $lossesComparedTableCumulated,
            'cumulated_losses_compared_chart' => $cumulated_losses_compared_chart,
            'ticketCountTable' => $ticketCountTable,
            'ticketCountTableMonth' => $ticketCountTableMonth,
            'kwhLossesYearTable' =>$kwhLossesYearTable,
            'kwhLossesMonthTable' =>$kwhLossesMonthTable,
            'economicsMandy2' => $economicsMandy2,
            'wkhLossesChartMonth' => $losseskwhchart,
            'kwhLossesChartYear' => $losseskwhchartyear,
            'wkhLossesTicketChartMonth' => "",
            'TicketAvailabilityMonthTable' => $TicketAvailabilityMonthTable,
            'TicketAvailabilityYearTable' => $TicketAvailabilityYearTable,
            'monthlyLossesHelpTable' => $monthlyLossesHelpTable,
            'yearLossesHelpTable' => $yearLossesHelpTable,
            'losseskwhchartYearMonthly' => $losseskwhchartYearMonthly,
            'PercentageTableYear' => $percentageTableYear,
            'percentageTableMonth' => $percentageTable,
            'monthlyTableForPRAndPA' => $monthlyTableForPRAndPA,
            'PA_MonthlyGraphic' => $PA_MonthlyGraphic,
            'PR_MonthlyGraphic' => $PR_MonthlyGraphic,
            'InverterPRRankTables' => $orderedArray,
            'InverterPRRankGraphics' => $pr_rank_graph,
            'efficiencyRanking' => $efficiencyRanking,
            'prSumaryTable' => $prSumaryTable,
            'sumary_pie_graph' => $sumary_pie_graph,
            'waterfallHelpTable' => $waterfallDiagramHelpTable,
            'waterfallDiagram' => $waterfallDiagram
        ];

        return $output;
    }

    /**
     * @param $begin
     * @param $end
     * @param $anlage
     */
    #[ArrayShape(['SORLosses' => "int|mixed", 'EFORLosses' => "int|mixed", 'OMCLosses' => "int|mixed", 'ExpectedLosses' => "int|mixed", 'PPCLosses' => "int|mixed"])]
    public function calculateLosses($begin, $end, $anlage) :Array
    {
        $sumLossesMonthSOR = 0;
        $sumLossesMonthEFOR = 0;
        $sumLossesMonthOMC = 0;
        $sumLossesMonthExpected = 0;
        $sumLossesMonthPPC = 0;
        $sumDataGap = 0;

        foreach ($this->ticketDateRepo->getAllByInterval($begin, $end, $anlage) as $date){
            $intervalBegin = date("Y-m-d H:i",$date->getBegin()->getTimestamp());
            $intervalEnd = date("Y-m-d H:i",$date->getEnd()->getTimestamp());
            foreach($date->getInverterArray() as $inverter) {
                if($inverter != "*") {
                    switch ($anlage->getConfigType()) { // we need this to query for the inverter in the SOR and EFOR cases, in the OMC case the whole plant is down
                        case 1 :
                            $inverterQuery = " AND group_dc = '$inverter'";
                            break;
                        default:
                            $inverterQuery = " AND group_ac = '$inverter'";
                    }
                }
                else $inverterQuery = "";
                if ($date->getAlertType() == 10 ) {

                        $sqlExpected = "SELECT sum(ac_exp_power) as expected
                                FROM " . $anlage->getDbNameDcSoll() . "                      
                                WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'  $inverterQuery";
                        $resExp = $this->conn->query($sqlExpected);

                        if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                        else $exp = 0;


                    if ($date->getDataGapEvaluation() == 10) {
                        $sumLossesMonthEFOR = $sumLossesMonthEFOR + $exp;
                    }
                    else{
                        $sumDataGap = $sumDataGap + $exp;
                    }

                    //$sumLossesMonthSOR = $sumLossesMonthSOR + $exp;
                } else if ($date->getAlertType() == 20) {
                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'  $inverterQuery";
                    $resExp = $this->conn->query($sqlExpected);

                    if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                    else $exp = 0;

                    $sumLossesMonthEFOR = $sumLossesMonthEFOR + $exp;
                } else if ($date->getAlertType() == 30) {
                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'";
                    $resExp = $this->conn->query($sqlExpected);

                    if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                    else $exp = 0;
                    $sumLossesMonthOMC = $sumLossesMonthEFOR + $exp;
                } else if ($date->getAlertType() == 60) {
                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'";
                    $resExp = $this->conn->query($sqlExpected);

                    if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                    else $exp = 0;
                    $sumLossesMonthExpected = $sumLossesMonthExpected + $exp;
                } else if ($date->getAlertType() == 50) {
                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'";
                    $resExp = $this->conn->query($sqlExpected);

                    if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                    else $exp = 0;
                    $sumLossesMonthPPC = $sumLossesMonthPPC + $exp;
                    $sumLossesMonthOMC = $sumLossesMonthEFOR + $exp;
                }

            }
        }


        $kwhLossesMonthTable = [
            'SORLosses'      => $sumLossesMonthSOR,
            'EFORLosses'     => $sumLossesMonthEFOR,
            'OMCLosses'      => $sumLossesMonthOMC,
            'DataGapLosses'  => $sumDataGap,
            'ExpectedLosses' => $sumLossesMonthExpected,
            'PPCLosses'      => $sumLossesMonthPPC,
        ];
        return $kwhLossesMonthTable;
    }

    /**
     * @param Anlage $anlage
     * @param $month
     * @param $year
     * @return Array
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    private function calcPRInvArray(Anlage $anlage, $month, $year):Array {
        // now we will cheat the data in but in the future we will use the params to retrieve the data
        $PRArray = []; // this is the array that we will return at the end with the inv name, power sum (kWh), pnom (kWp), power (kWh/kWp), avg power, avg irr, theo power, Inverter PR, calculated PR
        $invArray = $anlage->getInverterFromAnlage();
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);
        $invPnomArray = $anlage->getPnomInverterArray();
        $invNr = count($invArray);
        $contractualPR = $anlage->getContractualPR();
        $powerSum = 0;
        $prSum = 0;
        for ($index = 1; $index <= $invNr; $index++) {
            $prValues = $this->PRCalulation->calcPRByInverterAM($anlage, $index, new \DateTime($year."-".$month."-"."01"), new \DateTime($year."-".$month."-".$daysInMonth));
            $invPnom = $invPnomArray[$index];
            $power = $prValues['powerAct'];
            $PRArray['name'][] = $invArray[$index];
            $PRArray['power'][] = $power;
            $PRArray['Pnom'][] = $invPnom;
            $PRArray['powerYield'][] = $power / $invPnom;
            $PRArray['avgIrr'][] = $prValues['irradiation'];
            $PRArray['theoPower'][] = $prValues['powerTheo'];
            $PRArray['invPR'][] = $prValues['prDep3Act'];
            $PRArray['calcPR'][] = $contractualPR;
            $powerSum = $powerSum + $power;
            $prSum = $prSum + $prValues['prDep3Act'];
        }
        $PRArray['powerAVG'] = $powerSum / $invNr;
        $PRArray['PRAvg'] = $prSum / $invNr;
        return $PRArray;
    }

    /**
     * @param Anlage $anlage
     * @param $month
     * @param $year
     * @return array
     */
    private function calcPRInvArrayDayly(Anlage $anlage, $month, $year){
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);
        $begin = $year."-".$month."-01 00:00";
        $end = $year."-".$month."-".$daysInMonth." 23:59";
        $sql = 'SELECT stamp, (sum(wr_pac)/sum(wr_pdc) * 100) as efficiency, unit AS inverter  FROM '.$anlage->getDbNameIst()." WHERE stamp BETWEEN '$begin' AND '$end' GROUP BY UNIT, date_format(stamp, '%y%m%d')";
        $res = $this->conn->query($sql);
        $inverter = 1;
        $index = 1;
        $efficiencySum = 0;
        $efficiencyCount = 0;
        foreach($res->fetchAll(PDO::FETCH_ASSOC) as $result){
            if ($result['inverter'] != $inverter){
                $output['avg'][$inverter] = $efficiencyCount > 0 ? round($efficiencySum / $efficiencyCount, 2) : 0;
                $inverter = $result['inverter'];
                $index = 1;
                $efficiencySum = 0;
                $efficiencyCount = 0;
            }
            if ($result['efficiency'] <= 100 and $result['efficiency'] >= 0) {
                $output['values'][$inverter][] = round($result['efficiency'], 2);
                $efficiencyCount = $efficiencyCount + 1;
                $efficiencySum = $efficiencySum + $result['efficiency'];
                $index = $index + 1;
            }
        }
        $output['avg'][$inverter] = $efficiencyCount > 0 ? round($efficiencySum / $efficiencyCount, 2) : 0; //we make the last average outside of the loop
        return $output;
    }

    /**
     * @param Anlage $anlage
     * @param $month
     * @return float|int|mixed
     */
    private function getForecastIrr(Anlage $anlage, $month){
        $forecast = $this->forecastDayRepo->findForcastDayByMonth($anlage, $month);
        $sumIrrMonth = 0;

        foreach($forecast as $data){
            if ($data->getIrrday() > 0) $sumIrrMonth = $sumIrrMonth + $data->getIrrday()/1000;
        }
        return $sumIrrMonth;
    }

}