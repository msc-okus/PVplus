<?php

namespace App\Service\Reports;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Reports\Goldbeck\EPCMonthlyPRGuaranteeReport;
use App\Repository\AnlagenRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\MonthlyDataRepository;
use App\Repository\PRRepository;
use App\Service\AvailabilityService;
use App\Service\FunctionsService;
use App\Service\PRCalulationService;
use App\Service\ReportsEpcNewService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ReportEpcService
{
    use G4NTrait;

    public function __construct(
        private AnlagenRepository $anlageRepo,
        private GridMeterDayRepository $gridMeterRepo,
        private PRRepository $prRepository,
        private MonthlyDataRepository $monthlyDataRepo,
        private EntityManagerInterface $em,
        private NormalizerInterface $serializer,
        private FunctionsService $functions,
        private PRCalulationService $PRCalulation,
        private AvailabilityService $availabilityService,
        private ReportsEpcNewService $epcNew
    )
    {}

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function createEpcReport(Anlage $anlage, DateTime $date): string
    {
        $currentDate = date('Y-m-d H-i');
        $pdfFilename = 'EPC Report '.$anlage->getAnlName().' - '.$currentDate.'.pdf';
        $error = false;
        $output = '';
        switch ($anlage->getEpcReportType()) {
            case 'prGuarantee' :
                $reportArray = $this->reportPRGuarantee($anlage, $date);
                $report = new EPCMonthlyPRGuaranteeReport([
                    'headlines' => [
                        [
                            'projektNr' => $anlage->getProjektNr(),
                            'anlage' => $anlage->getAnlName(),
                            'eigner' => $anlage->getEigner()->getFirma(),
                            'date' => $currentDate,
                            'kwpeak' => $anlage->getPnom(),
                            'finalReport' => $reportArray['finalReport'],
                        ],
                    ],
                    'main' => $reportArray[0],
                    'forecast' => $reportArray[1],
                    'pld' => $reportArray[2],
                    'header' => $reportArray[3],
                    'legend' => $this->serializer->normalize($anlage->getLegendEpcReports()->toArray(), null, ['groups' => 'legend']),
                    'forecast_real' => $reportArray['prForecast'],
                    'formel' => $reportArray['formel'],
                ]);
                $output = $report->run()->render(true);
                break;
            case 'yieldGuarantee':
                $monthTable = $this->epcNew->monthTable($anlage, $date);
                $reportArray['monthTable'] = $monthTable;
                $reportArray['forcastTable'] = $this->epcNew->forcastTable($anlage, $monthTable, $date);

                // $output = $this->functions->printArrayAsTable($reportArray['forcastTable']);
                // $output .= $this->functions->print2DArrayAsTable($reportArray['monthTable']);
                break;
            default:
                $error = true;
                $reportArray = [];
                $report = null;
        }

        if (!$error) {
            // Speichere Report als 'epc-reprt' in die Report Entity
            $reportEntity = new AnlagenReports();
            $startDate = $anlage->getFacDateStart();
            $endDate = $anlage->getFacDate();
            $reportEntity
                ->setCreatedAt(new DateTime())
                ->setAnlage($anlage)
                ->setEigner($anlage->getEigner())
                ->setReportType('epc-report')
                ->setStartDate(self::getCetTime('object'))
                ->setEndDate($endDate)
                ->setRawReport($output)
                ->setContentArray($reportArray)
                ->setMonth($date->format('n'))
                ->setYear($date->format('Y'));
            $this->em->persist($reportEntity);
            $this->em->flush();
        } else {
            $output = '<h1>Fehler: Es Ist kein Report ausgewählt.</h1>';
        }

        return $output;
    }

    /**
     * @throws Exception
     */
    public function reportPRGuarantee(Anlage $anlage, DateTime $date): array
    {
        $anzahlMonate = ((int) $anlage->getEpcReportEnd()->format('Y') - (int) $anlage->getEpcReportStart()->format('Y')) * 12 + ((int) $anlage->getEpcReportEnd()->format('m') - (int) $anlage->getEpcReportStart()->format('m')) + 1;
        $startYear = $anlage->getEpcReportStart()->format('Y');
        $currentMonth = (int) $date->format('m');
        $currentYear = (int) $date->format('Y');
        $finalReport = $currentMonth == $anlage->getEpcReportEnd()->format('m') && $currentYear == $anlage->getEpcReportEnd()->format('Y');
        $report['finalReport'] = $finalReport;

        $sumPrRealPrProg = $sumDays = $sumErtragDesign = $sumEGridReal = $sumAnteil = $sumPrReal = $sumSpecPowerGuar = $sumSpecPowerRealProg = $counter = $sumPrDesign = $sumSpezErtragDesign = 0;
        $sumIrrMonth = $sumDaysReal = $sumErtragDesignReal = $sumEGridRealReal = $sumPrRealReal = $sumEGridRealDesignReal = $sumEGridRealDesign = $sumPrRealPrProgReal = 0;
        $sumSpecPowerGuarReal = $sumSpecPowerRealProgReal = $monateReal = $counterReal = $prAvailability = $formelPowerTheo = 0;

        $realDateTextEnd = $forecastDateText = $realDateText = '';
        /*
         * Zwei Durchläufe:
         * Im ersten werden bestimmte Werte berechnet (die im zweiten Durchlauf gebraucht werden)
         * Im zweiten wir der Report erzeugt
         */
        for ($run = 1; $run <= 2; ++$run) {
            $year = $startYear;
            $facStartDay = $anlage->getEpcReportStart()->format('d');
            $facEndDay = $anlage->getEpcReportEnd()->format('d');
            $month = (int) $anlage->getEpcReportStart()->format('m');
            $daysInStartMonth = (int) $anlage->getEpcReportStart()->format('j');
            $daysInEndMonth = (int) $anlage->getEpcReportEnd()->format('j');
            for ($n = 1; $n <= $anzahlMonate; ++$n) {
                if ($month >= 13) {
                    $month = 1;
                    ++$year;
                }

                $daysInMonth    = date('t', strtotime("$year-$month-01")) * 1;
                $from           = date('Y-m-d', strtotime("$year-$month-01 00:00"));
                $to             = date('Y-m-d', strtotime("$year-$month-$daysInMonth 23:59"));

                switch ($n) {
                    case 1:
                        $from               = date('Y-m-d', strtotime("$year-$month-$facStartDay 00:00"));
                        $prArray            = $this->PRCalulation->calcPR($anlage, date_create($from), date_create($to));
                        $days               = $daysInMonth - $daysInStartMonth + 1;
                        $ertragPvSyst       = $anlage->getOneMonthPvSyst($month)->getErtragDesign() / $daysInMonth * $days;
                        $prDesignPvSyst     = $anlage->getOneMonthPvSyst($month)->getPrDesign();
                        $forecastDateText   = date('My', strtotime("$year-$month-1")).' - ';
                        $realDateText       = date('My', strtotime("$year-$month-1")).' - ';
                        break;
                    case $anzahlMonate:
                        $days               = $daysInEndMonth;
                        $to                 = date('Y-m-d 23:59', strtotime("$year-$month-$facEndDay 23:59"));
                        $prArray            = $this->PRCalulation->calcPR($anlage, date_create($from), date_create($to));
                        $ertragPvSyst       = $anlage->getOneMonthPvSyst($month)->getErtragDesign() / $daysInMonth * $days;
                        $prDesignPvSyst     = $anlage->getOneMonthPvSyst($month)->getPrDesign();
                        $forecastDateText   .= date('My', strtotime("$year-$month-1"));
                        break;
                    default:
                        $days               = $daysInMonth;
                        $prArray            = $this->PRCalulation->calcPR($anlage, date_create($from), date_create($to));
                        $prDesignPvSyst     = $anlage->getOneMonthPvSyst($month)->getPrDesign();
                        $ertragPvSyst       = $anlage->getOneMonthPvSyst($month)->getErtragDesign();
                }
                $prGuarantie        = $prDesignPvSyst - ($anlage->getDesignPR() - $anlage->getContractualPR());
                $spezErtragDesign   = $ertragPvSyst / $anlage->getKwPeakPvSyst();

                /** @var AnlagenPR $pr */
                $pr = $this->prRepository->findOneBy(['anlage' => $anlage, 'stamp' => date_create(date('Y-m-d', strtotime("$year-$month-$daysInMonth")))]);

                $currentMonthClass = '';
                if (date_create($from) <= $date) {
                    $prReal         = $prArray['prEvu']; // $this->format($pr->getPrEvuMonth());
                    $prStandard     = $prArray['prDefaultEvu']; // $this->format($pr->getPrDefaultMonthEvu());
                    switch ($n) {
                        case 1:
                        case $anzahlMonate:
                            $eGridReal      = $prArray['powerEvu'];
                            $irrMonth       = $prArray['irradiation'];
                            $prAvailability = $prArray['availability'];
                            if ($anlage->getUseGridMeterDayData()) {
                                $eGridReal  = $prArray['powerEGridExt'];
                                $prReal     = $prArray['prEGridExt'];
                                $prStandard = $prArray['prDefaultEGridExt'];
                            }
                            break;
                        default:
                            $eGridReal      = $prArray['powerEvu']; // $pr->getPowerEvuMonth();
                            $irrMonth       = $prArray['irradiation']; // $pr->getIrrMonth();
                            $prAvailability = $prArray['availability']; // $this->availabilityService->calcAvailability($anlage, date_create("$year-$month-01 00:00"), date_create("$year-$month-$days 23:59"));
                            if ($anlage->getUseGridMeterDayData()) {
                                $eGridReal  = $prArray['powerEGridExt']; // $this->gridMeterRepo->sumByDateRange($anlage, $from, $to);
                                $prReal     = $prArray['prEGridExt']; // $pr->getPrEGridExtMonth();
                                $prStandard = $prArray['prDefaultEGridExt']; // $this->format($pr->getPrDefaultMonthEGridExt());
                            }
                    }

                    $prRealprProg = $prReal;
                    $realDateTextEnd = date('My', strtotime("$year-$month-1"));
                    if (($month == $currentMonth && $year == $currentYear) && $run === 2) {
                        // für das Einfärben der Zeile des aktuellen Monats
                        $currentMonthClass  = 'current-month';
                        $prArrayFormel = $this->PRCalulation->calcPR($anlage, $anlage->getEpcReportStart(), date_create($to));
                        #dd($prArrayFormel);
                        if ($anlage->getUseGridMeterDayData()) {
                            $formelEnergy   = $prArrayFormel['powerEGridExt'];
                            $formelPR       = $prArrayFormel['prEGridExt'];
                            $prStandard     = $prArrayFormel['prDefaultEGridExt'];
                        } else {
                            $formelEnergy   = $prArrayFormel['powerEvu'];
                            $formelPR       = $prArrayFormel['prEvu'];
                            $prStandard     = $prArrayFormel['prDefaultEvu'];
                        }
                        $formelIrr          = $prArrayFormel['irradiation'];
                        $formelPowerTheo    = $prArrayFormel['powerTheo'];
                        $formelAvailability = $prArrayFormel['availability'];
                        $formelAlgorithmus  = $prArrayFormel['algorithmus'];
                        $tempCorrection     = $prArrayFormel['tempCorrection'];
                    }
                    if ($run === 1) {
                        ++$monateReal;
                        $sumDaysReal                += $days;
                        $sumErtragDesignReal        += $ertragPvSyst;
                        $sumEGridRealReal           += $eGridReal;
                        $sumPrRealReal              += $prReal;
                        $sumEGridRealDesignReal     += $eGridReal - $ertragPvSyst;
                        $sumSpecPowerGuarReal       += $spezErtragDesign * (1 - ((float) $anlage->getDesignPR() - (float) $anlage->getContractualPR()) / 100);
                        $sumSpecPowerRealProgReal   += $eGridReal / $anlage->getPnom();
                        ++$counterReal;
                    }
                } else {
                    $prStandard = 0;
                    $eGridReal      = $ertragPvSyst;
                    $prReal         = $prDesignPvSyst;
                    $prRealprProg   = $prGuarantie;
                    $irrMonth       = 0;
                    $prAvailability = 0;
                }

                if ($run === 1) { // Vorberechnung einiger Werte für den zweiten Lauf (run === 2)
                    $sumDays                += $days;
                    $sumErtragDesign        += $ertragPvSyst;
                    $sumEGridReal           += $eGridReal;
                    $sumPrDesign            += $prDesignPvSyst;
                    $sumPrReal              += $prReal;
                    $sumSpecPowerGuar       += $spezErtragDesign * (1 - ((float) $anlage->getDesignPR() - (float) $anlage->getContractualPR()) / 100);
                    $sumSpecPowerRealProg   += $eGridReal / $anlage->getPnom();
                    $sumIrrMonth            += $irrMonth;
                    $sumEGridRealDesign     += $eGridReal - $ertragPvSyst;
                    ++$counter;
                    if ($counter > 24) {
                        $counter = 24;
                    }
                }
                if ($run === 2) {// Monatswerte berechnen
                    if ($n === $anzahlMonate) {
                        $realDateText .= $realDateTextEnd;
                    }
                    $sumSpezErtragDesign = $sumErtragDesign / (float) $anlage->getKwPeakPvSyst();
                    $anteil = ($sumSpezErtragDesign > 0) ? $spezErtragDesign / $sumSpezErtragDesign : 0;
                    $sumAnteil += $anteil;
                    $sumPrRealPrProg += $prRealprProg * $anteil;
                    if ($pr) {
                        $sumPrRealPrProgReal += $prRealprProg * $anteil;
                    }

                    $report[0][] = [
                        'month'             => date('m / y', strtotime("$year-$month-1")),
                        'days'              => $days,
                        'irradiation'       => $this->format($irrMonth),
                        'prDesign'          => $this->format($prDesignPvSyst),
                        'ertragDesign'      => $this->format($ertragPvSyst),
                        'spezErtragDesign'  => $this->format($spezErtragDesign),
                        'prGuar'            => $this->format($prGuarantie),
                        'eGridReal'         => $this->format($eGridReal),
                        'eGridReal-Design'  => $this->format($eGridReal - $ertragPvSyst),
                        'spezErtrag'        => $this->format($eGridReal / $anlage->getPnom(), 2),
                        'prReal'            => $this->format($prReal),
                        'prReal_prDesign'   => $this->format($prReal - $prDesignPvSyst),
                        'availability'      => $this->format($prAvailability),
                        'dummy'             => '',
                        'prReal_prGuar'     => $this->format($prReal - $prGuarantie),
                        'prReal_prProg'     => $this->format($prRealprProg),
                        'anteil'            => $this->format($anteil * 100),
                        'specPowerGuar'     => $this->format($spezErtragDesign * (1 - ($anlage->getDesignPR() - $anlage->getContractualPR()) / 100)),
                        'specPowerRealProg' => $this->format($eGridReal / $anlage->getPnom()),
                        'currentMonthClass' => $currentMonthClass,
                    ];
                }
                ++$month;
            }
        }

        // Forecast (ganzes Jahr, Bsp Sep20 bis Sep21)
        if (!$finalReport) {
            $report[0][] = [
                'month'                 => 'Forecast<br>' . $forecastDateText,
                'days'                  => 'months: ' . $anzahlMonate,
                'irradiation'           => $this->format($sumIrrMonth),
                'prDesign'              => $this->format($anlage->getDesignPR()),
                'ertragDesign'          => $this->format($sumErtragDesign),
                'spezErtragDesign'      => $this->format($sumErtragDesign / $anlage->getKwPeakPvSyst()),
                'prGuar'                => $this->format($anlage->getContractualPR()),
                'eGridReal'             => $this->format($sumEGridReal),
                'eGridReal-Design'      => $this->format($sumEGridRealDesign),
                'spezErtrag'            => $this->format($sumEGridReal / $anlage->getPnom()),
                'prReal'                => $this->format($sumPrReal / $counter),
                'prReal_prDesign'       => $this->format(($sumPrReal / $counter) - $anlage->getDesignPR()), // PR Real minus PR Design
                'availability'          => '',
                'dummy'                 => '',
                'prReal_prGuar'         => $this->format(($sumPrReal / $counter) - $anlage->getContractualPR()), // PR Real minus PR Garantiert
                'prReal_prProg'         => $this->format($sumPrRealPrProg),  // PR Real oder wenn kein PR Real dann PR Prognostiziert
                'anteil'                => $this->format($sumAnteil * 100),
                'specPowerGuar'         => $this->format($sumSpecPowerGuar),
                'specPowerRealProg'     => $this->format($sumSpecPowerRealProg),
                'currentMonthClass'     => 'sum-forcast',
            ];
        }
        // Real / Aktuell (nur bis zum aktuellen Monat, Bsp Sep20 bis Jan 20
        $report[0][] = [
            'month'                 => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Real&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>'.$realDateText,
            'days'                  => 'months: '.$monateReal,
            'irradiation'           => $this->format($sumIrrMonth),
            'prDesign'              => $this->format($anlage->getDesignPR()),
            'ertragDesign'          => $this->format($sumErtragDesignReal),
            'spezErtragDesign'      => $this->format($sumErtragDesignReal / $anlage->getKwPeakPvSyst()),
            'prGuar'                => $this->format($anlage->getContractualPR()),
            'eGridReal'             => $this->format($sumEGridRealReal),
            'eGridReal-Design'      => $this->format($sumEGridRealDesignReal),
            'spezErtrag'            => $this->format($sumEGridRealReal / $anlage->getPnom()),
            'prReal'                => $this->format($formelPR),
            'prReal_prDesign'       => $this->format($formelPR - $anlage->getDesignPR()),
            'availability'          => $this->format($formelAvailability),
            'dummy'                 => '',
            'prReal_prGuar'         => $this->format($formelPR - $anlage->getContractualPR()),
            'prReal_prProg'         => $this->format($formelPR),
            'anteil'                => $this->format($sumAnteil * 100),
            'specPowerGuar'         => $this->format($sumSpecPowerGuarReal),
            'specPowerRealProg'     => $this->format($sumSpecPowerRealProgReal),
            'currentMonthClass'     => 'sum-real',
        ];

        // PLD Berechnung

        // PR Abweichung für das Jahr berechen -> Daten für PR Forecast
        $prDiffYear = ($sumPrReal / $counter) - $anlage->getContractualPR();
        switch ($anlage->getPldAlgorithm()) {
            case 'Leek/Kampen':
                $sumPld = $prDiffYear * $anlage->getPldPR();
                $report[2][] = [
                    'year' => '0',
                    'eLoss' => '0',
                    'pld' => '0',
                ];
                break;
            default:
                // PLD Forecast Gesamtlaufzeit
                // Daten für PLD Forecast
                $eLoss = (((float) $anlage->getContractualPR() / 100 - $sumPrRealPrProg / 100) * $sumSpecPowerRealProg * (float) $anlage->getKwPeakPvSyst());
                $sumPld = 0;
                for ($year = 1; $year <= 15; ++$year) {
                    $pld = ($eLoss * $anlage->getPldPR()) / (1 + ($anlage->getPldNPValue() / 100)) ** ($year - 1);
                    $sumPld += $pld;
                    $report[2][] = [
                        'year' => $year,
                        'eLoss' => $this->format($eLoss),
                        'pld' => $this->format($pld),
                    ];
                }
        }

        // Daten für PR Forecast
        $report[1] = [
            [
                'PRDiffYear' => $this->format($prDiffYear),
                'message' => ($prDiffYear >= 0) ? 'PR eingehalten' : 'PR nicht eingehalten',
                'pld' => ($prDiffYear >= 0) ? 0 : $sumPld,
                'forecastDateText' => $forecastDateText,
            ],
        ];

        // PLD für 'Current' Zeitraum berechnen

        // PR Abweichung für das Jahr berechen -> Daten für PR Forecast
        $prDiffYear = ($sumPrReal / $counter) - $anlage->getContractualPR();
        switch ($anlage->getPldAlgorithm()) {
            case 'Leek/Kampen':
                $sumPld = $prDiffYear * $anlage->getPldPR();
                $report[2][] = [
                    'year' => '0',
                    'eLoss' => '0',
                    'pld' => '0',
                ];
                break;
            default:
            // PLD Forecast Gesamtlaufzeit
            // Daten für PLD Forecast
            }

        // PR Abweichung für das Jahr berechen -> Daten für PR Forecast
        $prDiffForecast = $formelPR - $anlage->getContractualPR();
        switch ($anlage->getPldAlgorithm()) {
            case 'Leek/Kampen':
                $sumPld = abs($prDiffForecast) * $anlage->getPldPR();
                $report[2][0] = [
                    'year' => '0',
                    'eLoss' => '0',
                    'pld' => '0',
                ];
                break;
            default:
                // PLD Forecast Gesamtlaufzeit
                // Daten für PLD Forecast
                $eLoss = (((float) $anlage->getContractualPR() / 100 - $sumPrRealPrProg / 100) * $sumSpecPowerRealProg * (float) $anlage->getKwPeakPvSyst());
                $sumPld = 0;
                for ($year = 1; $year <= 15; ++$year) {
                    $pld = ($eLoss * $anlage->getPldPR()) / (1 + ($anlage->getPldNPValue() / 100)) ** ($year - 1);
                    $sumPld += $pld;
                }
        }

        $report['pld'][] = [
            'algorithmus' => $anlage->getPldAlgorithm(),
        ];

        // Daten für PR Forecast
        $report['prForecast'][] = [
            'PRDiffYear' => $this->format($prDiffForecast),
            'message' => ($prDiffForecast >= 0) ? 'PR eingehalten' : 'PR nicht eingehalten',
            'pld' => ($prDiffForecast >= 0) ? 0 : $sumPld,
            'forecastDateText' => $realDateText,
            'availability' => $this->format($formelAvailability),
        ];

        // Daten für die Darstellung der Formel
        $report['formel'][] = [
            'eGridReal' => $this->format($sumEGridRealReal), // $formelEnergy
            'prReal' => $formelPR,
            'availability' => $this->format($formelAvailability),
            'theoPower' => $this->format($formelPowerTheo),
            'irradiation' => $this->format($formelIrr),
            'algorithmus' => $formelAlgorithmus,
            'tempCorrection' => $this->format($tempCorrection),
        ];

        $report[3][] = [
            'PRDesign' => $anlage->getDesignPR(),
            'Risikoabschlag' => $anlage->getLid(),
            'AnnualDegradation' => $anlage->getAnnualDegradation(),
            'PRgarantiert' => $anlage->getContractualPR(),
            'kwPeak' => $anlage->getPnom(),
            'kwPeakPvSyst' => $anlage->getKwPeakPvSyst(),
            'startFac' => $anlage->getFacDateStart()->format('d.m.Y'),
            'endeFac' => $anlage->getFacDate()->format('d.m.Y'),
            'startPac' => $anlage->getPacDate()->format('d.m.Y'),
            'endePac' => $anlage->getPacDateEnd()->format('d.m.Y'),
            'pld' => $anlage->getPldPR(),
        ];

        return $report;
    }

    private function format($value, $round = 2): float
    {
        return round($value, $round);
    }
}