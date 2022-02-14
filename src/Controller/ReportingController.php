<?php

namespace App\Controller;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Entity\AnlagenReports;
use App\Form\Reports\ReportsFormType;
use App\Helper\G4NTrait;
use App\Reports\Goldbeck\EPCMonthlyPRGuaranteeReport;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Service\PdfService;
use App\Service\ReportEpcService;
use App\Service\ReportsEpcNewService;
use App\Service\ReportService;
use App\Service\ReportsMonthlyService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ReportingController extends AbstractController
{
    use G4NTrait;

    /**
     * @Route("/reporting/create", name="app_reporting_create")
     * @deprecated or use as ajax endpoint ???
     */
    public function createReport(Request $request, AnlagenRepository $anlagenRepo, ReportService $report, ReportsMonthlyService $reportsMonthly, ReportEpcService $epcReport, ReportsEpcNewService $epcNew, PdfService $pdf): RedirectResponse
    {
        $session=$this->container->get('session');

        $searchstatus   = $session->get('search');
        $searchtype     = $session->get('type');
        $anlageq        = $session->get('anlage');
        $searchmonth    = $session->get('month');
        $route          = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
        $route          = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&search=yes";
        $reportType     = $request->query->get('report-typ');
        $reportMonth    = $request->query->get('month');
        $reportYear     = $request->query->get('year');
        $daysOfMonth    = date('t',strtotime("$reportYear-$reportMonth-01"));

        $reportDate     = new \DateTime("$reportYear-$reportMonth-$daysOfMonth");
        $anlageId       = $request->query->get('anlage-id');
        $aktAnlagen     = $anlagenRepo->findIdLike([$anlageId]);
        switch ($reportType){
            case 'monthly':
                $output = $report->monthlyReport($aktAnlagen, $reportMonth, $reportYear, 0, 0, true, false, false);
                break;
            case 'epc':
                $output = $epcReport->createEpcReport($aktAnlagen[0], $reportDate);
                break;
            case 'am':
                return $this->redirectToRoute('report_asset_management', ['id' => $anlageId, 'month' => $reportMonth, 'year' => $reportYear, 'export' => 1, 'pages' => 0]);
                break;
        }
        $request->query->set('report-typ', $reportType);
        $request->query->set('month', $reportMonth);
        $request->query->set('year', $reportYear);
        $request->query->set('anlage-id', $anlageId);


        return $this->redirect($route);
    }

    /**
     * @Route("/reporting", name="app_reporting_list")
     */
    public function list(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository, AnlagenRepository $anlagenRepo, ReportService $report, ReportEpcService $reportEpc, ReportsMonthlyService $reportsMonthly): Response
    {

        $session = $this->container->get('session');
        $anlage = $searchstatus = $searchtype = $searchmonth = null;
        $searchyear = date('Y');
        if ($request->query->get('searchstatus') != null && $request->query->get('searchstatus')  != "") $searchstatus    = $request->query->get('searchstatus');
        if ($request->query->get('searchtype')   != null && $request->query->get('searchtype')    != "") $searchtype      = $request->query->get('searchtype');
        if ($request->query->get('searchmonth')  != null && $request->query->get('searchmonth')   != "") $searchmonth     = $request->query->get('searchmonth');
        if ($request->query->get('searchyear')   != null && $request->query->get('searchyear')    != "") $searchyear      = $request->query->get('searchyear'); else $searchyear = null;
        if ($request->query->get('anlage')       != null && $request->query->get('anlage')        != "") $anlage          = $request->query->get('anlage');


        if ($request->query->get('new-report') === 'yes' ) {
            $searchstatus   = $session->get('search');
            $searchtype     = $session->get('type');
            $searchmonth    = $session->get('month');
            $searchyear     = $session->get('search_year');
            $anlage         = $session->get('anlage');
            $new            = $request->query->get('new-report');
            $reportType     = $request->query->get('report-typ');
            $reportMonth    = $request->query->get('month');
            $reportYear     = $request->query->get('year');
            $daysOfMonth    = date('t',strtotime("$reportYear-$reportMonth-01"));
            $reportDate     = new \DateTime("$reportYear-$reportMonth-$daysOfMonth");
            $anlageId       = $request->query->get('anlage-id');
            $aktAnlagen     = $anlagenRepo->findIdLike([$anlageId]);
            // create Reports
            switch ($reportType){
                case 'monthly':
                    #$output = $report->monthlyReport($aktAnlagen, $reportMonth, $reportYear, 0, 0, true, false, false);
                    $output = $reportsMonthly->createMonthlyReport($aktAnlagen[0], $reportMonth, $reportYear);
                    break;
                case 'epc':
                    $output = $reportEpc->createEpcReport($aktAnlagen[0], $reportDate);
                    break;
                case 'am':
                    return $this->redirectToRoute('report_asset_management', ['id' => $anlageId, 'month' => $reportMonth, 'year' => $reportYear, 'export' => 1, 'pages' => 0]);
                    //$output = $assetManagement->assetReport($aktAnlagen[0], $reportMonth, $reportYear, 1);
                    break;

            }
            $request->query->set('new-report', 'no');
            $request->query->set('report-typ', $reportType);
            $request->query->set('month', $reportMonth);
            $request->query->set('year', $reportYear);
            $request->query->set('anlage-id', $anlageId);

            $route = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
            $route = $route."?anlage=".$anlage."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&searchyear=".$searchyear."&search=yes";

            return $this->redirect($route);
        }

        $queryBuilder = $reportsRepository->getWithSearchQueryBuilder($anlage,$searchstatus,$searchtype,$searchmonth,$searchyear);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );
        $session->set('search', $searchstatus);
        $session->set('type', $searchtype);
        $session->set('anlage', $anlage);
        $session->set('month', $searchmonth);
        $session->set('search_year', $searchyear);
        $anlagen = $anlagenRepo->findAll();

        return $this->render('reporting/list.html.twig', [
            'pagination' => $pagination,
            'anlagen'    => $anlagen,
            'stati'      => self::reportStati(),
            'searchyear' => $searchyear,
            'month'      => $searchmonth,
            'type'       => $searchtype,
            'status'     => $searchstatus,
            'anlage'     => $anlage,
        ]);
    }

    /**
     * @Route("/reporting/anlagen/find", name="app_admin_reports_find", methods="GET")
     */
    public function find(AnlagenRepository $anlagenRepository, Request $request): JsonResponse
    {
        $anlage = $anlagenRepository->findByAllMatching($request->query->get('query'));
        return $this->json([
            'anlagen' => $anlage
        ], 200, [], ['groups' => ['main']]);
    }

    /**
     * @Route("/reporting/edit/{id}", name="app_reporting_edit")
     */
    public function edit($id, ReportsRepository $reportsRepository, Request $request, Security $security, EntityManagerInterface $em): Response
    {
        $session=$this->container->get('session');

        $searchstatus=$session->get('search');
        $searchtype=$session->get('type');
        $anlageq=$session->get('anlage');
        $searchmonth=$session->get('month');
        $searchyear     = $session->get('search_year');
        $route = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
        $route = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&search=yes";

        $report = $reportsRepository->find($id);
        $anlage = $report->getAnlage();
        $form = $this->createForm(ReportsFormType::class, $report);

        $form->handleRequest($request);

        //Creating the route with the query

        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked() ) ) {
            $successMessage = 'Plant data saved!';
            $em->persist($report);
            $em->flush();
            if ($form->get('saveclose')->isClicked()) {
                $this->addFlash('success', $successMessage);
                return $this->redirect($route);
            }
        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirect($route);
        }


        return $this->render('reporting/edit.html.twig', [
            'reportForm'    => $form->createView(),
            'report'        => $report,
            'anlage'        => $anlage,
        ]);
    }

    /**
     * @Route("/reporting/pdf/{id}", name="app_reporting_pdf")
     */
    public function showReportAsPdf($id, ReportEpcService $reportEpcService, ReportService $reportService, ReportsRepository $reportsRepository, NormalizerInterface $serializer, PdfService $pdf, ReportsEpcNewService $epcNewService, ReportsMonthlyService $reportsMonthly)
    {
        /** @var AnlagenReports|null $report */
        $session=$this->container->get('session');

        $searchstatus   = $session->get('search');
        $searchtype     = $session->get('type');
        $anlageq        = $session->get('anlage');
        $searchmonth    = $session->get('month');
        $searchyear     = $session->get('search_year');
        $route          = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
        $route          = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&searchyear=".$searchyear."&search=yes";

        $report = $reportsRepository->find($id);
        $month = $report->getMonth();
        $year = $report->getYear();
        $reportCreationDate = $report->getCreatedAt()->format('Y-m-d h:i:s');
        $anlage = $report->getAnlage();
        $currentDate = date('Y-m-d H-i');

        $reportArray = $report->getContentArray();
        switch ($report->getReportType()) {
            case 'epc-report':
                $pdfFilename = 'EPC Report ' . $anlage->getAnlName() . ' - ' . $currentDate . '.pdf';
                switch ($anlage->getEpcReportType()) {
                    case 'prGuarantee' :
                        $headline = [
                            [
                                'projektNr'     => $anlage->getProjektNr(),
                                'anlage'        => $anlage->getAnlName(),
                                'eigner'        => $anlage->getEigner()->getFirma(),
                                'date'          => $currentDate,
                                'kwpeak'        => $anlage->getKwPeak(),
                                'reportCreationDate' => $reportCreationDate,
                                'epcNote'       => $anlage->getEpcReportNote(),
                            ],
                        ];
                        $report = new EPCMonthlyPRGuaranteeReport([
                            'headlines'     => $headline,
                            'main'          => $reportArray[0],
                            'forecast'      => $reportArray[1],
                            'pld'           => $reportArray[2],
                            'header'        => $reportArray[3],
                            'legend'        => $serializer->normalize($anlage->getLegendEpcReports()->toArray(), null, ['groups' => 'legend']),
                            'forecast_real' => $reportArray['prForecast'],
                            'formel'        => $reportArray['formel'],
                        ]);
                        $secretToken = '2bf7e9e8c86aa136b2e0e7a34d5c9bc2f4a5f83291a5c79f5a8c63a3c1227da9';
                        $settings = [
                            // 'useLocalTempFolder' => true,
                            'pageWaiting' => 'networkidle2', //load, domcontentloaded, networkidle0, networkidle2
                        ];
                        $report->run();
                        $pdfOptions = [
                            'format'                => 'A4',
                            'landscape'             => true,
                            'noRepeatTableFooter'   => false,
                            'printBackground'       => true,
                            'displayHeaderFooter'   => true,
                        ];
                        $report->cloudExport()
                            ->chromeHeadlessio($secretToken)
                            ->settings($settings)
                            ->pdf($pdfOptions)
                            ->toBrowser($pdfFilename);
                        exit; // Ohne exit führt es unter manchen Systemen (Browser) zu fehlerhaften Downloads
                        break;
                    case 'yieldGuarantee':
                        $result = $this->renderView('report/epcReport.html.twig', [
                            'anlage'            => $anlage,
                            'monthsTable'       => $reportArray['monthTable'],
                            'forcast'           => $reportArray['forcastTable'],
                            'legend'            => $anlage->getLegendEpcReports(),
                            'chart1'            => $epcNewService->chartYieldPercenDiff($anlage, $reportArray['monthTable']),//$reportArray['chartYieldPercenDiff'],
                            'chart2'            => $epcNewService->chartYieldCumulative($anlage, $reportArray['monthTable']),
                        ]);


                        $response = new BinaryFileResponse($pdf->createPdfTemp($anlage, $result, 'string'));
                        $response->headers->set ( 'Content-Type', 'application/pdf' );
                        $response->deleteFileAfterSend(true);
                        $response->setContentDisposition(
                            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                            $anlage->getAnlName().'_EPC-Report_'.$month.'_'.$year.'.pdf'
                        );

                        return $response;
                }
                break;
            case 'monthly-report':
                //Standard G4N Report (Goldbeck = O&M Report)
                switch ($report->getReportTypeVersion()){
                    case 1: //Version 1 -> Calulation on demand, store to serialized array and buil pdf and exl from this Data
                        $reportsMonthly->exportReportToPDF($anlage, $report);
                        break;
                    default:
                        $reportService->buildMonthlyReport($anlage, $report->getContentArray(), $reportCreationDate, 0 ,0, true);
                }
                break;
            case 'am-report':
                $month = $report->getMonth();
                $year = $report->getYear();
                $anlageName = $report->getAnlage();
                $pos = $this->substr_Index($this->getParameter('kernel.project_dir'), '/', 5);
                $pathpart = substr($this->getParameter('kernel.project_dir'), $pos);

                $file_with_path = '/usr/home/pvpluy/public_html'.$pathpart.'/public/' . $anlageName.'_AssetReport_'.$month.'_'.$year.'.pdf';
                $response = new BinaryFileResponse ( $file_with_path );
                $response->headers->set ( 'Content-Type', 'text/plain' );
                $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $anlageName.'_AssetReport_'.$month.'_'.$year.'.pdf'
                );

                return $response;
        }

        return $this->redirect($route);
    }


    /**
     * @Route("/reporting/excel/{id}", name="app_reporting_excel")
     */
    public function showReportAsExcel($id, ReportEpcService $reportEpcService, ReportService $reportService, ReportsRepository $reportsRepository, ReportsMonthlyService $reportsMonthly)
    {
        $session=$this->container->get('session');

        $searchstatus=$session->get('search');
        $searchtype=$session->get('type');
        $anlageq=$session->get('anlage');
        $searchmonth=$session->get('month');
        $searchyear     = $session->get('search_year');
        $route = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
        $route = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&searchyear=".$searchyear."&search=yes";

        /** @var AnlagenReports|null $report */
        $report = $reportsRepository->find($id);
        $reportCreationDate = $report->getCreatedAt()->format('Y-m-d h:i:s');
        $anlage = $report->getAnlage();
        $currentDate = date('y-m-d');
        $excelFilename = 'Report ' . $currentDate . '.xlsx';
        $template = '';
        $headline = [
            [
                'projektNr'     => 'projektNr',
                'anlage'        => 'anlage',
                'eigner'        => 'eigner',
                'date'          => 'date',
                'kwpeak'        => 'kwpeak',
            ],
            [
                'projektNr'     => $anlage->getProjektNr(),
                'anlage'        => $anlage->getAnlName(),
                'eigner'        => $anlage->getEigner()->getFirma(),
                'date'          => $currentDate,
                'kwpeak'        => $anlage->getKwPeak(),
            ],
        ];
        $reportArray = $report->getContentArray();
        switch ($report->getReportType()) {
            case 'epc-report':
                $excelFilename = $anlage->getAnlName() . $currentDate . 'EPC Report.xlsx';
                switch ($anlage->getEpcReportType()) {
                    case 'prGuarantee' :
                        $report = new EPCMonthlyPRGuaranteeReport([
                            'headlines' => $headline,
                            'main'      => $reportArray[0],
                            'forecast'  => $reportArray[1],
                            'pld'       => $reportArray[2],
                            'header'    => $reportArray[3],
                        ]);
                        $template = 'EPCMonthlyPRGuaranteeReportExcel';
                        break;
                    case 'yieldGuarantee':

                        break;
                    default:
                }
                $report->run();
                $report->exportToXLSX($template)->toBrowser($excelFilename);
                exit; // Ohne exit führt es unter manchen Systemen (Browser) zu fehlerhaften Downloads
                break;
            case 'monthly-report':
                //Standard G4N Report (Goldbeck = O&M Report)
                switch ($report->getReportTypeVersion()){
                    case 1: // Version 1 -> Calulation on demand, store to serialized array and buil pdf and exl from this Data
                        $reportsMonthly->exportReportToExcel($anlage, $report);
                        break;
                    default: // old Version
                        $reportService->buildMonthlyReport($anlage, $report->getContentArray(), $reportCreationDate, 1);
                        $reportService->buildMonthlyReport($anlage, $report->getContentArray(), $reportCreationDate, 2,0);
                        $reportService->buildMonthlyReport($anlage, $report->getContentArray(), $reportCreationDate, 2,1);

                }
                break;
        }

        return $this->redirect($route);
    }


    /**
     * @IsGranted("ROLE_DEV")
     * @Route ("app_reporting/pdf/delete/{id}", name="app_reporting_delete")
     */
    public function deleteReport($id, ReportsRepository $reportsRepository, Security $security, EntityManagerInterface $em): RedirectResponse
    {
        $session        = $this->container->get('session');

        $searchstatus   = $session->get('search');
        $searchtype     = $session->get('type');
        $anlageq        = $session->get('anlage');
        $searchmonth    = $session->get('month');
        $searchyear     = $session->get('search_year');
        $route          = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
        $route          = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&searchyear=".$searchyear."&search=yes";

        if ($this->isGranted('ROLE_DEV'))
        {
            /** @var AnlagenReports|null $report */
            $report = $reportsRepository->find($id);
            if ($report) {
                $em->remove($report);
                $em->flush();
            }
        }

        return $this->redirect($route);
    }

    private function substr_Index( $str, $needle, $nth ): bool|int
    {
        $str2 = '';
        $posTotal = 0;
        for($i=0; $i < $nth; $i++){

            if($str2 != ''){
                $str = $str2;
            }

            $pos   = strpos($str, $needle);
            $str2  = substr($str, $pos+1);
            $posTotal += $pos+1;

        }
        return $posTotal-1;
    }
}
