<?php

namespace App\Controller;

use App\Entity\AnlagenReports;
use App\Form\AssetManagement\AssetManagementeReportFormType;
use App\Form\Reports\ReportsFormType;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use App\Message\Command\GenerateAMReport;
use App\Reports\Goldbeck\EPCMonthlyPRGuaranteeReport;
use App\Reports\ReportMonthly\ReportMonthly;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Service\LogMessagesService;
use App\Service\ReportEpcService;
use App\Service\AssetManagementService;
use App\Service\PdfService;
use App\Service\ReportEpcPRNewService;
use App\Service\Reports\MonthlyService;
use App\Service\ReportsEpcNewService;
use App\Service\ReportService;
use App\Service\ReportsMonthlyService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Snappy\Pdf;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Nuzkito\ChromePdf\ChromePdf;

class ReportingController extends AbstractController
{
    use G4NTrait;
    use PVPNameArraysTrait;

    public function __construct(private $kernelProjectDir)
    {

    }

    /**
     * @throws ExceptionInterface
     */
    #[Route(path: '/reporting/create', name: 'app_reporting_create', methods: ['GET', 'POST'])]
    public function createReport(
        Request $request,
        PaginatorInterface $paginator,
        ReportsRepository $reportsRepository,
        AnlagenRepository $anlagenRepo,
        ReportEpcService $reportEpc,
        ReportsMonthlyService $reportsMonthly,
        AssetManagementService $assetManagement,
        ReportEpcPRNewService $reportEpcNew,
        LogMessagesService $logMessages,
        MessageBusInterface $messageBus,
        string $kernelProjectDir
    ): Response
    {
        $anlage = $request->query->get('anlage');
        $searchstatus = $request->query->get('searchstatus');
        $searchtype = $request->query->get('searchtype');
        $searchmonth = $request->query->get('searchmonth');
        $searchyear = $request->query->get('searchyear');
        $reportType = $request->query->get('report-typ');
        $reportMonth = $request->query->get('month');
        $reportYear = $request->query->get('year');
        $daysOfMonth = date('t', strtotime("$reportYear-$reportMonth-01"));
        $reportDate = new \DateTime("$reportYear-$reportMonth-$daysOfMonth");
        $anlageId = $request->query->get('anlage-id');
        $aktAnlagen = $anlagenRepo->findIdLike([$anlageId]);
        $userId = $this->getUser()->getUserIdentifier();
        switch ($reportType) {
            case 'monthly':
                $output = $reportsMonthly->createMonthlyReport($aktAnlagen[0], $reportMonth, $reportYear);
                break;
            case 'epc':
                $output = $reportEpc->createEpcReport($aktAnlagen[0], $reportDate);
                break;
            case 'epc-new-pr':
                $output = $reportEpcNew->createEpcReportNew($aktAnlagen[0], $reportDate);
                break;
            case 'am':
                // we try to find and delete a previous report from this month/year
                #$output = $assetManagement->createAmReport($aktAnlagen[0], $reportMonth, $reportYear);
                $logId = $logMessages->writeNewEntry($aktAnlagen[0], 'AM Report', "create AM Report " . $aktAnlagen[0]->getAnlName() . " - $reportMonth / $reportYear");
                $message = new GenerateAMReport($aktAnlagen[0]->getAnlId(), $reportMonth, $reportYear, $userId, $logId);
                $messageBus->dispatch($message);
                break;
        }
        $queryBuilder = $reportsRepository->getWithSearchQueryBuilder($anlage, $searchstatus, $searchtype, $searchmonth, $searchyear);
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );
        return $this->render('reporting/_inc/_listReports.html.twig', [
            'pagination' => $pagination,
            'stati' => self::reportStati(),
            'anlage' => $aktAnlagen[0],
        ]);
    }

    #[Route(path: '/reporting/search', name: 'app_reporting_search', methods: ['GET', 'POST'])]
    public function searchReports(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository): Response
    {
        $anlage = $request->query->get('anlage');
        $searchstatus = $request->query->get('searchstatus');
        $searchtype = $request->query->get('searchtype');
        $searchmonth = $request->query->get('searchmonth');
        $searchyear = $request->query->get('searchyear');
        $page = $request->query->getInt('page', 1);

        $queryBuilder = $reportsRepository->getWithSearchQueryBuilder($anlage, $searchstatus, $searchtype, $searchmonth, $searchyear);
        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            20
        );
        return $this->render('reporting/_inc/_listReports.html.twig', [
            'pagination' => $pagination,
            'stati' => self::reportStati(),
        ]);
    }

    #[Route(path: '/reporting', name: 'app_reporting_list')]
    public function list(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository, AnlagenRepository $anlagenRepo): Response
    {
        $searchyear = date('Y');
        $searchstatus = $searchtype = $searchmonth = $anlage = '';
        $queryBuilder = $reportsRepository->getWithSearchQueryBuilder($anlage, $searchstatus, $searchtype, $searchmonth, $searchyear);
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        if ($request->query->get('ajax') || $request->isXmlHttpRequest()) {
            return $this->render('reporting/_inc/_listReports.html.twig', [
                'pagination' => $pagination,
                'stati' => self::reportStati(),
            ]);
        }

        $anlagen = $anlagenRepo->findAllActiveAndAllowed();

        return $this->render('reporting/list.html.twig', [
            'pagination' => $pagination,
            'stati'      => self::reportStati(),
            'anlagen'    => $anlagen,
            'searchyear' => $searchyear,
            'month'      => $searchmonth,
            'type'       => $searchtype,
            'status'     => $searchstatus,
            'anlage'     => $anlage,
        ]);
    }

    #[Route(path: '/reporting/edit/{id}/{page}', name: 'app_reporting_edit', defaults: ['page' => 1])]
    public function edit($id, $page, ReportsRepository $reportsRepository, Request $request, EntityManagerInterface $em): Response
    {
        $report = $reportsRepository->find($id);
        $form = $this->createForm(ReportsFormType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($report);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);
            }
        }

        $template = '_inc/_editForm.html.twig';

        return $this->renderForm('reporting/'.$template, [
            'reportForm'    => $form,//->createView(),
            'report'        => $report,
            'anlage'        => $report->getAnlage(),
            'page'          => $page
        ]);
    }

    #[Route(path: '/reporting/delete/{id}', name: 'app_reporting_delete')]
    #[IsGranted(['ROLE_DEV'])]
    public function deleteReport($id, ReportsRepository $reportsRepository, EntityManagerInterface $em): Response
    {
        if ($this->isGranted('ROLE_DEV')) {
            /** @var AnlagenReports|null $report */
            $report = $reportsRepository->find($id);
            if ($report) {
                $em->remove($report);
                $em->flush();
            }
        }

        return new Response(null, 204);
    }

    #[Route(path: '/reporting/pdf/{id}', name: 'app_reporting_pdf')]
    public function showReportAsPdf(Request $request, $id, ReportService $reportService, ReportsRepository $reportsRepository, NormalizerInterface $serializer, ReportsEpcNewService $epcNewService, ReportsMonthlyService $reportsMonthly, Pdf $snappyPdf, PdfService $pdf, $tempPathBaseUrl)
    {
        /** @var AnlagenReports|null $report */
        $session = $this->container->get('session');
        $searchstatus       = $session->get('search');
        $searchtype         = $session->get('type');
        $anlageq            = $session->get('anlage');
        $searchmonth        = $session->get('month');
        $searchyear         = $session->get('search_year');
        $route              = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABSOLUTE_PATH);
        $route              = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&searchyear=".$searchyear."&search=yes";
        $report             = $reportsRepository->find($id);
        $month              = $report->getMonth();
        $year               = $report->getYear();
        $reportCreationDate = $report->getCreatedAt()->format('Y-m-d H:i:s');
        $anlage             = $report->getAnlage();
        $currentDate        = date('Y-m-d H-i');
        $reportArray        = $report->getContentArray();
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
                                'kwpeak'        => $anlage->getPnom(),
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
                        $secretToken = '550725b81db78b424fbaf4b88d05efdfececf25c6ff81d8bcd0cbcb496c1e6a8';
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

                        $pdf->createPdf($result, 'string', $anlage->getAnlName().'_EPC-Report_'.$month.'_'.$year.'.pdf');

                }
                break;
            case 'epc-new-pr':
                $pdfFilename = 'EPC Report ' . $anlage->getAnlName() . ' - ' . $currentDate . '.pdf';
                $result = $this->renderView('report/epcReportPR.html.twig', [
                    'anlage'        => $anlage,
                    'monthsTable'   => $reportArray['monthTable'],
                    'forcast'       => $reportArray['forcastTable'],
                    'pldTable'      => $reportArray['pldTable'],
                    'legend'        => $anlage->getLegendEpcReports(),
                    // 'chart1'            => $chartYieldPercenDiff,
                    // 'chart2'            => $chartYieldCumulativ,
                ]);

                $pdf->createPdf($result, 'string', $anlage->getAnlName().'_EPC-Report_'.$month.'_'.$year.'.pdf');

                /*
                $response = new BinaryFileResponse($pdf->createPdf($result, 'string'));
                $response->headers->set('Content-Type', 'application/pdf');
                $response->deleteFileAfterSend(true);
                $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $anlage->getAnlName().'_EPC-Report_'.$month.'_'.$year.'.pdf'
                );
                */
                break;
            case 'monthly-report':
                //standard G4N Report (an O&M Goldbeck angelehnt)
                switch ($report->getReportTypeVersion()) {
                    case 1: // Version 1 -> Calulation on demand, store to serialized array and buil pdf and xls from this Data
                        $reportsMonthly->exportReportToPDF($anlage, $report);
                        break;
                    default: // old Version
                        $output = $reportService->buildMonthlyReport($anlage, $report->getContentArray(), $reportCreationDate, 0, 0, true);
                }
                break;
            case 'am-report':
                $report = $reportsRepository->find($id);
                if ($report) {
                    $output = $report->getContentArray();
                    $form = $this->createForm(AssetManagementeReportFormType::class);
                    $form->handleRequest($request);
                    if ($form->isSubmitted() && $form->isValid()) {
                        $data = $form->getData();
                        #$output['data'] = $data;
                        $result = $this->renderView('report/assetreport.html.twig', [
                            'invNr' => count($output['plantAvailabilityMonth']),
                            'comments' => $report->getComments(),
                            'data' => $data,
                            'anlage' => $anlage,
                            'year' => $output['year'],
                            'month' => $output['month'],
                            'reportmonth' => $output['reportmonth'],
                            'montharray' => $output['monthArray'],
                            'degradation' => $output['degradation'],
                            'forecast_PVSYST_table' => $output['forecast_PVSYST_table'],
                            'forecast_PVSYST' => $output['forecast_PVSYST'],
                            'forecast_G4N_table' => $output['forecast_G4N_table'],
                            'forecast_G4N' => $output['forecast_G4N'],
                            'dataMonthArray' => $output['dataMonthArray'],
                            'dataMonthArrayFullYear' => $output['dataMonthArrayFullYear'],
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
                            'table_overview_dayly' => $output['table_overview_dayly'],
                            'plantAvailabilityCurrentYear' => $output['plantAvailabilityCurrentYear'],
                            'daysInReportMonth' => $output['daysInReportMonth'],
                            'tableColsLimit' => $output['tableColsLimit'],
                            'acGroups' => $output['acGroups'],
                            'availability_Year_To_Date' => $output['availability_Year_To_Date'],
                            'Availability_Year_To_Date_Table' => $output['Availability_Year_To_Date_Table'],
                            'failures_Year_To_Date' => $output['failures_Year_To_Date'],
                            'plant_availability' => $output['plant_availability'],
                            'actual' => $output['actual'],
                            'plantAvailabilityMonth' => $output['plantAvailabilityMonth'],
                            'operations_currents_dayly_table' => $output['operations_currents_dayly_table'],
                            'income_per_month' => $output['income_per_month'],
                            'income_per_month_chart' => $output['income_per_month_chart'],
                            'economicsMandy' => $output['economicsMandy'],
                            'total_Costs_Per_Date' => $output['total_Costs_Per_Date'],
                            'operating_statement_chart' => $output['operating_statement_chart'],
                            'economicsCumulatedForecast' => $output['economicsCumulatedForecast'],
                            'economicsCumulatedForecastChart' => $output['economicsCumulatedForecastChart'],
                            'lossesComparedTable' => $output['lossesComparedTable'],
                            'losses_compared_chart' => $output['losses_compared_chart'],
                            'lossesComparedTableCumulated' => $output['lossesComparedTableCumulated'],
                            'cumulated_losses_compared_chart' => $output['cumulated_losses_compared_chart'],
                            'availabilityMonthTable' => $output['availabilityMonthTable'],
                            'fails_month' => $output['fails_month'],
                            'ticketCountTable' => $output['ticketCountTable'],
                            'ticketCountTableMonth' => $output['ticketCountTableMonth'],
                            'kwhLossesMonthTable' => $output['kwhLossesMonthTable'],
                            'kwhLossesYearTable' => $output['kwhLossesYearTable'],
                            'economicsMandy2' => $output['economicsMandy2'],
                            'wkhLossesChartMonth' => $output['wkhLossesChartMonth'],
                            'TicketAvailabilityMonthTable' => $output['TicketAvailabilityMonthTable'],
                            'TicketAvailabilityYearTable' => $output['TicketAvailabilityYearTable'],
                        ]);

                        $filename = $anlage->getAnlName() . '_AssetReport_' . $month . '_' . $year . '.pdf';
                        $result = str_replace('src="//', 'src="https://', $result);
                        $pdf->createPdf($result, 'string', $filename);

                        return $this->redirect($route);
                    }

                    return $this->render('report/_form.html.twig', [
                        'assetForm' => $form->createView(),
                        'anlage' => $anlage,
                    ]);

                }
        }
        return $this->redirect($route);
    }


    #[Route(path: '/reporting/excel/{id}', name: 'app_reporting_excel')]
    public function showReportAsExcel($id, ReportEpcService $reportEpcService, ReportService $reportService, ReportsRepository $reportsRepository, ReportsMonthlyService $reportsMonthly)
    {
        $session = $this->container->get('session');
        $searchstatus = $session->get('search');
        $searchtype = $session->get('type');
        $anlageq = $session->get('anlage');
        $searchmonth = $session->get('month');
        $searchyear = $session->get('search_year');
        $route = $this->generateUrl('app_reporting_list', [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $route .= '?anlage='.$anlageq.'&searchstatus='.$searchstatus.'&searchtype='.$searchtype.'&searchmonth='.$searchmonth.'&searchyear='.$searchyear.'&search=yes';
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
                    case 1: // Version 1 -> Calulation on demand, store to serialized array and buil pdf and xls from this Data
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

    #[Route(path: '/reporting/html/{id}', name: 'app_reporting_html')]
    public function showReportAsHtml($id, ReportsRepository $reportsRepository, Request $request, ReportService $reportService, NormalizerInterface $serializer, ReportsEpcNewService $epcNewService, ReportsMonthlyService $reportsMonthly) : Response
    {
        $result = "<h2>Something is wrong !!! (perhaps no Report ?)</h2>";
        $report = $reportsRepository->find($id);
        if ($report) {
            /** @var AnlagenReports|null $report */
            $session=$this->container->get('session');
            $searchstatus   = $session->get('search');
            $searchtype     = $session->get('type');
            $anlageq        = $session->get('anlage');
            $searchmonth    = $session->get('month');
            $route          = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABSOLUTE_PATH);
            $route          = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&search=yes";

            $report = $reportsRepository->find($id);
            $month = $report->getMonth();
            $year = $report->getYear();
            $reportCreationDate = $report->getCreatedAt()->format('Y-m-d h:i:s');
            $anlage = $report->getAnlage();
            $currentDate = date('Y-m-d H-i');
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

                case 'monthly-report':
                    switch ($report->getReportTypeVersion()) {
                        case 1: // Version 1 -> Calulation on demand, store to serialized array and buil pdf and xls from this Data
                            $reportout = new ReportMonthly($reportArray);
                            $result = $reportout->run()->render('ReportMonthly', true);
                            break;
                        default: // old Version
                            $result = $reportService->buildMonthlyReport($anlage, $reportArray, $reportCreationDate, 0, 0, false);
                    }
                    break;

                case 'am-report':
                    $output = $report->getContentArray();
                    $anlage = $report->getAnlage();
                    $form = $this->createForm(AssetManagementeReportFormType::class);
                    $form->handleRequest($request);
                    $data = $form->getData();
                    $output["data"] = $data;
                    //dd($output['wkhLossesChartMonth'], $output['operations_right'], $output['economicsCumulatedForecastChart']);
                    if ($form->isSubmitted() && $form->isValid()) {
                        $result = $this->renderView('report/assetreport.html.twig', [
                            'invNr' => count($output["plantAvailabilityMonth"]),
                            'comments' => $report->getComments(),
                            'data' => $data,
                            'anlage' => $anlage,
                            'year' => $output['year'],
                            'month' => $output['month'],
                            'reportmonth' => $output['reportmonth'],
                            'montharray' => $output['monthArray'],
                            'degradation' => $output['degradation'],
                            'forecast_PVSYST_table' => $output['forecast_PVSYST_table'],
                            'forecast_PVSYST' => $output['forecast_PVSYST'],
                            'forecast_G4N_table' => $output['forecast_G4N_table'],
                            'forecast_G4N' => $output['forecast_G4N'],
                            'dataMonthArray' => $output['dataMonthArray'],
                            'dataMonthArrayFullYear' => $output['dataMonthArrayFullYear'],
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
                            'Availability_Year_To_Date_Table' => $output['Availability_Year_To_Date_Table'],
                            'operations_monthly_right_g4n_tr7' => $output['operations_monthly_right_g4n_tr7'],
                            'operations_monthly_right_iout_tr1' => $output['operations_monthly_right_iout_tr1'],
                            'operations_monthly_right_iout_tr2' => $output['operations_monthly_right_iout_tr2'],
                            'operations_monthly_right_iout_tr3' => $output['operations_monthly_right_iout_tr3'],
                            'operations_monthly_right_iout_tr4' => $output['operations_monthly_right_iout_tr4'],
                            'operations_monthly_right_iout_tr5' => $output['operations_monthly_right_iout_tr5'],
                            'operations_monthly_right_iout_tr6' => $output['operations_monthly_right_iout_tr6'],
                            'operations_monthly_right_iout_tr7' => $output['operations_monthly_right_iout_tr7'],
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
                            'economicsMandy' => $output['economicsMandy'],
                            'total_Costs_Per_Date' => $output['total_Costs_Per_Date'],
                            'operating_statement_chart' => $output['operating_statement_chart'],
                            'economicsCumulatedForecast' => $output['economicsCumulatedForecast'],
                            'economicsCumulatedForecastChart' => $output['economicsCumulatedForecastChart'],
                            'lossesComparedTable' => $output['lossesComparedTable'],
                            'losses_compared_chart' => $output['losses_compared_chart'],
                            'lossesComparedTableCumulated' => $output['lossesComparedTableCumulated'],
                            'cumulated_losses_compared_chart' => $output['cumulated_losses_compared_chart'],
                            'availabilityMonthTable' => $output['availabilityMonthTable'],
                            'fails_month' => $output['fails_month'],
                            'ticketCountTable' => $output['ticketCountTable'],
                            'ticketCountTableMonth' => $output['ticketCountTableMonth'],
                            'kwhLossesMonthTable' => $output['kwhLossesMonthTable'],
                            'kwhLossesYearTable' => $output['kwhLossesYearTable'],
                            'economicsMandy2' => $output['economicsMandy2'],
                            'wkhLossesChartMonth' => $output['wkhLossesChartMonth'],
                            'TicketAvailabilityMonthTable' => $output['TicketAvailabilityMonthTable'],
                            'TicketAvailabilityYearTable' => $output['TicketAvailabilityYearTable'],
                        ]);
                        break;
                    }
                    return $this->render('report/_form.html.twig', [
                        'assetForm' => $form->createView(),
                        'anlage' => $anlage
                    ]);

                case 'epc-report':
                    switch ($anlage->getEpcReportType()) {
                        case 'prGuarantee' :
                            $result = "<h2>PR Guarantee - not ready</h2>";
                            break;
                        case 'yieldGuarantee' :
                            $result = $this->renderView('report/epcReport.html.twig', [
                                'anlage'            => $anlage,
                                'monthsTable'       => $reportArray['monthTable'],
                                'forcast'           => $reportArray['forcastTable'],
                                'legend'            => $anlage->getLegendEpcReports(),
                                'chart1'            => $epcNewService->chartYieldPercenDiff($anlage, $reportArray['monthTable']),//$reportArray['chartYieldPercenDiff'],
                                'chart2'            => $epcNewService->chartYieldCumulative($anlage, $reportArray['monthTable']),
                            ]);
                            break;
                    }
                    break;
                case 'epc-new-pr':

                    $result = $this->renderView('report/epcReportPR.html.twig', [
                        'anlage'        => $anlage,
                        'monthsTable'   => $reportArray['monthTable'],
                        'forcast'       => $reportArray['forcastTable'],
                        'pldTable'      => $reportArray['pldTable'],
                        'legend'        => $anlage->getLegendEpcReports(),
                        // 'chart1'            => $chartYieldPercenDiff,
                        // 'chart2'            => $chartYieldCumulativ,
                    ]);
                    break;
            }
        }
        return $this->render('reporting/showHtml.html.twig', [
            'html' => $result,
        ]);
    }

    /**
     * generate an Excel table based on the report data
     */
    #[Route(path: '/reporting/newExcel/{id}', name: 'app_reporting_new_excel')]
    public function showReportAsNewExcel($id, ReportEpcService $reportEpcService, ReportService $reportService, ReportsRepository $reportsRepository, ReportsMonthlyService $reportsMonthly): RedirectResponse
    {
        $session = $this->container->get('session');
        $searchstatus = $session->get('search');
        $searchtype = $session->get('type');
        $anlageq = $session->get('anlage');
        $searchmonth = $session->get('month');
        $searchyear = $session->get('search_year');
        //reporting list path
        $route = $this->generateUrl('app_reporting_list', [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $route .= '?anlage='.$anlageq.'&searchstatus='.$searchstatus.'&searchtype='.$searchtype.'&searchmonth='.$searchmonth.'&searchyear='.$searchyear.'&search=yes';


        /** @var AnlagenReports|null $report */
        $report = $reportsRepository->find($id);  //list of reports (monthly, epc and am -reports)
        if ($report){
            if (strcmp($report->getReportType(),"monthly-report") === 0){
                    return $this->redirect($route);
            } elseif (strcmp($report->getReportType(),"epc-report") === 0){
                $recordContent=$report->getContentArrayForExcel();
                if (array_key_exists('monthTable',$recordContent) && array_key_exists('forcastTable',$recordContent) && count($recordContent)===2){
                    $reports= $report->getContentArrayForExcel();
                    $this->exportAsExcelTable($reports);
                } else {
                    return $this->redirect($route);
                }
            } else {
                return $this->redirect($route);
            }
        } else {
            return $this->redirect($route);
        }

        return $this->redirect($route);
    }


   //ToDo: have to be moved to a service
    private function exportAsExcelTable($all_reports):void
    {
        // Generating SpreadSheet
        $spreadsheet = new Spreadsheet();

        // Set default font
        try {
            $spreadsheet->getDefaultStyle()
                ->getFont()
                ->setName('Arial')
                ->setSize(10);
        } catch (Exception $e) {

        }

        //help to check if new worksheets are needed
        $loop_counter=0;

        foreach ($all_reports as $reportType => $reports) {

            if ($loop_counter > 0) {

                //create new Worksheet
                $spreadsheet->createSheet();

                //set the new Worksheet as active sheet
                try {
                    $spreadsheet->setActiveSheetIndex($loop_counter);
                } catch (Exception $e) {
                }
            }
            // get the active sheet
            $sheet = $spreadsheet->getActiveSheet();

            //set the active sheet Title
            $sheet->setTitle($reportType);

            //check if the array is multidimensional
            if (is_array($reports[array_key_first($reports)])) {
                $report = (array)$reports[array_key_first($reports)];

                //set columm dimension to auto size

                $arraySize=count($report);

                $alphabets=[];
                $first='A';

                for ($i=0 ; $i< $arraySize;$i++){
                    $alphabets[]=$first++;
                }

                //heading
                $sheet->setCellValue('A1',$reportType);



                //merge heading
                $lastCell=$alphabets[$arraySize-1];

                $lastCell1=$lastCell.'1';

                try {
                    $sheet->mergeCells("A1:{$lastCell1}");
                } catch (Exception $e) {
                }

                //set heading font style

                $sheet->getStyle('A1')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(12)
                    ->setColor( new Color( Color::COLOR_BLACK ) );


                //set heading text Alignment
                $sheet->getStyle('A1')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                //set columm dimension to auto size
                foreach ($alphabets as $letter){

                    $sheet->getColumnDimension($letter)
                        ->setAutoSize(true);
                }

                //header text
                $keys=[];
                foreach ($alphabets as $letter){

                    $cell=$letter.'2';

                    $key=key($report);
                    $keys[]=$key;

                    $sheet->setCellValue($cell,$key);
                    next($report);


                }

                //set header background color and fond using styling array
                //styling arrays
                //table head style

                $tableHead =[
                    'font'=>[
                        'color'=>[
                            'rgb'=> '000000'
                        ],
                        'bold'=>true,
                        'size'=>11
                    ],
                    'alignment'=>[
                        'horizontal'=>Alignment::HORIZONTAL_CENTER
                    ]
                ];

                $lastCell2=$lastCell.'2';
                $sheet->getStyle("A2:{$lastCell2}")->applyFromArray($tableHead);


                //The content
                //current row
                $row=3;


                foreach ($reports as $data ) {
                    $col=0;
                    foreach ($keys as $key){
                        $sheet->setCellValue($alphabets[$col] . $row, $data[$key]);
                        $col++;
                    }

                    //increment row
                    $row++;
                }

                $loop_counter++;



            }
            else {
                $report = $reports;

                //set columm dimension to auto size

                $alphabets=[ 'A','B'];
                //heading
                $sheet->setCellValue('A1',$reportType);

                //merge heading

                try {
                    $sheet->mergeCells("A1:B1");
                } catch (Exception $e) {
                }

                //set heading font style

                $sheet->getStyle('A1')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(12)
                    ->setColor( new Color( Color::COLOR_BLACK ) );


                //set heading text Alignment
                $sheet->getStyle('A1')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                //set columm dimension to auto size
                foreach ($alphabets as $letter){

                    $sheet->getColumnDimension($letter)
                        ->setAutoSize(true);
                }

                //set header background color and fond using styling array
                //styling arrays
                //table head style

                $tableHead =[
                    'font'=>[
                        'color'=>[
                            'rgb'=> '000000'
                        ],
                        'bold'=>true,
                        'size'=>11
                    ]
                ];

                //The content
                //current row
                $row=2;

                foreach ($reports as $key => $value ) {
                    $sheet->setCellValue('A' . $row, $key);
                    $sheet->setCellValue('B' . $row, $value);
                    $row++;
                }
                $sheet->getStyle("A2:A{$row}")->applyFromArray($tableHead);
                $loop_counter++;

            }

        }



        // Set the header first , so the result will be treated as a xlsx file
        header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        //make it an attachment so we can define filename
        header('Content-Disposition: attachment;filename="report2.xlsx"');


        // Write and send created spreadsheet
        $writer = new Xlsx($spreadsheet);
        try {
            $writer->save('php://output');
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
        }

        // This exit(); is required to prevent errors while opening the generated .xlsx
        exit();


    }
    private function exportAsExcelTableOption2($all_reports):void
    {
        // Generating SpreadSheet
        $spreadsheet = new Spreadsheet();

        // Set default font
        try {
            $spreadsheet->getDefaultStyle()
                ->getFont()
                ->setName('Arial')
                ->setSize(10);
        } catch (Exception $e) {

        }

        //help to check if new worksheets are needed
        $loop_counter=0;

        foreach ($all_reports as $reportType => $reports){

            if($loop_counter>0){

                //create new Worksheet
                $spreadsheet->createSheet();

                //set the new Worksheet as active sheet
                try {
                    $spreadsheet->setActiveSheetIndex($loop_counter);
                } catch (Exception $e) {
                }
            }
            // get the active sheet
            $sheet = $spreadsheet->getActiveSheet();

            //set the active sheet Title
            $sheet->setTitle($reportType);

            //check if the array is multidimensional
            if(is_array($reports[array_key_first($reports)])){
                $report=(array)$reports[array_key_first($reports)];
            }else{
                $report=$reports;
            }


            //set columm dimension to auto size

            $arraySize=count($report);

            $alphabets=[];
            $first='A';

            for ($i=0 ; $i< $arraySize;$i++){
                $alphabets[]=$first++;
            }

            //heading
            $sheet->setCellValue('A1',$reportType);



            //merge heading
            $lastCell=$alphabets[$arraySize-1];

            $lastCell1=$lastCell.'1';

            try {
                $sheet->mergeCells("A1:{$lastCell1}");
            } catch (Exception $e) {
            }

            //set heading font style

            $sheet->getStyle('A1')
                ->getFont()
                ->setBold(true)
                ->setSize(12)
                ->setColor( new Color( Color::COLOR_BLACK ) );


            //set heading text Alignment
            $sheet->getStyle('A1')
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);


            foreach ($alphabets as $letter){
                //set columm dimension to auto size
                $sheet->getColumnDimension($letter)
                    ->setAutoSize(true);
            }

            //header text
            $keys=[];
            foreach ($alphabets as $letter){

                $cell=$letter.'2';

                $key=key($report);
                $keys[]=$key;

                $sheet->setCellValue($cell,$key);
                next($report);


            }

            //set header background color and fond using styling array
            //styling arrays
            //table head style

            $tableHead =[
                'font'=>[
                    'color'=>[
                        'rgb'=> '000000'
                    ],
                    'bold'=>true,
                    'size'=>11
                ],
                'alignment'=>[
                    'horizontal'=>Alignment::HORIZONTAL_CENTER
                ]
            ];

            $lastCell2=$lastCell.'2';
            $sheet->getStyle("A2:{$lastCell2}")->applyFromArray($tableHead);


            //The content
            //current row
            $row=3;

            if(is_array($reports[array_key_first($reports)])){
                foreach ($reports as $data ) {
                    $col=0;
                    foreach ($keys as $key){
                        $sheet->setCellValue($alphabets[$col] . $row, $data[$key]);
                        $col++;
                    }

                    //increment row
                    $row++;
                }
            }else{
                $col=0;
                foreach ($reports as $value ) {
                    $sheet->setCellValue($alphabets[$col] . $row, $value);
                    $col++;
                }
            }
            $loop_counter++;

        }


            // Set the header first , so the result will be treated as a xlsx file
            header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            //make it an attachment so we can define filename
            header('Content-Disposition: attachment;filename="report.xlsx"');


            // Write and send created spreadsheet
            $writer = new Xlsx($spreadsheet);
        try {
            $writer->save('php://output');
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
        }

        // This exit(); is required to prevent errors while opening the generated .xlsx
            exit();


    }

    /** (Steve)
     * generate PDF
     */
    #[Route(path: '/new_reporting/pdf/{id}', name: 'app_reporting_new_pdf')]
    public function newShowReportAsPdf(Request $request, $id, ReportService $reportService, ReportsRepository $reportsRepository, NormalizerInterface $serializer, ReportsEpcNewService $epcNewService, MonthlyService $reportsMonthly, $tempPathBaseUrl, $kernelProjectDir)
    {
        /** @var AnlagenReports|null $report */
        $session = $this->container->get('session');
        $pdf = new PdfService($tempPathBaseUrl);
        $searchstatus       = $session->get('search');
        $searchtype         = $session->get('type');
        $anlageq            = $session->get('anlage');
        $searchmonth        = $session->get('month');
        $searchyear         = $session->get('search_year');
        $route              = $this->generateUrl('app_reporting_list',[], UrlGeneratorInterface::ABSOLUTE_PATH);
        $route              = $route."?anlage=".$anlageq."&searchstatus=".$searchstatus."&searchtype=".$searchtype."&searchmonth=".$searchmonth."&searchyear=".$searchyear."&search=yes";
        $report             = $reportsRepository->find($id);
        $month              = $report->getMonth();
        $year               = $report->getYear();
        $reportCreationDate = $report->getCreatedAt()->format('Y-m-d H:i:s');
        $anlage             = $report->getAnlage();
        $currentDate        = date('Y-m-d H-i');
        $reportArray        = $report->getContentArrayForExcel();


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
                                'kwpeak'        => $anlage->getPnom(),
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
                        $secretToken = '550725b81db78b424fbaf4b88d05efdfececf25c6ff81d8bcd0cbcb496c1e6a8';
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
                        $response->headers->set('Content-Type', 'application/pdf');
                        $response->deleteFileAfterSend(true);
                        $response->setContentDisposition(
                            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                            $anlage->getAnlName().'_EPC-Report_'.$month.'_'.$year.'.pdf'
                        );

                        return $response;
                }
                break;
            case 'epc-new-pr':
                $pdfFilename = 'EPC Report ' . $anlage->getAnlName() . ' - ' . $currentDate . '.pdf';
                $result = $this->renderView('report/epcReportPR.html.twig', [
                    'anlage'        => $anlage,
                    'monthsTable'   => $reportArray['monthTable'],
                    'forcast'       => $reportArray['forcastTable'],
                    'pldTable'      => $reportArray['pldTable'],
                    'legend'        => $anlage->getLegendEpcReports(),
                    // 'chart1'            => $chartYieldPercenDiff,
                    // 'chart2'            => $chartYieldCumulativ,
                ]);

                $response = new BinaryFileResponse($pdf->createPdfTemp($anlage, $result, 'string'));
                $response->headers->set('Content-Type', 'application/pdf');
                $response->deleteFileAfterSend(true);
                $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $anlage->getAnlName().'_EPC-Report_'.$month.'_'.$year.'.pdf'
                );
                break;
            case 'monthly-report':
                $result = $this->renderView('report/newMonthlyReport.html.twig', [
                     'reportContentHeadline'    => count($reportArray['headline'])===1?$this->convertToarray($reportArray['headline']):$reportArray['headline'],
                     'reports'                  =>[
                        'energy_production'                     => count($reportArray['energyproduction'])===1?$this->convertToarray($reportArray['energyproduction']):$reportArray['energyproduction'],
                        'performance_ratio_and_availability'    =>count($reportArray['performanceratioandavailability'])===1?$this->convertToarray($reportArray['performanceratioandavailability']):$reportArray['performanceratioandavailability'],
                        'day_values'                            =>count($reportArray['dayvalues'])===1?$this->convertToarray($reportArray['dayvalues']):$reportArray['dayvalues'],
                        'case5'                                 => count($reportArray['case5'])===1?$this->convertToarray($reportArray['case5']):$reportArray['case5'],
                        'irradiation_and_tempvalues'            => count($reportArray['irradiationandtempvalues'])===1?$this->convertToarray($reportArray['irradiationandtempvalues']):$this->arrayEqualizer($reportArray['irradiationandtempvalues']),
                        'day_chart_values'                      => count($reportArray['daychartvalues'])===1?$this->convertToarray($reportArray['daychartvalues']):$reportArray['daychartvalues'],
                        'legend'                                => count($reportArray['legend'])===1?$this->convertToarray($reportArray['legend']):$reportArray['legend'],
                        'own_params'                            => count($reportArray['ownparams'])===1?$this->convertToarray($reportArray['ownparams']):$reportArray['ownparams'],
                     ],
                    'dictionary'=>[
                          'PD'=>'Period / Duration',
                          'GMNB'=>'Grid meter [kWh](Netzbetreiber)',
                          'GMNA'=>'Grid meter [kWh](Netzanalysegerät)',
                          'IOUT'=>'Inverter out [kWh](kumulierte Werte der einzelnen WR)',
                          'kwPeakPvSyst'=>'kw Peak Pv Syst',
                          'G4NExpected'=>'G4N Expected[kWh]',
                          "Availability1" => 'Availability 1',
                          "Availability2" => 'Availability 2',
                          'datum'=>'Datum',
                          "PowerEvuMonth" => 'Power EVU [kWh]',
                          "powerEGridExt" => 'Power EVU (ext) [kWh]',
                          "spezYield" => 'Spec. Yield [kWh/kWp]',
                          "prEvuEpc" => 'PR EPC',
                          "prEvuDefault" => 'PR',
                          "irradiation" => 'Irradiation [W/qm]',
                          "plantAvailability" => 'PA',
                          "powerTheo" => 'Theoretical power [kWh]',
                          "powerExp" => 'Power Exp [kWh]',
                          "case5perDay" => 'case5 per Day',
                          "GH_041" => '',
                          "GH_141" => '',
                          "GM_141" => '',
                          "GM_151" => '',
                          "Avg_temp" => '',
                          "prEvuProz" => '',
                          "row" => 'Row',
                          "title" => 'Title',
                          "unit" => 'Unit',
                          "description" =>'Description',
                          "source" => 'Source',
                          "logoPath" => 'Logo Path',
                          "doctype" => 'Doctype',
                          "footerType" => 'Footer Type',
                          "month" => 'Month',
                          "year" => 'Year',
                          "plant_name" => 'Plant Name',
                          "plant_power" => 'Pnom [kWp]',
                          "projektid" => 'Project ID',
                          "anlagenId" => 'Plant ID',
                          "showAvailability" => 'Show Availability',
                          "showAvailabilitySecond" => 'Show Availability Second',
                          "useGridMeterDayData" => 'use Grid Meter Day Data',
                          "useEvu" => '',
                          "showPvSyst" => 'show PvSys',
                          "showHeatAndTemperaturTable" => 'Show Heat And Temperature Table',
                          "reportCreationDate" => 'Report Creation Date'
                    ]



                ]);



                $pdf = new ChromePdf('/usr/bin/chromium');
                $pos = $this->substr_Index($kernelProjectDir, '/', 5);
                $pathpart = substr($kernelProjectDir, $pos);

                $pdf->output('/usr/home/pvpluy/public_html' . $pathpart . '/public/' . $anlage->getAnlName() . '_AssetReport_' . $month . '_' . $year . '.pdf');

                $reportfile = fopen('/usr/home/pvpluy/public_html' . $pathpart . '/public/' . $anlage->getAnlName() . '_AssetReport_' . $month . '_' . $year . '.html', "w") or die("Unable to open file!");
                //cleanup html
                $pos = strpos($result, '<html>');
                fwrite($reportfile, substr($result, $pos));
                fclose($reportfile);
                $pdf->generateFromHtml(substr($result, $pos));
                $pdf->generateFromFile('/usr/home/pvpluy/public_html' . $pathpart . '/public/' . $anlage->getAnlName() . '_AssetReport_' . $month . '_' . $year . '.html');
                $filename = $anlage->getAnlName() . '_AssetReport_' . $month . '_' . $year . '.pdf';

                $pdf->output($filename);


                header("Content-type: application/pdf");
                header("Content-Length: " . filesize('/usr/home/pvpluy/public_html' . $pathpart . '/public/' . $anlage->getAnlName() . '_AssetReport_' . $month . '_' . $year . '.pdf'));
                header("Content-type: application/pdf");

                // Send the file to the browser.
                readfile('/usr/home/pvpluy/public_html' . $pathpart . '/public/' . $anlage->getAnlName() . '_AssetReport_' . $month . '_' . $year . '.pdf');


                break;
        }
        return $this->redirect($route);
    }

    //ToDo: have to be moved to a service
    private function convertToarray($data): array
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($data));
        return iterator_to_array($it,true);
    }
    private function arrayEqualizer($inputs): array
    {
        $max_size=0;
        $index=0;
       foreach ($inputs as $key=> $value){
           if(count($value)>$max_size){
               $max_size=count($value);
               $index=$key;
           }
        }

       $tmp=[];
        foreach ($inputs as $key=> $value){
            if($key === $index ){
                $tmp[]= $value;
            }else{

               foreach ($inputs[$index] as $x=>$y) {
                   if(array_key_exists($x,$value)){
                       $inputs[$index][$x]=$value[$x];
                   }else{
                       $inputs[$index][$x]=null;
                   }
               }
               $tmp[]=$inputs[$index];
            }
        }

        return $tmp;
    }

    #[Deprecated]
    private function substr_Index($str, $needle, $nth): bool|int
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

