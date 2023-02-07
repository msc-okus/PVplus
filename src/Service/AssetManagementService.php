<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Repository\EconomicVarNamesRepository;
use App\Repository\EconomicVarValuesRepository;
use App\Repository\PvSystMonthRepository;
use App\Repository\ReportsRepository;
use App\Repository\TicketDateRepository;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Hisune\EchartsPHP\ECharts;
use PDO;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AssetManagementService
{
    use G4NTrait;

    private PDO $conn;

    public function __construct(
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
    ) 
    {
        $this->conn = self::getPdoConnection();
    }

    public function createAmReport(Anlage $anlage, $reportMonth, $reportYear): string
    {
        $report = $this->reportRepo->findOneByAMY($anlage, $reportMonth, $reportYear)[0];
        $comment = '';
        if ($report) {
            $comment = $report->getComments();
            $this->em->remove($report);
            $this->em->flush();
        }
        // then we generate our own report and try to persist it
        $output = $this->assetReport($anlage, $reportMonth, $reportYear, 0);
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
        $report = new AnlagenReports();
        $report
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
        $this->em->persist($report);
        $this->em->flush();

        return 'Asset Report generated'; //$output;
    }

    /**
     * @throws ExceptionInterface
     */
    public function assetReport($anlage, $month = 0, $year = 0, $pages = 0): array
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

        return $this->buildAssetReport($anlage, $report);
    }
    /**
     * @param Anlage $anlage
     * @param array $report
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function buildAssetReport(Anlage $anlage, array $report): array
    {

        $month = $report['reportMonth'];
        for ($i = 0; $i < 12; ++$i) {
            $forecast[$i] = $this->functions->getForcastByMonth($anlage, $i);
        }
        $plantSize = $anlage->getPnom();
        $plantId = $anlage->getAnlId();
        $monthName = date('F', mktime(0, 0, 0, $report['reportMonth'], 10));
        $currentMonth = date('m');

        if ($report['reportMonth'] < 10) {
            $report['reportMonth'] = str_replace(0, '', $report['reportMonth']);
        }

        $daysInReportMonth = cal_days_in_month(CAL_GREGORIAN, $report['reportMonth'], $report['reportYear']);

        $monthArray = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
        ];

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
            $dataMonthArray[] = $monthArray[$i - 1];
            $expectedPvSyst[] = $Ertrag_design;

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
            'forecast' => $forecast,
        ];
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
                            'name' => 'Actual(Yield)',
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
/*
                        [
                            'name' => 'forecast g4n',
                            'type' => 'bar',
                            'data' => $forecast,
                            'visualMap' => 'false',
                        ],
  */                  ];
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
                            'name' => 'Actual(Yield)',
                            'type' => 'bar',
                            'data' => $powerAct,
                            'visualMap' => 'false',
                        ],
                        /*
                        [
                            'name' => 'forecast g4n',
                            'type' => 'bar',
                            'data' => $forecast,
                            'visualMap' => 'false',
                        ],
                        */
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

        // Begin Cumulative Forecast with PVSYST

        // Forecast / degradation

        $degradation = $anlage->getLossesForecast();
        // Cumulative Forecast
        $powerSum[0] = $powerEvu[0];
        for ($i = 0; $i < 12; ++$i) {
            if ($i + 1 > $report['reportMonth']) {
                if ($expectedPvSyst[$i]) {
                    $powerSum[$i] = $expectedPvSyst[$i] + $powerSum[$i - 1];
                } else {
                    $powerSum[$i] = $forecast[$i] + $powerSum[$i - 1];
                }
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
        $PVSYSExpSum[0] = $expectedPvSyst[0];
        for ($i = 0; $i < 12; ++$i) {
            if (!$expectedPvSyst[$i]) {
                $PVSYSExpSum[$i] = $forecast[$i] + $PVSYSExpSum[$i - 1];
            } else {
                $PVSYSExpSum[$i] = $expectedPvSyst[$i] + $PVSYSExpSum[$i - 1];
            }
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
                    'data' => $tbody_forecast_PVSYSTP50,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Production ACT / PVSYST - P90',
                    'type' => 'line',
                    'data' => $tbody_forecast_PVSYSTP90,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Plan PVSYST - P50',
                    'type' => 'line',
                    'data' => $tbody_forecast_plan_PVSYSTP50,
                    'visualMap' => 'false',
                    'lineStyle' => [
                        'type' => 'dashed',
                    ],
                ],
                [
                    'name' => 'Plan PVSYST - P90',
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
                    'name' => 'Difference ACT to PVSYST',
                    'type' => 'line',
                    'data' => $diefference_prod_to_pvsyst,
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Difference ACT to expected g4n',
                    'type' => 'line',
                    'data' => $diefference_prod_to_expected_g4n,
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
                            'name' => 'Difference ACT to PVSYST',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_PVSYST,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference ACT to expected g4n',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_Expected_G4n,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference ACT to Grid',
                            'type' => 'line',
                            'data' => $difference_Inverter_to_Egrid,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference ACT to forecast',
                            'type' => 'line',
                            'data' => $difference_actual_forecast,
                            'visualMap' => 'false',
                        ]
                    ];
            } else {
                $chart->series =
                    [
                        [
                            'name' => 'Difference ACT to PVSYST',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_PVSYST,
                            'visualMap' => 'false',
                        ],
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
            }
        } else {
            if ($anlage->hasGrid()) {
                $chart->series =
                    [
                        [
                            'name' => 'Difference ACT to expected g4n',
                            'type' => 'line',
                            'data' => $difference_Egrid_to_Expected_G4n,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference ACT to Grid',
                            'type' => 'line',
                            'data' => $difference_Inverter_to_Egrid,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference ACT to forecast',
                            'type' => 'line',
                            'data' => $difference_actual_forecast,
                            'visualMap' => 'false',
                        ]
                    ];
            } else {
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

        if ($powerEvu[$report['reportMonth'] - 1] < 1){
            $var = 0;
        }
        else {
            $var = round((1 - $expectedPvSyst[$report['reportMonth'] - 1] / $powerEvu[$report['reportMonth'] - 1]) * 100, 2);
        }
        $operations_monthly_right_pvsyst_tr1 = [
            $monthName.' '.$report['reportYear'],
            $powerEvu[$report['reportMonth'] - 1],
            $expectedPvSyst[$report['reportMonth'] - 1],
            $powerEvu[$report['reportMonth'] - 1] - $expectedPvSyst[$report['reportMonth'] - 1],
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
        if (($powerEvuQ1 > 0) && $anlage->hasPVSYST()) {
            $expectedPvSystQ1 = 0;


            if ($month >= 3) {
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

        $start = $report['reportYear'].'-04-01 00:00';
        $end = $report['reportMonth'] >= '6' ? $report['reportYear'].'-06-30 23:59' : $report['to'];

        $data2_grid_meter = $this->functions->getSumAcPower($anlage, $start, $end);
        if ($anlage->getShowEvuDiag()) {
            $powerEvuQ2 = $data2_grid_meter['powerEvu'];
        } else {
            $powerEvuQ2 = $data2_grid_meter['powerAct'];
        }

        if (( $powerEvuQ2 > 0) && $anlage->hasPVSYST()) {
            if ($month >= 6) {
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
        if (( $powerEvuQ3 > 0) && $anlage->hasPVSYST()) {
            if ($month >= 9) {
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
        if (($powerEvuQ4 > 0) && $anlage->hasPVSYST()) {
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
        $SOFErrors  = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 10, $anlage)[0][1];
        $EFORErrors = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 20, $anlage)[0][1];
        $OMCErrors  = (int) $this->ticketDateRepo->countByIntervalErrorPlant($report['reportYear'].'-01-01', $endate, 30, $anlage)[0][1];
        $dataGaps   = (int) $this->ticketDateRepo->countByIntervalNullPlant($report['reportYear'].'-01-01', $endate, $anlage)[0][1];
        $totalErrors = $SOFErrors + $EFORErrors + $OMCErrors;
        // here we calculate the ammount of quarters to calculate the relative percentages
        $sumquarters = 0;
        for ($month = 1; $month <= (int) $report['reportMonth']; ++$month) {
            $begin = $report['reportYear'].'-'.$month.'-'.'01 00:00:00';
            $lastDayOfMonth = date('t', strtotime($begin));
            $end = $report['reportYear'].'-'.$month.'-'.$lastDayOfMonth.' 23:55:00';
            $sqlw = 'SELECT count(db_id) as quarters
                    FROM  '.$anlage->getDbNameWeather()."  
                    WHERE stamp BETWEEN '$begin' AND '$end' AND (g_lower + g_upper)/2 > '".$anlage->getThreshold2PA()."'";// hay que cambiar aqui para que la radiacion sea mayor que un valor

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
            foreach($date->getInverterArray() as $inverter) {
                if ($inverter != "*") {
                    switch ($anlage->getConfigType()) { // we need this to query for the inverter in the SOR and EFOR cases, in the OMC case the whole plant is down

                        case 1 :
                            $inverterQuery = " AND group_dc = $inverter";
                            break;
                        default:
                            $inverterQuery = " AND group_ac = $inverter";
                    }
                }else $inverterQuery = "";

                if ($date->getErrorType() == 10) {
                    $sqlActual = "SELECT sum(wr_pac) as power
                            FROM " . $anlage->getDbNameIst() . " 
                            WHERE wr_pac >= 0 AND stamp >= '$intervalBegin' AND stamp < '$intervalEnd'  ". $inverterQuery;

                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'  ". $inverterQuery;
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
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd' ". $inverterQuery;
                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd' ".$inverterQuery;
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
        if ($sumquarters = 0) {
            $actualAvailabilityPorcent = (($sumquarters - $totalErrors) / $sumquarters) * 100;
            $actualSOFPorcent = 100 - (($sumquarters - $SOFErrors) / $sumquarters) * 100;
            $actualEFORPorcent = 100 - (($sumquarters - $EFORErrors) / $sumquarters) * 100;
            $actualOMCPorcent = 100 - (($sumquarters - $OMCErrors) / $sumquarters) * 100;

        }
        else{
            $actualAvailabilityPorcent = 0;
            $actualSOFPorcent = 0;
            $actualEFORPorcent = 0;
            $actualOMCPorcent = 0;

        }
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
                if ($date->getErrorType() == 10) {
                    $sqlActual = "SELECT sum(wr_pac) as power
                            FROM " . $anlage->getDbNameIst() . " 
                            WHERE wr_pac >= 0 AND stamp >= '$intervalBegin' AND stamp < '$intervalEnd'  $inverterQuery";

                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'  $inverterQuery";
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
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'  $inverterQuery";
                    $sqlExpected = "SELECT sum(ac_exp_power) as expected
                            FROM " . $anlage->getDbNameDcSoll() . "                      
                            WHERE stamp >= '$intervalBegin' AND stamp < '$intervalEnd'  $inverterQuery";
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
        if ($quartersInMonth > 0) {
            $actualAvailabilityPorcentMonth = (($quartersInMonth - $totalErrorsMonth) / $quartersInMonth) * 100;
            $actualSOFPorcentMonth = 100 - (($quartersInMonth - $SOFErrorsMonth) / $quartersInMonth) * 100;
            $actualEFORPorcentMonth = 100 - (($quartersInMonth - $EFORErrorsMonth) / $quartersInMonth) * 100;
            $actualOMCPorcentMonth = 100 - (($quartersInMonth - $OMCErrorsMonth) / $quartersInMonth) * 100;
        }
        else{
            $actualAvailabilityPorcentMonth = 0;
            $actualSOFPorcentMonth = 0;
            $actualEFORPorcentMonth = 0;
            $actualOMCPorcentMonth = 0;
        }
        if ($EFORErrorsMonth > 0)$actualGapPorcentMonth = 100 - (($EFORErrorsMonth - $dataGapsMonth) / $EFORErrorsMonth) * 100;
        else $actualGapPorcentMonth = 0;

        $kwhLossesMonthTable = [
            'SORLosses'     => $sumLossesMonthSOR,
            'EFORLosses'    => $sumLossesMonthEFOR,
            'OMCLosses'     => $sumLossesMonthOMC
        ];
       // dd($kwhLossesMonthTable);
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


        //Tables for the kwh losses with bar graphs

        if ($anlage->hasPVSYST()){
            $PVSYSTmonthExpected = $tbody_a_production['expectedPvSyst'][$month-2];
            $PVSYSTyearExpected = 0;
            for($index = 0; $index < $month -1; $index++){
                $PVSYSTyearExpected = $PVSYSTyearExpected + $tbody_a_production['expectedPvSyst'][$index];
            }
        }
        $G4NmonthExpected = $tbody_a_production['powerExp'][$month-2];
        $G4NyearExpected = 0;
        for($index = 0; $index < $month -1; $index++){
            $G4NyearExpected = $G4NyearExpected + $tbody_a_production['powerExp'][$index];
        }
        $ActualPower = $tbody_a_production['powerAct'][$month-2];
        $ActualPowerYear = 0;
        for($index = 0; $index < $month -1; $index++){
            $ActualPowerYear = $ActualPowerYear + $tbody_a_production['powerAct'][$index];
        }

        // dd($kwhLossesYearTable, $kwhLossesMonthTable, $G4NmonthExpected, $G4NyearExpected, $PVSYSTmonthExpected, $PVSYSTyearExpected,$tbody_a_production,$ActualPower, $ActualPowerYear);
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);


        $chart->xAxis = [
            'type' => 'value',
            'name' => 'KWH',
            'nameLocation' => 'middle',
            'nameGap' => 80,
            'scale' => true,
            'min' => 0,
            'gridIndex' => 0,
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
                'verticalAlign' => 'bottom',
                'rotate' => '90'
            ],
        ];
        $chart->yAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'splitArea' => [
                'show' => true,
            ],
            'data' => [],
            'scale' => true,
            'min' => 0,
        ];
        if ($anlage->hasPVSYST()) {
            $chart->series =
                [
                    [
                        'name' => 'Expected G4N',
                        'type' => 'bar',
                        'data' => [$G4NmonthExpected] ,
                    ],
                    [
                        'name' => 'Expected PV SYST',
                        'type' => 'bar',
                        'data' => [
                            $expectedPvSyst[$report['reportMonth'] - 1],
                        ],
                    ],
                    [
                        'name' => 'G4N Simulation',
                        'type' => 'bar',
                        'data' => [
                            $forecast[$month-2],
                        ],
                    ],
                    [
                        'name' => 'Actual',
                        'type' => 'bar',
                        'data' => [$ActualPower],
                    ],
                    [
                        'name' => 'SOR Losses',
                        'type' => 'bar',
                        'data' => [$kwhLossesMonthTable['SORLosses']],
                    ],
                    [
                        'name' => 'EFOR Losses',
                        'type' => 'bar',
                        'data' => [$kwhLossesMonthTable['EFORLosses']],
                    ],
                    [
                        'name' => 'OMC Losses',
                        'type' => 'bar',
                        'data' => [$kwhLossesMonthTable['OMCLosses']],
                    ],

                ];
        }
        else {
            $chart->series =
                [
                    [
                        'name' => 'Expected G4N',
                        'type' => 'bar',
                        'data' => [$G4NmonthExpected],
                    ],
                    [
                        'name' => 'G4N Simulation',
                        'type' => 'bar',
                        'data' => [
                            $forecast[$month-2],
                        ],
                    ],
                    [
                    'name' => 'Actual',
                    'type' => 'bar',
                    'data' => [$ActualPower],
                    ],
                    [
                        'name' => 'SOR Losses',
                        'type' => 'bar',
                        'data' => [$kwhLossesMonthTable['SORLosses']],
                    ],
                    [
                        'name' => 'EFOR Losses',
                        'type' => 'bar',
                        'data' => [$kwhLossesMonthTable['EFORLosses']],
                    ],
                    [
                        'name' => 'OMC Losses',
                        'type' => 'bar',
                        'data' => [$kwhLossesMonthTable['OMCLosses']],
                    ],

                ];
        }
        $option = [
            'animation' => false,
            'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000'],
            'title' => [
                'text' => 'Production Monthly',
                'left' => 'center',
            ],
            'legend' => [
                'show' => true,
                'left' => 'center',
                'top' => 20,
            ],
            'grid' => [
                'height' => '80%',
                'top' => 80,
                'width' => '80%',
                'left' => 90,
            ],
        ];


        $chart->setOption($option);
        $losseskwhchart = $chart->render('Month_losses', ['style' => 'height: 350px; width:28cm; ']);

        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);
        $chart->xAxis = [
            'type' => 'category',
            'axisLabel' => [
                'show' => true,
                'margin' => '10',
            ],
            'borderType' => 'solid',
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
            'gridIndex' => 0
        ];
        if ($anlage->hasPVSYST()) {
            $chart->series =
                [
                    [
                        'name' => 'Expected G4N',
                        'type' => 'bar',
                        'data' => [$G4NyearExpected],
                        'visualMap' => 'false',
                    ],
                    [
                        'name' => 'Expected PV SYST',
                        'type' => 'bar',
                        'data' => [
                            $PVSYSTyearExpected,
                        ],
                        'visualMap' => 'false',
                    ],
                    [
                        'name' => 'G4N Simulation',
                        'type' => 'bar',
                        'data' => [
                            $forecastSum[$month-2],
                        ],
                        'visualMap' => 'false',
                    ],
                    [
                        'name' => 'Actual',
                        'type' => 'bar',
                        'data' => [$ActualPowerYear],
                        'visualMap' => 'false',
                    ],

                ];
        }
        else {
            $chart->series =
                [
                    [
                        'name' => 'Expected G4N',
                        'type' => 'bar',
                        'data' => [$G4NyearExpected],
                        'visualMap' => 'false',
                    ],
                    [
                        'name' => 'G4N Simulation',
                        'type' => 'bar',
                        'data' => [
                            $forecastSum[$month-2],
                        ],
                        'visualMap' => 'false',
                    ],
                    [
                        'name' => 'Actual',
                        'type' => 'bar',
                        'data' => [$ActualPowerYear],
                        'visualMap' => 'false',
                    ],
                ];
        }
        $option = [
            'yaxis' => ['scale' => false, 'min' => 0],
            'animation' => false,
            'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000'],
            'title' => [
                'text' => 'Production Monthly',
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
                'top' => 80,
                'width' => '80%',
                'left' => 90,
            ],
        ];


        $chart->setOption($option);
        $losseskwhchartyear = $chart->render('Year_losses', ['style' => 'height: 350px; width:28cm; ']);

        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

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
        for ($inverter = 1; $inverter <= $inverters; ++$inverter) {
            $pa = [];
            for ($day = 1; $day <= $daysInReportMonth; ++$day) {
                $tempFrom = new \DateTime($report['reportYear'].'-'.$report['reportMonth']."-$day 00:00");
                $tempTo = new \DateTime($report['reportYear'].'-'.$report['reportMonth']."-$day 23:59");
                $pa[] = [
                    'form_date' => $day,
                    'pa' => $this->availability->calcAvailability($anlage, $tempFrom, $tempTo, $inverter, 0),
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

        $revenuesSumPVSYST[0] = $incomePerMonth['revenues_act'][0];
        $revenuesSumG4N[0] = $incomePerMonth['revenues_act'][0];
        $revenuesSumForecast[0] = $incomePerMonth['powerExp'][0];
        $P50SumPVSYS[0] = $incomePerMonth['PVSYST_plan_proceeds_EXP'][0];
        $P50SumG4N[0] = $incomePerMonth['gvn_plan_proceeds_EXP'][0];
        $costSum[0] = $economicsMandy[0];
        for ($i = 1; $i < 12; ++$i) {
            $costSum[$i] = $costSum[$i - 1] + $economicsMandy[$i];

            //$revenuesSumPVSYST[$i] = $economicsMandy[$i] + $revenuesSumPVSYST[$i - 1];
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
                    'name' => 'Actual plus Forecast g4n',
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
                    'name' => 'Expected g4n plus Forecast g4n',
                    'type' => 'line',
                    'data' => $economicsCumulatedForecast['revenues_ACT_and_Revenues_Plan_Forecast'],
                    'visualMap' => 'false',
                ],
                [
                    'name' => 'Forecast g4n P50',
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

                $chart->series =
                    [
                        [
                            'name' => 'Difference ACT to PVSYST',
                            'type' => 'line',
                            'data' => $diefference_prod_to_pvsyst,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference ACT to expected g4n',
                            'type' => 'line',
                            'data' => $diefference_prod_to_expected_g4n,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference ACT to forecast g4n',
                            'type' => 'line',
                            'data' => $difference_prod_to_forecast,
                            'visualMap' => 'false',
                        ],
                    ];

        } else {

                $chart->series =
                    [
                        [
                            'name' => 'Difference ACT to expected g4n',
                            'type' => 'line',
                            'data' => $diefference_prod_to_expected_g4n,
                            'visualMap' => 'false',
                        ],
                        [
                            'name' => 'Difference ACT to forecast g4n',
                            'type' => 'line',
                            'data' => $difference_prod_to_forecast,
                            'visualMap' => 'false',
                        ],
                    ];


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
                    'name' => 'Forecast g4n',
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
        unset($option);
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
                    'name' => 'Forecast g4n - proceeds',
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
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

        // end Operating Statement

        // beginn Losses compared

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
        $chart->tooltip = [];
        $chart->xAxis = [];
        $chart->yAxis = [];
        $chart->series = [];
        unset($option);

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

        $TicketAvailabilityMonthTable =$this->PRCalulation->calcPR( $anlage, date_create(date("Y-m-d ",strtotime($report['from']))), date_create(date("Y-m-d ",strtotime($report['to']))));
        $TicketAvailabilityYearTable = $this->PRCalulation->calcPR( $anlage, date_create(date("Y-m-d ",strtotime($report['from']))), date_create(date("Y-m-d ",strtotime($report['to']))), "year");


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
            'wkhLossesTicketChartMonth' => $lossesTicketkwhchart,
            'TicketAvailabilityMonthTable' => $TicketAvailabilityMonthTable,
            'TicketAvailabilityYearTable' => $TicketAvailabilityYearTable
        ];

        return $output;
    }
}