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
        $anlage = $ar->findIdLike("57")[0];
        $am->createAmReport($anlage, "11", "2022");

        return $this->render('base.html.twig');// this is suposed to never run so no problem
    }
    #[Route(path: '/test/pdf', name: 'default_pdf')]
    public function testpdf(FunctionsService $fs, AnlagenRepository $ar, WeatherServiceNew $weather, AssetManagementService $am){

        $anlage = $ar->findIdLike("57")[0];


        $expected = 9000;
        $dccablelosses = -(int)($expected * (0.5/100));
        $inverterlosses = -(int)($expected * (1/100));
        $accablelosses = -(int)($expected * (0.8/100));
        $missmatchinglosses = -(int)($expected * (1/100));
        $transformerlosses = -(int)($expected * (1.5/100));
        $expKpi = $expected + $dccablelosses + $inverterlosses + $accablelosses + $missmatchinglosses + $transformerlosses;

        $data = [$expected, $dccablelosses, $inverterlosses, $accablelosses, $missmatchinglosses, $transformerlosses, $expKpi];
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

            if ($key === 0) $help[0] = 0;

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
            'data' =>['Expected', 'kpi1', 'kpi2', ' kpi3', 'kpi4', 'kpi5','ExpectedKpi'],
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
                   /* we cannot display the numbers because the bars are stacked, meaning that when we have negative values we will 0 the 0 of the possitive value
                   'label' => [
                       'show' => true,
                       'position' => 'inside'
                   ],
                   */
               ],
               [
                   'name' => 'negative',
                   'type' => 'bar',
                   'stack' => 'x',
                   'data' => $negative,
                   'itemStyle'=>[
                       'color'=>'#f33'
                   ],
                   /*
                   'label' => [
                       'show' => true,
                       'position' => 'inside'
                   ],
                   */
               ],

            ];

        $option =[
            'animation' => false,
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
