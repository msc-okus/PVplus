<?php

namespace App\Service\Reports;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Entity\TicketDate;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\Case5Repository;
use App\Repository\PRRepository;
use App\Repository\PvSystMonthRepository;
use App\Repository\ReportsRepository;
use App\Repository\TicketDateRepository;
use App\Repository\TicketRepository;
use App\Service\FunctionsService;
use App\Service\PdfService;
use App\Service\PRCalulationService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Hisune\EchartsPHP\ECharts;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use DateTime;

/**
 *
 */
class ReportsMonthlyV2Service
{
    use G4NTrait;

    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private $userPlant,
        private $passwordPlant,
        private AnlagenRepository $anlagenRepository,
        private PRRepository $PRRepository,
        private ReportsRepository $reportsRepository,
        private EntityManagerInterface $em,
        private PvSystMonthRepository $pvSystMonthRepo,
        private Case5Repository $case5Repo,
        private FunctionsService $functions,
        private NormalizerInterface $serializer,
        private PRCalulationService $PRCalulation,
        private ReportService $reportService,
        private TicketRepository $ticketRepo,
        private TicketDateRepository $ticketDateRepo,
        private Environment $twig,
        private PdfService $pdf,
        private TranslatorInterface $translator)
    {
    }

    /**
     * @param Anlage $anlage
     * @param int $reportMonth
     * @param int $reportYear
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function createReportV2(Anlage $anlage, int $reportMonth = 0, int $reportYear = 0): string
    {
        $output = '';
        $report['month'] = $reportMonth;
        $report['year'] = $reportYear;

        $report['overviews'] = $this->buidOverview($anlage, null, null, $reportMonth, $reportYear);
        $report['days'] = $this->buildTable($anlage, null, null, $reportMonth, $reportYear);
        $report['chart1'] = $this->buildChart($anlage, $report['days']);
        $report['tickets'] = $this->buildPerformanceTicketsOverview($anlage, null, null, $reportMonth, $reportYear);
        $report['legend'] = null;

        $pathToPdf = "";
        /*try {
            $returnArray = $this->createPDF($anlage, $report);
        } catch (LoaderError $e) {
            throw new Exception('PDF Loader Error');
        } catch (RuntimeError $e) {
            throw new Exception('PDF Runtime Error');
        } catch (SyntaxError $e) {
            throw new Exception('PDF Syntax Error');
        }*/
        $returnArray = $this->createPDF($anlage, $report);
        $pathToPdf = $returnArray['path'];
        $html = $returnArray['html'];
        unset($returnArray);

        // Store to Database
        $reportEntity = new AnlagenReports();
        $startDate = new \DateTime("$reportYear-$reportMonth-01");
        $endDate = new \DateTime($startDate->format('Y-m-t'));

        $reportEntity
            ->setCreatedAt(new \DateTime())
            ->setAnlage($anlage)
            ->setEigner($anlage->getEigner())
            ->setReportType('monthly-report')
            ->setReportTypeVersion(2)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setMonth($startDate->format('n'))
            ->setYear($startDate->format('Y'))
            ->setRawReport($html)
            ->setContentArray($report)
            ->setFile($pathToPdf);
        $this->em->persist($reportEntity);
        $this->em->flush();

        return $html;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function buidOverview(Anlage $anlage, ?int $startDay = null, ?int $endDay = null, int $month = 0, int $year = 0): array
    {
        $overview = [];
        if ($startDay === null) $startDay = 1;
        $daysInMonth = (int)date('t', strtotime("$year-$month-01"));
        if ($endDay  !== null && $endDay < $daysInMonth) {
            $daysInMonth = $endDay;
        }

        // calculate PR and related data for the current month
        $fromDay = new \DateTime("$year-$month-$startDay 00:00");
        $toDay = new \DateTime("$year-$month-$daysInMonth 23:59");
        $prSumArray = $this->PRCalulation->calcPR($anlage, $fromDay, $toDay);
        $overview['power'][0] = [
            'period'    => 'Month',
            'grid'      => $prSumArray['powerEvu'],
            'inverter'  => $prSumArray['powerAct'],
            'expected'  => $prSumArray['powerExp'],
        ];
        $overview['pr'][0] = [
            'period'    => 'Month',
            'grid'      => $prSumArray['prDep1Evu'],
            'inverter'  => $prSumArray['prDep1Act'],
            'expected'  => $prSumArray['prDep1Exp'],
            'pa'        => $prSumArray['pa2'],
        ];
        $fromDay = new \DateTime("$year-01-01 00:00");
        $toDay = new \DateTime("$year-$month-$daysInMonth 23:59");
        $prSumArray = $this->PRCalulation->calcPR($anlage, $fromDay, $toDay);
        $overview['power'][1] = [
            'period'    => "Total Year ($year)",
            'grid'      => $prSumArray['powerEvu'],
            'inverter'  => $prSumArray['powerAct'],
            'expected'  => $prSumArray['powerExp'],
        ];
        $overview['pr'][1] = [
            'period'    => "Total Year ($year)",
            'grid'      => $prSumArray['prDep1Evu'],
            'inverter'  => $prSumArray['prDep1Act'],
            'expected'  => $prSumArray['prDep1Exp'],
            'pa'        => $prSumArray['pa1'],
        ];

        return $overview;
    }


    private function buildChart(Anlage $anlage, array $table): string
    {
        $days = count($table)-1;
        $xAxis = $yAxis = [];

        for ($n = 1; $n <= $days; ++$n) {
            $xAxis[] = $table[$n]['datum_alt'];
            $number = $anlage->getUseGridMeterDayData() ? $table[$n]['powerEGridExt'] : $table[$n]['powerEvu'];
            $yAxis[] = round($number,2);
        }
        $chart = new ECharts();

        $chart->xAxis[] = [
            'type' => 'category',
            'data' => $xAxis,
            'axisLabel' => [
                'rotate' => 30,
            ],
        ];
        $chart->yAxis[] = [
            'type' => 'value',
            'splitLine' => [
                'lineStyle' => [
                    'type' => 'dashed',
                ],
            ],
            'axisLabel' => [
                'formatter' => '{value} MWh',
                'align' => 'right',
            ],
        ];

        $chart->series[] = [
            'type' => 'bar',
            'data' => $yAxis,
            'visualMap' => false,
            'label' => [
                'show' => true,
                'position' => 'inside',
                'formatter' => '{c}',
                'rotate' => 90,
            ],
        ];

        $options = [
            'animation' => false,
            'color' => ['#3366CC'],
            'grid' => [
                'top' => 50,
                'left' => 120,
                'width' => '85%',
            ],
        ];
        $chart->setOption($options);

        return $chart->render('chartPower', ['style' => 'height: 250px; margin-bottom: 40px;', 'renderer' => 'svg']);
    }


    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function buildTable(Anlage $anlage, ?int $startDay = null, ?int $endDay = null, int $month = 0, int $year = 0): array
    {
        $dayValues = [];
        if ($startDay === null) $startDay = 1;
        $daysInMonth = (int)date('t', strtotime("$year-$month-01"));
        if ($endDay  !== null && $endDay < $daysInMonth) {
            $daysInMonth = $endDay;
        }

        // begin create Array for Day Values Table
        for ($i = $startDay; $i <= $daysInMonth; ++$i) {
            // Table
            $day = new \DateTime("$year-$month-$i 12:00");
            $prArray = $this->PRCalulation->calcPR($anlage, $day);

            $dayValues[$i]['datum'] = $day->format('Y-m-d');
            $dayValues[$i]['datum_alt'] = $day->format('m-d');
            foreach($prArray as $key => $value) {
                $dayValues[$i][$key] = $value;
            }
        }
        unset($prArray);

        // calculate PR and related data for the current month
        $fromDay = new \DateTime("$year-$month-$startDay 00:00");
        $toDay = new \DateTime("$year-$month-$daysInMonth 23:59");
        $prSumArray = $this->PRCalulation->calcPR($anlage, $fromDay, $toDay);

        // Summe / Total Row
        $i = $daysInMonth + 1;
        $dayValues[$i]['datum'] = 'Total';
        foreach($prSumArray as $key => $value) {
            $dayValues[$i][$key] = $value;
        }

        return $dayValues;
    }

    private function buildPerformanceTicketsOverview(Anlage $anlage, ?int $startDay = null, ?int $endDay = null, int $month = 0, int $year = 0): array
    {
        if ($startDay === null) $startDay = 1;
        $daysInMonth = (int)date('t', strtotime("$year-$month-01"));
        if ($endDay  !== null && $endDay < $daysInMonth) {
            $daysInMonth = $endDay;
        }
        $from = date_create("$year-$month-$startDay 00:00");
        $to = date_create("$year-$month-$daysInMonth 23:59");
        #$tickets = $this->ticketRepo->findBy(['anlage' => $anlage->getAnlId(), 'kpiStatus' => '10', 'alertType' => '72']);

        $tickets = $this->ticketDateRepo->performanceTickets($anlage, $from, $to);
        $ticketsOverview = [];
        /** @var TicketDate $ticket */
        $counter = 1;
        foreach ($tickets as $ticket){
            $ticketsOverview[$counter]['start'] = $ticket->getBegin()->format("d.m.y H:i");
            $ticketsOverview[$counter]['end'] = $ticket->getEnd()->format("d.m.y H:i");
            $ticketsOverview[$counter]['type'] = $this->translator->trans("ticket.error.category.".$ticket->getAlertType());
            $ticketsOverview[$counter]['editor'] = $ticket->getTicket()->getEditor();
            ++$counter;
        }
        return $ticketsOverview;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    private function createPDF(Anlage $anlage, array $report): array
    {
        $html = $this->twig->render('report/monthly/monthlyReport.html.twig', [
            'anlage'        => $anlage,
            'report'        => $report,
        ]);

        $html = str_replace('src="//', 'src="https://', $html); // replace local pathes to global
        $fileroute = $anlage->getEigner()->getFirma()."/".$anlage->getAnlName() . '/MonthlyReport/'  ;
        return [
            'path' => $this->pdf->createPage($html, $fileroute, "Monthly_Report_" . $report['month'] . "_" . $report['year'] ."_". time() , false), // we will store this later in the entity
            'html' => $html,
        ];
    }
}