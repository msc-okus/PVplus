<?php

namespace App\Service;

use App\Entity\Anlage;
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

    public function monthTable(Anlage $anlage, DateTime $from, DateTime $to): array
    {
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
        $monthlyData = $this->monthlyDataRepo->findOneBy(['anlage' => $anlage, 'year' => $year, 'month' => $month]);

        for ($n = 1; $n <= $anzahlMonate; $n++) {
            if ($month >= 13) {
                $month = 1;
                $year++;
            }
            $daysInMonth    = (int)date('t', strtotime("$year-$month-01"));
            $from           = date_create(date('Y-m-d 00:00', strtotime("$year-$month-01")));
            $to             = date_create(date('Y-m-d 23:59', strtotime("$year-$month-$daysInMonth")));
            switch ($n) {
                case 1:
                    $from = date('Y-m-d', strtotime("$year-$month-$facStartDay 00:00"));

                    $days = $daysInMonth - $daysInStartMonth +1;
                    break;
                case $anzahlMonate:
                    $days = $daysInEndMonth;
                    $to = date('Y-m-d', strtotime("$year-$month-$facEndDay 23:59"));
                    break;
                default:
                    $days = $daysInMonth;

            }
            $prArray = $this->PRCalulation->calcPR($anlage, $from, $to);


            if ($monthlyData != null && $monthlyData->getPvSystPR() > 0) {
                $prDesignPvSyst = $monthlyData->getPvSystPR();
            } else {
                ($anlage->getOneMonthPvSyst($month) != null) ? $prDesignPvSyst = $anlage->getOneMonthPvSyst($month)->getPrDesign() : $prDesignPvSyst = 0;
            }

            if ($anlage->getUseGridMeterDayData()){
                if ($monthlyData != null && $monthlyData->getExternMeterDataMonth() > 0) {
                    $eGridReal = $monthlyData->getExternMeterDataMonth();
                } else {
                    $eGridReal = $prArray['powerEGridExt'];
                }
                $prReal = $prArray['prEGridExt'];
            }

            $tableArray[$n]['month']                        = date('m / Y', strtotime("$year-$month-1"));
            $tableArray[$n]['days']                         = $days;
            $tableArray[$n]['irrDesign']                    = "";
            $tableArray[$n]['yieldDesign']                  = "";
            $tableArray[$n]['specificYieldDesign']          = "";
            $tableArray[$n]['prDesign']                     = "";
            $tableArray[$n]['prGuarantie']                  = "";
            $tableArray[$n]['theorYieldDesign']             = "";
            $tableArray[$n]['theorYieldMTDesign']           = "";
            $tableArray[$n]['irrFTDesign']                  = "";
            $tableArray[$n]['irr']                          = "";
            $tableArray[$n]['eGridYield']                   = "";
            $tableArray[$n]['specificYield']                = "";
            $tableArray[$n]['availability']                 = "";
            $tableArray[$n]['part']                         = "";
            $tableArray[$n]['prReal_prProg']                = ""; // PR Real bzw PR Prognostiziert wenn noch kein PR Real vorhanden
            $tableArray[$n]['theorYield']                   = "";
            $tableArray[$n]['theorYieldMT']                 = "";
            $tableArray[$n]['irrMT']                        = "";
            $tableArray[$n]['prReal_prForecast']            = "";
            $tableArray[$n]['yieldEGrid_yieldForecast']     = "";
            $tableArray[$n]['yield_guaranteed']             = "";
            $tableArray[$n]['yieldEGrid']                   = "";
            $tableArray[$n]['prRealMinusPrGuraReduction']   = "";
            $tableArray[$n]['yieldEGridForecast']           = "";
            $tableArray[$n]['yieldEGridMinusGuranteed']     = "";
            $tableArray[$n]['prRealMinusPrGura']            = "";
            $tableArray[$n]['eGridDivExpected']             = "";

        }

        return $tableArray;
    }
}