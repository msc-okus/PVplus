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
        $anzahlMonate = ((int)$anlage->getEpcReportEnd()->format('Y') - (int)$anlage->getEpcReportStart()->format('Y')) * 12 + ((int)$anlage->getEpcReportEnd()->format('m') - (int)$anlage->getEpcReportStart()->format('m')) + 2;
        $zeileSumme1 = $anzahlMonate + 1;
        $zeileSumme2 = $anzahlMonate + 2;
        $zeileSumme3 = $anzahlMonate + 3;
        $zeileSumme4 = $anzahlMonate + 4;
        $zeileSumme5 = $anzahlMonate + 5;
        $zeileSumme6 = $anzahlMonate + 6;

        $startYear      = $anlage->getEpcReportStart()->format('Y');
        $endYear        = $anlage->getEpcReportEnd()->format('Y');
        $startMonth     = (int)$anlage->getFacDateStart()->format('m') ;
        $yearCount      = $endYear - $startYear;
        $currentMonth   = (int)$date->format('m');//(int)date('m');
        $currentYear    = (int)$date->format('Y');//(int)date('Y');
        if ($currentMonth === 1) {
            // Jahresanfang / aktuelles Datum ist 'Januar'
            $reportMonth = 12;
            $reportYear = $currentYear - 1;
        } else {
            $reportMonth = $currentMonth - 1;
            $reportYear = $currentYear;
        }
        $reportMonth        = $currentMonth;
        $reportYear         = $currentYear;
        $daysInReportMonth  = (int)date('t', strtotime("$reportYear-$reportMonth-01"));
        $facStartMonth      = (int)$anlage->getFacDateStart()->format('m');
        $facStartDay        = $anlage->getFacDateStart()->format('d');
        $facEndMonth        = $anlage->getFacDate()->format('m');
        $facEndDay          = $anlage->getFacDate()->format('d');
        $isYear1            = true;
        $isYear2            = false;
        $lastMonthYear      = false;

        $month = $startMonth;
        $year = $startYear;

        $daysInStartMonth = (int)$anlage->getFacDateStart()->format('j');
        $daysInEndMonth = (int)$anlage->getFacDate()->format('j');

        $endDateCurrentReportMonth = date_create("$reportYear-$reportMonth-$daysInReportMonth");

        if (true) { //prüfe auf PVSYST verfügbar
            $pvSystData = $anlage->getPvSystMonthsArray();
        }

        #$availabilitySummeZeil2 = $this->availabilityService->calcAvailability($anlage, $anlage->getFacDateStart(), $endDateCurrentReportMonth);

        $prPVSyst           = 88.43; // muss aus Anlage kommen
        $prFAC              = 84.23; // muss aus Anlage kommen
        $deductionTransform = 0; // wo muss das herkommen ????
        $deductionRisk      = 100 * (1 - $prFAC / $prPVSyst);
        $deductionOverall   = 100 - (100 - $deductionTransform) * (100 - $deductionRisk) / 100;

        dump("Deduction Overall: $deductionOverall | ");
        /////////////////////////////
        /// Runde 1
        /////////////////////////////

        for ($n = 1; $n <= $anzahlMonate; $n++) {
            if ($month > 12) {
                $month = 1;
                $year++;
            }

            $daysInMonth = (int)date('t', strtotime("$year-$month-01"));
            $from_local = date_create(date('Y-m-d 00:00', strtotime("$year-$month-01")));
            $to_local = date_create(date('Y-m-d 23:59', strtotime("$year-$month-$daysInMonth")));
            $hasMonthData = $from_local <= $date; // Wenn das Datum in $from_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true
            $isCurrentMonth = $to_local->format('Y') == $currentYear && $to_local->format('m') == $currentMonth;
            $rollingPeriod = true;

            $monthlyRecalculatedData = $this->monthlyDataRepo->findOneBy(['anlage' => $anlage, 'year' => $year, 'month' => $month]);

            if ($n === 1 || $n === 14) { // 1. und 14. Durchlauf Rumpf Monat Anfang
                $month = $startMonth;
                $from_local = date_create(date('Y-m-d', strtotime("$year-$month-$facStartDay 00:00")));
                $days = $daysInMonth - $daysInStartMonth + 1;
                $factor = $days / $daysInMonth;
            } elseif ($n === 13 || $n === 26) { // 13. und 26. Durchlauf Rumpf Monat Ende
                $month = $startMonth;
                $days = $daysInEndMonth;
                $factor = $days / $daysInMonth;
                $to_local = date_create(date('Y-m-d', strtotime("$year-$startMonth-$facEndDay 23:59")));
            } else {
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
            $tableArray[$n]['M_tempCompFactDesign']                   = (1 + ($tableArray[$n]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
            $tableArray[$n]['N_effRefYieldDesign']                    = $tableArray[$n]['F_refYieldDesign'] * $tableArray[$n]['M_tempCompFactDesign'];
            $tableArray[$n]['O_effTheoEnergyDesign']                  = $tableArray[$n]['N_effRefYieldDesign'] * $anlage->getKwPeakPvSyst();
            $tableArray[$n]['P_prMonthDesign']                        = $tableArray[$n]['I_specificYieldDesign'] / $tableArray[$n]['N_effRefYieldDesign'] * 100;
            $tableArray[$n]['Q_prGuarDesign']                         = $tableArray[$n]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
            $tableArray[$n]['R_specificYieldGuarDesign']              = $tableArray[$n]['Q_prGuarDesign'] / 100 * $tableArray[$n]['N_effRefYieldDesign'];
            $tableArray[$n]['S_eGridGuarDesign']                      = $tableArray[$n]['R_specificYieldGuarDesign'] * $anlage->getKwPeakPvSyst();
            // MeasuermentValues
            $tableArray[$n]['T_irr']                                  = $hasMonthData ? $prArray['irradiation'] : $tableArray[$n]['E_IrrDesign'];
            $tableArray[$n]['U_refYield']                             = $tableArray[$n]['T_irr'];
            $tableArray[$n]['V_theoEnergy']                           = $tableArray[$n]['T_irr'] * $anlage->getPnom();
            $tableArray[$n]['W_eGrid']                                = $hasMonthData ? $eGridReal : $tableArray[$n]['H_eGridDesign'] * $anlage->getPnom() / $anlage->getKwPeakPvSyst();
            $tableArray[$n]['X_specificYield']                        = $tableArray[$n]['W_eGrid'] / $anlage->getPnom();
            $tableArray[$n]['Y_pr']                                   = $tableArray[$n]['X_specificYield'] / $tableArray[$n]['U_refYield'] * 100;
            $tableArray[$n]['Z_tCellAvgWeighted']                     = $hasMonthData ? $prArray['tCellAvgMultiIrr'] / $tableArray[$n]['T_irr'] : $tableArray[$n]['L_tempAmbWeightedDesign'];
            $tableArray[$n]['Z_tCellAvgWeighted']                     = $hasMonthData ? $prArray['tCellAvg'] : $tableArray[$n]['L_tempAmbWeightedDesign'];
            $tableArray[$n]['AA_tCompensationFactor']                 = 0;
            $tableArray[$n]['AB_effRefYield']                         = 0;
            $tableArray[$n]['AC_effTheoEnergy']                       = 0;
            $tableArray[$n]['AC1_theoEnergyMeasured']                 = $hasMonthData ? $prArray['powerTheoTempCorr'] * $factor : 0;
            $tableArray[$n]['AD_prMonth']                             = 0;
            $tableArray[$n]['AE_ratio']                               = 0;
            $tableArray[$n]['AF_ratioFT']                             = 0;
            $tableArray[$n]['AG_epcPA']                               = 0;
            //Analysis
            $tableArray[$n]['AH_prForecast']                          = 0;
            $tableArray[$n]['AI_eGridForecast']                       = 0;
            $tableArray[$n]['AJ_specificYieldForecast']               = 0;
            $tableArray[$n]['AK_absDiffPrRealGuarForecast']           = 0;
            $tableArray[$n]['AL_relDiffPrRealGuarForecast']           = 0;

            // Steuerrung
            $tableArray[$n]['current_month']                           = $isCurrentMonth ? -1 : 0;
            $tableArray[$n]['style']                                   = "";

            ############
            if ($n <= 13) { // Year 1
                $tableArray[$zeileSumme1]['B_month']                    = "Year 1";
                $tableArray[$zeileSumme1]['C_days_month']               = 0;
                // DesignValues
                $tableArray[$zeileSumme1]['D_days_fac']                 += $tableArray[$n]['D_days_fac'];
                $tableArray[$zeileSumme1]['E_IrrDesign']                += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme1]['F_refYieldDesign']           += $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme1]['G_theoEnergyDesign']         += $tableArray[$n]['G_theoEnergyDesign'];
                $tableArray[$zeileSumme1]['H_eGridDesign']              += $tableArray[$n]['H_eGridDesign'];
                $tableArray[$zeileSumme1]['I_specificYieldDesign']      += $tableArray[$n]['I_specificYieldDesign'];
                $tableArray[$zeileSumme1]['J_prDesign']                 = $tableArray[$zeileSumme1]['I_specificYieldDesign'] / $tableArray[$zeileSumme1]['F_refYieldDesign'] * 100;
                $tableArray[$zeileSumme1]['K_tempAmbDesign']            = 0;
                $tableArray[$zeileSumme1]['L_tempAmbWeightedDesign']    = 0;
                $tableArray[$zeileSumme1]['M_tempCompFactDesign']       = 0;
                $k_tempAmbDesign1                                       += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac'] ; ########### in zweiter Runde noch durch summe Tage teilen
                $l_tempAmbWeightedDesign1                               += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign'] ; ########### in zweiter Runde noch durch summe Strahlung teilen
                $tableArray[$zeileSumme1]['N_effRefYieldDesign']        += $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme1]['O_effTheoEnergyDesign']      += $tableArray[$n]['O_effTheoEnergyDesign'];
                $tableArray[$zeileSumme1]['P_prMonthDesign']            = $tableArray[$zeileSumme1]['I_specificYieldDesign'] / $tableArray[$zeileSumme1]['N_effRefYieldDesign'] * 100;
                $tableArray[$zeileSumme1]['Q_prGuarDesign']             = $tableArray[$zeileSumme1]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme1]['R_specificYieldGuarDesign']  += $tableArray[$n]['R_specificYieldGuarDesign'];
                $tableArray[$zeileSumme1]['S_eGridGuarDesign']          += $tableArray[$n]['S_eGridGuarDesign'];
                // MeasuermentValues
                $tableArray[$zeileSumme1]['T_irr']                      += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme1]['U_refYield']                 += $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme1]['V_theoEnergy']               += $tableArray[$n]['V_theoEnergy'];
                $tableArray[$zeileSumme1]['W_eGrid']                    += $tableArray[$n]['W_eGrid'];
                $tableArray[$zeileSumme1]['X_specificYield']            += $tableArray[$n]['X_specificYield'];
                $tableArray[$zeileSumme1]['Y_pr']                       = $tableArray[$zeileSumme1]['X_specificYield'] / $tableArray[$zeileSumme1]['U_refYield'] * 100;
                $tableArray[$zeileSumme1]['Z_tCellAvgWeighted']         = 0;
                $z_tCellAvgWeighted1                                    += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield'] ; ########### in zweiter Runde noch durch summe aus 'U_refYield' teilen
                $tableArray[$zeileSumme1]['AA_tCompensationFactor']         = 0;
                $tableArray[$zeileSumme1]['AB_effRefYield']                 = 0;
                $tableArray[$zeileSumme1]['AC_effTheoEnergy']               = 0;
                $tableArray[$zeileSumme1]['AC1_theoEnergyMeasured']         += $tableArray[$n]['AC1_theoEnergyMeasured'];
                $tableArray[$zeileSumme1]['AD_prMonth']                     = 0;
                $tableArray[$zeileSumme1]['AE_ratio']                       = 0;
                $tableArray[$zeileSumme1]['AF_ratioFT']                     = 0;
                $tableArray[$zeileSumme1]['AG_epcPA']                       = 0;
                //Analysis
                $tableArray[$zeileSumme1]['AH_prForecast']                  = 0;
                $tableArray[$zeileSumme1]['AI_eGridForecast']               = 0;
                $tableArray[$zeileSumme1]['AJ_specificYieldForecast']       = 0;
                $tableArray[$zeileSumme1]['AK_absDiffPrRealGuarForecast']   = 0;
                $tableArray[$zeileSumme1]['AL_relDiffPrRealGuarForecast']   = 0;

                $tableArray[$zeileSumme1]['current_month']              = 0;
                $tableArray[$zeileSumme1]['style']                      = "";
            }

            if ($n >= 14) { // Year 2
                $tableArray[$zeileSumme2]['B_month']                    = "Year 1";
                $tableArray[$zeileSumme2]['C_days_month']               = 0;
                // DesignValues
                $tableArray[$zeileSumme2]['D_days_fac']                 += $tableArray[$n]['D_days_fac'];
                $tableArray[$zeileSumme2]['E_IrrDesign']                += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme2]['F_refYieldDesign']           += $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme2]['G_theoEnergyDesign']         += $tableArray[$n]['G_theoEnergyDesign'];
                $tableArray[$zeileSumme2]['H_eGridDesign']              += $tableArray[$n]['H_eGridDesign'];
                $tableArray[$zeileSumme2]['I_specificYieldDesign']      += $tableArray[$n]['I_specificYieldDesign'];
                $tableArray[$zeileSumme2]['J_prDesign']                 = $tableArray[$zeileSumme2]['I_specificYieldDesign'] / $tableArray[$zeileSumme2]['F_refYieldDesign'] * 100;
                $tableArray[$zeileSumme2]['K_tempAmbDesign']            = 0;
                $tableArray[$zeileSumme2]['L_tempAmbWeightedDesign']    = 0;
                $tableArray[$zeileSumme2]['M_tempCompFactDesign']       = 0;
                $k_tempAmbDesign2                                       += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac'] ; ########### in zweiter Runde noch durch summe Tage teilen
                $l_tempAmbWeightedDesign2                               += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign'] ; ########### in zweiter Runde noch durch summe Strahlung teilen
                $tableArray[$zeileSumme2]['N_effRefYieldDesign']        += $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme2]['O_effTheoEnergyDesign']      += $tableArray[$n]['O_effTheoEnergyDesign'];
                $tableArray[$zeileSumme2]['P_prMonthDesign']            = $tableArray[$zeileSumme2]['I_specificYieldDesign'] / $tableArray[$zeileSumme2]['N_effRefYieldDesign'] * 100;
                $tableArray[$zeileSumme2]['Q_prGuarDesign']             = $tableArray[$zeileSumme2]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme2]['R_specificYieldGuarDesign']  += $tableArray[$n]['R_specificYieldGuarDesign'];
                $tableArray[$zeileSumme2]['S_eGridGuarDesign']          += $tableArray[$n]['S_eGridGuarDesign'];
                // MeasuermentValues
                $tableArray[$zeileSumme2]['T_irr']                      += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme2]['U_refYield']                 += $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme2]['V_theoEnergy']               += $tableArray[$n]['V_theoEnergy'];
                $tableArray[$zeileSumme2]['W_eGrid']                    += $tableArray[$n]['W_eGrid'];
                $tableArray[$zeileSumme2]['X_specificYield']            += $tableArray[$n]['X_specificYield'];
                $tableArray[$zeileSumme2]['Y_pr']                       = $tableArray[$zeileSumme2]['X_specificYield'] / $tableArray[$zeileSumme2]['U_refYield'] * 100;
                $tableArray[$zeileSumme2]['Z_tCellAvgWeighted']         = 0;
                $z_tCellAvgWeighted2                                    += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield'] ; ########### in zweiter Runde noch durch summe aus 'U_refYield' teilen
                $tableArray[$zeileSumme2]['AA_tCompensationFactor']                 = 0;
                $tableArray[$zeileSumme2]['AB_effRefYield']                         = 0;
                $tableArray[$zeileSumme2]['AC_effTheoEnergy']                       = 0;
                $tableArray[$zeileSumme2]['AC1_theoEnergyMeasured']                 += $tableArray[$n]['AC1_theoEnergyMeasured'];
                $tableArray[$zeileSumme2]['AD_prMonth']                             = 0;
                $tableArray[$zeileSumme2]['AE_ratio']                               = 0;
                $tableArray[$zeileSumme2]['AF_ratioFT']                             = 0;
                $tableArray[$zeileSumme2]['AG_epcPA']                               = 0;
                //Analysis
                $tableArray[$zeileSumme2]['AH_prForecast']                          = 0;
                $tableArray[$zeileSumme2]['AI_eGridForecast']                       = 0;
                $tableArray[$zeileSumme2]['AJ_specificYieldForecast']               = 0;
                $tableArray[$zeileSumme2]['AK_absDiffPrRealGuarForecast']           = 0;
                $tableArray[$zeileSumme2]['AL_relDiffPrRealGuarForecast']           = 0;

                $tableArray[$zeileSumme2]['current_month']              = 0;
                $tableArray[$zeileSumme2]['style']                      = "";
            }

            $tableArray[$zeileSumme3]['B_month']                    = "Both Years";
            $tableArray[$zeileSumme3]['C_days_month']               = 0;
            // DesignValues
            $tableArray[$zeileSumme3]['D_days_fac']                 += $tableArray[$n]['D_days_fac'];
            $tableArray[$zeileSumme3]['E_IrrDesign']                += $tableArray[$n]['E_IrrDesign'];
            $tableArray[$zeileSumme3]['F_refYieldDesign']           += $tableArray[$n]['F_refYieldDesign'];
            $tableArray[$zeileSumme3]['G_theoEnergyDesign']         += $tableArray[$n]['G_theoEnergyDesign'];
            $tableArray[$zeileSumme3]['H_eGridDesign']              += $tableArray[$n]['H_eGridDesign'];
            $tableArray[$zeileSumme3]['I_specificYieldDesign']      += $tableArray[$n]['I_specificYieldDesign'];
            $tableArray[$zeileSumme3]['J_prDesign']                 = $tableArray[$zeileSumme3]['I_specificYieldDesign'] / $tableArray[$zeileSumme3]['F_refYieldDesign'] * 100;
            $tableArray[$zeileSumme3]['K_tempAmbDesign']            = 0;
            $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']    = 0;
            $tableArray[$zeileSumme3]['M_tempCompFactDesign']       = 0;
            $k_tempAmbDesign3                                       += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac']; ########### in zweiter Runde noch durch summe Tage teilen
            $l_tempAmbWeightedDesign3                               += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign']; ########### in zweiter Runde noch durch summe Strahlung teilen
            $tableArray[$zeileSumme3]['N_effRefYieldDesign']        += $tableArray[$n]['N_effRefYieldDesign'];
            $tableArray[$zeileSumme3]['O_effTheoEnergyDesign']      += $tableArray[$n]['O_effTheoEnergyDesign'];
            $tableArray[$zeileSumme3]['P_prMonthDesign']            = $tableArray[$zeileSumme3]['I_specificYieldDesign'] / $tableArray[$zeileSumme3]['N_effRefYieldDesign'] * 100;
            $tableArray[$zeileSumme3]['Q_prGuarDesign']             = $tableArray[$zeileSumme3]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
            $tableArray[$zeileSumme3]['R_specificYieldGuarDesign']  += $tableArray[$n]['R_specificYieldGuarDesign'];
            $tableArray[$zeileSumme3]['S_eGridGuarDesign']          += $tableArray[$n]['S_eGridGuarDesign'];
            // MeasuermentValues
            $tableArray[$zeileSumme3]['T_irr']                      += $tableArray[$n]['T_irr'];
            $tableArray[$zeileSumme3]['U_refYield']                 += $tableArray[$n]['U_refYield'];
            $tableArray[$zeileSumme3]['V_theoEnergy']               += $tableArray[$n]['V_theoEnergy'];
            $tableArray[$zeileSumme3]['W_eGrid']                    += $tableArray[$n]['W_eGrid'];
            $tableArray[$zeileSumme3]['X_specificYield']            += $tableArray[$n]['X_specificYield'];
            $tableArray[$zeileSumme3]['Y_pr']                       = $tableArray[$zeileSumme3]['X_specificYield'] / $tableArray[$zeileSumme3]['U_refYield'] * 100;
            $tableArray[$zeileSumme3]['Z_tCellAvgWeighted']         = 0;
            $z_tCellAvgWeighted3                                    += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield'] ; ########### in zweiter Runde noch durch summe aus 'U_refYield' teilen
            $tableArray[$zeileSumme3]['AA_tCompensationFactor']                 = 0;
            $tableArray[$zeileSumme3]['AB_effRefYield']                         = 0;
            $tableArray[$zeileSumme3]['AC_effTheoEnergy']                       = 0;
            $tableArray[$zeileSumme3]['AC1_theoEnergyMeasured']                 += $tableArray[$n]['AC1_theoEnergyMeasured'];
            $tableArray[$zeileSumme3]['AD_prMonth']                             = 0;
            $tableArray[$zeileSumme3]['AE_ratio']                               = 0;
            $tableArray[$zeileSumme3]['AF_ratioFT']                             = 0;
            $tableArray[$zeileSumme3]['AG_epcPA']                               = 0;
            //Analysis
            $tableArray[$zeileSumme3]['AH_prForecast']                          = 0;
            $tableArray[$zeileSumme3]['AI_eGridForecast']                       = 0;
            $tableArray[$zeileSumme3]['AJ_specificYieldForecast']               = 0;
            $tableArray[$zeileSumme3]['AK_absDiffPrRealGuarForecast']           = 0;
            $tableArray[$zeileSumme3]['AL_relDiffPrRealGuarForecast']           = 0;

            $tableArray[$zeileSumme3]['current_month']              = 0;
            $tableArray[$zeileSumme3]['style']                      = "";

            if ($rollingPeriod) {
                $tableArray[$zeileSumme4]['B_month']                    = "Rolling period";
                $tableArray[$zeileSumme4]['C_days_month']               = 0;
                // DesignValues
                $tableArray[$zeileSumme4]['D_days_fac']                 += $tableArray[$n]['D_days_fac'];
                $tableArray[$zeileSumme4]['E_IrrDesign']                += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme4]['F_refYieldDesign']           += $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme4]['G_theoEnergyDesign']         += $tableArray[$n]['G_theoEnergyDesign'];
                $tableArray[$zeileSumme4]['H_eGridDesign']              += $tableArray[$n]['H_eGridDesign'];
                $tableArray[$zeileSumme4]['I_specificYieldDesign']      += $tableArray[$n]['I_specificYieldDesign'];
                $tableArray[$zeileSumme4]['J_prDesign']                 = $tableArray[$zeileSumme4]['I_specificYieldDesign'] / $tableArray[$zeileSumme4]['F_refYieldDesign'] * 100;
                $tableArray[$zeileSumme4]['K_tempAmbDesign']            = 0;
                $tableArray[$zeileSumme4]['L_tempAmbWeightedDesign']    = 0;
                $tableArray[$zeileSumme4]['M_tempCompFactDesign']       = 0;
                $k_tempAmbDesign4                                       += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac'] ; ########### in zweiter Runde noch durch summe Tage teilen
                $l_tempAmbWeightedDesign4                               += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign'] ; ########### in zweiter Runde noch durch summe Strahlung teilen
                $tableArray[$zeileSumme4]['N_effRefYieldDesign']        += $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme4]['O_effTheoEnergyDesign']      += $tableArray[$n]['O_effTheoEnergyDesign'];
                $tableArray[$zeileSumme4]['P_prMonthDesign']            = $tableArray[$zeileSumme4]['I_specificYieldDesign'] / $tableArray[$zeileSumme4]['N_effRefYieldDesign'] * 100;
                $tableArray[$zeileSumme4]['Q_prGuarDesign']             = $tableArray[$zeileSumme4]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme4]['R_specificYieldGuarDesign']  += $tableArray[$n]['R_specificYieldGuarDesign'];
                $tableArray[$zeileSumme4]['S_eGridGuarDesign']          += $tableArray[$n]['S_eGridGuarDesign'];
                // MeasuermentValues
                $tableArray[$zeileSumme4]['T_irr']                      += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme4]['U_refYield']                 += $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme4]['V_theoEnergy']               += $tableArray[$n]['V_theoEnergy'];
                $tableArray[$zeileSumme4]['W_eGrid']                    += $tableArray[$n]['W_eGrid'];
                $tableArray[$zeileSumme4]['X_specificYield']            += $tableArray[$n]['X_specificYield'];
                $tableArray[$zeileSumme4]['Y_pr']                       = $tableArray[$zeileSumme4]['X_specificYield'] / $tableArray[$zeileSumme4]['U_refYield'] * 100;
                $tableArray[$zeileSumme4]['Z_tCellAvgWeighted']         = 0;
                $z_tCellAvgWeighted4                                    += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield'] ; ########### in zweiter Runde noch durch summe aus 'U_refYield' teilen
                $tableArray[$zeileSumme4]['AA_tCompensationFactor']                 = 0;
                $tableArray[$zeileSumme4]['AB_effRefYield']                         = 0;
                $tableArray[$zeileSumme4]['AC_effTheoEnergy']                       = 0;
                $tableArray[$zeileSumme4]['AC1_theoEnergyMeasured']                 += $tableArray[$n]['AC1_theoEnergyMeasured'];
                $tableArray[$zeileSumme4]['AD_prMonth']                             = 0;
                $tableArray[$zeileSumme4]['AE_ratio']                               = 0;
                $tableArray[$zeileSumme4]['AF_ratioFT']                             = 0;
                $tableArray[$zeileSumme4]['AG_epcPA']                               = 0;
                //Analysis
                $tableArray[$zeileSumme4]['AH_prForecast']                          = 0;
                $tableArray[$zeileSumme4]['AI_eGridForecast']                       = 0;
                $tableArray[$zeileSumme4]['AJ_specificYieldForecast']               = 0;
                $tableArray[$zeileSumme4]['AK_absDiffPrRealGuarForecast']           = 0;
                $tableArray[$zeileSumme4]['AL_relDiffPrRealGuarForecast']           = 0;

                $tableArray[$zeileSumme4]['current_month']              = 0;
                $tableArray[$zeileSumme4]['style']                      = "";
            }

            if ($hasMonthData) {
                $tableArray[$zeileSumme5]['B_month']                    = "Current up to date";
                $tableArray[$zeileSumme5]['C_days_month']               = 0;
                // DesignValues
                $tableArray[$zeileSumme5]['D_days_fac']                 += $tableArray[$n]['D_days_fac'];
                $tableArray[$zeileSumme5]['E_IrrDesign']                += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme5]['F_refYieldDesign']           += $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme5]['G_theoEnergyDesign']         += $tableArray[$n]['G_theoEnergyDesign'];
                $tableArray[$zeileSumme5]['H_eGridDesign']              += $tableArray[$n]['H_eGridDesign'];
                $tableArray[$zeileSumme5]['I_specificYieldDesign']      += $tableArray[$n]['I_specificYieldDesign'];
                $tableArray[$zeileSumme5]['J_prDesign']                 = $tableArray[$zeileSumme5]['I_specificYieldDesign'] / $tableArray[$zeileSumme5]['F_refYieldDesign'] * 100;
                $tableArray[$zeileSumme5]['K_tempAmbDesign']            = 0;
                $tableArray[$zeileSumme5]['L_tempAmbWeightedDesign']    = 0;
                $tableArray[$zeileSumme5]['M_tempCompFactDesign']       = 0;
                $k_tempAmbDesign5                                       += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac'] ; ########### in zweiter Runde noch durch summe Tage teilen
                $l_tempAmbWeightedDesign5                               += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign'] ; ########### in zweiter Runde noch durch summe Strahlung teilen
                $tableArray[$zeileSumme5]['N_effRefYieldDesign']        += $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme5]['O_effTheoEnergyDesign']      += $tableArray[$n]['O_effTheoEnergyDesign'];
                $tableArray[$zeileSumme5]['P_prMonthDesign']            = $tableArray[$zeileSumme5]['I_specificYieldDesign'] / $tableArray[$zeileSumme5]['N_effRefYieldDesign'] * 100;
                $tableArray[$zeileSumme5]['Q_prGuarDesign']             = $tableArray[$zeileSumme5]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme5]['R_specificYieldGuarDesign']  += $tableArray[$n]['R_specificYieldGuarDesign'];
                $tableArray[$zeileSumme5]['S_eGridGuarDesign']          += $tableArray[$n]['S_eGridGuarDesign'];
                // MeasuermentValues
                $tableArray[$zeileSumme5]['T_irr']                      += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme5]['U_refYield']                 += $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme5]['V_theoEnergy']               += $tableArray[$n]['V_theoEnergy'];
                $tableArray[$zeileSumme5]['W_eGrid']                    += $tableArray[$n]['W_eGrid'];
                $tableArray[$zeileSumme5]['X_specificYield']            += $tableArray[$n]['X_specificYield'];
                $tableArray[$zeileSumme5]['Y_pr']                       = $tableArray[$zeileSumme5]['X_specificYield'] / $tableArray[$zeileSumme5]['U_refYield'] * 100;
                $tableArray[$zeileSumme5]['Z_tCellAvgWeighted']         = 0;
                $z_tCellAvgWeighted5                                    += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield'] ; ########### in zweiter Runde noch durch summe aus 'U_refYield' teilen
                $tableArray[$zeileSumme5]['AA_tCompensationFactor']                 = 0;
                $tableArray[$zeileSumme5]['AB_effRefYield']                         = 0;
                $tableArray[$zeileSumme5]['AC_effTheoEnergy']                       = 0;
                $tableArray[$zeileSumme5]['AC1_theoEnergyMeasured']                 += $tableArray[$n]['AC1_theoEnergyMeasured'];
                $tableArray[$zeileSumme5]['AD_prMonth']                             = 0;
                $tableArray[$zeileSumme5]['AE_ratio']                               = 0;
                $tableArray[$zeileSumme5]['AF_ratioFT']                             = 0;
                $tableArray[$zeileSumme5]['AG_epcPA']                               = 0;
                //Analysis
                $tableArray[$zeileSumme5]['AH_prForecast']                          = 0;
                $tableArray[$zeileSumme5]['AI_eGridForecast']                       = 0;
                $tableArray[$zeileSumme5]['AJ_specificYieldForecast']               = 0;
                $tableArray[$zeileSumme5]['AK_absDiffPrRealGuarForecast']           = 0;
                $tableArray[$zeileSumme5]['AL_relDiffPrRealGuarForecast']           = 0;

                $tableArray[$zeileSumme5]['current_month']              = 0;
                $tableArray[$zeileSumme5]['style']                      = "";
            } else {
                $tableArray[$zeileSumme6]['B_month']                    = "Forcast period (after current date)";
                $tableArray[$zeileSumme6]['C_days_month']               = 0;
                // DesignValues
                $tableArray[$zeileSumme6]['D_days_fac']                 += $tableArray[$n]['D_days_fac'];
                $tableArray[$zeileSumme6]['E_IrrDesign']                += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme6]['F_refYieldDesign']           += $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme6]['G_theoEnergyDesign']         += $tableArray[$n]['G_theoEnergyDesign'];
                $tableArray[$zeileSumme6]['H_eGridDesign']              += $tableArray[$n]['H_eGridDesign'];
                $tableArray[$zeileSumme6]['I_specificYieldDesign']      += $tableArray[$n]['I_specificYieldDesign'];
                $tableArray[$zeileSumme6]['J_prDesign']                 = $tableArray[$zeileSumme6]['I_specificYieldDesign'] / $tableArray[$zeileSumme6]['F_refYieldDesign'] * 100;
                $tableArray[$zeileSumme6]['K_tempAmbDesign']            = 0;
                $tableArray[$zeileSumme6]['L_tempAmbWeightedDesign']    = 0;
                $tableArray[$zeileSumme6]['M_tempCompFactDesign']       = 0;
                $k_tempAmbDesign6                                       += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac'] ; ########### in zweiter Runde noch durch summe Tage teilen
                $l_tempAmbWeightedDesign6                               += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign'] ; ########### in zweiter Runde noch durch summe Strahlung teilen
                $tableArray[$zeileSumme6]['N_effRefYieldDesign']        += $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme6]['O_effTheoEnergyDesign']      += $tableArray[$n]['O_effTheoEnergyDesign'];
                $tableArray[$zeileSumme6]['P_prMonthDesign']            = $tableArray[$zeileSumme6]['I_specificYieldDesign'] / $tableArray[$zeileSumme6]['N_effRefYieldDesign'] * 100;
                $tableArray[$zeileSumme6]['Q_prGuarDesign']             = $tableArray[$zeileSumme6]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme6]['R_specificYieldGuarDesign']  += $tableArray[$n]['R_specificYieldGuarDesign'];
                $tableArray[$zeileSumme6]['S_eGridGuarDesign']          += $tableArray[$n]['S_eGridGuarDesign'];
                // MeasuermentValues
                $tableArray[$zeileSumme6]['T_irr']                      += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme6]['U_refYield']                 += $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme6]['V_theoEnergy']               += $tableArray[$n]['V_theoEnergy'];
                $tableArray[$zeileSumme6]['W_eGrid']                    += $tableArray[$n]['W_eGrid'];
                $tableArray[$zeileSumme6]['X_specificYield']            += $tableArray[$n]['X_specificYield'];
                $tableArray[$zeileSumme6]['Y_pr']                       = $tableArray[$zeileSumme6]['X_specificYield'] / $tableArray[$zeileSumme6]['U_refYield'] * 100;
                $tableArray[$zeileSumme6]['Z_tCellAvgWeighted']         = 0;
                $z_tCellAvgWeighted6                                    += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield'] ; ########### in zweiter Runde noch durch summe aus 'U_refYield' teilen
                $tableArray[$zeileSumme6]['AA_tCompensationFactor']                 = 0;
                $tableArray[$zeileSumme6]['AB_effRefYield']                         = 0;
                $tableArray[$zeileSumme6]['AC_effTheoEnergy']                       = 0;
                $tableArray[$zeileSumme6]['AC1_theoEnergyMeasured']                 += $tableArray[$n]['AC1_theoEnergyMeasured'];
                $tableArray[$zeileSumme6]['AD_prMonth']                             = 0;
                $tableArray[$zeileSumme6]['AE_ratio']                               = 0;
                $tableArray[$zeileSumme6]['AF_ratioFT']                             = 0;
                $tableArray[$zeileSumme6]['AG_epcPA']                               = 0;
                //Analysis
                $tableArray[$zeileSumme6]['AH_prForecast']                          = 0;
                $tableArray[$zeileSumme6]['AI_eGridForecast']                       = 0;
                $tableArray[$zeileSumme6]['AJ_specificYieldForecast']               = 0;
                $tableArray[$zeileSumme6]['AK_absDiffPrRealGuarForecast']           = 0;
                $tableArray[$zeileSumme6]['AL_relDiffPrRealGuarForecast']           = 0;

                $tableArray[$zeileSumme6]['current_month']              = 0;
                $tableArray[$zeileSumme6]['style']                      = "";
            }

            if ($n <> 13 && $n <> 26) $month++;
        }


        /////////////////////////////
        /// Runde 2
        /////////////////////////////
        $month = $startMonth;
        $year = $startYear;

        for ($n = 1; $n <= $anzahlMonate; $n++) {
            if ($month > 12) {
                $month = 1;
                $year++;
            }

            $from_local = date_create(date('Y-m-d 00:00', strtotime("$year-$month-01")));
            $hasMonthData = $from_local <= $date; // Wenn das Datum in $from_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true
            $rollingPeriod = true;

            $tableArray[$n]['AE_ratio']                     = $tableArray[$n]['U_refYield'] / $tableArray[$zeileSumme3]['U_refYield'] * 100;
            #$tableArray[$n]['Z_tCellAvgWeighted']           = $hasMonthData ? $tableArray[$n]['Z_tCellAvgWeighted'] / $tableArray[$zeileSumme3]['T_irr'] : $tableArray[$n]['Z_tCellAvgWeighted'];


            if ($n <= 13) { // Year 1
                $tableArray[$zeileSumme1]['K_tempAmbDesign']                        = $k_tempAmbDesign1 / $tableArray[$zeileSumme1]['D_days_fac'];
                $tableArray[$zeileSumme1]['L_tempAmbWeightedDesign']                = $l_tempAmbWeightedDesign1  / $tableArray[$zeileSumme1]['E_IrrDesign'];
                $tableArray[$zeileSumme1]['Z_tCellAvgWeighted']                     = $z_tCellAvgWeighted1 / $tableArray[$zeileSumme1]['U_refYield'];
                $tableArray[$zeileSumme1]['M_tempCompFactDesign']                   = (1 + ($tableArray[$zeileSumme1]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
                $tableArray[$zeileSumme1]['AE_ratio']                               += $tableArray[$n]['AE_ratio'];
            }

            if ($n >= 14) { // Year 2
                $tableArray[$zeileSumme2]['K_tempAmbDesign']                        = $k_tempAmbDesign2 / $tableArray[$zeileSumme2]['D_days_fac'];
                $tableArray[$zeileSumme2]['L_tempAmbWeightedDesign']                = $l_tempAmbWeightedDesign2 / $tableArray[$zeileSumme2]['E_IrrDesign'];
                $tableArray[$zeileSumme2]['Z_tCellAvgWeighted']                     = $z_tCellAvgWeighted2 / $tableArray[$zeileSumme2]['U_refYield'];
                $tableArray[$zeileSumme2]['M_tempCompFactDesign']                   = (1 + ($tableArray[$zeileSumme2]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
                $tableArray[$zeileSumme2]['AE_ratio']                               += $tableArray[$n]['AE_ratio'];
            }

            $tableArray[$zeileSumme3]['K_tempAmbDesign']                        = $k_tempAmbDesign3 / $tableArray[$zeileSumme3]['D_days_fac'];
            $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']                = $l_tempAmbWeightedDesign3 / $tableArray[$zeileSumme3]['E_IrrDesign'];
            $tableArray[$zeileSumme3]['Z_tCellAvgWeighted']                     = $z_tCellAvgWeighted3 / $tableArray[$zeileSumme3]['U_refYield'];
            $tableArray[$zeileSumme3]['M_tempCompFactDesign']                   = (1 + ($tableArray[$zeileSumme3]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
            $tableArray[$zeileSumme3]['AE_ratio']                               += $tableArray[$n]['AE_ratio'];

            if ($rollingPeriod) {
                $tableArray[$zeileSumme4]['K_tempAmbDesign']                        = $k_tempAmbDesign4 / $tableArray[$zeileSumme4]['D_days_fac'];
                $tableArray[$zeileSumme4]['L_tempAmbWeightedDesign']                = $l_tempAmbWeightedDesign4 / $tableArray[$zeileSumme4]['E_IrrDesign'];
                $tableArray[$zeileSumme4]['Z_tCellAvgWeighted']                     = $z_tCellAvgWeighted4 / $tableArray[$zeileSumme4]['U_refYield'];
                $tableArray[$zeileSumme4]['M_tempCompFactDesign']                   = (1 + ($tableArray[$zeileSumme4]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
                $tableArray[$zeileSumme4]['AE_ratio']                               += $tableArray[$n]['AE_ratio'];
            }

            if ($hasMonthData) {
                $tableArray[$zeileSumme5]['K_tempAmbDesign']                        = $k_tempAmbDesign5 / $tableArray[$zeileSumme5]['D_days_fac'];
                $tableArray[$zeileSumme5]['L_tempAmbWeightedDesign']                = $l_tempAmbWeightedDesign5 / $tableArray[$zeileSumme5]['E_IrrDesign'];
                $tableArray[$zeileSumme5]['Z_tCellAvgWeighted']                     = $z_tCellAvgWeighted5 / $tableArray[$zeileSumme5]['U_refYield'];
                $tableArray[$zeileSumme5]['M_tempCompFactDesign']                   = (1 + ($tableArray[$zeileSumme5]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
                $tableArray[$zeileSumme5]['AE_ratio']                               += $tableArray[$n]['AE_ratio'];
            } else {
                $tableArray[$zeileSumme6]['K_tempAmbDesign']                        = $k_tempAmbDesign6 / $tableArray[$zeileSumme6]['D_days_fac'];
                $tableArray[$zeileSumme6]['L_tempAmbWeightedDesign']                = $l_tempAmbWeightedDesign6 / $tableArray[$zeileSumme6]['E_IrrDesign'];
                $tableArray[$zeileSumme6]['Z_tCellAvgWeighted']                     = $z_tCellAvgWeighted2 / $tableArray[$zeileSumme2]['U_refYield'];
                $tableArray[$zeileSumme6]['M_tempCompFactDesign']                   = (1 + ($tableArray[$zeileSumme6]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
                $tableArray[$zeileSumme6]['AE_ratio']                               += $tableArray[$n]['AE_ratio'];
            }

            $tableArray[$n]['AA_tCompensationFactor']       = 1 + ($tableArray[$n]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
            $tableArray[$n]['AB_effRefYield']               = $tableArray[$n]['U_refYield'] * $tableArray[$n]['AA_tCompensationFactor'];
            $tableArray[$n]['AC_effTheoEnergy']             = $tableArray[$n]['AB_effRefYield'] * $anlage->getPnom();
            $tableArray[$n]['AD_prMonth']                   = $tableArray[$n]['X_specificYield'] / $tableArray[$n]['AB_effRefYield'] * 100;
            #$tableArray[$n]['AF_ratioFT']                   = $tableArray[$n]['AB_effRefYield'] / $tableArray[$zeileSumme3]['AB_effRefYield'] * 100;

            if ($n <= 13) { // Year 1
                $tableArray[$zeileSumme1]['AB_effRefYield']                 += $tableArray[$n]['AB_effRefYield'];
                $tableArray[$zeileSumme1]['AD_prMonth']                     = $tableArray[$zeileSumme1]['X_specificYield'] / $tableArray[$zeileSumme1]['AB_effRefYield'] * 100;
            }

            if ($n >= 14) { // Year 2
                $tableArray[$zeileSumme2]['AB_effRefYield']                 += $tableArray[$n]['AB_effRefYield'];
                $tableArray[$zeileSumme2]['AD_prMonth']                     = $tableArray[$zeileSumme2]['X_specificYield'] / $tableArray[$zeileSumme2]['AB_effRefYield'] * 100;
            }

            $tableArray[$zeileSumme3]['AB_effRefYield']             += $tableArray[$n]['AB_effRefYield'];
            $tableArray[$zeileSumme3]['AD_prMonth']                 = $tableArray[$zeileSumme3]['X_specificYield'] / $tableArray[$zeileSumme3]['AB_effRefYield'] * 100;

            if ($rollingPeriod) {
                $tableArray[$zeileSumme4]['AB_effRefYield']             += $tableArray[$n]['AB_effRefYield'];
                $tableArray[$zeileSumme4]['AD_prMonth']                 = $tableArray[$zeileSumme4]['X_specificYield'] / $tableArray[$zeileSumme4]['AB_effRefYield'] * 100;
            }

            if ($hasMonthData) {
                $tableArray[$zeileSumme5]['AB_effRefYield']             += $tableArray[$n]['AB_effRefYield'];
                $tableArray[$zeileSumme5]['AD_prMonth']                 = $tableArray[$zeileSumme5]['X_specificYield'] / $tableArray[$zeileSumme5]['AB_effRefYield'] * 100;

            } else {
                $tableArray[$zeileSumme6]['AB_effRefYield']                 += $tableArray[$n]['AB_effRefYield'];
                $tableArray[$zeileSumme6]['AD_prMonth']                     = $tableArray[$zeileSumme6]['X_specificYield'] / $tableArray[$zeileSumme6]['AB_effRefYield'] * 100;

            }

            if ($n <> 13 && $n <> 26) $month++;
        }


        $riskForecastUpToDate = $tableArray[$zeileSumme5]['AD_prMonth'] / $tableArray[$zeileSumme5]['P_prMonthDesign'];
        $riskForecastRollingPeriod = $tableArray[$zeileSumme4]['AD_prMonth'] / $tableArray[$zeileSumme4]['P_prMonthDesign'];

        /////////////////////////////
        /// Runde 3
        /////////////////////////////
        $month = $startMonth;
        $year = $startYear;

        for ($n = 1; $n <= $anzahlMonate; $n++) {
            if ($month > 12) {
                $month = 1;
                $year++;
            }

            $from_local = date_create(date('Y-m-d 00:00', strtotime("$year-$month-01")));
            $hasMonthData = $from_local <= $date; // Wenn das Datum in $from_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true
            $rollingPeriod = true;

            //Analysis
            $tableArray[$n]['AH_prForecast']                                = $hasMonthData ? $tableArray[$n]['AD_prMonth'] : $tableArray[$n]['AD_prMonth'] * $riskForecastUpToDate;
            $tableArray[$n]['AI_eGridForecast']                             = $tableArray[$n]['AH_prForecast'] / 100 * $tableArray[$n]['AB_effRefYield'] * $anlage->getPnom();
            $tableArray[$n]['AJ_specificYieldForecast']                     = $tableArray[$n]['AI_eGridForecast'] / $anlage->getPnom();
            $tableArray[$n]['AK_absDiffPrRealGuarForecast']                 = $tableArray[$n]['AH_prForecast'] - $tableArray[$n]['Q_prGuarDesign'];
            $tableArray[$n]['AL_relDiffPrRealGuarForecast']                 = ($tableArray[$n]['AH_prForecast'] / $tableArray[$n]['Q_prGuarDesign'] - 1) * 100;

            if ($n <= 13) { // Year 1
                $tableArray[$zeileSumme1]['AA_tCompensationFactor']         = 1 + ($tableArray[$zeileSumme1]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
                $tableArray[$zeileSumme1]['AC_effTheoEnergy']               += $tableArray[$n]['AC_effTheoEnergy'];
               # $tableArray[$zeileSumme1]['AF_ratioFT']                     += $tableArray[$n]['AF_ratioFT'];
                //Analysis
                $tableArray[$zeileSumme1]['AH_prForecast']                  += $tableArray[$zeileSumme1]['AI_eGridForecast'] / $tableArray[$zeileSumme1]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme1]['AI_eGridForecast']               += $tableArray[$n]['AI_eGridForecast'];
                $tableArray[$zeileSumme1]['AH_prForecast']                  = $tableArray[$zeileSumme1]['AI_eGridForecast'] / $tableArray[$zeileSumme1]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme1]['AJ_specificYieldForecast']       += $tableArray[$n]['AJ_specificYieldForecast'];
                $tableArray[$zeileSumme1]['AK_absDiffPrRealGuarForecast']   = $tableArray[$zeileSumme1]['AH_prForecast'] - $tableArray[$zeileSumme1]['Q_prGuarDesign'];
                $tableArray[$zeileSumme1]['AL_relDiffPrRealGuarForecast']   = ($tableArray[$zeileSumme1]['AH_prForecast'] / $tableArray[$zeileSumme1]['Q_prGuarDesign'] - 1) * 100;
            }

            if ($n >= 14) { // Year 2
                $tableArray[$zeileSumme2]['AA_tCompensationFactor']         = 1 + ($tableArray[$zeileSumme2]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
                $tableArray[$zeileSumme2]['AC_effTheoEnergy']               += $tableArray[$n]['AC_effTheoEnergy'];
                #$tableArray[$zeileSumme2]['AF_ratioFT']                     += $tableArray[$n]['AF_ratioFT'];
                //Analysis
                $tableArray[$zeileSumme2]['AH_prForecast']                  += $tableArray[$zeileSumme2]['AI_eGridForecast'] / $tableArray[$zeileSumme2]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme2]['AI_eGridForecast']               += $tableArray[$n]['AI_eGridForecast'];
                $tableArray[$zeileSumme2]['AH_prForecast']                  = $tableArray[$zeileSumme2]['AI_eGridForecast'] / $tableArray[$zeileSumme2]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme2]['AJ_specificYieldForecast']       += $tableArray[$n]['AJ_specificYieldForecast'];
                $tableArray[$zeileSumme2]['AK_absDiffPrRealGuarForecast']   = $tableArray[$zeileSumme2]['AH_prForecast'] - $tableArray[$zeileSumme2]['Q_prGuarDesign'];
                $tableArray[$zeileSumme2]['AL_relDiffPrRealGuarForecast']   = ($tableArray[$zeileSumme2]['AH_prForecast'] / $tableArray[$zeileSumme2]['Q_prGuarDesign'] - 1) * 100;
            }

            $tableArray[$zeileSumme3]['AA_tCompensationFactor']     = 1 + ($tableArray[$zeileSumme3]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
            $tableArray[$zeileSumme3]['AC_effTheoEnergy']           += $tableArray[$n]['AC_effTheoEnergy'];
            #$tableArray[$zeileSumme3]['AF_ratioFT']                 += $tableArray[$n]['AF_ratioFT'];
            //Analysis
            $tableArray[$zeileSumme3]['AH_prForecast']                  += $tableArray[$zeileSumme3]['AI_eGridForecast'] / $tableArray[$zeileSumme3]['AC_effTheoEnergy'] * 100;
            $tableArray[$zeileSumme3]['AI_eGridForecast']               += $tableArray[$n]['AI_eGridForecast'];
            $tableArray[$zeileSumme3]['AH_prForecast']                  = $tableArray[$zeileSumme3]['AI_eGridForecast'] / $tableArray[$zeileSumme3]['AC_effTheoEnergy'] * 100;
            $tableArray[$zeileSumme3]['AJ_specificYieldForecast']       += $tableArray[$n]['AJ_specificYieldForecast'];
            $tableArray[$zeileSumme3]['AK_absDiffPrRealGuarForecast']   = $tableArray[$zeileSumme3]['AH_prForecast'] - $tableArray[$zeileSumme3]['Q_prGuarDesign'];
            $tableArray[$zeileSumme3]['AL_relDiffPrRealGuarForecast']   = ($tableArray[$zeileSumme3]['AH_prForecast'] / $tableArray[$zeileSumme3]['Q_prGuarDesign'] - 1) * 100;

            if ($rollingPeriod) {
                $tableArray[$zeileSumme4]['AA_tCompensationFactor']     = 1 + ($tableArray[$zeileSumme4]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
                $tableArray[$zeileSumme4]['AC_effTheoEnergy']           += $tableArray[$n]['AC_effTheoEnergy'];
                #$tableArray[$zeileSumme4]['AF_ratioFT']                 += $tableArray[$n]['AF_ratioFT'];
                //Analysis
                $tableArray[$zeileSumme4]['AH_prForecast']                  += $tableArray[$zeileSumme4]['AI_eGridForecast'] / $tableArray[$zeileSumme4]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme4]['AI_eGridForecast']               += $tableArray[$n]['AI_eGridForecast'];
                $tableArray[$zeileSumme4]['AH_prForecast']                  = $tableArray[$zeileSumme4]['AI_eGridForecast'] / $tableArray[$zeileSumme4]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme4]['AJ_specificYieldForecast']       += $tableArray[$n]['AJ_specificYieldForecast'];
                $tableArray[$zeileSumme4]['AK_absDiffPrRealGuarForecast']   = $tableArray[$zeileSumme4]['AH_prForecast'] - $tableArray[$zeileSumme4]['Q_prGuarDesign'];
                $tableArray[$zeileSumme4]['AL_relDiffPrRealGuarForecast']   = ($tableArray[$zeileSumme4]['AH_prForecast'] / $tableArray[$zeileSumme4]['Q_prGuarDesign'] - 1) * 100;
            }

            if ($hasMonthData) {
                $tableArray[$zeileSumme5]['AA_tCompensationFactor']     = 1 + ($tableArray[$zeileSumme5]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
                $tableArray[$zeileSumme5]['AC_effTheoEnergy']           += $tableArray[$n]['AC_effTheoEnergy'];
                #$tableArray[$zeileSumme5]['AF_ratioFT']                 += $tableArray[$n]['AF_ratioFT'];
                //Analysis
                $tableArray[$zeileSumme5]['AH_prForecast']                  += $tableArray[$zeileSumme5]['AI_eGridForecast'] / $tableArray[$zeileSumme5]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme5]['AI_eGridForecast']               += $tableArray[$n]['AI_eGridForecast'];
                $tableArray[$zeileSumme5]['AH_prForecast']                  = $tableArray[$zeileSumme5]['AI_eGridForecast'] / $tableArray[$zeileSumme5]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme5]['AJ_specificYieldForecast']       += $tableArray[$n]['AJ_specificYieldForecast'];
                $tableArray[$zeileSumme5]['AK_absDiffPrRealGuarForecast']   = $tableArray[$zeileSumme5]['AH_prForecast'] - $tableArray[$zeileSumme5]['Q_prGuarDesign'];
                $tableArray[$zeileSumme5]['AL_relDiffPrRealGuarForecast']   = ($tableArray[$zeileSumme5]['AH_prForecast'] / $tableArray[$zeileSumme5]['Q_prGuarDesign'] - 1) * 100;
            } else {
                $tableArray[$zeileSumme6]['AA_tCompensationFactor']         = 1 + ($tableArray[$zeileSumme6]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
                $tableArray[$zeileSumme6]['AC_effTheoEnergy']               += $tableArray[$n]['AC_effTheoEnergy'];
                #$tableArray[$zeileSumme6]['AF_ratioFT']                     += $tableArray[$n]['AF_ratioFT'];
                //Analysis
                $tableArray[$zeileSumme6]['AH_prForecast']                  += $tableArray[$zeileSumme6]['AI_eGridForecast'] / $tableArray[$zeileSumme6]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme6]['AI_eGridForecast']               += $tableArray[$n]['AI_eGridForecast'];
                $tableArray[$zeileSumme6]['AH_prForecast']                  = $tableArray[$zeileSumme6]['AI_eGridForecast'] / $tableArray[$zeileSumme6]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme6]['AJ_specificYieldForecast']       += $tableArray[$n]['AJ_specificYieldForecast'];
                $tableArray[$zeileSumme6]['AK_absDiffPrRealGuarForecast']   = $tableArray[$zeileSumme6]['AH_prForecast'] - $tableArray[$zeileSumme6]['Q_prGuarDesign'];
                $tableArray[$zeileSumme6]['AL_relDiffPrRealGuarForecast']   = ($tableArray[$zeileSumme6]['AH_prForecast'] / $tableArray[$zeileSumme6]['Q_prGuarDesign'] - 1) * 100;
            }

            if ($n <> 13 && $n <> 26) $month++;
        }
        ksort($tableArray);

        return $tableArray;
    }
}