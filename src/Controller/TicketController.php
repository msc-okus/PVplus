<?php

namespace App\Controller;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Entity\Anlage;
use App\Entity\Ticket;
use App\Entity\TicketDate;
use App\Form\Model\ToolsModel;
use App\Form\Reports\ReportsFormType;
use App\Form\Ticket\TicketEditFormType;
use App\Form\Ticket\TicketFormType;
use App\Form\Tools\ToolsFormType;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Repository\TicketDateRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Service\AvailabilityService;
use App\Service\ExpectedService;
use App\Service\PRCalulationService;
use App\Service\ReportEpcService;
use App\Service\ReportService;
use App\Service\ReportsMonthlyService;
use Carbon\Doctrine\DateTimeType;
use Doctrine\ORM\EntityManagerInterface;
use http\Url;
use Knp\Component\Pager\PaginatorInterface;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\Types\Object_;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatableMessage;


class TicketController extends BaseController
{
    use PVPNameArraysTrait;

    #[Route(path: '/ticket/create', name: 'app_ticket_create')]
    public function create(EntityManagerInterface $em, Request $request) : Response
    {

        $session=$this->container->get('session');

        $form = $this->createForm(TicketFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ticket = $form->getData();
            $ticket->setEditor($this->getUser()->getUsername());
            $ticket->setInverter("*");
            $date = new TicketDate();
            $date->copyTicket($ticket);
            $ticket->addDate($date);

            dd($ticket);
            //$em->persist($ticket);
            //$em->flush();
            return new Response(null, 204);
        }

        $page= $request->query->getInt('page', 1);
        return $this->render('ticket/_inc/_edit.html.twig',[
            'ticketForm'=>$form->createView(),
            'edited' => false,
            'page'          => $page,
        ]);
    }

