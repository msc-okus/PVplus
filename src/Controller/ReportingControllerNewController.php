<?php

namespace App\Controller;

use App\Entity\AnlagenReports;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Service\AssetManagementService;
use App\Service\ReportEpcPRNewService;
use App\Service\ReportEpcService;
use App\Service\ReportService;
use App\Service\ReportsMonthlyService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class ReportingControllerNewController extends AbstractController
{
    #[Route('/reporting/controller/new', name: 'app_reporting_controller_new')]
    public function index(): Response
    {
        return $this->render('reporting_controller_new/index.html.twig', [
            'controller_name' => 'ReportingControllerNewController',
        ]);
    }
    /**
     * @throws ExceptionInterface
     */
    #[Route(path: '/reportingNew/create', name: 'app_reportingnew_create', methods: ['GET', 'POST'])]
    public function createReport(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository, AnlagenRepository $anlagenRepo,
                                 ReportService $report, EntityManagerInterface $em, ReportsMonthlyService $reportsMonthly, ReportEpcService $reportEpc, ReportEpcPRNewService $reportEpcNew,
                                  AssetManagementService $assetManagement, ReportsRepository $reportRepo): Response
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
                $report = $reportRepo->findOneByAMY($aktAnlagen[0], $reportMonth, $reportYear)[0];
                $comment = '';
                if ($report) {
                    $comment = $report->getComments();
                    $em->remove($report);
                    $em->flush();
                }
                $report = new AnlagenReports();
                // then we generate our own report and try to persist it
                $output = $assetManagement->assetReport($aktAnlagen[0], $reportMonth, $reportYear, 0);
                $data = [
                    'Production' => true,
                    'ProdCap' => true,
                    'CumulatForecastPVSYS' => true,
                    'CumulatForecastG4N' => true,
                    'CumulatLosses' => true,
                    'MonthlyProd' => true,
                    'DailyProd' => true,
                    'Availability' => true,
                    'AvYearlyOverview' => true,
                    'AvMonthlyOverview' => true,
                    'AvInv' => true,
                    'StringCurr' => true,
                    'InvPow' => true,
                    'Economics' => true, ];
                $output['data'] = $data;
                $report = new AnlagenReports();
                $report
                    ->setAnlage($aktAnlagen[0])
                    ->setEigner($aktAnlagen[0]->getEigner())
                    ->setMonth($reportMonth)
                    ->setYear($reportYear)
                    ->setStartDate(date_create_from_format('d.m.y', date('d.m.y', strtotime('01.'.$reportMonth.'.'.$reportYear))))
                    ->setEndDate(date_create_from_format('d.m.y', date('d.m.y', strtotime('30.'.$reportMonth.'.'.$reportYear))))
                    ->setReportType('am-report')
                    ->setContentArray($output)
                    ->setRawReport('')
                    ->setComments($comment);
                $em->persist($report);
                $em->flush();
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
}
