<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\MonthlyDataRepository;
use App\Repository\PRRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ReportsEpcNewService
{
    private AnlagenRepository $anlageRepo;
    private GridMeterDayRepository $gridMeterRepo;
    private PRRepository $prRepository;
    private MonthlyDataRepository $monthlyDataRepo;
    private EntityManagerInterface $em;
    private NormalizerInterface $serializer;
    private FunctionsService $functions;
    private PRCalulationService $PRCalulation;

    public function __construct(AnlagenRepository $anlageRepo, GridMeterDayRepository $gridMeterRepo, PRRepository $prRepository,
                                MonthlyDataRepository $monthlyDataRepo, EntityManagerInterface $em, NormalizerInterface $serializer,
                                FunctionsService $functions, PRCalulationService $PRCalulation)
    {
        $this->anlageRepo = $anlageRepo;
        $this->gridMeterRepo = $gridMeterRepo;
        $this->prRepository = $prRepository;
        $this->monthlyDataRepo = $monthlyDataRepo;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->functions = $functions;
        $this->PRCalulation = $PRCalulation;
    }

    use G4NTrait;

    public function monthTable(Anlage $anlage, ?DateTime $date = null): array
    {
        if ($date === null) $date = new DateTime();

        $tableArray = [];
        $anzahlMonate = ((int)$anlage->getEpcReportEnd()->format('Y') - (int)$anlage->getEpcReportStart()->format('Y')) * 12 + ((int)$anlage->getEpcReportEnd()->format('m') - (int)$anlage->getEpcReportStart()->format('m')) + 1;
        $startYear = $anlage->getEpcReportStart()->format('Y');
        $endYear = $anlage->getEpcReportEnd()->format('Y');
        $yearCount = $endYear - $startYear;
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');

        $year = $startYear;
        if (self::getCetTime('object') < $anlage->getFacDateStart()) {
            $facStartMonth = $anlage->getPacDate()->format('m');
            $facStartDay = $anlage->getPacDate()->format('d');
            $facEndMonth = $anlage->getPacDateEnd()->format('m');
            $facEndDay = $anlage->getPacDateEnd()->format('d');
            $month = $anlage->getPacDate()->format('m') * 1;
            $daysInStartMonth = (int)$anlage->getPacDate()->format('j');
            $daysInEndMonth = (int)$anlage->getPacDateEnd()->format('j');
        } else {
            $facStartMonth = $anlage->getFacDateStart()->format('m');
            $facStartDay = $anlage->getFacDateStart()->format('d');
            $facEndMonth = $anlage->getFacDate()->format('m');
            $facEndDay = $anlage->getFacDate()->format('d');
            $month = $anlage->getFacDateStart()->format('m') * 1;
            $daysInStartMonth = (int)$anlage->getFacDateStart()->format('j');
            $daysInEndMonth = (int)$anlage->getFacDate()->format('j');
        }

        if (true) { //prüfe auf PVSYST verfügbar
            $pvSystData = $anlage->getPvSystMonthsArray();
        }
        // Runde 1 //
        for ($n = 1; $n <= $anzahlMonate; $n++) {
            if ($month >= 13) {
                $month = 1;
                $year++;
            }

            $daysInMonth    = (int)date('t', strtotime("$year-$month-01"));
            $from_local     = date_create(date('Y-m-d 00:00', strtotime("$year-$month-01")));
            $to_local       = date_create(date('Y-m-d 23:59', strtotime("$year-$month-$daysInMonth")));
            $hasMonthData   = $to_local <= $date; // Wenn das Datum in $to_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true

            if (true) { //prüfe auf PVSYST verfügbar
                $monthlyRecalculatedData = $this->monthlyDataRepo->findOneBy(['anlage' => $anlage, 'year' => $year, 'month' => $month]);
            }
            switch ($n) {
                case 1:
                    $from_local = date_create(date('Y-m-d', strtotime("$year-$month-$facStartDay 00:00")));

                    $days = $daysInMonth - $daysInStartMonth +1;
                    break;
                case $anzahlMonate:
                    $days = $daysInEndMonth;
                    $to_local = date_create(date('Y-m-d', strtotime("$year-$month-$facEndDay 23:59")));
                    break;
                default:
                    $days = $daysInMonth;

            }
            $prArray = $this->PRCalulation->calcPR($anlage, $from_local, $to_local);

            if ($anlage->getUseGridMeterDayData()){
                if ($monthlyRecalculatedData != null && $monthlyRecalculatedData->getExternMeterDataMonth() > 0) {
                    $eGridReal = $monthlyRecalculatedData->getExternMeterDataMonth();
                } else {
                    $eGridReal = $prArray['powerEGridExt'];
                }
                $prReal = $prArray['prEGridExt'];
            } else {
                $eGridReal = $prArray['powerEvu'];
                $prReal = $prArray['prEvu'];
            }
            #

            $tableArray[$n]['month']                        = date('m / Y', strtotime("$year-$month-1")); // Spalte B
            $tableArray[$n]['days']                         = $days; // Spalte C
            $tableArray[$n]['irrDesign']                    = ($hasMonthData) ? $monthlyRecalculatedData->getPvSystIrr() : $pvSystData[$month-1]['irrDesign']; // Spalte D // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$n]['yieldDesign']                  = ($hasMonthData) ? $monthlyRecalculatedData->getPvSystErtrag() : $pvSystData[$month-1]['ertragDesign']; // Spalte E // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$n]['specificYieldDesign']          = ($hasMonthData) ? $monthlyRecalculatedData->getPvSystErtrag() / $anlage->getKwPeakPvSyst() : $pvSystData[$month-1]['ertragDesign'] / $anlage->getKwPeakPvSyst();  // Spalte F // berechnet aus IrrDesign un der Anlagenleistung (kwPeak)
            $tableArray[$n]['prDesign']                     = ($hasMonthData) ? $monthlyRecalculatedData->getPvSystPR() : $pvSystData[$month-1]['prDesign']; // Spalte G // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$n]['prGuarantie']                  = ($hasMonthData) ? $monthlyRecalculatedData->getPvSystPR() - $anlage->getTransformerTee() - $anlage->getGuaranteeTee() : $pvSystData[$month-1]['prDesign'] - $anlage->getTransformerTee() - $anlage->getGuaranteeTee(); // Spalte H
            $tableArray[$n]['theorYieldDesign']             = ($hasMonthData) ? $monthlyRecalculatedData * $anlage->getKwPeakPvSyst() : $pvSystData[$month-1]['irrDesign'] * $anlage->getKwPeakPvSyst(); // Spalte I
            $tableArray[$n]['theorYieldMTDesign']           = ""; // Spalte J
            $tableArray[$n]['irrFTDesign']                  = ""; // Spalte K
            $tableArray[$n]['irr']                          = ($hasMonthData) ? $prArray['irradiation'] : ''; // Spalte L // Irradiation
            $tableArray[$n]['eGridYield']                   = $eGridReal; // Spalte QM // eGrid gemessen (je nach Konfiguration der Anlage aus dem Feld e_z_evu oder aus den Tageswerten der externen Grid Messung
            $tableArray[$n]['specificYield']                = $eGridReal / $anlage->getKwPeak(); // Spalte N
            $tableArray[$n]['availability']                 = $prArray['availability']; // Spalte O
            $tableArray[$n]['part']                         = ""; // Spalte P // muss in Runde 2 Berechnet werden
            $tableArray[$n]['prReal_prProg']                = ($hasMonthData) ? $eGridReal : $pvSystData[$month-1]['ertragDesign']; // Spalte Q // PR Real bzw PR prognostiziert wenn noch kein PR Real vorhanden
            $tableArray[$n]['theorYield']                   = $prArray['irradiation'] * $anlage->getKwPeak(); // Spalte R // theoretical Energy ohne FT Korrektur
            $tableArray[$n]['theorYieldMT']                 = $prArray['powerTheoTempCorr']; // Spalte S // theoretical Energy mit FT Korrektur
            $tableArray[$n]['irrMT']                        = ""; // Spalte T // Irradiation mit FT Korrektur (haben wir noch nicht)

            $tableArray[$n]['prReal_withRisk']              = ""; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$n]['eGrid_withRisk']               = ""; // Spalte V //
            $tableArray[$n]['yield_guaranteed']             = ""; // Spalte W //
            $tableArray[$n]['yieldEGrid']                   = $tableArray[$n]['eGrid_withRisk'] - $tableArray[$n]['yield_guaranteed']; // Spalte X
            $tableArray[$n]['prRealMinusPrGuraReduction']   = $tableArray[$n]['prReal_withRisk'] - $tableArray[$n]['prGuarantie']; // Spalte Y
            $tableArray[$n]['yieldEGridForecast']           = ""; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$n]['yieldEGridMinusGuranteed']     = ""; // Spalte AA // muss in Runde 2 Berechnet werden
            $tableArray[$n]['prRealMinusPrGura']            = $tableArray[$n]['prReal_prProg'] - $tableArray[$n]['prGuarantie']; // Spalte AB
            $tableArray[$n]['eGridDivExpected']             = ""; // Spalte AC // muss in Runde 2 Berechnet werden


            $month++;
        }

        return $tableArray;
    }
}