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
use App\Service\ReportEpcPRNewService;
use App\Service\WeatherServiceNew;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DefaultMREController extends BaseController
{
    use G4NTrait;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
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

    #[Route(path: '/mr/pa/test')]
    public function pa(AvailabilityService $availability, AvailabilityByTicketService $availabilityByTicket, AnlagenRepository $anlagenRepository): Response
    {
        $anlage = $anlagenRepository->find('93');
        $from   = date_create('2021-11-01 00:00');
        $to     = date_create('2021-11-30 23:00');
        $output = $availability->calcAvailability($anlage, $from, $to);

        return $this->render('cron/showResult.html.twig', [
            'headline' => "PA",
            'availabilitys' => '',
            'output' => $output,
        ]);
    }

    #[Route(path: '/mr/bavelse/export/{year}/{month}')]
    public function bavelseExport($year, $month, ExportService $bavelseExport, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => '97']);
        $from = date_create($year.'-'.$month.'-01');
        $to = date_create($year.'-'.($month+1).'-01');
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
