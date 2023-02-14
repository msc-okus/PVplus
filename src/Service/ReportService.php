<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenPvSystMonth;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Reports\ReportMonthly\ReportMonthly;
use App\Repository\AnlagenRepository;
use App\Repository\Case5Repository;
use App\Repository\PRRepository;
use App\Repository\PvSystMonthRepository;
use App\Repository\ReportsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ReportService
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
        private PRCalulationService $PRCalulation)
    {
    }

    /**
     * @param $anlagen
     * @param int $month
     * @param int $year
     * @param int $docType
     * @param int $chartTypeToExport
     * @param bool $storeDocument
     * @param bool $exit
     * @param bool $export
     * @return string
     * @throws ExceptionInterface
     */
    public function monthlyReport($anlagen, int $month = 0, int $year = 0, int $docType = 0, int $chartTypeToExport = 0, bool $storeDocument = true, bool $exit = true, bool $export = true): string
    {
        if ($month != 0 && $year != 0) {
            $yesterday = strtotime("$year-$month-01");
        } else {
            $currentTime = G4NTrait::getCetTime();
            $yesterday = $currentTime - 86400 * 4;
        }

        $reportMonth = date('m', $yesterday);
        $reportYear = date('Y', $yesterday);
        $lastDayMonth = date('t', $yesterday);
        $from = "$reportYear-$reportMonth-01 00:00";
        $to = "$reportYear-$reportMonth-$lastDayMonth 23:59";
        $output = '';

        /** @var Anlage $anlage */
        foreach ($anlagen as $anlage) {
            $report = [];
            $report['yesterday'] = $yesterday;
            $report['reportMonth'] = $reportMonth;
            $report['from'] = $from;
            $report['to'] = $to;
            $report['reportYear'] = $reportYear;
            $report['anlage'] = $anlage;
            $report['prs'] = $this->PRRepository->findPRInMonth($anlage, $reportMonth, $reportYear);
            $report['lastPR'] = $this->PRRepository->findOneBy(['anlage' => $anlage, 'stamp' => date_create("$year-$month-$lastDayMonth")]);
            $report['case5s'] = $this->case5Repo->findAllAnlageDay($anlage, $from);
            $report['pvSyst'] = $this->getPvSystMonthData($anlage, $month, $year);

            if ($export === true) {
                $output = $this->buildMonthlyReport($anlage, $report, $docType, $chartTypeToExport, $exit);
            }

            if ($storeDocument) {
                // Store to Database
                $reportEntity = new AnlagenReports();
                $startDate = new \DateTime("$reportYear-$reportMonth-01");
                $endDate = new \DateTime($startDate->format('Y-m-t'));

                $reportEntity
                    ->setCreatedAt(new \DateTime())
                    ->setAnlage($anlage)
                    ->setEigner($anlage->getEigner())
                    ->setReportType('monthly-report')
                    ->setReportTypeVersion(0)
                    ->setStartDate($startDate)
                    ->setEndDate($endDate)
                    ->setMonth($startDate->format('m'))
                    ->setYear($startDate->format('Y'))
                    ->setRawReport($output)
                    ->setContentArray($report);
                $this->em->persist($reportEntity);
                $this->em->flush();
            }
        }

        return $output;
    }

    /**
     * @param Anlage $anlage
     * @param array $report
     * @param $reportCreationDate
     * @param int $docType (0 = PDF, 1 = Excel, 2 = PNG (Grafiken))
     * @param int $chartTypeToExport (0 = , 1 = )
     * @param bool $exit
     * @return string
     * @throws ExceptionInterface
     * @deprecated
     */
    public function buildMonthlyReport(Anlage $anlage, array $report, $reportCreationDate, int $docType = 0, int $chartTypeToExport = 0, bool $exit = true): string
    {
        // beginn create Array for Day Values Table

        $anlagenId = $anlage->getAnlId();
        $month = $report['reportMonth'];
        $year = $report['reportYear'];
        $yesterday = $report['yesterday'];
        $legend = $this->serializer->normalize($anlage->getLegendMonthlyReports()->toArray(), null, ['groups' => 'legend']);
        $case5 = $this->serializer->normalize($anlage->getAnlageCase5s()->toArray(), null, ['groups' => 'case5']);
        $projektid = $anlage->getProjektNr();
        $showAvailability = $anlage->getShowAvailability();
        $showAvailabilitySecond = $anlage->getShowAvailabilitySecond();
        $usePac = $anlage->getUsePac();
        $countCase5 = 0;
        $output = '';

        ($docType == 1) ? $total = 'Total' : $total = '<b>Total</b>';
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
        for ($i = 0; $i < count($report['prs']); ++$i) {
            $stamp = $report['prs'][$i]->getstamp();
            #dump($stamp);
            $dayValues['datum'] = $report['prs'][$i]->getstamp()->format('m-d');
            $dayValues['PowerEvuMonth'] = ($anlage->getShowEvuDiag()) ? (float) $report['prs'][$i]->getPowerEvu() : (float) $report['prs'][$i]->getPowerAct();
            if ($anlage->getUseGridMeterDayData()) {
                $dayValues['powerEGridExt'] = (float) $report['prs'][$i]->getpowerEGridExt();
                $dayValues['spezYield'] = (float) $report['prs'][$i]->getpowerEGridExt() / $anlage->getKwPeak();
                $dayValues['prEvuEpc'] = (float) $report['prs'][$i]->getPrEGridExt();
                $dayValues['prEvuDefault'] = (float) $report['prs'][$i]->getPrDefaultEGridExt();
            } else {
                $dayValues['powerEGridExt'] = 0;
                $dayValues['spezYield'] = ($anlage->getShowEvuDiag()) ? (float) $report['prs'][$i]->getPowerEvu() / $anlage->getKwPeak() : (float) $report['prs'][$i]->getPowerAct() / $anlage->getKwPeak();
                $dayValues['prEvuEpc'] = ($anlage->getShowEvuDiag()) ? (float) $report['prs'][$i]->getPrEvu() : (float) $report['prs'][$i]->getPrAct();
                $dayValues['prEvuDefault'] = ($anlage->getShowEvuDiag()) ? (float) $report['prs'][$i]->getPrDefaultEvu() : (float) $report['prs'][$i]->getPrDefaultAct();
            }
            $dayValues['irradiation'] = (float) $report['prs'][$i]->getirradiation();
            if ($showAvailability === true) {
                $dayValues['plantAvailability'] = (float) $report['prs'][$i]->getplantAvailability();
            }
            if ($showAvailabilitySecond === true) {
                $dayValues['plantAvailabilitySecond'] = (float) $report['prs'][$i]->getplantAvailabilitySecond();
            }
            $dayValues['powerTheo'] = (float) $report['prs'][$i]->getpowerTheo();
            $dayValues['powerExp'] = (float) $report['prs'][$i]->getpowerExp();
            $dayValues['case5perDay'] = $report['prs'][$i]->getcase5perDay();

            $dayValuesFinal[] = $dayValues;
        }

        // die Totalzeile
        $dayValues['datum'] = $total;
        if ($anlage->getUseGridMeterDayData()) {
            $dayValues['powerEGridExt'] = (float) $report['lastPR']->getpowerEGridExtMonth();
        }
        $dayValues['PowerEvuMonth'] = ($anlage->getShowEvuDiag()) ? (float) $report['lastPR']->getPowerEvuMonth() : (float) $report['lastPR']->getPowerActMonth();
        $dayValues['spezYield'] = (float) $report['lastPR']->getspezYield();
        $dayValues['irradiation'] = (float) $report['lastPR']->getIrrMonth();
        if ($anlage->getUseGridMeterDayData()) {
            $dayValues['prEvuEpc'] = (float) $report['lastPR']->getPrEGridExtMonth();
            $dayValues['prEvuDefault'] = (float) $report['lastPR']->getPrDefaultMonthEGridExt();
        } else {
            $dayValues['prEvuEpc'] = ($anlage->getShowEvuDiag()) ? (float) $report['lastPR']->getPrEvuMonth() : (float) $report['lastPR']->getPrActMonth();
            $dayValues['prEvuDefault'] = ($anlage->getShowEvuDiag()) ? (float) $report['lastPR']->getPrDefaultMonthEvu() : (float) $report['lastPR']->getPrDefaultMonthAct();
        }
        if ($showAvailability === true) {
            $dayValues['plantAvailability'] = (float) $report['lastPR']->getPlantAvailabilityPerMonth();
        }
        if ($showAvailabilitySecond === true) {
            $dayValues['plantAvailabilitySecond'] = (float) $report['lastPR']->getPlantAvailabilityPerMonthSecond();
        }
        $dayValues['powerTheo'] = (float) $report['lastPR']->getPowerTheoMonth();
        $dayValues['powerExp'] = (float) $report['lastPR']->getPowerExpMonth();
        $dayValues['case5perDay'] = count($case5Values);
        $dayValuesFinal[] = $dayValues;

        // end create Array for Day Values Table

        // beginn create Array for Energy Production Chart
        // die Daten dem Array hinzufuegen
        for ($i = 0; $i < count($report['prs']); ++$i) {
            $dayChartValues[] =
                [
                    'datum' => $report['prs'][$i]->getstamp()->format('d'),
                    'powerEGridExt' => (float) $report['prs'][$i]->getpowerEGridExt(),
                    'PowerEvuMonth' => ($anlage->getShowEvuDiag()) ? (float) $report['prs'][$i]->getPowerEvu() : (float) $report['prs'][$i]->getPowerAct(),
                    'irradiation' => (float) $report['prs'][$i]->getirradiation(),
                    'prEvuProz' => ($anlage->getShowEvuDiag()) ? (float) $report['prs'][$i]->getPrEvu() : (float) $report['prs'][$i]->getPrAct(),
                ];
        }
        // end create Array for Energy Production Chart

        // beginn create Array for Heat and Temperatur Table
        // die Daten dem Array hinzufuegen
        $heatAndTempValues = [];
        for ($i = 0; $i < count($report['prs']); ++$i) {
            $heatValues = [];
            $heatValues['datum'] = $report['prs'][$i]->getstamp()->format('m-d');
            foreach ($report['prs'][$i]->getirradiationJson() as $key => $value) {
                $heatValues[$key] = (float) $value;
            }

            $j = 1;
            $sum = 0;
            $tempValues = [];
            foreach ($report['prs'][$i]->getTemperaturJson() as $key => $value) {
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

        $energypPoduction[0] = [
            'PD' => date('F', $yesterday),
            'GMNB' => (float) $report['lastPR']->getpowerEGridExtMonth(),
            'GMNA' => (float) $report['lastPR']->getPowerEvuMonth(),
            'IOUT' => (float) $report['lastPR']->getpowerActMonth(),
            'kwPeakPvSyst' => (float) $report['pvSyst']['powerMonth'],
            'G4NExpected' => (float) $report['lastPR']->getpowerExpMonth(),
        ];
        if ($usePac == true) {
            $energypPoduction[1] = [
                'PD' => 'PAC ('.$report['anlage']->getPacDate()->format('Y-m-d').')',
                'GMNB' => (float) $report['lastPR']->getpowerEGridExtPac(),
                'GMNA' => (float) $report['lastPR']->getpowerEvuPac(),
                'IOUT' => (float) $report['lastPR']->getpowerActPac(),
                'kwPeakPvSyst' => (float) $report['pvSyst']['powerPac'],
                'G4NExpected' => (float) $report['lastPR']->getpowerExpPac(),
            ];
        }
        $energypPoduction[2] = [
            'PD' => 'Total year ('.$report['reportYear'].')',
            'GMNB' => (float) $report['lastPR']->getpowerEGridExtYear(),
            'GMNA' => (float) $report['lastPR']->getPowerEvuYear(),
            'IOUT' => (float) $report['lastPR']->getPowerActYear(),
            'kwPeakPvSyst' => (float) $report['pvSyst']['powerYear'],
            'G4NExpected' => (float) $report['lastPR']->getpowerExpYear(),
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
            'PD' => date('F', $yesterday),
            'GMNB' => (float) $report['lastPR']->getprEGridExtMonth(),
            'GMNA' => (float) $report['lastPR']->getprEvuMonth(),
            'IOUT' => (float) $report['lastPR']->getprActMonth(),
            'kwPeakPvSyst' => (float) $report['pvSyst']['prMonth'],
            'G4NExpected' => (float) $report['lastPR']->getprExpMonth(),
            'Availability1' => (float) $report['lastPR']->getplantAvailabilityPerMonth(),
            'Availability2' => (float) $report['lastPR']->getplantAvailabilityPerMonthSecond(),
        ];
        if ($usePac == true) {
            $performanceRatioAndAvailability[1] = [
                'PD' => 'PAC ('.$report['anlage']->getPacDate()->format('Y-m-d').')',
                'GMNB' => (float) $report['lastPR']->getprEGridExtPac(),
                'GMNA' => (float) $report['lastPR']->getprEvuPac(),
                'IOUT' => (float) $report['lastPR']->getprActPac(),
                'kwPeakPvSyst' => (float) $report['pvSyst']['prPac'],
                'G4NExpected' => (float) $report['lastPR']->getprExpPac(),
                'Availability1' => (float) $report['lastPR']->getplantAvailabilityPerPac(),
                'Availability2' => (float) $report['lastPR']->getplantAvailabilityPerPacSecond(),
            ];
        }
        $performanceRatioAndAvailability[2] = [
            // 'PD' => date('F', $yesterday),
            'PD' => 'Total year ('.$report['reportYear'].')',
            'GMNB' => (float) $report['lastPR']->getprEGridExtYear(),
            'GMNA' => (float) $report['lastPR']->getprEvuYear(),
            'IOUT' => (float) $report['lastPR']->getprActYear(),
            'kwPeakPvSyst' => (float) $report['pvSyst']['prYear'],
            'G4NExpected' => (float) $report['lastPR']->getprExpYear(),
            'Availability1' => (float) $report['lastPR']->getplantAvailabilityPerYear(),
            'Availability2' => (float) $report['lastPR']->getplantAvailabilityPerYearSecond(),
        ];

        // jetzt alles zusammenbauen
        // $usePac
        $reportout = new ReportMonthly([
            'headline' => [
                [
                    'month' => $report['reportMonth'],
                    'year' => $report['reportYear'],
                    'plant_name' => $report['anlage']->getAnlName(),
                    'plant_power' => $report['anlage']->getPower(),
                    'projektid' => $projektid,
                ],
            ],
            'anlagenid' => $anlagenId,
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
                    'doctype' => $docType,
                    'footerType' => 'monthlyReport',
                    'month' => $report['reportMonth'],
                    'year' => $report['reportYear'],
                    'plant_name' => $report['anlage']->getAnlName(),
                    'plant_power' => $report['anlage']->getPower(),
                    'projektid' => $projektid,
                    'anlagenId' => $anlagenId,
                    'showAvailability' => $showAvailability,
                    'showAvailabilitySecond' => $showAvailabilitySecond,
                    'useGridMeterDayData' => $anlage->getUseGridMeterDayData(),
                    'useEvu' => $anlage->getShowEvuDiag(),
                    'showPvSyst' => $anlage->getShowPvSyst(),
                    'showHeatAndTemperaturTable' => $showHeatAndTemperaturTable,
                    'reportCreationDate' => $reportCreationDate,
                ],
            ],
        ]);
        $output = $reportout->run()->render('ReportMonthly', true);
        if ($exit) {
            switch ($docType) {
                case 1: // Excel Export
                    $currentDate = date('Y-m-d H-i');
                    $excelFilename = $report['anlage']->getAnlName().' '.$report['reportYear'].$report['reportMonth'].' Monthly Report.xlsx';
                    $output = $reportout->run()->render(true);
                    $reportout->run();
                    $reportout->exportToXLSX('ReportMonthly')->toBrowser($excelFilename);
                    exit; // Ohne exit führt es unter manchen Systemen (Browser) zu fehlerhaften Downloads

                case 2: // Bilder der Charts Exportieren
                    $output = $reportout->run()->render(true);
                    $settings = [
                        // 'useLocalTempFolder' => true,
                        'pageWaiting' => 'networkidle2', // load, domcontentloaded, networkidle0, networkidle2,
                    ];
                    $secretToken = '2bf7e9e8c86aa136b2e0e7a34d5c9bc2f4a5f83291a5c79f5a8c63a3c1227da9';
                    $reportout->run();

                    switch ($chartTypeToExport) {
                        case 0:
                            $exporttemplate = 'ReportMonthlyPrChartPng';
                            $pngFilename = $report['anlage']->getAnlName().' '.$report['reportYear'].$report['reportMonth'].' Monthly Report PR.png';
                            break;
                        case 1:
                            $exporttemplate = 'ReportMonthlyEpChartPng';
                            $pngFilename = $report['anlage']->getAnlName().' '.$report['reportYear'].$report['reportMonth'].' Monthly Report EP.png';
                            break;
                    }
                    $reportout->cloudExport($exporttemplate)
                        ->chromeHeadlessio($secretToken)
                        ->settings($settings)
                        ->png([
                            'format' => 'A4',
                            'fullPage' => true,
                        ])
                        ->toBrowser($pngFilename);
                    exit; // Ohne exit führt es unter manchen Systemen (Browser) zu fehlerhaften Downloads

                default:
                    $output = $reportout->run()->render('ReportMonthly', true);
                    $pdfFilename = $report['anlage']->getAnlName().' '.$report['reportYear'].$report['reportMonth'].' Monthly Report.pdf';
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
        }

        return $output;
    }

    /**
     * @param Anlage $anlage
     * @param $month
     * @param $year
     * @return array
     */
    public function getPvSystMonthData(Anlage $anlage, $month, $year): array
    {
        $pvSystMonth = $this->pvSystMonthRepo->findOneBy(['anlage' => $anlage, 'month' => (int) $month]);
        if ($pvSystMonth) {
            $prPvSystMonth = $pvSystMonth->getPrDesign();
            $powerPvSyst = $pvSystMonth->getErtragDesign();
        } else {
            $prPvSystMonth = 0;
            $powerPvSyst = 0;
        }
        /** @var AnlagenPvSystMonth[] $pvSystYear */
        $pvSystYear = $this->pvSystMonthRepo->findAllYear($anlage, (int) $month);
        $powerPac = 0;
        $powerYear = 0;
        foreach ($pvSystYear as $pvSystYearValue) {
            $powerYear += $pvSystYearValue->getErtragDesign();
        }
        /* @var AnlagenPvSystMonth[] $pvSystPac */
        if ($anlage->getUsePac()) {
            $pvSystPac = $this->pvSystMonthRepo->findAllPac($anlage, (int) $month);
            $anzRecordspvSystPac = count($pvSystPac);
            foreach ($pvSystPac as $pvSystPacValue) {
                // Wenn Anzahl Monate kleiner 12 dann muss der erste Moanat nur anteilig berechnet werden.
                // Wenn 12 oder mehr dann kann der ganze Moant addiert werden
                // und das nur beim ersten PAC Monat
                if ((int) $anlage->getPacDate()->format('m') == $pvSystPacValue->getMonth() && $anzRecordspvSystPac < 12) {
                    $dayPac = (int) $anlage->getPacDate()->format('d');
                    $daysInMonthPac = (int) $anlage->getPacDate()->format('t');
                    $days = $daysInMonthPac - $dayPac + 1;
                    $powerPac += $pvSystPacValue->getErtragDesign() / $daysInMonthPac * $days;
                } else {
                    $powerPac += $pvSystPacValue->getErtragDesign();
                }
            }
        }

        $resultArray = [
            'prMonth' => (float) $prPvSystMonth,
            'prPac' => $anlage->getDesignPR(),
            'prYear' => $anlage->getDesignPR(),
            'powerMonth' => (float) $powerPvSyst,
            'powerPac' => (float) $powerPac,
            'powerYear' => (float) $powerYear,
        ];

        return $resultArray;
    }
}
