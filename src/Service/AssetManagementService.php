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

    public function assetReport($anlage, $month = 0, $year = 0, $chartTypeToExport = 0): array
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
        $report['prs'] = $this->PRRepository->findPRInMonth($report['anlage'], $reportMonth, $reportYear);
        $report['lastPR'] = $this->PRRepository->findOneBy(['anlage' => $report['anlage'], 'stamp' => date_create("$year-$month-$lastDayMonth")]);
        $report['case5s'] = $this->case5Repo->findAllAnlageDay($report['anlage'], $from);
        $report['pvSyst'] = $this->getPvSystMonthData($report['anlage'], $month, $year);
        $useGridMeterDayData = $report['anlage']->getUseGridMeterDayData();
        $showAvailability = $report['anlage']->getAnlId();
        $showAvailabilitySecond = $report['anlage']->getShowAvailabilitySecond();
        $usePac = $report['anlage']->getUsePac();

        $countCase5 = 0;

        $output = $this->buildAssetReport($report['anlage'], $report, $chartTypeToExport);

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
            $monthExtendetArray[$i]['month'] = $monthArray[$i];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $i + 1, $report['reportYear']);
            $monthExtendetArray[$i]['days'] = $daysInMonth;
            $monthExtendetArray[$i]['hours'] = $daysInMonth * 24;
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

            //Das hier ist noetig da alle 12 Monate benötigt werden
            $sql = "SELECT ertrag_design FROM anlagen_pv_syst_month WHERE anlage_id = $anlId and month = $i";
            $resultErtrag_design = $connAnlage->query($sql);
            if ($resultErtrag_design) {
                if ($resultErtrag_design->num_rows == 1) {
                    $Ertrag_design = $resultErtrag_design->fetch_assoc();
                }
            }

            if ($i > $report['reportMonth']) {
                $data1_grid_meter['powerEvu'] = 0;
                $data1_grid_meter['powerAct'] = 0;//Inv out
                $data1_grid_meter['powerExp'] = 0;
                $data1_grid_meter['powerExpEvu'] = 0;
            }

            $powerEvu[] = str_replace('.', ',', $data1_grid_meter['powerEvu']);
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
            $expectedPvSyst[] = $Ertrag_design['ertrag_design'];

            $expectedPvSystYearToDate = $expectedPvSystYearToDate + $expectedPvSystDb;
            unset($pvSyst);
            #if ($report['reportMonth'] == $i && $report['reportYear'] == $currentYear) {
            #$i = 13;
            #}
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
            $dataCfArray[$i]['month'] = $monthExtendetArray[$i]['month'];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $i + 1, $report['reportYear']);
            $dataCfArray[$i]['days'] = $daysInMonth;
            $dataCfArray[$i]['hours'] = $daysInMonth * 24;
            $dataCfArray[$i]['cf'] = round(($tbody_a_production['powerEvu'][$i] / 1000) / (($plantSize / 1000) * ($daysInMonth * 24)) * 100, 3);
        }

        //beginn chart
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
            'data' => array_slice($dataMonthArray, 0, $report['reportMonth'])
        );
        $chart->yAxis = array(
            'type' => 'value',
            'min' => 0,
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 70
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
            'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000'],
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

        $operations_right = $chart->render('operations_right', ['style' => 'height: 450px; width:860px; margin-left:40px;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        //End Production

        //Beginn Cumulative Forecast with PVSYST
        //fuer die Tabelle

        #Forecast / degradation
        $degradation = 4.98;

        //Cumulative Forecast
        $kumsum[0] = $powerEvu[0];
        for ($i = 0; $i < 12; $i++) {
            if ($i + 1 > $report['reportMonth']) {
                $kumsum[$i] = $expectedPvSyst[$i] + $kumsum[$i - 1];
            } else {
                $kumsum[$i] = $powerEvu[$i] + $kumsum[$i - 1];
            }
            $tbody_forcast_PVSYSTP50[] = $kumsum[$i];
            if ($i + 1 < $report['reportMonth']) {
                $tbody_forcast_PVSYSTP90[] = $kumsum[$i];
            } else {
                $tbody_forcast_PVSYSTP90[] = $kumsum[$i] - ($kumsum[$i] * $degradation / 100);
            }
        }
        #Forecast / PVSYST - P90
        $kumsum[0] = $expectedPvSyst[0];
        for ($i = 0; $i < 12; $i++) {
            $kumsum[$i] = $expectedPvSyst[$i] + $kumsum[$i - 1];
            $tbody_forcast_plan_PVSYSTP50[] = $kumsum[$i];

            $tbody_forcast_plan_PVSYSTP90[] = $kumsum[$i] - ($kumsum[$i] * $degradation / 100);

        }


        $forecast_PVSYST_table = [
            'forcast_PVSYSTP50' => $tbody_forcast_PVSYSTP50,
            'forcast_PVSYSTP90' => $tbody_forcast_PVSYSTP90,
            'forcast_plan_PVSYSTP50' => $tbody_forcast_plan_PVSYSTP50,
            'forcast_plan_PVSYSTP90' => $tbody_forcast_plan_PVSYSTP90,
        ];

        //beginn chart
        $chart->tooltip->show = true;
        $chart->tooltip->trigger = 'item';

        $chart->xAxis = array(
            'type' => 'category',
            'axisLabel' => array(
                'show' => true,
                'margin' => '0',
            ),
            'splitArea' => array(
                'show' => true,
            ),
            'data' => $dataMonthArray
        );
        $chart->yAxis = array(
            'type' => 'value',
            'min' => 0,
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 70
        );
        $chart->series =
            [
                [
                    'name' => 'Production ACT / PVSYST - P50',
                    'type' => 'line',
                    'data' => $tbody_forcast_PVSYSTP50,
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Production ACT / PVSYST - P90',
                    'type' => 'line',
                    'data' => $tbody_forcast_PVSYSTP90,
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Plan PVSYST - P50',
                    'type' => 'line',
                    'data' => $tbody_forcast_plan_PVSYSTP50,
                    'visualMap' => 'false',
                    'lineStyle' => array(
                        'type' => 'dashed'
                    )
                ],
                [
                    'name' => 'Plan PVSYST - P90',
                    'type' => 'line',
                    'data' => $tbody_forcast_plan_PVSYSTP90,
                    'visualMap' => 'false',
                    'lineStyle' => array(
                        'type' => 'dashed'
                    )
                ]
            ];

        $option = array(
            'color' => ['#c55a11', '#0070c0', '#70ad47', '#ff0000'],
            'title' => [
                'text' => 'Cumulative forecast plan PVSYST',
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

        $forecast_PVSYST = $chart->render('forecast_PVSYST', ['style' => 'height: 400px; width:21.7cm; margin-left:4.5cm;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        //End Cumulative Forecast with PVSYST


        //Beginn Cumulative Forecast with G4N
        //fuer die Tabelle

        //Forecast / PVSYST - P50
        //die ersten beiden Eintraege koennen von PVSYT uebernommen werden
        $forecast_G4N_table = [
            'forcast_PVSYSTP50' => $tbody_forcast_PVSYSTP50,
            'forcast_PVSYSTP90' => $tbody_forcast_PVSYSTP90,
            'forcast_plan_PVSYSTP50' => $tbody_forcast_plan_PVSYSTP50,
            'forcast_plan_PVSYSTP90' => $tbody_forcast_plan_PVSYSTP90,
        ];

        //beginn chart
        $chart->tooltip->show = true;
        $chart->tooltip->trigger = 'item';

        $chart->xAxis = array(
            'type' => 'category',
            'axisLabel' => array(
                'show' => true,
                'margin' => '0',
            ),
            'splitArea' => array(
                'show' => true,
            ),
            'data' => $dataMonthArray
        );
        $chart->yAxis = array(
            'type' => 'value',
            'min' => 0,
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 70
        );
        $chart->series =
            [
                [
                    'name' => 'Production ACT / g4n',
                    'type' => 'line',
                    'data' => $tbody_forcast_PVSYSTP50,
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Production ACT /  g4n - P90',
                    'type' => 'line',
                    'data' => $tbody_forcast_PVSYSTP90,
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Plan g4n Forecast - P50',
                    'type' => 'line',
                    'data' => $tbody_forcast_plan_PVSYSTP50,
                    'visualMap' => 'false',
                    'lineStyle' => array(
                        'type' => 'dashed'
                    )
                ],
                [
                    'name' => 'Plan g4n Forecast - P90',
                    'type' => 'line',
                    'data' => $tbody_forcast_plan_PVSYSTP90,
                    'visualMap' => 'false',
                    'lineStyle' => array(
                        'type' => 'dashed'
                    )
                ]
            ];

        $option = array(
            'color' => ['#c55a11', '#0070c0', '#70ad47', '#ff0000'],
            'title' => [
                'text' => 'Cumulative forecast plan g4n',
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

        $forecast_G4N = $chart->render('forecast_G4N', ['style' => 'height: 400px; width:21.7cm; margin-left:4.5cm;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        //End Cumulative Forecast with G4N


        //Beginn Cumulative Losses
        //fuer die Tabelle 2
        #losses

        for ($i = 0; $i < count($tbody_a_production['powerEvu']); $i++) {
            if ($i + 1 > $report['reportMonth']) {
                $diefference_prod_to_pvsyst[] = 0;
            } else {
                $diefference_prod_to_pvsyst[] = $tbody_a_production['powerEvu'][$i] - $tbody_a_production['expectedPvSyst'][$i];
            }
        }

        for ($i = 0; $i < count($tbody_a_production['powerEvu']); $i++) {
            $diefference_prod_to_expected_g4n[] = $tbody_a_production['powerEvu'][$i] - $tbody_a_production['powerExpEvu'][$i];
        }

        for ($i = 0; $i < count($tbody_a_production['powerEvu']); $i++) {
            $diefference_prod_to_egrid[] = $tbody_a_production['powerEvu'][$i] - $tbody_a_production['powerAct'][$i];
        }

        $losses_t2 = [
            'diefference_prod_to_pvsyst' => $diefference_prod_to_pvsyst,
            'diefference_prod_to_expected_g4n' => $diefference_prod_to_expected_g4n,
            'diefference_prod_to_egrid' => $diefference_prod_to_egrid,
        ];

        //beginn chart
        $chart->tooltip->show = true;
        $chart->tooltip->trigger = 'item';

        $chart->xAxis = array(
            'type' => 'category',
            'axisLabel' => array(
                'show' => true,
                'margin' => '0',
            ),
            'splitArea' => array(
                'show' => true,
            ),
            'data' => $dataMonthArray
        );
        $chart->yAxis = array(
            'type' => 'value',
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 70
        );
        $chart->series =
            [
                [
                    'name' => 'Difference Egrid to PVSYST',
                    'type' => 'line',
                    'data' => $diefference_prod_to_pvsyst,
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Difference Egrid to expected g4n',
                    'type' => 'line',
                    'data' => $diefference_prod_to_expected_g4n,
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Difference inverter to Egrid',
                    'type' => 'line',
                    'data' => $diefference_prod_to_egrid,
                    'visualMap' => 'false',
                ]
            ];

        $option = array(
            'color' => ['#0070c0', '#c55a11', '#a5a5a5'],
            'title' => [
                'text' => 'Monthly losses at plan values(PVSYS-g4n-INV))',
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
                    'height' => '70%',
                    'top' => 50,
                    'width' => '90%',
                ),
        );

        Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
        $chart->setOption($option);

        $losses_monthly = $chart->render('losses_monthly', ['style' => 'height: 400px; width:21.7cm; margin-left:4.5cm;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        //fuer die Tabelle 1
        #losses_monthly
        $kumsum1[0] = $diefference_prod_to_pvsyst[0];
        $kumsum2[0] = $diefference_prod_to_expected_g4n[0];
        $kumsum3[0] = $diefference_prod_to_egrid[0];
        for ($i = 0; $i < 12; $i++) {
            $kumsum1[$i] = $diefference_prod_to_pvsyst[$i] + $kumsum1[$i - 1];
            $kumsum2[$i] = $diefference_prod_to_expected_g4n[$i] + $kumsum2[$i - 1];
            $kumsum3[$i] = $diefference_prod_to_egrid[$i] + $kumsum3[$i - 1];
            if ($i + 1 > $report['reportMonth']) {
                $difference_Egrid_to_PVSYST[$i] = 0;
                $difference_Egrid_to_Expected_G4n[$i] = 0;
                $difference_Inverter_to_Egrid[$i] = 0;
            } else {
                $difference_Egrid_to_PVSYST[] = $kumsum1[$i];
                $difference_Egrid_to_Expected_G4n[] = $kumsum2[$i];
                $difference_Inverter_to_Egrid[] = $kumsum3[$i];
            }
        }

        $losses_t1 = [
            'difference_Egrid_to_PVSYST' => $difference_Egrid_to_PVSYST,
            'difference_Egrid_to_Expected_G4n' => $difference_Egrid_to_Expected_G4n,
            'difference_Inverter_to_Egrid' => $difference_Inverter_to_Egrid,
        ];

        //beginn chart
        $chart->tooltip->show = true;
        $chart->tooltip->trigger = 'item';

        $chart->xAxis = array(
            'type' => 'category',
            'axisLabel' => array(
                'show' => true,
                'margin' => '0',
            ),
            'splitArea' => array(
                'show' => true,
            ),
            'data' => $dataMonthArray
        );
        $chart->yAxis = array(
            'type' => 'value',
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 70
        );
        $chart->series =
            [
                [
                    'name' => 'Difference Egrid to PVSYST',
                    'type' => 'line',
                    'data' => $difference_Egrid_to_PVSYST,
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Difference Egrid to expected g4n',
                    'type' => 'line',
                    'data' => $difference_Egrid_to_Expected_G4n,
                    'visualMap' => 'false'
                ],
                [
                    'name' => 'Difference inverter to Egrid',
                    'type' => 'line',
                    'data' => $difference_Inverter_to_Egrid,
                    'visualMap' => 'false',
                ]
            ];

        $option = array(
            'color' => ['#0070c0', '#c55a11', '#a5a5a5'],
            'title' => [
                'text' => 'Comulative losses',
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
                    'height' => '70%',
                    'top' => 50,
                    'width' => '90%',
                ),
        );

        Config::addExtraScript('cool.js', 'https://dev.g4npvplus.net/echarts/theme/');
        $chart->setOption($option);

        $losses_year = $chart->render('losses_yearly', ['style' => 'height: 400px; width:21.7cm; margin-left:4.5cm;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

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
        ];

        return $output;

    }
}