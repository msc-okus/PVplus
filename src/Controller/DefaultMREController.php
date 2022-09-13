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
        $time = strtotime('2022-08-03');

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
    public function pa(AvailabilityByTicketService $availability, AnlagenRepository $anlagenRepository): Response
    {
        $anlage = $anlagenRepository->find('112');
        $date = '2022-08-30';
        $output = $availability->checkAvailability($anlage, strtotime($date), 1);

        return $this->render('cron/showResult.html.twig', [
            'headline' => "PA $date",
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

    #[Route(path: '/test/pa/{month}/{year}')]
    public function testPa($month, $year, AnlageAvailabilityRepository $availabilityRepository, Case5Repository $case5Repository, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        $output2 = '<table>';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => 84]);
        if ($anlage->getUseNewDcSchema()) {
            foreach ($anlage->getAcGroups() as $acGroup) {
                $inverterPowerDc[$acGroup->getAcGroup()] = $acGroup->getDcPowerInverter();
            }
        } else {
            foreach ($anlage->getAcGroups() as $acGroup) {
                ($acGroup->getDcPowerInverter() > 0) ? $powerPerInverter = $acGroup->getDcPowerInverter() / ($acGroup->getUnitLast() - $acGroup->getUnitFirst() + 1) : $powerPerInverter = 0;
                for ($inverter = $acGroup->getUnitFirst(); $inverter <= $acGroup->getUnitLast(); ++$inverter) {
                    $inverterPowerDc[$inverter] = $powerPerInverter;
                }
            }
        }
        // Speichern der ermittelten Werte
        $lastDayInMonth = date('t', "$year-$month-01");
        $from = date_create("$year-$month-01 00:00");
        $to = date_create("$year-$month-$lastDayInMonth 23:59");
        $availabilitys = $availabilityRepository->sumAllCasesByDate($anlage, $from, $to);
        $sumPart1 = $sumPart2 = $sumPart3 = 0;
        foreach ($availabilitys as $row) {
            $inverter = $row['inverter'];
            // Berechnung der protzentualen Verfügbarkeit Part 1 und Part 2
            if ($row['control'] - $row['case4'] != 0) {
                // ///////////////////
                $invAPart1 = (($row['case1'] + $row['case2']) / ($row['control'] - $row['case5'])) * 100;
                // ///////////////////
                ($anlage->getPnom() > 0 && $inverterPowerDc[$inverter] > 0) ? $invAPart2 = $inverterPowerDc[$inverter] / $anlage->getPnom() : $invAPart2 = 1;
                $invAPart3 = $invAPart1 * $invAPart2;
            } else {
                $invAPart1 = 0;
                $invAPart2 = 0;
                $invAPart3 = 0;
            }
            $sumPart1 += $invAPart1;
            $sumPart2 += $invAPart2;
            $sumPart3 += $invAPart3;

            $output2 .= "<tr>
                    <td>Inverter: $inverter</td>
                    <td>Case1: ".$row['case1'].' / '.$row['case1'] / 4 .'</td>
                    <td>Case2: '.$row['case2'].' / '.$row['case2'] / 4 .'</td>
                    <td>Case3: '.$row['case3'].' / '.$row['case3'] / 4 .'</td>
                    <td>Case4: '.$row['case4'].' / '.$row['case4'] / 4 .'</td>
                    <td>Case5: '.$row['case5'].' / '.$row['case5'] / 4 .'</td>
                    <td>Control: '.$row['control'].' / '.$row['control'] / 4 .'</td></tr>';
            $output .= "Inverter: $inverter: PA Part 1: $invAPart1 | PA Part 2: $invAPart2 | PA Part 3: $invAPart3<br>";
        }
        $output2 .= '</table>';
        $summe = "<b>Summe: PA Part 1: $sumPart1 | PA Part 2: $sumPart2 | PA Part 3: $sumPart3</b><br>";

        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Test PA',
            'availabilitys' => '',
            'output' => $output2.$summe,
        ]);
    }

    #[Route(path: '/test/epc/{id}', defaults: ['id' => 92])]
    public function testNewEpc($id, AnlagenRepository $anlagenRepository, FunctionsService $functions, ReportEpcPRNewService $epcNew): Response
    {
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);
        $date = date_create('2022-07-01 00:00');
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
