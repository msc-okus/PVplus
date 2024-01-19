<?php

namespace App\Controller;

use App\Entity\AnlagenReports;
use App\Form\AssetManagement\AssetManagementeReportFormType;
use App\Form\Reports\ReportsFormType;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use App\Message\Command\GenerateAMReport;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Service\AssetManagementService;
use App\Service\Functions\ImageGetterService;
use App\Service\LogMessagesService;
use App\Service\PdfService;
use App\Service\ReportEpcPRNewService;
use App\Service\Reports\ReportEpcService;
use App\Service\Reports\ReportsEpcYieldV2;
use App\Service\Reports\ReportsMonthlyV2Service;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Knp\Component\Pager\PaginatorInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Psr\Cache\InvalidArgumentException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Service\SchowService;

class ReportingController extends AbstractController
{
    use G4NTrait;
    use PVPNameArraysTrait;

    public function __construct(private $kernelProjectDir)
    {
    }

    /**
     * @throws \Doctrine\Instantiator\Exception\ExceptionInterface
     * @throws InvalidArgumentException
     * @throws NoResultException
     */
    #[Route(path: '/reporting/create', name: 'app_reporting_create', methods: ['GET', 'POST'])]
    public function createReport(
        Request $request,
        PaginatorInterface $paginator,
        ReportsRepository $reportsRepository,
        AnlagenRepository $anlagenRepo,
        ReportEpcService $reportEpc,
        ReportsMonthlyV2Service $reportsMonthly,
        AssetManagementService $assetManagement,
        ReportEpcPRNewService $reportEpcNew,
        LogMessagesService $logMessages,
        MessageBusInterface $messageBus,
        EntityManagerInterface $em,
        SchowService $testService
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
        //$local = $request->query->get('local');
        $daysOfMonth = date('t', strtotime("$reportYear-$reportMonth-01"));
        $reportDate = new \DateTime("$reportYear-$reportMonth-$daysOfMonth");
        $anlageId = $request->query->get('anlage-id');
        $aktAnlagen = $anlagenRepo->findIdLike([$anlageId]);
        $userId = $this->getUser()->getUserIdentifier();
        $uid = $this->getUser()->getUserId();

        switch ($reportType) {
            case 'monthly':
                $output = $reportsMonthly->createReportV2($aktAnlagen[0], $reportMonth, $reportYear);
                break;
            case 'epc':
                $output = $reportEpc->createEpcReport($aktAnlagen[0], $reportDate);
                break;
            case 'epc-new-pr':
                $output = $reportEpcNew->createEpcReportNew($aktAnlagen[0], $reportDate);
                break;
            case 'am':
                // we try to find and delete a previous report from this month/year
                if ($_ENV['APP_ENV'] === 'prod') {
                    $report = $assetManagement->createAmReport($aktAnlagen[0], $reportMonth, $reportYear, (int)$uid);
                    $em->persist($report);
                    $em->flush();
                } else if ($_ENV['APP_ENV'] === 'dev'){
                    $logId = $logMessages->writeNewEntry($aktAnlagen[0], 'AM Report', "create AM Report " . $aktAnlagen[0]->getAnlName() . " - $reportMonth / $reportYear", (int)$uid);
                    $message = new GenerateAMReport($aktAnlagen[0]->getAnlId(), $reportMonth, $reportYear, $userId, $logId);
                    #$messageBus->dispatch($message);
                    $anlageName = $aktAnlagen[0]->getAnlName();
                    return new Response($testService->showMessege("Your AM Repoert for Plant $anlageName is ready!"));
                }
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
                return new Response(null, Response::HTTP_NO_CONTENT);
            }
        }

        $template = '_inc/_editForm.html.twig';

