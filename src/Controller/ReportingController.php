<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Form\Reports\ReportsFormType;
use App\Helper\G4NTrait;
use App\Reports\Goldbeck\EPCMonthlyPRGuaranteeReport;
use App\Reports\Goldbeck\EPCMonthlyYieldGuaranteeReport;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Service\ReportEpcService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use koolreport\KoolReport;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ReportingController extends AbstractController
{
    use G4NTrait;

    /**
     * @Route("/reporting", name="app_reporting_list")
     */
    public function list(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository, AnlagenRepository $anlagenRepo, ReportService $report, ReportEpcService $epcReport): Response
    {
        $q = $request->query->get('qr');
        if ($request->query->get('search') == 'yes' && $q == '') $request->getSession()->set('qr', '');
        if ($q) $request->getSession()->set('qr', $q);

        if ($q == "" && $request->getSession()->get('qr') != "") {
            $q = $request->getSession()->get('qr');
            $request->query->set('qr', $q);
        }

        $anlagen = $anlagenRepo->findAll();
        if($request->query->get('new-report') === 'yes') {
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
                    dump("Ist noch nicht fertig");
                    break;
            }
            $request->query->set('report-typ', $reportType);
            $request->query->set('month', $reportMonth);
            $request->query->set('year', $reportYear);
            $request->query->set('anlage-id', $anlageId);
        }

        $queryBuilder = $reportsRepository->getWithSearchQueryBuilder($q);

        $pagination = $paginator->paginate(
            $queryBuilder,                                    /* query NOT result */
            $request->query->getInt('page', 1),   /* page number*/
            20                                          /*limit per page*/
        );

        return $this->render('reporting/list.html.twig', [
            'pagination' => $pagination,
            'anlagen'    => $anlagen,
            'stati'      => self::reportStati(),
        ]);
    }

    /**
     * @Route("/reporting/edit/{id}", name="app_reporting_edit")
     */
    public function edit($id, ReportsRepository $reportsRepository, Request $request, Security $security, EntityManagerInterface $em): Response
    {
        $report = $reportsRepository->find($id);
        $anlage = $report->getAnlage();
        $form = $this->createForm(ReportsFormType::class, $report);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked() ) ) {

            $successMessage = 'Plant data saved!';
            $em->persist($report);
            $em->flush();
            if ($form->get('saveclose')->isClicked()) {
                $this->addFlash('success', $successMessage);
                return $this->redirectToRoute('app_reporting_list');
            }
        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_reporting_list');
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
    public function showReportAsPdf($id, ReportEpcService $reportEpcService, ReportService $reportService, ReportsRepository $reportsRepository, NormalizerInterface $serializer): RedirectResponse
    {
        /** @var AnlagenReports|null $report */
        $report = $reportsRepository->find($id);
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
        }

        return $this->redirectToRoute('app_reporting_list');
    }

    /**
     * @Route("/reporting/excel/{id}", name="app_reporting_excel")
     */
    public function showReportAsExcel($id, ReportEpcService $reportEpcService, ReportService $reportService, ReportsRepository $reportsRepository)
    {
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

        return $this->redirectToRoute('app_reporting_list');
    }


    /**
     * @Route("/test/epc/report/{id}/{pdf}", defaults={"pdf"=false})
     */
    public function epcReport($id, $pdf, AnlagenRepository $anlagenRepository, ReportEpcService $reportEpc, EntityManagerInterface $em)
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
    public function deleteReport($id, ReportsRepository $reportsRepository, Security $security, EntityManagerInterface $em)
    {
        if ($this->isGranted('ROLE_DEV'))
        {
            /** @var AnlagenReports|null $report */
            $report = $reportsRepository->find($id);
            if ($report) {
                $em->remove($report);
                $em->flush();
            }
        }

        return $this->redirectToRoute('app_reporting_list');

    }
}
