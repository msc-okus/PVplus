<?php

namespace App\Service\Reports;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Reports\ReportMonthly\ReportMonthly;
use App\Repository\AnlagenRepository;
use App\Repository\Case5Repository;
use App\Repository\PRRepository;
use App\Repository\PvSystMonthRepository;
use App\Repository\ReportsRepository;
use App\Service\FunctionsService;
use App\Service\PRCalulationService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\NoReturn;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ReportsMonthlyService
{
    use G4NTrait;

    public function __construct(
        private AnlagenRepository $anlagenRepository,
        private PRRepository $PRRepository,
        private ReportsRepository $reportsRepository,
        private EntityManagerInterface $em,
        private PvSystMonthRepository $pvSystMonthRepo,
        private Case5Repository $case5Repo,
        private FunctionsService $functions,
        private NormalizerInterface $serializer,
        private PRCalulationService $PRCalulation,
        private ReportService $reportService)
    {
    }

    /**
     * @throws ExceptionInterface
     */
    public function createMonthlyReport(Anlage $anlage, int $reportMonth = 0, int $reportYear = 0): string
    {
        $output = '';

        $report = $this->buildMonthlyReport($anlage, $reportMonth, $reportYear);

        // Store to Database
        $reportEntity = new AnlagenReports();
        $startDate = new \DateTime("$reportYear-$reportMonth-01");
        $endDate = new \DateTime($startDate->format('Y-m-t'));

        $reportEntity
            ->setCreatedAt(new \DateTime())
            ->setAnlage($anlage)
            ->setEigner($anlage->getEigner())
            ->setReportType('monthly-report')
            ->setReportTypeVersion(1)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setMonth($startDate->format('n'))
            ->setYear($startDate->format('Y'))
            ->setRawReport($output)
            ->setContentArray($report);
        $this->em->persist($reportEntity);
        $this->em->flush();

        return $output;
    }

    /**
     * @throws ExceptionInterface
     * @throws \Exception
     */
    public function buildMonthlyReport(Anlage $anlage, int $reportMonth = 0, int $reportYear = 0): array
    {
        // create Array for Day Values Table
        $date = new \DateTime("$reportYear-$reportMonth-01 00:00");
        $month = $reportMonth;
        $year = $reportYear;
        $daysInMonth = (int) date('t', strtotime("$year-$month-01"));
        // $yesterday = $report['yesterday'];
        $legend = $this->serializer->normalize($anlage->getLegendMonthlyReports()->toArray(), null, ['groups' => 'legend']);
        $case5 = $this->serializer->normalize($anlage->getAnlageCase5s()->toArray(), null, ['groups' => 'case5']);

        $total = 'Total';
        $case5Values = [];
        // beginn case5
        // die Daten nur im korrekten Monat ausgeben
        for ($i = 0; $i < count($case5); ++$i) {
            if (date('m', strtotime($case5[$i]['stampFrom'])) == $month || date('m', strtotime($case5[$i]['stampTo'])) == $month) {
                $case5Values[] = [
                    'stampFrom' => $case5[$i]['stampFrom'],
                    'stampTo' => $case5[$i]['stampTo'],
                    'inverter' => $case5[$i]['inverter'],
                    'reason' => $case5[$i]['reason'],
                ];
            }
        }
        // end case5

        // beginn create Array for Day Values Table
        $dayValuesFinal = [];
        // die Daten dem Array hinzufuegen
        for ($i = 1; $i <= $daysInMonth; ++$i) {
            // Table
            $day = new \DateTime("$year-$month-$i 12:00");
            $prArray = $this->PRCalulation->calcPR($anlage, $day);

            $dayValues['datum'] = $day->format('m-d');

            $dayValues['PowerEvuMonth'] = $anlage->getShowEvuDiag() ? $prArray['powerEvu'] : $prArray['powerAct'];
            if ($anlage->getUseGridMeterDayData()) {
                $dayValues['powerEGridExt'] = $prArray['powerEGridExt'];
                $dayValues['spezYield'] = $dayValues['powerEGridExt'] / $anlage->getPnom();
                $dayValues['prEvuEpc'] = $prArray['prEGridExt'];
                $dayValues['prEvuDefault'] = $prArray['prDefaultEGridExt'];
            } else {
                $dayValues['powerEGridExt'] = 0;
                $dayValues['spezYield'] = $anlage->getShowEvuDiag() ? $prArray['powerEvu'] / $anlage->getPnom() : $prArray['powerAct'] / $anlage->getPnom();
                $dayValues['prEvuEpc'] = $anlage->getShowEvuDiag() ? $prArray['prEvu'] : $prArray['prAct'];
                $dayValues['prEvuDefault'] = $anlage->getShowEvuDiag() ? $prArray['prDefaultEvu'] : $prArray['prDefaultAct'];
            }
            $dayValues['irradiation'] = $prArray['irradiation'];

            $dayValues['pa0'] = $prArray['pa0'];
            $dayValues['pa1'] = $prArray['pa1'];
            $dayValues['pa2'] = $prArray['pa2'];
            $dayValues['pa3'] = $prArray['pa3'];

            $dayValues['powerTheo'] = $prArray['irradiation'] * $anlage->getPnom();
            $dayValues['powerTheoFT'] = $prArray['powerTheo'];
            $dayValues['powerExp'] = $prArray['powerExp'];
            $dayValues['case5perDay'] = $prArray['case5perDay']; // $report['prs'][$i]->getcase5perDay();


            $dayValuesFinal[] = $dayValues;

            // Chart
            $dayChartValues[] = [
                'datum' => $dayValues['datum'],
                'powerEGridExt' => $dayValues['powerEGridExt'],
                'PowerEvuMonth' => $dayValues['PowerEvuMonth'],
                'irradiation' => $dayValues['irradiation'],
                'prEvuProz' => $dayValues['prEvuEpc'],
            ];
        }

        unset($prArray);

        // calculate PR and related data for the current month
        $fromDay = new \DateTime("$year-$month-01 00:00");
        $toDay = new \DateTime("$year-$month-$daysInMonth 23:59");
        $prSumArray = $this->PRCalulation->calcPR($anlage, $fromDay, $toDay);
        // Summe / Total Row
        $sumValues['datum'] = $total;
        if ( false ) {
            foreach ($prSumArray as $prKey => $value) {
                $sumValues[$prKey] = $value;
            }
        } else {
            $sumValues['PowerEvuMonth'] = $anlage->getShowEvuDiag() ? $prSumArray['powerEvu'] : $prSumArray['powerAct'];
            if ($anlage->getUseGridMeterDayData()) {
                $sumValues['powerEGridExt'] = $prSumArray['powerEGridExt'];
                $sumValues['spezYield'] = $sumValues['powerEGridExt'] / $anlage->getPnom();
                $sumValues['prEvuEpc'] = $prSumArray['prEGridExt'];
                $sumValues['prEvuDefault'] = $prSumArray['prDefaultEGridExt'];
            } else {
                $sumValues['spezYield'] = $anlage->getShowEvuDiag() ? $prSumArray['powerEvu'] / $anlage->getPnom() : $prSumArray['powerAct'] / $anlage->getPnom();
                $sumValues['prEvuEpc'] = $anlage->getShowEvuDiag() ? $prSumArray['prEvu'] : $prSumArray['prAct'];
                $sumValues['prEvuDefault'] = $anlage->getShowEvuDiag() ? $prSumArray['prDefaultEvu'] : $prSumArray['prDefaultAct'];
            }
            $sumValues['irradiation'] = $prSumArray['irradiation'];
            $sumValues['pa0'] = $prSumArray['pa0'];
            $sumValues['pa1'] = $prSumArray['pa1'];
            $sumValues['pa2'] = $prSumArray['pa2'];
            $sumValues['pa3'] = $prSumArray['pa3'];
            if ($anlage->getShowAvailability()) {

            }
            if ($anlage->getShowAvailabilitySecond()) {

            }
            $sumValues['powerTheo'] = $prSumArray['irradiation'] * $anlage->getPnom();
            $sumValues['powerTheoFT'] = $prSumArray['powerTheo'];
            $sumValues['powerExp'] = $prSumArray['powerExp'];
            $sumValues['case5perDay'] = $prSumArray['case5perDay'];
        }
        $dayValuesFinal[] = $sumValues;

        // beginn create array for heat and temperatur table
        // die Daten dem Array hinzufuegen
        $heatAndTempValues = [];
        $prs = $this->PRRepository->findPRInMonth($anlage, $reportMonth, $reportYear);
        for ($i = 0; $i < count($prs); ++$i) {
            $heatValues = [];
            $heatValues['datum'] = $prs[$i]->getstamp()->format('m-d');
            foreach ($prs[$i]->getirradiationJson() as $key => $value) {
                $heatValues[$key] = (float) $value;
            }

            $j = 1;
            $sum = 0;
            $tempValues = [];
            foreach ($prs[$i]->getTemperaturJson() as $key => $value) {
                $tempValues[$key] = (float) $value;
                ++$j;
                $sum += (float) $value;
            }

            $tempav = ['Avg_temp' => $sum / $j];
            $tempValues = array_merge($tempValues, $tempav);

            // pruefen, ob es Temperaturwerte gibt
            (count($tempValues) > 0) ? $heatAndTempValues[] = array_merge($heatValues, $tempValues) : $heatAndTempValues[] = $heatValues;
        }
        // end create array for heat and temperatur table
        // wenn gar nichts geleifert wird, dann die gesamte Tabelle ausblenden
        (count($heatAndTempValues) > 0) ? $showHeatAndTemperaturTable = true : $showHeatAndTemperaturTable = false;

        $pvSyst = $this->reportService->getPvSystMonthData($anlage, $month, $year);
        // Month
        $energypPoduction[0] = [
            'PD' => $date->format('F'),
            'GMNB' => $prSumArray['powerEGridExt'],    // Grid Meter EVU (externes Meßßgerät)
            'GMNA' => $prSumArray['powerEvu'],         // Grid Meter via VCOM (e_z_evu)
            'IOUT' => $prSumArray['powerAct'],         // Inverter Out
            'kwPeakPvSyst' => $pvSyst['powerMonth'],
            'G4NExpected' => $prSumArray['powerExp'],
        ];

        // Since Pac
        if ($anlage->getUsePac()) {
            // calculate PR and related data for the since PAC
            $toDay = new \DateTime("$year-$month-$daysInMonth 23:59");
            $prSumArrayPac = $this->PRCalulation->calcPR($anlage, $anlage->getPacDate(), $toDay);
            $energypPoduction[1] = [
                'PD' => 'PAC ('.$anlage->getPacDate()->format('Y-m-d').')',
                'GMNB' => $prSumArrayPac['powerEGridExt'],
                'GMNA' => $prSumArrayPac['powerEvu'],
                'IOUT' => $prSumArrayPac['powerAct'],
                'kwPeakPvSyst' => $pvSyst['powerPac'],
                'G4NExpected' => $prSumArrayPac['powerExp'],
            ];
        }

        // Total Year
        // calculate PR and related data for the current year
        $fromDay = new \DateTime("$year-01-01 00:00");
        $toDay = new \DateTime("$year-$month-$daysInMonth 23:59");
        $prSumArrayYear = $this->PRCalulation->calcPR($anlage, $fromDay, $toDay);
        $energypPoduction[2] = [
            'PD' => 'Total year ('.$reportYear.')',
            'GMNB' => $prSumArrayYear['powerEGridExt'],
            'GMNA' => $prSumArrayYear['powerEvu'],
            'IOUT' => $prSumArrayYear['powerAct'],
            'kwPeakPvSyst' => $pvSyst['powerYear'],
            'G4NExpected' => $prSumArrayYear['powerExp'],
        ];
        /*
        $energypPoduction[3] = [
            'PD' => 'FAC Forecast',
            'GMNB' => 0,
            'GMNA' => 0,
            'IOUT' => 0,
            'kwPeakPvSyst' => 0,
            'G4NExpected' => 0,
        ];*/

        $performanceRatioAndAvailability[0] = [
            'PD' => $date->format('F'),
            'GMNB' => $prSumArray['prDefaultEGridExt'],
            'GMNA' => $prSumArray['prDefaultEvu'],
            'IOUT' => $prSumArray['prDefaultAct'],
            'kwPeakPvSyst' => $pvSyst['prMonth'],
            'G4NExpected' => $prSumArray['prDefaultExp'],
            'Availability1' => $prSumArray['availability'],
            'Availability2' => $prSumArray['availability2'],
        ];
        if ($anlage->getUsePac()) {
            $performanceRatioAndAvailability[1] = [
                'PD' => 'PAC ('.$anlage->getPacDate()->format('Y-m-d').')',
                'GMNB' => $prSumArrayPac['prDefaultEGridExt'], // (float)$report['lastPR']->getprEGridExtPac(),
                'GMNA' => $prSumArrayPac['prDefaultEvu'], // (float)$report['lastPR']->getprEvuPac(),
                'IOUT' => $prSumArrayPac['prDefaultAct'], // (float)$report['lastPR']->getprActPac(),
                'kwPeakPvSyst' => $pvSyst['prPac'],
                'G4NExpected' => $prSumArrayPac['prDefaultExp'], // (float)$report['lastPR']->getprExpPac(),
                'Availability1' => $prSumArrayPac['availability'], // (float)$report['lastPR']->getplantAvailabilityPerPac(),
                'Availability2' => $prSumArrayPac['availability2'], // (float)$report['lastPR']->getplantAvailabilityPerPacSecond(),
            ];
        }
        $performanceRatioAndAvailability[2] = [
            'PD' => 'Total year ('.$reportYear.')',
            'GMNB' => $prSumArrayYear['prDefaultEGridExt'], // (float)$report['lastPR']->getprEGridExtYear(),
            'GMNA' => $prSumArrayYear['prDefaultEvu'], // (float)$report['lastPR']->getprEvuYear(),
            'IOUT' => $prSumArrayYear['prDefaultAct'], // (float)$report['lastPR']->getprActYear(),
            'kwPeakPvSyst' => $pvSyst['prYear'],
            'G4NExpected' => $prSumArrayYear['prDefaultExp'], // (float)$report['lastPR']->getprExpYear(),
            'Availability1' => $prSumArrayYear['availability'], // (float)$report['lastPR']->getplantAvailabilityPerYear(),
            'Availability2' => $prSumArrayYear['availability2'], // (float)$report['lastPR']->getplantAvailabilityPerYearSecond(),
        ];

        // jetzt alles zusammenbauen
        $report = [
            'headline' => [
                [
                    'month' => $reportMonth,
                    'year' => $reportYear,
                    'plant_name' => $anlage->getAnlName(),
                    'plant_power' => $anlage->getPnom(),
                    'projektid' => $anlage->getProjektNr(),
                ],
            ],
            'anlagenid' => $anlage->getAnlId(),
            'energyproduction' => $energypPoduction,

            'performanceratioandavailability' => $performanceRatioAndAvailability,

            'case5' => $case5Values,
            'dayvalues' => $dayValuesFinal,
            'irradiationandtempvalues' => $heatAndTempValues,
            'daychartvalues' => $dayChartValues,
            'legend' => $legend,
            // ownparams sind nötig um sie im Excelexport verwenden zu koennen (der Zugriff auf die Standartparams ist bei Excelexport nicht moeglich)
            'ownparams' => [
                [
                    'logoPath' => $anlage->getEigner()->getLogo(),
                    'doctype' => 0,  // $docType,
                    'footerType' => 'monthlyReport',
                    'month' => $reportMonth,
                    'year' => $reportYear,
                    'plant_name' => $anlage->getAnlName(),
                    'plant_power' => $anlage->getPnom(),
                    'projektid' => $anlage->getProjektNr(),
                    'anlagenId' => $anlage->getAnlId(),
                    'showAvailability' => $anlage->getShowAvailability(),
                    'showAvailabilitySecond' => $anlage->getShowAvailabilitySecond(),
                    'useGridMeterDayData' => $anlage->getUseGridMeterDayData(),
                    'useEvu' => $anlage->getShowEvuDiag(),
                    'showPvSyst' => $anlage->getShowPvSyst(),
                    'showHeatAndTemperaturTable' => $showHeatAndTemperaturTable,
                    'reportCreationDate' => date('Y-m-d H:i'),
                ],
            ],
        ];

        return $report;
    }

    #[NoReturn]
    public function exportReportToPDF(Anlage $anlage, AnlagenReports $report): void
    {
        // übergabe der Werte an KoolReport
        $reportout = new ReportMonthly($report->getContentArray());
        $output = $reportout->run()->render('ReportMonthly', true);
        $pdfFilename = $anlage->getAnlName().' '.sprintf("%04d%02d", $report->getYear(), $report->getMonth()).' Monthly Report.pdf';
        $settings = [
            // 'useLocalTempFolder' => true,
            'pageWaiting' => 'networkidle2', // load, domcontentloaded, networkidle0, networkidle2
        ];
        $secretToken = '2bf7e9e8c86aa136b2e0e7a34d5c9bc2f4a5f83291a5c79f5a8c63a3c1227da9';
        $reportout->run();
        $pdfOptions = [
            'format' => 'A4',
            'landscape' => false,
            'noRepeatTableFooter' => false,
            'printBackground' => true,
            'displayHeaderFooter' => true,
        ];
        $reportout->cloudExport('ReportMonthly')
            ->chromeHeadlessio($secretToken)
            ->settings($settings)
            ->pdf($pdfOptions)
            ->toBrowser($pdfFilename);
        exit; // Ohne exit führt es unter manchen Systemen (Browser) zu fehlerhaften Downloads
    }

    #[NoReturn]
    #[Deprecated]
    public function exportReportToExcel(Anlage $anlage, AnlagenReports $report)
    {
        $excelFilename = $anlage->getAnlName().' '.$report->getYear().$report->getMonth().' Monthly Report.xlsx';

        $reportout = new ReportMonthly($report->getContentArray());
        $reportout->run()->render('ReportMonthly', true);
        $reportout->run()->render(true);
        $reportout->run();
        $reportout->exportToXLSX('ReportMonthly')->toBrowser($excelFilename);
        exit; // Ohne exit führt es unter manchen Systemen (Browser) zu fehlerhaften Downloads
    }

    #[NoReturn]
    #[Deprecated]
    public function exportDiagramsToImage(Anlage $anlage, AnlagenReports $report, $chartTypeToExport = 0)
    {
        $reportout = new ReportMonthly($report->getContentArray());
        $reportout->run()->render(true);
        $reportout->run();

        switch ($chartTypeToExport) {
            case 1:
                $exporttemplate = 'ReportMonthlyEpChartPng';
                $pngFilename = $anlage->getAnlName().' '.$report->getYear().$report->getMonth().' Monthly Report EP.png';
                break;
            default:
                $exporttemplate = 'ReportMonthlyPrChartPng';
                $pngFilename = $anlage->getAnlName().' '.$report->getYear().$report->getMonth().' Monthly Report PR.png';
                break;
        }

        $secretToken = '2bf7e9e8c86aa136b2e0e7a34d5c9bc2f4a5f83291a5c79f5a8c63a3c1227da9';
        $settings = [
            'pageWaiting' => 'networkidle2', // load, domcontentloaded, networkidle0, networkidle2,
        ];
        $reportout->cloudExport($exporttemplate)
            ->chromeHeadlessio($secretToken)
            ->settings($settings)
            ->png([
                'format' => 'A4',
                'fullPage' => true,
            ])
            ->toBrowser($pngFilename);
        exit; // Ohne exit führt es unter manchen Systemen (Browser) zu fehlerhaften Downloads
    }
}
