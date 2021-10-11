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
    public function assetReport($id, $month, $year, $charttypetoexport, $pages, AssetManagementService $assetManagement, AnlagenRepository $anlagenRepository)
    {
        $anlage = $anlagenRepository->findIdLike([$id]);
        $output = $assetManagement->assetReport($anlage, $month, $year, $charttypetoexport, $pages);

        return $this->render('report/assetreport.html.twig', [
            'owner' => $output['owner'],
            'plantSize' => $output['plantSize'],
            'year' => $output['year'],
            'month' => $output['month'],
            'reportmonth' => $output['reportmonth'],
            'customer_logo' => 'https://gs.g4npvplus.net/goldbeck/reports/asset_management/goldbecksolar_logo.svg',
            'font_color' => '#9aacc3',
            'font_color_second' => '#fbba00',
            'font_color_third' => '#104476',
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
        ]);

    }

}
