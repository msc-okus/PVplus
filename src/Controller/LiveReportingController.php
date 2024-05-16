<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\TicketDate;
use App\Repository\AnlagenRepository;
use App\Repository\MonthlyDataRepository;
use App\Repository\TicketDateRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\Reports\ReportsMonthlyV2Service;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Form\LiveReporting\CreateMonthlyForm;
use Craue\FormFlowBundle\Form\FormFlow;

class LiveReportingController extends AbstractController
{
    public function __construct(
        private readonly TicketDateRepository $ticketDateRepo,
        private readonly TranslatorInterface $translator,
    )
    {

    }
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
        $output = $table = $tickets = null;
        $startDay = $request->request->get('start-day');
        $endDay = $request->request->get('end-day');
        $month = $request->request->get('month');
        $year = $request->request->get('year');
        $anlageId = $request->request->get('anlage-id');
        $submitted = $request->request->get('new-report') == 'yes' && isset($month) && isset($year);

        $formData = new MonthlyDataRepository(); // Your form data class. Has to be an object, won't work properly with an array.

        $flow = $this->getSubscribedServices('livereporting.form.flow.createMonthly'); // must match the flow's service id
        $flow->bind($formData);

        // form of the current step
        $form = $flow->createForm();

        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // form for the next step
                $form = $flow->createForm();
            } else {
                // flow finished
                $em = $this->getDoctrine()->getManager();
                $em->persist($formData);
                $em->flush();

                $flow->reset(); // remove step data from the session

                return $this->redirectToRoute('home'); // redirect when done
            }
        }

        return $this->render('live_reporting/_inc/createMonthly.html.twig', [
            'headline' => 'Monthly Report',
            'form' => $form->createView(),
            'flow' => $flow,
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
            'headline'  => 'Report – individual date, but only monthly values.',
            'message'   => '',
            'startday'  => $startDate->format('Y-m-d'),
            'endday'    => $endDate->format('Y-m-d'),
            'anlagen'   => $anlagen,
            'anlage'    => $anlage,
            'report'    => $output,
            'status'    => $anlageId,
        ]);

    }

    private function buildPerformanceTicketsOverview(Anlage $anlage, ?int $startDay = null, ?int $endDay = null, int $month = 0, int $year = 0): array
    {
        if ($startDay === null) $startDay = 1;
        $daysInMonth = (int)date('t', strtotime("$year-$month-01"));
        if ($endDay  !== null && $endDay < $daysInMonth) {
            $daysInMonth = $endDay;
        }
        $from = date_create("$year-$month-$startDay 00:00");
        $to = date_create("$year-$month-$daysInMonth 23:59");
        #$tickets = $this->ticketRepo->findBy(['anlage' => $anlage->getAnlId(), 'kpiStatus' => '10', 'alertType' => '72']);

        $tickets = $this->ticketDateRepo->performanceTickets($anlage, $from, $to);
        $ticketsOverview = [];
        /** @var TicketDate $ticket */
        $counter = 1;
        foreach ($tickets as $ticket){
            $ticketsOverview[$counter]['id'] = $ticket->getTicket()->getId();
            $ticketsOverview[$counter]['ticketName'] = $ticket->getTicket()->getTicketName();
            $ticketsOverview[$counter]['begin'] = $ticket->getBegin();
            $ticketsOverview[$counter]['end'] = $ticket->getEnd();
            $ticketsOverview[$counter]['alertType'] = $ticket->getAlertType();
            $ticketsOverview[$counter]['editor'] = $ticket->getTicket()->getEditor();
            ++$counter;
        }
        return $ticketsOverview;
    }
}
