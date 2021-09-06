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

class DefaultController extends BaseController
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
     * @Route("/testasset/report/{id}/{month}/{year}/{inverter}/{doctype}/{charttypetoexport}/{storeDocument}", defaults={"storeDocument"=false})
     */
    public function testAssetReport($id, $month, $year, $inverter, $doctype, $charttypetoexport, $storeDocument, AssetManagementService $assetManagement, AnlagenRepository $anlagenRepository)
    {
        $anlagen = $anlagenRepository->findIdLike([$id]);
        $output = $assetManagement->assetReport($anlagen, $month, $year, $inverter, $doctype, $charttypetoexport, $storeDocument);

        return $this->render('report/assetreport.goldbeck.html.twig', [
            'operations_right' => $output['operations_right'],
            'month' => $output['month'],
            'dataMmonthArray' => $output['dataMmonthArray'],
            'year' => $output['year'],
            'table_overview_monthly' => $output['table_overview_monthly'],
            'operations_monthly_left' => $output['operations_monthly_left'],
            'operations_monthly_right_tupper_tr1' => $output['operations_monthly_right_tupper_tr1'],
            'operations_monthly_right_tupper_tr2' => $output['operations_monthly_right_tupper_tr2'],
            'operations_monthly_right_tupper_tr3' => $output['operations_monthly_right_tupper_tr3'],
            'operations_monthly_right_tupper_tr4' => $output['operations_monthly_right_tupper_tr4'],
            'operations_monthly_right_tupper_tr5' => $output['operations_monthly_right_tupper_tr5'],
            'operations_monthly_right_tupper_tr6' => $output['operations_monthly_right_tupper_tr6'],
            'operations_monthly_right_tupper_tr7' => $output['operations_monthly_right_tupper_tr7'],
            'operations_monthly_right_tlower_tr1' => $output['operations_monthly_right_tlower_tr1'],
            'operations_monthly_right_tlower_tr2' => $output['operations_monthly_right_tlower_tr2'],
            'operations_monthly_right_tlower_tr3' => $output['operations_monthly_right_tlower_tr3'],
            'operations_monthly_right_tlower_tr4' => $output['operations_monthly_right_tlower_tr4'],
            'operations_monthly_right_tlower_tr5' => $output['operations_monthly_right_tlower_tr5'],
            'operations_monthly_right_tlower_tr6' => $output['operations_monthly_right_tlower_tr6'],
            'operations_monthly_right_tlower_tr7' => $output['operations_monthly_right_tlower_tr7'],
            'table_overview_dayly'  => $output['table_overview_dayly'],
            'useGridMeterDayData' => $output['useGridMeterDayData'],
            'plantAvailability' => $output['plantAvailability'],
            'plantAvailabilityCurrentYear' => $output['plantAvailabilityCurrentYear'],
            'showAvailability' => $output['showAvailability'],
            'showAvailabilitySecond' => $output['showAvailabilitySecond'],
            'operations_freetext_one' => $output['operations_freetext_one'],
            'operations_dayly_1' => $output['operations_dayly_1'],
            'operations_dayly_2' =>  $output['operations_dayly_2'],
            'operations_availability_1' => $output['operations_availability_1'],
            'operations_availability_2' =>  $output['operations_availability_2'],
            'operations_availability_dayly' => $output['operations_availability_dayly'],
            'operations_currents_dayly_1' => $output['operations_currents_dayly_1'],
            'operations_currents_dayly_2' => $output['operations_currents_dayly_2'],
            'operations_currents_dayly_table' => $output['operations_currents_dayly_table'],
            'daysInReportMonth' => $output['daysInReportMonth'],
            'tableColsLimit' => $output['tableColsLimit'],
            'operations_inverters_dayly_1' => $output['operations_inverters_dayly_1'],
            'operations_inverters_dayly_2' => $output['operations_inverters_dayly_2'],
            'inverters_heatmap' => $output['inverters_heatmap'],
            'inverters_heatmap_1' => $output['inverters_heatmap_1'],
            'inverters_heatmap_2' => $output['inverters_heatmap_2'],
            'acGroups' => $output['acGroups']
        ]);

    }



    /**
     * @param $doctype ( 0 = PDF, 1 = Excel, 2 = PNG (Grafiken) )
     * @param $charttypetoexport (0 = , 1 = )
     * @param $storeDocument (true / false)
     * @Route("/test/report/{id}/{month}/{year}/{doctype}/{charttypetoexport}/{storeDocument}", defaults={"storeDocument"=false})
     * @deprecated
     */
    public function testReport($id, $month, $year, $doctype, $charttypetoexport, $storeDocument, ReportService $report, AnlagenRepository $anlagenRepository)
    {
        /** @var Anlage [] $anlagen */
        $anlagen = $anlagenRepository->findIdLike([$id]);
        $output = $report->monthlyReport($anlagen, $month, $year, $doctype, $charttypetoexport, $storeDocument);

        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Monthly Report',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }


    /**
     * @Route("/test/pvsyst")
     */
    public function pvsyst(PVSystDatenRepository $PVSystDatenRepository, PVSystService $PVSystService, EntityManagerInterface $em)
    {
        $pvSystDaten = $PVSystDatenRepository->findAll();
        $output = "";

        foreach ($pvSystDaten as $data) {
            $output .= $PVSystService->normalizeDate($data->getStamp()) . '<br>';
            $data->setStamp($PVSystService->normalizeDate($data->getStamp()));
            $em->persist($data);
        }
        $em->flush();

        return $this->render('cron/showResult.html.twig', [
            'headline' => 'PV-Syst Tests',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }

    /**
     * @Route("/test/systemstatus")
     */
    public function checkSystemStatus(CheckSystemStatusService $checkSystemStatus)
    {
        $output = $checkSystemStatus->checkSystemStatus();

        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Systemstatus',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }
}
