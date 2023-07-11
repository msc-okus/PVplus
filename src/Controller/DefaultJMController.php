<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Entity\Ticket;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use App\Service\AlertSystemService;
use App\Service\AlertSystemV2Service;
use App\Service\AlertSystemWeatherService;
use App\Service\AssetManagementService;
use App\Service\Charts\IrradiationChartService;
use App\Service\FunctionsService;
use App\Service\MessageService;
use App\Service\PdfService;
use App\Service\PRCalulationService;
use App\Service\WeatherServiceNew;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PDO;
use Hisune\EchartsPHP\Doc\IDE\XAxis;
use Hisune\EchartsPHP\ECharts;
use Twig\Environment;

/**
 * @IsGranted("ROLE_G4N")
 */
class DefaultJMController extends AbstractController
{
    private functionsService $functions;
    use G4NTrait;
    public function __construct(
        private Environment $twig,
        private PdfService $pdf,
        FunctionsService $functions,
        private PRCalulationService $PRCalulation,
    )
    {
        $this->functions = $functions;
    }
    #[Route(path: '/default/j/m', name: 'default_j_m')]
    public function index() : Response
    {
        return $this->render('default_jm/index.html.twig', [
            'controller_name' => 'DefaultJMController',
        ]);
    }

    #[Route(path: '/test/createticket', name: 'default_check')]
    public function check(AnlagenRepository $anlagenRepository, AlertSystemV2Service $service)
    {
        $anlage = $anlagenRepository->findIdLike("184")[0];
        $fromStamp = strtotime("2022-04-01 ");
        $toStamp = strtotime("2022-12-31");
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
            $service->generateTicketsInterval($anlage, date('Y-m-d H:i:00', $stamp));
        }
        dd("hello World");
    }


    #[Route(path: '/test/read', name: 'default_read')]
    public function testread(FunctionsService $fs, AnlagenRepository $ar, WeatherServiceNew $weather, AssetManagementService $am){
        $anlage = $ar->findIdLike("207")[0];
        dd($anlage->getMinIrrThreshold());

        return $this->render('base.html.twig');// this is suposed to never run so no problem
    }
    #[Route(path: '/test/pdf', name: 'default_pdf')]
    public function testpdf(FunctionsService $fs, AnlagenRepository $ar, WeatherServiceNew $weather, AssetManagementService $am){

        $anlage = $ar->findIdLike("110")[0];


        $inverterPRArray = $this->calcPRInvArrayDayly($anlage, "01", "2023");
        $orderedArray = [];
        dump($inverterPRArray);
        $index = 0;
        $index2 = 0;
        while (count($inverterPRArray['invPR']) !== 0){
            $keys = array_keys($inverterPRArray['invPR'], min($inverterPRArray['invPR']));
            foreach($keys as $key ){
                $orderedArray[$index2][$index]['name'] = $inverterPRArray['name'][$key];
                $orderedArray[$index2][$index]['powerSum'] = $inverterPRArray['powerSum'][$key];
                $orderedArray[$index2][$index]['Pnom'] = $inverterPRArray['Pnom'][$key];
                $orderedArray[$index2][$index]['power'] = $inverterPRArray['power'][$key];
                $orderedArray[$index2][$index]['avgPower'] = $inverterPRArray['avgPower'][$key];
                $orderedArray[$index2][$index]['avgIrr'] = $inverterPRArray['avgIrr'][$key];
                $orderedArray[$index2][$index]['theoPower'] = $inverterPRArray['theoPower'][$key];
                $orderedArray[$index2][$index]['invPR'] = $inverterPRArray['invPR'][$key];
                $orderedArray[$index2][$index]['calcPR'] = $inverterPRArray['calcPR'][$key];
                $graphDataPR[$index2]['name'][] = $inverterPRArray['name'][$key];
                $graphDataPR[$index2]['PR'][]= $inverterPRArray['invPR'][$key];
                $graphDataPR[$index2]['power'][]= $inverterPRArray['power'][$key];
                $graphDataPR[$index2]['yield'] = $inverterPRArray['calcPR'][$key];
                unset($inverterPRArray['invPR'][$key]);
                $index = $index + 1;
                if ($index > 50){
                    $index = 0;
                    $index2 = $index2 + 1;
                }
            }
        }

        dd($graphDataPR['PR'],$graphDataPR['name']);
        foreach($graphDataPR as $key => $data) {
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
                'data' => $graphDataPR['name'],
            ];
            $chart->yAxis = [
                [
                    'type' => 'value',
                    'min' => 0,
                    'name' => 'kWh/kWp',
                    'nameLocation' => 'middle',
                ],
                [
                    'type' => 'value',
                    'min' => 0,
                    'max' => 100,
                    'alignTicks' => true,
                    'name' => '[%]',
                    'nameLocation' => 'middle',

                ]
            ];
            $chart->series =
                [
                    [
                        'name' => 'specific yield',
                        'type' => 'bar',
                        'data' => $data['power'],
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
                                    'name' => 'calculated PR',
                                    'yAxis' => $data['yield'],
                                    'lineStyle' => [
                                        'type' => 'solid',
                                        'width' => 3,
                                        'color' => 'red'
                                    ]
                                ]
                            ],
                            'symbol' => 'none',
                        ]
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
                    'text' => 'TEST',
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
            $test[] = $chart->render('pr_graph_'.$key, ['style' => 'height: 450px; width:700px;']);
        }
        $html5 = $this->twig->render('report/test.html.twig', [
        'anlage' => $anlage,
        'monthName' => 'December',
        'year' => '2023',

        'graph' => $test,

        ]);
        $view = $this->renderView('report/test.html.twig', [
            'anlage' => $anlage,
            'monthName' => 'December',
            'year' => '2023',

            'graph' => $test,

        ]);
/*
        return $this->render('reporting/showHtml.html.twig', [
            'html' => $view,
        ]);
*/
        $html5 = str_replace('src="//', 'src="https://', $html5);
        $fileroute = "/test/AssetReport/waterfallgraphs/" ;
        $this->pdf->createPage($html5, $fileroute, "MonthlyProd", true);// we will store this later in the entity

    }
    private function calcPRInvArrayDayly(Anlage $anlage, $month, $year){
        // now we will cheat the data in but in the future we will use the params to retrieve the data
        $PRArray = []; // this is the array that we will return at the end with the inv name, power sum (kWh), pnom (kWp), power (kWh/kWp), avg power, avg irr, theo power, Inverter PR, calculated PR
        $invArray = $anlage->getInverterFromAnlage();
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);
        $invNr = count($invArray);

        for ($index = 1; $index <= $invNr; $index++) {
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $prValues = $this->PRCalulation->calcPRByInverterAM($anlage, $index, new \DateTime($year . "-" . $month . "-" . $day));
                $PRArray['name'] = $invArray[$index];
                $PRArray['invPR'][] = $prValues['prDep3Act'];
            }
            $megaArray[] = $PRArray;
            unset($PRArray);
        }
        dd($megaArray);
        return $PRArray;
    }
}
