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
        $searchstatus = $session->get('search');
        $editor = $session->get('editor');
        $anlage = $session->get('anlage');
        $id = $session->get('id');
        $prio = $session->get('prio');
        $Route = $this->generateUrl('app_ticket_list',[], UrlGeneratorInterface::ABS_PATH);
        $Route = $Route."?anlage=".$anlage."&user=".$editor."&id=".$id."&prio=".$prio."&searchstatus=".$searchstatus."&search=yes";
        $form = $this->createForm(TicketFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $ticket = $form->getData();
            $ticket->setEditor($this->getUser()->getUsername());
            $em->persist($ticket);
            $em->flush();
            $this->addFlash('success', 'Ticket saved!');
            if ($form->get('saveclose')->isClicked()) {
                return $this->redirect($Route);
            }
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirect($Route);
        }
        return $this->render('ticket/create.html.twig',[
            'ticketForm'=>$form->createView(),
            'edited' => false
        ]);
    }

    #[Route(path: '/ticket/edit/{id}', name: 'app_ticket_edit')]
    public function edit($id, TicketRepository $ticketRepo, EntityManagerInterface $em, Request $request) : Response
    {
        $ticket = $ticketRepo->find($id);
        $ticketDates = $ticket->getDates();
        if($ticketDates->isEmpty()) $ticketDates = null;
        //reading data from session
        $form = $this->createForm(TicketFormType::class, $ticket);
        $page           = $request->query->getInt('page', 1);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $request->attributes->set('page', $page);
            $ticket = $form->getData();
            $ticket->setEditor($this->getUser()->getUsername());
            if ($ticket->getStatus() === 30 && $ticket->getend() === null) $ticket->setEnd(new \DateTime("now"));

            $em->persist($ticket);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);
            }
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
    public function split( $id, TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em): Response
    {
        dd($id);
        $page = $request->query->getInt('page', 1);
        $ticket = $ticketRepo->findOneById($id);
        $beginTime = date_create($request->query->get('begin-time'));
        $endTime = date_create($request->query->get('end-time'));

        if ($ticket !== null && $beginTime && $endTime) {
            if($ticket->getDates()->count() == 1) $ticket->removeAllDates();
            if ($beginTime > $ticket->getBegin()) {
                $firstDate = new TicketDate();
                $firstDate->copyTicket($ticket);
                $firstDate->setBegin($ticket->getBegin());
                $firstDate->setEnd($beginTime);
                $ticket->addDate($firstDate);
            }

            $mainDate = new TicketDate();
            $mainDate->copyTicket($ticket);
            $mainDate->setBegin($beginTime);
            $mainDate->setEnd($endTime);
            $ticket->addDate($mainDate);

            if ($endTime < $ticket->getEnd()) {
                $secondDate = new TicketDate();
                $secondDate->copyTicket($ticket);
                $secondDate->setBegin($endTime);
                $secondDate->setEnd($ticket->getEnd());
                $ticket->addDate($secondDate);
            }
            $ticket->setSplitted(true);

            $em->persist($ticket);
            $em->flush();
/*
            if ($request->isXmlHttpRequest()) {
                return new Response(null, 204);

            }
*/
        }

        $ticketDates = $ticket->getDates();
        if ($ticketDates->isEmpty()) $ticketDates = null;

        $form = $this->createForm(TicketFormType::class, $ticket);

        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm'    => $form,
            'ticket'        => $ticket,
            'edited'        => true,
            'dates'         => $ticketDates,
            'page'          => $page,
        ]);
    }


    #[Route(path: '/ticket/split/edit/{id}', name: 'app_ticket_split_edit')]
    public function splitEdit( $id, TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em) : Response
    {
        $ticket = $ticketRepo->findOneById($id);
        $dates = $ticket->getDates()->getValues();
        for ($i = 0; $i < $ticket->getDates()->count(); $i++){
            $date = $dates[$i];
            $em->remove($date);
        }
        $ticket->setSplitted(false);
        $ticket->removeAllDates();
        $em->flush();
        $beginTime = $request->query->get('begin-time');
        $endTime = $request->query->get('end-time');
        if ($ticket != null && $beginTime && $endTime) {
            if ($beginTime > $ticket->getBegin()){
                $firstDate = new TicketDate();
                $ticket->addDate($firstDate);
                //$em->persist($firstDate);
            }
            $mainDate = new TicketDate();
            $mainDate->copyTicket();
            $ticket->addDate($mainDate);

            //$em->persist($mainDate);
            if ($endTime < $ticket->getEnd()){
                $secondDate = new TicketDate();
                $secondDate->copyTicket($ticket);
                $ticket->addDate($secondDate);
                //$em->persist($secondDate);
            }
            $ticket->setSplitted(true);
            $em->persist($ticket);
            $em->flush();
            $Route = $this->generateUrl('app_ticket_edit', ['id' => $id], UrlGeneratorInterface::ABS_PATH);
            return $this->redirect($Route);
        }
        $ticketDates = $ticket->getDates();
        if($ticketDates->isEmpty()) $ticketDates = null;


        $form = $this->createForm(TicketFormType::class, $ticket);
        return $this->render('ticket/edit.html.twig', [
            'ticketForm' => $form->createView(),
            'ticket' => $ticket,
            'edited' => true,
            'dates' => $ticketDates
        ]);

    }

    #[Route(path: '/ticket/join', name: 'app_ticket_join', methods:['GET','POST'])]
    public function join(TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em): Response
    {
        dump(json_decode(file_get_contents('php://input')));
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
            dump($masterTicket);
        }
        return $this->render('/ticket/join.html.twig', [
            'text' => "estamos aqui"
        ]);
    }

}