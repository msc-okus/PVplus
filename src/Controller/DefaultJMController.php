<?php

namespace App\Controller;

use _PHPStan_adbc35a1c\Nette\Utils\DateTime;
use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Repository\TicketRepository;
use App\Service\TicketsGeneration\InternalAlertSystemService;
use App\Service\TicketsGeneration\AlertSystemV2Service;
use App\Service\AssetManagementService;
use App\Service\FunctionsService;
use App\Service\PdfService;
use App\Service\PRCalulationService;
use App\Service\WeatherServiceNew;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PDO;
use Hisune\EchartsPHP\ECharts;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[IsGranted('ROLE_G4N')]
class DefaultJMController extends AbstractController
{
    use G4NTrait;

    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private Environment $twig,
        private PdfService $pdf,
        private FunctionsService $functions,
        private PRCalulationService $PRCalulation,
        private ReportsRepository $reportRepo,
    )
    {

    }



    #[Route(path: '/generate/tickets', name: 'generate_tickets')]
    public function generateTickets(AnlagenRepository $anlagenRepository, TicketRepository $ticketRepo, EntityManagerInterface $em, AlertSystemV2Service $alertServiceV2)
    {
        $fromDate = "2023-11-01 00:00";
        $toDate = "2023-11-16 00:00";
        $anlagen[] = $anlagenRepository->findIdLike("56")[0];
        $anlagen[] = $anlagenRepository->findIdLike("233")[0];//faulty included in purpose
        $anlagen[] = $anlagenRepository->findIdLike("219")[0];
        $anlagen[] = $anlagenRepository->findIdLike("231")[0];
        $anlagen[] = $anlagenRepository->findIdLike("182")[0];
        $anlagen[] = $anlagenRepository->findIdLike("112")[0];
        $anlagen[] = $anlagenRepository->findIdLike("184")[0];

        $fromStamp = strtotime($fromDate);
        $toStamp = strtotime($toDate);
        foreach ($anlagen as $anlage) {
            $tickets = $ticketRepo->findForSafeDelete($anlage, $fromDate, $toDate);
            try {
                foreach ($tickets as $ticket) {
                    $dates = $ticket->getDates();
                    foreach ($dates as $date) {
                        $em->remove($date);
                    }
                    $em->remove($ticket);
                }
                $em->flush();
                for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {


                    $alertServiceV2->generateTicketsInterval($anlage, date('Y-m-d H:i:00', $stamp));
                }
            }catch(\SQLiteException $e){}
        }

        dd("hello world");
    }

    #[Route(path: '/test/time', name: 'default_time')]
    public function testTime(AnlagenRepository $anlagenRepository)
    {
        $anlagen = $anlagenRepository->findAllActiveAndAllowed();

        foreach ($anlagen as $anlage){
            $timeZone = new DateTimeZone($this->getNearestTimezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(),strtoupper($anlage->getCountry())));
            dump($timeZone->getName());
            date_default_timezone_set($timeZone->getName());
            dump(new DateTime('now'));
        }

        dd("hello World");
    }


    #[Route(path: '/test/createticket', name: 'default_check')]
    public function check(AnlagenRepository $anlagenRepository, InternalAlertSystemService $service)
    {
        $anlage = $anlagenRepository->findIdLike("96")[0];
        $fromStamp = strtotime("2023-09-26 00:00");
        $toStamp = strtotime("2023-09-27 14:00");
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
            $service->checkSystem($anlage, date('Y-m-d H:i:00', $stamp));
        }
        dd("hello World");
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

    #[Route(path: '/test/pdfw', name: 'default_pdf')]
    public function testpdfwaterfall(FunctionsService $fs, AnlagenRepository $ar, WeatherServiceNew $weather, AssetManagementService $am): \Symfony\Component\HttpFoundation\Response{
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
                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],
                    'data' => $help,
                ],
                [
                    'name' => 'positive',
                    'type' => 'bar',
                    'stack' => 'x',
                    'data' => $positive,

                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],

                ],
                [
                    'name' => 'negative',
                    'type' => 'bar',
                    'stack' => 'x',
                    'data' => $negative,
                    'itemStyle'=>[
                        'color'=>'#f33'
                    ],

                    'label' => [
                        'show' => true,
                        'position' => 'inside'
                    ],

                ],

            ];

        $option =[
            'animation' => false,
        ];
        $chart->setOption($option);
        $test = $chart->render('test', ['style' => 'height: 450px; width:900px;']);

        $view = $this->renderView('report/test.html.twig', [
            'anlage' => $anlage,
            'monthName' => 'December',
            'year' => '2023',

            'graph' => $test,

        ]);

                return $this->render('reporting/showHtml.html.twig', [
                    'html' => $view,
                ]);


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
    #[Route(path: '/test/sftp', name: 'default_sftp_test')]
    public function sftpTest($fileSystemFtp, AnlagenRepository $ar, EntityManagerInterface $em){
        $anlage = $ar->findIdLike(54);
        $reportArray = $this->reportRepo->findOneByAMYT(null, "", "2023","monthly-report");
        foreach ($reportArray as $report){

            $file = str_replace("/usr/home/pvpluy/public_html/public", "./pdf", $report->getFile());
            $file = str_replace("//", "/", $file);
           $report->setFile($file);

           $em->persist($report);
        }
        $em->flush();
        dd($fileSystemFtp);

    }

}
