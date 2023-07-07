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
        FunctionsService $functions
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


        $chart = new ECharts(); // We must use AMCharts
        $chart->tooltip->show = false;

        $chart->xAxis = [
            'type' => 'category',
            'data' => ['Jan', 'Feb'],
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
            'data' =>  [ 70, 75],
            'label' => [
                'show' => true,
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
                'name' => $anlage->getSettings(),
                'type' => 'bar',
                'data' => [ 90, 100],
                'label' => [
                    'show' => true,
                ],
            ];
        if( !$anlage->getSettings()->isDisableDep2()) $series[] = [
            'name' => 'EPC',
            'type' => 'bar',
            'data' =>  [ 80, 85],
            'label' => [
                'show' => true,
            ],
        ];
        if( !$anlage->getSettings()->isDisableDep3()) $series[] =   [
            'name' => 'AM',
            'type' => 'bar',
            'data' =>  [ 90, 95],
            'label' => [
                'show' => true,
            ],
        ];
        $chart->series = $series;


        $option = [
            'animation' => false,
            'color' => ['#698ed0', '#f1975a', '#b7b7b7', '#ffc000'],
            'title' => [
                'fontFamily' => 'monospace',
                'text' => 'PA Graphic',
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


        $test = $chart->render('test', ['style' => 'height: 450px; width:900px;']);


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
}
