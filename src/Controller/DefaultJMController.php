<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Entity\Ticket;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use App\Service\TicketsGeneration\AlertSystemService;
use App\Service\TicketsGeneration\AlertSystemV2Service;
use App\Service\AlertSystemWeatherService;
use App\Service\AssetManagementService;
use App\Service\Charts\IrradiationChartService;
use App\Service\FunctionsService;
use App\Service\MessageService;
use App\Service\PdfService;
use App\Service\PRCalulationService;
use App\Service\TicketsGeneration\InternalAlertSystemService;
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
    use G4NTrait;
    private PDO $conn;
    public function __construct(
        private Environment $twig,
        private PdfService $pdf,
        private FunctionsService $functions,
        private PRCalulationService $PRCalulation,

    )
    {
        $this->conn = self::getPdoConnection();

    }
    #[Route(path: '/default/j/m', name: 'default_j_m')]
    public function index() : Response
    {
        return $this->render('default_jm/index.html.twig', [
            'controller_name' => 'DefaultJMController',
        ]);
    }

    #[Route(path: '/test/createticket', name: 'default_check')]
    public function check(AnlagenRepository $anlagenRepository, InternalAlertSystemService $service)
    {
        $anlage = $anlagenRepository->findIdLike("218")[0];
        $fromStamp = strtotime("2023-06-15 00:00");


        $service->generateTicketsInterval($anlage, date('Y-m-d H:i:00', $fromStamp));

        dd("hello World");
    }


    #[Route(path: '/test/read', name: 'default_read')]
    public function testread(FunctionsService $fs, AnlagenRepository $ar, WeatherServiceNew $weather, AssetManagementService $am){
        $anlage = $ar->findIdLike("110")[0];

        return $this->render('base.html.twig');// this is suposed to never run so no problem
    }
    #[Route(path: '/test/pdf', name: 'default_pdf')]
    public function testpdf(FunctionsService $fs, AnlagenRepository $ar, WeatherServiceNew $weather, AssetManagementService $am){
        $anlage = $ar->findIdLike("110")[0];
        $invArray = $anlage->getInverterFromAnlage();
        $efficiencyArray= $this->calcPRInvArrayDayly($anlage, "01", "2023");
        $orderedArray = [];
        $index = 0;
        $index2 = 0;
        $index3 = 0;
        dump($efficiencyArray);
        while (count($efficiencyArray['avg']) !== 0){
            $keys = array_keys($efficiencyArray['avg'], min($efficiencyArray['avg']));
            foreach($keys as $key ){
                $orderedArray[$index2]['avg'][$index] = $efficiencyArray['avg'][$key];
                $orderedArray[$index2]['names'][$index] = $invArray[$key];
                foreach ($efficiencyArray['values'][$key] as $value){
                    $orderedArray[$index2]['value'][$index3] = [$invArray[$key], $value];
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
        foreach($orderedArray as $key => $data) {
            $chart = new ECharts();
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
            $test[] = $chart->render('pr_graph_'.$key, ['style' => 'height: 550; width:900px;']);

        }
        $html5 = $this->twig->render('report/test.html.twig', [
        'anlage' => $anlage,
        'monthName' => 'December',
        'year' => '2023',

        'test' => $test,

        ]);


        $html5 = str_replace('src="//', 'src="https://', $html5);
        $fileroute = "/test/AssetReport/waterfallgraphs/" ;
        $this->pdf->createPage($html5, $fileroute, "MonthlyProd", true);// we will store this later in the entity

    }
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
                $output['avg'][$inverter] = round($efficiencySum / $efficiencyCount, 2);
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
        $output['avg'][$inverter ] = round($efficiencySum / $efficiencyCount, 2); //we make the last average outside of the loop
        return $output;
    }
}
