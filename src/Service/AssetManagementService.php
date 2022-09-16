<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenPvSystMonth;
use App\Helper\G4NTrait;
use App\Repository\EconomicVarNamesRepository;
use App\Repository\EconomicVarValuesRepository;
use App\Repository\PvSystMonthRepository;
use App\Repository\TicketDateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Hisune\EchartsPHP\ECharts;
use PDO;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AssetManagementService
{
    use G4NTrait;

    private PDO $conn;

    private \mysqli $connAnlage;

    private EntityManagerInterface $em;

    private PvSystMonthRepository $pvSystMonthRepo;

    private EconomicVarValuesRepository $ecoVarValueRepo;

    private EconomicVarNamesRepository $ecoVarNameRepo;

    private FunctionsService $functions;

    private NormalizerInterface $serializer;

    private DownloadAnalyseService $DownloadAnalyseService;

    private PRCalulationService $PRCalulation;

    private AvailabilityService $availability;

    private TicketDateRepository $ticketDateRepo;

    public function __construct(
        EntityManagerInterface $em,
        PvSystMonthRepository $pvSystMonthRepo,
        FunctionsService $functions,
        NormalizerInterface $serializer,
        DownloadAnalyseService $analyseService,
        EconomicVarValuesRepository $ecoVarValueRep,
        PRCalulationService $PRCalulation,
        EconomicVarNamesRepository $ecoVarNameRep,
        AvailabilityService $availability,
        TicketDateRepository $ticketDateRepo
    ) {
        $this->functions = $functions;
        $this->em = $em;
        $this->pvSystMonthRepo = $pvSystMonthRepo;
        $this->ecoVarValueRepo = $ecoVarValueRep;
        $this->ecoVarNameRepo = $ecoVarNameRep;
        $this->serializer = $serializer;
        $this->conn = self::getPdoConnection();
        $this->connAnlage = self::connectToDatabaseAnlage();
        $this->DownloadAnalyseService = $analyseService;
        $this->PRCalulation = $PRCalulation;
        $this->availability = $availability;
        $this->ticketDateRepo = $ticketDateRepo;
    }

    public function assetReport($anlage, $month = 0, $year = 0, $pages = 0): array
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

        $report = [];
        $report['yesterday'] = $yesterday;
        $report['reportMonth'] = $reportMonth;
        $report['from'] = $from;
        $report['to'] = $to;
        $report['reportYear'] = $reportYear;

        $output = $this->buildAssetReport($anlage, $report);

        return $output;
    }

    private function getPvSystMonthData(Anlage $anlage, $month, $year): array
    {
        if ($anlage->hasPVSYST()) {
            $pvSystMonth = $this->pvSystMonthRepo->findOneBy(['anlage' => $anlage, 'month' => (int) $month]);
            if ($pvSystMonth) {
                $prPvSystMonth = $pvSystMonth->getPrDesign();
                $powerPvSyst = $pvSystMonth->getErtragDesign();
            } else {
                $prPvSystMonth = 0;
                $powerPvSyst = 0;
            }
            /** @var AnlagenPvSystMonth[] $pvSystYear */
            $pvSystYear = $this->pvSystMonthRepo->findAllYear($anlage, (int) $month);
            $powerPac = 0;
            $powerYear = 0;

            foreach ($pvSystYear as $pvSystYearValue) {
                $powerYear += $pvSystYearValue->getErtragDesign();
            }
            /** @var AnlagenPvSystMonth[] $pvSystPac */
            $pvSystPac = $this->pvSystMonthRepo->findAllPac($anlage, (int) $month);
            $anzRecordspvSystPac = count($pvSystPac);
            foreach ($pvSystPac as $pvSystPacValue) {
                if ((int) $anlage->getPacDate()->format('m') == $pvSystPacValue->getMonth() && $anzRecordspvSystPac < 12) {
                    $dayPac = (int) $anlage->getPacDate()->format('d');
                    $daysInMonthPac = (int) $anlage->getPacDate()->format('t');
                    $days = $daysInMonthPac - $dayPac + 1;
                    $powerPac += $pvSystPacValue->getErtragDesign() / $daysInMonthPac * $days;
                } else {
                    $powerPac += $pvSystPacValue->getErtragDesign();
                }
            }

            return [
                'prMonth' => $prPvSystMonth,
                'prPac' => $anlage->getDesignPR(),
                'prYear' => $anlage->getDesignPR(),
                'powerMonth' => $powerPvSyst,
                'powerPac' => $powerPac,
                'powerYear' => $powerYear,
            ];
        } else {
            return [
            'prMonth' => 0,
            'prPac' => 0,
            'prYear' => 0,
            'powerMonth' => 0,
            'powerPac' => 0,
            'powerYear' => 0,
        ];
        }
    }

    /**
     * @param int  $docType ( 0 = PDF, 1 = Excel, 2 = PNG (Grafiken))
     * @param int  $pages   ( 0 = , 1 = )
     * @param bool $exit
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function buildAssetReport(Anlage $anlage, array $report): array
    {
        $useGridMeterDayData = $anlage->getUseGridMeterDayData();
        $showAvailability = $anlage->getShowAvailability();
        $showAvailabilitySecond = $anlage->getShowAvailabilitySecond();
        $plantSize = $anlage->getPnom();
        $plantName = $anlage->getAnlName();
        $anlGeoLat = $anlage->getAnlGeoLat();
        $anlGeoLon = $anlage->getAnlGeoLon();
        $owner = $anlage->getEigner()->getFirma();
        $plantId = $anlage->getAnlId();

        $monthName = date('F', mktime(0, 0, 0, $report['reportMonth'], 10));
        $currentYear = date('Y');
        $currentMonth = date('m');

        if ($report['reportMonth'] < 10) {
            $report['reportMonth'] = str_replace(0, '', $report['reportMonth']);
        }

        $daysInReportMonth = cal_days_in_month(CAL_GREGORIAN, $report['reportMonth'], $report['reportYear']);

        $monthArray = [
            'Jan', 'Feb', 'Mar', 'April', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dez',
        ];
        for ($i = 0; $i < count($monthArray); ++$i) {
            $monthExtendetArray[$i]['month'] = $monthArray[$i];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $i + 1, $report['reportYear']);
            $monthExtendetArray[$i]['days'] = $daysInMonth;
            $monthExtendetArray[$i]['hours'] = $daysInMonth * 24;
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

            // Das hier ist noetig da alle 12 Monate benötigt werden
            if ($anlage->hasPVSYST()) {
                $resultErtrag_design = $this->pvSystMonthRepo->findOneMonth($anlage, $i);
            } else {
                $resultErtrag_design = 0;
            }
            if ($resultErtrag_design) {
                $Ertrag_design = $resultErtrag_design->getErtragDesign();
            }

            if ($i > $report['reportMonth']) {
                $data1_grid_meter['powerEvu'] = 0;
                $data1_grid_meter['powerAct'] = 0; // Inv out
                $data1_grid_meter['powerExp'] = 0;
                $data1_grid_meter['powerExpEvu'] = 0;
                $data1_grid_meter['powerEGridExt'] = 0;
                $Ertrag_design = 0;
            }
            if ($anlage->getShowEvuDiag()) {
                (float) $powerEvu[] = $data1_grid_meter['powerEvu']; // read comment in line
                (float) $powerAct[] = $data1_grid_meter['powerAct']; // Inv out
                (float) $powerExp[] = $data1_grid_meter['powerExp'];
                (float) $powerExpEvu[] = $data1_grid_meter['powerExpEvu'];
                (float) $powerExternal[] = $data1_grid_meter['powerEGridExt'];
            } else {
                (float) $powerEvu[] = $data1_grid_meter['powerAct']; // read comment in line
                (float) $powerAct[] = $data1_grid_meter['powerAct']; // Inv out
                (float) $powerExp[] = $data1_grid_meter['powerExp'];
                (float) $powerExpEvu[] = $data1_grid_meter['powerExp'];
                (float) $powerExternal[] = $data1_grid_meter['powerEGridExt'];
            }

//            if ($anlage->hasPVSYST()) $pvSyst = $this->pvSystMonthRepo->findOneMonth($anlage, $i);
//            else $pvSyst = 0;
            $dataMonthArray[] = $monthArray[$i - 1];
            $expectedPvSyst[] = $Ertrag_design;

//            unset($pvSyst);
        }

        for ($i = 1; $i <= 12; ++$i) {
            $dataMonthArrayFullYear[] = $monthArray[$i - 1];
        }

        // fuer die Tabelle
        $tbody_a_production = [
            'powerEvu' => $powerEvu,
            'powerAct' => $powerAct,
            'powerExp' => $powerExp,
            'expectedPvSyst' => $expectedPvSyst,
            'powerExpEvu' => $powerExpEvu,
            'powerExt' => $powerExternal,
        ];

        // fuer die Tabelle Capacity Factor

        for ($i = 0; $i < count($monthExtendetArray); ++$i) {
            $dataCfArray[$i]['month'] = $monthExtendetArray[$i]['month'];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $i + 1, $report['reportYear']);
            $dataCfArray[$i]['days'] = $daysInMonth;
            $dataCfArray[$i]['hours'] = $daysInMonth * 24;
            $dataCfArray[$i]['cf'] = ($tbody_a_production['powerEvu'][$i] / 1000) / (($plantSize / 1000) * ($daysInMonth * 24)) * 100;
        }
        // chart building, skip to line 950
        // begin chart
        $chart = new ECharts(); // We must use AMCharts
        $chart->tooltip->show = true;
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
            'data' => array_slice($dataMonthArray, 0, $report['reportMonth']),
        ];
        $chart->yAxis = [
            'type' => 'value',
            'min' => 0,
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 80,
            'offset' => -20,
        ];
        if ($anlage->hasPVSYST() === true) {
            if ($anlage->hasGrid()) {
                $chart->series =
                    [
                        [
                            'name' => 'Yield (Grid meter)',
                            'type' => 'bar',
                            'data' => $powerEvu,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Expected PVSYST',
                            'type' => 'bar',
                            'data' => $expectedPvSyst,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Expected g4n',
                            'type' => 'bar',
                            'data' => $powerExp,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Inverter out',
                            'type' => 'bar',
                            'data' => $powerAct,
                            'visualMap' => 'false',
                        ],
                    ];
            } else {
                $chart->series =
                    [
                        [
                            'name' => 'Expected PVSYST',
                            'type' => 'bar',
                            'data' => $expectedPvSyst,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Expected g4n',
                            'type' => 'bar',
                            'data' => $powerExp,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Inverter out',
                            'type' => 'bar',
                            'data' => $powerAct,
                            'visualMap' => 'false',
                        ],
                    ];
            }
        } else {
            if ($anlage->hasGrid()) {
                $chart->series =
                    [
                        [
                            'name' => 'Yield (Grid meter)',
                            'type' => 'bar',
                            'data' => $powerEvu,
                            'visualMap' => 'false',
                        ],

                        [
                            'name' => 'Expected g4n',
                            'type' => 'bar',
                            'data' => $powerExp,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Inverter out',
                            'type' => 'bar',
                            'data' => $powerAct,
                            'visualMap' => 'false',
                        ],
                    ];
            } else {
                $chart->series =
                    [
                        [
                            'name' => 'Expected g4n',
                            'type' => 'bar',
                            'data' => $powerExp,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Inverter out',
                            'type' => 'bar',
                            'data' => $powerAct,
                            'visualMap' => 'false',
                        ],
                    ];
            }
        }

        $option = [
            'animation' => false,
            'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000'],
            'title' => [
                'text' => 'Year '.$report['reportYear'],
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
                    'width' => '80%',
                    'left' => 100,
                ],
        ];

        $chart->setOption($option);

        $operations_right = $chart->render('operations_right', ['style' => 'height: 450px; width:700px;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        // End Production

        // Beginn Cumulative Forecast with PVSYST
        // fuer die Tabelle

        // Forecast / degradation
        for ($i = 0; $i < 12; ++$i) {
            $forecast[$i] = $this->functions->getForcastByMonth($anlage, $i);
        }
        unset($kumsum);
        $degradation = $anlage->getLossesForecast();
        // Cumulative Forecast
        $kumsum[0] = $powerEvu[0];
        for ($i = 0; $i < 12; ++$i) {
            if ($i + 1 > $report['reportMonth']) {
                if ($expectedPvSyst[$i]) {
                    $kumsum[$i] = $expectedPvSyst[$i] + $kumsum[$i - 1];
                } else {
                    $kumsum[$i] = $forecast[$i] + $kumsum[$i - 1];
                }
            } else {
                $kumsum[$i] = $powerEvu[$i] + $kumsum[$i - 1];
            }
            $tbody_forcast_PVSYSTP50[] = $kumsum[$i];

            $tbody_forcast_PVSYSTP90[] = $kumsum[$i] - ($kumsum[$i] * $degradation / 100);
        }
        unset($kumsum);
        // Forecast / PVSYST - P90
        $kumsum[0] = $expectedPvSyst[0];
        for ($i = 0; $i < 12; ++$i) {
            if (!$expectedPvSyst[$i]) {
                $kumsum[$i] = $forecast[$i] + $kumsum[$i - 1];
            } else {
                $kumsum[$i] = $expectedPvSyst[$i] + $kumsum[$i - 1];
            }
            $tbody_forcast_plan_PVSYSTP50[] = $kumsum[$i];

            $tbody_forcast_plan_PVSYSTP90[] = $kumsum[$i] - ($kumsum[$i] * $degradation / 100);
        }

        $forecast_PVSYST_table = [
            'forcast_PVSYSTP50' => $tbody_forcast_PVSYSTP50,
            'forcast_PVSYSTP90' => $tbody_forcast_PVSYSTP90,
            'forcast_plan_PVSYSTP50' => $tbody_forcast_plan_PVSYSTP50,
            'forcast_plan_PVSYSTP90' => $tbody_forcast_plan_PVSYSTP90,
        ];

        // beginn chart
        // # $chart->tooltip->show = true;
        // # $chart->tooltip->trigger = 'item';

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '0',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $dataMonthArrayFullYear,
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
                    'name' => 'Production ACT / PVSYST - P50',
                    'type' => 'line',
                    'data' => $tbody_forcast_PVSYSTP50,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Production ACT / PVSYST - P90',
                    'type' => 'line',
                    'data' => $tbody_forcast_PVSYSTP90,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Plan PVSYST - P50',
                    'type' => 'line',
                    'data' => $tbody_forcast_plan_PVSYSTP50,
                    'visualMap' => 'false',
                    'lineStyle' => [
                        'type' => 'dashed',
                    ],
                ],
                [
                    'name' => 'Plan PVSYST - P90',
                    'type' => 'line',
                    'data' => $tbody_forcast_plan_PVSYSTP90,
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
                'text' => 'Cumulative forecast plan PVSYST',
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
                    'width' => '85%',
                    'left' => 100,
                ],
        ];

        $chart->setOption($option);

        $forecast_PVSYST = $chart->render('forecast_PVSYST', ['style' => 'height: 450px; width:28cm;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        // End Cumulative Forecast with PVSYST

        // Beginn Cumulative Forecast with G4N
        // fuer die Tabelle

        $kumsum[0] = $powerEvu[0];
        for ($i = 0; $i < 12; ++$i) {
            if ($i + 1 > $report['reportMonth']) {
                if ($powerExpEvu[$i]) {
                    $kumsum[$i] = $powerExpEvu[$i] + $kumsum[$i - 1];
                } else {
                    $kumsum[$i] = $forecast[$i] + $kumsum[$i - 1];
                }
            } else {
                $kumsum[$i] = $powerEvu[$i] + $kumsum[$i - 1];
            }
            $tbody_forcast_G4NP50[] = $kumsum[$i];

            $tbody_forcast_G4NP90[] = $kumsum[$i] - ($kumsum[$i] * $degradation / 100);
        }
        // Forecast / G4N

        $kumsum[0] = $powerExpEvu[0];
        for ($i = 0; $i < 12; ++$i) {
            if ($powerExpEvu[$i]) {
                $kumsum[$i] = $powerExpEvu[$i] + $kumsum[$i - 1];
            } else {
                $kumsum[$i] = $forecast[$i] + $kumsum[$i - 1];
            }
            $tbody_forcast_plan_G4NP50[] = $kumsum[$i];
            $tbody_forcast_plan_G4NP90[] = $kumsum[$i] - ($kumsum[$i] * $degradation / 100);
        }

        $forecast_G4N_table = [
            'forcast_G4NP50' => $tbody_forcast_G4NP50,
            'forcast_G4NP90' => $tbody_forcast_G4NP90,
            'forcast_plan_G4NP50' => $tbody_forcast_plan_G4NP50,
            'forcast_plan_G4NP90' => $tbody_forcast_plan_G4NP90,
        ];
        // beginn chart
        // $chart->tooltip->show = true;
        // $chart->tooltip->trigger = 'item';

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '0',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $dataMonthArrayFullYear,
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
                    'name' => 'Plan g4n Forecast - P50',
                    'type' => 'line',
                    'data' => $tbody_forcast_plan_G4NP50,
                    'visualMap' => 'false',
                    'lineStyle' => [
                        'type' => 'dashed',
                    ],
                ],
                [
                    'name' => 'Plan g4n Forecast - P90',
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
                'text' => 'Cumulative forecast plan g4n',
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
                    'width' => '85%',
                    'left' => 100,
                ],
        ];

        $chart->setOption($option);

        $forecast_G4N = $chart->render('forecast_G4N', ['style' => 'height: 450px; width:28cm;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        // End Cumulative Forecast with G4N

        // Beginn Cumulative Losses
        // fuer die Tabelle 2
        // losses

        for ($i = 0; $i < 12; ++$i) {
            if ($i < count($tbody_a_production['powerEvu'])) {
                if ($i + 1 > $report['reportMonth']) {
                    $diefference_prod_to_pvsyst[] = 0;
                } else {
                    if ($anlage->getShowEvuDiag()) {
                        if ($anlage->getUseGridMeterDayData()) {
                            $diefference_prod_to_pvsyst[] = $tbody_a_production['powerExt'][$i] - $tbody_a_production['expectedPvSyst'][$i];
                        } else {
                            $diefference_prod_to_pvsyst[] = $tbody_a_production['powerEvu'][$i] - $tbody_a_production['expectedPvSyst'][$i];
                        }
                    } else {
                        $diefference_prod_to_pvsyst[] = $tbody_a_production['powerAct'][$i] - $tbody_a_production['expectedPvSyst'][$i];
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
                        $diefference_prod_to_egrid[] = $tbody_a_production['powerExt'][$i] - $tbody_a_production['powerAct'][$i];
                    } else {
                        $diefference_prod_to_egrid[] = $tbody_a_production['powerEvu'][$i] - $tbody_a_production['powerAct'][$i];
                    }
                } else {
                    $diefference_prod_to_egrid[] = $tbody_a_production['powerAct'][$i] - $tbody_a_production['powerAct'][$i];
                }
            } else {
                $diefference_prod_to_egrid[] = 0;
            }
        }

        $losses_t2 = [
            'diefference_prod_to_pvsyst' => $diefference_prod_to_pvsyst,
            'diefference_prod_to_expected_g4n' => $diefference_prod_to_expected_g4n,
            'diefference_prod_to_egrid' => $diefference_prod_to_egrid,
        ];

        // beginn chart
        // $chart->tooltip->show = true;
        // $chart->tooltip->trigger = 'item';

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '0',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $dataMonthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 80,
        ];
        $chart->series =
            [
                [
                    'name' => 'Difference Egrid to PVSYST',
                    'type' => 'line',
                    'data' => $diefference_prod_to_pvsyst,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Difference Egrid to expected g4n',
                    'type' => 'line',
                    'data' => $diefference_prod_to_expected_g4n,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Difference inverter to Egrid',
                    'type' => 'line',
                    'data' => $diefference_prod_to_egrid,
                    'visualMap' => 'false',
                ],
            ];

        $option = [
            'animation' => false,
            'color' => ['#0070c0', '#c55a11', '#a5a5a5'],
            'title' => [
                'text' => 'Monthly losses at plan values',
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
                    'width' => '100%',
                    'left' => 100,
                ],
        ];

        $chart->setOption($option);

        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        // fuer die Tabelle 1

        for ($i = 0; $i < count($diefference_prod_to_pvsyst); ++$i) {
            if ($i == 0) {
                $kumsum1[0] = $diefference_prod_to_pvsyst[0];
                $kumsum2[0] = $diefference_prod_to_expected_g4n[0];
                $kumsum3[0] = $diefference_prod_to_egrid[0];
            } else {
                $kumsum1[$i] = $diefference_prod_to_pvsyst[$i] + $kumsum1[$i - 1];
                $kumsum2[$i] = $diefference_prod_to_expected_g4n[$i] + $kumsum2[$i - 1];
                $kumsum3[$i] = $diefference_prod_to_egrid[$i] + $kumsum3[$i - 1];
            }
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

        // beginn chart
        // $chart->tooltip->show = true;
        // $chart->tooltip->trigger = 'item';
        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $dataMonthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 70,
        ];
        if ($anlage->hasPVSYST()) {
            if ($anlage->hasGrid()) {
                $chart->series =
                    [
                        [
                            'name' => 'Difference Grid to PVSYST',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_PVSYST,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference Grid to expected g4n',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_Expected_G4n,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference inverter to Grid',
                            'type' => 'line',
                            'data' => $difference_Inverter_to_Egrid,
                            'visualMap' => 'false',
                        ],
                    ];
            } else {
                $chart->series =
                    [
                        [
                            'name' => 'Difference Inverter to PVSYST',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_PVSYST,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference Inverter to expected g4n',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_Expected_G4n,
                            'visualMap' => 'false',
                        ],
                    ];
            }
        } else {
            if ($anlage->hasGrid()) {
                $chart->series =
                    [
                        [
                            'name' => 'Difference Grid to expected g4n',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_Expected_G4n,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference inverter to Grid',
                            'type' => 'line',
                            'data' => $difference_Inverter_to_Egrid,
                            'visualMap' => 'false',
                        ],
                    ];
            } else {
                $chart->series =
                    [
                        [
                            'name' => 'Difference Inverter to expected g4n',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_Expected_G4n,
                            'visualMap' => 'false',
                        ],
                    ];
            }
        }

        $option = [
            'animation' => false,
            'color' => ['#0070c0', '#c55a11', '#a5a5a5'],
            'title' => [
                'text' => 'Cumulative losses',
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
                    'width' => '90%',
                ],
        ];

        $chart->setOption($option);

        $losses_year = $chart->render('losses_yearly', ['style' => 'height: 450px; width: 23cm;']);

        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];

        unset($option);
        // End Cumulative Losses

        // Start Monthley expected vs.actuals
        // $chart->tooltip->show = true;

        // $chart->tooltip->trigger = 'item';

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => false,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => [],
            'scale' => true,
            'min' => 0,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 80,
            'scale' => true,
            'min' => 0,
        ];
        if ($anlage->hasPVSYST()) {
            if ($anlage->hasGrid()) {
                $chart->series =
                    [
                        [
                            'name' => 'Yield (Grid meter)',
                            'type' => 'bar',
                            'data' => [
                                $powerEvu[$report['reportMonth'] - 1],
                            ],
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Expected PV SYST',
                            'type' => 'bar',
                            'data' => [
                                $expectedPvSyst[$report['reportMonth'] - 1],
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
                            'name' => 'Inverter out',
                            'type' => 'bar',
                            'data' => [
                                $powerAct[$report['reportMonth'] - 1],
                            ],
                            'visualMap' => 'false',
                        ],
                    ];
            } else {
                $chart->series =
                    [
                        [
                            'name' => 'Expected PV SYST',
                            'type' => 'bar',
                            'data' => [
                                $expectedPvSyst[$report['reportMonth'] - 1],
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
                            'name' => 'Inverter out',
                            'type' => 'bar',
                            'data' => [
                                $powerAct[$report['reportMonth'] - 1],
                            ],
                            'visualMap' => 'false',
                        ],
                    ];
            }
        } else {
            if ($anlage->hasGrid()) {
                $chart->series =
                    [
                        [
                            'name' => 'Yield (Grid meter)',
                            'type' => 'bar',
                            'data' => [
                                $powerEvu[$report['reportMonth'] - 1],
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
                            'name' => 'Inverter out',
                            'type' => 'bar',
                            'data' => [
                                $powerAct[$report['reportMonth'] - 1],
                            ],
                            'visualMap' => 'false',
                        ],
                    ];
            } else {
                $chart->series =
                    [
                        [
                            'name' => 'Expected g4n',
                            'type' => 'bar',
                            'data' => [
                                $powerExp[$report['reportMonth'] - 1],
                            ],
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Inverter out',
                            'type' => 'bar',
                            'data' => [
                                $powerAct[$report['reportMonth'] - 1],
                            ],
                            'visualMap' => 'false',
                        ],
                    ];
            }
        }
        $option = [
            'yaxis' => ['scale' => false, 'min' => 0],
            'animation' => false,
            'color' => ['#698ed0', '#f1975a', '#c5e0b4', '#ffc000'],
            'title' => [
                'text' => $monthName.' '.$report['reportYear'],
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
                    'height' => '100%',
                    'top' => 80,
                    'left' => 90,
                    'width' => '80%',
                ],
        ];

        $chart->setOption($option);
        $production_monthly_chart = $chart->render('production_monthly_chart', ['style' => 'height: 310px; width:100%;']);

        $chart->tooltip = [];
        $chart->xAxis = [];

        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        // Tabelle rechts oben

        $operations_monthly_right_pvsyst_tr1 = [
            $monthName.' '.$report['reportYear'],
            $powerEvu[$report['reportMonth'] - 1],
            $expectedPvSyst[$report['reportMonth'] - 1],
            $powerEvu[$report['reportMonth'] - 1] - $expectedPvSyst[$report['reportMonth'] - 1],
            round((1 - $expectedPvSyst[$report['reportMonth'] - 1] / $powerEvu[$report['reportMonth'] - 1]) * 100, 2),
        ];

        // Parameter fuer die Berechnung Q1
        $start = $report['reportYear'].'-01-01 00:00';

        $end = $report['reportMonth'] >= '3' ? $report['reportYear'].'-03-31 23:59' : $report['to'];
        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        if ($anlage->getShowEvuDiag()) {
            $powerEvuQ1 = $data2_grid_meter['powerEvu'];
        } else {
            $powerEvuQ1 = $data2_grid_meter['powerAct'];
        }
        if ((($currentYear == $report['reportYear'] || $currentYear > $report['reportYear']) && $powerEvuQ1 > 0) && $anlage->hasPVSYST()) {
            $expectedPvSystQ1 = 0;

            if ($report['reportMonth'] >= '3') {
                $expectedPvSystQ1 = $tbody_a_production['expectedPvSyst'][0] + $tbody_a_production['expectedPvSyst'][1] + $tbody_a_production['expectedPvSyst'][2];
            } else {
                for ($i = 0; $i <= intval($report['reportMonth']); ++$i) {
                    $expectedPvSystQ1 += $tbody_a_production['expectedPvSyst'][$i];
                }
            }

            $operations_monthly_right_pvsyst_tr2 = [
                $powerEvuQ1,
                $expectedPvSystQ1,
                abs($powerEvuQ1 - $expectedPvSystQ1),
                round((1 - $expectedPvSystQ1 / $powerEvuQ1) * 100, 2),
            ];
        } else {
            $operations_monthly_right_pvsyst_tr2 = [
                $powerEvuQ1,
                '0',
                '0',
                '0',
            ];
        }

        // Parameter fuer die Berechnung Q2
        $start = $report['reportYear'].'-04-01 00:00';
        $end = $report['reportMonth'] >= '6' ? $report['reportYear'].'-06-30 23:59' : $report['to'];

        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        if ($anlage->getShowEvuDiag()) {
            $powerEvuQ2 = $data2_grid_meter['powerEvu'];
        } else {
            $powerEvuQ2 = $data2_grid_meter['powerAct'];
        }

        if (((($currentYear == $report['reportYear'] && $currentMonth > 3) || $currentYear > $report['reportYear']) && $powerEvuQ2 > 0) && $anlage->hasPVSYST()) {
            if ($report['reportMonth'] >= '6') {
                $expectedPvSystQ2 = $tbody_a_production['expectedPvSyst'][3] + $tbody_a_production['expectedPvSyst'][4] + $tbody_a_production['expectedPvSyst'][5];
            } else {
                for ($i = 3; $i <= intval($report['reportMonth']); ++$i) {
                    $expectedPvSystQ2 += $tbody_a_production['expectedPvSyst'][$i];
                }
            }

            $operations_monthly_right_pvsyst_tr3 = [
                $powerEvuQ2,
                $expectedPvSystQ2,
                $powerEvuQ2 - $expectedPvSystQ2,
                round((1 - $expectedPvSystQ2 / $powerEvuQ2) * 100, 2),
            ];
        } else {
            $operations_monthly_right_pvsyst_tr3 = [
                $powerEvuQ2,
                '0',
                '0',
                '0',
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
        if (((($currentYear == $report['reportYear'] && $currentMonth > 6) || $currentYear > $report['reportYear']) && $powerEvuQ3 > 0) && $anlage->hasPVSYST()) {
            if ($report['reportMonth'] >= '9') {
                $expectedPvSystQ3 = $tbody_a_production['expectedPvSyst'][6] + $tbody_a_production['expectedPvSyst'][7] + $tbody_a_production['expectedPvSyst'][8];
            } else {
                for ($i = 6; $i <= intval($report['reportMonth']); ++$i) {
                    $expectedPvSystQ3 += $tbody_a_production['expectedPvSyst'][$i];
                }
            }

            $operations_monthly_right_pvsyst_tr4 = [
                $powerEvuQ3,
                $expectedPvSystQ3,
                $powerEvuQ3 - $expectedPvSystQ3,
                round((1 - $expectedPvSystQ3 / $powerEvuQ3) * 100, 2),
            ];
        } else {
            $operations_monthly_right_pvsyst_tr4 = [
                $powerEvuQ3,
                '0',
                '0',
                '0',
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
        if (($currentYear > $report['reportYear'] && $powerEvuQ4 > 0) && $anlage->hasPVSYST()) {
            for ($i = 9; $i <= intval($report['reportMonth']); ++$i) {
                $expectedPvSystQ4 += $tbody_a_production['expectedPvSyst'][$i];
            }

            $operations_monthly_right_pvsyst_tr5 = [
                $powerEvuQ4,
                $expectedPvSystQ4,
                $powerEvuQ4 - $expectedPvSystQ4,
                round((1 - $expectedPvSystQ4 / $powerEvuQ4) * 100, 2),
            ];
        } else {
            $operations_monthly_right_pvsyst_tr5 = [
                $powerEvuQ4,
                '0',
                '0',
                '0',
            ];
        }

        // Year to date

        $monthPacDate = $anlage->getPacDate()->format('m');
        $yearPacDate = $anlage->getPacDate()->format('Y');

        $start = $report['reportYear'].'-01-01 00:00';
        $end = $report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInReportMonth.' 23:59';
        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        $powerEvuYtoD = $data2_grid_meter['powerEvu'];

        if (($powerEvuYtoD > 0 && !($yearPacDate == $report['reportYear'] && $monthPacDate > $report['reportMonth'])) && $anlage->hasPVSYST()) {
            // Part 1 Year to Date
            if ($yearPacDate == $report['reportYear']) {
                $month = $monthPacDate;
            } else {
                $month = '1';
            }

            $resultErtrag_design = $this->pvSystMonthRepo->findOneByInterval($anlage, $month, $report['reportMonth']);
            if ($resultErtrag_design) {
                $expectedPvSystYtoDFirst = $resultErtrag_design['ertrag_design'];
            }

            $operations_monthly_right_pvsyst_tr6 = [
                $powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4,
                $expectedPvSystYtoDFirst,
                $powerEvuYtoD - $expectedPvSystYtoDFirst,
                (1 - $expectedPvSystYtoDFirst / $powerEvuYtoD) * 100,
            ];
        } else {
            $operations_monthly_right_pvsyst_tr6 = [
                $powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4,
                '0',
                '0',
                '0',
            ];
        }

        // Gesamte Laufzeit

        $operations_monthly_right_pvsyst_tr7 = [
            0.00,
            0.00,
            0.00,
            0.00,
        ];
        // Ende Tabelle rechts oben

        // Tabelle rechts mitte

        $operations_monthly_right_g4n_tr1 = [
            $monthName.' '.$report['reportYear'],
            $powerEvu[$report['reportMonth'] - 1],
            $powerExpEvu[$report['reportMonth'] - 1],
            $powerEvu[$report['reportMonth'] - 1] - $powerExpEvu[$report['reportMonth'] - 1],
            (1 - $powerExpEvu[$report['reportMonth'] - 1] / $powerEvu[$report['reportMonth'] - 1]) * 100,
        ];

        // Parameter fuer die Berechnung Q1
        if (($currentYear == $report['reportYear'] || $currentYear > $report['reportYear']) && $powerEvuQ1 > 0) {
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
        if ((($currentYear == $report['reportYear'] && $currentMonth > 3) || $currentYear > $report['reportYear']) && $powerEvuQ2 > 0) {
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
        if ((($currentYear == $report['reportYear'] && $currentMonth > 6) || $currentYear > $report['reportYear']) && $powerEvuQ3 > 0) {
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
        if ($currentYear > $report['reportYear'] && $powerEvuQ4 > 0) {
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
            $difference = ($y * 100) / $x;
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

        // Tabelle rechts unten
        $operations_monthly_right_iout_tr1 = [
            $monthName.' '.$report['reportYear'],
            $powerEvu[$report['reportMonth'] - 1],
            $powerAct[$report['reportMonth'] - 1],
            $powerEvu[$report['reportMonth'] - 1] - $powerAct[$report['reportMonth'] - 1],
            (1 - $powerAct[$report['reportMonth'] - 1] / $powerEvu[$report['reportMonth'] - 1]) * 100,
        ];

        // Parameter fuer die Berechnung Q1
        if (($currentYear == $report['reportYear'] && $currentMonth > 3) || $currentYear > $report['reportYear'] && $powerEvuQ1 > 0) {
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
        if ((($currentYear == $report['reportYear'] && $currentMonth > 6) || $currentYear > $report['reportYear']) && $powerEvuQ2 > 0) {
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
        if ((($currentYear == $report['reportYear'] && $currentMonth > 9) || $currentYear > $report['reportYear']) && $powerEvuQ3 > 0) {
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
        if ($currentYear > $report['reportYear'] && $powerEvuQ4 > 0) {
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

        // Parameter fuer Year to Date
        if (!($yearPacDate == $report['reportYear'] && $monthPacDate > $currentMonth)) {
            $x = $powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4;
            $y = ($powerEvuQ1 + $powerEvuQ2 + $powerEvuQ3 + $powerEvuQ4) - ($temp_q1 + $temp_q2 + $temp_q3 + $temp_q4);
            $difference = ($y * 100) / $x;
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

        $output = $this->DownloadAnalyseService->getAllSingleSystemData($anlage, $report['reportYear'], $report['reportMonth'], 2);
        $dcData = $this->DownloadAnalyseService->getDcSingleSystemData($anlage, $start, $end, '%d.%m.%Y');
        $dcDataExpected = $this->DownloadAnalyseService->getEcpectedDcSingleSystemData($anlage, $start, $end, '%d.%m.%Y');

        if ($output) {
            for ($i = 0; $i < count($output); ++$i) {
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
                        'panneltemp' => (float) $output[$i]->getpanneltemp(),
                    ];
            }
        }

        // End Operations dayly

        // Fuer die PA des aktuellen Jahres
        $daysInThisMonth = cal_days_in_month(CAL_GREGORIAN, $report['reportMonth'], $report['reportYear']);
        if ($anlage->getConfigType() == 1) {
            // Type 1 is the only one where acGrops are NOT the Inveerter
            $inverters = $anlage->getGroups()->count();
        } else {
            // use acGroups as Inverter
            $inverters = $anlage->getAcGroups()->count();
        }
        for ($inverter = 0; $inverter < $inverters; ++$inverter) {
            $pa = [];
            for ($tempMonth = 1; $tempMonth <= $report['reportMonth']; ++$tempMonth) {
                $daysInThisMonth = cal_days_in_month(CAL_GREGORIAN, $tempMonth, $report['reportYear']);
                $startDate = new \DateTime($report['reportYear']."-$tempMonth-01 00:00");
                $endDate = new \DateTime($report['reportYear']."-$tempMonth-$daysInThisMonth 00:00");
                $pa[] = [
                    'form_date' => $tempMonth,
                    'pa' => $this->availability->calcAvailability($anlage, $startDate, $endDate, $inverter),
                    'unit' => $inverter,
                ];
            }
            $outPaCY[] = $pa;
            unset($pa);
        }

        // we have to generate the overall values of errors for the year
        $daysInThisMonth = cal_days_in_month(CAL_GREGORIAN, $report['reportMonth'], $report['reportYear']);
        $endate = $report['reportYear'].'-'.$report['reportMonth'].'-'.$daysInThisMonth;
        $SOFErrors = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 10, $anlage)[0][1];
        $EFORErrors = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 20, $anlage)[0][1];
        $OMCErrors = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 30, $anlage)[0][1];
        $dataGaps = (int) $this->ticketDateRepo->countByIntervalNullPlant($report['reportYear'].'-01-01', $endate, $anlage)[0][1];
        $totalErrors = $SOFErrors + $EFORErrors + $OMCErrors;
        // here we calculate the ammount of quarters to calculate the relative percentages
        $sumquarters = 0;
        for ($month = 1; $month <= (int) $report['reportMonth']; ++$month) {
            $begin = $report['reportYear'].'-'.$month.'-'.'01 00:00:00';
            $lastDayOfMonth = date('t', strtotime($begin));
            $end = $report['reportYear'].'-'.$month.'-'.$lastDayOfMonth.' 23:55:00';
            $sqlw = 'SELECT count(db_id) as quarters
                    FROM  '.$anlage->getDbNameWeather()."  
                    WHERE stamp BETWEEN '$begin' AND '$end' AND (g_lower + g_upper)/2 > '$anlage->getThreshold2PA()'";// hay que cambiar aqui para que la radiacion sea mayor que un valor

            $resw = $this->conn->query($sqlw);
            $quartersInMonth = $resw->fetch(PDO::FETCH_ASSOC)['quarters'] * $anlage->getAnzInverter();
            $sumquarters = $sumquarters + $quartersInMonth;
        }
        $sumLossesYearSOR = 0;
        $sumLossesYearEFOR = 0;
        $sumLossesYearOMC = 0;
        foreach ($this->ticketDateRepo->getAllByInterval($report['reportYear'].'-01-01', $end,$anlage) as $date){
            $intervalBegin = date("Y-m-d H:i",$date->getBegin()->getTimestamp());
            $intervalEnd = date("Y-m-d H:i",$date->getEnd()->getTimestamp());

            //$inverter = $date->getInverter();
            dump($date);
            foreach($date->getInverterArray() as $inverter) {
                switch ($anlage->getConfigType()) { // we need this to query for the inverter in the SOR and EFOR cases, in the OMC case the whole plant is down

                    case 1 :
                        $inverterQuery = "group_dc = $inverter";
                        break;
                    default:
                        $inverterQuery = "group_ac = $inverter";
                }

                if ($date->getErrorType() == 10) {
                    $sqlActual = "SELECT sum(wr_pac) as power
                            FROM " . $anlage->getDbNameIst() . " 
                            WHERE wr_pac >= 0 AND stamp >= '$intervalBegin' AND stamp < '$intervalEnd' AND ". $inverterQuery;

                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd' AND ". $inverterQuery;
                    dump($sqlActual);
                    $resAct = $this->conn->query($sqlActual);
                    $resExp = $this->conn->query($sqlExpected);

                    if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                    else $exp = 0;
                    if ($resAct->rowCount() > 0) $actual = $resAct->fetch(PDO::FETCH_ASSOC)['power'];
                    else $actual = 0;
                    $sumLossesYearSOR = $sumLossesYearSOR - ($actual - $exp);
                } else if ($date->getErrorType() == 20) {
                    $sqlActual = "SELECT sum(wr_pac) as power
                            FROM " . $anlage->getDbNameIst() . " 
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd' AND $inverterQuery";
                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd' AND $inverterQuery";
                    $resAct = $this->conn->query($sqlActual);
                    $resExp = $this->conn->query($sqlExpected);

                    if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                    else $exp = 0;
                    if ($resAct->rowCount() > 0) $actual = $resAct->fetch(PDO::FETCH_ASSOC)['power'];
                    else $actual = 0;
                    $sumLossesYearEFOR = $sumLossesYearEFOR - ($actual - $exp);
                } else if ($date->getErrorType() == 30) {
                    $sqlActual = "SELECT sum(wr_pac) as power
                            FROM " . $anlage->getDbNameIst() . " 
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'";
                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'";
                    $resAct = $this->conn->query($sqlActual);
                    $resExp = $this->conn->query($sqlExpected);

                    if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                    else $exp = 0;
                    if ($resAct->rowCount() > 0) $actual = $resAct->fetch(PDO::FETCH_ASSOC)['power'];
                    else $actual = 0;
                    $sumLossesYearOMC = $sumLossesYearEFOR - ($actual - $exp);
                }
            }
        }

        $actualAvailabilityPorcent = (($sumquarters - $totalErrors) / $sumquarters) * 100;
        $actualSOFPorcent = 100 - (($sumquarters - $SOFErrors) / $sumquarters) * 100;
        $actualEFORPorcent = 100 - (($sumquarters - $EFORErrors) / $sumquarters) * 100;
        $actualOMCPorcent = 100 - (($sumquarters - $OMCErrors) / $sumquarters) * 100;

        if ($EFORErrors > 0)$actualGapPorcent = 100 - (($EFORErrors - $dataGaps) / $EFORErrors) * 100;
        else $actualGapPorcent = 0;

        if ($totalErrors != 0) {
            $failRelativeSOFPorcent = 100 - (($totalErrors - $SOFErrors) / $totalErrors) * 100;
            $failRelativeEFORPorcent = 100 - (($totalErrors - $EFORErrors) / $totalErrors) * 100;
            $failRelativeOMCPorcent = 100 - (($totalErrors - $OMCErrors) / $totalErrors) * 100;
        } else {
            $failRelativeSOFPorcent = 0;
            $failRelativeEFORPorcent = 0;
            $failRelativeOMCPorcent = 0;
        }
        $kwhLossesYearTable = [
            'SORLosses'     => $sumLossesYearSOR,
            'EFORLosses'    => $sumLossesYearEFOR,
            'OMCLosses'     => $sumLossesYearOMC
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
            'SOFTickets' => (int) $this->ticketDateRepo->countTicketsByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 10, $anlage)[0][1],
            'EFORTickets' => (int) $this->ticketDateRepo->countTicketsByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 20, $anlage)[0][1],
            'OMCTickets' => (int) $this->ticketDateRepo->countTicketsByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 30, $anlage)[0][1],
            'SOFQuarters' => $SOFErrors,
            'EFORQuarters' => $EFORErrors,
            'OMCQuarters' => $OMCErrors,
        ];

        // we can add the values we generate for the table of the errors to generate the pie graphic directly
        $chart->series = [
            [
                'type' => 'pie',
                'data' => [
                    [
                        'value' => $actualAvailabilityPorcent,
                        'name' => 'PA',
                    ],
                    [
                        'value' => $actualSOFPorcent,
                        'name' => 'SOF',
                    ],
                    [
                        'value' => $actualEFORPorcent,
                        'name' => 'EFOR',
                    ],
                    [
                        'value' => $actualOMCPorcent,
                        'name' => 'OMC',
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
            'color' => ['#9dc3e6', '#f3a672', '#ff0000', '#c5e0b4'],
            'title' => [
                'text' => 'Availability: Year to date',
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
        $availability_Year_To_Date = $chart->render('availability_Year_To_Date', ['style' => 'height: 175px; width:300px; ']);

        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        // Failures: Year to date
        // $chart->tooltip->show = true;
        // $chart->tooltip->trigger = 'item';
        $chart->series =
            [
                [
                    'type' => 'pie',
                    'data' => [
                        [
                            'value' => $failRelativeSOFPorcent,
                            'name' => 'SOF',
                        ],
                        [
                            'value' => $failRelativeEFORPorcent,
                            'name' => 'EFOR',
                        ],
                        [
                            'value' => $failRelativeOMCPorcent,
                            'name' => 'OMC',
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
            'color' => ['#9dc3e6', '#ffa4a4', '#ffc000'],
            'title' => [
                'text' => 'Failure - Year to date',
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
        $failures_Year_To_Date = $chart->render('failures_Year_To_Date', ['style' => 'height: 175px; width:300px; ']);

        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);
        $SOFErrorsMonth = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 10, $anlage)[0][1];
        $EFORErrorsMonth = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 20, $anlage)[0][1];
        $OMCErrorsMonth = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 30, $anlage)[0][1];
        $dataGapsMonth = (int) $this->ticketDateRepo->countByIntervalNullPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, $anlage)[0][1];

        $totalErrorsMonth = $SOFErrorsMonth + $EFORErrorsMonth + $OMCErrorsMonth;

        $begin = $report['reportYear'].'-'.$report['reportMonth'].'-'.'01 00:00:00';
        $lastDayOfMonth = date('t', strtotime($begin));
        $end = $report['reportYear'].'-'.$report['reportMonth'].'-'.$lastDayOfMonth.' 23:55:00';
        $sqlw = 'SELECT count(db_id) as quarters
                    FROM  '.$anlage->getDbNameWeather()."  
                    WHERE stamp BETWEEN '$begin' AND '$end' 
                    AND g_lower + g_upper > 0";

        $resw = $this->conn->query($sqlw);

        $sumLossesMonthSOR = 0;
        $sumLossesMonthEFOR = 0;
        $sumLossesMonthOMC = 0;

        foreach ($this->ticketDateRepo->getAllByInterval($begin, $end, $anlage) as $date){
            $intervalBegin = date("Y-m-d H:i",$date->getBegin()->getTimestamp());
            $intervalEnd = date("Y-m-d H:i",$date->getEnd()->getTimestamp());
            $inverter = $date->getInverter();
            foreach($date->getInverterArray() as $inverter) {
                switch ($anlage->getConfigType()) { // we need this to query for the inverter in the SOR and EFOR cases, in the OMC case the whole plant is down

                    case 1 :
                        $inverterQuery = "group_dc = '$inverter'";
                        break;
                    default:
                        $inverterQuery = "group_ac = '$inverter'";
                }
                if ($date->getErrorType() == 10) {
                    $sqlActual = "SELECT sum(wr_pac) as power
                            FROM " . $anlage->getDbNameIst() . " 
                            WHERE wr_pac >= 0 AND stamp >= '$intervalBegin' AND stamp < '$intervalEnd' AND $inverterQuery";

                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd' AND $inverterQuery";
                    $resAct = $this->conn->query($sqlActual);
                    $resExp = $this->conn->query($sqlExpected);

                    if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                    else $exp = 0;
                    if ($resAct->rowCount() > 0) $actual = $resAct->fetch(PDO::FETCH_ASSOC)['power'];
                    else $actual = 0;
                    $sumLossesMonthSOR = $sumLossesMonthSOR - ($actual - $exp);
                } else if ($date->getErrorType() == 20) {
                    $sqlActual = "SELECT sum(wr_pac) as power
                            FROM " . $anlage->getDbNameIst() . " 
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd' AND $inverterQuery";
                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd' AND $inverterQuery";
                    $resAct = $this->conn->query($sqlActual);
                    $resExp = $this->conn->query($sqlExpected);

                    if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                    else $exp = 0;
                    if ($resAct->rowCount() > 0) $actual = $resAct->fetch(PDO::FETCH_ASSOC)['power'];
                    else $actual = 0;
                    $sumLossesMonthEFOR = $sumLossesMonthEFOR - ($actual - $exp);
                } else if ($date->getErrorType() == 30) {
                    $sqlActual = "SELECT sum(wr_pac) as power
                            FROM " . $anlage->getDbNameIst() . " 
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'";
                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'";
                    $resAct = $this->conn->query($sqlActual);
                    $resExp = $this->conn->query($sqlExpected);

                    if ($resExp->rowCount() > 0) $exp = $resExp->fetch(PDO::FETCH_ASSOC)['expected'];
                    else $exp = 0;
                    if ($resAct->rowCount() > 0) $actual = $resAct->fetch(PDO::FETCH_ASSOC)['power'];
                    else $actual = 0;
                    $sumLossesMonthOMC = $sumLossesMonthEFOR - ($actual - $exp);
                }
            }
        }

        $quartersInMonth = $resw->fetch(PDO::FETCH_ASSOC)['quarters'] * $anlage->getAnzInverter();
        $actualAvailabilityPorcentMonth = (($quartersInMonth - $totalErrorsMonth) / $quartersInMonth) * 100;
        $actualSOFPorcentMonth = 100 - (($quartersInMonth - $SOFErrorsMonth) / $quartersInMonth) * 100;
        $actualEFORPorcentMonth = 100 - (($quartersInMonth - $EFORErrorsMonth) / $quartersInMonth) * 100;
        $actualOMCPorcentMonth = 100 - (($quartersInMonth - $OMCErrorsMonth) / $quartersInMonth) * 100;
        if ($EFORErrorsMonth > 0)$actualGapPorcentMonth = 100 - (($EFORErrorsMonth - $dataGapsMonth) / $EFORErrorsMonth) * 100;
        else $actualGapPorcentMonth = 0;

        $kwhLossesMonthTable = [
            'SORLosses'     => $sumLossesMonthSOR,
            'EFORLosses'    => $sumLossesMonthEFOR,
            'OMCLosses'     => $sumLossesMonthOMC
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
            'SOFTickets' => (int) $this->ticketDateRepo->countTicketsByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 10, $anlage)[0][1],
            'EFORTickets' => (int) $this->ticketDateRepo->countTicketsByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 20, $anlage)[0][1],
            'OMCTickets' => (int) $this->ticketDateRepo->countTicketsByIntervalErrorPlant($report['reportYear'].'-'.$report['reportMonth'].'-01', $endate, 30, $anlage)[0][1],
            'SOFQuarters' => $SOFErrorsMonth,
            'EFORQuarters' => $EFORErrorsMonth,
            'OMCQuarters' => $OMCErrorsMonth,
        ];
        if ($totalErrorsMonth != 0) {
            $failRelativeSOFPorcentMonth = 100 - (($totalErrorsMonth - $SOFErrorsMonth) / $totalErrorsMonth) * 100;
            $failRelativeEFORPorcentMonth = 100 - (($totalErrorsMonth - $EFORErrorsMonth) / $totalErrorsMonth) * 100;
            $failRelativeOMCPorcentMonth = 100 - (($totalErrorsMonth - $OMCErrorsMonth) / $totalErrorsMonth) * 100;
        } else {
            $failRelativeSOFPorcentMonth = 0;
            $failRelativeEFORPorcentMonth = 0;
            $failRelativeOMCPorcentMonth = 0;
        }

        $chart->series =
            [
                [
                    'type' => 'pie',
                    'data' => [
                        [
                            'value' => $actualAvailabilityPorcent,
                            'name' => 'PA',
                        ],
                        [
                            'value' => $actualSOFPorcentMonth,
                            'name' => 'SOF',
                        ],
                        [
                            'value' => $actualEFORPorcentMonth,
                            'name' => 'EFOR',
                        ],
                        [
                            'value' => $actualOMCPorcentMonth,
                            'name' => 'OMC',
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
            'color' => ['#c5e0b4', '#ed7d31', '#941651', '#ffc000'],
            'title' => [
                'text' => 'Plant availability: '.$monthName.' '.$report['reportYear'],
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
        $plant_availability = $chart->render('plant_availability', ['style' => 'height: 175px; width:300px; ']);

        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

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

        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        // fuer PA Report Month
        if ($anlage->getConfigType() == 1) {
            // Type 1 is the only one where acGrops are NOT the Inveerter
            $inverters = $anlage->getGroups()->count();
        } else {
            // use acGroups as Inverter
            $inverters = $anlage->getAcGroups()->count();
        }
        for ($inverter = 0; $inverter < $inverters; ++$inverter) {
            $pa = [];
            for ($day = 1; $day <= $daysInReportMonth; ++$day) {
                $tempFrom = new \DateTime($report['reportYear'].'-'.$report['reportMonth']."-$day 00:00");
                $tempTo = new \DateTime($report['reportYear'].'-'.$report['reportMonth']."-$day 23:59");
                $pa[] = [
                    'form_date' => $day,
                    'pa' => $this->availability->calcAvailability($anlage, $tempFrom, $tempTo, $inverter),
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
                            // 'diff_current_dc' => ($dcIst[$j]['act_current_dc'] != 0) ? (1 - $value[$i]['exp_current_dc'] / $dcIst[$j]['act_current_dc']) * 100 : 0,
                            // 'diff_power_dc' => ($dcIst[$j]['act_power_dc'] != 0) ? (1 - $value[$i]['exp_power_dc'] / $dcIst[$j]['act_power_dc']) * 100 : 0,
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
                            'diff_current_dc' => ($dcIst[$j]['act_current_dc'] != 0) ? (($dcIst[$j]['act_current_dc'] - $value[$i]['exp_current_dc']) / $value[$i]['exp_current_dc']) * 100 : 0,
                            'diff_power_dc' => ($dcIst[$j]['act_power_dc'] != 0) ? (($dcIst[$j]['act_power_dc'] - $value[$i]['exp_power_dc']) / $value[$i]['exp_power_dc']) * 100 : 0,
                            // 'diff_current_dc' => ($dcIst[$j]['act_current_dc'] != 0) ? (1 - $value[$i]['exp_current_dc'] / $dcIst[$j]['act_current_dc']) * 100 : 0,
                            // 'diff_power_dc' => ($dcIst[$j]['act_power_dc'] != 0) ? (1 - $value[$i]['exp_power_dc'] / $dcIst[$j]['act_power_dc']) * 100 : 0,
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
        $resultEconomicsNames = $this->ecoVarNameRepo->findOneByAnlage($anlage);

        if ($resultEconomicsNames) {
            /* This can be removed if we add a way to know whether a variable is fix or not, then we will be able to get it from anlage entity
            and make all the calculations in the twig template
              */

            $ecoVarValues = $this->ecoVarValueRepo->findByAnlage($anlage);

            for ($i = 0; $i < count($ecoVarValues) - 1; ++$i) {
                (float) $oum[] = $ecoVarValues[$i]->getVar1();
                $oumTotal = $oumTotal + $oum[$i];
                (float) $electricity[] = $ecoVarValues[$i]->getVar2();
                $electricityTotal = $electricityTotal + $electricity[$i];
                (float) $technicalDispatch[] = $ecoVarValues[$i]->getVar3();
                $technicalDispatchTotal = $technicalDispatchTotal + $technicalDispatch[$i];
                (float) $transTeleCom[] = $ecoVarValues[$i]->getVar4();
                $transTeleComTotal = $transTeleComTotal + $transTeleCom[$i];
                (float) $security[] = $ecoVarValues[$i]->getVar5();
                $securityTotal = $securityTotal + $security[$i];
                (float) $networkServiceFee[] = $ecoVarValues[$i]->getVar6();
                $networkServiceFeeToatal = $networkServiceFeeToatal + $networkServiceFee[$i];
                (float) $legalServices[] = $ecoVarValues[$i]->getVar7();
                $legalServicesTotal = $legalServicesTotal + $legalServices[$i];
                (float) $accountancyAndAdministrationCosts[] = $ecoVarValues[$i]->getVar8();
                $accountancyAndAdministrationCostsTotal = $accountancyAndAdministrationCostsTotal + $accountancyAndAdministrationCosts[$i];
                (float) $Iinsurance[] = $ecoVarValues[$i]->getVar9();
                $IinsuranceTotal = $IinsuranceTotal + $Iinsurance[$i];
                (float) $other[] = $ecoVarValues[$i]->getVar10();
                $otherTotal = $otherTotal + $other[$i];
                $fixesTotal[$i] = $oum[$i] +
                $electricity[$i] +
                $technicalDispatch[$i] +
                $transTeleCom[$i] +
                $security[$i] +
                $networkServiceFee[$i] +
                $legalServices[$i] +
                $legalServices[$i] +
                $accountancyAndAdministrationCosts[$i] +
                $Iinsurance[$i] +
                $other[$i];
                (float) $variable1[] = $ecoVarValues[$i]->getVar11();
                $variable1Total = $variable1Total + $variable1[$i];
                (float) $variable2[] = $ecoVarValues[$i]->getVar12();
                $variable2Total = $variable2Total + $variable2[$i];
                (float) $variable3[] = $ecoVarValues[$i]->getVar13();
                $variable3Total = $variable3Total + $variable3[$i];
                (float) $variable4[] = $ecoVarValues[$i]->getVar14();
                $variable4Total = $variable4Total + $variable4[$i];
                (float) $variable5[] = $ecoVarValues[$i]->getVar15();
                $variable5Total = $variable5Total + $variable5[$i];
                $variablesTotal[$i] = $variable1[$i] +
                $variable2[$i] +
                $variable3[$i] +
                $variable4[$i] +
                $variable5[$i];
                (float) $kwhPrice[] = $ecoVarValues[$i]->getKwHPrice();
                $monthTotal[] = $fixesTotal[$i] + $variablesTotal[$i];
            }
        }
        $economicsMandy = [
            'oum' => $oum,
            'electricity' => $electricity,
            'technicalDispatch' => $technicalDispatch,
            'transTeleCom' => $transTeleCom,
            'security' => $security,
            'networkServiceFee' => $networkServiceFee,
            'legalServices' => $legalServices,
            'accountancyAndAdministrationCosts' => $accountancyAndAdministrationCosts,
            'Iinsurance' => $Iinsurance,
            'other' => $other,
            'fixesTotal' => $fixesTotal,
            'variable1' => $variable1,
            'variable2' => $variable2,
            'variable3' => $variable3,
            'variable4' => $variable4,
            'variable5' => $variable5,
            'variablesTotal' => $variablesTotal,
            'kwhPrice' => $kwhPrice,
            'monthTotal' => $monthTotal,
        ];

        // beginn Operating statement
        for ($i = 0; $i < 12; ++$i) {
            $monthleyFeedInTarif = $kwhPrice[$i];
            $incomePerMonth['revenues_act'][$i] = $tbody_a_production['powerEvu'][$i] * $monthleyFeedInTarif;
            $incomePerMonth['PVSYST_plan_proceeds_EXP'][$i] = $tbody_a_production['expectedPvSyst'][$i] * $monthleyFeedInTarif;
            $incomePerMonth['gvn_plan_proceeds_EXP'][$i] = $tbody_a_production['powerExpEvu'][$i] * $monthleyFeedInTarif;
            // -Total costs
            $incomePerMonth['revenues_act_minus_totals'][$i] = round($incomePerMonth['revenues_act'][$i] - $monthTotal[$i], 0);
            $incomePerMonth['PVSYST_plan_proceeds_EXP_minus_totals'][$i] = round($incomePerMonth['PVSYST_plan_proceeds_EXP'][$i] - $monthTotal[$i], 0);
            $incomePerMonth['gvn_plan_proceeds_EXP_minus_totals'][$i] = round($incomePerMonth['gvn_plan_proceeds_EXP'][$i] - $monthTotal[$i], 0);

            $incomePerMonth['monthley_feed_in_tarif'][$i] = $monthleyFeedInTarif;
        }
        // end Operating statement

        // beginn economics Cumulated Forecast
        // ohne Kosten(Hilfstabelle)
        for ($i = 0; $i < count($dataMonthArrayFullYear); ++$i) {
            $ohneKostenForecastPVSYST[$i] = $tbody_a_production['expectedPvSyst'][$i] * $incomePerMonth['monthley_feed_in_tarif'][$i];
            $ohneKostenForecastG4N[$i] = $forecast[$i + 1] * $kwhPrice[$i];
        }

        // mit Kosten(Hilfstabelle)
        for ($i = 0; $i < count($dataMonthArrayFullYear); ++$i) {
            $mitKostenForecastPVSYST[$i] = $ohneKostenForecastPVSYST[$i] - $economicsMandy['monthTotal'][$i];
            $mitKostenForecastG4N[$i] = $ohneKostenForecastG4N[$i] - $economicsMandy['monthTotal'][$i];
        }

        $kumsum1[0] = $economicsMandy['monthTotal'][0];
        $kumsum2[0] = $incomePerMonth['PVSYST_plan_proceeds_EXP_minus_totals'][0];
        $kumsum3[0] = $incomePerMonth['PVSYST_plan_proceeds_EXP'][0];
        $kumsum4[0] = $incomePerMonth['gvn_plan_proceeds_EXP'][0];
        for ($i = 0; $i < 12; ++$i) {
            $kumsum1[$i] = $economicsMandy['monthTotal'][$i] + $kumsum1[$i - 1];
            if ($i < $report['reportMonth']) {
                $kumsum2[$i] = $kumsum1[$i];
            } else {
                $kumsum2[$i] = $kumsum1[$i] + $mitKostenForecastG4N[$i];
            }
            $kumsum3[$i] = $incomePerMonth['PVSYST_plan_proceeds_EXP'][$i] + $kumsum3[$i - 1];
            $kumsum4[$i] = $incomePerMonth['gvn_plan_proceeds_EXP'][$i] + $kumsum4[$i - 1];
            $result1[] = $kumsum1[$i];
            $result2[] = $kumsum2[$i];
            $result3[] = $kumsum3[$i];
            $result4[] = $kumsum4[$i];
        }

        $economicsCumulatedForecast = [
            'revenues_ACT_and_Revenues_Plan_PVSYT' => $result1,
            'revenues_ACT_and_Revenues_Plan_G4N' => $result2,
            'PVSYST_plan_proceeds_P50' => $result3,
            'g4n_plan_proceeds_EXP_P50' => $result4,
        ];

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $dataMonthArrayFullYear,
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
                    'name' => 'Revenues ACT and Revenues Plan PVSYST',
                    'type' => 'line',
                    'data' => $economicsCumulatedForecast['revenues_ACT_and_Revenues_Plan_PVSYT'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Revenues ACT and Revenues Plan g4n',
                    'type' => 'line',
                    'data' => $economicsCumulatedForecast['revenues_ACT_and_Revenues_Plan_G4N'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'PVSYST plan proceeds - P50',
                    'type' => 'line',
                    'data' => $economicsCumulatedForecast['PVSYST_plan_proceeds_P50'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'g4n plan proceeds - EXP - P50',
                    'type' => 'line',
                    'data' => $economicsCumulatedForecast['g4n_plan_proceeds_EXP_P50'],
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
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);
        // end Chart economics Cumulated Forecast

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $dataMonthArray,
        ];
        $chart->yAxis = [
            'type' => 'value',
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 70,
        ];
        if ($anlage->hasPVSYST()) {
            if ($anlage->hasGrid()) {
                $chart->series =
                    [
                        [
                            'name' => 'Difference Grid to PVSYST',
                            'type' => 'line',
                            'data' => $diefference_prod_to_pvsyst,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference Grid to expected g4n',
                            'type' => 'line',
                            'data' => $diefference_prod_to_expected_g4n,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference inverter to Grid',
                            'type' => 'line',
                            'data' => $diefference_prod_to_egrid,
                            'visualMap' => 'false',
                        ],
                    ];
            } else {
                $chart->series =
                    [
                        [
                            'name' => 'Difference Inverter to PVSYST',
                            'type' => 'line',
                            'data' => $diefference_prod_to_pvsyst,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference Inverter to expected g4n',
                            'type' => 'line',
                            'data' => $diefference_prod_to_expected_g4n,
                            'visualMap' => 'false',
                        ],
                    ];
            }
        } else {
            if ($anlage->hasGrid()) {
                $chart->series =
                    [
                        [
                            'name' => 'Difference Grid to expected g4n',
                            'type' => 'line',
                            'data' => $diefference_prod_to_expected_g4n,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference inverter to Grid',
                            'type' => 'line',
                            'data' => $diefference_prod_to_egrid,
                            'visualMap' => 'false',
                        ],
                    ];
            } else {
                $chart->series =
                    [
                        [
                            'name' => 'Difference Inverter to expected g4n',
                            'type' => 'line',
                            'data' => $diefference_prod_to_expected_g4n,
                            'visualMap' => 'false',
                        ],
                    ];
            }
        }

        $option = [
            'animation' => false,
            'color' => ['#0070c0', '#c55a11', '#a5a5a5'],
            'title' => [
                'text' => 'Monthly losses at plan values',
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
                    'width' => '90%',
                ],
        ];

        $chart->setOption($option);

        $losses_monthly = $chart->render('losses_monthly', ['style' => 'height: 450px; width:23cm;']);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);
        // end elosses monthly

        // beginn chart costs per month
        $chart = new ECharts();
        // $chart->tooltip->show = true;

        // $chart->tooltip->trigger = 'item';

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $dataMonthArray,
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
                    'name' => 'Revenues ACT',
                    'type' => 'bar',
                    'data' => $incomePerMonth['revenues_act'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'PVSYST plan proceeds - EXP',
                    'type' => 'bar',
                    'data' => $incomePerMonth['PVSYST_plan_proceeds_EXP'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'g4n plan proceeds - EXP',
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
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        $chart->series =
            [
                [
                    'type' => 'pie',
                    'data' => [
                        [
                            'value' => $oumTotal,
                            'name' => 'O&M',
                        ],
                        [
                            'value' => $electricityTotal,
                            'name' => 'Electricity',
                        ],
                        [
                            'value' => $technicalDispatchTotal,
                            'name' => 'Technical dispatch (KEGOC)',
                        ],
                        [
                            'value' => $transTeleComTotal,
                            'name' => 'TransTeleCom (cell equipment maintenance)',
                        ],
                        [
                            'value' => $securityTotal,
                            'name' => 'Security',
                        ],
                        [
                            'value' => $networkServiceFeeToatal,
                            'name' => 'Network service fee (ASTEL)',
                        ],
                        [
                            'value' => $legalServicesTotal,
                            'name' => 'legal services',
                        ],
                        [
                            'value' => $accountancyAndAdministrationCostsTotal,
                            'name' => 'Accountancy and administration costs',
                        ],
                        [
                            'value' => $IinsuranceTotal,
                            'name' => 'Insurance',
                        ],
                        [
                            'value' => $otherTotal,
                            'name' => 'Other',
                        ],
                        [
                            'value' => $variable1Total,
                            'name' => 'Variable 1',
                        ],                        [
                            'value' => $variable2Total,
                            'name' => 'Variable 2)',
                        ],
                        [
                            'value' => $variable3Total,
                            'name' => 'Variable 3',
                        ],
                        [
                            'value' => $variable4Total,
                            'name' => 'Variable 4',
                        ],
                        [
                            'value' => $variable5Total,
                            'name' => 'Variable 5',
                        ],
                    ],
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

        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

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
            'data' => $dataMonthArray,
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
                    'name' => 'Profit ACT',
                    'type' => 'bar',
                    'data' => $incomePerMonth['revenues_act_minus_totals'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'PVSYST plan proceeds',
                    'type' => 'bar',
                    'data' => $incomePerMonth['PVSYST_plan_proceeds_EXP_minus_totals'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'g4n plan proceeds - EXP ',
                    'type' => 'bar',
                    'data' => $incomePerMonth['gvn_plan_proceeds_EXP_minus_totals'],
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
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        // end Operating Statement

        // beginn Losses compared
        for ($i = 0; $i < 12; ++$i) {
            $Difference_Profit_ACT_to_PVSYST_plan[] = $incomePerMonth['revenues_act_minus_totals'][$i] - $incomePerMonth['PVSYST_plan_proceeds_EXP_minus_totals'][$i];
            $Difference_Profit_ACT_to_g4n_plan[] = $incomePerMonth['revenues_act_minus_totals'][$i] - $incomePerMonth['gvn_plan_proceeds_EXP_minus_totals'][$i];
        }

        $lossesComparedTable = [
            'Difference_Profit_ACT_to_PVSYST_plan' => $Difference_Profit_ACT_to_PVSYST_plan,
            'Difference_Profit_ACT_to_g4n_plan' => $Difference_Profit_ACT_to_g4n_plan,
        ];

        // end Losses compared

        // beginn Chart Losses compared
        $chart = new ECharts();
        // $chart->tooltip->show = true;

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $dataMonthArray,
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
                    'name' => 'Difference Income ACT to PVSYST plan',
                    'type' => 'bar',
                    'data' => $lossesComparedTable['Difference_Profit_ACT_to_PVSYST_plan'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'PVSYST plan proceeds',
                    'type' => 'bar',
                    'data' => $lossesComparedTable['Difference_Profit_ACT_to_g4n_plan'],
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
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        // end Chart Losses compared

        // beginn Table Losses compared cummulated

        unset($result1);
        unset($result2);
        $kumsum1[0] = $lossesComparedTable['Difference_Profit_ACT_to_PVSYST_plan'][0];
        $kumsum2[0] = $lossesComparedTable['Difference_Profit_ACT_to_g4n_plan'][0];
        for ($i = 0; $i < 12; ++$i) {
            $kumsum1[$i] = $lossesComparedTable['Difference_Profit_ACT_to_PVSYST_plan'][$i] + $kumsum1[$i - 1];
            $kumsum2[$i] = $lossesComparedTable['Difference_Profit_ACT_to_g4n_plan'][$i] + $kumsum2[$i - 1];
            $result1[] = $kumsum1[$i];
            $result2[] = $kumsum2[$i];
        }

        $lossesComparedTableCumulated = [
            'Difference_Profit_ACT_to_PVSYST_plan_cum' => $result1,
            'Difference_Profit_ACT_to_g4n_plan_cum' => $result2,
            ];

        // end Table Losses compared cummulated

        // beginn Chart Losses compared cummulated
        $chart = new ECharts();
        // $chart->tooltip->show = true;

        // $chart->tooltip->trigger = 'item';

        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => $dataMonthArray,
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
                    'name' => 'Difference Income ACT to PVSYST plan',
                    'type' => 'line',
                    'data' => $lossesComparedTableCumulated['Difference_Profit_ACT_to_PVSYST_plan_cum'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'PVSYST plan proceeds',
                    'type' => 'line',
                    'data' => $lossesComparedTableCumulated['Difference_Profit_ACT_to_g4n_plan_cum'],
                    'visualMap' => 'false',
                ],
            ];

        $option = [
            'animation' => false,
            'color' => ['#9dc3e6', '#92d050'],
            'title' => [
                'text' => 'Commulative Losses Operating statement [EUR] ',
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
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);
        // end Chart Losses compared cummulated
        $output = [
            'plantId' => $plantId,
            'owner' => $owner,
            'plantSize' => $plantSize,
            'plantName' => $plantName,
            'anlGeoLat' => $anlGeoLat,
            'anlGeoLon' => $anlGeoLon,
            'month' => $monthName,
            'reportmonth' => $report['reportMonth'],
            'year' => $report['reportYear'],
            'monthArray' => $monthArray,
            'dataMonthArray' => $dataMonthArray,
            'dataMonthArrayFullYear' => $dataMonthArrayFullYear,
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
            'useGridMeterDayData' => $useGridMeterDayData,
            'availabilityMonthTable' => $availabilityMonthTable,
            'showAvailability' => $showAvailability,
            'showAvailabilitySecond' => $showAvailabilitySecond,
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
            'kwhLossesMonthTable' =>$kwhLossesMonthTable
        ];

        return $output;
    }
}