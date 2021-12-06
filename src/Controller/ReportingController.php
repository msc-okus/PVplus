<?php

namespace App\Controller;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Entity\User;
use App\Form\Reports\ReportsFormType;
use App\Helper\G4NTrait;
use App\Reports\Goldbeck\EPCMonthlyPRGuaranteeReport;
use App\Reports\Goldbeck\EPCMonthlyYieldGuaranteeReport;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Repository\UserRepository;
use App\Service\ReportEpcService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use koolreport\KoolReport;
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
     */
    public function create(Request $request, AnlagenRepository $anlagenRepo, ReportService $report, ReportEpcService $epcReport): RedirectResponse
    {
        $session=$this->container->get('session');

        $searchstatus=$session->get('search');
        $searchtype=$session->get('type');
        $anlageq=$session->get('anlage');
        $searchmonth=$session->get('month');
        $route = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
        $route = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&search=yes";
        $reportType = $request->query->get('report-typ');
        $reportMonth = $request->query->get('month');
        $reportYear = $request->query->get('year');
        $anlageId = $request->query->get('anlage-id');
        $aktAnlagen = $anlagenRepo->findIdLike([$anlageId]);
        switch ($reportType){
            case 'monthly':
                $output = $report->monthlyReport($aktAnlagen, $reportMonth, $reportYear, 0, 0, true, false, false);
                break;
            case 'epc':
                $output = $epcReport->createEpcReport($aktAnlagen[0]);
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
    public function list(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository, AnlagenRepository $anlagenRepo, ReportService $report, ReportEpcService $epcReport): Response
    {
        $session = $this->container->get('session');
        $anlage = $searchstatus = $searchtype = $searchmonth = null;
        if($request->query->get('searchstatus') != null & $request->query->get('searchstatus')  != "") $searchstatus    = $request->query->get('searchstatus');
        if($request->query->get('searchtype')   != null & $request->query->get('searchtype')    != "") $searchtype      = $request->query->get('searchtype');
        if($request->query->get('searchmonth')  != null & $request->query->get('searchmonth')   != "") $searchmonth     = $request->query->get('searchmonth');
        #if($request->query->get('qr')           != null & $request->query->get('qr')            != "") $q               = $request->query->get('qr');
        if($request->query->get('anlage')       != null & $request->query->get('anlage')        != "") $anlage          = $request->query->get('anlage');


        if($request->query->get('new-report') === 'yes' ) {
            $searchstatus   = $session->get('search');
            $searchtype     = $session->get('type');
            $searchmonth    = $session->get('month');
            $anlage         = $session->get('anlage');
            $new            = $request->query->get('new-report');
            $reportType     = $request->query->get('report-typ');
            $reportMonth    = $request->query->get('month');
            $reportYear     = $request->query->get('year');
            $anlageId       = $request->query->get('anlage-id');
            $aktAnlagen     = $anlagenRepo->findIdLike([$anlageId]);
            switch ($reportType){
                case 'monthly':
                    $output = $report->monthlyReport($aktAnlagen, $reportMonth, $reportYear, 0, 0, true, false, false);
                    break;
                case 'epc':
                    $output = $epcReport->createEpcReport($aktAnlagen[0]);
                    break;
                case 'am':
                    return $this->redirectToRoute('report_asset_management', ['id' => $anlageId, 'month' => $reportMonth, 'year' => $reportYear, 'export' => 1, 'pages' => 0]);
                    break;

            }
            $request->query->set('new-report', 'no');
            $request->query->set('report-typ', $reportType);
            $request->query->set('month', $reportMonth);
            $request->query->set('year', $reportYear);
            $request->query->set('anlage-id', $anlageId);

            $route = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
            $route = $route."?anlage=".$anlage."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&search=yes";

            return $this->redirect($route);
        }

        $queryBuilder = $reportsRepository->getWithSearchQueryBuilder($anlage,$searchstatus,$searchtype,$searchmonth);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );
        $session->set('search', $searchstatus);
        $session->set('type', $searchtype);
        $session->set('anlage', $anlage);
        $session->set('month', $searchmonth);
        $anlagen = $anlagenRepo->findAll();

        return $this->render('reporting/list.html.twig', [
            'pagination' => $pagination,
            'anlagen'    => $anlagen,
            'stati'      => self::reportStati(),
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
    public function showReportAsPdf($id, ReportEpcService $reportEpcService, ReportService $reportService, ReportsRepository $reportsRepository, NormalizerInterface $serializer)
    {
        /** @var AnlagenReports|null $report */
        $session=$this->container->get('session');

        $searchstatus   = $session->get('search');
        $searchtype     = $session->get('type');
        $anlageq        = $session->get('anlage');
        $searchmonth    = $session->get('month');
        $route          = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
        $route          = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&search=yes";

        $report = $reportsRepository->find($id);
        $month = $report->getMonth();
        $reportCreationDate = $report->getCreatedAt()->format('Y-m-d h:i:s');
        $anlage = $report->getAnlage();
        $currentDate = date('Y-m-d H-i');
        $pdfFilename = 'Report ' . $currentDate . '.pdf';
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
        $reportArray = $report->getContentArray();
        switch ($report->getReportType()) {
            case 'epc-report':
                $pdfFilename = 'EPC Report ' . $anlage->getAnlName() . ' - ' . $currentDate . '.pdf';
                switch ($anlage->getEpcReportType()) {
                    case 'prGuarantee' :
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
                        break;
                    case 'yieldGuarantee':
                        $report = new EPCMonthlyYieldGuaranteeReport([
                            'headlines'     => $headline,
                            'main'          => $reportArray[0],
                            'forecast24'    => $reportArray[1],
                            'header'        => $reportArray[2],
                            'forecast_real' => $reportArray[3],
                            'legend'        => $serializer->normalize($anlage->getLegendEpcReports()->toArray(), null, ['groups' => 'legend']),

                        ]);
                        break;
                    default:
                }
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
            case 'monthly-report':
                //standard G4N Report (an O&M Goldbeck angelehnt)
                $reportService->buildMonthlyReport($anlage, $report->getContentArray(), $reportCreationDate, 0 ,0, true);
                // exit für Monthly Reports werden in buildMonthlyReports ausgeführt, wenn 'exit' parameter = true
                break;
            case 'am-report':
                #$reportService->buildAmReport($anlage, $report->getContentArray(), $reportCreationDate, 0 ,0, true);
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
                break;
        }

        return $this->redirect($route);
    }


    /**
     * @Route("/reporting/excel/{id}", name="app_reporting_excel")
     */
    public function showReportAsExcel($id, ReportEpcService $reportEpcService, ReportService $reportService, ReportsRepository $reportsRepository)
    {
        $session=$this->container->get('session');

        $searchstatus=$session->get('search');
        $searchtype=$session->get('type');
        $anlageq=$session->get('anlage');
        $searchmonth=$session->get('month');
        $route = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
        $route = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&search=yes";

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
                        $report = new EPCMonthlyYieldGuaranteeReport([
                            'headlines' => $headline,
                            'main'      => $reportArray[0],
                            'forecast'  => $reportArray[1],
                            'header'    => $reportArray[2],
                        ]);
                        $template = 'EPCMonthlyYieldGuaranteeReportExcel';
                        break;
                    default:
                }
                $report->run();
                $report->exportToXLSX($template)->toBrowser($excelFilename);
                exit; // Ohne exit führt es unter manchen Systemen (Browser) zu fehlerhaften Downloads
                break;
            case 'monthly-report':
                //Standard G4N Report (Goldbeck = O&M Report)
                $reportService->buildMonthlyReport($anlage, $report->getContentArray(), $reportCreationDate, 1);
                $reportService->buildMonthlyReport($anlage, $report->getContentArray(), $reportCreationDate, 2,0);
                $reportService->buildMonthlyReport($anlage, $report->getContentArray(), $reportCreationDate, 2,1);
                break;
        }

        return $this->redirect($route);
    }


    /**
     * @Route("/test/epc/report/{id}/{pdf}", defaults={"pdf"=false})
     */
    public function epcReport($id, $pdf, AnlagenRepository $anlagenRepository, ReportEpcService $reportEpc, EntityManagerInterface $em): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlagen = $anlagenRepository->findIdLike([$id]);
        $anlage = $anlagen[0];
        $currentDate = date('Y-m-d H-i');
        ############## $currentDate = '2021-05-31 12:00';
        $pdfFilename = 'EPC Report ' . $anlage->getAnlName() . ' - ' . $currentDate . '.pdf';
        $error = false;
        switch ($anlage->getEpcReportType()) {
            case 'prGuarantee' :
                $reportArray = $reportEpc->reportPRGuarantee($anlage);
                $report = new EPCMonthlyPRGuaranteeReport([
                    'headlines' => [
                        [
                            'projektNr'     => $anlage->getProjektNr(),
                            'anlage'        => $anlage->getAnlName(),
                            'eigner'        => $anlage->getEigner()->getFirma(),
                            'date'          => $currentDate,
                            'kwpeak'        => $anlage->getKwPeak(),
                        ],
                    ],
                    'main'          => $reportArray[0],
                    'forecast'      => $reportArray[1],
                    'pld'           => $reportArray[2],
                    'header'        => $reportArray[3],
                    'legend'        => $reportArray[4],
                    'forecast_real' => $reportArray[5],
                ]);
                break;
            case 'yieldGuarantee':
                $reportArray = $reportEpc->reportYieldGuarantee($anlage);

                $report = new EPCMonthlyYieldGuaranteeReport([
                    'headlines' => [
                        [
                            'projektNr'     => $anlage->getProjektNr(),
                            'anlage'        => $anlage->getAnlName(),
                            'eigner'        => $anlage->getEigner()->getFirma(),
                            'date'          => $currentDate,
                            'kwpeak'        => $anlage->getKwPeak(),
                        ],
                    ],
                    'main'          => $reportArray[0],
                    'forecast24'    => $reportArray[1],
                    'header'        => $reportArray[2],
                    'forecast_real' => $reportArray[3],
                    'legend'        => $reportArray[4],
                ]);
                break;
            default:
                $error = true;
                $reportArray = [];
                $report = null;
        }

        if (!$error) {
            $output = $report->run()->render(true);

            // Speichere Report als 'epc-reprt' in die Report Entity
            if (true) {
                $reportEntity = new AnlagenReports();
                $startDate = $anlage->getFacDateStart();
                $endDate = $anlage->getFacDate();
                $reportEntity
                    ->setCreatedAt(new \DateTime())
                    ->setAnlage($anlage)
                    ->setEigner($anlage->getEigner())
                    ->setReportType('epc-report')
                    ->setStartDate(self::getCetTime('object'))
                    ->setMonth(self::getCetTime('object')->format('m')-1)
                    ->setYear(self::getCetTime('object')->format('Y'))
                    ->setEndDate($endDate)
                    ->setRawReport($output)
                    ->setContentArray($reportArray);
                $em->persist($reportEntity);
                $em->flush();
            }

            // erzeuge PDF mit CloudExport von KoolReport
            if ($pdf) {
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
            }
        } else {
            $output = "<h1>Fehler: Es Ist kein Report ausgewählt.</h1>";
        }


        return $this->render('cron/showResult.html.twig', [
            'headline'      => 'EPC Report',
            'availabilitys' => '',
            'output'        => $output,
        ]);

    }


    /**
     * @IsGranted("ROLE_DEV")
     * @Route ("app_reporting/pdf/delete/{id}", name="app_reporting_delete")
     */
    public function deleteReport($id, ReportsRepository $reportsRepository, Security $security, EntityManagerInterface $em): RedirectResponse
    {
        $session=$this->container->get('session');

        $searchstatus=$session->get('search');
        $searchtype=$session->get('type');
        $anlageq=$session->get('anlage');
        $searchmonth=$session->get('month');
        $route = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABS_PATH);
        $route = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&search=yes";

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

    function substr_Index( $str, $needle, $nth ){
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
