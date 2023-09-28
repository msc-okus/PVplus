<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
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
        private readonly AvailabilityService $availabilityService)
    {
    }

    public function createEpcReportNew(Anlage $anlage, DateTime $date): string
    {
        $output = "";
        $reportArray = [];
        $monthTable = $this->monthTable($anlage, $date)->table;
        $pldTable =
        $forcastTable =
        $reportArray['monthTable'] = $monthTable;
        $reportArray['pldTable'] = $this->pldTable($anlage, $monthTable, $date);
        $reportArray['forcastTable'] = $this->forcastTable($anlage, $monthTable, $pldTable, $date);

        // Speichere Report als 'epc-reprt' in die Report Entity
        $reportEntity = new AnlagenReports();
        $reportEntity
            ->setCreatedAt(new DateTime())
            ->setAnlage($anlage)
            ->setEigner($anlage->getEigner())
            ->setReportType('epc-new-pr')
            ->setStartDate(self::getCetTime('object'))
            ->setEndDate($anlage->getFacDate())
            ->setRawReport('')
            ->setContentArray($reportArray)
            ->setMonth($date->format('n'))
            ->setYear($date->format('Y'));
        $this->em->persist($reportEntity);
        $this->em->flush();

        return $output;
    }

    public function monthTable(Anlage $anlage, ?DateTime $date = null): array|object
    {
        if ($date === null) {
            $date = new DateTime();
        }

        $tableArray = [];
        $anzahlMonate = ((int) $anlage->getEpcReportEnd()->format('Y') - (int) $anlage->getEpcReportStart()->format('Y')) * 12 + ((int) $anlage->getEpcReportEnd()->format('m') - (int) $anlage->getEpcReportStart()->format('m'));
        $anzahlMonate = $anzahlMonate + (int) (($anzahlMonate / 12) + 0.9);
        $rollingPeriodMonthsStart = ((int) $date->format('Y') - (int) $anlage->getEpcReportStart()->format('Y')) * 12 + ((int) $date->format('m') - (int) $anlage->getEpcReportStart()->format('m')) - 11;
        $rollingPeriodMonthsEnd = ((int) $date->format('Y') - (int) $anlage->getEpcReportStart()->format('Y')) * 12 + ((int) $date->format('m') - (int) $anlage->getEpcReportStart()->format('m')) + 2;

        $zeileSumme1 = $anzahlMonate + 1;
        $zeileSumme2 = $anzahlMonate + 2;
        $zeileSumme3 = $anzahlMonate + 3;
        $zeileSumme4 = $anzahlMonate + 4;
        $zeileSumme5 = $anzahlMonate + 5;
        $zeileSumme6 = $anzahlMonate + 6;

        $startYear = $anlage->getEpcReportStart()->format('Y');
        $endYear = $anlage->getEpcReportEnd()->format('Y');
        $startMonth = (int) $anlage->getFacDateStart()->format('m');
        $yearCount = $endYear - $startYear;
        $currentMonth = (int) $date->format('m'); // (int)date('m');
        $currentYear = (int) $date->format('Y'); // (int)date('Y');
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
        $daysInReportMonth = (int) date('t', strtotime("$reportYear-$reportMonth-01"));
        $facStartMonth = (int) $anlage->getFacDateStart()->format('m');
        $facStartDay = $anlage->getFacDateStart()->format('d');
        $facEndMonth = $anlage->getFacDate()->format('m');
        $facEndDay = $anlage->getFacDate()->format('d');
        $isYear1 = true;
        $isYear2 = false;
        $lastMonthYear = false;

        $month = $startMonth;
        $year = $startYear;

        $daysInStartMonth = (int) $anlage->getFacDateStart()->format('j');
        $daysInEndMonth = (int) $anlage->getFacDate()->format('j');

        $endDateCurrentReportMonth = date_create("$reportYear-$reportMonth-$daysInReportMonth");

        if (true) { // prüfe auf PVSYST verfügbar
            $pvSystData = $anlage->getPvSystMonthsArray();
        }

        // $availabilitySummeZeil2 = $this->availabilityService->calcAvailability($anlage, $anlage->getFacDateStart(), $endDateCurrentReportMonth);

        $prPVSyst = $anlage->getDesignPR(); // 88.43;
        $prFAC = $anlage->getContractualPR(); // 84.23;
        $deductionTransform = $anlage->getTransformerTee();
        $deductionRisk = 100 * (1 - $prFAC / $prPVSyst);
        $deductionOverall = 100 - (100 - $deductionTransform) * (100 - $deductionRisk) / 100;

        // ///////////////////////////
        // / Runde 1
        // ///////////////////////////

        for ($n = 1; $n <= $anzahlMonate; ++$n) {
            if ($month > 12) {
                $month = 1;
                ++$year;
            }

            $daysInMonth = (int) date('t', strtotime("$year-$month-01"));
            $from_local = date_create(date('Y-m-d 00:00', strtotime("$year-$month-01")));
            $to_local = date_create(date('Y-m-d 23:59', strtotime("$year-$month-$daysInMonth")));
            $hasMonthData = $from_local <= $date; // Wenn das Datum in $from_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true
            $isCurrentMonth = $to_local->format('Y') == $currentYear && $to_local->format('m') == $currentMonth;
            // last 12 month,
            if ($rollingPeriodMonthsStart > 0) {
                $rollingPeriod = $rollingPeriodMonthsStart < $n && $rollingPeriodMonthsEnd >= $n;
            } else {
                $rollingPeriod = $n <= $rollingPeriodMonthsEnd || $n >= $anzahlMonate + $rollingPeriodMonthsStart;
            }

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

            $tableArray[$n]['B_month'] = date('m / Y', strtotime("$year-$month-1"));
            $tableArray[$n]['C_days_month'] = $daysInMonth;
            // DesignValues
            $tableArray[$n]['D_days_fac'] = $days;
            $tableArray[$n]['E_IrrDesign'] = $pvSystData[$month]['irrDesign'] * $factor;
            $tableArray[$n]['F_refYieldDesign'] = $tableArray[$n]['E_IrrDesign'];
            $tableArray[$n]['G_theoEnergyDesign'] = $tableArray[$n]['F_refYieldDesign'] * $anlage->getKwPeakPvSyst();
            $tableArray[$n]['H_eGridDesign'] = $pvSystData[$month]['ertragDesign'] * $factor;
            $tableArray[$n]['I_specificYieldDesign'] = $tableArray[$n]['H_eGridDesign'] / $anlage->getKwPeakPvSyst();
            $tableArray[$n]['J_prDesign'] = $tableArray[$n]['H_eGridDesign'] / ($tableArray[$n]['E_IrrDesign'] * $anlage->getKwPeakPvSyst()) * 100;
            $tableArray[$n]['K_tempAmbDesign'] = $pvSystData[$month]['tempAmbDesign'];
            $tableArray[$n]['L_tempAmbWeightedDesign'] = $pvSystData[$month]['tempAmbWeightedDesign'];
            $tableArray[$n]['M_tempCompFactDesign'] = (1 + ($tableArray[$n]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
            $tableArray[$n]['N_effRefYieldDesign'] = $tableArray[$n]['F_refYieldDesign'] * $tableArray[$n]['M_tempCompFactDesign'];
            $tableArray[$n]['O_effTheoEnergyDesign'] = $tableArray[$n]['N_effRefYieldDesign'] * $anlage->getKwPeakPvSyst();
            $tableArray[$n]['P_prMonthDesign'] = $tableArray[$n]['I_specificYieldDesign'] / $tableArray[$n]['N_effRefYieldDesign'] * 100;
            $tableArray[$n]['Q_prGuarDesign'] = $tableArray[$n]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
            $tableArray[$n]['R_specificYieldGuarDesign'] = $tableArray[$n]['Q_prGuarDesign'] / 100 * $tableArray[$n]['N_effRefYieldDesign'];
            $tableArray[$n]['S_eGridGuarDesign'] = $tableArray[$n]['R_specificYieldGuarDesign'] * $anlage->getKwPeakPvSyst();
            // MeasuermentValues
            $tableArray[$n]['T_irr'] = $hasMonthData ? $prArray['irradiation'] : $tableArray[$n]['E_IrrDesign'];
            $tableArray[$n]['U_refYield'] = $tableArray[$n]['T_irr'];
            $tableArray[$n]['V_theoEnergy'] = $tableArray[$n]['T_irr'] * $anlage->getPnom();
            $tableArray[$n]['W_eGrid'] = $hasMonthData ? $eGridReal : $tableArray[$n]['H_eGridDesign'] * $anlage->getPnom() / $anlage->getKwPeakPvSyst();
            $tableArray[$n]['X_specificYield'] = $tableArray[$n]['W_eGrid'] / $anlage->getPnom();
            $tableArray[$n]['Y_pr'] = $tableArray[$n]['X_specificYield'] / $tableArray[$n]['U_refYield'] * 100;

            $tableArray[$n]['Z_tCellAvgWeighted'] = $hasMonthData ? ($prArray['tCellAvgMultiIrr'] / ($tableArray[$n]['T_irr'] * 4000)) : $tableArray[$n]['L_tempAmbWeightedDesign']; // Strahlung (in kWh/qm) mit 4000 Multipizieren um W/qm zu bekommen
            $tableArray[$n]['AA_tCompensationFactor'] = 0;
            $tableArray[$n]['AB_effRefYield'] = 0;
            $tableArray[$n]['AC_effTheoEnergy'] = 0;
            $tableArray[$n]['AC1_theoEnergyMeasured'] = $hasMonthData ? $prArray['powerTheoTempCorr'] : 0;
            $tableArray[$n]['AD_prMonth'] = 0;
            $tableArray[$n]['AE_ratio'] = 0;
            $tableArray[$n]['AF_ratioFT'] = 0;
            $tableArray[$n]['AG_epcPA'] = 0;
            // Analysis
            $tableArray[$n]['AH_prForecast'] = 0;
            $tableArray[$n]['AI_eGridForecast'] = 0;
            $tableArray[$n]['AJ_specificYieldForecast'] = 0;
            $tableArray[$n]['AK_absDiffPrRealGuarForecast'] = 0;
            $tableArray[$n]['AL_relDiffPrRealGuarForecast'] = 0;

            // Steuerrung
            $tableArray[$n]['current_month'] = $isCurrentMonth ? -1 : 0;
            $tableArray[$n]['style'] = '';

            // ###########
            if ($n <= 13) { // Year 1
                $tableArray[$zeileSumme1]['B_month'] = 'Year 1';
                $tableArray[$zeileSumme1]['C_days_month'] = 0;
                // DesignValues
                $tableArray[$zeileSumme1]['D_days_fac'] += $tableArray[$n]['D_days_fac'];
                $tableArray[$zeileSumme1]['E_IrrDesign'] += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme1]['F_refYieldDesign'] += $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme1]['G_theoEnergyDesign'] += $tableArray[$n]['G_theoEnergyDesign'];
                $tableArray[$zeileSumme1]['H_eGridDesign'] += $tableArray[$n]['H_eGridDesign'];
                $tableArray[$zeileSumme1]['I_specificYieldDesign'] += $tableArray[$n]['I_specificYieldDesign'];
                $tableArray[$zeileSumme1]['J_prDesign'] = $tableArray[$zeileSumme1]['I_specificYieldDesign'] / $tableArray[$zeileSumme1]['F_refYieldDesign'] * 100;
                $tableArray[$zeileSumme1]['K_tempAmbDesign'] = 0;
                $tableArray[$zeileSumme1]['L_tempAmbWeightedDesign'] = 0;
                $tableArray[$zeileSumme1]['M_tempCompFactDesign'] = 0;
                $k_tempAmbDesign1 += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac']; // ########## in zweiter Runde noch durch summe Tage teilen
                $l_tempAmbWeightedDesign1 += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign']; // ########## in zweiter Runde noch durch summe Strahlung teilen
                $tableArray[$zeileSumme1]['N_effRefYieldDesign'] += $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme1]['O_effTheoEnergyDesign'] += $tableArray[$n]['O_effTheoEnergyDesign'];
                $tableArray[$zeileSumme1]['P_prMonthDesign'] = $tableArray[$zeileSumme1]['I_specificYieldDesign'] / $tableArray[$zeileSumme1]['N_effRefYieldDesign'] * 100;
                $tableArray[$zeileSumme1]['Q_prGuarDesign'] = $tableArray[$zeileSumme1]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme1]['R_specificYieldGuarDesign'] += $tableArray[$n]['R_specificYieldGuarDesign'];
                $tableArray[$zeileSumme1]['S_eGridGuarDesign'] += $tableArray[$n]['S_eGridGuarDesign'];
                // MeasuermentValues
                $tableArray[$zeileSumme1]['T_irr'] += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme1]['U_refYield'] += $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme1]['V_theoEnergy'] += $tableArray[$n]['V_theoEnergy'];
                $tableArray[$zeileSumme1]['W_eGrid'] += $tableArray[$n]['W_eGrid'];
                $tableArray[$zeileSumme1]['X_specificYield'] += $tableArray[$n]['X_specificYield'];
                $tableArray[$zeileSumme1]['Y_pr'] = $tableArray[$zeileSumme1]['X_specificYield'] / $tableArray[$zeileSumme1]['U_refYield'] * 100;
                $tableArray[$zeileSumme1]['Z_tCellAvgWeighted'] = 0;
                $z_tCellAvgWeighted1 += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield']; // ########## in zweiter Runde noch durch summe aus 'U_refYield' teilen
                $tableArray[$zeileSumme1]['AA_tCompensationFactor'] = 0;
                $tableArray[$zeileSumme1]['AB_effRefYield'] = 0;
                $tableArray[$zeileSumme1]['AC_effTheoEnergy'] = 0;
                $tableArray[$zeileSumme1]['AC1_theoEnergyMeasured'] += $tableArray[$n]['AC1_theoEnergyMeasured'];
                $tableArray[$zeileSumme1]['AD_prMonth'] = 0;
                $tableArray[$zeileSumme1]['AE_ratio'] = 0;
                $tableArray[$zeileSumme1]['AF_ratioFT'] = 0;
                $tableArray[$zeileSumme1]['AG_epcPA'] = 0;
                // Analysis
                $tableArray[$zeileSumme1]['AH_prForecast'] = 0;
                $tableArray[$zeileSumme1]['AI_eGridForecast'] = 0;
                $tableArray[$zeileSumme1]['AJ_specificYieldForecast'] = 0;
                $tableArray[$zeileSumme1]['AK_absDiffPrRealGuarForecast'] = 0;
                $tableArray[$zeileSumme1]['AL_relDiffPrRealGuarForecast'] = 0;

                $tableArray[$zeileSumme1]['current_month'] = 0;
                $tableArray[$zeileSumme1]['style'] = '';
            }

            if ($n >= 14) { // Year 2
                $tableArray[$zeileSumme2]['B_month'] = 'Year 2';
                $tableArray[$zeileSumme2]['C_days_month'] = 0;
                // DesignValues
                $tableArray[$zeileSumme2]['D_days_fac'] += $tableArray[$n]['D_days_fac'];
                $tableArray[$zeileSumme2]['E_IrrDesign'] += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme2]['F_refYieldDesign'] += $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme2]['G_theoEnergyDesign'] += $tableArray[$n]['G_theoEnergyDesign'];
                $tableArray[$zeileSumme2]['H_eGridDesign'] += $tableArray[$n]['H_eGridDesign'];
                $tableArray[$zeileSumme2]['I_specificYieldDesign'] += $tableArray[$n]['I_specificYieldDesign'];
                $tableArray[$zeileSumme2]['J_prDesign'] = $tableArray[$zeileSumme2]['I_specificYieldDesign'] / $tableArray[$zeileSumme2]['F_refYieldDesign'] * 100;
                $tableArray[$zeileSumme2]['K_tempAmbDesign'] = 0;
                $tableArray[$zeileSumme2]['L_tempAmbWeightedDesign'] = 0;
                $tableArray[$zeileSumme2]['M_tempCompFactDesign'] = 0;
                $k_tempAmbDesign2 += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac']; // ########## in zweiter Runde noch durch summe Tage teilen
                $l_tempAmbWeightedDesign2 += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign']; // ########## in zweiter Runde noch durch summe Strahlung teilen
                $tableArray[$zeileSumme2]['N_effRefYieldDesign'] += $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme2]['O_effTheoEnergyDesign'] += $tableArray[$n]['O_effTheoEnergyDesign'];
                $tableArray[$zeileSumme2]['P_prMonthDesign'] = $tableArray[$zeileSumme2]['I_specificYieldDesign'] / $tableArray[$zeileSumme2]['N_effRefYieldDesign'] * 100;
                $tableArray[$zeileSumme2]['Q_prGuarDesign'] = $tableArray[$zeileSumme2]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme2]['R_specificYieldGuarDesign'] += $tableArray[$n]['R_specificYieldGuarDesign'];
                $tableArray[$zeileSumme2]['S_eGridGuarDesign'] += $tableArray[$n]['S_eGridGuarDesign'];
                // MeasuermentValues
                $tableArray[$zeileSumme2]['T_irr'] += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme2]['U_refYield'] += $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme2]['V_theoEnergy'] += $tableArray[$n]['V_theoEnergy'];
                $tableArray[$zeileSumme2]['W_eGrid'] += $tableArray[$n]['W_eGrid'];
                $tableArray[$zeileSumme2]['X_specificYield'] += $tableArray[$n]['X_specificYield'];
                $tableArray[$zeileSumme2]['Y_pr'] = $tableArray[$zeileSumme2]['X_specificYield'] / $tableArray[$zeileSumme2]['U_refYield'] * 100;
                $tableArray[$zeileSumme2]['Z_tCellAvgWeighted'] = 0;
                $z_tCellAvgWeighted2 += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield']; // ########## in zweiter Runde noch durch summe aus 'U_refYield' teilen
                $tableArray[$zeileSumme2]['AA_tCompensationFactor'] = 0;
                $tableArray[$zeileSumme2]['AB_effRefYield'] = 0;
                $tableArray[$zeileSumme2]['AC_effTheoEnergy'] = 0;
                $tableArray[$zeileSumme2]['AC1_theoEnergyMeasured'] += $tableArray[$n]['AC1_theoEnergyMeasured'];
                $tableArray[$zeileSumme2]['AD_prMonth'] = 0;
                $tableArray[$zeileSumme2]['AE_ratio'] = 0;
                $tableArray[$zeileSumme2]['AF_ratioFT'] = 0;
                $tableArray[$zeileSumme2]['AG_epcPA'] = 0;
                // Analysis
                $tableArray[$zeileSumme2]['AH_prForecast'] = 0;
                $tableArray[$zeileSumme2]['AI_eGridForecast'] = 0;
                $tableArray[$zeileSumme2]['AJ_specificYieldForecast'] = 0;
                $tableArray[$zeileSumme2]['AK_absDiffPrRealGuarForecast'] = 0;
                $tableArray[$zeileSumme2]['AL_relDiffPrRealGuarForecast'] = 0;

                $tableArray[$zeileSumme2]['current_month'] = 0;
                $tableArray[$zeileSumme2]['style'] = '';
            }

            $tableArray[$zeileSumme3]['B_month'] = 'Both Years';
            $tableArray[$zeileSumme3]['C_days_month'] = 0;
            // DesignValues
            $tableArray[$zeileSumme3]['D_days_fac'] += $tableArray[$n]['D_days_fac'];
            $tableArray[$zeileSumme3]['E_IrrDesign'] += $tableArray[$n]['E_IrrDesign'];
            $tableArray[$zeileSumme3]['F_refYieldDesign'] += $tableArray[$n]['F_refYieldDesign'];
            $tableArray[$zeileSumme3]['G_theoEnergyDesign'] += $tableArray[$n]['G_theoEnergyDesign'];
            $tableArray[$zeileSumme3]['H_eGridDesign'] += $tableArray[$n]['H_eGridDesign'];
            $tableArray[$zeileSumme3]['I_specificYieldDesign'] += $tableArray[$n]['I_specificYieldDesign'];
            $tableArray[$zeileSumme3]['J_prDesign'] = $tableArray[$zeileSumme3]['I_specificYieldDesign'] / $tableArray[$zeileSumme3]['F_refYieldDesign'] * 100;
            $tableArray[$zeileSumme3]['K_tempAmbDesign'] = 0;
            $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign'] = 0;
            $tableArray[$zeileSumme3]['M_tempCompFactDesign'] = 0;
            $k_tempAmbDesign3 += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac']; // ########## in zweiter Runde noch durch summe Tage teilen
            $l_tempAmbWeightedDesign3 += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign']; // ########## in zweiter Runde noch durch summe Strahlung teilen
            $tableArray[$zeileSumme3]['N_effRefYieldDesign'] += $tableArray[$n]['N_effRefYieldDesign'];
            $tableArray[$zeileSumme3]['O_effTheoEnergyDesign'] += $tableArray[$n]['O_effTheoEnergyDesign'];
            $tableArray[$zeileSumme3]['P_prMonthDesign'] = $tableArray[$zeileSumme3]['I_specificYieldDesign'] / $tableArray[$zeileSumme3]['N_effRefYieldDesign'] * 100;
            $tableArray[$zeileSumme3]['Q_prGuarDesign'] = $tableArray[$zeileSumme3]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
            $tableArray[$zeileSumme3]['R_specificYieldGuarDesign'] += $tableArray[$n]['R_specificYieldGuarDesign'];
            $tableArray[$zeileSumme3]['S_eGridGuarDesign'] += $tableArray[$n]['S_eGridGuarDesign'];
            // MeasuermentValues
            $tableArray[$zeileSumme3]['T_irr'] += $tableArray[$n]['T_irr'];
            $tableArray[$zeileSumme3]['U_refYield'] += $tableArray[$n]['U_refYield'];
            $tableArray[$zeileSumme3]['V_theoEnergy'] += $tableArray[$n]['V_theoEnergy'];
            $tableArray[$zeileSumme3]['W_eGrid'] += $tableArray[$n]['W_eGrid'];
            $tableArray[$zeileSumme3]['X_specificYield'] += $tableArray[$n]['X_specificYield'];
            $tableArray[$zeileSumme3]['Y_pr'] = $tableArray[$zeileSumme3]['X_specificYield'] / $tableArray[$zeileSumme3]['U_refYield'] * 100;
            $tableArray[$zeileSumme3]['Z_tCellAvgWeighted'] = 0;
            $z_tCellAvgWeighted3 += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield']; // ########## in zweiter Runde noch durch summe aus 'U_refYield' teilen
            $tableArray[$zeileSumme3]['AA_tCompensationFactor'] = 0;
            $tableArray[$zeileSumme3]['AB_effRefYield'] = 0;
            $tableArray[$zeileSumme3]['AC_effTheoEnergy'] = 0;
            $tableArray[$zeileSumme3]['AC1_theoEnergyMeasured'] += $tableArray[$n]['AC1_theoEnergyMeasured'];
            $tableArray[$zeileSumme3]['AD_prMonth'] = 0;
            $tableArray[$zeileSumme3]['AE_ratio'] = 0;
            $tableArray[$zeileSumme3]['AF_ratioFT'] = 0;
            $tableArray[$zeileSumme3]['AG_epcPA'] = 0;
            // Analysis
            $tableArray[$zeileSumme3]['AH_prForecast'] = 0;
            $tableArray[$zeileSumme3]['AI_eGridForecast'] = 0;
            $tableArray[$zeileSumme3]['AJ_specificYieldForecast'] = 0;
            $tableArray[$zeileSumme3]['AK_absDiffPrRealGuarForecast'] = 0;
            $tableArray[$zeileSumme3]['AL_relDiffPrRealGuarForecast'] = 0;

            $tableArray[$zeileSumme3]['current_month'] = 0;
            $tableArray[$zeileSumme3]['style'] = '';

            if ($rollingPeriod) {
                $tableArray[$zeileSumme4]['B_month'] = 'Rolling period';
                $tableArray[$zeileSumme4]['C_days_month'] = 0;
                // DesignValues
                $tableArray[$zeileSumme4]['D_days_fac'] += $tableArray[$n]['D_days_fac'];
                $tableArray[$zeileSumme4]['E_IrrDesign'] += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme4]['F_refYieldDesign'] += $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme4]['G_theoEnergyDesign'] += $tableArray[$n]['G_theoEnergyDesign'];
                $tableArray[$zeileSumme4]['H_eGridDesign'] += $tableArray[$n]['H_eGridDesign'];
                $tableArray[$zeileSumme4]['I_specificYieldDesign'] += $tableArray[$n]['I_specificYieldDesign'];
                $tableArray[$zeileSumme4]['J_prDesign'] = $tableArray[$zeileSumme4]['I_specificYieldDesign'] / $tableArray[$zeileSumme4]['F_refYieldDesign'] * 100;
                $tableArray[$zeileSumme4]['K_tempAmbDesign'] = 0;
                $tableArray[$zeileSumme4]['L_tempAmbWeightedDesign'] = 0;
                $tableArray[$zeileSumme4]['M_tempCompFactDesign'] = 0;
                $k_tempAmbDesign4 += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac']; // ########## in zweiter Runde noch durch summe Tage teilen
                $l_tempAmbWeightedDesign4 += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign']; // ########## in zweiter Runde noch durch summe Strahlung teilen
                $tableArray[$zeileSumme4]['N_effRefYieldDesign'] += $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme4]['O_effTheoEnergyDesign'] += $tableArray[$n]['O_effTheoEnergyDesign'];
                $tableArray[$zeileSumme4]['P_prMonthDesign'] = $tableArray[$zeileSumme4]['I_specificYieldDesign'] / $tableArray[$zeileSumme4]['N_effRefYieldDesign'] * 100;
                $tableArray[$zeileSumme4]['Q_prGuarDesign'] = $tableArray[$zeileSumme4]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme4]['R_specificYieldGuarDesign'] += $tableArray[$n]['R_specificYieldGuarDesign'];
                $tableArray[$zeileSumme4]['S_eGridGuarDesign'] += $tableArray[$n]['S_eGridGuarDesign'];
                // MeasuermentValues
                $tableArray[$zeileSumme4]['T_irr'] += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme4]['U_refYield'] += $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme4]['V_theoEnergy'] += $tableArray[$n]['V_theoEnergy'];
                $tableArray[$zeileSumme4]['W_eGrid'] += $tableArray[$n]['W_eGrid'];
                $tableArray[$zeileSumme4]['X_specificYield'] += $tableArray[$n]['X_specificYield'];
                $tableArray[$zeileSumme4]['Y_pr'] = $tableArray[$zeileSumme4]['X_specificYield'] / $tableArray[$zeileSumme4]['U_refYield'] * 100;
                $tableArray[$zeileSumme4]['Z_tCellAvgWeighted'] = 0;
                $z_tCellAvgWeighted4 += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield']; // ########## in zweiter Runde noch durch summe aus 'U_refYield' teilen
                $tableArray[$zeileSumme4]['AA_tCompensationFactor'] = 0;
                $tableArray[$zeileSumme4]['AB_effRefYield'] = 0;
                $tableArray[$zeileSumme4]['AC_effTheoEnergy'] = 0;
                $tableArray[$zeileSumme4]['AC1_theoEnergyMeasured'] += $tableArray[$n]['AC1_theoEnergyMeasured'];
                $tableArray[$zeileSumme4]['AD_prMonth'] = 0;
                $tableArray[$zeileSumme4]['AE_ratio'] = 0;
                $tableArray[$zeileSumme4]['AF_ratioFT'] = 0;
                $tableArray[$zeileSumme4]['AG_epcPA'] = 0;
                // Analysis
                $tableArray[$zeileSumme4]['AH_prForecast'] = 0;
                $tableArray[$zeileSumme4]['AI_eGridForecast'] = 0;
                $tableArray[$zeileSumme4]['AJ_specificYieldForecast'] = 0;
                $tableArray[$zeileSumme4]['AK_absDiffPrRealGuarForecast'] = 0;
                $tableArray[$zeileSumme4]['AL_relDiffPrRealGuarForecast'] = 0;

                $tableArray[$zeileSumme4]['current_month'] = 0;
                $tableArray[$zeileSumme4]['style'] = '';
            }

            if ($hasMonthData) {
                $tableArray[$zeileSumme5]['B_month'] = 'Current up to date';
                $tableArray[$zeileSumme5]['C_days_month'] = 0;
                // DesignValues
                $tableArray[$zeileSumme5]['D_days_fac'] += $tableArray[$n]['D_days_fac'];
                $tableArray[$zeileSumme5]['E_IrrDesign'] += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme5]['F_refYieldDesign'] += $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme5]['G_theoEnergyDesign'] += $tableArray[$n]['G_theoEnergyDesign'];
                $tableArray[$zeileSumme5]['H_eGridDesign'] += $tableArray[$n]['H_eGridDesign'];
                $tableArray[$zeileSumme5]['I_specificYieldDesign'] += $tableArray[$n]['I_specificYieldDesign'];
                $tableArray[$zeileSumme5]['J_prDesign'] = $tableArray[$zeileSumme5]['I_specificYieldDesign'] / $tableArray[$zeileSumme5]['F_refYieldDesign'] * 100;
                $tableArray[$zeileSumme5]['K_tempAmbDesign'] = 0;
                $tableArray[$zeileSumme5]['L_tempAmbWeightedDesign'] = 0;
                $tableArray[$zeileSumme5]['M_tempCompFactDesign'] = 0;
                $k_tempAmbDesign5 += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac']; // ########## in zweiter Runde noch durch summe Tage teilen
                $l_tempAmbWeightedDesign5 += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign']; // ########## in zweiter Runde noch durch summe Strahlung teilen
                $tableArray[$zeileSumme5]['N_effRefYieldDesign'] += $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme5]['O_effTheoEnergyDesign'] += $tableArray[$n]['O_effTheoEnergyDesign'];
                $tableArray[$zeileSumme5]['P_prMonthDesign'] = $tableArray[$zeileSumme5]['I_specificYieldDesign'] / $tableArray[$zeileSumme5]['N_effRefYieldDesign'] * 100;
                $tableArray[$zeileSumme5]['Q_prGuarDesign'] = $tableArray[$zeileSumme5]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme5]['R_specificYieldGuarDesign'] += $tableArray[$n]['R_specificYieldGuarDesign'];
                $tableArray[$zeileSumme5]['S_eGridGuarDesign'] += $tableArray[$n]['S_eGridGuarDesign'];
                // MeasuermentValues
                $tableArray[$zeileSumme5]['T_irr'] += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme5]['U_refYield'] += $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme5]['V_theoEnergy'] += $tableArray[$n]['V_theoEnergy'];
                $tableArray[$zeileSumme5]['W_eGrid'] += $tableArray[$n]['W_eGrid'];
                $tableArray[$zeileSumme5]['X_specificYield'] += $tableArray[$n]['X_specificYield'];
                $tableArray[$zeileSumme5]['Y_pr'] = $tableArray[$zeileSumme5]['X_specificYield'] / $tableArray[$zeileSumme5]['U_refYield'] * 100;
                $tableArray[$zeileSumme5]['Z_tCellAvgWeighted'] = 0;
                $z_tCellAvgWeighted5 += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield']; // ########## in zweiter Runde noch durch summe aus 'U_refYield' teilen
                $tableArray[$zeileSumme5]['AA_tCompensationFactor'] = 0;
                $tableArray[$zeileSumme5]['AB_effRefYield'] = 0;
                $tableArray[$zeileSumme5]['AC_effTheoEnergy'] = 0;
                $tableArray[$zeileSumme5]['AC1_theoEnergyMeasured'] += $tableArray[$n]['AC1_theoEnergyMeasured'];
                $tableArray[$zeileSumme5]['AD_prMonth'] = 0;
                $tableArray[$zeileSumme5]['AE_ratio'] = 0;
                $tableArray[$zeileSumme5]['AF_ratioFT'] = 0;
                $tableArray[$zeileSumme5]['AG_epcPA'] = 0;
                // Analysis
                $tableArray[$zeileSumme5]['AH_prForecast'] = 0;
                $tableArray[$zeileSumme5]['AI_eGridForecast'] = 0;
                $tableArray[$zeileSumme5]['AJ_specificYieldForecast'] = 0;
                $tableArray[$zeileSumme5]['AK_absDiffPrRealGuarForecast'] = 0;
                $tableArray[$zeileSumme5]['AL_relDiffPrRealGuarForecast'] = 0;

                $tableArray[$zeileSumme5]['current_month'] = 0;
                $tableArray[$zeileSumme5]['style'] = '';
            } else {
                $tableArray[$zeileSumme6]['B_month'] = 'Forcast period<br>(after current date)';
                $tableArray[$zeileSumme6]['C_days_month'] = 0;
                // DesignValues
                $tableArray[$zeileSumme6]['D_days_fac'] += $tableArray[$n]['D_days_fac'];
                $tableArray[$zeileSumme6]['E_IrrDesign'] += $tableArray[$n]['E_IrrDesign'];
                $tableArray[$zeileSumme6]['F_refYieldDesign'] += $tableArray[$n]['F_refYieldDesign'];
                $tableArray[$zeileSumme6]['G_theoEnergyDesign'] += $tableArray[$n]['G_theoEnergyDesign'];
                $tableArray[$zeileSumme6]['H_eGridDesign'] += $tableArray[$n]['H_eGridDesign'];
                $tableArray[$zeileSumme6]['I_specificYieldDesign'] += $tableArray[$n]['I_specificYieldDesign'];
                $tableArray[$zeileSumme6]['J_prDesign'] = $tableArray[$zeileSumme6]['I_specificYieldDesign'] / $tableArray[$zeileSumme6]['F_refYieldDesign'] * 100;
                $tableArray[$zeileSumme6]['K_tempAmbDesign'] = 0;
                $tableArray[$zeileSumme6]['L_tempAmbWeightedDesign'] = 0;
                $tableArray[$zeileSumme6]['M_tempCompFactDesign'] = 0;
                $k_tempAmbDesign6 += $tableArray[$n]['K_tempAmbDesign'] * $tableArray[$n]['D_days_fac']; // ########## in zweiter Runde noch durch summe Tage teilen
                $l_tempAmbWeightedDesign6 += $tableArray[$n]['L_tempAmbWeightedDesign'] * $tableArray[$n]['E_IrrDesign']; // ########## in zweiter Runde noch durch summe Strahlung teilen
                $tableArray[$zeileSumme6]['N_effRefYieldDesign'] += $tableArray[$n]['N_effRefYieldDesign'];
                $tableArray[$zeileSumme6]['O_effTheoEnergyDesign'] += $tableArray[$n]['O_effTheoEnergyDesign'];
                $tableArray[$zeileSumme6]['P_prMonthDesign'] = $tableArray[$zeileSumme6]['I_specificYieldDesign'] / $tableArray[$zeileSumme6]['N_effRefYieldDesign'] * 100;
                $tableArray[$zeileSumme6]['Q_prGuarDesign'] = $tableArray[$zeileSumme6]['P_prMonthDesign'] * (1 - $deductionOverall / 100);
                $tableArray[$zeileSumme6]['R_specificYieldGuarDesign'] += $tableArray[$n]['R_specificYieldGuarDesign'];
                $tableArray[$zeileSumme6]['S_eGridGuarDesign'] += $tableArray[$n]['S_eGridGuarDesign'];
                // MeasuermentValues
                $tableArray[$zeileSumme6]['T_irr'] += $tableArray[$n]['T_irr'];
                $tableArray[$zeileSumme6]['U_refYield'] += $tableArray[$n]['U_refYield'];
                $tableArray[$zeileSumme6]['V_theoEnergy'] += $tableArray[$n]['V_theoEnergy'];
                $tableArray[$zeileSumme6]['W_eGrid'] += $tableArray[$n]['W_eGrid'];
                $tableArray[$zeileSumme6]['X_specificYield'] += $tableArray[$n]['X_specificYield'];
                $tableArray[$zeileSumme6]['Y_pr'] = $tableArray[$zeileSumme6]['X_specificYield'] / $tableArray[$zeileSumme6]['U_refYield'] * 100;
                $tableArray[$zeileSumme6]['Z_tCellAvgWeighted'] = 0;
                $z_tCellAvgWeighted6 += $tableArray[$n]['Z_tCellAvgWeighted'] * $tableArray[$n]['U_refYield']; // ########## in zweiter Runde noch durch summe aus 'U_refYield' teilen
                $tableArray[$zeileSumme6]['AA_tCompensationFactor'] = 0;
                $tableArray[$zeileSumme6]['AB_effRefYield'] = 0;
                $tableArray[$zeileSumme6]['AC_effTheoEnergy'] = 0;
                $tableArray[$zeileSumme6]['AC1_theoEnergyMeasured'] += $tableArray[$n]['AC1_theoEnergyMeasured'];
                $tableArray[$zeileSumme6]['AD_prMonth'] = 0;
                $tableArray[$zeileSumme6]['AE_ratio'] = 0;
                $tableArray[$zeileSumme6]['AF_ratioFT'] = 0;
                $tableArray[$zeileSumme6]['AG_epcPA'] = 0;
                // Analysis
                $tableArray[$zeileSumme6]['AH_prForecast'] = 0;
                $tableArray[$zeileSumme6]['AI_eGridForecast'] = 0;
                $tableArray[$zeileSumme6]['AJ_specificYieldForecast'] = 0;
                $tableArray[$zeileSumme6]['AK_absDiffPrRealGuarForecast'] = 0;
                $tableArray[$zeileSumme6]['AL_relDiffPrRealGuarForecast'] = 0;

                $tableArray[$zeileSumme6]['current_month'] = 0;
                $tableArray[$zeileSumme6]['style'] = '';
            }

            if ($n != 13 && $n != 26) {
                ++$month;
            }
        }

        // ///////////////////////////
        // / Runde 2
        // ///////////////////////////
        $month = $startMonth;
        $year = $startYear;

        for ($n = 1; $n <= $anzahlMonate; ++$n) {
            if ($month > 12) {
                $month = 1;
                ++$year;
            }

            $from_local = date_create(date('Y-m-d 00:00', strtotime("$year-$month-01")));
            $hasMonthData = $from_local <= $date; // Wenn das Datum in $from_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true
            if ($rollingPeriodMonthsStart > 0) {
                $rollingPeriod = $rollingPeriodMonthsStart < $n && $rollingPeriodMonthsEnd >= $n;
            } else {
                $rollingPeriod = $n <= $rollingPeriodMonthsEnd || $n >= $anzahlMonate + $rollingPeriodMonthsStart;
            }

            $tableArray[$n]['AE_ratio'] = $tableArray[$n]['U_refYield'] / $tableArray[$zeileSumme3]['U_refYield'] * 100;

            if ($n <= 13) { // Year 1
                $tableArray[$zeileSumme1]['K_tempAmbDesign'] = $k_tempAmbDesign1 / $tableArray[$zeileSumme1]['D_days_fac'];
                $tableArray[$zeileSumme1]['L_tempAmbWeightedDesign'] = $l_tempAmbWeightedDesign1 / $tableArray[$zeileSumme1]['E_IrrDesign'];
                $tableArray[$zeileSumme1]['Z_tCellAvgWeighted'] = $z_tCellAvgWeighted1 / $tableArray[$zeileSumme1]['U_refYield'];
                $tableArray[$zeileSumme1]['M_tempCompFactDesign'] = (1 + ($tableArray[$zeileSumme1]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
                $tableArray[$zeileSumme1]['AE_ratio'] += $tableArray[$n]['AE_ratio'];
            }

            if ($n >= 14) { // Year 2
                $tableArray[$zeileSumme2]['K_tempAmbDesign'] = $k_tempAmbDesign2 / $tableArray[$zeileSumme2]['D_days_fac'];
                $tableArray[$zeileSumme2]['L_tempAmbWeightedDesign'] = $l_tempAmbWeightedDesign2 / $tableArray[$zeileSumme2]['E_IrrDesign'];
                $tableArray[$zeileSumme2]['Z_tCellAvgWeighted'] = $z_tCellAvgWeighted2 / $tableArray[$zeileSumme2]['U_refYield'];
                $tableArray[$zeileSumme2]['M_tempCompFactDesign'] = (1 + ($tableArray[$zeileSumme2]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
                $tableArray[$zeileSumme2]['AE_ratio'] += $tableArray[$n]['AE_ratio'];
            }

            $tableArray[$zeileSumme3]['K_tempAmbDesign'] = $k_tempAmbDesign3 / $tableArray[$zeileSumme3]['D_days_fac'];
            $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign'] = $l_tempAmbWeightedDesign3 / $tableArray[$zeileSumme3]['E_IrrDesign'];
            $tableArray[$zeileSumme3]['Z_tCellAvgWeighted'] = $z_tCellAvgWeighted3 / $tableArray[$zeileSumme3]['U_refYield'];
            $tableArray[$zeileSumme3]['M_tempCompFactDesign'] = (1 + ($tableArray[$zeileSumme3]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
            $tableArray[$zeileSumme3]['AE_ratio'] += $tableArray[$n]['AE_ratio'];

            if ($rollingPeriod) {
                $tableArray[$zeileSumme4]['K_tempAmbDesign'] = $k_tempAmbDesign4 / $tableArray[$zeileSumme4]['D_days_fac'];
                $tableArray[$zeileSumme4]['L_tempAmbWeightedDesign'] = $l_tempAmbWeightedDesign4 / $tableArray[$zeileSumme4]['E_IrrDesign'];
                $tableArray[$zeileSumme4]['Z_tCellAvgWeighted'] = $z_tCellAvgWeighted4 / $tableArray[$zeileSumme4]['U_refYield'];
                $tableArray[$zeileSumme4]['M_tempCompFactDesign'] = (1 + ($tableArray[$zeileSumme4]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
                $tableArray[$zeileSumme4]['AE_ratio'] += $tableArray[$n]['AE_ratio'];
            }

            if ($hasMonthData) {
                $tableArray[$zeileSumme5]['K_tempAmbDesign'] = $k_tempAmbDesign5 / $tableArray[$zeileSumme5]['D_days_fac'];
                $tableArray[$zeileSumme5]['L_tempAmbWeightedDesign'] = $l_tempAmbWeightedDesign5 / $tableArray[$zeileSumme5]['E_IrrDesign'];
                $tableArray[$zeileSumme5]['Z_tCellAvgWeighted'] = $z_tCellAvgWeighted5 / $tableArray[$zeileSumme5]['U_refYield'];
                $tableArray[$zeileSumme5]['M_tempCompFactDesign'] = (1 + ($tableArray[$zeileSumme5]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
                $tableArray[$zeileSumme5]['AE_ratio'] += $tableArray[$n]['AE_ratio'];
            } else {
                $tableArray[$zeileSumme6]['K_tempAmbDesign'] = $k_tempAmbDesign6 / $tableArray[$zeileSumme6]['D_days_fac'];
                $tableArray[$zeileSumme6]['L_tempAmbWeightedDesign'] = $l_tempAmbWeightedDesign6 / $tableArray[$zeileSumme6]['E_IrrDesign'];
                $tableArray[$zeileSumme6]['Z_tCellAvgWeighted'] = $z_tCellAvgWeighted2 / $tableArray[$zeileSumme2]['U_refYield'];
                $tableArray[$zeileSumme6]['M_tempCompFactDesign'] = (1 + ($tableArray[$zeileSumme6]['L_tempAmbWeightedDesign'] - $anlage->getTempCorrCellTypeAvg()) * $anlage->getTempCorrGamma() / 100);
                $tableArray[$zeileSumme6]['AE_ratio'] += $tableArray[$n]['AE_ratio'];
            }

            $tableArray[$n]['AA_tCompensationFactor'] = 1 + ($tableArray[$n]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
            $tableArray[$n]['AB_effRefYield'] = $tableArray[$n]['U_refYield'] * $tableArray[$n]['AA_tCompensationFactor'];
            $tableArray[$n]['AC_effTheoEnergy'] = $tableArray[$n]['AB_effRefYield'] * $anlage->getPnom();
            $tableArray[$n]['AD_prMonth'] = $tableArray[$n]['X_specificYield'] / $tableArray[$n]['AB_effRefYield'] * 100;
            // $tableArray[$n]['AF_ratioFT']                   = $tableArray[$n]['AB_effRefYield'] / $tableArray[$zeileSumme3]['AB_effRefYield'] * 100;

            if ($n <= 13) { // Year 1
                $tableArray[$zeileSumme1]['AB_effRefYield'] += $tableArray[$n]['AB_effRefYield'];
                $tableArray[$zeileSumme1]['AD_prMonth'] = $tableArray[$zeileSumme1]['X_specificYield'] / $tableArray[$zeileSumme1]['AB_effRefYield'] * 100;
            }

            if ($n >= 14) { // Year 2
                $tableArray[$zeileSumme2]['AB_effRefYield'] += $tableArray[$n]['AB_effRefYield'];
                $tableArray[$zeileSumme2]['AD_prMonth'] = $tableArray[$zeileSumme2]['X_specificYield'] / $tableArray[$zeileSumme2]['AB_effRefYield'] * 100;
            }

            $tableArray[$zeileSumme3]['AB_effRefYield'] += $tableArray[$n]['AB_effRefYield'];
            $tableArray[$zeileSumme3]['AD_prMonth'] = $tableArray[$zeileSumme3]['X_specificYield'] / $tableArray[$zeileSumme3]['AB_effRefYield'] * 100;

            if ($rollingPeriod) {
                $tableArray[$zeileSumme4]['AB_effRefYield'] += $tableArray[$n]['AB_effRefYield'];
                $tableArray[$zeileSumme4]['AD_prMonth'] = $tableArray[$zeileSumme4]['X_specificYield'] / $tableArray[$zeileSumme4]['AB_effRefYield'] * 100;
            }

            if ($hasMonthData) {
                $tableArray[$zeileSumme5]['AB_effRefYield'] += $tableArray[$n]['AB_effRefYield'];
                $tableArray[$zeileSumme5]['AD_prMonth'] = $tableArray[$zeileSumme5]['X_specificYield'] / $tableArray[$zeileSumme5]['AB_effRefYield'] * 100;
            } else {
                $tableArray[$zeileSumme6]['AB_effRefYield'] += $tableArray[$n]['AB_effRefYield'];
                $tableArray[$zeileSumme6]['AD_prMonth'] = $tableArray[$zeileSumme6]['X_specificYield'] / $tableArray[$zeileSumme6]['AB_effRefYield'] * 100;
            }

            if ($n != 13 && $n != 26) {
                ++$month;
            }
        }

        $riskForecastUpToDate = $tableArray[$zeileSumme5]['AD_prMonth'] / $tableArray[$zeileSumme5]['P_prMonthDesign'];
        $riskForecastRollingPeriod = $tableArray[$zeileSumme4]['AD_prMonth'] / $tableArray[$zeileSumme4]['P_prMonthDesign'];

        // ///////////////////////////
        // / Runde 3
        // ///////////////////////////
        $month = $startMonth;
        $year = $startYear;

        for ($n = 1; $n <= $anzahlMonate; ++$n) {
            if ($month > 12) {
                $month = 1;
                ++$year;
            }

            $from_local = date_create(date('Y-m-d 00:00', strtotime("$year-$month-01")));
            $hasMonthData = $from_local <= $date; // Wenn das Datum in $from_local kleiner ist als das Datum in $date, es also für alle Tage des Monats Daten vorliegen, dann ist $hasMonthData === true
            if ($rollingPeriodMonthsStart > 0) {
                $rollingPeriod = $rollingPeriodMonthsStart < $n && $rollingPeriodMonthsEnd >= $n;
            } else {
                $rollingPeriod = $n <= $rollingPeriodMonthsEnd || $n >= $anzahlMonate + $rollingPeriodMonthsStart;
            }

            // Analysis
            $tableArray[$n]['AH_prForecast'] = $hasMonthData ? $tableArray[$n]['AD_prMonth'] : $tableArray[$n]['AD_prMonth'] * $riskForecastUpToDate;
            $tableArray[$n]['AI_eGridForecast'] = $tableArray[$n]['AH_prForecast'] / 100 * $tableArray[$n]['AB_effRefYield'] * $anlage->getPnom();
            $tableArray[$n]['AJ_specificYieldForecast'] = $tableArray[$n]['AI_eGridForecast'] / $anlage->getPnom();
            $tableArray[$n]['AK_absDiffPrRealGuarForecast'] = $tableArray[$n]['AH_prForecast'] - $tableArray[$n]['Q_prGuarDesign'];
            $tableArray[$n]['AL_relDiffPrRealGuarForecast'] = ($tableArray[$n]['AH_prForecast'] / $tableArray[$n]['Q_prGuarDesign'] - 1) * 100;

            if ($n <= 13) { // Year 1
                $tableArray[$zeileSumme1]['AA_tCompensationFactor'] = 1 + ($tableArray[$zeileSumme1]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
                $tableArray[$zeileSumme1]['AC_effTheoEnergy'] += $tableArray[$n]['AC_effTheoEnergy'];
                // $tableArray[$zeileSumme1]['AF_ratioFT']                     += $tableArray[$n]['AF_ratioFT'];
                // Analysis
                $tableArray[$zeileSumme1]['AH_prForecast'] += $tableArray[$zeileSumme1]['AI_eGridForecast'] / $tableArray[$zeileSumme1]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme1]['AI_eGridForecast'] += $tableArray[$n]['AI_eGridForecast'];
                $tableArray[$zeileSumme1]['AH_prForecast'] = $tableArray[$zeileSumme1]['AI_eGridForecast'] / $tableArray[$zeileSumme1]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme1]['AJ_specificYieldForecast'] += $tableArray[$n]['AJ_specificYieldForecast'];
                $tableArray[$zeileSumme1]['AK_absDiffPrRealGuarForecast'] = $tableArray[$zeileSumme1]['AH_prForecast'] - $tableArray[$zeileSumme1]['Q_prGuarDesign'];
                $tableArray[$zeileSumme1]['AL_relDiffPrRealGuarForecast'] = ($tableArray[$zeileSumme1]['AH_prForecast'] / $tableArray[$zeileSumme1]['Q_prGuarDesign'] - 1) * 100;
            }

            if ($n >= 14) { // Year 2
                $tableArray[$zeileSumme2]['AA_tCompensationFactor'] = 1 + ($tableArray[$zeileSumme2]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
                $tableArray[$zeileSumme2]['AC_effTheoEnergy'] += $tableArray[$n]['AC_effTheoEnergy'];
                // $tableArray[$zeileSumme2]['AF_ratioFT']                     += $tableArray[$n]['AF_ratioFT'];
                // Analysis
                $tableArray[$zeileSumme2]['AH_prForecast'] += $tableArray[$zeileSumme2]['AI_eGridForecast'] / $tableArray[$zeileSumme2]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme2]['AI_eGridForecast'] += $tableArray[$n]['AI_eGridForecast'];
                $tableArray[$zeileSumme2]['AH_prForecast'] = $tableArray[$zeileSumme2]['AI_eGridForecast'] / $tableArray[$zeileSumme2]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme2]['AJ_specificYieldForecast'] += $tableArray[$n]['AJ_specificYieldForecast'];
                $tableArray[$zeileSumme2]['AK_absDiffPrRealGuarForecast'] = $tableArray[$zeileSumme2]['AH_prForecast'] - $tableArray[$zeileSumme2]['Q_prGuarDesign'];
                $tableArray[$zeileSumme2]['AL_relDiffPrRealGuarForecast'] = ($tableArray[$zeileSumme2]['AH_prForecast'] / $tableArray[$zeileSumme2]['Q_prGuarDesign'] - 1) * 100;
            }

            $tableArray[$zeileSumme3]['AA_tCompensationFactor'] = 1 + ($tableArray[$zeileSumme3]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
            $tableArray[$zeileSumme3]['AC_effTheoEnergy'] += $tableArray[$n]['AC_effTheoEnergy'];
            // $tableArray[$zeileSumme3]['AF_ratioFT']                     += $tableArray[$n]['AF_ratioFT'];
            // Analysis
            $tableArray[$zeileSumme3]['AH_prForecast'] += $tableArray[$zeileSumme3]['AI_eGridForecast'] / $tableArray[$zeileSumme3]['AC_effTheoEnergy'] * 100;
            $tableArray[$zeileSumme3]['AI_eGridForecast'] += $tableArray[$n]['AI_eGridForecast'];
            $tableArray[$zeileSumme3]['AH_prForecast'] = $tableArray[$zeileSumme3]['AI_eGridForecast'] / $tableArray[$zeileSumme3]['AC_effTheoEnergy'] * 100;
            $tableArray[$zeileSumme3]['AJ_specificYieldForecast'] += $tableArray[$n]['AJ_specificYieldForecast'];
            $tableArray[$zeileSumme3]['AK_absDiffPrRealGuarForecast'] = $tableArray[$zeileSumme3]['AH_prForecast'] - $tableArray[$zeileSumme3]['Q_prGuarDesign'];
            $tableArray[$zeileSumme3]['AL_relDiffPrRealGuarForecast'] = ($tableArray[$zeileSumme3]['AH_prForecast'] / $tableArray[$zeileSumme3]['Q_prGuarDesign'] - 1) * 100;

            if ($rollingPeriod) {
                $tableArray[$zeileSumme4]['AA_tCompensationFactor'] = 1 + ($tableArray[$zeileSumme4]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
                $tableArray[$zeileSumme4]['AC_effTheoEnergy'] += $tableArray[$n]['AC_effTheoEnergy'];
                // $tableArray[$zeileSumme4]['AF_ratioFT']                     += $tableArray[$n]['AF_ratioFT'];
                // Analysis
                $tableArray[$zeileSumme4]['AH_prForecast'] += $tableArray[$zeileSumme4]['AI_eGridForecast'] / $tableArray[$zeileSumme4]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme4]['AI_eGridForecast'] += $tableArray[$n]['AI_eGridForecast'];
                $tableArray[$zeileSumme4]['AH_prForecast'] = $tableArray[$zeileSumme4]['AI_eGridForecast'] / $tableArray[$zeileSumme4]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme4]['AJ_specificYieldForecast'] += $tableArray[$n]['AJ_specificYieldForecast'];
                $tableArray[$zeileSumme4]['AK_absDiffPrRealGuarForecast'] = $tableArray[$zeileSumme4]['AH_prForecast'] - $tableArray[$zeileSumme4]['Q_prGuarDesign'];
                $tableArray[$zeileSumme4]['AL_relDiffPrRealGuarForecast'] = ($tableArray[$zeileSumme4]['AH_prForecast'] / $tableArray[$zeileSumme4]['Q_prGuarDesign'] - 1) * 100;
            }

            if ($hasMonthData) {
                $tableArray[$zeileSumme5]['AA_tCompensationFactor'] = 1 + ($tableArray[$zeileSumme5]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
                $tableArray[$zeileSumme5]['AC_effTheoEnergy'] += $tableArray[$n]['AC_effTheoEnergy'];
                // $tableArray[$zeileSumme5]['AF_ratioFT']                     += $tableArray[$n]['AF_ratioFT'];
                // Analysis
                $tableArray[$zeileSumme5]['AH_prForecast'] += $tableArray[$zeileSumme5]['AI_eGridForecast'] / $tableArray[$zeileSumme5]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme5]['AI_eGridForecast'] += $tableArray[$n]['AI_eGridForecast'];
                $tableArray[$zeileSumme5]['AH_prForecast'] = $tableArray[$zeileSumme5]['AI_eGridForecast'] / $tableArray[$zeileSumme5]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme5]['AJ_specificYieldForecast'] += $tableArray[$n]['AJ_specificYieldForecast'];
                $tableArray[$zeileSumme5]['AK_absDiffPrRealGuarForecast'] = $tableArray[$zeileSumme5]['AH_prForecast'] - $tableArray[$zeileSumme5]['Q_prGuarDesign'];
                $tableArray[$zeileSumme5]['AL_relDiffPrRealGuarForecast'] = ($tableArray[$zeileSumme5]['AH_prForecast'] / $tableArray[$zeileSumme5]['Q_prGuarDesign'] - 1) * 100;
            } else {
                $tableArray[$zeileSumme6]['AA_tCompensationFactor'] = 1 + ($tableArray[$zeileSumme6]['Z_tCellAvgWeighted'] - $tableArray[$zeileSumme3]['L_tempAmbWeightedDesign']) * $anlage->getTempCorrGamma() / 100;
                $tableArray[$zeileSumme6]['AC_effTheoEnergy'] += $tableArray[$n]['AC_effTheoEnergy'];
                // $tableArray[$zeileSumme6]['AF_ratioFT']                     += $tableArray[$n]['AF_ratioFT'];
                // Analysis
                $tableArray[$zeileSumme6]['AH_prForecast'] += $tableArray[$zeileSumme6]['AI_eGridForecast'] / $tableArray[$zeileSumme6]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme6]['AI_eGridForecast'] += $tableArray[$n]['AI_eGridForecast'];
                $tableArray[$zeileSumme6]['AH_prForecast'] = $tableArray[$zeileSumme6]['AI_eGridForecast'] / $tableArray[$zeileSumme6]['AC_effTheoEnergy'] * 100;
                $tableArray[$zeileSumme6]['AJ_specificYieldForecast'] += $tableArray[$n]['AJ_specificYieldForecast'];
                $tableArray[$zeileSumme6]['AK_absDiffPrRealGuarForecast'] = $tableArray[$zeileSumme6]['AH_prForecast'] - $tableArray[$zeileSumme6]['Q_prGuarDesign'];
                $tableArray[$zeileSumme6]['AL_relDiffPrRealGuarForecast'] = ($tableArray[$zeileSumme6]['AH_prForecast'] / $tableArray[$zeileSumme6]['Q_prGuarDesign'] - 1) * 100;
            }

            if ($n != 13 && $n != 26) {
                ++$month;
            }
        }
        ksort($tableArray);

        $result = (object) ['table' => $tableArray, 'riskForecastUpToDate' => $riskForecastUpToDate, 'riskForecastRollingPeriod' => $riskForecastRollingPeriod];

        return $result;
    }

    public function pldTable(Anlage $anlage, array $monthTable, ?DateTime $date = null): array
    {
        $result = [];
        $zeileSumme1 = count($monthTable) - 5;
        $zeileSumme2 = count($monthTable) - 4;
        $zeileSumme3 = count($monthTable) - 3;
        $zeileSumme4 = count($monthTable) - 2;
        $zeileSumme5 = count($monthTable) - 1;
        $zeileSumme6 = count($monthTable) - 0;

        $discountRate = 6.625;
        $yieldDesign = 882.00; // Yield PVSyst

        $result[16]['year'] = '';
        $result[16]['Ve'] = '';
        $result[16]['factor'] = '';
        $result[16]['multi'] = '';
        $result[16]['eloss_current'] = '';
        $result[16]['eloss_year1'] = '';
        $result[16]['eloss_year2'] = '';
        $result[16]['eloss_year12'] = '';
        $result[16]['eloss_rolling'] = '';
        $result[16]['elossR_year1'] = '';
        $result[16]['elossR_year2'] = '';
        $result[16]['elossR_year12'] = '';
        $result[16]['elossR_rolling'] = '';
        $result[16]['value_current'] = 0;
        $result[16]['value_year1'] = 0;
        $result[16]['value_year2'] = 0;
        $result[16]['value_year12'] = 0;
        $result[16]['value_rolling'] = 0;
        $result[16]['valueR_year1'] = 0;
        $result[16]['valueR_year2'] = 0;
        $result[16]['valueR_year12'] = 0;
        $result[16]['valueR_rolling'] = 0;

        for ($year = 1; $year <= 15; ++$year) {
            $result[$year]['year'] = $year;
            $result[$year]['Ve'] = $anlage->getPldPR() * 1000;
            $result[$year]['factor'] = ((1 + $discountRate / 100) ** (2 - $year));
            $result[$year]['multi'] = $result[$year]['Ve'] * $result[$year]['factor'];

            // E loss
            // without Risk
            $result[$year]['eloss_current'] = (($monthTable[$zeileSumme5]['Q_prGuarDesign'] - $monthTable[$zeileSumme5]['AD_prMonth']) / $monthTable[$zeileSumme5]['Q_prGuarDesign'] * 100) / 100 * $yieldDesign * $anlage->getPnom() / 1000;
            $result[$year]['eloss_year1'] = (($anlage->getContractualPR() - $monthTable[$zeileSumme1]['AD_prMonth']) / $anlage->getContractualPR() * 100) / 100 * $yieldDesign * $anlage->getPnom() / 1000;
            $result[$year]['eloss_year2'] = (($anlage->getContractualPR() - $monthTable[$zeileSumme2]['AD_prMonth']) / $anlage->getContractualPR() * 100) / 100 * $yieldDesign * $anlage->getPnom() / 1000;
            $result[$year]['eloss_year12'] = (($anlage->getContractualPR() - $monthTable[$zeileSumme3]['AD_prMonth']) / $anlage->getContractualPR() * 100) / 100 * $yieldDesign * $anlage->getPnom() / 1000;
            $result[$year]['eloss_rolling'] = (($anlage->getContractualPR() - $monthTable[$zeileSumme4]['AD_prMonth']) / $anlage->getContractualPR() * 100) / 100 * $yieldDesign * $anlage->getPnom() / 1000;
            // with Risk
            $result[$year]['elossR_year1'] = (($anlage->getContractualPR() - $monthTable[$zeileSumme1]['AH_prForecast']) / $anlage->getContractualPR() * 100) / 100 * $yieldDesign * $anlage->getPnom() / 1000;
            $result[$year]['elossR_year2'] = (($anlage->getContractualPR() - $monthTable[$zeileSumme2]['AH_prForecast']) / $anlage->getContractualPR() * 100) / 100 * $yieldDesign * $anlage->getPnom() / 1000;
            $result[$year]['elossR_year12'] = (($anlage->getContractualPR() - $monthTable[$zeileSumme3]['AH_prForecast']) / $anlage->getContractualPR() * 100) / 100 * $yieldDesign * $anlage->getPnom() / 1000;
            $result[$year]['elossR_rolling'] = (($anlage->getContractualPR() - $monthTable[$zeileSumme4]['AH_prForecast']) / $anlage->getContractualPR() * 100) / 100 * $yieldDesign * $anlage->getPnom() / 1000;

            // Present value at end of period
            // without Risk
            $result[$year]['value_current'] = $result[$year]['multi'] * $result[$year]['eloss_current'];
            $result[$year]['value_year1'] = $result[$year]['multi'] * $result[$year]['eloss_year1'];
            $result[$year]['value_year2'] = $result[$year]['multi'] * $result[$year]['eloss_year2'];
            $result[$year]['value_year12'] = $result[$year]['multi'] * $result[$year]['eloss_year12'];
            $result[$year]['value_rolling'] = $result[$year]['multi'] * $result[$year]['eloss_rolling'];
            // with Risk
            $result[$year]['valueR_year1'] = $result[$year]['multi'] * $result[$year]['elossR_year1'];
            $result[$year]['valueR_year2'] = $result[$year]['multi'] * $result[$year]['elossR_year2'];
            $result[$year]['valueR_year12'] = $result[$year]['multi'] * $result[$year]['elossR_year12'];
            $result[$year]['valueR_rolling'] = $result[$year]['multi'] * $result[$year]['elossR_rolling'];

            // without Risk
            $result[16]['value_current'] += $result[$year]['value_current'];
            $result[16]['value_year1'] += $result[$year]['value_year1'];
            $result[16]['value_year2'] += $result[$year]['value_year2'];
            $result[16]['value_year12'] += $result[$year]['value_year12'];
            $result[16]['value_rolling'] += $result[$year]['value_rolling'];
            // with Risk
            $result[16]['valueR_year1'] += $result[$year]['valueR_year1'];
            $result[16]['valueR_year2'] += $result[$year]['valueR_year2'];
            $result[16]['valueR_year12'] += $result[$year]['valueR_year12'];
            $result[16]['valueR_rolling'] += $result[$year]['valueR_rolling'];
        }
        ksort($result);

        return $result;
    }

    public function forcastTable(Anlage $anlage, array $monthTable, array $pldTable, ?DateTime $date = null): array
    {
        if ($date === null) {
            $date = new DateTime();
        }

        $result = [];
        $zeileSumme1 = count($monthTable) - 5;
        $zeileSumme2 = count($monthTable) - 4;
        $zeileSumme3 = count($monthTable) - 3;
        $zeileSumme4 = count($monthTable) - 2;
        $zeileSumme5 = count($monthTable) - 1;
        $zeileSumme6 = count($monthTable) - 0;

        $yieldDesign = 882.00; // Yield PVSyst

        $result['year1']['PrActForcast'] = $monthTable[$zeileSumme1]['AD_prMonth'];
        $result['year1']['PrActForcastRisk'] = $monthTable[$zeileSumme1]['AH_prForecast'];
        $result['year1']['PA'] = $monthTable[$zeileSumme1]['AG_epcPA'];
        $result['year1']['PRdiff'] = ($anlage->getContractualPR() - $result['year1']['PrActForcast']) / $anlage->getContractualPR() * 100;
        $result['year1']['PRdiffRisk'] = ($anlage->getContractualPR() - $result['year1']['PrActForcastRisk']) / $anlage->getContractualPR() * 100;
        $result['year1']['Eloss'] = $result['year1']['PRdiff'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['year1']['ElossRisk'] = $result['year1']['PRdiffRisk'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['year1']['PLD'] = $result['year1']['Eloss'] <= 0 ? 'no PLD' : $pldTable[16]['value_year1'];
        $result['year1']['PLDRisk'] = $result['year1']['ElossRisk'] <= 0 ? 'no PLD' : $pldTable[16]['valueR_year1'];

        $result['year2']['PrActForcast'] = $monthTable[$zeileSumme2]['AD_prMonth'];
        $result['year2']['PrActForcastRisk'] = $monthTable[$zeileSumme2]['AH_prForecast'];
        $result['year2']['PA'] = $monthTable[$zeileSumme2]['AG_epcPA'];
        $result['year2']['PRdiff'] = ($anlage->getContractualPR() - $result['year2']['PrActForcast']) / $anlage->getContractualPR() * 100;
        $result['year2']['PRdiffRisk'] = ($anlage->getContractualPR() - $result['year2']['PrActForcastRisk']) / $anlage->getContractualPR() * 100;
        $result['year2']['Eloss'] = $result['year2']['PRdiff'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['year2']['ElossRisk'] = $result['year2']['PRdiffRisk'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['year2']['PLD'] = $result['year2']['Eloss'] <= 0 ? 'no PLD' : $pldTable[16]['value_year2'];
        $result['year2']['PLDRisk'] = $result['year2']['ElossRisk'] <= 0 ? 'no PLD' : $pldTable[16]['valueR_year2'];

        $result['year12']['PrActForcast'] = $monthTable[$zeileSumme3]['AD_prMonth'];
        $result['year12']['PrActForcastRisk'] = $monthTable[$zeileSumme3]['AH_prForecast'];
        $result['year12']['PA'] = $monthTable[$zeileSumme3]['AG_epcPA'];
        $result['year12']['PRdiff'] = ($anlage->getContractualPR() - $result['year12']['PrActForcast']) / $anlage->getContractualPR() * 100;
        $result['year12']['PRdiffRisk'] = ($anlage->getContractualPR() - $result['year12']['PrActForcastRisk']) / $anlage->getContractualPR() * 100;
        $result['year12']['Eloss'] = $result['year12']['PRdiff'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['year12']['ElossRisk'] = $result['year12']['PRdiffRisk'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['year12']['PLD'] = $result['year12']['Eloss'] <= 0 ? 'no PLD' : $pldTable[16]['value_year12'];
        $result['year12']['PLDRisk'] = $result['year12']['ElossRisk'] <= 0 ? 'no PLD' : $pldTable[16]['valueR_year12'];

        $result['rolling']['PrActForcast'] = $monthTable[$zeileSumme4]['AD_prMonth'];
        $result['rolling']['PrActForcastRisk'] = $monthTable[$zeileSumme4]['AH_prForecast'];
        $result['rolling']['PA'] = $monthTable[$zeileSumme4]['AG_epcPA'];
        $result['rolling']['PRdiff'] = ($anlage->getContractualPR() - $result['rolling']['PrActForcast']) / $anlage->getContractualPR() * 100;
        $result['rolling']['PRdiffRisk'] = ($anlage->getContractualPR() - $result['rolling']['PrActForcastRisk']) / $anlage->getContractualPR() * 100;
        $result['rolling']['Eloss'] = $result['rolling']['PRdiff'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['rolling']['ElossRisk'] = $result['rolling']['PRdiffRisk'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['rolling']['PLD'] = $result['rolling']['Eloss'] <= 0 ? 'no PLD' : $pldTable[16]['value_rolling'];
        $result['rolling']['PLDRisk'] = $result['rolling']['ElossRisk'] <= 0 ? 'no PLD' : $pldTable[16]['valueR_rolling'];

        $result['current']['PrActForcast'] = $monthTable[$zeileSumme5]['AD_prMonth'];
        $result['current']['PrActForcastRisk'] = $monthTable[$zeileSumme5]['AH_prForecast'];
        $result['current']['PA'] = $monthTable[$zeileSumme5]['AG_epcPA'];
        $result['current']['PRdiff'] = (($monthTable[$zeileSumme5]['Q_prGuarDesign'] - $monthTable[$zeileSumme5]['AD_prMonth']) / $monthTable[$zeileSumme5]['Q_prGuarDesign'] * 100);
        $result['current']['PRdiffRisk'] = (($monthTable[$zeileSumme5]['Q_prGuarDesign'] - $monthTable[$zeileSumme5]['PrActForcastRisk']) / $monthTable[$zeileSumme5]['Q_prGuarDesign'] * 100);
        $result['current']['Eloss'] = $result['current']['PRdiff'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['current']['ElossRisk'] = $result['current']['PRdiffRisk'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['current']['PLD'] = $result['current']['Eloss'] <= 0 ? 'no PLD' : $pldTable[16]['value_current'];
        $result['current']['PLDRisk'] = '';

        $result['forcast']['PrActForcast'] = $monthTable[$zeileSumme6]['AD_prMonth'];
        $result['forcast']['PrActForcastRisk'] = $monthTable[$zeileSumme6]['AH_prForecast'];
        $result['forcast']['PA'] = $monthTable[$zeileSumme6]['AG_epcPA'];
        $result['forcast']['PRdiff'] = ($anlage->getContractualPR() - $result['forcast']['PrActForcast']) / $anlage->getContractualPR() * 100;
        $result['forcast']['PRdiffRisk'] = ($anlage->getContractualPR() - $result['forcast']['PrActForcastRisk']) / $anlage->getContractualPR() * 100;
        $result['forcast']['Eloss'] = $result['forcast']['PRdiff'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['forcast']['ElossRisk'] = $result['forcast']['PRdiffRisk'] / 100 * $yieldDesign * $anlage->getPnom() / 1000;
        $result['forcast']['PLD'] = $result['forcast']['Eloss'] <= 0 ? 'no PLD' : 'PLD';
        $result['forcast']['PLDRisk'] = $result['forcast']['ElossRisk'] <= 0 ? 'no PLD' : 'PLD';

        return $result;
    }
}
