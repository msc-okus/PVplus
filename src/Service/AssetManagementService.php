<?php


namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenPvSystMonth;
use App\Entity\AnlagePR;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Repository\Case5Repository;
use App\Repository\PvSystMonthRepository;
use App\Repository\ReportsRepository;
use App\Repository\AnlagenRepository;
use App\Repository\PRRepository;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use App\Reports\ReportMonthly\ReportMonthly;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Constraints\DateTime;

use Hisune\EchartsPHP\ECharts;
use Hisune\EchartsPHP\Doc\IDE\Series;
use Hisune\EchartsPHP\Config;
use Nuzkito\ChromePdf\ChromePdf;
use PDOStatement;
use PDO;
use PDOException;
use PDORow;


class AssetManagementService
{
    use G4NTrait;

    private AnlagenRepository $anlagenRepository;
    private PRRepository $PRRepository;
    private Environment $twig;
    private ReportsRepository $reportsRepository;
    private EntityManagerInterface $em;
    private MessageService $messageService;
    private PvSystMonthRepository $pvSystMonthRepo;
    private Case5Repository $case5Repo;
    private FunctionsService $functions;
    private NormalizerInterface $serializer;

    public function __construct(
        AnlagenRepository      $anlagenRepository,
        PRRepository           $PRRepository,
        ReportsRepository      $reportsRepository,
        EntityManagerInterface $em,
        Environment            $twig,
        MessageService         $messageService,
        PvSystMonthRepository  $pvSystMonthRepo,
        Case5Repository        $case5Repo,
        FunctionsService       $functions,
        NormalizerInterface    $serializer)
    {

        $this->anlagenRepository = $anlagenRepository;
        $this->PRRepository = $PRRepository;
        $this->twig = $twig;
        $this->reportsRepository = $reportsRepository;
        $this->functions = $functions;
        $this->em = $em;
        $this->messageService = $messageService;
        $this->pvSystMonthRepo = $pvSystMonthRepo;
        $this->case5Repo = $case5Repo;
        $this->serializer = $serializer;
    }

    public function assetReport($anlage, $month = 0, $year = 0, $inverter = 1, $docType = 0, $chartTypeToExport = 0, $storeDocument = true): array
    {
        if ($month != 0 && $year != 0) {
            $yesterday = strtotime("$year-$month-01");
        } else {
            $currentTime = G4NTrait::getCetTime();
            $yesterday = $currentTime - 86400 * 4;
        }

        $reportMonth = date('m', $yesterday);
        $reportYear = date('Y', $yesterday);
        $lastDayMonth = date('t', $yesterday);
        $from = "$reportYear-$reportMonth-01 00:00";
        $to = "$reportYear-$reportMonth-$lastDayMonth 23:59";

        $output = '';

        $report = [];
        $report['yesterday'] = $yesterday;
        $report['reportMonth'] = $reportMonth;
        $report['from'] = $from;
        $report['to'] = $to;
        $report['reportYear'] = $reportYear;
        $report['anlage'] = $anlage[0];
        $report['inverter'] = $inverter;
        $report['prs'] = $this->PRRepository->findPRInMonth($report['anlage'], $reportMonth, $reportYear);
        $report['lastPR'] = $this->PRRepository->findOneBy(['anlage' => $report['anlage'], 'stamp' => date_create("$year-$month-$lastDayMonth")]);
        $report['case5s'] = $this->case5Repo->findAllAnlageDay($report['anlage'], $from);
        $report['pvSyst'] = $this->getPvSystMonthData($report['anlage'], $month, $year);
        $useGridMeterDayData = $report['anlage']->getUseGridMeterDayData();
        $showAvailability = $report['anlage']->getAnlId();
        $showAvailabilitySecond = $report['anlage']->getShowAvailabilitySecond();
        $usePac = $report['anlage']->getUsePac();

        $countCase5 = 0;

        $output = $this->buildAssetReport($report['anlage'], $report, $docType, $chartTypeToExport);

        if ($storeDocument) {
            // Store to Database
            $reportEntity = new AnlagenReports();
            $startDate = new \DateTime("$reportYear-$reportMonth-01");
            $endDate = new \DateTime($startDate->format("Y-m-t"));

            $reportEntity
                ->setCreatedAt(new \DateTime())
                ->setAnlage($report['anlage'])
                ->setEigner($report['anlage']->getEigner())
                ->setReportType('monthly-report')
                ->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setMonth($startDate->format('m'))
                ->setYear($startDate->format('Y'))
                ->setRawReport($output)
                ->setContentArray($report);
            $this->em->persist($reportEntity);
            $this->em->flush();
        }

        return $output;

    }

