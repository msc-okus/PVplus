<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Reports\Goldbeck\EPCMonthlyPRGuaranteeReport;
use App\Reports\Goldbeck\EPCMonthlyYieldGuaranteeReport;
use App\Repository\AnlagenRepository;
use App\Repository\PVSystDatenRepository;
use App\Service\CheckSystemStatusService;
use App\Service\PVSystService;
use App\Service\ReportEpcService;
use App\Service\ReportService;
use App\Service\AssetManagementService;
use Doctrine\ORM\EntityManagerInterface;
use Nuzkito\ChromePdf\ChromePdf;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;

class AssetManagementController extends BaseController
{
    use G4NTrait;

    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param $doctype ( 0 = PDF, 1 = Excel, 2 = PNG (Grafiken) )
     * @param $charttypetoexport (0 = , 1 = )
     * @Route("/asset/report/{id}/{month}/{year}/{charttypetoexport}/{pages}")
     */
    public function assetReport($id, $month, $year, $charttypetoexport, $pages, AssetManagementService $assetManagement, AnlagenRepository $anlagenRepository, Request $request)
    {
        $anlage = $anlagenRepository->findIdLike([$id]);
        $output = $assetManagement->assetReport($anlage, $month, $year, $charttypetoexport, $pages);
        $baseurl = $request->getSchemeAndHttpHost();

        return $this->render('report/assetreport.html.twig', [
            'baseurl' => $baseurl,
            'owner' => $output['owner'],
            'plantSize' => $output['plantSize'],
            'year' => $output['year'],
            'month' => $output['month'],
            'reportmonth' => $output['reportmonth'],
            'customer_logo' => 'https://gs.g4npvplus.net/goldbeck/reports/asset_management/goldbecksolar_logo.svg',
            'font_color' => '#9aacc3',
            'font_color_second' => '#91bc5b',
            'font_color_third' => '#36639c',
            'montharray' => $output['monthArray'],
            'degradation' => $output['degradation'],
            'forecast_PVSYST_table' => $output['forecast_PVSYST_table'],
            'forecast_PVSYST' => $output['forecast_PVSYST'],
            'forecast_G4N_table' => $output['forecast_G4N_table'],
            'forecast_G4N' => $output['forecast_G4N'],
            'dataMonthArray' => $output['dataMonthArray'],
            'dataCfArray' => $output['dataCfArray'],
            'operations_right' => $output['operations_right'],
            'table_overview_monthly' => $output['table_overview_monthly'],
            'losses_t1' => $output['losses_t1'],
            'losses_t2' => $output['losses_t2'],
            'losses_year' => $output['losses_year'],
            'losses_monthly' => $output['losses_monthly'],
            'production_monthly_chart' => $output['production_monthly_chart'],
            'operations_monthly_right_pvsyst_tr1' => $output['operations_monthly_right_pvsyst_tr1'],
            'operations_monthly_right_pvsyst_tr2' => $output['operations_monthly_right_pvsyst_tr2'],
            'operations_monthly_right_pvsyst_tr3' => $output['operations_monthly_right_pvsyst_tr3'],
            'operations_monthly_right_pvsyst_tr4' => $output['operations_monthly_right_pvsyst_tr4'],
            'operations_monthly_right_pvsyst_tr5' => $output['operations_monthly_right_pvsyst_tr5'],
            'operations_monthly_right_pvsyst_tr6' => $output['operations_monthly_right_pvsyst_tr6'],
            'operations_monthly_right_pvsyst_tr7' => $output['operations_monthly_right_pvsyst_tr7'],
            'operations_monthly_right_g4n_tr1' => $output['operations_monthly_right_g4n_tr1'],
            'operations_monthly_right_g4n_tr2' => $output['operations_monthly_right_g4n_tr2'],
            'operations_monthly_right_g4n_tr3' => $output['operations_monthly_right_g4n_tr3'],
            'operations_monthly_right_g4n_tr4' => $output['operations_monthly_right_g4n_tr4'],
            'operations_monthly_right_g4n_tr5' => $output['operations_monthly_right_g4n_tr5'],
            'operations_monthly_right_g4n_tr6' => $output['operations_monthly_right_g4n_tr6'],
            'operations_monthly_right_g4n_tr7' => $output['operations_monthly_right_g4n_tr7'],
            'operations_monthly_right_iout_tr1' => $output['operations_monthly_right_iout_tr1'],
            'operations_monthly_right_iout_tr2' => $output['operations_monthly_right_iout_tr2'],
            'operations_monthly_right_iout_tr3' => $output['operations_monthly_right_iout_tr3'],
            'operations_monthly_right_iout_tr4' => $output['operations_monthly_right_iout_tr4'],
            'operations_monthly_right_iout_tr5' => $output['operations_monthly_right_iout_tr5'],
            'operations_monthly_right_iout_tr6' => $output['operations_monthly_right_iout_tr6'],
            'operations_monthly_right_iout_tr7' => $output['operations_monthly_right_iout_tr7'],
            'useGridMeterDayData' => $output['useGridMeterDayData'],
            'showAvailability' => $output['showAvailability'],
            'showAvailabilitySecond' => $output['showAvailabilitySecond'],
            'table_overview_dayly' => $output['table_overview_dayly'],
            'plantAvailabilityCurrentYear' => $output['plantAvailabilityCurrentYear'],
            'daysInReportMonth' => $output['daysInReportMonth'],
            'tableColsLimit' => $output['tableColsLimit'],
            'acGroups' => $output['acGroups'],
            'availability_Year_To_Date' => $output['availability_Year_To_Date'],
            'failures_Year_To_Date' => $output['failures_Year_To_Date'],
            'plant_availability' => $output['plant_availability'],
            'actual' => $output['actual'],
            'plantAvailabilityMonth' => $output['plantAvailabilityMonth'],
            'operations_currents_dayly_table' => $output['operations_currents_dayly_table'],
            'income_per_month' => $output['income_per_month'],
            'income_per_month_chart' => $output['income_per_month_chart'],
            'total_Costs_Per_Date' => $output['total_Costs_Per_Date'],
            'operating_statement_chart' => $output['operating_statement_chart'],
        ]);

    }

}
