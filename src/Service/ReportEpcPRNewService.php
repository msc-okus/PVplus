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

class ReportEpcPRNewService
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

        $startYear = $anlage->getEpcReportStart()->format('Y');
        $endYear = $anlage->getEpcReportEnd()->format('Y');
        $startMonth = (int)$anlage->getFacDateStart()->format('m') ;
        $yearCount = $endYear - $startYear;
        $currentMonth = (int)$date->format('m');//(int)date('m');
        $currentYear = (int)$date->format('Y');//(int)date('Y');
        if ($currentMonth === 1) {
            // Jahresanfang / aktuelles Datum ist 'Januar'
            $reportMonth = 12;
            $reportYear = $currentYear - 1;
        } else {
            $reportMonth = $currentMonth - 1;
            $reportYear = $currentYear;
        }
        $reportMonth = $currentMonth;
        $reportYear = $currentYear;
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
            $hasMonthData = $from_local <= $date; // Wenn das Datum in $from_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true
            $isCurrentMonth = $to_local->format('Y') == $currentYear && $to_local->format('m') == $currentMonth;


            $monthlyRecalculatedData = $this->monthlyDataRepo->findOneBy(['anlage' => $anlage, 'year' => $year, 'month' => $month]);

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

            $tableArray[$n]['B_month']                                = date('m / Y', strtotime("$year-$month-1"));
            $tableArray[$n]['C_days_month']                           = $daysInMonth;
            // DesignValues
            $tableArray[$n]['D_days_fac']                             = $days;
            $tableArray[$n]['E_IrrDesign']                            = $pvSystData[$month - 1]['irrDesign'] * $factor;
            $tableArray[$n]['F_refYieldDesign']                       = $tableArray[$n]['E_IrrDesign'];
            $tableArray[$n]['G_theoEnergyDesign']                     = $tableArray[$n]['F_refYieldDesign'] * $anlage->getKwPeakPvSyst();
            $tableArray[$n]['H_eGridDesign']                          = $pvSystData[$month - 1]['ertragDesign'] * $factor;
            $tableArray[$n]['I_specificYieldDesign']                  = $tableArray[$n]['H_eGridDesign'] / $anlage->getKwPeakPvSyst();
            $tableArray[$n]['J_prDesign']                             = $tableArray[$n]['H_eGridDesign'] / ($tableArray[$n]['E_IrrDesign'] * $anlage->getKwPeakPvSyst()) * 100;
            $tableArray[$n]['K_tempAmbDesign']                        = $pvSystData[$month - 1]['tempAmbDesign'];
            $tableArray[$n]['L_tempAmbWeightedDesign']                = $pvSystData[$month - 1]['tempAmbWeightedDesign'];
            $tableArray[$n]['M_tempCompFactDesign']                   = (1 + ($tableArray[$n]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrA() / 100);
            $tableArray[$n]['N_effRefYieldDesign']                    = $tableArray[$n]['F_refYieldDesign'] * $tableArray[$n]['F_refYieldDesign'];
            $tableArray[$n]['O_effTheoEnergyDesign']                  = $tableArray[$n]['N_effRefYieldDesign'] * $anlage->getKwPeakPvSyst();
            $tableArray[$n]['P_prMonthDesign']                        =
            $tableArray[$n]['Q_prGuarDesign']                         =
            $tableArray[$n]['R_specificYieldGuarDesign']              =
            $tableArray[$n]['S_eGridGuarDesign']                      =
            // MeasuermentValues
            $tableArray[$n]['T_irr']                                  =
            $tableArray[$n]['U_refYield']                             =
            $tableArray[$n]['V_theoEnergy']                           =
            $tableArray[$n]['W_eGrid']                                =
            $tableArray[$n]['X_specificYield']                        =
            $tableArray[$n]['Y_pr']                                   =
            $tableArray[$n]['Z_tCellAvgWeighted']                     =
            $tableArray[$n]['AA_tCompensationFactor']                 =
            $tableArray[$n]['AB_effRefYield']                         =
            $tableArray[$n]['AC_effTheoEnergy']                       =
            $tableArray[$n]['AD_prMonth']                             =
            $tableArray[$n]['AE_ratio']                               =
            $tableArray[$n]['AF_ratioFT']                             =
            $tableArray[$n]['AG_epcPA']                               =
            //Analysis
            $tableArray[$n]['AH_prForecast']                          =
            $tableArray[$n]['AI_eGridForecast']                       =
            $tableArray[$n]['AJ_specificYieldForecast']               =
            $tableArray[$n]['AK_absDiffPrRealGuarForecast']           =
            $tableArray[$n]['AL_relDiffPrRealGuarForecast']           =
            // only for internal use
            $tableArray[$n]['current_month']                           = ($isCurrentMonth) ? -1 : 0;
            $tableArray[$n]['style']                                   = "";




            $month++;
        }

        /////////////////////////////
        /// Runde 2
        /////////////////////////////

        $riskForcastPROffset    = $tableArray[$zeileSumme2]['Q_prReal_prProg'] - $tableArray[$zeileSumme2]['G_prDesign'];
        $riskForcastYield1      = $tableArray[$zeileSumme2]['Q_prReal_prProg'] / $tableArray[$zeileSumme2]['G_prDesign'];
        $riskForcastYield2      = $tableArray[$zeileSumme2]['M_eGridYield'] / $tableArray[$zeileSumme2]['E_yieldDesign'];

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
            $hasMonthData = $from_local <= $date; // Wenn das Datum in $from_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true

            $tableArray[$n]['P_part']                                 = $tableArray[$n]['L_irr'] / $tableArray[$zeileSumme1]['L_irr'] * 100; // Spalte P //
            $tableArray[$n]['U_prReal_withRisk']                      = ($hasMonthData) ? $tableArray[$n]['Q_prReal_prProg'] : $tableArray[$n]['Q_prReal_prProg'] + $riskForcastPROffset; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$n]['V_eGrid_withRisk']                       = ($hasMonthData) ? $tableArray[$n]['M_eGridYield'] : ($tableArray[$n]['U_prReal_withRisk'] / 100) * $tableArray[$n]['L_irr'] * $anlage->getKwPeak(); // Spalte V //
            $tableArray[$n]['X_eGridMinuseGridGuar']                  = $tableArray[$n]['V_eGrid_withRisk'] - $tableArray[$n]['W_yield_guaranteed_exp']; // Spalte X
            $tableArray[$n]['Y_prRealMinusPrGuraReduction']           = $tableArray[$n]['U_prReal_withRisk'] - $tableArray[$n]['H_prGuarantie']; // Spalte Y
            $tableArray[$n]['Z_yieldEGridForecast']                   = ($hasMonthData) ? $tableArray[$n]['M_eGridYield'] : $tableArray[$n]['M_eGridYield'] * $riskForcastYield1; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$n]['AC_eGridDivExpected']                    = ($tableArray[$n]['W_yield_guaranteed_exp'] > 0) ? ($tableArray[$n]['V_eGrid_withRisk'] - $tableArray[$n]['W_yield_guaranteed_exp']) / $tableArray[$n]['W_yield_guaranteed_exp'] * 100 : 0; // Spalte AC // muss in Runde 2 Berechnet werden

            $tableArray[$zeileSumme1]['P_part']                       += $tableArray[$n]['P_part']; // Spalte P
            #$tableArray[$zeileSumme1]['Q_prReal_prProg']              = $this->PRCalulation->calcPrByValues($anlage, $tableArray[$zeileSumme1]['L_irr'], $tableArray[$zeileSumme1]['N_specificYield'], $tableArray[$zeileSumme1]['M_eGridYield'], $tableArray[$zeileSumme1]['S_theorYieldMT'], $tableArray[$zeileSumme2]['O_availability']); // Spalte Q // PR Real bzw PR prognostiziert, wenn noch kein PR Real vorhanden
            $tableArray[$zeileSumme1]['U_prReal_withRisk']            += ($tableArray[$n]['U_prReal_withRisk'] * $tableArray[$n]['P_part']) / 100; // Spalte U // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['V_eGrid_withRisk']             += $tableArray[$n]['V_eGrid_withRisk']; // Spalte V //
            $tableArray[$zeileSumme1]['X_eGridMinuseGridGuar']        += $tableArray[$n]['X_eGridMinuseGridGuar']; // Spalte X
            $tableArray[$zeileSumme1]['Y_prRealMinusPrGuraReduction'] = round($tableArray[$zeileSumme1]['U_prReal_withRisk'],3) - round($tableArray[$zeileSumme1]['H_prGuarantie'],3); // Spalte Y
            $tableArray[$zeileSumme1]['Z_yieldEGridForecast']         += $tableArray[$n]['Z_yieldEGridForecast']; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme1]['AC_eGridDivExpected']          = ($tableArray[$zeileSumme1]['V_eGrid_withRisk'] - $tableArray[$zeileSumme1]['W_yield_guaranteed_exp']) /$tableArray[$zeileSumme1]['W_yield_guaranteed_exp'] * 100; // Spalte AC // muss in Runde 2 Berechnet werden


            $tableArray[$zeileSumme2]['U_prReal_withRisk']            = $tableArray[$zeileSumme2]['Q_prReal_prProg']; // Spalte U // muss in Runde 2 Berechnet werden
            #$tableArray[$zeileSumme2]['Q_prReal_prProg']              = $this->PRCalulation->calcPrByValues($anlage, $tableArray[$zeileSumme2]['L_irr'], $tableArray[$zeileSumme2]['N_specificYield'], $tableArray[$zeileSumme2]['M_eGridYield'], $tableArray[$zeileSumme2]['S_theorYieldMT'], $tableArray[$zeileSumme2]['O_availability']); // Spalte Q // PR Real bzw PR prognostiziert, wenn noch kein PR Real vorhanden
            $tableArray[$zeileSumme2]['V_eGrid_withRisk']             += ($hasMonthData) ? $tableArray[$n]['V_eGrid_withRisk'] : 0; // Spalte V //
            $tableArray[$zeileSumme2]['X_eGridMinuseGridGuar']        += ($hasMonthData) ? $tableArray[$n]['X_eGridMinuseGridGuar'] : 0; // Spalte X
            $tableArray[$zeileSumme2]['Y_prRealMinusPrGuraReduction'] = round($tableArray[$zeileSumme2]['U_prReal_withRisk'],3) - round($tableArray[$zeileSumme2]['H_prGuarantie'],3); // Spalte Y
            $tableArray[$zeileSumme2]['Z_yieldEGridForecast']         += ($hasMonthData) ? $tableArray[$n]['Z_yieldEGridForecast'] : 0; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme2]['AC_eGridDivExpected']          = ($tableArray[$zeileSumme2]['W_yield_guaranteed_exp'] > 0) ? ($tableArray[$zeileSumme2]['V_eGrid_withRisk'] - $tableArray[$zeileSumme2]['W_yield_guaranteed_exp']) / $tableArray[$zeileSumme2]['W_yield_guaranteed_exp'] * 100 : 0; // Spalte AC // muss in Runde 2 Berechnet werden

            $tableArray[$zeileSumme3]['U_prReal_withRisk']            = $tableArray[$zeileSumme3]['Q_prReal_prProg'] + $riskForcastPROffset; // Spalte U // muss in Runde 2 Berechnet werden
            #$tableArray[$zeileSumme3]['Q_prReal_prProg']              = $this->PRCalulation->calcPrByValues($anlage, $tableArray[$zeileSumme3]['L_irr'], $tableArray[$zeileSumme3]['N_specificYield'], $tableArray[$zeileSumme3]['M_eGridYield'], $tableArray[$zeileSumme3]['S_theorYieldMT'], $tableArray[$zeileSumme2]['O_availability']); // Spalte Q // PR Real bzw PR prognostiziert, wenn noch kein PR Real vorhanden
            $tableArray[$zeileSumme3]['V_eGrid_withRisk']             += ($hasMonthData) ? 0 : $tableArray[$n]['V_eGrid_withRisk']; // Spalte V //
            $tableArray[$zeileSumme3]['X_eGridMinuseGridGuar']        += ($hasMonthData) ? 0 : $tableArray[$n]['X_eGridMinuseGridGuar']; // Spalte X
            $tableArray[$zeileSumme3]['Y_prRealMinusPrGuraReduction'] = round($tableArray[$zeileSumme3]['U_prReal_withRisk'],3) - round($tableArray[$zeileSumme3]['H_prGuarantie'],3); // Spalte Y
            $tableArray[$zeileSumme3]['Z_yieldEGridForecast']         += ($hasMonthData) ? 0 : $tableArray[$n]['Z_yieldEGridForecast']; // Spalte Z // muss in Runde 2 Berechnet werden
            $tableArray[$zeileSumme3]['AC_eGridDivExpected']          = ($tableArray[$zeileSumme3]['W_yield_guaranteed_exp'] > 0) ? ($tableArray[$zeileSumme3]['V_eGrid_withRisk'] - $tableArray[$zeileSumme3]['W_yield_guaranteed_exp']) / $tableArray[$zeileSumme3]['W_yield_guaranteed_exp'] * 100 : 0; // Spalte AC // muss in Runde 2 Berechnet werden

            $month++;
        }
        ksort($tableArray);

        return $tableArray;
    }
}