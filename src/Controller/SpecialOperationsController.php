<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\ExportService;
use App\Service\Reports\ReportsMonthlyService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class SpecialOperationsController extends AbstractController
{
    #[Route('/special/operations', name: 'app_special_operations')]
    public function index(): Response
    {
        return $this->render('special_operations/index.html.twig', [
            'controller_name' => 'SpecialOperationsController',
        ]);
    }

    #[Route(path: '/special/operations/bavelse/report', name: 'bavelse_report')]
    public function bavelseExport(Request $request, ExportService $bavelseExport, AnlagenRepository $anlagenRepository, AvailabilityByTicketService $availabilityByTicket): Response
    {
        $output = $output2 = $availability = '';

        $month = $request->request->get('month');
        $year = $request->request->get('year');
        $anlageId = $request->request->get('anlage-id');
        $submitted = $request->request->get('new-report') == 'yes' && isset($month) && isset($year);


        // Start individual part
        /** @var Anlage $anlage */
        $headline = 'Bavelse Berg Monats Bericht';
        $anlageId = '97';
        $anlagen = $anlagenRepository->findBy(['anlId' => $anlageId]);
        $anlage = $anlagenRepository->findOneBy(['anlId' => $anlageId]);
        $from = date_create($year.'-'.$month.'-01 00:01');
        $dayOfMonth = $from->format('t');
        $to = date_create($year.'-'.($month).'-'.$dayOfMonth.' 23:59');

        if ($submitted && isset($anlageId)) {
            $daysInMonth = $to->format('t');
            #$output = $bavelseExport->gewichtetTagesstrahlungAsTable($anlage, $from, $to);
            #$availability = $availabilityByTicket->calcAvailability($anlage, date_create("$year-$month-01"), date_create("$year-$month-$daysInMonth"), null, 2);
            $output2 = $bavelseExport->gewichtetTagesstrahlungOneLine($anlage, $anlage->getFacDateStart(), $to);
        }
        // End individual part

        return $this->render('special_operations/index.html.twig', [
            'headline'      => $headline,
            'anlagen'       => $anlagen,
            'availabilitys' => $availability,
            'output'        => $output,
            'output2'       => $output2,
            'status'        => $anlageId,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/special/operations/monthly', name: 'monthly_report_test')]
    public function monthlyReportTest(Request $request, AnlagenRepository $anlagenRepository, ReportsMonthlyService $reportsMonthly): Response
    {
        $output = null;
        $month = $request->request->get('month');
        $year = $request->request->get('year');
        $anlageId = $request->request->get('anlage-id');
        $submitted = $request->request->get('new-report') == 'yes' && isset($month) && isset($year);

        // Start individual part
        /** @var Anlage $anlage */
        $headline = 'Monats Bericht (Testumgebung)';
        $anlagen = $anlagenRepository->findAllActiveAndAllowed();

        if ($submitted && isset($anlageId)) {
            $anlage = $anlagenRepository->findOneBy(['anlId' => $anlageId]);
            $output = $reportsMonthly->buildMonthlyReportNew($anlage, $month, $year);
        }

        return $this->render('report/reportMonthlyNew.html.twig', [
            'headline'      => $headline,
            'anlagen'       => $anlagen,
            'anlage'        => $anlage,
            'report'        => $output,
            'status'        => $anlageId,
        ]);

    }
}
