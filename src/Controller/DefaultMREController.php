<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use App\Repository\Case5Repository;
use App\Service\AvailabilityByTicketService;
use App\Service\AvailabilityService;
use App\Service\CheckSystemStatusService;
use App\Service\ExportService;
use App\Service\FunctionsService;
use App\Service\PRCalulationService;
use App\Service\ReportEpcPRNewService;
use App\Service\WeatherServiceNew;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    #[Route(path: '/mr/sun')]
    public function testSunRise(WeatherServiceNew $weatherService, AnlagenRepository $anlagenRepository): Response
    {
        $anlage = $anlagenRepository->find('112');
        $time = time();
        $time = strtotime('2022-05-23');

        $sunrisedatas = date_sun_info($time, (float)$anlage->getAnlGeoLat(), (float)$anlage->getAnlGeoLon());
        foreach ($sunrisedatas as $key => $value) {
            $sunriseArray[] = ['Key' => $key, "Stamp" => date('Y-m-d H:i', $value)];
        }
        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Sunrise / Sunset',
            'availabilitys' => '',
            'output' => self::printArrayAsTable($sunriseArray),
        ]);
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
     * @throws \Exception
     */
    #[Route(path: '/mr/pa/test/{plant}/{year}/{month}', defaults: ['plant' => '95', 'year' => '2022', 'month' => '6'])]
    public function pa(string $plant, string $year, string $month, AvailabilityService $availability, AvailabilityByTicketService $availabilityByTicket, AnlagenRepository $anlagenRepository): Response
    {
        $anlage = $anlagenRepository->find($plant);
        $output = "";
        $date = date_create("$year-$month-01 12:00");
        $daysInMonth = 23;
        $daysInMonth = $date->format("t");
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $from = date_create("$year-$month-$day 12:00");
            #$output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 0);
            #$output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 1);
            $output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 2);
            $output .= "PA: " . number_format(round($this->availabilityByTicket->calcAvailability($anlage, date_create("$year-$month-$day"), date_create("$year-$month-$day"), null, 2), 3),'3') . "<br>";

            #$output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 3);
        }

        $availability = $this->availabilityByTicket->calcAvailability($anlage, date_create("$year-$month-01"), date_create("$year-$month-$daysInMonth"), null, 2);

        return $this->render('cron/showResult.html.twig', [
            'headline' => " PA Dep 2 – Year: $year Month: $month Days in month: $daysInMonth",
            'availabilitys' => '',
            'output' => "<hr>".$output."<hr>PA: $availability",
        ]);
    }

    #[Route(path: '/mr/bavelse/export/{year}/{month}')]
    public function bavelseExport($year, $month, ExportService $bavelseExport, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => '97']);
        $from = date_create($year.'-'.$month.'-01');
        if ($month == 12) {
            $to = date_create(($year+1).'-01-01');
        } else {
            $to = date_create($year.'-'.($month+1).'-01');
        }
        $output = $bavelseExport->gewichtetTagesstrahlung($anlage, $from, $to);

        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Systemstatus',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }

    #[Route(path: '/mr/export/rawdata/{id}')]
    public function exportRawDataExport($id, ExportService $bavelseExport, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);
        $from = date_create('2021-01-01');
        $to = date_create('2021-10-31');
        $output = $bavelseExport->getRawData($anlage, $from, $to);

        return $this->render('cron/showResult.html.twig', [
            'headline' => $anlage->getAnlName().' RawData Export',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }

    #[Route(path: '/mr/export/facRawData/{id}')]
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
    public function testNewEpc($id, AnlagenRepository $anlagenRepository, FunctionsService $functions, ReportEpcPRNewService $epcNew): Response
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

        return $this->render('report/epcReportPR.html.twig', [
            'anlage' => $anlage,
            'monthsTable' => $result->table,
            'forcast' => $forcastTable,
            'pldTable' => $pldTable,
            'legend' => $anlage->getLegendEpcReports(),
            // 'chart1'            => $chartYieldPercenDiff,
            // 'chart2'            => $chartYieldCumulativ,
        ]);
    }

}