    private function getPvSystMonthData(Anlage $anlage, $month, $year): array
    {
        $pvSystMonth = $this->pvSystMonthRepo->findOneBy(['anlage' => $anlage, 'month' => (int)$month]);
        if ($pvSystMonth) {
            $prPvSystMonth = $pvSystMonth->getPrDesign();
            $powerPvSyst = $pvSystMonth->getErtragDesign();
        } else {
            $prPvSystMonth = 0;
            $powerPvSyst = 0;
        }
        /** @var AnlagenPvSystMonth[] $pvSystYear */
        $pvSystYear = $this->pvSystMonthRepo->findAllYear($anlage, (int)$month);
        $powerPac = 0;
        $powerYear = 0;

        foreach ($pvSystYear as $pvSystYearValue) {
            $powerYear += $pvSystYearValue->getErtragDesign();
        }
        /** @var AnlagenPvSystMonth[] $pvSystPac */
        $pvSystPac = $this->pvSystMonthRepo->findAllPac($anlage, (int)$month);
        $anzRecordspvSystPac = count($pvSystPac);
        foreach ($pvSystPac as $pvSystPacValue) {
            // wenn Anzahl Monate kleiner 12 dann muss der erste Moanat nur anteilig berechnet werden
            // wenn 12 oder mehr dann kann der ganze Moant addiert werden
            // und das nur beim ersten PAC Monat
            if ((int)$anlage->getPacDate()->format('m') == $pvSystPacValue->getMonth() && $anzRecordspvSystPac < 12) {
                $dayPac = (int)$anlage->getPacDate()->format('d');
                $daysInMonthPac = (int)$anlage->getPacDate()->format('t');
                $days = $daysInMonthPac - $dayPac + 1;
                $powerPac += $pvSystPacValue->getErtragDesign() / $daysInMonthPac * $days;
            } else {
                $powerPac += $pvSystPacValue->getErtragDesign();
            }
        }

        $resultArray = [
            'prMonth' => $prPvSystMonth,
            'prPac' => $anlage->getDesignPR(),
            'prYear' => $anlage->getDesignPR(),
            'powerMonth' => $powerPvSyst,
            'powerPac' => $powerPac,
            'powerYear' => $powerYear
        ];

        return $resultArray;
    }

