<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\Ticket;
use App\Entity\TicketDate;
use App\Form\Ticket\TicketFormType;
use App\Helper\PVPNameArraysTrait;
use App\Repository\AnlagenRepository;
use App\Repository\TicketDateRepository;
use App\Repository\TicketRepository;
use App\Service\FunctionsService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends BaseController
{
    use PVPNameArraysTrait;

    #[Route(path: '/ticket/create', name: 'app_ticket_create')]
    public function create(EntityManagerInterface $em, Request $request, AnlagenRepository $anlRepo, functionsService $functions): Response
    {
        if ($request->query->get('anlage') !== null) {
            $anlage = $anlRepo->findIdLike((int)$request->query->get('anlage'))[0];
        } else {
            $anlage = null;
        }

        $form = $this->createForm(TicketFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticket = $form->getData();
            $ticket->setEditor($this->getUser()->getUsername());
            $date = new TicketDate();
            $date->copyTicket($ticket);
            $ticket->addDate($date);
            $em->persist($ticket);
            $em->flush();

            return new Response(null, 204);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $anlage = $form->getData()->getAnlage();
        }

        $nameArray = $anlage->getInverterFromAnlage();
        $inverterArray = [];
        foreach ($nameArray as $key => $value){
            $inverterArray[$key]["inv"] = $value;
            $inverterArray[$key]["select"] = "";
        }

        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm'    => $form,
            'ticket'        => false,
            'anlage'        => $anlage,
            'edited'        => false,
            'invArray'      => $inverterArray,
        ]);
    }

    #[Route(path: '/ticket/edit/{id}', name: 'app_ticket_edit')]
    public function edit($id, TicketRepository $ticketRepo, EntityManagerInterface $em, Request $request, functionsService $functions ): Response
    {
        $ticket = $ticketRepo->find($id);
        $ticketDates = $ticket->getDates();
        $anlage = $ticket->getAnlage();
        $nameArray = $anlage->getInverterFromAnlage();
        $selected = $ticket->getInverterArray();
        $indexSelect = 0;
        for($index = 1; $index <= sizeof($nameArray); $index++){
            $value = $nameArray[$index];
            $inverterArray[$index]["inv"] = $value;
            if ($index === (int)$selected[$indexSelect]){
                $inverterArray[$index]["select"] = "checked";
                $indexSelect++;
            }
            else{
                $inverterArray[$index]["select"] = "";
            }
        }
        if ($ticketDates->isEmpty()) {
            $inverterArray = null;
        }
        $form = $this->createForm(TicketFormType::class, $ticket);
        $page = $request->query->getInt('page', 1);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $request->attributes->set('page', $page);
            /** @var Ticket $ticket */
            $ticket = $form->getData();
            $ticketDates = $ticket->getDates();
            $ticket->setEditor($this->getUser()->getUsername());
            if ($ticket->getStatus() === 30 && $ticket->getEnd() === null) {
                $ticket->setEnd(new \DateTime('now'));
            }
            // Adjust, if neccesary, the start ean end Date of the master Ticket, depending on the TicketDates
            // TODO: Check what hapend if the last ticket is not the 'last' ticket, means if the order of the ticketDates are not respected

            if ($ticketDates) {
                if ($ticketDates->first()->getBegin() <= $ticket->getBegin()) {
                    $ticket->setBegin($ticketDates->first()->getBegin());
                } else {
                    $ticketDates->first()->setBegin($ticket->getBegin());
                }
                if ($ticketDates->last()->getEnd() >= $ticket->getEnd()) {
                    $ticket->setEnd($ticketDates->last()->getEnd());
                } else {
                    $ticketDates->last()->setEnd($ticket->getEnd());
                }
                foreach ($ticketDates as $date){
                    $date->setInverter($ticket->getInverter());
                }
            }

            if ($ticket->getStatus() == '10') $ticket->setStatus(30); // If 'New' Ticket change to work in Progress

            $em->persist($ticket);
            $em->flush();

            return new Response(null, 204);
        }

        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm' => $form,
            'ticket' => $ticket,
            'anlage' => $anlage,
            'edited' => true,
            'invArray' => $inverterArray
        ]);
    }

    #[Route(path: '/ticket/list', name: 'app_ticket_list')]
    public function list(TicketRepository $ticketRepo, PaginatorInterface $paginator, Request $request, AnlagenRepository $anlagenRepo, RequestStack $requestStack): Response
    {
        $filter = [];
        $session = $requestStack->getSession();

        $pageSession = $session->get('page');
        $page = $request->query->getInt('page');
        if ($page == 0) {
            if ($pageSession == 0) {
                $page = 1;
            } else {
                $page = $pageSession;
            }
        }

        // Reading data from request
        /** @var Anlage|string $anlage */
        $anlageId = $request->query->get('anlage');
        if ($anlageId != '') {
            $anlage = $anlagenRepo->findOneBy(['anlId' => $anlageId]);
            $anlageName = $anlage->getAnlName();
        } else {
            $anlageName = '';
            $anlage = null;
        }

        $status = $request->query->get('status');
        $editor = $request->query->get('editor');
        $id = $request->query->get('id');
        $inverter = $request->query->get('inverter');
        $prio = $request->query->get('prio');
        $category = $request->query->get('category');
        $type = $request->query->get('type');
        $prooftam = $request->query->get('prooftam', 0);


        $filter['anlagen']['value'] = $anlage;
        $filter['anlagen']['array'] = $anlagenRepo->findAllActiveAndAllowed();
        $filter['status']['value'] = $status;
        $filter['status']['array'] = self::ticketStati();
        $filter['priority']['value'] = $prio;
        $filter['priority']['array'] = self::ticketPriority();
        $filter['category']['value'] = $category;
        $filter['category']['array'] = self::errorCategorie();
        $filter['type']['value'] = $type;
        $filter['type']['array'] = self::errorType();

        $order['begin'] = 'DESC'; // null, ASC, DESC
        $order['updatedAt'] = 'DESC';

        $queryBuilder = $ticketRepo->getWithSearchQueryBuilderNew($anlageName, $editor, $id, $prio, $status, $category, $type, $inverter, $order, $prooftam);
        $pagination = $paginator->paginate($queryBuilder, $page,25 );

        // check if we get no result
        if ($pagination->count() == 0){
            $page = 1;
            $pagination = $paginator->paginate($queryBuilder, $page,25 );
        }
        $session->set('page', "$page");

        if ($request->query->get('ajax') || $request->isXmlHttpRequest()) {
            return $this->render('ticket/_inc/_listTickets.html.twig', [
                'pagination'    => $pagination,
                'anlagen'       => $anlagenRepo->findAllActiveAndAllowed(),
            ]);
        }

        return $this->render('ticket/list.html.twig', [
            'pagination'    => $pagination,
            'anlage'        => $anlage,
            'anlagen'       => $anlagenRepo->findAllActiveAndAllowed(),
            'user'          => $editor,
            'id'            => $id,
            'inverter'      => $inverter,
            'filter'        => $filter,
            'prooftam'      => $prooftam,
        ]);
    }

    #[Route(path: '/ticket/split/{id}', name: 'app_ticket_split', methods: ['GET', 'POST'])]
    public function split($id, TicketDateRepository $ticketDateRepo, TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em, functionsService $functions): Response
    {
        $page = $request->query->getInt('page', 1);

        $ticketDate = $ticketDateRepo->findOneById($id);

        $ticket = $ticketRepo->findOneById($ticketDate->getTicket());
        $splitTime = date_create($request->query->get('begin-time'));
        $anlage = $ticket->getAnlage();
        $nameArray = $functions->getInverterArray($anlage);
        $selected = $ticket->getInverterArray();

        $indexSelect = 0;

        foreach ($nameArray as $key => $value){
            $inverterArray[$key]["inv"] = $value;
            if ($key === (int)$selected[$indexSelect]){
                $inverterArray[$key]["select"] = "checked";
                $indexSelect ++;
            } else {
                $inverterArray[$key]["select"] = "";
            }
        }


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
        if (count($ticketDates) == 0) {
            $ticketDates = null;
        }

        $form = $this->createForm(TicketFormType::class, $ticket);

        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm' => $form,
            'ticket' => $ticket,
            'edited' => true,
            'anlage' => $anlage,
            'dates' => $ticketDates,
            'page' => $page,
            'invArray' => $inverterArray
        ]);
    }

    #[Route(path: '/ticket/delete/{id}', name: 'app_ticket_delete')]
    public function delete($id, TicketRepository $ticketRepo, TicketDateRepository $ticketDateRepo, Request $request, EntityManagerInterface $em, functionsService $functions): Response
    {
        $option = $request->query->get('value');
        $page = $request->query->getInt('page', 1);
        $ticketDate = $ticketDateRepo->findOneById($id);
        $ticket = $ticketRepo->findOneById($ticketDate->getTicket());
        $anlage = $ticket->getAnlage();
        $nameArray = $functions->getInverterArray($anlage);
        $selected = $ticket->getInverterArray();

        $indexSelect = 0;

        foreach ($nameArray as $key => $value){
            $inverterArray[$key]["inv"] = $value;
            if($key === (int)$selected[$indexSelect]){
                $inverterArray[$key]["select"] = "checked";
                $indexSelect ++;
            }
            else{
                $inverterArray[$key]["select"] = "";
            }
        }

        if ($ticket) {
            switch ($option) {
                case 'Previous':
                    $previousDate = $this->findPreviousDate($ticketDate->getBegin()->format('Y-m-d H:i'), $ticket, $ticketDateRepo);
                    if ($previousDate) {
                        $previousDate->setEnd($ticketDate->getEnd());
                        $ticket->removeDate($ticketDate);
                    }
                    break;
                case 'Next':
                    $nextDate = $this::findNextDate($ticketDate->getEnd()->format('Y-m-d H:i'), $ticket, $ticketDateRepo);
                    if ($nextDate) {
                        $nextDate->setBegin($ticketDate->getBegin());
                        $ticket->removeDate($ticketDate);
                    }
                    break;
                case 'None':
                    $ticket->removeDate($ticketDate);
                    break;
            }
            $em->persist($ticket);
            $em->flush();

            return new Response(null, 204);
        }
        $ticketDates = $ticket->getDates();
        if ($ticketDates->isEmpty()) {
            $ticketDates = null;
        }

        $form = $this->createForm(TicketFormType::class, $ticket);

        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm' => $form,
            'ticket' => $ticket,
            'edited' => true,
            'anlage' => $anlage,
            'dates' => $ticketDates,
            'page' => $page,
            'invArray' => $inverterArray
        ]);
    }

    #[Route(path: '/ticket/splitbyinverter', name: 'app_ticket_split_inverter')]
    public function splitByInverter(TicketRepository $ticketRepo, TicketDateRepository $ticketDateRepo, Request $request, EntityManagerInterface $em): Response
    {
        $ticket = $ticketRepo->findOneById($request->query->get('id'));
        $newTicket = new Ticket();
        $newTicket->setInverter($request->query->get('inverterb'));
        $ticket->setInverter($request->query->get('invertera'));
        $newTicket->copyTicket($ticket);
        $newTicket->setInverter($request->query->get('inverterb'));
        $ticket->setInverter($request->query->get('invertera'));

        $ticket->setEditor($this->getUser()->getUsername());
        $newTicket->setEditor($this->getUser()->getUsername());

        if ($ticket->getStatus() == '10') $ticket->setStatus(30);
        if ($newTicket->getStatus() == '10') $newTicket->setStatus(30);

        $em->persist($ticket);
        $em->persist($newTicket);
        $em->flush();
        $ticket->setDescription($ticket->getDescription()." Ticket splited into Ticket: ". $newTicket->getId());
        $em->persist($ticket);

        $em->flush();


        $form = $this->createForm(TicketFormType::class, $ticket);

        $anlage = $ticket->getAnlage();
        $nameArray = $anlage->getInverterFromAnlage();
        $selected = $ticket->getInverterArray();
        $indexSelect = 0;
        for($index = 1; $index <= sizeof($nameArray); $index++){
            $value = $nameArray[$index];
            $inverterArray[$index]["inv"] = $value;
            if ($index === (int)$selected[$indexSelect]){
                $inverterArray[$index]["select"] = "checked";
                $indexSelect++;
            }
            else{
                $inverterArray[$index]["select"] = "";
            }
        }
        if ($ticket->getDates()->isEmpty()) {
            $inverterArray = null;
        }
        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm' => $form,
            'ticket' => $ticket,
            'anlage' => $anlage,
            'edited' => true,
            'invArray' => $inverterArray
        ]);
    }

    #[Route(path: '/ticket/join', name: 'app_ticket_join', methods: ['GET', 'POST'])]
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
            for ($i = 1; $i < count($tickets); ++$i) {
                $ticket = $ticketRepo->findOneById($tickets[$i]);
                $ticketdate = new TicketDate();
                if ($ticket->getBegin()->format('Y/m/d H:i') < $begin) {
                    $begin = $ticket->getBegin();
                }
                if ($ticket->getEnd()->format('Y/m/d H:i') > $end) {
                    $end = $ticket->getEnd();
                }
                $ticketdate->copyTicket($ticket);
                $masterTicket->addDate($ticketdate);
            }
            $masterTicket->setEnd($end);
            $masterTicket->setBegin($begin);
            $masterTicket->setAnlage($anlage);
            $masterTicket->setAlertType('Defined in the Sub-Tickets');
            $masterTicket->setAnswer('Defined in the Sub-Tickets');
            $masterTicket->setInverter('Defined in the Sub-Ticket');
            $masterTicket->setSplitted(true);

            // $em->flush();
        }

        return $this->render('/ticket/join.html.twig', [
            'text' => 'estamos aqui',
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
