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
use Hisune\EchartsPHP\ECharts;

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
    private AvailabilityService $availabilityService;

    public function __construct(AnlagenRepository $anlageRepo, GridMeterDayRepository $gridMeterRepo, PRRepository $prRepository,
                                MonthlyDataRepository $monthlyDataRepo, EntityManagerInterface $em, NormalizerInterface $serializer,
                                FunctionsService $functions, PRCalulationService $PRCalulation, AvailabilityService $availabilityService)
    {
        $this->anlageRepo = $anlageRepo;
        $this->gridMeterRepo = $gridMeterRepo;
        $this->prRepository = $prRepository;
        $this->monthlyDataRepo = $monthlyDataRepo;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->functions = $functions;
        $this->PRCalulation = $PRCalulation;
        $this->availabilityService = $availabilityService;
    }

    use G4NTrait;

    public function monthTable(Anlage $anlage, ?DateTime $date = null): array
    {
        if ($date === null) $date = new DateTime();

        $tableArray = [];
        $anzahlMonate = ((int)$anlage->getEpcReportEnd()->format('Y') - (int)$anlage->getEpcReportStart()->format('Y')) * 12 + ((int)$anlage->getEpcReportEnd()->format('m') - (int)$anlage->getEpcReportStart()->format('m')) + 1;
        $zeileSumme1 = $anzahlMonate + 1;
        $zeileSumme2 = $anzahlMonate + 2;
        $zeileSumme3 = $anzahlMonate + 3;

        $tableArray[$zeileSumme1]['C_days']                      = 0;
        $tableArray[$zeileSumme1]['D_irrDesign']                 = 0;
        $tableArray[$zeileSumme1]['E_yieldDesign']               = 0;
        $tableArray[$zeileSumme1]['F_specificYieldDesign']       = 0;
        $tableArray[$zeileSumme1]['I_theorYieldDesign']          = 0;
        $tableArray[$zeileSumme1]['L_irr']                       = 0;
        $tableArray[$zeileSumme1]['M_eGridYield']                = 0;
        $tableArray[$zeileSumme1]['R_theorYield']                = 0;
        $tableArray[$zeileSumme1]['S_theorYieldMT']              = 0;
        $tableArray[$zeileSumme1]['W_yield_guaranteed_exp']      = 0;
        $tableArray[$zeileSumme1]['AA_yieldEGridMinusGuranteed'] = 0;
        $tableArray[$zeileSumme1]['current_month']               = 0;
        $tableArray[$zeileSumme2]['C_days']                      = 0;
        $tableArray[$zeileSumme2]['D_irrDesign']                 = 0;
        $tableArray[$zeileSumme2]['E_yieldDesign']               = 0;
        $tableArray[$zeileSumme2]['F_specificYieldDesign']       = 0;
        $tableArray[$zeileSumme2]['I_theorYieldDesign']          = 0;
        $tableArray[$zeileSumme2]['L_irr']                       = 0;
        $tableArray[$zeileSumme2]['M_eGridYield']                = 0;
        $tableArray[$zeileSumme2]['R_theorYield']                = 0;
        $tableArray[$zeileSumme2]['S_theorYieldMT']              = 0;
        $tableArray[$zeileSumme2]['W_yield_guaranteed_exp']      = 0;
        $tableArray[$zeileSumme2]['AA_yieldEGridMinusGuranteed'] = 0;
        $tableArray[$zeileSumme2]['current_month']               = 0;
        $tableArray[$zeileSumme3]['C_days']                      = 0;
        $tableArray[$zeileSumme3]['D_irrDesign']                 = 0;
        $tableArray[$zeileSumme3]['E_yieldDesign']               = 0;
        $tableArray[$zeileSumme3]['F_specificYieldDesign']       = 0;
        $tableArray[$zeileSumme3]['I_theorYieldDesign']          = 0;
        $tableArray[$zeileSumme3]['L_irr']                       = 0;
        $tableArray[$zeileSumme3]['M_eGridYield']                = 0;
        $tableArray[$zeileSumme3]['R_theorYield']                = 0;
        $tableArray[$zeileSumme3]['S_theorYieldMT']              = 0;
        $tableArray[$zeileSumme3]['W_yield_guaranteed_exp']      = 0;
        $tableArray[$zeileSumme3]['AA_yieldEGridMinusGuranteed'] = 0;
        $tableArray[$zeileSumme3]['current_month']               = 0;


        $startYear = $anlage->getEpcReportStart()->format('Y');
        $endYear = $anlage->getEpcReportEnd()->format('Y');
        $startMonth = (int)$anlage->getFacDateStart()->format('m') ;
        $yearCount = $endYear - $startYear;
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        $reportMonth = $currentMonth - 1;
        $reportYear = ($reportMonth == 12) ? $currentYear - 1 : $currentYear;
        $daysInReportMonth = (int)date('t', strtotime("$reportYear-$reportMonth-01"));
        $facStartMonth = (int)$anlage->getFacDateStart()->format('m');
        $facStartDay = $anlage->getFacDateStart()->format('d');
        $facEndMonth = $anlage->getFacDate()->format('m');
        $facEndDay = $anlage->getFacDate()->format('d');

        $month = $startMonth;
        $year = $startYear;

        $daysInStartMonth = (int)$anlage->getFacDateStart()->format('j');
        $daysInEndMonth = (int)$anlage->getFacDate()->format('j');

        $endDateCurrentReportMonth = date_create("$reportYear-$reportMonth-$daysInReportMonth");

        if (true) { //prüfe auf PVSYST verfügbar
            $pvSystData = $anlage->getPvSystMonthsArray();
        }

        $availabilitySummeZeil2 = $this->availabilityService->calcAvailability($anlage, $anlage->getFacDateStart(), $endDateCurrentReportMonth);

        /////////////////////////////
        /// Runde 1
        /////////////////////////////

        for ($n = 1; $n <= $anzahlMonate; $n++) {
            if ($month >= 13) {
                $month = 1;
                $year++;
            }

            $daysInMonth = (int)date('t', strtotime("$year-$month-01"));
            $from_local = date_create(date('Y-m-d 00:00', strtotime("$year-$month-01")));
            $to_local = date_create(date('Y-m-d 23:59', strtotime("$year-$month-$daysInMonth")));
            $hasMonthData = $to_local < $date; // Wenn das Datum in $to_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true
            $isCurrentMonth = $to_local->format('Y') == $currentYear && $to_local->format('m') == $currentMonth-1;

            if (true) { //prüfe auf PVSYST verfügbar
                $monthlyRecalculatedData = $this->monthlyDataRepo->findOneBy(['anlage' => $anlage, 'year' => $year, 'month' => $month]);
            }
            switch ($n) {
                case 1:
                    $from_local = date_create(date('Y-m-d', strtotime("$year-$month-$facStartDay 00:00")));
                    $days = $daysInMonth - $daysInStartMonth + 1;
                    $factor = $days / $daysInMonth;
                    break;
                case $anzahlMonate:
                    $days = $daysInEndMonth;
                    $factor = $days / $daysInMonth;
                    $to_local = date_create(date('Y-m-d', strtotime("$year-$month-$facEndDay 23:59")));
                    break;
                default:
                    $days = $daysInMonth;
                    $factor = 1;

            }
            $prArray = $this->PRCalulation->calcPR($anlage, $from_local, $to_local);

            if ($anlage->getUseGridMeterDayData()) {
                if ($monthlyRecalculatedData != null && $monthlyRecalculatedData->getExternMeterDataMonth() > 0) {
                    $eGridReal = $monthlyRecalculatedData->getExternMeterDataMonth();
                } else {
                    $eGridReal = $prArray['powerEGridExt'];
                }
            } else {
                $eGridReal = $prArray['powerEvu'];
            }

            $tableArray[$n]['B_month']                                = date('m / Y', strtotime("$year-$month-1")); // Spalte B
            $tableArray[$n]['C_days']                                 = $days; // Spalte C
            $tableArray[$n]['D_irrDesign']                            = ($hasMonthData) ? $monthlyRecalculatedData->getPvSystIrr() : $pvSystData[$month - 1]['irrDesign'] * $factor; // Spalte D // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$n]['E_yieldDesign']                          = ($hasMonthData) ? $monthlyRecalculatedData->getPvSystErtrag() : $pvSystData[$month - 1]['ertragDesign'] * $factor; // Spalte E // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$n]['F_specificYieldDesign']                  = $tableArray[$n]['E_yieldDesign'] / $anlage->getKwPeakPvSyst();  // Spalte F // berechnet aus IrrDesign un der Anlagenleistung (kwPeak)
            $tableArray[$n]['G_prDesign']                             = $tableArray[$n]['F_specificYieldDesign'] / $tableArray[$n]['D_irrDesign'] * 100; // Spalte G // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$n]['H_prGuarantie']                          = $tableArray[$n]['G_prDesign'] - $anlage->getTransformerTee() - $anlage->getGuaranteeTee(); // Spalte H
            $tableArray[$n]['I_theorYieldDesign']                     = ($hasMonthData) ? $monthlyRecalculatedData->getPvSystIrr() * $anlage->getKwPeakPvSyst() : $pvSystData[$month - 1]['irrDesign'] * $anlage->getKwPeakPvSyst() * $factor; // Spalte I
            $tableArray[$n]['J_theorYieldMTDesign']                   = ""; // Spalte J
            $tableArray[$n]['K_irrFTDesign']                          = ""; // Spalte K
            $tableArray[$n]['L_irr']                                  = ($hasMonthData) ? $prArray['irradiation'] : $tableArray[$n]['D_irrDesign']; // Spalte L // Irradiation
            $tableArray[$n]['M_eGridYield']                           = ($hasMonthData) ? $eGridReal : $pvSystData[$month - 1]['ertragDesign'] * $factor; // Spalte M // eGrid gemessen (je nach Konfiguration der Anlage aus dem Feld e_z_evu oder aus den Tageswerten der externen Grid Messung
            $tableArray[$n]['N_specificYield']                        = $tableArray[$n]['M_eGridYield'] / $anlage->getKwPeak(); // Spalte N
            $tableArray[$n]['O_availability']                         = ($hasMonthData) ? $prArray['availability'] : ''; // Spalte O
            $tableArray[$n]['P_part']                                 = 0; // Spalte P // muss in Runde 2 Berechnet werden
            $tableArray[$n]['Q_prReal_prProg']                        = $tableArray[$n]['N_specificYield'] / $tableArray[$n]['L_irr'] * 100; //($hasMonthData) ? $prReal : $pvSystData[$month - 1]['prDesign']; // Spalte Q // PR Real bzw PR prognostiziert wenn noch kein PR Real vorhanden
            $tableArray[$n]['R_theorYield']                           = $tableArray[$n]['L_irr'] * $anlage->getKwPeak(); // Spalte R // theoretical Energy ohne FT Korrektur
            $tableArray[$n]['S_theorYieldMT']                         = $prArray['powerTheoTempCorr']; // Spalte S // theoretical Energy mit FT Korrektur
            $tableArray[$n]['T_irrMT']                                = ""; // Spalte T // Irradiation mit FT Korrektur (haben wir noch nicht)
            $tableArray[$n]['U_prReal_withRisk']                      = 0; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$n]['V_eGrid_withRisk']                       = 0; // Spalte V // muss in Runde 2 Berechnet werden
            $tableArray[$n]['W_yield_guaranteed_exp']                 = $tableArray[$n]['E_yieldDesign'] * (1 - ($anlage->getTransformerTee() + $anlage->getGuaranteeTee()) / 100); // Spalte W //
            $tableArray[$n]['X_eGridMinuseGridGuar']                  = 0; // Spalte X
            $tableArray[$n]['Y_prRealMinusPrGuraReduction']           = 0; // Spalte Y
            $tableArray[$n]['Z_yieldEGridForecast']                   = 0; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$n]['AA_yieldEGridMinusGuranteed']             = $tableArray[$n]['M_eGridYield'] - $tableArray[$n]['W_yield_guaranteed_exp']; // Spalte AA
            $tableArray[$n]['AB_prRealMinusPrGura']                    = $tableArray[$n]['Q_prReal_prProg'] - $tableArray[$n]['H_prGuarantie']; // Spalte AB
            $tableArray[$n]['AC_eGridDivExpected']                     = 0; // Spalte AC // muss in Runde 2 Berechnet werden
            $tableArray[$n]['current_month']                           = ($isCurrentMonth) ? -1 : 0;
            $tableArray[$n]['style']                                   = "";

            $tableArray[$zeileSumme1]['B_month']                      = "2 years (incl. Forecast)"; // Spalte B
            $tableArray[$zeileSumme1]['C_days']                       += $days; // Spalte C
            $tableArray[$zeileSumme1]['D_irrDesign']                  += $tableArray[$n]['D_irrDesign']; // Spalte D // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$zeileSumme1]['E_yieldDesign']                += $tableArray[$n]['E_yieldDesign']; // Spalte E // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$zeileSumme1]['F_specificYieldDesign']        += $tableArray[$n]['F_specificYieldDesign'];  // Spalte F // berechnet aus IrrDesign un der Anlagenleistung (kwPeak)
            $tableArray[$zeileSumme1]['G_prDesign']                   =  $tableArray[$zeileSumme1]['F_specificYieldDesign'] / $tableArray[$zeileSumme1]['D_irrDesign'] * 100; // Spalte G // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$zeileSumme1]['H_prGuarantie']                =  $tableArray[$zeileSumme1]['G_prDesign'] - $anlage->getTransformerTee() - $anlage->getGuaranteeTee(); // Spalte H
            $tableArray[$zeileSumme1]['I_theorYieldDesign']           += ($hasMonthData) ? $monthlyRecalculatedData->getPvSystIrr() * $anlage->getKwPeakPvSyst() : $pvSystData[$month - 1]['irrDesign'] * $anlage->getKwPeakPvSyst(); // Spalte I
            $tableArray[$zeileSumme1]['J_theorYieldMTDesign']         = ''; // Spalte J
            $tableArray[$zeileSumme1]['K_irrFTDesign']                = ''; // Spalte K
            $tableArray[$zeileSumme1]['L_irr']                        += $tableArray[$n]['L_irr']; // Spalte L // Irradiation
            $tableArray[$zeileSumme1]['M_eGridYield']                 += $tableArray[$n]['M_eGridYield']; // Spalte QM // eGrid gemessen (je nach Konfiguration der Anlage aus dem Feld e_z_evu oder aus den Tageswerten der externen Grid Messung
            $tableArray[$zeileSumme1]['N_specificYield']              = $tableArray[$zeileSumme1]['M_eGridYield'] / $anlage->getKwPeak(); // Spalte N
            $tableArray[$zeileSumme1]['O_availability']               = ''; // Spalte O // eigentlich nicht berechenebar
            $tableArray[$zeileSumme1]['P_part']                       = 0; // Spalte P // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['Q_prReal_prProg']              = $tableArray[$zeileSumme1]['N_specificYield'] / $tableArray[$zeileSumme1]['L_irr'] * 100; // Spalte Q // PR Real bzw PR prognostiziert, wenn noch kein PR Real vorhanden // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['R_theorYield']                 += $tableArray[$n]['R_theorYield']; // Spalte R // theoretical Energy ohne FT Korrektur
            $tableArray[$zeileSumme1]['S_theorYieldMT']               += $prArray['powerTheoTempCorr']; // Spalte S // theoretical Energy mit FT Korrektur
            $tableArray[$zeileSumme1]['T_irrMT']                      = ''; // Spalte T // Irradiation mit FT Korrektur (haben wir noch nicht)
            $tableArray[$zeileSumme1]['U_prReal_withRisk']            = 0; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['V_eGrid_withRisk']             = 0; // Spalte V // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['W_yield_guaranteed_exp']       += $tableArray[$n]['W_yield_guaranteed_exp']; // Spalte W // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['X_eGridMinuseGridGuar']        = 0; // Spalte X // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['Y_prRealMinusPrGuraReduction'] = 0; // Spalte Y // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['Z_yieldEGridForecast']         = 0; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['AA_yieldEGridMinusGuranteed']   += $tableArray[$n]['AA_yieldEGridMinusGuranteed']; // Spalte AA
            $tableArray[$zeileSumme1]['AB_prRealMinusPrGura']          = $tableArray[$zeileSumme1]['Q_prReal_prProg'] - $tableArray[$zeileSumme1]['H_prGuarantie']; // Spalte AB
            $tableArray[$zeileSumme1]['AC_eGridDivExpected']           = 0; // Spalte AC // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['style']                         = "strong line";

            $tableArray[$zeileSumme2]['B_month']                      = "Current up to date"; // Spalte B
            $tableArray[$zeileSumme2]['C_days']                       += ($hasMonthData) ? $days : 0; // Spalte C
            $tableArray[$zeileSumme2]['D_irrDesign']                  += ($hasMonthData) ? $tableArray[$n]['D_irrDesign'] : 0; // Spalte D // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$zeileSumme2]['E_yieldDesign']                += ($hasMonthData) ? $tableArray[$n]['E_yieldDesign'] : 0; // Spalte E // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$zeileSumme2]['F_specificYieldDesign']        += ($hasMonthData) ? $tableArray[$n]['F_specificYieldDesign'] : 0;  // Spalte F // berechnet aus IrrDesign un der Anlagenleistung (kwPeak)
            $tableArray[$zeileSumme2]['G_prDesign']                   =  $tableArray[$zeileSumme2]['F_specificYieldDesign'] / $tableArray[$zeileSumme2]['D_irrDesign'] * 100; // Spalte G // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$zeileSumme2]['H_prGuarantie']                =  $tableArray[$zeileSumme2]['G_prDesign'] - $anlage->getTransformerTee() - $anlage->getGuaranteeTee(); // Spalte H
            $tableArray[$zeileSumme2]['I_theorYieldDesign']           += ($hasMonthData) ? $tableArray[$zeileSumme1]['I_theorYieldDesign'] : 0; // Spalte I
            $tableArray[$zeileSumme2]['J_theorYieldMTDesign']         = ''; // Spalte J
            $tableArray[$zeileSumme2]['K_irrFTDesign']                = ''; // Spalte K
            $tableArray[$zeileSumme2]['L_irr']                        += ($hasMonthData) ? $tableArray[$n]['L_irr'] : 0; // Spalte L // Irradiation
            $tableArray[$zeileSumme2]['M_eGridYield']                 += ($hasMonthData) ? $tableArray[$n]['M_eGridYield'] : 0; // Spalte M // eGrid gemessen (je nach Konfiguration der Anlage aus dem Feld e_z_evu oder aus den Tageswerten der externen Grid Messung
            $tableArray[$zeileSumme2]['N_specificYield']              = $tableArray[$zeileSumme2]['M_eGridYield'] / $anlage->getKwPeak(); // Spalte N
            $tableArray[$zeileSumme2]['O_availability']               = $availabilitySummeZeil2; // Spalte O
            $tableArray[$zeileSumme2]['P_part']                       = ''; // Spalte P // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme2]['Q_prReal_prProg']              = $tableArray[$zeileSumme2]['N_specificYield'] / $tableArray[$zeileSumme2]['L_irr'] * 100; // Spalte Q // PR Real bzw PR prognostiziert, wenn noch kein PR Real vorhanden // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme2]['R_theorYield']                 += ($hasMonthData) ? $tableArray[$n]['R_theorYield'] : 0; // Spalte R // theoretical Energy ohne FT Korrektur
            $tableArray[$zeileSumme2]['S_theorYieldMT']               += ($hasMonthData) ? $prArray['powerTheoTempCorr'] : 0; // Spalte S // theoretical Energy mit FT Korrektur
            $tableArray[$zeileSumme2]['T_irrMT']                      = ''; // Spalte T // Irradiation mit FT Korrektur (haben wir noch nicht)
            $tableArray[$zeileSumme2]['U_prReal_withRisk']            = 0; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme2]['V_eGrid_withRisk']             = 0; // Spalte V //
            $tableArray[$zeileSumme2]['W_yield_guaranteed_exp']       += ($hasMonthData) ? $tableArray[$n]['W_yield_guaranteed_exp'] : 0; // Spalte W //
            $tableArray[$zeileSumme2]['X_eGridMinuseGridGuar']        = 0; // Spalte X // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme2]['Y_prRealMinusPrGuraReduction'] = 0; // Spalte Y // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme2]['Z_yieldEGridForecast']         = 0; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme2]['AA_yieldEGridMinusGuranteed']   += ($hasMonthData) ? $tableArray[$n]['AA_yieldEGridMinusGuranteed'] : 0; // Spalte AA
            $tableArray[$zeileSumme2]['AB_prRealMinusPrGura']          = $tableArray[$zeileSumme2]['Q_prReal_prProg'] - $tableArray[$zeileSumme2]['H_prGuarantie']; // Spalte AB
            $tableArray[$zeileSumme2]['AC_eGridDivExpected']           = 0; // Spalte AC // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme2]['style']                         = "strong";

            $tableArray[$zeileSumme3]['B_month']                      = "Forecast period (after current month)"; // Spalte B
            $tableArray[$zeileSumme3]['C_days']                       += ($hasMonthData) ? 0 : $days; // Spalte C
            $tableArray[$zeileSumme3]['D_irrDesign']                  += ($hasMonthData) ? 0 : $tableArray[$n]['D_irrDesign']; // Spalte D // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$zeileSumme3]['E_yieldDesign']                += ($hasMonthData) ? 0 : $tableArray[$n]['E_yieldDesign']; // Spalte E // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$zeileSumme3]['F_specificYieldDesign']        += ($hasMonthData) ? 0 : $tableArray[$n]['F_specificYieldDesign'];  // Spalte F // berechnet aus IrrDesign un der Anlagenleistung (kwPeak)
            $tableArray[$zeileSumme3]['G_prDesign']                   =  ($hasMonthData) ? 0 : $tableArray[$zeileSumme3]['F_specificYieldDesign'] / $tableArray[$zeileSumme3]['D_irrDesign'] * 100; // Spalte G // kommt aus der Tabelle PvSyst Werte Design
            $tableArray[$zeileSumme3]['H_prGuarantie']                =  $tableArray[$zeileSumme3]['G_prDesign'] - $anlage->getTransformerTee() - $anlage->getGuaranteeTee(); // Spalte H
            $tableArray[$zeileSumme3]['I_theorYieldDesign']           += ($hasMonthData) ? 0 : $tableArray[$zeileSumme1]['I_theorYieldDesign']; // Spalte I
            $tableArray[$zeileSumme3]['J_theorYieldMTDesign']         = ''; // Spalte J
            $tableArray[$zeileSumme3]['K_irrFTDesign']                = ''; // Spalte K
            $tableArray[$zeileSumme3]['L_irr']                        += ($hasMonthData) ? 0 : $tableArray[$n]['L_irr']; // Spalte L // Irradiation
            $tableArray[$zeileSumme3]['M_eGridYield']                 += ($hasMonthData) ? 0 : $tableArray[$n]['M_eGridYield']; // Spalte M // eGrid gemessen (je nach Konfiguration der Anlage aus dem Feld e_z_evu oder aus den Tageswerten der externen Grid Messung
            $tableArray[$zeileSumme3]['N_specificYield']              = $tableArray[$zeileSumme3]['M_eGridYield'] / $anlage->getKwPeak(); // Spalte N
            $tableArray[$zeileSumme3]['O_availability']               = ''; // Spalte O
            $tableArray[$zeileSumme3]['P_part']                       = 0; // Spalte P // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['Q_prReal_prProg']              = ($hasMonthData) ? 0 : $tableArray[$zeileSumme3]['N_specificYield'] / $tableArray[$zeileSumme3]['L_irr'] * 100; // Spalte Q // PR Real bzw PR prognostiziert, wenn noch kein PR Real vorhanden // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['R_theorYield']                 += ($hasMonthData) ? 0 : $tableArray[$n]['R_theorYield']; // Spalte R // theoretical Energy ohne FT Korrektur
            $tableArray[$zeileSumme3]['S_theorYieldMT']               += $prArray['powerTheoTempCorr']; // Spalte S // theoretical Energy mit FT Korrektur
            $tableArray[$zeileSumme3]['T_irrMT']                      = ''; // Spalte T // Irradiation mit FT Korrektur (haben wir noch nicht)
            $tableArray[$zeileSumme3]['U_prReal_withRisk']            = 0; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['V_eGrid_withRisk']             = 0; // Spalte V //
            $tableArray[$zeileSumme3]['W_yield_guaranteed_exp']       += ($hasMonthData) ? 0 : $tableArray[$n]['W_yield_guaranteed_exp']; // Spalte W //
            $tableArray[$zeileSumme3]['X_eGridMinuseGridGuar']        = 0; // Spalte X // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['Y_prRealMinusPrGuraReduction'] = 0; // Spalte Y // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['Z_yieldEGridForecast']         = 0; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['AA_yieldEGridMinusGuranteed']  += ($hasMonthData) ? 0 : $tableArray[$n]['AA_yieldEGridMinusGuranteed']; // Spalte AA // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['AB_prRealMinusPrGura']         = $tableArray[$zeileSumme3]['Q_prReal_prProg'] - $tableArray[$zeileSumme3]['H_prGuarantie']; // Spalte AB
            $tableArray[$zeileSumme3]['AC_eGridDivExpected']          = 0; // Spalte AC // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['style']                         = "strong";

            $month++;
        }

        /////////////////////////////
        /// Runde 2
        /////////////////////////////

        $riskForcastPROffset    = $tableArray[$zeileSumme2]['Q_prReal_prProg'] - $tableArray[$zeileSumme2]['G_prDesign'];
        $riskForcastYield1      = $tableArray[$zeileSumme2]['Q_prReal_prProg'] / $tableArray[$zeileSumme2]['G_prDesign'];
        $riskForcastYield2      = $tableArray[$zeileSumme2]['M_eGridYield'] / $tableArray[$zeileSumme2]['E_yieldDesign'];;
        #dump("RiskOffset: $riskForcastPROffset | RiskForcast 1: $riskForcastYield1 | RiskForcast 2: $riskForcastYield2");

        $month = $startMonth;
        $year = $startYear;

        for ($n = 1; $n <= $anzahlMonate; $n++) {
            if ($month >= 13) {
                $month = 1;
                $year++;
            }

            $daysInMonth = (int)date('t', strtotime("$year-$month-01"));
            $from_local = date_create(date('Y-m-d 00:00', strtotime("$year-$month-01")));
            $to_local = date_create(date('Y-m-d 23:59', strtotime("$year-$month-$daysInMonth")));
            $hasMonthData = $to_local < $date; // Wenn das Datum in $to_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true

            $tableArray[$n]['P_part']                                 = $tableArray[$n]['L_irr'] / $tableArray[$zeileSumme1]['L_irr'] * 100; // Spalte P //
            $tableArray[$n]['U_prReal_withRisk']                      = ($hasMonthData) ? $tableArray[$n]['Q_prReal_prProg'] : $tableArray[$n]['Q_prReal_prProg'] + $riskForcastPROffset; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$n]['V_eGrid_withRisk']                       = ($hasMonthData) ? $tableArray[$n]['M_eGridYield'] : ($tableArray[$n]['U_prReal_withRisk'] / 100) * $tableArray[$n]['L_irr'] * $anlage->getKwPeak(); // Spalte V //
            $tableArray[$n]['X_eGridMinuseGridGuar']                  = $tableArray[$n]['V_eGrid_withRisk'] - $tableArray[$n]['W_yield_guaranteed_exp']; // Spalte X
            $tableArray[$n]['Y_prRealMinusPrGuraReduction']           = $tableArray[$n]['U_prReal_withRisk'] - $tableArray[$n]['H_prGuarantie']; // Spalte Y
            $tableArray[$n]['Z_yieldEGridForecast']                   = ($hasMonthData) ? $tableArray[$n]['M_eGridYield'] : $tableArray[$n]['M_eGridYield'] * $riskForcastYield1; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$n]['AC_eGridDivExpected']                    = ($tableArray[$n]['V_eGrid_withRisk'] - $tableArray[$n]['W_yield_guaranteed_exp']) / $tableArray[$n]['W_yield_guaranteed_exp'] * 100; // Spalte AC // muss in Runde 2 Berechnet werden

            $tableArray[$zeileSumme1]['P_part']                       += $tableArray[$n]['P_part']; // Spalte P
            $tableArray[$zeileSumme1]['U_prReal_withRisk']            += ($tableArray[$n]['U_prReal_withRisk'] * $tableArray[$n]['P_part']) / 100; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['V_eGrid_withRisk']             += $tableArray[$n]['V_eGrid_withRisk']; // Spalte V //
            $tableArray[$zeileSumme1]['X_eGridMinuseGridGuar']        += $tableArray[$n]['X_eGridMinuseGridGuar']; // Spalte X
            $tableArray[$zeileSumme1]['Y_prRealMinusPrGuraReduction'] = $tableArray[$zeileSumme1]['U_prReal_withRisk'] - $tableArray[$zeileSumme1]['H_prGuarantie']; // Spalte Y
            $tableArray[$zeileSumme1]['Z_yieldEGridForecast']         += $tableArray[$n]['Z_yieldEGridForecast']; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['AC_eGridDivExpected']          = ($tableArray[$zeileSumme1]['V_eGrid_withRisk'] - $tableArray[$zeileSumme1]['W_yield_guaranteed_exp']) /$tableArray[$zeileSumme1]['W_yield_guaranteed_exp'] * 100; // Spalte AC // muss in Runde 2 Berechnet werden


            $tableArray[$zeileSumme2]['U_prReal_withRisk']            = $tableArray[$zeileSumme2]['Q_prReal_prProg']; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme2]['V_eGrid_withRisk']             += ($hasMonthData) ? $tableArray[$n]['V_eGrid_withRisk'] : 0; // Spalte V //
            $tableArray[$zeileSumme2]['X_eGridMinuseGridGuar']        += ($hasMonthData) ? $tableArray[$n]['X_eGridMinuseGridGuar'] : 0; // Spalte X
            $tableArray[$zeileSumme2]['Y_prRealMinusPrGuraReduction'] = $tableArray[$zeileSumme2]['U_prReal_withRisk'] - $tableArray[$zeileSumme2]['H_prGuarantie']; // Spalte Y
            $tableArray[$zeileSumme2]['Z_yieldEGridForecast']         += ($hasMonthData) ? $tableArray[$n]['Z_yieldEGridForecast'] : 0; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme2]['AC_eGridDivExpected']          = ($tableArray[$zeileSumme2]['V_eGrid_withRisk'] - $tableArray[$zeileSumme2]['W_yield_guaranteed_exp']) /$tableArray[$zeileSumme2]['W_yield_guaranteed_exp'] * 100; // Spalte AC // muss in Runde 2 Berechnet werden

            $tableArray[$zeileSumme3]['U_prReal_withRisk']            = $tableArray[$zeileSumme3]['Q_prReal_prProg'] + $riskForcastPROffset; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['V_eGrid_withRisk']             += ($hasMonthData) ? 0 : $tableArray[$n]['V_eGrid_withRisk']; // Spalte V //
            $tableArray[$zeileSumme3]['X_eGridMinuseGridGuar']        += ($hasMonthData) ? 0 : $tableArray[$n]['X_eGridMinuseGridGuar']; // Spalte X
            $tableArray[$zeileSumme3]['Y_prRealMinusPrGuraReduction'] = $tableArray[$zeileSumme3]['U_prReal_withRisk'] - $tableArray[$zeileSumme3]['H_prGuarantie']; // Spalte Y
            $tableArray[$zeileSumme3]['Z_yieldEGridForecast']         += ($hasMonthData) ? 0 : $tableArray[$n]['Z_yieldEGridForecast']; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['AC_eGridDivExpected']          = ($tableArray[$zeileSumme3]['V_eGrid_withRisk'] - $tableArray[$zeileSumme3]['W_yield_guaranteed_exp']) /$tableArray[$zeileSumme3]['W_yield_guaranteed_exp'] * 100; // Spalte AC // muss in Runde 2 Berechnet werden

            $month++;
        }
        ksort($tableArray);

        return $tableArray;
    }

    public function forcastTable(Anlage $anlage, array $monthTable, ?DateTime  $date = null): array
    {
        if ($date === null) $date = new DateTime();

        $result = [];
        $zeileSumme1 = count($monthTable) - 2;
        $zeileSumme2 = count($monthTable) - 1;
        $zeileSumme3 = count($monthTable) - 0;

        $b8 = $monthTable[$zeileSumme1]['E_yieldDesign'];
        $b9 = $monthTable[$zeileSumme1]['W_yield_guaranteed_exp'];
        $b10 = $monthTable[$zeileSumme1]['V_eGrid_withRisk'];
        $b11 = $monthTable[$zeileSumme1]['V_eGrid_withRisk'] - $monthTable[$zeileSumme1]['W_yield_guaranteed_exp'];
        $b12 = $monthTable[$zeileSumme2]['O_availability'] / 100;
        $pldForcast = (($b9 - ($b10 / $b12)) / $b8) * 100 * $anlage->getPldYield();

        $g8 = $monthTable[$zeileSumme2]['E_yieldDesign'];
        $g9 = $monthTable[$zeileSumme2]['W_yield_guaranteed_exp'];
        $g10 = $monthTable[$zeileSumme2]['V_eGrid_withRisk'];
        $g11 = $monthTable[$zeileSumme2]['V_eGrid_withRisk'] - $monthTable[$zeileSumme2]['W_yield_guaranteed_exp'];
        $g12 = $b12;
        $pldReal    = (($g9 - ($g10 / $g12)) / $g8) * 100 * $anlage->getPldYield();

        $result['forcast']                      = "Forcast " . $anlage->getFacDateStart()->format('M y') . " - " . $anlage->getFacDate()->format('M y');
        $result['expected_energy_forecast']     = $monthTable[$zeileSumme1]['E_yieldDesign']; // B8
        $result['guaranteed_energy_forecast']   = $monthTable[$zeileSumme1]['W_yield_guaranteed_exp']; // B9
        $result['measured_energy_forecast']     = $monthTable[$zeileSumme1]['V_eGrid_withRisk']; // B10
        $result['difference_calc_forecast']     = $monthTable[$zeileSumme1]['V_eGrid_withRisk'] - $monthTable[$zeileSumme1]['W_yield_guaranteed_exp']; // B11
        $result['pa_forecast']                  = $monthTable[$zeileSumme2]['O_availability']; // B12
        $result['pld_forecast']                 = $pldForcast;
        $result['percent_diff_calc_forecast']   = ($monthTable[$zeileSumme1]['V_eGrid_withRisk'] - $monthTable[$zeileSumme1]['W_yield_guaranteed_exp']) * 100 / $monthTable[$zeileSumme1]['W_yield_guaranteed_exp'];
        $result['ratio_forecast']               = $monthTable[$zeileSumme1]['V_eGrid_withRisk'] * 100 / $monthTable[$zeileSumme1]['W_yield_guaranteed_exp'];

        $result['real']                         = "Real " . $anlage->getFacDateStart()->format('M y') . " - " . $date->format('M y');
        $result['expected_energy_real']         = $monthTable[$zeileSumme2]['E_yieldDesign'];
        $result['guaranteed_energy_real']       = $monthTable[$zeileSumme2]['W_yield_guaranteed_exp'];
        $result['measured_energy_real']         = $monthTable[$zeileSumme2]['V_eGrid_withRisk'];
        $result['difference_calc_real']         = $monthTable[$zeileSumme2]['V_eGrid_withRisk'] - $monthTable[$zeileSumme2]['W_yield_guaranteed_exp'];
        $result['pa_real']                      = $monthTable[$zeileSumme2]['O_availability'];
        $result['pld_real']                     = $pldReal;
        $result['percent_diff_calc_real']       = ($monthTable[$zeileSumme2]['V_eGrid_withRisk'] - $monthTable[$zeileSumme2]['W_yield_guaranteed_exp']) * 100 / $monthTable[$zeileSumme2]['W_yield_guaranteed_exp'];
        $result['ratio_real']                   = $monthTable[$zeileSumme2]['V_eGrid_withRisk'] * 100 / $monthTable[$zeileSumme2]['W_yield_guaranteed_exp'];

        return $result;
    }

    public function chartYieldPercenDiff(Anlage $anlage, array $monthTable, ?DateTime  $date = null): string
    {
        if ($date === null) $date = new DateTime();
        $anzahlMonate = ((int)$anlage->getEpcReportEnd()->format('Y') - (int)$anlage->getEpcReportStart()->format('Y')) * 12 + ((int)$anlage->getEpcReportEnd()->format('m') - (int)$anlage->getEpcReportStart()->format('m')) + 1;
        $xAxis = $yAxis = [];

        for ($n = 1; $n <= $anzahlMonate; $n++){
            $xAxis[] = $monthTable[$n]['B_month'];
            $yAxis[] = round($monthTable[$n]['AC_eGridDivExpected'],2);
        }
        $chart = new ECharts();
        $chart->xAxis[] = [
            'type'      => 'category',
            'data'      => $xAxis,
            'axisLabel' =>  [
                'rotate'    => 30,
            ],
        ];
        $chart->yAxis[] = [
            'type'      => 'value',
            'splitLine' => [
                'lineStyle' => [
                    'type'      => 'dashed',
                ],
            ],
            'axisLabel' =>  [
                'formatter'     => '{value} %',
                'align'         => 'right',
            ],
        ];
        $chart->series[] = [
            'type'      => 'bar',
            'data'      => $yAxis,
            'visualMap' => false,
            'label'     => [
                'show'      => true,
                'position'  => 'inside',
                'formatter' => '{c} %',
                'rotate'    => 90,
            ],

        ];

        $options = [
            'color'     => ['#3366CC'],
            'grid'      => [
                'height'    => '70%',
                'top'       => 50,
                'left'      => 50,
                'width'     => '95%',
            ],
        ];
        $chart->setOption($options);

        return $chart->render('chartYieldPercenDiff');
    }
}