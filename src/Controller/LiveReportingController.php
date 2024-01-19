<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\Reports\ReportEpcService;
use App\Service\Reports\ReportsEpcYieldV2;
use App\Service\Reports\ReportsMonthlyV2Service;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LiveReportingController extends AbstractController
{
    /**
     * Erzeugt einen Monatsreport mit den einzelenen Tagen und einer Monatstotalen
     * Kann auch für einen Auswal einiger Tage eines Moants genutzt werden
     *
     * @param Request $request
     * @param AnlagenRepository $anlagenRepository
     * @param ReportsMonthlyV2Service $reportsMonthly
     * @return Response
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    #[Route(path: '/livereport/month', name: 'month_daily_report')]
    public function monthlyReportWithDays(Request $request, AnlagenRepository $anlagenRepository, ReportsMonthlyV2Service $reportsMonthly): Response
    {
        $output = $table = null;
        $startDay = $request->request->get('start-day');
        $endDay = $request->request->get('end-day');
        $month = $request->request->get('month');
        $year = $request->request->get('year');
        $anlageId = $request->request->get('anlage-id');
        $submitted = $request->request->get('new-report') == 'yes' && isset($month) && isset($year);

        // Start individual part
        /** @var Anlage $anlage */
        $headline = 'Monats Bericht (Testumgebung)';
        $anlagen = $anlagenRepository->findAllActiveAndAllowed();

        if ($submitted && $anlageId !== null) {
            $anlage = $anlagenRepository->findOneByIdAndJoin($anlageId);
            $output['days'] = $reportsMonthly->buildTable($anlage, $startDay, $endDay, $month, $year);
        }

        return $this->render('live_reporting/reportMonthlyNew.html.twig', [
            'headline' => $headline,
            'anlagen' => $anlagen,
            'anlage' => $anlage,
            'report' => $output,
            'status' => $anlageId,
            'datatable' => $table,
        ]);

    }

    /**
     * Erzeugt Reports für einen längeren Zeitraum, aber maximal 1 Wert pro Monat
     *
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     */
    #[Route(path: '/livereport/individual', name: 'individual_report')]
    public function reportIndividual(Request $request, AnlagenRepository $anlagenRepository, ReportsMonthlyV2Service $reportsMonthly, AvailabilityByTicketService $availabilityByTicket): Response
    {
        $output = $table = null;

        $anlageId = $request->request->get('anlage-id');
        $startDate = date_create($request->request->get('start-day'));
        $endDate = date_create($request->request->get('end-day'));

        $submittedNew = $request->request->get('new-report') == 'yes' && $anlageId !== "";
        $submittedPA = $request->request->get('recalc-PA') == 'yes' && $anlageId !== "";

        // Start individual part
        /** @var Anlage $anlage */
        $headline = 'Report – individual date, but only monthly values.';
        $anlagen = $anlagenRepository->findAllActiveAndAllowed();

        if ($submittedPA) {
            $anlage = $anlagenRepository->findOneByIdAndJoin($anlageId);
            // recalculate Availability
            for ($stamp = $startDate->getTimestamp(); $stamp <= $endDate->getTimestamp(); $stamp += (24 * 3600)) {
                $day = date_create(date("Y-m-d 12:00", $stamp));
                $availabilityByTicket->checkAvailability($anlage, $day, 0);
                if (!$anlage->getSettings()->isDisableDep1()) $availabilityByTicket->checkAvailability($anlage, $day, 1);
                if (!$anlage->getSettings()->isDisableDep2()) $availabilityByTicket->checkAvailability($anlage, $day, 2);
                if (!$anlage->getSettings()->isDisableDep3()) $availabilityByTicket->checkAvailability($anlage, $day, 3);
            }
        }
        if ($submittedNew) {
            $anlage = $anlagenRepository->findOneByIdAndJoin($anlageId);
            $output['days'] = $reportsMonthly->buildTable2($anlage, $startDate, $endDate);
        }

        return $this->render('live_reporting/reportIndividualNew.html.twig', [
            'headline' => $headline,
            'message'  => '',
            'startday' => $startDate->format('Y-m-d'),
            'endday' => $endDate->format('Y-m-d'),
            'anlagen' => $anlagen,
            'anlage' => $anlage,
            'report' => $output,
            'status' => $anlageId,
        ]);

    }

    /**
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     */
    #[Route(path: '/livereport/epc', name: 'epc_live_report')]
    public function epcLiveReport(Request $request, AnlagenRepository $anlagenRepository, ReportEpcService $reportEpc, ReportsEpcYieldV2 $epcYieldV2, AvailabilityByTicketService $availabilityByTicket)
    {
        $output = $table = null;

        $anlageId = $request->request->get('anlage-id');
        $date = new \DateTime("now");

        /** @var Anlage $anlage */
        $headline = 'Report – individual date, but only monthly values.';
        $anlagen = $anlagenRepository->findAllActiveAndAllowed();

        if ($request->request->get('new-report') == 'yes' && $anlageId !== "") {
            $anlage = $anlagenRepository->findOneByIdAndJoin($anlageId);
            // recalculate Availability
            for ($stamp = $anlage->getEpcReportStart()->getTimestamp(); $stamp <= $anlage->getEpcReportEnd()->getTimestamp(); $stamp += (24 * 3600)) {
                $day = date_create(date("Y-m-d 12:00", $stamp));
                $availabilityByTicket->checkAvailability($anlage, $day, 2);
            }

            switch ($anlage->getEpcReportType()) {
                case 'prGuarantee' :
                    $reportArray = $reportEpc->reportPRGuarantee($anlage, $date);
                    break;
                case 'yieldGuarantee':
                    $monthTable = $epcYieldV2->monthTable($anlage, $date);
                    $reportArray['monthTable'] = $monthTable;
                    #$reportArray['forcastTable'] = $epcYieldV2->forcastTable($anlage, $monthTable, $date);
                    break;
                default:
                    $error = true;
                    $reportArray = [];
                    $report = null;
            }
        }

        return $this->render('live_reporting/reportEpc.html.twig', [
            'headline' => $headline,
            'message'  => '',
            'anlagen' => $anlagen,
            'anlage' => $anlage,
            'report' => $output,
            'status' => $anlageId,
        ]);
    }
}