    #[Route(path: '/ticket/edit/{id}', name: 'app_ticket_edit')]
    public function edit($id, TicketRepository $ticketRepo, EntityManagerInterface $em, Request $request) : Response
    {
        $ticket = $ticketRepo->find($id);
        $ticketDates = $ticket->getDates();
        if($ticketDates->isEmpty()) $ticketDates = null;
        $form = $this->createForm(TicketFormType::class, $ticket);
        $page= $request->query->getInt('page', 1);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $request->attributes->set('page', $page);
            $ticket = $form->getData();
            $ticketDates = $ticket->getDates();
            $ticket->setEditor($this->getUser()->getUsername());
            if ($ticket->getStatus() === 30 && $ticket->getend() === null) $ticket->setEnd(new \DateTime("now"));
            if($ticketDates){
                if ($ticketDates->first()->getBegin < $ticket->getBegin()){
                    $ticket->setBegin($ticketDates->first()->getBegin());
                    $this->addFlash('warning', 'Inconsistent date, the date was not saved');
                }
                else{
                    $ticketDates->first()->setBegin($ticket->getBegin());
                }
                if ($ticketDates->last()->getEnd() > $ticket->getEnd()){
                    $ticket->setEnd($ticketDates->last()->getEnd());
                    $this->addFlash('warning', 'Inconsistent date, the date was not saved');
                }
                else{
                    $ticketDates->last()->setEnd($ticket->getEnd());
                }
            }
            $ticket->setStatus(30);
            $em->persist($ticket);
            $em->flush();
            return new Response(null, 204);

        }

        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm'    => $form,
            'ticket'        => $ticket,
            'edited'        => true,
            'dates'         => $ticketDates,
            'page'          => $page,
        ]);
    }

    #[Route(path: '/ticket/list', name: 'app_ticket_list')]
    public function list(TicketRepository $ticketRepo, PaginatorInterface $paginator, Request $request, AnlagenRepository $anlagenRepo, RequestStack $requestStack) : Response
    {
        $filter = [];
        $session = $requestStack->getSession();

        $pageSession = $session->get('page');
        $page = $request->query->getInt('page');
        if ($page == 0) {
            if ($pageSession == 0){
                $page = 1;
            } else {
                $page = $pageSession;
            }
        }

        //Reading data from request
        /** @var Anlage|string $anlage */
        if ($request->query->get('anlage') != '') {
            $anlage = $anlagenRepo->findOneBy(['anlName' => $request->query->get('anlage')]);
        } else {
            $anlage = "";
        }
        $status     = $request->query->get('status', default: 10);
        $editor     = $request->query->get('editor');
        $id         = $request->query->get('id');
        $inverter   = $request->query->get('inverter');
        $prio       = $request->query->get('prio');
        $category   = $request->query->get('category');
        $type       = $request->query->get('type');


        $filter['status']['value'] = $status;
        $filter['status']['array'] = self::ticketStati();
        $filter['priority']['value'] = $prio;
        $filter['priority']['array'] = self::ticketPriority();
        $filter['category']['value'] = $category;
        $filter['category']['array'] = self::errorCategorie();
        $filter['type']['value'] = $type;
        $filter['type']['array'] = self::errorType();

        $order['begin'] = 'DESC'; // null, ASC, DESC

        $queryBuilder = $ticketRepo->getWithSearchQueryBuilderNew($anlage, $editor, $id, $prio, $status, $category, $type, $inverter, $order);
        $pagination = $paginator->paginate(
            $queryBuilder,                                    /* query NOT result */
            $page,   /* page number*/
            25                                          /*limit per page*/
        );

        $session->set('page', "$page");

        if ($request->query->get('ajax')) {
            return $this->render('ticket/_inc/_listTickets.html.twig', [
                'pagination' => $pagination,
            ]);
        }

        return $this->render('ticket/list.html.twig',[
            'pagination' => $pagination,
            'anlage'     => $anlage,
            'user'       => $editor,
            'id'         => $id,
            'inverter'   => $inverter,
            'filter'     => $filter,
        ]);

    }

    #[Route(path: '/ticket/search', name: 'app_ticket_search', methods: ['GET', 'POST'])]
    #[Deprecated]
    public function searchTickets(TicketRepository $ticketRepo, PaginatorInterface $paginator, Request $request): Response
    {
        dd('do nort use this Funtion any longer');

        $anlage     = $request->query->get('anlage');
        $status     = $request->query->get('status');
        $editor     = $request->query->get('editor');
        $id         = $request->query->get('id');
        $inverter   = $request->query->get('inverter');
        $prio       = $request->query->get('prio');
        $category   = $request->query->get('category');
        $type       = $request->query->get('type');

        $queryBuilder = $ticketRepo->getWithSearchQueryBuilderNew($anlage, $editor, $id, $prio, $status, $category, $type, $inverter);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            25
        );

        return $this->render('ticket/_inc/_listTickets.html.twig', [
            'pagination' => $pagination,
        ]);
    }


    #[Route(path: '/ticket/split/{id}', name: 'app_ticket_split', methods: ['GET', 'POST'])]
    public function split( $id, TicketDateRepository $ticketDateRepo, TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em): Response
    {
        $page = $request->query->getInt('page', 1);

        $ticketDate = $ticketDateRepo->findOneById($id);

        $ticket = $ticketRepo->findOneById($ticketDate->getTicket());
        $splitTime = date_create($request->query->get('begin-time'));

        if ($splitTime && $ticket) {
            $mainDate = new TicketDate();
            $mainDate->copyTicketDate($ticketDate);
            $mainDate->setBegin($splitTime);
            $ticketDate->setEnd($splitTime);
            $ticket->addDate($mainDate);
            $ticket->setSplitted(true);

            $em->persist($ticket);
            $em->flush();

        }

        $ticketDates = $ticket->getDates()->getValues();
        if (count($ticketDates) == 0) $ticketDates = null;

        $form = $this->createForm(TicketFormType::class, $ticket);

        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm'    => $form,
            'ticket'        => $ticket,
            'edited'        => true,
            'dates'         => $ticketDates,
            'page'          => $page,
        ]);
    }



    #[Route(path: '/ticket/delete/{id}', name: 'app_ticket_delete')]
    public function delete($id, TicketRepository $ticketRepo, TicketDateRepository $ticketDateRepo, Request $request, EntityManagerInterface $em):Response
    {

        $option = $request->query->get('value');
        $page = $request->query->getInt('page', 1);
        $ticketDate = $ticketDateRepo->findOneById($id);
        $ticket = $ticketRepo->findOneById($ticketDate->getTicket());
        if($ticket) {
            switch ($option) {
                case "Previous":
                    $previousDate = $this->findPreviousDate($ticketDate->getBegin()->format('Y-m-d H:i'), $ticket, $ticketDateRepo);
                    if ($previousDate) {
                        $previousDate->setEnd($ticketDate->getEnd());
                        $ticket->removeDate($ticketDate);
                    }
                    break;
                case "Next":
                    $nextDate = $this::findNextDate($ticketDate->getEnd()->format('Y-m-d H:i'), $ticket, $ticketDateRepo);
                    if ($nextDate) {
                        $nextDate->setBegin($ticketDate->getBegin());
                        $ticket->removeDate($ticketDate);
                    }
                    break;
                case "None":
                    $ticket->removeDate($ticketDate);
                    break;
                default:
            }
            $em->persist($ticket);

            $em->flush();
            return new Response(null, 204);
        }
        $ticketDates = $ticket->getDates();
        if($ticketDates->isEmpty()) $ticketDates = null;

        $form = $this->createForm(TicketFormType::class, $ticket);

        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm'    => $form,
            'ticket'        => $ticket,
            'edited'        => true,
            'dates'         => $ticketDates,
            'page'          => $page,
        ]);
    }

    #[Route(path: '/ticket/join', name: 'app_ticket_join', methods:['GET','POST'])]
    public function join(TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em): Response
    {
        $tickets = json_decode(file_get_contents('php://input'));
        $masterTicket = new Ticket();
        if (count($tickets) > 0) {
            $ticket = $ticketRepo->findOneById($tickets[0]);
            $ticketdate = new TicketDate();
            $anlage = $ticket->getAnlage();
            $begin = $ticket->getBegin();
            $end = $ticket->getEnd();
            $ticketdate->copyTicket($ticket);
            $masterTicket->addDate($ticketdate);
            for ($i = 1; $i < count($tickets); $i++) {
                $ticket = $ticketRepo->findOneById($tickets[$i]);
                $ticketdate = new TicketDate();
                if ($ticket->getBegin()->format("Y/m/d H:i") < $begin) {$begin = $ticket->getBegin();}
                if ($ticket->getEnd()->format("Y/m/d H:i") > $end){ $end = $ticket->getEnd();}
                $ticketdate->copyTicket($ticket);
                $masterTicket->addDate($ticketdate);
            }
            $masterTicket->setEnd($end);
            $masterTicket->setBegin( $begin);
            $masterTicket->setAnlage($anlage);
            $masterTicket->setAlertType("Defined in the Sub-Tickets");
            $masterTicket->setAnswer("Defined in the Sub-Tickets");
            $masterTicket->setInverter("Defined in the Sub-Ticket");
            $masterTicket->setSplitted(true);

            //$em->flush();
        }
        return $this->render('/ticket/join.html.twig', [
            'text' => "estamos aqui"
        ]);
    }

    public function findNextDate($stamp, $ticket, $ticketDateRepo): ?TicketDate
    {
        $ticketDate = $ticketDateRepo->findOneByBeginTicket($stamp, $ticket);
        /*
        $found = false;
        while (($found != true) && (strtotime($stamp) < $ticket->getEnd()->getTimestamp())) {
            $ticketDate = $ticketDateRepo->findOneByBeginTicket($stamp, $ticket);
            if ($ticketDate) $found == true;
            else  $stamp = date('Y-m-d H:i', strtotime($stamp) + 900);
        }
        */
        return $ticketDate;
    }
    public function findPreviousDate($stamp, $ticket, $ticketDateRepo): ?TicketDate
    {
        $ticketDate = $ticketDateRepo->findOneByEndTicket($stamp, $ticket);
        /*
        $found = false;
        while (($found != true) && (strtotime($stamp) < $ticket->getBegin()->getTimestamp())) {
            $ticketDate = $ticketDateRepo->findOneByEndTicket($stamp, $ticket);
            if ($ticketDate) $found == true;
            else  $stamp = date('Y-m-d H:i', strtotime($stamp) - 900);
        }
        */
        return $ticketDate;
    }
}