        return $this->render('reporting/'.$template, [
            'reportForm'    => $form,
            'report'        => $report,
            'anlage'        => $report->getAnlage(),
            'page'          => $page
        ]);
    }

    #[Route(path: '/reporting/delete/{id}', name: 'app_reporting_delete')]
    #[IsGranted('ROLE_DEV')]
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

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws ExceptionInterface
     * @throws PdfReaderException
     * @throws CrossReferenceException
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws FilterException|FilesystemException
     */
    #[Route(path: '/reporting/pdf/{id}', name: 'app_reporting_pdf')]
    public function showReportAsPdf(Request $request, $id, ReportsRepository $reportsRepository, NormalizerInterface $serializer, ReportsEpcYieldV2 $epcNewService, PdfService $pdf, Filesystem $fileSystemFtp, ImageGetterService $imageGetter): Response
    {
        /** @var AnlagenReports|null $report */
        $session            = $request->getSession();
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
                $pdfFilename = 'Report ' . $anlage->getAnlName() . ' - ' . $currentDate . '.pdf';
                switch ($anlage->getEpcReportType()) {
                    case 'prGuarantee' :
                        $headline =
                            [
                                'projektNr'     => $anlage->getProjektNr(),
                                'anlage'        => $anlage->getAnlName(),
                                'eigner'        => $anlage->getEigner()->getFirma(),
                                'date'          => $currentDate,
                                'kwpeak'        => $anlage->getPnom(),
                                'reportCreationDate' => $reportCreationDate,
                                'epcNote'       => $anlage->getEpcReportNote(),
                                'main_headline' => $report->getHeadline(),
                                'reportStatus'  => $report->getReportStatus(),
                                'month'         => $month,
                                'year'          => $year
                            ]
                        ;
                        $result = $this->renderView('report/_epc_pr_2019/epcMonthlyPRGuarantee.html.twig', [ //'report/_epc_new/epcMonthlyPRGuarantee.html.twig'
                            'headline'      => $headline,
                            'main'          => $reportArray[0],
                            'forecast'      => $reportArray[1],
                            'pld'           => $reportArray[2],
                            'header'        => $reportArray[3],
                            'legend'        => $serializer->normalize($anlage->getLegendEpcReports()->toArray(), null, ['groups' => 'legend']),
                            'forecast_real' => $reportArray['prForecast'],
                            'formel'        => $reportArray['formel'],
                            'anlage'        => $anlage,
                            'logo'          => $imageGetter->getOwnerLogo($anlage->getEigner()),
                            'report'        => $report,
                        ]);
                        $pdf->createPdf($result, 'string', $anlage->getAnlName().'_EPC-Report_'.$month.'_'.$year.'.pdf');
                        break;

                    case 'yieldGuarantee':
                        $result = $this->renderView('report/_epc_yield_2021/epcReportYield.html.twig', [ //'report/epcReportYield.html.twig'
                            'anlage'            => $anlage,
                            'monthsTable'       => $reportArray['monthTable'],
                            'forcast'           => $reportArray['forcastTable'],
                            'legend'            => $anlage->getLegendEpcReports(),
                            'chart1'            => $epcNewService->chartYieldPercenDiff($anlage, $reportArray['monthTable']),//$reportArray['chartYieldPercenDiff'],
                            'chart2'            => $epcNewService->chartYieldCumulative($anlage, $reportArray['monthTable']),
                            'logo'              => $imageGetter->getOwnerLogo($anlage->getEigner()),
                            'report'            => $report,
                        ]);
                        $pdf->createPdf($result, 'string', $anlage->getAnlName().'_EPC-Report_'.$month.'_'.$year.'.pdf');
                }
                break;

            case 'epc-new-pr':
                $pdfFilename = 'QEPC Report ' . $anlage->getAnlName() . ' - ' . $currentDate . '.pdf';
                $result = $this->renderView('report/_epc_yield_2021/epcReportYield.html.twig', [
                    'anlage'        => $anlage,
                    'monthsTable'   => $reportArray['monthTable'],
                    'forcast'       => $reportArray['forcastTable'],
                    'pldTable'      => $reportArray['pldTable'],
                    'legend'        => $anlage->getLegendEpcReports(),
                    // 'chart1'            => $chartYieldPercenDiff,
                    // 'chart2'            => $chartYieldCumulativ,
                    'logo'          => $imageGetter->getOwnerLogo($anlage->getEigner()),
                    'report'        => $report,
                ]);
                $pdf->createPdf($result, 'string', $anlage->getAnlName().'_EPC-Report_'.$month.'_'.$year.'.pdf');
                break;

            case 'monthly-report':
                //standard G4N Report (an O&M Goldbeck angelehnt)
                $pdf = new Fpdi();
                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($report->getFile()));
                for ($i = 1; $i <= $pageCount; $i++) {
                    $pdf->AddPage("L");
                    $tplId = $pdf->importPage($i);
                    $pdf->useTemplate($tplId);
                }
                $pdf->Output("D", "Monthly_Reporting_".$report->getAnlage()->getAnlName() . "_" . $report->getMonth() . "_" . $report->getYear() . ".pdf");
                break;

            case 'am-report':
                $report = $reportsRepository->find($id);
                if ($report) {
                    $output = $report->getContentArray();
                    $form = $this->createForm(AssetManagementeReportFormType::class,null,['param' => $report]);
                    $form->handleRequest($request);
                    if ($form->isSubmitted() && $form->isValid()) {
                        $data = $form->getData();
                        $files = $report->getPdfParts();

                        $pdf = new Fpdi();
                        // this is the header and we will always want to include it

                        if ( $files['head']) {
                            $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['head']));
                            for ($i = 0; $i < $pageCount; $i++) {
                                $pdf->AddPage("L");
                                $tplId = $pdf->importPage($i + 1);
                                $pdf->useTemplate($tplId);
                            }
                        }
                        if($data['TechnicalPV'] && $files['ProductionCapFactor']){
                            if ($data['ProdCap']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['ProductionCapFactor']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['PRPATable'] && $files['PRPATable']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['PRPATable']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['MonthlyProd'] && $files['MonthlyProd']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['MonthlyProd']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                        }
                        if ($data['Production']) {
                            if ($data['ProdWithForecast'] && $files['production_with_forecast']){
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['production_with_forecast']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($anlage->hasPVSYST()) {
                            if ($data['CumulatForecastPVSYS'] && $files['CumForecastPVSYS']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['CumForecastPVSYS']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                        }
                            else {
                            if ($data['CumulatForecastG4N'] && $files['CumForecastG4N']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['CumForecastG4N']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                        }
                            if ($data['CumulatLosses'] && $files['CumLosses']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['CumLosses']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['PRTable'] && $files['PRTable']){
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['PRTable']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['DailyProd'] && $files['DailyProd']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['DailyProd']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['InvRank'] && $files['InverterRank']){
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['InverterRank']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['EfficiencyRank'] && $files['InverterEfficiencyRank']){
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['InverterEfficiencyRank']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['waterfallProd'] && $files['waterfallProd']){
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['waterfallProd']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                        }

                        if ($data['Availability']){

                            if ($data['AvYearlyTicketOverview'] && $files['AvailabilityYear']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['AvailabilityYear']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['AvMonthlyOverview'] && $files['AvailabilityMonth']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['AvailabilityMonth']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                        }
                        if($data['AnalysisHeatmap']){
                            if ($data['StringCurr'] && $files['String']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['String']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['InvPow'] && $files['Inverter']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['Inverter']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['AvYearlyOverview'] && $files['AvailabilityYearOverview']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['AvailabilityYearOverview']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }
                            if ($data['AvInv'] && $files['AvailabilityByInverter']) {
                                $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['AvailabilityByInverter']));
                                for ($i = 0; $i < $pageCount; $i++) {
                                    $pdf->AddPage("L");
                                    $tplId = $pdf->importPage($i + 1);
                                    $pdf->useTemplate($tplId);
                                }
                            }

                        }
                        if ($data['Economics'] && $files['Economic']) {
                            $pageCount = $pdf->setSourceFile($fileSystemFtp->readStream($files['Economic']));
                            for ($i = 0; $i < $pageCount; $i++) {
                                $pdf->AddPage("L");
                                $tplId = $pdf->importPage($i + 1);
                                $pdf->useTemplate($tplId);
                            }
                        }
                        $pdf->Output("D", "AssetReport".$report->getAnlage()->getAnlName() . "-" . $report->getMonth() . "-" . $report->getYear() . ".pdf");

                    }

                    return $this->render('report/_form.html.twig', [
                        'assetForm' => $form,
                        'anlage' => $anlage
                    ]);

                }

        }

        return $this->redirect($route);
    }


    /**
     * @param $id
     * @param ReportsRepository $reportsRepository
     * @param Request $request
     * @param NormalizerInterface $serializer
     * @param ReportsEpcYieldV2 $epcNewService
     * @param ImageGetterService $imageGetter
     * @return Response
     * @throws ExceptionInterface
     * @throws FilesystemException
     */
    #[Route(path: '/reporting/html/{id}', name: 'app_reporting_html')]
    public function showReportAsHtml($id, ReportsRepository $reportsRepository, Request $request, NormalizerInterface $serializer, ReportsEpcYieldV2 $epcNewService, ImageGetterService $imageGetter) : Response
    {
        $result = "<h2>Something is wrong !!! (perhaps no Report ?)</h2>";
        $report = $reportsRepository->find($id);
        if ($report) {
            /** @var AnlagenReports|null $report */
            $report = $reportsRepository->find($id);
            $anlage = $report->getAnlage();
            $reportArray = $report->getContentArray();
            switch ($report->getReportType()) {

                case 'monthly-report':
                    $result = $report->getRawReport();
                    break;

                case 'am-report':
                    $output = $report->getContentArray();
                    $anlage = $report->getAnlage();
                    $form = $this->createForm(AssetManagementeReportFormType::class);
                    $form->handleRequest($request);
                    $data = $form->getData();
                    $output["data"] = $data;
                    if ($form->isSubmitted() && $form->isValid()) {
                        $result = $this->renderView('report/assetreport.html.twig', [
                            'invNr' => is_countable($output["plantAvailabilityMonth"]) ? count($output["plantAvailabilityMonth"]) : 0,
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
                            'wkhLossesTicketChartMonth' => $output['wkhLossesTicketChartMonth'],
                            'kwhLossesChartYear' => $output['kwhLossesChartYear'],
                            'TicketAvailabilityMonthTable' => $output['TicketAvailabilityMonthTable'],
                            'TicketAvailabilityYearTable' => $output['TicketAvailabilityYearTable'],
                            'monthlyLossesHelpTable' => $output['monthlyLossesHelpTable'],
                            'yearLossesHelpTable' => $output['yearLossesHelpTable'],
                            'losseskwhchartYearMonthly' => $output['losseskwhchartYearMonthly'],
                            'PercentageTableYear' => $output['PercentageTableYear'],
                            'percentageTableMonth' => $output['percentageTableMonth'],
                        ]);
                        break;
                    }
                    return $this->render('report/_form.html.twig', [
                        'assetForm' => $form,
                        'anlage' => $anlage
                    ]);

                case 'epc-report':
                    switch ($anlage->getEpcReportType()) {
                        case 'prGuarantee' :
                            $headline =
                                [
                                    'projektNr'             => $anlage->getProjektNr(),
                                    'anlage'                => $anlage->getAnlName(),
                                    'eigner'                => $anlage->getEigner()->getFirma(),
                                    'date'                  => date('Y-m-d H-i'),
                                    'kwpeak'                => $anlage->getPnom(),
                                    'reportCreationDate'    => $report->getCreatedAt()->format('Y-m-d H:i:s'),
                                    'epcNote'               => $anlage->getEpcReportNote(),
                                    'main_headline'         => $report->getHeadline(),
                                    'reportStatus'          => $report->getReportStatus(),
                                    'month'                 => $report->getMonth(),
                                    'year'                  => $report->getYear(),
                                    'logo'                  => $imageGetter->getOwnerLogo($anlage->getEigner()),
                                    'report'                => $report,
                                ]
                            ;
                            $result = $this->renderView('report/_epc_pr_2019/epcMonthlyPRGuarantee.html.twig', [ //report/_epc_new/epcMonthlyPRGuarantee.html.twig
                                'headline'      => $headline,
                                'main'          => $reportArray[0],
                                'forecast'      => $reportArray[1],
                                'pld'           => $reportArray[2],
                                'header'        => $reportArray[3],
                                'legend'        => $serializer->normalize($anlage->getLegendEpcReports()->toArray(), null, ['groups' => 'legend']),
                                'forecast_real' => $reportArray['prForecast'],
                                'formel'        => $reportArray['formel'],
                                'anlage'        => $anlage,
                                'logo'          => $imageGetter->getOwnerLogo($anlage->getEigner()),
                                'report'        => $report,
                            ]);
                            break;

                        case 'yieldGuarantee' :
                            $result = $this->renderView('report/_epc_yield_2021/epcReportYield.html.twig', [
                                'anlage'            => $anlage,
                                'monthsTable'       => $reportArray['monthTable'],
                                'forcast'           => $reportArray['forcastTable'],
                                'legend'            => $anlage->getLegendEpcReports(),
                                'chart1'            => $epcNewService->chartYieldPercenDiff($anlage, $reportArray['monthTable']),//$reportArray['chartYieldPercenDiff'],
                                'chart2'            => $epcNewService->chartYieldCumulative($anlage, $reportArray['monthTable']),
                                'logo'              => $imageGetter->getOwnerLogo($anlage->getEigner()),
                                'report'            => $report,
                            ]);
                            break;
                    }
                    break;

                case 'epc-new-pr':
                    $result = $this->renderView('report/_epc_pr_2021/epcReportPR.html.twig', [
                        'anlage'        => $anlage,
                        'monthsTable'   => $reportArray['monthTable'],
                        'forcast'       => $reportArray['forcastTable'],
                        'pldTable'      => $reportArray['pldTable'],
                        'legend'        => $anlage->getLegendEpcReports(),
                        'logo'          => $imageGetter->getOwnerLogo($anlage->getEigner()),
                        'report'        => $report,
                    ]);
                    break;
            }
        }
        return $this->render('reporting/showHtml.html.twig', [
            'html' => $result,
        ]);
    }
}