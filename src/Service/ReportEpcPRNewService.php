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

            if ($n === 1) {
                $from_local = date_create(date('Y-m-d', strtotime("$year-$month-$facStartDay 00:00")));
                $days = $daysInMonth - $daysInStartMonth + 1;
                $factor = $days / $daysInMonth;
            } elseif ($n % 12 == 0) {
                $lastMonthYear = true;
                $days = $daysInEndMonth;
                $factor = $days / $daysInMonth;
                $to_local = date_create(date('Y-m-d', strtotime("$year-$month-$facEndDay 23:59")));
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

            $prPVSyst           = 88.43; // muss aus Anlage kommen
            $prFAC              = 84.23; // muss aus Anlage kommen
            $deductionTransform = 0; // wo muss das herkommen ????
            $deductionRisk      = 100 * (1 - $prFAC / $prPVSyst);
            $deductionOverall   = 100 - (100 - $deductionTransform) * (100 - $deductionRisk) / 100;

            # $tableArray[$n][''];

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
            $tableArray[$n]['P_prMonthDesign']                        = $tableArray[$n]['I_specificYieldDesign'] / $tableArray[$n]['N_effRefYieldDesign'];
            $tableArray[$n]['Q_prGuarDesign']                         = $tableArray[$n]['P_prMonthDesign'] - (1 - $deductionOverall / 100);
            $tableArray[$n]['R_specificYieldGuarDesign']              = $tableArray[$n]['Q_prGuarDesign'] / 100 * $tableArray[$n]['N_effRefYieldDesign'];
            $tableArray[$n]['S_eGridGuarDesign']                      = $tableArray[$n]['R_specificYieldGuarDesign'] * $anlage->getKwPeakPvSyst();
            // MeasuermentValues
            $tableArray[$n]['T_irr']                                  = $hasMonthData ? $prArray['irradiation'] : $tableArray[$n]['E_IrrDesign'];
            $tableArray[$n]['U_refYield']                             = $tableArray[$n]['T_irr'];
            $tableArray[$n]['V_theoEnergy']                           = $tableArray[$n]['T_irr'] * $anlage->getPnom();
            $tableArray[$n]['W_eGrid']                                = $hasMonthData ? $eGridReal : $tableArray[$n]['H_eGridDesign'] * $anlage->getPnom() / $anlage->getKwPeakPvSyst();
            $tableArray[$n]['X_specificYield']                        = $tableArray[$n]['W_eGrid'] / $anlage->getPnom();
            $tableArray[$n]['Y_pr']                                   = $tableArray[$n]['X_specificYield'] / $tableArray[$n]['U_refYield'];
            $tableArray[$n]['Z_tCellAvgWeighted']                     = $hasMonthData ? $prArray['tCellAvg'] : $tableArray[$n]['L_tempAmbWeightedDesign'];

            $tableArray[$n]['AG_epcPA']                               = '';
            // Steuerrung
            $tableArray[$n]['current_month']                           = $isCurrentMonth ? -1 : 0;
            $tableArray[$n]['style']                                   = "";

            ############
            if ($isYear1) {
                $tableArray[$zeileSumme1]['B_month']                    = "Year 1";
                $tableArray[$zeileSumme1]['C_days_month']               = '';
                // DesignValues
                $tableArray[$zeileSumme1]['D_days_fac']                 = 'days of year';
                $tableArray[$zeileSumme1]['E_IrrDesign']                += $pvSystData[$month - 1]['irrDesign'] * $factor;
                $tableArray[$zeileSumme1]['F_refYieldDesign']           += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme1]['G_theoEnergyDesign']         += $tableArray[$n]['F_refYieldDesign'] * $anlage->getKwPeakPvSyst();
                $tableArray[$zeileSumme1]['H_eGridDesign']              += $pvSystData[$month - 1]['ertragDesign'] * $factor;
                $tableArray[$zeileSumme1]['I_specificYieldDesign']      += $tableArray[$n]['H_eGridDesign'] / $anlage->getKwPeakPvSyst();
                $tableArray[$zeileSumme1]['J_prDesign']                 += $tableArray[$n]['H_eGridDesign'] / ($tableArray[$n]['E_IrrDesign'] * $anlage->getKwPeakPvSyst()) * 100;
                $tableArray[$zeileSumme1]['K_tempAmbDesign']            += $pvSystData[$month - 1]['tempAmbDesign'];
                $tableArray[$zeileSumme1]['L_tempAmbWeightedDesign']    += $pvSystData[$month - 1]['tempAmbWeightedDesign'];
                $tableArray[$zeileSumme1]['M_tempCompFactDesign']       += (1 + ($tableArray[$n]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrA() / 100);
                $tableArray[$zeileSumme1]['N_effRefYieldDesign']        += $tableArray[$n]['F_refYieldDesign'] * $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme1]['O_effTheoEnergyDesign']      += $tableArray[$n]['N_effRefYieldDesign'] * $anlage->getKwPeakPvSyst();
                $tableArray[$zeileSumme1]['P_prMonthDesign']            += $tableArray[$n]['I_specificYieldDesign'] / $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme1]['Q_prGuarDesign']             += $tableArray[$n]['P_prMonthDesign'] - (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme1]['R_specificYieldGuarDesign']  += $tableArray[$n]['Q_prGuarDesign'] / 100 * $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme1]['S_eGridGuarDesign']          += $tableArray[$n]['R_specificYieldGuarDesign'] * $anlage->getKwPeakPvSyst();
                // MeasuermentValues
                $tableArray[$zeileSumme1]['T_irr']                      += $hasMonthData ? $prArray['irradiation'] : $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme1]['U_refYield']                 += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme1]['V_theoEnergy']               += $tableArray[$n]['T_irr'] * $anlage->getPnom();
                $tableArray[$zeileSumme1]['W_eGrid']                    += $hasMonthData ? $eGridReal : $tableArray[$n]['H_eGridDesign'] * $anlage->getPnom() / $anlage->getKwPeakPvSyst();
                $tableArray[$zeileSumme1]['X_specificYield']            += $tableArray[$n]['W_eGrid'] / $anlage->getPnom();
                $tableArray[$zeileSumme1]['Y_pr']                       += $tableArray[$n]['X_specificYield'] / $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme1]['Z_tCellAvgWeighted']         += $hasMonthData ? $prArray['tCellAvg'] : $tableArray[$n]['L_tempAmbWeightedDesign'];
            }

            if ($isYear2) {
                $tableArray[$zeileSumme2]['B_month']                    = "Year 1";
                $tableArray[$zeileSumme2]['C_days_month']               = '';
                // DesignValues
                $tableArray[$zeileSumme2]['D_days_fac']                 = 'days of year';
                $tableArray[$zeileSumme2]['E_IrrDesign']                += $pvSystData[$month - 1]['irrDesign'] * $factor;
                $tableArray[$zeileSumme2]['F_refYieldDesign']           += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme2]['G_theoEnergyDesign']         += $tableArray[$n]['F_refYieldDesign'] * $anlage->getKwPeakPvSyst();
                $tableArray[$zeileSumme2]['H_eGridDesign']              += $pvSystData[$month - 1]['ertragDesign'] * $factor;
                $tableArray[$zeileSumme2]['I_specificYieldDesign']      += $tableArray[$n]['H_eGridDesign'] / $anlage->getKwPeakPvSyst();
                $tableArray[$zeileSumme2]['J_prDesign']                 += $tableArray[$n]['H_eGridDesign'] / ($tableArray[$n]['E_IrrDesign'] * $anlage->getKwPeakPvSyst()) * 100;
                $tableArray[$zeileSumme2]['K_tempAmbDesign']            += $pvSystData[$month - 1]['tempAmbDesign'];
                $tableArray[$zeileSumme2]['L_tempAmbWeightedDesign']    += $pvSystData[$month - 1]['tempAmbWeightedDesign'];
                $tableArray[$zeileSumme2]['M_tempCompFactDesign']       += (1 + ($tableArray[$n]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrA() / 100);
                $tableArray[$zeileSumme2]['N_effRefYieldDesign']        += $tableArray[$n]['F_refYieldDesign'] * $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme2]['O_effTheoEnergyDesign']      += $tableArray[$n]['N_effRefYieldDesign'] * $anlage->getKwPeakPvSyst();
                $tableArray[$zeileSumme2]['P_prMonthDesign']            += $tableArray[$n]['I_specificYieldDesign'] / $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme2]['Q_prGuarDesign']             += $tableArray[$n]['P_prMonthDesign'] - (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme2]['R_specificYieldGuarDesign']  += $tableArray[$n]['Q_prGuarDesign'] / 100 * $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme2]['S_eGridGuarDesign']          += $tableArray[$n]['R_specificYieldGuarDesign'] * $anlage->getKwPeakPvSyst();
                // MeasuermentValues
                $tableArray[$zeileSumme2]['T_irr']                      += $hasMonthData ? $prArray['irradiation'] : $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme2]['U_refYield']                 += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme2]['V_theoEnergy']               += $tableArray[$n]['T_irr'] * $anlage->getPnom();
                $tableArray[$zeileSumme2]['W_eGrid']                    += $hasMonthData ? $eGridReal : $tableArray[$n]['H_eGridDesign'] * $anlage->getPnom() / $anlage->getKwPeakPvSyst();
                $tableArray[$zeileSumme2]['X_specificYield']            += $tableArray[$n]['W_eGrid'] / $anlage->getPnom();
                $tableArray[$zeileSumme2]['Y_pr']                       += $tableArray[$n]['X_specificYield'] / $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme2]['Z_tCellAvgWeighted']         += $hasMonthData ? $prArray['tCellAvg'] : $tableArray[$n]['L_tempAmbWeightedDesign'];
            }

            if ($lastMonthYear && $isYear1) {
                $isYear1 = false;
                $isYear2 = true;
                $lastMonthYear = false;
            }

            $month++;
        }

        /////////////////////////////
        /// Runde 2
        /////////////////////////////


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

            $tableArray[$n]['AA_tCompensationFactor']                 = $tableArray[$n][''];
            $tableArray[$n]['AB_effRefYield']                         = $tableArray[$n][''];
            $tableArray[$n]['AC_effTheoEnergy']                       = $tableArray[$n][''];
            $tableArray[$n]['AD_prMonth']                             = $tableArray[$n][''];
            $tableArray[$n]['AE_ratio']                               = $tableArray[$n][''];
            $tableArray[$n]['AF_ratioFT']                             = $tableArray[$n][''];
            $tableArray[$n]['AG_epcPA']                               = '';
            //Analysis
            $tableArray[$n]['AH_prForecast']                          = $tableArray[$n][''];
            $tableArray[$n]['AI_eGridForecast']                       = $tableArray[$n][''];
            $tableArray[$n]['AJ_specificYieldForecast']               = $tableArray[$n][''];
            $tableArray[$n]['AK_absDiffPrRealGuarForecast']           = $tableArray[$n][''];
            $tableArray[$n]['AL_relDiffPrRealGuarForecast']           = $tableArray[$n][''];

            if ($lastMonthYear && $isYear1) {
                $isYear1 = false;
                $isYear2 = true;
                $lastMonthYear = false;
            }

            $month++;
        }
        ksort($tableArray);

        echo self::printArrayAsTable($tableArray);
        die;
        return $tableArray;
    }
}