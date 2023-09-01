<?php

namespace App\Controller;
use App\Service\GetPdoService;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use App\Repository\Case5Repository;
use App\Repository\WeatherStationRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\AvailabilityService;
use App\Service\CheckSystemStatusService;
use App\Service\ExpectedService;
use App\Service\ExportService;
use App\Service\Functions\SensorService;
use App\Service\FunctionsService;
use App\Service\PRCalulationService;
use App\Service\ReportEpcPRNewService;
use App\Service\Reports\ReportsMonthlyService;
use App\Service\Reports\ReportsMonthlyV2Service;
use App\Service\WeatherFunctionsService;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\NonUniqueResultException;
use JetBrains\PhpStorm\NoReturn;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Psr\Cache\InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @IsGranted("ROLE_G4N")
 */
class DefaultMREController extends BaseController
{
    use G4NTrait;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private PRCalulationService $prCalulation,
        private AvailabilityByTicketService $availabilityByTicket,
        private AvailabilityService $availabilityService
    )
    {
    }

    #[Route(path: '/mr/status')]
    public function updateStatus(CheckSystemStatusService $checkSystemStatus): Response
    {
        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Update Systemstatus',
            'availabilitys' => '',
            'output' => $checkSystemStatus->checkSystemStatus(),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route(path: '/mr/expected/{plant}', defaults: ['plant' => 57])]
    public function updateExpected($plant, ExpectedService $expectedService, AnlagenRepository $anlagenRepository): Response
    {
        $anlage = $anlagenRepository->find($plant);
        $from = '2023-06-02 13:00'; //date('Y-m-d 00:00');
        $to = date('Y-m-d 13:59');

        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Update Systemstatus',
            'availabilitys' => '',
            'output' => $expectedService->storeExpectedToDatabase($anlage, $from, $to),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/mr/pa/test/{plant}/{year}/{month}/{day}', defaults: ['plant' => 108, 'year' => 2022, 'month' => 3, 'day' => 31])]
    public function testPA(int $plant, int $year, int $month, int $day, AvailabilityService $availability, AvailabilityByTicketService $availabilityByTicket, AnlagenRepository $anlagenRepository): Response
    {
        $anlage = $anlagenRepository->find($plant);
        $output = "";
        $date = date_create("$year-$month-01 12:00");
        if ($day === 0) {
            $daysInMonth = 23;
            $daysInMonth = $date->format("t");
            $startday = 1;
        } else {
            $startday = $daysInMonth = $day;
        }
        for ($day = $startday; $day <= $daysInMonth; $day++) {
            $from = date_create("$year-$month-$day 12:00");

            #$output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 0)."<br>";
            #$output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 1)."<br>";
            $output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 2)."<br>";
            #$output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 3)."<br>";
            $output .= "PA: " . number_format(round($this->availabilityByTicket->calcAvailability($anlage, date_create("$year-$month-$day"), date_create("$year-$month-$day"), null, 2), 3),'3') . "<br>";

            #
        }

        $availability = $this->availabilityByTicket->calcAvailability($anlage, date_create("$year-$month-01"), date_create("$year-$month-$daysInMonth"), null, 2);

        return $this->render('cron/showResult.html.twig', [
            'headline' => " PA Dep 2 – Year: $year Month: $month Days in month: $daysInMonth",
            'availabilitys' => '',
            'output' => "<hr>".$output."<hr>PA: $availability",
        ]);
    }


    #[Route(path: '/mr/export/rawdata/{id}')]
    public function exportRawDataExport($id, ExportService $exportService, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);
        $from = $anlage->getEpcReportStart();
        $to = $anlage->getEpcReportEnd();
        $from = date_create("2022-08-01 00:00");
        $to = date_create("2022-08-31 23:55");
        $output = $exportService->getRawData($anlage, $from, $to);

        return $this->render('cron/showResult.html.twig', [
            'headline' => $anlage->getAnlName().' RawData Export',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     */
    #[Route(path: '/mr/export/bavelse/rawdata')]
    public function exportBavelseRawDataExport(ExportService $exportService, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => '97']);
        $from = $anlage->getEpcReportStart();
        $to = $anlage->getEpcReportEnd();
        $from = date_create("2023-01-01 00:00");
        $to = date_create("2023-01-31 23:55");
        $output = $exportService->gewichtetBavelseValuesExport($anlage, $from, $to);

        return $this->render('cron/showResult.html.twig', [
            'headline' => $anlage->getAnlName().' RawData Export',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }

    #[Route(path: '/mr/export/facRawData/{id}', name: 'export_fac_daily')]
    public function exportFacRawDataExport($id, ExportService $export, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);
        $output .= self::printArrayAsTable($export->getFacPRData($anlage, $anlage->getEpcReportStart(), $anlage->getEpcReportEnd()));
        $output .= '<hr>';
        // $output .= self::printArrayAsTable($export->getFacPAData($anlage, $from, $to));
        $output .= '<hr>';

        return $this->render('cron/showResult.html.twig', [
            'headline' => $anlage->getAnlName().' FacData Export',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }


    #[Route(path: '/test/epc/{id}', defaults: ['id' => 92])]
    public function testNewEpc($id, AnlagenRepository $anlagenRepository, FunctionsService $functions, ReportEpcPRNewService $epcNew, Environment $twig, Pdf $pdf): Response
    {
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);
        $date = date_create('2022-09-01 00:00');
        $result = $epcNew->monthTable($anlage, $date);
        $pldTable = $epcNew->pldTable($anlage, $result->table, $date);
        $forcastTable = $epcNew->forcastTable($anlage, $result->table, $pldTable, $date);
        // $chartYieldPercenDiff = $epcNew->chartYieldPercenDiff($anlage, $result->table, $date);
        // $chartYieldCumulativ = $epcNew->chartYieldCumulative($anlage, $result->table, $date);

        // $output = "<br>riskForecastUpToDate: ". $result->riskForecastUpToDate . "<br>riskForecastRollingPeriod: " . $result->riskForecastRollingPeriod;

        $html = $twig->render('report/epcReportPR.html.twig', [
            'anlage' => $anlage,
            'monthsTable' => $result->table,
            'forcast' => $forcastTable,
            'pldTable' => $pldTable,
            'legend' => $anlage->getLegendEpcReports(),
            // 'chart1'            => $chartYieldPercenDiff,
            // 'chart2'            => $chartYieldCumulativ,
        ]);

        #$output = $pdf->getOutputFromHtml($html, ['enable-local-file-access' => true]);
        return new PdfResponse(
            $pdf->getOutputFromHtml($html, ['enable-local-file-access' => true]),
            'file.pdf'
        );
    }

    /**
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    #[Route(path: '/test/monthly/{id}/{year}/{month}', defaults: ['id' => 188, 'year' => '2023', 'month' => '4'])]
    public function testNewMonthly($id, $year, $month, AnlagenRepository $anlagenRepository, ReportsMonthlyV2Service $reportsMonthly, SensorService $sensorService): Response
    {

        $date = date_create("$year-$month-01 12:00");
        $daysInMonth = $date->format("t");
        $anlage = $anlagenRepository->find($id);

        $output = $reportsMonthly->createReportV2($anlage, $month, $year);


        return $this->render('cron/showResult.html.twig', [
            'headline' => $anlage->getAnlName().' Test new Monthly Report',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route(path: '/test/pr')]
    public function testPR(AnlagenRepository $anlagenRepository, WeatherFunctionsService $weatherFunctions, SensorService $sensorService): Response
    {
        $startDate = date_create("2023-02-08 00:00");
        $endDate = date_create("2023-02-09 23:59");

        $anlage = $anlagenRepository->find('110');

        $weather = $weatherFunctions->getWeather($anlage->getWeatherStation(), $startDate->format('Y-m-d 00:00'), $endDate->format('Y-m-d 23:59'), false, $anlage);
        $output = "<h3>Vor der Einbindung der Tickets</h3> <pre>".print_r($weather, true)."</pre> <hr>";

        $weather = $sensorService->correctSensorsByTicket($anlage, $weather, $startDate, $endDate);
        $output .= "<h3>Nach der Einbindung der Tickets</h3><pre>".print_r($weather, true)."</pre>";

        return $this->render('cron/showResult.html.twig', [
            'headline' => $anlage->getAnlName().' PR Test für Ticket Einbindung',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }
}
