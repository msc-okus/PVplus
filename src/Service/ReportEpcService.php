<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\MonthlyDataRepository;
use App\Repository\PRRepository;
use App\Service\Reports\ReportsEpcYieldV2;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ReportEpcService
{
    use G4NTrait;

    public function __construct(
        private readonly AnlagenRepository $anlageRepo,
        private readonly GridMeterDayRepository $gridMeterRepo,
        private readonly PRRepository $prRepository,
        private readonly MonthlyDataRepository $monthlyDataRepo,
        private readonly EntityManagerInterface $em,
        private readonly NormalizerInterface $serializer,
        private readonly FunctionsService $functions,
        private readonly PRCalulationService $PRCalulation,
        private readonly AvailabilityService $availabilityService,
        private readonly ReportsEpcYieldV2 $epcNew,
        private readonly LogMessagesService $logMessages,
    )
    {}

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    #[Deprecated]
    public function createEpcReport(Anlage $anlage, \DateTime $date, ?string $userId = null, ?int $logId = null): string
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
                ->setYear($date->format('Y'))
                ->setCreatedBy($userId);
            $this->em->persist($reportEntity);
            $this->em->flush();

            $reportId = $reportEntity->getId();
            $this->logMessages->updateEntryAddReportId($logId, $reportId);
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
                        'eGridRealDesign'  => $this->format($eGridReal - $ertragPvSyst),
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
                'eGridRealDesign'      => $this->format($sumEGridRealDesign),
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
            'month'                 => 'Real<br>'.$realDateText,
            'days'                  => 'months: '.$monateReal,
            'irradiation'           => $this->format($sumIrrMonth),
            'prDesign'              => $this->format($anlage->getDesignPR()),
            'ertragDesign'          => $this->format($sumErtragDesignReal),
            'spezErtragDesign'      => $this->format($sumErtragDesignReal / $anlage->getKwPeakPvSyst()),
            'prGuar'                => $this->format($anlage->getContractualPR()),
            'eGridReal'             => $this->format($sumEGridRealReal),
            'eGridRealDesign'      => $this->format($sumEGridRealDesignReal),
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

    #[Deprecated]
    public function reportYieldGuarantee(Anlage $anlage): array
    {
        $anzahlMonate = ((int) $anlage->getEpcReportEnd()->format('Y') - (int) $anlage->getEpcReportStart()->format('Y')) * 12 + ((int) $anlage->getEpcReportEnd()->format('m') - (int) $anlage->getEpcReportStart()->format('m')) + 1;
        $startYear = $anlage->getEpcReportStart()->format('Y');
        $endYear = $anlage->getEpcReportEnd()->format('Y');
        $yearCount = $endYear - $startYear;
        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');

        $sumPrRealPrProg = $sumDays = $sumDaysReal = $sumErtragDesign = $sumEGridReal = $sumAnteil = $sumPrReal = $sumSpecPowerGuar = $sumSpecPowerRealProg = $counter = $sumPrDesign = $sumSpezErtragDesign = 0;
        $sumAvailability = $sumExpectedYield = $sumGuaranteedExpexted = $sumExpectedKorr = $monateReal = $sumIrrMonth = $sumEGridRealDesign = 0;
        $sumErtragDesignReal = $sumEGridRealReal = $sumPrRealReal = $sumEGridRealDesignReal = $sumSpecPowerGuarReal = $sumSpecPowerRealProgReal = $counterReal = $sumPrRealPrProgReal = 0;
        $sumGuaranteedExpextedReal = $sumExpectedYieldReal = $sumAvailabilityReal = 0;
        $realDateTextEnd = $forecastDateText = $realDateText = $currentMonthClass = '';
        /*
         * Zwei Durchläufe:
         * Im ersten werden bestimmte Werte berechnet (die im zweiten Durchlauf gebraucht werden)
         * Im zweiten wir der Report erzeugt
         */

        for ($run = 1; $run <= 2; ++$run) {
            $year = $startYear;
            if (self::getCetTime('object') < $anlage->getFacDateStart()) {
                $facStartMonth = $anlage->getPacDate()->format('m');
                $facStartDay = $anlage->getPacDate()->format('d');
                $facEndMonth = $anlage->getPacDateEnd()->format('m');
                $facEndDay = $anlage->getPacDateEnd()->format('d');
                $month = $anlage->getPacDate()->format('m') * 1;
                $daysInStartMonth = (int) $anlage->getPacDate()->format('j');
                $daysInEndMonth = (int) $anlage->getPacDateEnd()->format('j');
            } else {
                $facStartMonth = $anlage->getFacDateStart()->format('m');
                $facStartDay = $anlage->getFacDateStart()->format('d');
                $facEndMonth = $anlage->getFacDate()->format('m');
                $facEndDay = $anlage->getFacDate()->format('d');
                $month = $anlage->getFacDateStart()->format('m') * 1;
                $daysInStartMonth = (int) $anlage->getFacDateStart()->format('j');
                $daysInEndMonth = (int) $anlage->getFacDate()->format('j');
            }

            for ($n = 1; $n <= $anzahlMonate; ++$n) {
                if ($month >= 13) {
                    $month = 1;
                    ++$year;
                }

                $daysInMonth = (int) date('t', strtotime("$year-$month-01"));
                $from = date('Y-m-d', strtotime("$year-$month-01 00:00"));
                $to = date('Y-m-d', strtotime("$year-$month-$daysInMonth 23:59"));

                $monthlyData = $this->monthlyDataRepo->findOneBy(['anlage' => $anlage, 'year' => $year, 'month' => $month]);
                if ($monthlyData != null && $monthlyData->getPvSystPR() > 0) {
                    $prDesignPvSyst = $monthlyData->getPvSystPR();
                } else {
                    ($anlage->getOneMonthPvSyst($month) != null) ? $prDesignPvSyst = $anlage->getOneMonthPvSyst($month)->getPrDesign() : $prDesignPvSyst = 0;
                }
                $prGuarantie = $prDesignPvSyst - ($anlage->getDesignPR() - $anlage->getContractualPR());
                switch ($n) {
                    case 1:
                        $from = date('Y-m-d', strtotime("$year-$month-$facStartDay 00:00"));
                        $prArray = $this->PRCalulation->calcPR($anlage, date_create($from), date_create($to));
                        $days = $daysInMonth - $daysInStartMonth + 1;

                        if ($monthlyData != null && $monthlyData->getPvSystErtrag() > 0) {
                            // hier keine Korrektur des Wertes, da dieser schon Taggenau nachberechnet wurde
                            $ertragPvSyst = $monthlyData->getPvSystErtrag();
                            $expectedYield = $monthlyData->getPvSystErtrag();
                        } else {
                            ($anlage->getOneMonthPvSyst($month) != null) ? $ertragPvSyst = $anlage->getOneMonthPvSyst($month)->getErtragDesign() / $daysInMonth * $days : $ertragPvSyst = 0;
                            $expectedYield = $ertragPvSyst;
                        }

                        $forecastDateText = date('My', strtotime("$year-$month-1")).' - ';
                        $realDateText = date('My', strtotime("$year-$month-1")).' - ';
                        break;
                    case $anzahlMonate:
                        $days = $daysInEndMonth;
                        $to = date('Y-m-d', strtotime("$year-$month-$facEndDay 23:59"));
                        $prArray = $this->PRCalulation->calcPR($anlage, date_create($from), date_create($to));

                        if ($monthlyData != null && $monthlyData->getPvSystErtrag() > 0) {
                            // hier keine Korrektur des Wertes, da dieser schon Taggenau nachberechnet wurde
                            $ertragPvSyst = $monthlyData->getPvSystErtrag();
                            $expectedYield = $monthlyData->getPvSystErtrag();
                        } else {
                            ($anlage->getOneMonthPvSyst($month) != null) ? $ertragPvSyst = $anlage->getOneMonthPvSyst($month)->getErtragDesign() / $daysInMonth * $days : $ertragPvSyst = 0;
                            $expectedYield = $ertragPvSyst;
                        }

                        $forecastDateText .= date('My', strtotime("$year-$month-1"));
                        // $realDateText       .= $realDateTextEnd;
                        break;
                    default:
                        $days = $daysInMonth;
                        if ($monthlyData != null && $monthlyData->getPvSystErtrag() > 0) {
                            $ertragPvSyst = $monthlyData->getPvSystErtrag();
                            $expectedYield = $monthlyData->getPvSystErtrag();
                        } else {
                            ($anlage->getOneMonthPvSyst($month) != null) ? $ertragPvSyst = $anlage->getOneMonthPvSyst($month)->getErtragDesign() : $ertragPvSyst = 0;
                            $expectedYield = $ertragPvSyst;
                        }
                }
                $spezErtragDesign = $ertragPvSyst / $anlage->getKwPeakPvSyst();
                $currentMonthClass = '';

                $guaranteedExpexted = $anlage->getGuaranteedExpectedEnergy($expectedYield); // * (1 - ($anlage->getTransformerTee() / 100)) * (1 - ($anlage->getGuaranteeTee() / 100));
                $expectedKorr = $expectedYield * (1 - ($anlage->getTransformerTee() / 100));

                /* @var AnlagenPR $pr */
                ($month != $currentMonth or $year != $currentYear) ? $pr = $this->prRepository->findOneBy(['anlage' => $anlage, 'stamp' => date_create(date('Y-m-d', strtotime("$year-$month-$daysInMonth")))]) : $pr = null;
                if ($pr) {
                    switch ($n) {
                        case 1:
                        case $anzahlMonate:
                            $prReal = $prArray['prEvu'];
                            $eGridReal = $prArray['powerEvu'];
                            $irrMonth = $prArray['irradiation'];
                            if ($anlage->getUseGridMeterDayData()) {
                                if ($monthlyData != null && $monthlyData->getExternMeterDataMonth() > 0) {
                                    $eGridReal = $monthlyData->getExternMeterDataMonth();
                                } else {
                                    $eGridReal = $prArray['powerEGridExt'];
                                }
                                $prReal = $prArray['prEGridExt'];
                            }
                            $availability = $prArray['availability'];
                            break;
                        default:
                            if ($anlage->getUseGridMeterDayData()) {
                                $eGridReal = $pr->getPowerEGridExtMonth();
                            } else {
                                $eGridReal = $pr->getPowerEvuMonth();
                            }
                            $irrMonth = $pr->getIrrMonth();
                            $prReal = $pr->getPrEvuMonth();
                            $availability = $this->availabilityService->calcAvailability($anlage, date_create("$year-$month-01 00:00"), date_create("$year-$month-$days 23:59"));
                    }

                    $prRealprProg = $prReal;
                    $realDateTextEnd = date('My', strtotime("$year-$month-1"));
                    if ($run === 1) {
                        ++$monateReal;
                        $sumDaysReal += $days;
                        $sumErtragDesignReal += $ertragPvSyst;
                        $sumEGridRealReal += $eGridReal;
                        $sumPrRealReal += $prReal;
                        $sumEGridRealDesignReal += $eGridReal - $ertragPvSyst;
                        $sumSpecPowerGuarReal += $spezErtragDesign * (1 - ((float) $anlage->getDesignPR() - (float) $anlage->getContractualPR()) / 100);
                        $sumSpecPowerRealProgReal += $eGridReal / $anlage->getPnom();
                        $sumGuaranteedExpextedReal += $guaranteedExpexted;
                        $sumExpectedYieldReal += $expectedYield;
                        $sumAvailabilityReal += $availability;
                        ++$counterReal;
                    }
                } else {
                    $eGridReal = $ertragPvSyst;
                    $prReal = $prDesignPvSyst;
                    $prRealprProg = $prGuarantie;
                    $availability = 100;
                    $irrMonth = 0;
                }

                if ($month == $currentMonth - 1 && $year == date('Y')) {
                    // für das Einfärben der Zeile des aktuellen Monats
                    $currentMonthClass = 'current-month';
                    $prArrayFormel = $this->PRCalulation->calcPR($anlage, $anlage->getEpcReportStart(), date_create($to));
                    if ($anlage->getUseGridMeterDayData()) {
                        $formelEnergy = $prArrayFormel['powerEGridExt'];
                        $formelPR = $prArrayFormel['prEGridExt'];
                    } else {
                        $formelEnergy = $prArrayFormel['powerEvu'];
                        $formelPR = $prArrayFormel['prEvu'];
                    }
                    $formelIrr = $prArrayFormel['irradiation'];
                    $formelPowerTheo = $prArrayFormel['powerTheo'];
                    $formelAvailability = $prArrayFormel['availability'];
                    $formelAlgorithmus = $prArrayFormel['algorithmus'];
                    $tempCorrection = $prArrayFormel['tempCorrection'];
                }
                if ($run === 1) { // Vorberechnung einiger Werte für den zweiten Lauf (run === 2)
                    $sumDays += $days;
                    $sumErtragDesign += $ertragPvSyst;
                    $sumEGridReal += $eGridReal;
                    $sumPrDesign += $prDesignPvSyst;
                    $sumPrReal += $prReal;
                    $sumSpecPowerGuar += $spezErtragDesign * (1 - ((float) $anlage->getDesignPR() - (float) $anlage->getContractualPR()) / 100);
                    $sumSpecPowerRealProg += $eGridReal / $anlage->getPnom();
                    $sumAvailability += $availability;
                    $sumExpectedYield += $expectedYield;
                    $sumGuaranteedExpexted += $guaranteedExpexted;
                    $sumExpectedKorr += $expectedKorr;
                    $sumIrrMonth += $irrMonth;
                    $sumEGridRealDesign += $eGridReal - $ertragPvSyst;
                    ++$counter;
                }
                if ($run === 2) {// Monatswerte berechnen
                    if ($n == $anzahlMonate) {
                        $realDateText .= $realDateTextEnd;
                    }
                    $sumSpezErtragDesign = $sumErtragDesign / (float) $anlage->getKwPeakPvSyst();
                    $anteil = $spezErtragDesign / $sumSpezErtragDesign;
                    $sumAnteil += $anteil;
                    $sumPrRealPrProg += $prRealprProg * $anteil;
                    $eGridGuar = $ertragPvSyst * (1 - ((float) $anlage->getDesignPR() - (float) $anlage->getContractualPR()) / 100);
                    if ($pr) {
                        $sumPrRealPrProgReal += $prRealprProg * $anteil;
                    }

                    $report[0][] = [
                        'month' => date('m / Y', strtotime("$year-$month-1")),
                        'days' => $days,
                        'irradiation' => $this->format($irrMonth),
                        'prDesign' => $this->format($prDesignPvSyst),
                        'ertragDesign' => $this->format($ertragPvSyst),
                        'spezErtragDesign' => $this->format($spezErtragDesign),
                        'prGuar' => $this->format($prGuarantie),
                        'eGridReal' => $this->format($eGridReal),
                        'eGridRealDesign' => $this->format($eGridReal - $ertragPvSyst),
                        'eGridReal-Guar' => $this->format($eGridReal - $eGridGuar),
                        'spezErtrag' => $this->format($eGridReal / $anlage->getPnom(), 2),
                        'prReal' => $this->format($prReal),
                        'availability' => $this->format($availability),
                        'prReal_prDesign' => $this->format($prReal - $prDesignPvSyst),
                        'dummy' => '',
                        'prReal_prGuar' => $this->format($prReal - $prGuarantie),
                        'prReal_prProg' => $this->format($prRealprProg),
                        'anteil' => $this->format($anteil * 100),
                        'expectedErtrag' => $this->format($expectedYield),
                        'guaranteedExpexted' => $this->format($guaranteedExpexted),
                        'minusExpected' => $this->format(($eGridReal / $guaranteedExpexted * 100) - 100),
                        'currentMonthClass' => $currentMonthClass,
                    ];
                }
                ++$month;
            }
        }
        $report[0][] = [
            'month' => 'Forecast<br>'.$forecastDateText,
            'days' => 'months: '.$anzahlMonate,
            'irradiation' => $this->format($sumIrrMonth),
            'prDesign' => $this->format($anlage->getDesignPR()),
            'ertragDesign' => $this->format($sumErtragDesign),
            'spezErtragDesign' => $this->format($sumErtragDesign / $anlage->getKwPeakPvSyst()),
            'prGuar' => $this->format($anlage->getContractualPR()),
            'eGridReal' => $this->format($sumEGridReal),
            'eGridRealDesign' => $this->format($sumEGridRealDesign),
            'eGridReal-Guar' => $this->format($sumEGridReal - ($sumErtragDesign * (1 - ((float) $anlage->getDesignPR() - (float) $anlage->getContractualPR()) / 100))),
            'spezErtrag' => $this->format($sumEGridReal / $anlage->getPnom()),
            'prReal' => $this->format($sumPrReal / $counter),
            'prReal_prDesign' => $this->format(($sumPrReal / $counter) - $anlage->getDesignPR()),
            // 'availability'          => $this->format($sumAvailability / $counter),
            'availability' => $this->format($sumAvailability / $counter),
            'dummy' => '',
            'prReal_prGuar' => $this->format(($sumPrReal / $counter) - $anlage->getContractualPR()),
            'prReal_prProg' => $this->format($sumPrRealPrProg),
            'anteil' => $this->format($sumAnteil * 100),
            'expectedErtrag' => $this->format($sumExpectedYield),
            'guaranteedExpexted' => $this->format($sumGuaranteedExpexted),
            'minusExpected' => $this->format(($sumEGridReal / $sumGuaranteedExpexted * 100) - 100),
            'currentMonthClass' => 'sum-forcast',
        ];
        --$currentMonth;
        $paFormel = $this->availabilityService->calcAvailability($anlage, $anlage->getFacDateStart(), date_create("$year-$currentMonth-$daysInMonth 23:59"));
        $report[0][] = [
            'month' => 'Real<br>'.$realDateText,
            'days' => 'months: '.$monateReal,
            'irradiation' => $this->format($sumIrrMonth),
            'prDesign' => $this->format($anlage->getDesignPR()),
            'ertragDesign' => $this->format($sumErtragDesignReal),
            'spezErtragDesign' => $this->format($sumErtragDesignReal / $anlage->getKwPeakPvSyst()),
            'prGuar' => $this->format($anlage->getContractualPR()),
            'eGridReal' => $this->format($sumEGridRealReal),
            'eGridRealDesign' => $this->format($sumEGridRealDesignReal),
            'eGridReal-Guar' => $this->format($sumEGridRealReal - ($sumErtragDesignReal * (1 - ((float) $anlage->getDesignPR() - (float) $anlage->getContractualPR()) / 100))),
            'spezErtrag' => $this->format($sumEGridRealReal / $anlage->getPnom()),
            'prReal' => $this->format($formelPR), // $this->format($sumPrRealReal / $counterReal),
            'prReal_prDesign' => $this->format($formelPR - $anlage->getDesignPR()),
            'availability' => $this->format($paFormel),
            'dummy' => '',
            'prReal_prGuar' => $this->format($formelPR - $anlage->getContractualPR()), // $this->format(($sumPrRealReal / $counterReal) - $anlage->getContractualPR()),
            'prReal_prProg' => $this->format($formelPR),
            'anteil' => '-',
            'expectedErtrag' => $this->format($sumExpectedYieldReal),
            'guaranteedExpexted' => $this->format($sumGuaranteedExpextedReal),
            'minusExpected' => $this->format(($sumEGridRealReal / $sumGuaranteedExpextedReal * 100) - 100),
            'currentMonthClass' => 'sum-real',
        ];

        // PR Abweichung für das Jahr berechen → Daten für PR Forecast
        $prDiffYear = $sumPrRealPrProg - $anlage->getContractualPR();

        // Ergebnis Forecast 24 Monate
        $guaranteedExpectedEnergy = $sumGuaranteedExpexted;
        $measuredEnergy = $sumEGridReal;
        $availability = $sumAvailability / $counter;
        $expectedEnery = $sumExpectedYield;
        // je nachdem welche Formel für die PLD Berecnung genutzt werden soll
        switch ($anlage->getPldDivisor()) {
            case 'guaranteedExpected':
                $pld = (($guaranteedExpectedEnergy - ($measuredEnergy / (round($availability, 2) / 100))) / $guaranteedExpectedEnergy) * 100 * $anlage->getPldYield();
                $diffdCalculation = $measuredEnergy - $guaranteedExpectedEnergy;
                $percentDiffCalulation = ($measuredEnergy - $guaranteedExpectedEnergy) * 100 / $guaranteedExpectedEnergy;
                $ratio = $measuredEnergy * 100 / $guaranteedExpectedEnergy;
                $differenceCalcExplanation = 'Measured Energy - guaranteed Expected Energy';
                $percentDifferenceCalcExplanation = '(Measured Energy - guaranteed Expected Energy) x 100 / guaranteed Expected Energy';
                $ratioExplanation = 'Measured Energy x 100 / guaranteed Expected Energy';
                break;
            default:
                $pld = (($expectedEnery - ($measuredEnergy / (round($availability, 2) / 100))) / $expectedEnery) * 100 * $anlage->getPldYield();
                $diffdCalculation = $measuredEnergy - $expectedEnery;
                $percentDiffCalulation = ($measuredEnergy - $expectedEnery) * 100 / $expectedEnery;
                $ratio = $measuredEnergy * 100 / $expectedEnery;
                $differenceCalcExplanation = 'Measured Energy - Expected Energy';
                $percentDifferenceCalcExplanation = '(Measured Energy - Expected Energy) x 100 / Expected Energy';
                $ratioExplanation = 'Measured Energy x 100 / Expected Energy';
        }

        $pldExplanation = ($pld <= 0) ? 'keine PLD Zahlung' : 'PLD Zahlung';
        $report[1] = [
            [
                'parameter' => 'Guaranteed Expected Energy',
                'value' => $this->format($guaranteedExpectedEnergy),
                'unit' => 'kWh',
                'explanation' => 'Expected Energy - '.$anlage->getTransformerTee().'% Trafoverlust - '.$anlage->getGuaranteeTee().'% Sicherheitsabschlag',
            ],
            [
                'parameter' => 'Measured Energy',
                'value' => $this->format($measuredEnergy),
                'unit' => 'kWh',
                'explanation' => 'Einspeisung am Grid meter',
            ],
            [
                'parameter' => 'Availability',
                'value' => $this->format($availability),
                'unit' => '%',
                'explanation' => 'Verf&uuml;gbarkeit nach Annex 5.2 incl. downtime-Korrektur ',
            ],
            [
                'parameter' => 'Expected Energy',
                'value' => $this->format($expectedEnery),
                'unit' => 'kWh',
                'explanation' => 'monatlich aktualisierter PVSYST-Ertrag',
            ],
            [
                'parameter' => 'PLD',
                'value' => $this->format($pld),
                'unit' => 'EURO',
                'explanation' => $pldExplanation,
            ],
            [
                'parameter' => 'Difference Calculation',
                'value' => $this->format($diffdCalculation),
                'unit' => 'kWh/year',
                'explanation' => $differenceCalcExplanation,
            ],
            [
                'parameter' => 'Percent Difference Calculation',
                'value' => $this->format($percentDiffCalulation),
                'unit' => '%',
                'explanation' => $percentDifferenceCalcExplanation,
            ],
            [
                'parameter' => 'Ratio',
                'value' => $this->format($ratio),
                'unit' => '%',
                'explanation' => $ratioExplanation,
            ],
        ];

        $report[2] = [
            [
                'PRDesign' => $anlage->getDesignPR(),
                'PRgarantiert' => $anlage->getContractualPR(),
                'ExpectedEnergy' => $this->format($expectedEnery),
                'ExpectedEnergyGuar' => $this->format($guaranteedExpectedEnergy),
                'AbschlagTrafo' => $anlage->getTransformerTee(),
                'AbschlagGarantie' => $anlage->getGuaranteeTee(),
                'kwPeak' => $anlage->getPnom(),
                'kwPeakPvSyst' => $anlage->getKwPeakPvSyst(),
                'startFac' => $anlage->getFacDateStart()->format('d.m.Y'),
                'endeFac' => $anlage->getFacDate()->format('d.m.Y'),
                'startPac' => $anlage->getPacDate()->format('d.m.Y'),
                'endePac' => $anlage->getPacDateEnd()->format('d.m.Y'),
            ],
        ];

        // Ergebnis PAC Date bis letzte Tag des Auszuwertenden Zeitraums
        $guaranteedExpectedEnergy = $sumGuaranteedExpextedReal;
        $measuredEnergy = $sumEGridRealReal;
        $availability = $paFormel;
        $expectedEnery = $sumExpectedYieldReal;

        // je nachdem welche Formel für die PLD Berecnung genutzt werden soll

        switch ($anlage->getPldDivisor()) {
            case 'guaranteedExpected':
                $pld = (($guaranteedExpectedEnergy - ($measuredEnergy / (round($availability, 2) / 100))) / $guaranteedExpectedEnergy) * 100 * (($anlage->isUsePnomForPld()) ? $anlage->getPnom() : 1) * $anlage->getPldYield();
                $diffdCalculation = $measuredEnergy - $guaranteedExpectedEnergy;
                $percentDiffCalulation = ($measuredEnergy - $guaranteedExpectedEnergy) * 100 / $guaranteedExpectedEnergy;
                $ratio = $measuredEnergy * 100 / $guaranteedExpectedEnergy;
                break;
            default:
                $pld = (($expectedEnery - ($measuredEnergy / (round($availability, 2) / 100))) / $expectedEnery) * 100 * (($anlage->isUsePnomForPld()) ? $anlage->getPnom() : 1) * $anlage->getPldYield();
                $diffdCalculation = $measuredEnergy - $expectedEnery;
                $percentDiffCalulation = ($measuredEnergy - $expectedEnery) * 100 / $expectedEnery;
                $ratio = $measuredEnergy * 100 / $expectedEnery;
        }

        $pldExplanation = ($pld <= 0) ? 'keine PLD Zahlung' : 'PLD Zahlung';

        $report[3] = [
            [
                'parameter' => 'Guaranteed Expected Energy',
                'value' => $this->format($guaranteedExpectedEnergy),
                'unit' => 'kWh',
                'explanation' => 'Expected Energy - '.$anlage->getTransformerTee().'% Trafoverlust - '.$anlage->getGuaranteeTee().'% Sicherheitsabschlag',
            ],
            [
                'parameter' => 'Measured Energy',
                'value' => $this->format($measuredEnergy),
                'unit' => 'kWh',
                'explanation' => 'Einspeisung am Grid meter',
            ],
            [
                'parameter' => 'Availability',
                'value' => $this->format($availability),
                'unit' => '%',
                'explanation' => 'Verf&uuml;gbarkeit nach Annex 5.2 incl. downtime-Korrektur ',
            ],
            [
                'parameter' => 'Expected Energy',
                'value' => $this->format($expectedEnery),
                'unit' => 'kWh',
                'explanation' => 'monatlich aktualisierter PVSYST-Ertrag',
            ],
            [
                'parameter' => 'PLD',
                'value' => $this->format($pld),
                'unit' => 'EURO',
                'explanation' => $pldExplanation,
            ],
            [
                'parameter' => 'Difference Calculation',
                'value' => $this->format($diffdCalculation),
                'unit' => 'kWh/year',
                'explanation' => $differenceCalcExplanation,
            ],
            [
                'parameter' => 'Percent Difference Calculation',
                'value' => $this->format($percentDiffCalulation),
                'unit' => '%',
                'explanation' => $percentDifferenceCalcExplanation,
            ],
            [
                'parameter' => 'Ratio',
                'value' => $this->format($ratio),
                'unit' => '%',
                'explanation' => $ratioExplanation,
            ],
        ];

        return $report;
    }

    private function format($value, $round = 2): float
    {
        return round($value, $round);
    }
}
