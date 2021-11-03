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
use Symfony\Component\Mailer\MailerInterface;
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
    private MailerInterface $mailer;
    private MessageService $messageService;
    private PvSystMonthRepository $pvSystMonthRepo;
    private Case5Repository $case5Repo;
    private FunctionsService $functions;
    private NormalizerInterface $serializer;

    public function __construct(
        AnlagenRepository $anlagenRepository,
        PRRepository $PRRepository,
        ReportsRepository $reportsRepository,
        EntityManagerInterface $em,
        Environment $twig,
        MailerInterface $mailer,
        MessageService $messageService,
        PvSystMonthRepository $pvSystMonthRepo,
        Case5Repository $case5Repo,
        FunctionsService $functions,
        NormalizerInterface $serializer)
    {

        $this->anlagenRepository = $anlagenRepository;
        $this->PRRepository = $PRRepository;
        $this->twig = $twig;
        $this->reportsRepository = $reportsRepository;
        $this->functions = $functions;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->messageService = $messageService;
        $this->pvSystMonthRepo = $pvSystMonthRepo;
        $this->case5Repo = $case5Repo;
        $this->serializer = $serializer;
    }

    public function assetReport($anlagen, $month = 0, $year = 0, $inverter = 1, $docType = 0, $chartTypeToExport = 0, $storeDocument = true): array
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

        /** @var Anlage $anlage */
        foreach ($anlagen as $anlage) {
            $report = [];
            $report['yesterday'] = $yesterday;
            $report['reportMonth'] = $reportMonth;
            $report['from'] = $from;
            $report['to'] = $to;
            $report['reportYear'] = $reportYear;
            $report['anlage'] = $anlage;
            $report['inverter'] = $inverter;
            $report['prs'] = $this->PRRepository->findPRInMonth($anlage, $reportMonth, $reportYear);
            $report['lastPR'] = $this->PRRepository->findOneBy(['anlage' => $anlage, 'stamp' => date_create("$year-$month-$lastDayMonth")]);
            $report['case5s'] = $this->case5Repo->findAllAnlageDay($anlage, $from);
            $report['pvSyst'] = $this->getPvSystMonthData($anlage, $month, $year);
            $useGridMeterDayData = $anlage->getUseGridMeterDayData();
            $showAvailability = $anlage->getAnlId();
            $showAvailabilitySecond = $anlage->getShowAvailabilitySecond();
            $usePac = $anlage->getUsePac();
            $countCase5 = 0;

            $output = $this->buildAssetReport($anlage, $report, $docType, $chartTypeToExport);

            if ($storeDocument) {
                // Store to Database
                $reportEntity = new AnlagenReports();
                $startDate = new \DateTime("$reportYear-$reportMonth-01");
                $endDate = new \DateTime($startDate->format("Y-m-t"));

                $reportEntity
                    ->setCreatedAt(new \DateTime())
                    ->setAnlage($anlage)
                    ->setEigner($anlage->getEigner())
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

            $powerEvu[] = round($data1_grid_meter['powerEvu'],0);
            $powerAct[] = round($data1_grid_meter['powerAct'],0);//Inv out
            $powerExp[] = round($data1_grid_meter['powerExp'],0);

            $powerEvuYearToDate = round($powerEvuYearToDate + $data1_grid_meter['powerEvu'],2);
            $pvSyst = $this->pvSystMonthRepo->findOneMonth($anlage, $i);


            if(count($pvSyst) > 0) $expectedPvSystDb = $pvSyst->getErtragDesign();

            $dataMmonthArray[] = $monthArray[$i - 1];


            if ($data1_grid_meter['powerEvu'] == 0) {
                $expectedPvSystDb = 0;
            }
            $expectedPvSyst[] = $expectedPvSystDb;

            $expectedPvSystYearToDate = $expectedPvSystYearToDate + $expectedPvSystDb;
            unset($pvSyst);
            if ($currentMonth-1 == $i && $report['reportYear'] == $currentYear) {
                $i = 13;
            }
        }

        #fuer die Tabelle
        $tbody_a_production = [
            'powerEvu' => $powerEvu,
            'powerAct' => $powerAct,
            'powerExp' => $powerExp,
            'expectedPvSyst' => $expectedPvSyst,
        ];


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
            'data' => $dataMmonthArray
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

        $operations_right = $chart->render('operations_right', ['style' => 'height: 400px; width:1300px;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        $operations_freetext_one = '';
        //End Operations year

        //Beginn Operations month

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
            'data' => array()
        );
        $chart->yAxis = array(
            'type' => 'value',
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 90
        );

        $chart->series =
            [
                [
                    'name' => 'Yield (Grid meter)',
                    'type' => 'bar',
                    'data' => [
                        $powerEvu[$report['reportMonth'] - 1]
                    ],
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Expected PV SYST',
                    'type' => 'bar',
                    'data' => [
                        $expectedPvSyst[$report['reportMonth'] - 1]
                    ],
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Expected g4n',
                    'type' => 'bar',
                    'data' => [
                        $powerExp[$report['reportMonth'] - 1]
                    ],
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Inverter out',
                    'type' => 'bar',
                    'data' => [
                        $powerAct[$report['reportMonth'] - 1]
                    ],
                    'visualMap' => 'false'
                ]
            ];

        $option = array(
            'color' => ['#698ed0', '#f1975a', '#c5e0b4', '#ffc000'],
            'title' => [
                'text' => $monthName . ' ' . $report['reportYear'],
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
                    'width' => '100%',
                ),
        );

        Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
        $chart->setOption($option);
        $operations_monthly_left = $chart->render('operations_left', ['style' => 'height: 400px; width:1050px;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        //Tabelle rechts oben
        $operations_monthly_right_tupper_tr1 = [
            $monthName . ' ' . $report['reportYear'],
            $powerEvu[$report['reportMonth'] - 1],
            $expectedPvSyst[$report['reportMonth'] - 1],
            abs($powerEvu[$report['reportMonth'] - 1] - $expectedPvSyst[$report['reportMonth'] - 1]),
            round((1 - $expectedPvSyst[$report['reportMonth'] - 1] / $powerEvu[$report['reportMonth'] - 1]) * 100, 2)
        ];

        //Parameter fuer die Berechnung Q1
        $start = $report['reportYear'] . '-01-01 00:00';
        $end = $report['reportYear'] . '-03-31 23:59';
        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        $powerEvuQ1 = $data2_grid_meter['powerEvu'];
        if ((($currentYear == $report['reportYear'] && $currentMonth > 3) || $currentYear > $report['reportYear']) && $powerEvuQ1 > 0) {
            $sql = "SELECT sum(ertrag_design) as q1 FROM anlagen_pv_syst_month WHERE anlage_id = " . $anlId . " AND month <= 3";
            $resultErtrag_design = $connAnlage->query($sql);
            if ($resultErtrag_design) {
                if ($resultErtrag_design->num_rows == 1) {
                    $rowQ1Ertrag_design = $resultErtrag_design->fetch_assoc();
                    $expectedPvSystQ1 = $rowQ1Ertrag_design['q1'];
                }
            }

            $operations_monthly_right_tupper_tr2 = [
                $powerEvuQ1,
                $expectedPvSystQ1,
                abs($powerEvuQ1 - $expectedPvSystQ1),
                round((1 - $expectedPvSystQ1 / $powerEvuQ1) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tupper_tr2 = [
                '0',
                '0',
                '0',
                '0'
            ];
        }

        //Parameter fuer die Berechnung Q2
        $start = $report['reportYear'] . '-04-01 00:00';
        $end = $report['reportYear'] . '-06-30 23:59';
        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        $powerEvuQ2 = $data2_grid_meter['powerEvu'];
        if ((($currentYear == $report['reportYear'] && $currentMonth > 6) || $currentYear > $report['reportYear']) && $powerEvuQ2 > 0) {
            $sql = "SELECT sum(ertrag_design) as q2 FROM anlagen_pv_syst_month WHERE anlage_id = " . $anlId . " AND month >= 4 AND month <= 6";
            $resultErtrag_design = $connAnlage->query($sql);
            if ($resultErtrag_design) {
                if ($resultErtrag_design->num_rows == 1) {
                    $rowQ2Ertrag_design = $resultErtrag_design->fetch_assoc();
                    $expectedPvSystQ2 = $rowQ2Ertrag_design['q2'];
                }
            }

            $operations_monthly_right_tupper_tr3 = [
                $powerEvuQ2,
                $expectedPvSystQ2,
                abs($powerEvuQ2 - $expectedPvSystQ2),
                round((1 - $expectedPvSystQ2 / $powerEvuQ2) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tupper_tr3 = [
                '0',
                '0',
                '0',
                '0'
            ];
        }
        //Parameter fuer die Berechnung Q3
        $start = $report['reportYear'] . '-07-01 00:00';
        $end = $report['reportYear'] . '-09-30 23:59';
        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        $powerEvuQ3 = $data2_grid_meter['powerEvu'];
        if ((($currentYear == $report['reportYear'] && $currentMonth > 9) || $currentYear > $report['reportYear']) && $powerEvuQ3 > 0) {
            $sql = "SELECT sum(ertrag_design) as q3 FROM anlagen_pv_syst_month WHERE anlage_id = " . $anlId . " AND month >= 7 AND month <= 9";
            $resultErtrag_design = $connAnlage->query($sql);
            if ($resultErtrag_design) {
                if ($resultErtrag_design->num_rows == 1) {
                    $rowQ3Ertrag_design = $resultErtrag_design->fetch_assoc();
                    $expectedPvSystQ3 = $rowQ3Ertrag_design['q3'];
                }
            }

            $operations_monthly_right_tupper_tr4 = [
                $powerEvuQ3,
                $expectedPvSystQ3,
                abs($powerEvuQ3 - $expectedPvSystQ3),
                round((1 - $expectedPvSystQ3 / $powerEvuQ3) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tupper_tr4 = [
                '0',
                '0',
                '0',
                '0'
            ];
        }

        //Parameter fuer die Berechnung Q4
        $start = $report['reportYear'] . '-10-01 00:00';
        $end = $report['reportYear'] . '-12-31 23:59';
        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        $powerEvuQ4 = $data2_grid_meter['powerEvu'];
        if ($currentYear > $report['reportYear'] && $powerEvuQ4 > 0) {
            $sql = "SELECT sum(ertrag_design) as q4 FROM anlagen_pv_syst_month WHERE anlage_id = " . $anlId . " AND month >= 10 AND month <= 12";
            $resultErtrag_design = $connAnlage->query($sql);
            if ($resultErtrag_design) {
                if ($resultErtrag_design->num_rows == 1) {
                    $rowQ4Ertrag_design = $resultErtrag_design->fetch_assoc();
                    $expectedPvSystQ4 = $rowQ4Ertrag_design['q4'];
                }
            }

            $operations_monthly_right_tupper_tr5 = [
                $powerEvuQ4,
                $expectedPvSystQ4,
                abs($powerEvuQ4 - $expectedPvSystQ4),
                round((1 - $expectedPvSystQ4 / $powerEvuQ4) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tupper_tr5 = [
                '0',
                '0',
                '0',
                '0'
            ];
        }


        //Year to date
        //Parameter fuer die Berechnung YtD
        $timestamp = $anlage->getPacDate()->getTimestamp();
        $dayPacDate = $anlage->getPacDate()->format('d');
        $monthPacDate = $anlage->getPacDate()->format('m');
        $yearPacDate = $anlage->getPacDate()->format('Y');

        $start = $report['reportYear'] . '-01-01 00:00';
        $end = $report['reportYear'] . '-' . $report['reportMonth'] . '-' . $daysInReportMonth . ' 23:59';
        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        $powerEvuYtoD = $data2_grid_meter['powerEvu'];

        if ($powerEvuYtoD > 0 && !($yearPacDate == $report['reportYear'] && $monthPacDate > $report['reportMonth'])) {
            //Part 1 Year to Date
            $sqlMonthselection = '';
            if ($yearPacDate == $report['reportYear']) {
                $sqlMonthselection = ' and month >= ' . $monthPacDate;
            }
            $sql = "SELECT sum(ertrag_design) as ytd FROM anlagen_pv_syst_month WHERE anlage_id = " . $anlId . $sqlMonthselection . " and month <= " . $report['reportMonth'];
            $resultErtrag_design = $connAnlage->query($sql);
            if ($resultErtrag_design) {
                if ($resultErtrag_design->num_rows == 1) {
                    $rowYtoDErtrag_design = $resultErtrag_design->fetch_assoc();
                    $expectedPvSystYtoDFirst = $rowYtoDErtrag_design['ytd'];
                }
            }

            //Part 2 Year to Date
            $sql = "SELECT ertrag_design FROM anlagen_pv_syst_month WHERE anlage_id = " . $anlId . " AND month = " . $report['reportMonth'];

            $resultErtrag_design = $connAnlage->query($sql);
            if ($resultErtrag_design) {
                if ($resultErtrag_design->num_rows == 1) {
                    $ytdErtrag_design = $resultErtrag_design->fetch_assoc();
                    $expectedPvSystYtoDSecond = ($ytdErtrag_design['ertrag_design'] / cal_days_in_month(CAL_GREGORIAN, ($report['reportMonth']), $currentYear)) * $daysInReportMonth;
                }
            }

            /*echo $sql.'<br>';
            echo $ytdErtrag_design['ertrag_design'].'<br>';
            echo cal_days_in_month(CAL_GREGORIAN, ($currentMonth), $currentYear).'<br>';
            echo $expectedPvSystYtoDFirst.'<br>';
            echo $expectedPvSystYtoDSecond.'<br>';
            $expectedPvSystYtoD = $expectedPvSystYtoDFirst + $expectedPvSystYtoDSecond;
            echo $expectedPvSystYtoD.'<br>';
            exit;*/
            $expectedPvSystYtoD = $expectedPvSystYtoDFirst;

            $operations_monthly_right_tupper_tr6 = [
                $powerEvuYtoD,
                round($expectedPvSystYtoD, 0),
                round(abs($powerEvuYtoD - $expectedPvSystYtoD), 0),
                round((1 - $expectedPvSystYtoD / $powerEvuYtoD) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tupper_tr6 = [
                '0',
                '0',
                '0',
                '0'
            ];
        }

        //Gesamte Laufzeit
        $start = $anlage->getPacDate()->format('Y-m-d') . ' 00:00';
        $end = $currentYear . '-' . $report['reportMonth'] . '-' . $daysInReportMonth . ' 23:59';

        //first Part(remaining days from month PackDate) for all Exp PV Syst
        $daysInMonthPacDate = cal_days_in_month(CAL_GREGORIAN, $monthPacDate, $yearPacDate);

        $daysRemainingInMonthPacDate = $daysInMonthPacDate - ($dayPacDate-1);

        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        $powerEvuGl = $data2_grid_meter['powerEvu'];

        $sql = "SELECT ertrag_design FROM anlagen_pv_syst_month WHERE anlage_id = " . $anlId . " AND month = " . str_replace(0, '', $monthPacDate);

        $resultErtrag_design = $connAnlage->query($sql);
        if ($resultErtrag_design) {
            if ($resultErtrag_design->num_rows == 1) {
                $allErtrag_design = $resultErtrag_design->fetch_assoc();
                $allExpectedOneFirst = ($allErtrag_design['ertrag_design'] / $daysInMonthPacDate) * $daysRemainingInMonthPacDate;
            }
        }

        //second Part(remaining months from year PackDate) for all Exp PV Syst
        $sql = "SELECT sum(ertrag_design) as allinsecond FROM anlagen_pv_syst_month WHERE anlage_id = " . $anlId . " AND month > " . str_replace(0, '', $monthPacDate);
        $resultErtrag_design = $connAnlage->query($sql);
        if ($resultErtrag_design) {
            if ($resultErtrag_design->num_rows == 1) {
                $allErtrag_design = $resultErtrag_design->fetch_assoc();
                $allExpectedOneSecond = $allErtrag_design['allinsecond'];
            }
        }

        //third Part(years between Year PackDate and Year now) for all Exp PV Syst
        $sql = "SELECT sum(ertrag_design) as allinthird FROM anlagen_pv_syst_month WHERE anlage_id = " . $anlId;
        $resultErtrag_design = $connAnlage->query($sql);
        if ($resultErtrag_design) {
            if ($resultErtrag_design->num_rows == 1) {
                $allErtrag_design = $resultErtrag_design->fetch_assoc();
                $allExpectedOneThirdTemp = $allErtrag_design['allinthird'];
            }
        }
        $allExpectedOneThird = $allExpectedOneThirdTemp * ($currentYear - 1 - $yearPacDate);

        //fourtht Part(months from current year bevor current month) for all Exp PV Syst
        $sql = "SELECT sum(ertrag_design) as allinfourtht FROM anlagen_pv_syst_month WHERE anlage_id = " . $anlId . " AND month < " . str_replace(0, '', $currentMonth);
        $resultErtrag_design = $connAnlage->query($sql);
        if ($resultErtrag_design) {
            if ($resultErtrag_design->num_rows == 1) {
                $allErtrag_design = $resultErtrag_design->fetch_assoc();
                $allExpectedOneFourtht = $allErtrag_design['allinfourtht'];
            }
        }

        $allExpected = $allExpectedOneFirst + $allExpectedOneSecond + $allExpectedOneThird + $allExpectedOneFourtht;

        $operations_monthly_right_tupper_tr7 = [
            $powerEvuGl,
            round($allExpected, 0),
            round(abs($powerEvuGl - $allExpected)),
            round((1 - $allExpected / $powerEvuGl) * 100, 0)
        ];
        //Ende Tabelle rechts oben

        //Tabelle rechts unten
        $operations_monthly_right_tlower_tr1 = [
            $monthName . ' ' . $report['reportYear'],
            round($powerExp[$report['reportMonth'] - 1], 0),
            round(abs($powerEvu[$report['reportMonth'] - 1] - $powerExp[$report['reportMonth'] - 1]), 0),
            round((1 - $powerExp[$report['reportMonth'] - 1] / $powerEvu[$report['reportMonth'] - 1]) * 100, 2),
            round($powerAct[$report['reportMonth'] - 1], 0),
            round(abs($powerAct[$report['reportMonth'] - 1] - $powerExp[$report['reportMonth'] - 1]), 0),
            round((1 - $powerExp[$report['reportMonth'] - 1] / $powerAct[$report['reportMonth'] - 1]) * 100, 2),
        ];

        //Parameter fuer die Berechnung Q1
        if ((($currentYear == $report['reportYear'] && $currentMonth > 3) || $currentYear > $report['reportYear'])) {
            $start = $report['reportYear'] . '-01-01 00:00';
            $end = $report['reportYear'] . '-03-31 23:59';

            $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
            $powerEvu = $data2_grid_meter['powerEvu'];
            $powerAct = $data2_grid_meter['powerAct'];//Inv out
            $powerExp = $data2_grid_meter['powerExp'];


            $operations_monthly_right_tlower_tr2 = [
                round($powerExp, 0),
                round(abs($powerEvu - $powerExp), 0),
                round((1 - $powerExp / $powerEvu) * 100, 2),
                round($powerAct, 0),
                round(abs($powerAct - $powerExp), 0),
                round((1 - $powerExp / $powerAct) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tlower_tr2 = [
                '0',
                '0',
                '0',
                '0',
                '0',
                '0'
            ];
        }

        //Parameter fuer die Berechnung Q2
        if ((($currentYear == $report['reportYear'] && $currentMonth > 6) || $currentYear > $report['reportYear'])) {
            $start = $report['reportYear'] . '-04-01 00:00';
            $end = $report['reportYear'] . '-06-30 23:59';

            $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
            $powerEvu = $data2_grid_meter['powerEvu'];
            $powerAct = $data2_grid_meter['powerAct'];//Inv out
            $powerExp = $data2_grid_meter['powerExp'];


            $operations_monthly_right_tlower_tr3 = [
                round($powerExp, 0),
                round(abs($powerEvu - $powerExp), 0),
                round((1 - $powerExp / $powerEvu) * 100, 2),
                round($powerAct, 0),
                round(abs($powerAct - $powerExp), 0),
                round((1 - $powerExp / $powerAct) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tlower_tr3 = [
                '0',
                '0',
                '0',
                '0',
                '0',
                '0'
            ];
        }

        //Parameter fuer die Berechnung Q3
        if ((($currentYear == $report['reportYear'] && $currentMonth > 9) || $currentYear > $report['reportYear'])) {
            $start = $report['reportYear'] . '-07-1 00:00';
            $end = $report['reportYear'] . '09-30 23:59';

            $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
            $powerEvu = $data2_grid_meter['powerEvu'];
            $powerAct = $data2_grid_meter['powerAct'];//Inv out
            $powerExp = $data2_grid_meter['powerExp'];


            $operations_monthly_right_tlower_tr4 = [
                round($powerExp, 0),
                round(abs($powerEvu - $powerExp), 0),
                round((1 - $powerExp / $powerEvu) * 100, 2),
                round($powerAct, 0),
                round(abs($powerAct - $powerExp), 0),
                round((1 - $powerExp / $powerAct) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tlower_tr4 = [
                '0',
                '0',
                '0',
                '0',
                '0',
                '0'
            ];
        }

        //Parameter fuer die Berechnung Q4
        if ($currentYear > $report['reportYear']) {
            $start = $report['reportYear'] . '-10-01 00:00';
            $end = $report['reportYear'] . '-12-31 23:59';

            $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
            $powerEvu = $data2_grid_meter['powerEvu'];
            $powerAct = $data2_grid_meter['powerAct'];//Inv out
            $powerExp = $data2_grid_meter['powerExp'];


            $operations_monthly_right_tlower_tr5 = [
                round($powerExp, 0),
                round(abs($powerEvu - $powerExp), 0),
                round((1 - $powerExp / $powerEvu) * 100, 2),
                round($powerAct, 0),
                round(abs($powerAct - $powerExp), 0),
                round((1 - $powerExp / $powerAct) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tlower_tr5 = [
                '0',
                '0',
                '0',
                '0',
                '0',
                '0'
            ];
        }

        //Parameter fuer Year to Date
        if (!($yearPacDate == $report['reportYear'] && $monthPacDate > $currentMonth)) {
            $start = $report['reportYear'] . '-01-01 00:00';
            $end = $report['reportYear'] . '-' . $currentMonth . '-' . $currentDay . ' 23:59';

            $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
            $powerEvu = $data2_grid_meter['powerEvu'];
            $powerAct = $data2_grid_meter['powerAct'];//Inv out
            $powerExp = $data2_grid_meter['powerExp'];


            $operations_monthly_right_tlower_tr6 = [
                round($powerExp, 0),
                round(abs($powerEvu - $powerExp), 0),
                round((1 - $powerExp / $powerEvu) * 100, 2),
                round($powerAct, 0),
                round(abs($powerAct - $powerExp), 0),
                round((1 - $powerExp / $powerAct) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tlower_tr6 = [
                '0',
                '0',
                '0',
                '0',
                '0',
                '0'
            ];
        }

        //Parameter fuer total Runtime
        if (!($yearPacDate == $report['reportYear'] && $monthPacDate > $currentMonth)) {
            $start = $yearPacDate . '-' . $monthPacDate . '-' . $dayPacDate . ' 00:00';
            $end = $report['reportYear'] . '-' . $currentMonth . '-' . $currentDay . ' 23:59';

            $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
            $powerEvu = $data2_grid_meter['powerEvu'];
            $powerAct = $data2_grid_meter['powerAct'];//Inv out
            $powerExp = $data2_grid_meter['powerExp'];


            $operations_monthly_right_tlower_tr7 = [
                round($powerExp, 0),
                round(abs($powerEvu - $powerExp), 0),
                round((1 - $powerExp / $powerEvu) * 100, 2),
                round($powerAct, 0),
                round(abs($powerAct - $powerExp), 0),
                round((1 - $powerExp / $powerAct) * 100, 2)
            ];
        } else {
            $operations_monthly_right_tlower_tr7 = [
                '0',
                '0',
                '0',
                '0',
                '0',
                '0'
            ];
        }
        //End Operations month

        //Beginn Operations dayly1
        //The Table
        #beginn case5
        #die Daten  nur im korrekten Monat ausgeben
        $case5 = $this->serializer->normalize($anlage->getAnlageCase5s()->toArray(), null, ['groups' => 'case5']);
        $countCase5 = 0;
        for ($i = 0; $i < count($case5); $i++) {
            if (date('m', strtotime($case5[$i]['stampFrom'])) == $report['reportMonth'] || date('m', strtotime($case5[$i]['stampTo'])) == $report['reportMonth']) {
                $case5Values[] =
                    [
                        "stampFrom" => $case5[$i]['stampFrom'],
                        "stampTo" => $case5[$i]['stampTo'],
                        "inverter" => $case5[$i]['inverter'],
                        "reason" => $case5[$i]['reason'],
                    ];
                $countCase5 = count($case5);
            }
        }
        #end case5

        #beginn create Array for Day Values Table
        #die Daten dem Array hinzufuegen
        for ($i = 0; $i < count($report['prs']); $i++) {
            if($useGridMeterDayData === true) {$powerEGridExt = (float)$report['prs'][$i]->getpowerEGridExt();}
            if ($showAvailability === true) $plantAvailability = (float)$report['prs'][$i]->getplantAvailability();
            if ($showAvailabilitySecond === true) $plantAvailabilitySecond = (float)$report['prs'][$i]->getplantAvailabilitySecond();

            $table_overview_dayly[] =
                [
                    'datum' => $report['prs'][$i]->getstamp()->format('m-d'),
                    'powerEGridExt' => $powerEGridExt,
                    'PowerEvuMonth' => ($anlage->getShowEvuDiag()) ? (float)$report['prs'][$i]->getPowerEvu() : (float)$report['prs'][$i]->getPowerAct(),
                    'custirr' => round((float)$report['prs'][$i]->getspezYield(),3),
                    'irradiation' => round((float)$report['prs'][$i]->getirradiation(),3),
                    'prEvuProz' => round(($anlage->getShowEvuDiag()) ? (float)$report['prs'][$i]->getPrEvu() : (float)$report['prs'][$i]->getPrAct(),3),
                    'plantAvailability' => round($plantAvailability,3),
                    'plantAvailabilitySecond' => round($plantAvailabilitySecond,3),
                    'powerTheo' => round((float)$report['prs'][$i]->getpowerTheo(),3),
                    'powerExp' => round((float)$report['prs'][$i]->getpowerExp(),3),
                    'case5perDay' => $report['prs'][$i]->getcase5perDay(),
                ];
        }
        #die Totalzeile

        $table_overview_dayly[] = [
            'datum' => 'Total',
            'powerEGridExt' => $powerEGridExt,
            'PowerEvuMonth' => ($anlage->getShowEvuDiag()) ? (float)$report['lastPR']->getPowerEvuMonth() : (float)$report['lastPR']->getPowerActMonth(),
            'custirr' => round((float)$report['lastPR']->getspezYield(),3),
            'irradiation' => round((float)$report['lastPR']->getIrrMonth(),3),
            'prEvuProz' => round(($anlage->getShowEvuDiag()) ? (float)$report['lastPR']->getprEvuMonth() : (float)$report['lastPR']->getprActMonth(),3),
            'plantAvailability' => round($plantAvailability,3),
            'plantAvailabilitySecond' => round($plantAvailabilitySecond,3),
            'powerTheo' => round((float)$report['lastPR']->getpowerTheoMonth(),3),
            'powerExp' => round((float)$report['lastPR']->getpowerExpMonth(),3),
            'case5perDay' => $countCase5,
        ];

        #beginn create Array for Energy Production Chart
        #die Daten dem Array hinzufuegen
        $powerGridExtChart3[] = 0;
        $prEvuProz[] = 0;


        for ($i = 0; $i < count($report['prs']); $i++) {
            if($useGridMeterDayData === true) {
                $powerGridExtChart3[] = (float)$report['prs'][$i]->getpowerEGridExt();
            }else{
                $powerGridExtChart3[] = ($anlage->getShowEvuDiag()) ? (float)$report['prs'][$i]->getPowerEvu() : (float)$report['prs'][$i]->getPowerAct();
            }

            $prEvuProz[] = ($anlage->getShowEvuDiag()) ? (float)$report['prs'][$i]->getPrEvu() : (float)$report['prs'][$i]->getPrAct();

        }

        #end create Array for Energy Production Chart
        $chart->tooltip->show = true;

        $chart->tooltip->trigger = 'item';

        $chart->xAxis = array(
            'type' => 'category',
            'axisLabel' => array(
                'show' => true,
                'margin' => '5',
            ),
            'splitArea' => array(
                'show' => true,
            ),
            'min' => 1,
            'maxInterval' => 1,
            'data' => $array_yaxis
        );
        $chart->yAxis = [
            [
                'type' => 'value',
                'name' => 'Grid Meter',
                'min' => 0,
                'interval' => 50000,
                'name' => 'KWH',
                'nameLocation' => 'middle',
                'nameGap' => 50
            ],
            [
                'type' => 'value',
                'name' => 'Irradiation',
                'min' => 0,
                'max' => 100,
                'interval' => 5,
                'name' => '%',
                'nameLocation' => 'middle',
                'nameGap' => 25
            ]
        ];
        $chart->series =
            [
                [
                    'name' => 'Grid Meter',
                    'type' => 'bar',

                    'data' => $powerGridExtChart3,
                    'visualMap' => 'false'
                ]
                ,
                [
                    'name' => 'PR',
                    'type' => 'scatter',
                    'yAxisIndex' => 1,
                    'data' => $prEvuProz,
                    'visualMap' => 'false'
                ]
            ];

        $option = array(
            'color' => ['#ff0000', '#61e915'],
            'title' => [
                'text' => $monthName . ' ' . $report['reportYear'],
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
                    'width' => 'auto',
                ),
        );

        Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
        $chart->setOption($option);

        $operations_dayly_1 = $chart->render('operations_dayly_1', ['style' => 'height: 400px; width:600px;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);
        //End Operations dayly1


        //Beginn Operations dayly2
        #beginn create Array for Energy Production Chart
        #die Daten dem Array hinzufuegen
        $powerGridExtChart4[] = 0;
        $irradiation[] = 0;
        for ($i = 0; $i < count($report['prs']); $i++) {
            if($useGridMeterDayData === true) {
                $powerGridExtChart4[] = (float)$report['prs'][$i]->getpowerEGridExt();
            }else{
                $powerGridExtChart4[] = ($anlage->getShowEvuDiag()) ? (float)$report['prs'][$i]->getPowerEvu() : (float)$report['prs'][$i]->getPowerAct();
            }

            $irradiation[] = (float)$report['prs'][$i]->getirradiation();

        }

        #end create Array for Energy Production Chart

        $chart->tooltip->show = true;
        $chart->tooltip->trigger = 'item';

        $chart->xAxis = array(
            'type' => 'category',
            'axisLabel' => array(
                'show' => true,
                'margin' => '5',
            ),
            'splitArea' => array(
                'show' => true,
            ),
            'min' => 1,
            'maxInterval' => 1,
            'data' => $array_yaxis
        );
        $chart->yAxis = [
            [
                'type' => 'value',
                'min' => 0,
                'interval' => 50000,
                'name' => 'KWH',
                'nameLocation' => 'middle',
                'nameGap' => 50
            ],
            [
                'type' => 'value',
                'min' => 0,
                'interval' => 1,
                'name' => 'KWH/m2',
                'nameLocation' => 'middle',
                'nameGap' => 25
            ]
        ];
        $chart->series =
            [
               [
                    'name' => 'Grid Meter',
                    'type' => 'bar',
                    'data' => $powerGridExtChart4,
                    'visualMap' => 'false'
                ]
                ,
                [
                    'name' => 'Irradiation',
                    'type' => 'line',
                    'yAxisIndex' => 1,
                    'data' => $irradiation,
                    'visualMap' => 'false'
                ]
            ];

        $option = array(
            'color' => ['#ff0000', '#fbba00'],
            'title' => [
                'text' => $monthName . ' ' . $report['reportYear'],
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
                    'width' => 'auto',
                ),
        );

        Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
        $chart->setOption($option);

        $operations_dayly_2 = $chart->render('operations_dayly_2', ['style' => 'height: 400px; width:600px;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);
        //End Operations dayly2

        //Beginn Operations Availability 1

        $chart->tooltip->show = true;
        $chart->tooltip->trigger = 'item';

        $chart->series =
            [
                'name' => 'Grid Meter',
                'type' => 'pie',
                'data' => [
                    ['value' => 1048, 'name' => 'SOF - Maintanance'],
                    ['value' => 1200, 'name' => 'EFOR Defects/Failures'],
                    ['value' => 1500, 'name' => 'OMC - GRID outage, GRID rgulate'],
                    ['value' => 1048, 'name' => 'TOTAL'],
                ],
                'visualMap' => 'false'

            ];

        $option = array(
            'color' => ['#9dc3e6', '#f6b99e', '#7f7f7f', '#e2f0d9'],
            'title' => [
                'text' => 'Plant Avialability',
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
        );

        Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
        $chart->setOption($option);

        $operations_availability_1 = $chart->render('operations_availability_1', ['style' => 'height: 400px; width:600px;']);
        $chart->tooltip = [];
        $chart->series = [];
        unset($option);
        //End Operations Availability 1

        //Beginn Operations Availability 2
        $chart->tooltip->show = true;
        $chart->tooltip->trigger = 'item';

        $chart->series =
            [
                'name' => 'Grid Meter',
                'type' => 'pie',
                'data' => [
                    ['value' => 1048, 'name' => 'SOF - Maintanance'],
                    ['value' => 1200, 'name' => 'EFOR Defects/Failures'],
                    ['value' => 1500, 'name' => 'OMC - GRID outage, GRID rgulate']
                ],
                'visualMap' => 'false'

            ];

        $option = array(
            'color' => ['#4472c4', '#ed7d31', '#a5a5a5'],
            'title' => [
                'text' => 'Failures',
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
        );

        Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
        $chart->setOption($option);

        $operations_availability_2 = $chart->render('operations_availability_2', ['style' => 'height: 400px; width:600px;']);
        $chart->tooltip = [];
        $chart->series = [];
        unset($option);
        //End Operations Availability 1

        //fuer PA Report Month

        $sql = "SELECT DATE_FORMAT(stamp, '%Y-%m-%d') AS form_date, unit, COUNT(db_id) as anz, sum(pa_0) as summe, sum(pa_0)/COUNT(db_id)*100 as pa FROM ".$anlage->getDbNameIst()." where stamp BETWEEN '".$report['reportYear']."-".$report['reportMonth']."-1 00:00' and '".$report['reportYear']."-".$report['reportMonth']."-".$daysInReportMonth." 23:59' and pa_0 >= 0 group by unit, DATE_FORMAT(stamp, '%Y-%m-%d')";

        $result = $conn->prepare($sql);
        $result->execute();
        $i = 0;
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $pa[] = [
                'form_date' =>  date("d", strtotime($value['form_date'])),
                'pa' => round($value['pa'],3),
                'unit' => $value['unit']
            ];
            $i++;

            if($i > $daysInReportMonth-1){
                $i = 0;
                $outPa[] = $pa;
                unset($pa);
            }
        }

        //Fuer die PA des aktuellen Jahres
        for ($j = 1; $j <= $report['reportMonth']; $j++) {
            $daysInThisMonth = cal_days_in_month(CAL_GREGORIAN, $j, $report['reportYear']);
            $sql = "SELECT DATE_FORMAT(stamp, '%Y-%m') AS form_date, unit, COUNT(db_id) as anz, sum(pa_0) as summe, sum(pa_0)/COUNT(db_id)*100 as pa FROM " . $anlage->getDbNameIst() . " where stamp BETWEEN '" . $report['reportYear'] . "-" . $j . "-1 00:00' and '" . $report['reportYear'] . "-" . $j . "-" . $daysInThisMonth . " 23:59'and pa_0 >= 0  group by unit, DATE_FORMAT(stamp, '%Y-%m')";

            $result = $conn->prepare($sql);
            $result->execute();
            $i = 0;

            foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
                #$summ[$i] = $value['pa'];
                $pa[] = [
                    'form_date' =>  date("m", strtotime($value['form_date'])),
                    'pa' => round($value['pa'],3),
                    'unit' => $value['unit']
                ];
                $i++;

                #echo date("m", strtotime($value['form_date'])) . '<br>';
                if ($i == 40) {

                    $i = 0;
                    $outTemp[] = $pa;

                    unset($pa);
                    break;
                }

            }

            $outPaCY[] = $outTemp;
            unset($outTemp);

        }

//Beginn Operations Availability overview
                $dateform = '%d.%m.%Y';
                $z = 8;
                $i = 1;
                $j = 1;
                $k = 0;
                $l = 1;
                $max = 0;
/*
                $stmt = $conn->prepare('CALL GetExpectetData(:dateform, :datefrom, :dateto, :groupac)');

                while ($j <= $z) {
                    $stmt->execute(array(':dateform' => $dateform, ':datefrom' => $report['from'], ':dateto' => $report['to'], ':groupac' => $j));
                    while ($row = $stmt->fetch()) {
                        $data[] =
                            [
                                $k,
                                $i,
                                round($row["act_strom_dc"], 2),
                            ];
                        if($max < round($row["act_strom_dc"], 2)){
                            $max = round($row["act_strom_dc"], 0);
                        }
                        $i++;
                    }
                    $stmt->closeCursor();
                    $j++;
                    $k++;
                    $i = 1;
                }

                $chart->tooltip->show = true;
                $chart->visualMap->calculable = true;
                $chart->tooltip->trigger = 'item';
                $chart->tooltip->formatter = '{a} {b}  {c}';

                $chart->xAxis = array(
                    'type' => 'category',
                    'axisLabel' => array(
                        'show' => true,
                        'margin' => '10.5',
                        'size' => '10'
                    ),
                    'splitArea' => array(
                        'show' => true,
                    ),
                    'data' => array('INV1', 'INV2', 'INV3', 'INV4', 'INV5', 'INV6', 'INV7', 'INV8','INV1', 'INV2', 'INV3', 'INV4', 'INV5', 'INV6', 'INV7', 'INV8','INV1', 'INV2', 'INV3', 'INV4', 'INV5', 'INV6', 'INV7', 'INV8','INV1', 'INV2', 'INV3', 'INV4', 'INV5', 'INV6', 'INV7', 'INV8','INV1', 'INV2', 'INV3', 'INV4', 'INV5', 'INV6', 'INV7', 'INV8','INV1', 'INV2', 'INV3', 'INV4', 'INV5', 'INV6', 'INV7', 'INV8')
                );
                $chart->yAxis = array(
                    'type' => 'value',
                    'inverse' => true,
                    'min' => 1,
                    'max' => $range,
                    'maxInterval' => 1,
                    'data' => $array_yaxis,
                );

                $chart->series = array(
                    'name' => 'Availability',
                    'type' => 'heatmap',
                    'data' => [[0,1,15],[0,2,30],[0,3,350]],
                    'label' => array(
                        'show' => true,
                    ),
                );

                $option = array(
                    'title' => [
                        'text' => '',
                    ],
                    'tooltip' =>
                        [
                            'show' => true,
                        ],
                    'legend' =>
                        [
                            'show' => true,
                            'left' => 'center',
                            'top' => 'top'
                        ],
                    'grid' =>
                        array(
                            'height' => '80%',
                            'top' => 100,
                            'width' => 'auto',
                        ),
                    'toolbox' =>
                        [
                            'show' => false,
                        ],
                    'visualMap' => [
                        'show' => true,
                        'min' => 0,
                        'max' => $max+500,
                        'splitNumber' => 8,
                        'type' => 'piecewise',
                        'orient' => 'horizontal',
                        'left' => 'center',
                        'top' => 40,
                        'inRange' => [
                            'color' => ["#f9b783", "#f9806f", "#fa9974", "#fba679", "#fbb178", "#fdcb7d", "#fee983", "#63be7b"]

                        ],
                    ]
                );
                Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
                $chart->setOption($option);
                $operations_availability_dayly = $chart->render('operations_availability_dayly', ['style' => 'height: 900px; width:1200px;'], 'cool');
                $chart->tooltip = [];
                $chart->xAxis = [];
                $chart->yAxis = [];
                $chart->series = [];
                unset($option);
*/
        //End Operations Availability overview



        //Beginn Operations string_dayly1

        if ($anlage->getUseNewDcSchema()) {
        $sql = "SELECT DATE_FORMAT( a.stamp, '%d.%m.%Y') AS form_date, sum(b.wr_pdc) AS act_power_dc, sum(b.wr_idc) AS act_current_dc, b.group_ac as invgroup
            FROM (db_dummysoll a left JOIN ".$anlage->getDbNameDcIst()." b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '".$report['reportYear']."-".$report['reportMonth']."-1 00:00' and '".$report['reportYear']."-".$report['reportMonth']."-".$daysInReportMonth." 23:59' and b.group_ac > 0 GROUP BY form_date,b.group_ac ORDER BY b.group_ac,form_date";
        } else {
            $sql = "SELECT DATE_FORMAT( a.stamp, '%d.%m.%Y') AS form_date, sum(b.wr_pdc) AS act_power_dc, sum(b.wr_idc) AS act_current_dc, b.inv as invgroup
            FROM (db_dummysoll a left JOIN ".$anlage->getDbNameIst()." b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '".$report['reportYear']."-".$report['reportMonth']."-1 00:00' and '".$report['reportYear']."-".$report['reportMonth']."-".$daysInReportMonth." 23:59' and b.group_ac > 0 GROUP BY form_date,b.group_ac ORDER BY b.group_ac,form_date";
        }

        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $dcIst[] = [
                'act_power_dc' => $value['act_power_dc'],
                'act_current_dc' => $value['act_current_dc']
            ];
        }

        #".$anlage->getDbNameDcSoll()."
        $sql = "SELECT DATE_FORMAT( a.stamp, '%d.%m.%Y') AS form_date, sum(b.dc_exp_power) AS exp_power_dc, sum(b.dc_exp_current) AS exp_current_dc, b.group_ac as invgroup
            FROM (db_dummysoll a left JOIN ".$anlage->getDbNameDcSoll()." b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '".$report['reportYear']."-".$report['reportMonth']."-1 00:00' and '".$report['reportYear']."-".$report['reportMonth']."-".$daysInReportMonth." 23:59' and b.group_ac > 0 GROUP BY form_date,b.group_ac ORDER BY b.group_ac,form_date";

        $result = $conn->prepare($sql);
        $result->execute();
        $i = 0;
        $j = 0;
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $dcExpDcIst[] = [
                'group' => $value['invgroup'],
                'form_date' => date("d", strtotime($value['form_date'])),
                'exp_power_dc' => round($value['exp_power_dc'],0),
                'exp_current_dc' => round($value['exp_current_dc'],0),
                'act_power_dc' => round($dcIst[$j]['act_power_dc'],0),
                'act_current_dc' => round($dcIst[$j]['act_current_dc'],0),
                'diff_current_dc' => round((1 - $value['exp_current_dc'] / $dcIst[$j]['act_current_dc']) * 100, 0),
                'diff_power_dc' => round((1 - $value['exp_power_dc'] / $dcIst[$j]['act_power_dc']) * 100, 0),
            ];
            $i++;
            $j++;
            if($i > $daysInReportMonth-1){
                $i = 0;
                $outTableCurrentsPower[] = $dcExpDcIst;
                unset($dcExpDcIst);
            }
        }

        #echo '<pre>';
       # print_r($outTableCurrentsPower);
        #echo '</pre>';
        #exit;
            #$tableColsLimit
        
        $chart->tooltip->show = true;

        $chart->tooltip->trigger = 'item';

        $chart->xAxis = array(
            'type' => 'category',
            'axisLabel' => array(
                'show' => true,
                'margin' => '5',
            ),
            'splitArea' => array(
                'show' => true,
            ),
            'min' => 1,
            'data' => $array_yaxis
        );
        $chart->yAxis = [
            [
                'type' => 'value',
                'name' => 'Grid Meter',
                'min' => 0,
                'interval' => 50,
                'name' => 'A',
                'nameLocation' => 'middle',
                'nameGap' => 40
            ],
        ];
        $chart->series =
            [
                [
                    'name' => 'Act Current',
                    'type' => 'line',
                    'data' => [
                        50, 20, 95, 80, 44, 95
                    ],
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Expected Current',
                    'type' => 'line',

                    'data' => [
                        60, 30, 100, 99, 60, 102
                    ],
                    'visualMap' => 'false'
                ],
            ];

        $option = array(
            'color' => ['#6994d6', '#f7d20e'],
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
                    'width' => 'auto',
                ),
        );

        Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
        $chart->setOption($option);
        $operations_currents_dayly_1 = $chart->render('operations_currents_dayly_1', ['style' => 'height: 400px; width:600px;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);
        //End Operations currents_dayly1
        /*
                //Beginn Operations Availability overview
                $dateform = '%d.%m.%Y';
                $z = 8;
                $i = 1;
                $j = 1;
                $k = 0;
                $l = 1;
                $max = 0;

                $stmt = $conn->prepare('CALL GetExpectetData(:dateform, :datefrom, :dateto, :groupac)');

                while ($j <= $z) {
                    $stmt->execute(array(':dateform' => $dateform, ':datefrom' => $report['from'], ':dateto' => $report['to'], ':groupac' => $j));
                    while ($row = $stmt->fetch()) {
                        $data2[] =
                            [
                                $k,
                                $i,
                                round($row["act_strom_dc"], 2),
                            ];
                        if($max < round($row["act_strom_dc"], 2)){
                            $max = round($row["act_strom_dc"], 0);
                        }
                        $i++;
                    }
                    $stmt->closeCursor();
                    $j++;
                    $k++;
                    $i = 1;
                }

                $chart->tooltip->show = true;
                $chart->visualMap->calculable = true;
                $chart->tooltip->trigger = 'item';
                $chart->tooltip->formatter = '{a} {b}  {c}';

                $chart->xAxis = array(
                    'type' => 'category',
                    'axisLabel' => array(
                        'show' => true,
                        'margin' => '18.5',
                    ),
                    'splitArea' => array(
                        'show' => true,
                    ),
                    'data' => array('INV 1', 'INV 2', 'INV 3', 'INV 4', 'INV 5', 'INV 6', 'INV 7', 'INV 8')
                );
                $chart->yAxis = array(
                    'type' => 'value',
                    'inverse' => true,
                    'min' => 1,
                    'max' => $range,
                    'maxInterval' => 1,
                    'data' => $array_yaxis,
                );
                $chart->series = array(
                    'name' => 'String currents',
                    'type' => 'heatmap',
                    'data' => $data2,
                    'label' => array(
                        'show' => true,
                    ),
                );

                $option = array(
                    'title' => [
                        'text' => '',
                    ],
                    'tooltip' =>
                        [
                            'show' => true,
                        ],
                    'legend' =>
                        [
                            'show' => true,
                            'left' => 'center',
                            'top' => 'top'
                        ],
                    'grid' =>
                        array(
                            'height' => '80%',
                            'top' => 100,
                            'width' => 'auto',
                        ),
                    'toolbox' =>
                        [
                            'show' => false,
                        ],
                    'visualMap' => [
                        'show' => true,
                        'min' => 0,
                        'max' => $max+500,
                        'splitNumber' => 8,
                        'type' => 'piecewise',
                        'orient' => 'horizontal',
                        'left' => 'center',
                        'top' => 40,
                        'inRange' => [
                            'color' => ["#f9b783", "#f9806f", "#fa9974", "#fba679", "#fbb178", "#fdcb7d", "#fee983", "#63be7b"]

                        ],
                    ]
                );
                Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
                $chart->setOption($option);
                $operations_currents_dayly_2 = $chart->render('operations_currents_dayly_2', ['style' => 'height: 900px; width:600px;'], 'cool');
                $chart->tooltip = [];
                $chart->xAxis = [];
                $chart->yAxis = [];
                $chart->series = [];
                unset($option);
                //End Operations Availability overview

                //Beginn Inverters dayli
                $chart->tooltip->show = true;
                $chart->tooltip->trigger = 'item';
                $chart->visualMap->calculable = true;
                $chart->xAxis = array(
                    'type' => 'value',
                    'scale' => true,
                    'axisLabel' => array(
                        'show' => true,
                        'margin' => '5',
                    ),
                    'splitArea' => array(
                        'show' => true,
                    ),
                    'maxInterval' => 1,
                );
                $chart->yAxis = array(
                    'type' => 'value',
                    'name' => 'Differenz (%)',
                    'nameLocation' => 'middle',
                    'nameGap' => 40,
                    'scale' => true,
                    'axisLabel' => [
                        'formatter' => '{value}',
                        'splitLine' => [
                            'show' => false
                            ],
                        'inverse' => false,
                        'max' => 100,
                        ]
                );

                $chart->series =
                    [
                        [
                            'name' => 'Yield (Grid meter)',
                            'emphasis' => [
                                'focus' => 'series'
                            ],
                            'type' => 'scatter',
                            'data' => [
                                [5, -100], [5.25, -80], [5.50, -70], [5.75, -60], [6, -10], [7, -7], [8, -5], [9, -4], [10, -1], [11, 2], [12, 5], [13, 10], [14, 10], [15, 8], [16, 5], [17, -5], [18, -8.0], [19, -53.6], [20, -80.0], [21, -90]
                            ],
                            'visualMap' => 'true'
                        ],
                        [
                            'name' => 'Expected PV SYST',
                            'emphasis' => [
                                'focus' => 'series'
                            ],
                            'type' => 'scatter',
                            'data' => [
                                [5, -100], [6, -92], [7, -86], [8, -2], [9, 3.6], [10, 6], [11, 15], [12, 12], [13, 15], [14, 12], [15, 11], [16, 9.0], [17, -7], [18, -6], [19, -80], [20, -95], [21, -100]
                            ],
                            'visualMap' => 'true'
                        ],
                        [
                            'name' => 'Expected g4n',
                            'emphasis' => [
                                'focus' => 'series'
                            ],
                            'type' => 'scatter',
                            'data' => [
                                [5, -100], [6, -93], [7, -68], [8, 3], [9, 9], [10, 12], [11, 18], [12, 25], [13, 22], [14, 19], [15, 17], [16, 5], [17, -3], [18, -5.0], [19, -83.6], [20, -93.0], [21, -100]
                            ],
                            'visualMap' => 'true'
                        ]
                    ]
                ;

                $option = array(
                    #'color' => ['#698ed0', '#f1975a', '#b7b7b7'],
                    'title' => [
                        'text' => 'Year '.$report['reportYear'],
                        'left' => 'center',
                    ],
                    'tooltip' =>
                        [
                            'show' => true,
                        ],
                    'legend' =>
                        [
                            'show' => false,
                            'left' => 'center',
                            'top' => 20
                        ],
                    'grid' =>
                        array(
                            'height' => '80%',
                            'top' => 50,
                            'width' => 'auto',
                        ),
                    'visualMap' => [
                        'show' => true,
                        'min' => 0,
                        'max' => 100,
                        'splitNumber' => 3,
                        'type' => 'piecewise',
                        'pieces' => [
                            [
                                'min' => -100,
                                'max' => -10,
                            ],
                            [
                                'min' => -10,
                                'max' => -5,
                            ],
                            [
                                'min' => -5,
                                'max' => 30,
                            ]
                        ],
                        'orient' => 'horizontal',
                        'left' => 'center',
                        'top' => 20,
                        'inRange' => [
                            'color' => ["#ff0000", "#f7d20e", "#00ff00"]
                        ],
                        ]
                );

                Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
                $chart->setOption($option);

                $operations_inverters_dayly_1 = $chart->render('operations_inverters_dayly_1', ['style' => 'height: 400px; width:600px;']);
                $chart->tooltip = [];
                $chart->xAxis = [];
                $chart->yAxis = [];
                $chart->series = [];
                unset($option);
                //End Inverters dayli
        */

        //Beginn Inverters heatmap
        $data1 = [
            [1, 0, "#f00"],
            [2, 0, "#f00"],
            [3, 0, "#f00"],
            [4, 0, "#f00"],
            [5, 0, "#f00"],
            [6, 10, "#f00"],
            [7, 65, "#ffd80b"],
            [8, 70, "#ffd80b"],
            [9, 75, "#ffd80b"],
            [10, 80, "#ffd80b"],
            [11, 90, "#005e00"],
            [12, 95, "#005e00"],
            [13, 90, "#005e00"],
            [14, 99, "#005e00"],
            [15, 88, "#005e00"],
            [16, 70, "#005e00"],
            [17, 60, "#005e00"],
            [18, 20, "#ffd80b"],
            [19, 15, "#ffd80b"],
            [20, 10, "#ffd80b"],
            [21, 0, "#f00"],
            [22, 0, "#f00"],
            [23, 0, "#f00"],
            [24, 0, "#f00"]
        ];

        $data2 = [
            [1, 0, "#f00"],
            [2, 0, "#f00"],
            [3, 0, "#f00"],
            [4, 0, "#f00"],
            [5, 10, "#f00"],
            [6, 15, "#f00"],
            [7, 60, "#ffd80b"],
            [8, 75, "#ffd80b"],
            [9, 85, "#ffd80b"],
            [10, 90, "#ffd80b"],
            [11, 98, "#005e00"],
            [12, 95, "#005e00"],
            [13, 96, "#005e00"],
            [14, 99, "#005e00"],
            [15, 93, "#005e00"],
            [16, 96, "#005e00"],
            [17, 90, "#005e00"],
            [18, 20, "#ffd80b"],
            [19, 15, "#ffd80b"],
            [20, 10, "#ffd80b"],
            [21, 0, "#f00"],
            [22, 0, "#f00"],
            [23, 0, "#f00"],
            [24, 0, "#f00"]
        ];

        $data3 = [
            [1, 0, 45, "i3"],
            [2, 0, 27, "i3"],
            [3, 0, 60, "i3"],
            [4, 0, 81, "i3"],
            [5, 0, 77, "i3"],
            [6, 15, 81, "i3"],
            [7, 18, 77, "i3"],
            [8, 25, 65, "i3"],
            [9, 38, 33, "i3"],
            [10, 45, 55, "i3"],
            [11, 55, 81, "v"],
            [12, 59, 71, "i3"],
            [13, 62, 69, "i3"],
            [14, 58, 87, "i3"],
            [15, 55, 80, "i3"],
            [16, 45, 83, "i3"],
            [17, 40, 43, "i3"],
            [18, 35, 46, "i3"],
            [19, 26, 71, "i3"],
            [20, 20, 57, "i3"],
            [21, 15, 63, "i3"],
            [22, 0, 77, "i3"],
            [23, 0, 62, "i3"],
            [24, 0, 128, "i3"]
        ];

        $chart->tooltip->show = true;

        $chart->tooltip->trigger = 'item';

        $chart->xAxis = array(
            'type' => 'category',
            'scale' => true,
            'name' => 'Time',
            'nameLocation' => 'middle',
            'nameGap' => 20,
            'axisLabel' => array(
                'show' => true,
                'margin' => '5',
            ),

            'min' => 1,
            'maxInterval' => 1,
            'data' => array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24'),
        );
        $chart->yAxis = array(
            'type' => 'value',
            'name' => 'Panel Temperature (°C)',
            'nameLocation' => 'middle',
            'nameGap' => 40,
            'scale' => true,
            'axisLabel' => [
                'formatter' => '{value}',
                'splitLine' => [
                    'show' => false
                ],
                'inverse' => false,
                'max' => 65,
            ]
        );

        $chart->series =
            [
                [
                    'name' => 'i1',
                    'type' => 'scatter',
                    'dimensionIndex' => 2,
                    'data' => $data1,
                    'visualMap' => 'true',
                    'itemStyle' => [
                        'color' => "function (obj) {
                                    var value = obj.value;
                                    return value[2];
                                }"
                    ],
                ],
                [
                    'name' => 'i2',
                    'type' => 'scatter',
                    'dimensionIndex' => 2,
                    'data' => $data2,
                    'visualMap' => 'true',
                    'itemStyle' => [
                        'color' => "function (obj) {
                                    var value = obj.value;
                                    return value[2];
                                }"
                    ],
                ],
            ];

        $option = array(
            'color' => ['#698ed0', '#f1975a', '#b7b7b7'],
            'title' => [
                'text' => 'Abweichung Ist-Leistung zu Solleistung ' . $report['reportMonth'],
                'left' => 'center',
            ],
            'tooltip' =>
                [
                    'show' => true,
                ],
            'legend' =>
                [
                    'show' => false,
                    'left' => 'center',
                    'botom' => 20
                ],
            'grid' =>
                array(
                    'height' => '80%',
                    'top' => 50,
                    'width' => 'auto',
                ),

        );

        Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
        $chart->setOption($option);

        $inverters_heatmap = $chart->render('inverters_heatmap', ['style' => 'height: 400px; width:600px;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);
        //End Inverters heatmap

        $conn = null;

        $output = [
            'operations_right' => $operations_right,
            'month' => $monthName,
            'dataMmonthArray' => $dataMmonthArray,
            'year' => $report['reportYear'],
            'tbody_a_production' => $tbody_a_production,
            'table_overview_monthly' => $tbody_a_production,
            'operations_freetext_one' => 'sssssdasd',
            'operations_monthly_left' => $operations_monthly_left,
            'operations_monthly_right_tupper_tr1' => $operations_monthly_right_tupper_tr1,
            'operations_monthly_right_tupper_tr2' => $operations_monthly_right_tupper_tr2,
            'operations_monthly_right_tupper_tr3' => $operations_monthly_right_tupper_tr3,
            'operations_monthly_right_tupper_tr4' => $operations_monthly_right_tupper_tr4,
            'operations_monthly_right_tupper_tr5' => $operations_monthly_right_tupper_tr5,
            'operations_monthly_right_tupper_tr6' => $operations_monthly_right_tupper_tr6,
            'operations_monthly_right_tupper_tr7' => $operations_monthly_right_tupper_tr7,
            'operations_monthly_right_tlower_tr1' => $operations_monthly_right_tlower_tr1,
            'operations_monthly_right_tlower_tr2' => $operations_monthly_right_tlower_tr2,
            'operations_monthly_right_tlower_tr3' => $operations_monthly_right_tlower_tr3,
            'operations_monthly_right_tlower_tr4' => $operations_monthly_right_tlower_tr4,
            'operations_monthly_right_tlower_tr5' => $operations_monthly_right_tlower_tr5,
            'operations_monthly_right_tlower_tr6' => $operations_monthly_right_tlower_tr6,
            'operations_monthly_right_tlower_tr7' => $operations_monthly_right_tlower_tr7,
            'table_overview_dayly' => $table_overview_dayly,
            'useGridMeterDayData' => $useGridMeterDayData,
            'plantAvailability' => $outPa,
            'plantAvailabilityCurrentYear' => $outPaCY,
            'showAvailability' => $showAvailability,
            'showAvailabilitySecond' => $showAvailabilitySecond,
            'operations_dayly_1' => $operations_dayly_1,
            'operations_dayly_2' => $operations_dayly_2,
            'operations_availability_1' => $operations_availability_1,
            'operations_availability_2' => $operations_availability_2,
            #'operations_availability_dayly' => $operations_availability_dayly,
            'operations_currents_dayly_1' => $operations_currents_dayly_1,
            'operations_currents_dayly_2' => $operations_currents_dayly_2,
            'operations_currents_dayly_table' => $outTableCurrentsPower,
            'daysInReportMonth' => $daysInReportMonth,
            'tableColsLimit' => $tableColsLimit,
            'operations_inverters_dayly_1' => $operations_inverters_dayly_1,
            'operations_inverters_dayly_2' => $operations_inverters_dayly_2,
            'inverters_heatmap' => $inverters_heatmap,
            'inverters_heatmap_1' => $inverters_heatmap_1,
            'inverters_heatmap_2' => $inverters_heatmap_2,
            'acGroups' => $acGroups
        ];

        return $output;

    }
}