    /**
     * @param Anlage $anlage
     * @param array $report
     * @param int $docType ( 0 = PDF, 1 = Excel, 2 = PNG (Grafiken))
     * @param int $chartTypeToExport ( 0 = , 1 = )
     * @param bool $exit
     * @return array
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function buildAssetReport(Anlage $anlage, array $report, int $docType = 0, int $chartTypeToExport = 0, $exit = false): array
    {

        $conn = self::getPdoConnection();
        $connAnlage = self::connectToDatabaseAnlage();
        $useGridMeterDayData = $anlage->getUseGridMeterDayData();
        $showAvailability = $anlage->getShowAvailability();
        $showAvailabilitySecond = $anlage->getShowAvailabilitySecond();
        $usePac = $anlage->getUsePac();
        $plantSize = $anlage->getPower();
        $owner = $anlage->getEigner()->getFirma();

        $monthName = date("F", mktime(0, 0, 0, $report['reportMonth'], 10));
        $currentYear = date("Y");
        $currentMonth = date("m");
        $currentDay = date("d");

        if ($report['reportMonth'] < 10) {
            $report['reportMonth'] = str_replace(0, '', $report['reportMonth']);
        }

        $daysInReportMonth = cal_days_in_month(CAL_GREGORIAN, $report['reportMonth'], $report['reportYear']);

        $monthArray = [
            'Jan', 'Feb', 'Mar', 'April', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dez'
        ];
        for ($i = 0; $i < count($monthArray); $i++) {
            $monthExtendetArray[$i]['month'] =  $monthArray[$i];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $i+1, $report['reportYear']);
            $monthExtendetArray[$i]['days'] =  $daysInMonth;
            $monthExtendetArray[$i]['hours'] =  $daysInMonth*24;
        }

        //die DC Geuppen ermitteln
        $acGroups = $anlage->getAcGroups()->toArray();

        //zum Erzeugen einer Monatsbezogenen Tagesachse
        $start_date = strtotime($report['from']);
        $end_date = strtotime($report['to']);
        $dateDiff = $end_date - $start_date;

        $range = (int)round($dateDiff / (60 * 60 * 24), 0);

        while ($l <= $range) {
            $array_yaxis[] = $l;
            $l++;
        }

        //Begrenzung der Spaltenanzahl einer Tabelle
        $tableColsLimit = 10;

        //Beginn Operations year
        $anlId = $anlage->getAnlId();
        $powerEvuYearToDate = 0;
        $expectedPvSystYearToDate = 0;


        for ($i = 1; $i < 13; $i++) {
            if ($i < 10) {
                $month_transfer = "0$i";
            } else {
                $month_transfer = $i;
            }

            $start = $report['reportYear'] . '-' . $month_transfer . '-01 00:00';
            $end = $report['reportYear'] . '-' . $month_transfer . '-' . $daysInReportMonth . ' 23:59';

            $data1_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
            $powerEvu[] = str_replace('.',',',$data1_grid_meter['powerEvu']);
            $powerAct[] = $data1_grid_meter['powerAct'];//Inv out
            $powerExp[] = $data1_grid_meter['powerExp'];
            $powerExpEvu[] = $data1_grid_meter['powerExpEvu'];


            $powerEvuYearToDate = round($powerEvuYearToDate + $data1_grid_meter['powerEvu'], 2);
            $pvSyst = $this->pvSystMonthRepo->findOneMonth($anlage, $i);


            if (count($pvSyst) > 0) $expectedPvSystDb = $pvSyst->getErtragDesign();

            $dataMonthArray[] = $monthArray[$i - 1];


            if ($data1_grid_meter['powerEvu'] == 0) {
                $expectedPvSystDb = 0;
            }
            $expectedPvSyst[] = $expectedPvSystDb;

            $expectedPvSystYearToDate = $expectedPvSystYearToDate + $expectedPvSystDb;
            unset($pvSyst);
            if ($report['reportMonth'] == $i && $report['reportYear'] == $currentYear) {
                $i = 13;
            }
        }

        #fuer die Tabelle
        $tbody_a_production = [
            'powerEvu' => $powerEvu,
            'powerAct' => $powerAct,
            'powerExp' => $powerExp,
            'expectedPvSyst' => $expectedPvSyst,
            'powerExpEvu' => $powerExpEvu
        ];

        //fuer die Tabelle Capacity Factor
        for ($i = 0; $i < count($monthExtendetArray); $i++) {
            $dataCfArray[$i]['month'] =  $monthExtendetArray[$i]['month'];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $i+1, $report['reportYear']);
            $dataCfArray[$i]['days'] =  $daysInMonth;
            $dataCfArray[$i]['hours'] =  $daysInMonth*24;
            $dataCfArray[$i]['cf'] =  round(($tbody_a_production['powerEvu'][$i]/1000)/(($plantSize/1000)*($daysInMonth*24))*100,3);
        }

        $chart = new ECharts();
        $chart->tooltip->show = true;

                $chart->tooltip->trigger = 'item';

                $chart->xAxis = array(
                    'type' => 'category',
                    'axisLabel' => array(
                        'show' => true,
                        'margin' => '10',
                    ),
                    'splitArea' => array(
                        'show' => true,
                    ),
                    'data' => $dataMonthArray
                );
                $chart->yAxis = array(
                    'type' => 'value',
                );
                $chart->series =
                    [
                        [
                            'name' => 'Yield (Grid meter)',
                            'type' => 'bar',
                            'data' => $powerEvu,
                            'visualMap' => 'false'
                        ],
                        [
                            'name' => 'Expected PV SYST',
                            'type' => 'bar',
                            'data' => $expectedPvSyst,
                            'visualMap' => 'false'
                        ],
                        [
                            'name' => 'Expected g4n',
                            'type' => 'bar',
                            'data' => $powerExp,
                            'visualMap' => 'false'
                        ],
                        [
                            'name' => 'Inverter out',
                            'type' => 'bar',
                            'data' => $powerAct,
                            'visualMap' => 'false'
                        ]
                    ];

                $option = array(
                    'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000', '#000ffc'],
                    'title' => [
                        'text' => 'Year ' . $report['reportYear'],
                        'left' => 'center',
                    ],
                    'tooltip' =>
                        [
                            'show' => true,
                        ],
                    'legend' =>
                        [
                            'show' => true,
                            'left' => 'center',
                            'top' => 20
                        ],
                    'grid' =>
                        array(
                            'height' => '80%',
                            'top' => 50,
                            'width' => '90%',
                        ),
                );

                Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
                $chart->setOption($option);

                $operations_right = $chart->render('operations_right', ['style' => 'height: 400px; width:700px;']);
                $chart->tooltip = [];
                $chart->xAxis = [];
                $chart->yAxis = [];
                $chart->series = [];
                unset($option);

                //End Operations year

        $conn = null;

        $output = [
            'owner' => $owner,
            'plantSize' => $plantSize,
            'month' => $monthName,
            'reportmonth' => $report['reportMonth'],
            'year' => $report['reportYear'],
            'montharray' => $monthArray,
            'dataMonthArray' => $dataMonthArray,
            'dataCfArray' => $dataCfArray,
            'operations_right' => $operations_right,
            'table_overview_monthly' => $tbody_a_production,
        ];

        return $output;

    }
}