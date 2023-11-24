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
use App\Service\AvailabilityByTicketService;
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
use App\Service\PdoService;
use function GuzzleHttp\Psr7\str;

/**
 *
 */
class ReportsMonthlyV2Service
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly AnlagenRepository $anlagenRepository,
        private readonly PRRepository $PRRepository,
        private readonly ReportsRepository $reportsRepository,
        private readonly EntityManagerInterface $em,
        private readonly PvSystMonthRepository $pvSystMonthRepo,
        private readonly Case5Repository $case5Repo,
        private readonly FunctionsService $functions,
        private readonly NormalizerInterface $serializer,
        private readonly PRCalulationService $PRCalulation,
        private readonly ReportService $reportService,
        private readonly TicketRepository $ticketRepo,
        private readonly TicketDateRepository $ticketDateRepo,
        private readonly Environment $twig,
        private readonly PdfService $pdf,
        private readonly TranslatorInterface $translator,
        private readonly AvailabilityByTicketService $availabilityByTicket)
    {
    }

    /**
     * @param Anlage $anlage
     * @param int $reportMonth
     * @param int $reportYear
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
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
            if ($anlage->getShowEvuDiag()){
                $number = $anlage->getUseGridMeterDayData() ? $table[$n]['powerEGridExt'] : $table[$n]['powerEvu'];
            } else {
                $number = $table[$n]['powerAct'];
            }

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
        $daysInMonth = (int) date('t', strtotime("$year-$month-01"));
        if ($endDay  !== null && $endDay < $daysInMonth) {
            $daysInMonth = $endDay;
        }

        // begin create Array for Day Values Table
        for ($i = $startDay; $i <= $daysInMonth; ++$i) {
            // Table
            $day = new \DateTime("$year-$month-$i 12:00");
            $prArray = $this->PRCalulation->calcPR($anlage, $day);

            $dayValues[$i]['datum'] = $day->format('y-m-d');
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
        $dayValues[$i]['datum_alt'] = 'Total';
        foreach($prSumArray as $key => $value) {
            $dayValues[$i][$key] = $value;
        }
        return $dayValues;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function buildTable2(Anlage $anlage, DateTime $startDate, DateTime $endDate): array
    {
        #$startDay   = (int) $startDate->format('j');
        $startMonth = (int) $startDate->format('n');
        $startYear  = (int) $startDate->format('y');
        #$endDay     = (int) $endDate->format('j');
        $endMonth   = (int) $endDate->format('n');
        $endYear    = (int) $endDate->format('y');

        if ($endYear - $startYear === 0) {
            $numOfMonth = $endMonth - $startMonth + 1;
        } else {
            $numOfMonth = ($endYear - $startYear - 1) * 12 ;
            if ($startMonth > $endMonth) {
                $numOfMonth += 12 - $startMonth + $endMonth + 1;
            } else {
                $numOfMonth += 12 - $endMonth + $startMonth + 1;
            }

        }

        $currentYear = $startYear;
        $currentMonth = $startMonth;
        // begin create Array for monthly values Table
        for ($monthCount = 1; $monthCount <= $numOfMonth; ++$monthCount) {
            // Table
            switch ($monthCount){
                case 1 :
                    $startDay = (int) $startDate->format('j');
                    $endDay = (int) date('t', strtotime("$currentYear-$startMonth-01"));
                    $monthValues[$monthCount]['datum'] = date("Y-m-d -->",strtotime("$currentYear-$currentMonth-$startDay"));
                    $monthValues[$monthCount]['datum_alt'] = date("Y-m-d -->",strtotime("$currentYear - $currentMonth - $startDay"));
                    break;
                case $numOfMonth:
                    $startDay = 1;
                    $endDay = (int) $endDate->format('j');
                    $monthValues[$monthCount]['datum'] = date("--> Y-m-d",strtotime("$currentYear-$currentMonth-$endDay"));
                    $monthValues[$monthCount]['datum_alt'] = date("--> Y-m-d",strtotime("$currentYear-$currentMonth-$endDay"));
                    break;
                default:
                    $startDay = 1;
                    $endDay = (int) date('t', strtotime("$currentYear-$startMonth-01"));
                    $monthValues[$monthCount]['datum'] = date("M Y",strtotime("$currentYear-$currentMonth-1"));
                    $monthValues[$monthCount]['datum_alt'] = date("M Y",strtotime("$currentYear-$currentMonth-1"));
            }
            $localStartDate = new \DateTime("$currentYear-$currentMonth-$startDay 12:00");
            $localEndDate = new \DateTime("$currentYear-$currentMonth-$endDay 12:00");
            $prArray = $this->PRCalulation->calcPR($anlage, $localStartDate, $localEndDate);


            foreach($prArray as $key => $value) {
                $monthValues[$monthCount][$key] = $value;
            }
            $currentMonth++;
            if ($currentMonth === 13){
                $currentYear++;
                $currentMonth = 1;
            }
        }
        unset($prArray);

        // calculate PR and related data for the hole time
        $prSumArray = $this->PRCalulation->calcPR($anlage, $startDate, $endDate);

        // Summe / Total Row

        $monthValues[$numOfMonth+1]['datum'] = 'Total';
        $monthValues[$numOfMonth+1]['datum_alt'] = 'Total';
        foreach($prSumArray as $key => $value) {
            $monthValues[$numOfMonth+1][$key] = $value;
        }

        return $monthValues;
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
        $html = $this->twig->render('report/_monthly/monthlyReport2023.html.twig', [
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