<?php

namespace App\Service\Reports;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\MonthlyDataRepository;
use App\Repository\PRRepository;
use App\Service\AvailabilityService;
use App\Service\FunctionsService;
use App\Service\PRCalulationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Service\PdoService;
use App\Service\LogMessagesService;

class ReportEpcService
{
    use G4NTrait;

    public function __construct(
private readonly PdoService $pdoService,
        private readonly AnlagenRepository $anlageRepo,
        private readonly GridMeterDayRepository $gridMeterRepo,
        private readonly PRRepository $prRepository,
        private readonly MonthlyDataRepository $monthlyDataRepo,
        private readonly EntityManagerInterface $em,
        private readonly NormalizerInterface $serializer,
        private readonly FunctionsService $functions,
        private readonly PRCalulationService $PRCalulation,
        private readonly AvailabilityService $availabilityService,
        private readonly ReportsEpcYieldV2 $epcYieldV2,
        private LogMessagesService $logMessages,
    )
    {}

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function createEpcReport(Anlage $anlage, DateTime $date, ?string $userId = null, ?int $logId = null): string
    {
        $currentDate = date('Y-m-d H-i');
        $error = false;
        $output = '';
        switch ($anlage->getEpcReportType()) {
            case 'prGuarantee' :
                $reportArray = $this->reportPRGuarantee($anlage, $date);
                break;
            case 'yieldGuarantee':
                $monthTable = $this->epcYieldV2->monthTable($anlage, $date);
                $reportArray['monthTable'] = $monthTable;
                $reportArray['forcastTable'] = $this->epcYieldV2->forcastTable($anlage, $monthTable, $date);
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
     * @throws Exception|InvalidArgumentException
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
                $spezErtragDesign   = $ertragPvSyst / ($anlage->getKwPeakPvSyst() > 0 ? $anlage->getKwPeakPvSyst() : $anlage->getPnom());

                /** @var AnlagenPR $pr */
                $pr = $this->prRepository->findOneBy(['anlage' => $anlage, 'stamp' => date_create(date('Y-m-d', strtotime("$year-$month-$daysInMonth")))]);

                $class = '';
                if (date_create($from) <= $date) {
                    $prReal         = $prArray['prDep2Evu']; // $this->format($pr->getPrEvuMonth());
                    $prStandard     = $prArray['prDep0Evu']; // $this->format($pr->getPrDefaultMonthEvu());
                    switch ($n) {
                        case 1:
                        case $anzahlMonate:
                            $eGridReal      = $prArray['powerEvuDep2'];
                            $irrMonth       = $prArray['irradiation'];
                            $prAvailability = $prArray['pa2'];
                            if ($anlage->getUseGridMeterDayData()) {
                                $eGridReal  = $prArray['powerEGridExt'];
                                $prReal     = $prArray['prDep2EGridExt'];
                                $prStandard = $prArray['prDep0EGridExt'];
                            }
                            break;
                        default:
                            $eGridReal      = $prArray['powerEvuDep2']; // $pr->getPowerEvuMonth();
                            $irrMonth       = $prArray['irradiation']; // $pr->getIrrMonth();
                            $prAvailability = $prArray['pa2']; // $this->availabilityService->calcAvailability($anlage, date_create("$year-$month-01 00:00"), date_create("$year-$month-$days 23:59"));
                            if ($anlage->getUseGridMeterDayData()) {
                                $eGridReal  = $prArray['powerEGridExt']; // $this->gridMeterRepo->sumByDateRange($anlage, $from, $to);
                                $prReal     = $prArray['prDep2EGridExt']; // $pr->getPrEGridExtMonth();
                                $prStandard = $prArray['prDep0EGridExt']; // $this->format($pr->getPrDefaultMonthEGridExt());
                            }
                    }
                    $prRealprProg = $prReal;
                    $realDateTextEnd = date('My', strtotime("$year-$month-1"));
                    if (($month == $currentMonth && $year == $currentYear) && $run === 2) {
                        // für das Einfärben der Zeile des aktuellen Monats
                        $class  = 'current-month';
                        $prArrayFormel = $this->PRCalulation->calcPR($anlage, $anlage->getEpcReportStart(), date_create($to));
                        if ($anlage->getUseGridMeterDayData()) {
                            $formelEnergy   = $prArrayFormel['powerEGridExt'];
                            $formelPR       = $prArrayFormel['prDep2EGridExt'];
                        } else {
                            $formelEnergy   = $prArrayFormel['powerEvuDep2'];
                            $formelPR       = $prArrayFormel['prDep2Evu'];
                        }
                        $formelIrr          = $prArrayFormel['irradiation'];
                        $formelPowerTheo    = $prArrayFormel['powerTheoDep2'];
                        $formelAvailability = $prArrayFormel['pa2'];
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
                    if ($counter > 24) $counter = 24;

                }
                if ($run === 2) {// Monatswerte berechnen
                    if ($n === $anzahlMonate) {
                        $realDateText .= $realDateTextEnd;
                    }
                    $sumSpezErtragDesign = $sumErtragDesign / ($anlage->getKwPeakPvSyst() > 0 ? $anlage->getKwPeakPvSyst() : $anlage->getPnom());
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
                        'eGridRealDesign'   => $this->format($eGridReal - $ertragPvSyst),
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
                        'currentMonthClass' => $class,
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
                'spezErtragDesign'      => $this->format($sumErtragDesign / ($anlage->getKwPeakPvSyst() > 0 ? $anlage->getKwPeakPvSyst() : $anlage->getPnom())),
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
            'month'                 => 'Real'.$realDateText,
            'days'                  => 'months: '.$monateReal,
            'irradiation'           => $this->format($sumIrrMonth),
            'prDesign'              => $this->format($anlage->getDesignPR()),
            'ertragDesign'          => $this->format($sumErtragDesignReal),
            'spezErtragDesign'      => $this->format($sumErtragDesignReal / ($anlage->getKwPeakPvSyst() > 0 ? $anlage->getKwPeakPvSyst() : $anlage->getPnom())),
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
            'eGridReal' => $this->format($formelEnergy), // $formelEnergy | $sumEGridRealReal
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
