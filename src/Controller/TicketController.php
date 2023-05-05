<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\Eigner;
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
            $anlage = $anlRepo->find($request->query->get('anlage'));
        } else {
            $anlage = null;
        }

        if ($anlage) {
            $ticket = new Ticket();
            $ticket->setAnlage($anlage);
            $ticket
                ->setBegin(date_create(date('Y-m-d H:i:s', time() - time() % 900)))
                ->setEnd(date_create(date('Y-m-d H:i:s', (time() - time() % 900) + 900)))
                ->setAlertType(0);
            $ticketDate = new TicketDate();
            $ticketDate
                ->setBegin($ticket->getBegin())
                ->setEnd($ticket->getEnd())
                ->setAnlage($anlage);
            $ticket->getDates()->add($ticketDate);
            $form = $this->createForm(TicketFormType::class, $ticket);
        } else {
            $form = $this->createForm(TicketFormType::class);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Ticket $ticket */
            $ticket = $form->getData();

            $ticket->getDates()->first()->setBegin($ticket->getBegin());
            $ticket->getDates()->last()->setEnd($ticket->getEnd());
            $ticket->setEditor($this->getUser()->getUsername());
            $dates = $ticket->getDates();

            foreach ($dates as $date) {
                $date->copyTicket($ticket);
                if ($date->getAlertType() == 20) {
                    $date->setKpiPaDep1(10);
                    $date->setKpiPaDep2(10);
                    $date->setKpiPaDep3(10);
                }
                if ($ticket->getAlertType() == 20) $ticket->getDates()[0]->setDataGapEvaluation(10);

            }
            $em->persist($ticket);

            $em->flush();

            return new Response(null, 204);

        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $anlage = $form->getData()->getAnlage();
        }

        $nameArray = $anlage->getInverterFromAnlage();
        $inverterArray = [];
        // I loop over the array with the real names and the array of selected inverters
        // of the inverter to create a 2-dimension array with the real name and the inverters that are selected
        //In this case there will  be none selected
        foreach ($nameArray as $key => $value){
            $inverterArray[$key]["inv"] = $value;
            $inverterArray[$key]["select"] = "";
        }
        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm'    => $form,
            'ticket'        => $ticket,
            'anlage'        => $anlage,
            'edited'        => false,
            'invArray'      => $inverterArray,
            'performanceTicket' => false
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
        // I loop over the array with the real names and the array of selected inverters
        // of the inverter to create a 2-dimension array with the real name and the inverters that are selected
        if ($selected[0] == "*"){
            for ($index = 1; $index <= sizeof($nameArray); $index++){
                $value = $nameArray[$index];
                $inverterArray[$index]["inv"] = $value;
                $inverterArray[$index]["select"] = "checked";
            }
        }
        else {
            for ($index = 1; $index <= sizeof($nameArray); $index++){
                $value = $nameArray[$index];
                $inverterArray[$index]["inv"] = $value;
                if ($index === (int)$selected[$indexSelect]){
                    $inverterArray[$index]["select"] = "checked";
                    $indexSelect++;
                } else {
                    $inverterArray[$index]["select"] = "";
                }
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
            if($form->getData()->isIgnoreTicket()){
                $ticket->setWhoHided($this->getUser()->getUsername());
                $ticket->setWhenHidded(date("Y-m-d H:i:s"));
            }
            else{
                $ticket->setWhoHided("");
                $ticket->setWhenHidded("");
            }
            $ticketDates = $ticket->getDates();
            $ticket->setEditor($this->getUser()->getUsername());

            if ($ticket->getStatus() === 30 && $ticket->getEnd() === null) {
                $ticket->setEnd(new \DateTime('now'));
            }
            // Adjust, if neccesary, the start and end Date of the master Ticket, depending on the TicketDates

            if ($ticketDates) { // cambiar aqui para que si el primer y ultimo date estan fuera del ticket se expande el ticket
                $found = false;
                while(!$found){

                    $firstTicketDate = $ticketDates->first();

                    if ($firstTicketDate->getEnd() < $ticket->getBegin()) $ticket->removeDate($firstTicketDate);
                    elseif ($firstTicketDate->getEnd() == $ticket->getBegin()){
                        $ticket->removeDate($firstTicketDate);
                        $found = true;
                    }
                    else {
                        $firstTicketDate->setBegin($ticket->getBegin());
                        $found = true;
                        $em->persist($firstTicketDate);
                    }
                }

                $found = false;
                while (!$found){
                    $lastTicketDate = $ticket->getDates()->last();
                    if ($lastTicketDate->getBegin() > $ticket->getEnd()) {
                        $ticket->removeDate($lastTicketDate);
                    } elseif ($lastTicketDate->getBegin() == $ticket->getEnd()){
                        $ticket->removeDate($lastTicketDate);
                        $found = true;
                    } else {
                        $lastTicketDate->setEnd($ticket->getEnd());
                        $found = true;
                        $em->persist($lastTicketDate);
                    }
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
        if ($ticket->getAlertType() >=70 && $ticket->getAlertType() < 80) $performanceTicket =  true;
        else $performanceTicket = false;
        return $this->renderForm('ticket/_inc/_edit.html.twig', [
            'ticketForm' => $form,
            'ticket' => $ticket,
            'anlage' => $anlage,
            'edited' => true,
            'invArray' => $inverterArray,
            'performanceTicket' => $performanceTicket
        ]);
    }

    #[Route(path: '/ticket/list', name: 'app_ticket_list')]
    public function list(TicketRepository $ticketRepo, PaginatorInterface $paginator, Request $request, AnlagenRepository $anlagenRepo, RequestStack $requestStack): Response
    {
        $filter = [];
        $session = $requestStack->getSession();
        $pageSession = $session->get('page');
        $page = $request->query->getInt('page');


        if ($request->query->get('filtering') == 'filtered')
        {
            //$page = 1;
            $request->query->set('filtering', 'non-filtered');

        } // we do this to reset the page if the user uses the filter

        if ($page == 0) {
            if ($pageSession == 0) {
                $page = 1;
            } else {
                $page = $pageSession;
            }
        }
        $anlageId = $request->query->get('anlage');
        if ($anlageId != '') {
            $anlage = $anlagenRepo->findOneBy(['anlId' => $anlageId]);
        } else {
            $anlage = null;
        }
        $status = $request->query->get('status');
        $editor = $request->query->get('editor');
        $id = $request->query->get('id');
        $inverter = $request->query->get('inverter');
        $prio = $request->query->get('prio');
        $category = $request->query->get('category');
        $type = $request->query->get('type');
        $sort = $request->query->get('sort', "");
        $direction = $request->query->get('direction', "");
        $prooftam = $request->query->get('prooftam', 0);
        $proofepc = $request->query->get('proofepc', 0);
        $proofam = $request->query->get('proofam', 0);
        $ignored = $request->query->get('ignored', 0);
        $TicketName = $request->query->get('TicketName', "");
        $kpistatus = $request->query->get('kpistatus', 0);
        $begin = $request->query->get('begin', "");
        $end = $request->query->get('end', "");
        if ($ignored == 0) $ignoredBool = false;
        else $ignoredBool = true;
        if ($sort === "") $sort = "ticket.begin";
        if ($direction === "") $direction ="desc";
        $filter['anlagen']['value'] = $anlage;
        $filter['anlagen']['array'] = $anlagenRepo->findAllActiveAndAllowed();
        $filter['TicketName']['value'] = $TicketName;
        $filter['status']['value'] = $status;
        $filter['status']['array'] = self::ticketStati();
        $filter['priority']['value'] = $prio;
        $filter['priority']['array'] = self::ticketPriority();
        $filter['category']['value'] = $category;
        $filter['category']['array'] = self::listAllErrorCategorie();
        $filter['type']['value'] = $type;
        $filter['type']['array'] = self::errorType();
        $filter['kpistatus']['value'] = $kpistatus;
        $filter['kpistatus']['array'] = self::kpiStatus();

        $queryBuilder = $ticketRepo->getWithSearchQueryBuilderNew($anlage, $editor, $id, $prio, $status, $category, $type, $inverter, $prooftam, $proofepc, $proofam, $sort, $direction, $ignoredBool, $TicketName, $kpistatus, $begin, $end);


        $pagination = $paginator->paginate($queryBuilder, $page,25 );
        $pagination->setParam('sort', $sort);
        $pagination->setParam('direction', $direction);
        // check if we get no result
        if ($pagination->count() == 0){
            $page = 1;
            $pagination = $paginator->paginate($queryBuilder, $page,25 );
            $pagination->setParam('sort', $sort);
            $pagination->setParam('direction', $direction);
        }
        $session->set('page', "$page");

        if ($request->query->get('ajax') || $request->isXmlHttpRequest()) {
            return $this->render('ticket/_inc/_listTickets.html.twig', [
                'pagination'    => $pagination,
                'anlagen'       => $anlagenRepo->findAllActiveAndAllowed(),
            ]);

        }
        //here we will configure the array of reason suggestions
        if ($anlage != null){

        }
        else $reasonArray = [];
        return $this->render('ticket/list.html.twig', [
            'pagination'    => $pagination,
            'anlage'        => $anlage,
            'anlagen'       => $anlagenRepo->findAllActiveAndAllowed(),
            'user'          => $editor,
            'id'            => $id,
            'TicketName'    => $TicketName,
            'inverter'      => $inverter,
            'filter'        => $filter,
            'prooftam'      => $prooftam,
            'sort'          => $sort,
            'direction'     => $direction,
            'begin'         => $begin,
            'end'           => $end,
            'reasonArray'   => $reasonArray,
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
        $nameArray = $anlage->getInverterFromAnlage();
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


        if ($splitTime) {
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
    #[Route(path: '/ticket/deleteTicket/{id}', name: 'app_ticket_deleteticket')]
    public function deleteTicket($id, TicketRepository $ticketRepo,  PaginatorInterface $paginator, Request $request, AnlagenRepository $anlagenRepo, EntityManagerInterface $em, functionsService $functions,  RequestStack $requestStack): Response
    {
        $filter = [];
        $session = $requestStack->getSession();
        $pageSession = $session->get('page');
        $page = $request->query->getInt('page');

        $ticket = $ticketRepo->findOneById($id);
        if ($ticket != null){
            $em->remove($ticket);
            $em->flush();
        }


        if ($request->query->get('filtering') == 'filtered')
        {
            //$page = 1;
            $request->query->set('filtering', 'non-filtered');
        } // we do this to reset the page if the user uses the filter

        if ($page == 0) {
            if ($pageSession == 0) {
                $page = 1;
            } else {
                $page = $pageSession;
            }
        }
        $anlageId = $request->query->get('anlage');
        if ($anlageId != '') {
            $anlage = $anlagenRepo->findOneBy(['anlId' => $anlageId]);
        } else {
            $anlage = null;
        }

        $status = $request->query->get('status');
        $editor = $request->query->get('editor');
        $id = $request->query->get('id');
        $inverter = $request->query->get('inverter');
        $prio = $request->query->get('prio');
        $category = $request->query->get('category');
        $type = $request->query->get('type');
        $sort = $request->query->get('sort', "");
        $direction = $request->query->get('direction', "");
        $prooftam = $request->query->get('prooftam', 0);
        $proofepc = $request->query->get('proofepc', 0);
        $proofam = $request->query->get('proofam', 0);
        $ignored = $request->query->get('ignored', 0);
        $TicketName = $request->query->get('TicketName', "");
        $kpistatus = $request->query->get('kpistatus', 0);
        $begin = $request->query->get('begin', "");
        $end = $request->query->get('end', "");
        if ($ignored == 0) $ignoredBool = false;
        else $ignoredBool = true;
        if ($sort === "") $sort = "ticket.begin";
        if ($direction === "") $direction ="desc";
        $filter['anlagen']['value'] = $anlage;
        $filter['anlagen']['array'] = $anlagenRepo->findAllActiveAndAllowed();
        $filter['TicketName']['value'] = $TicketName;
        $filter['status']['value'] = $status;
        $filter['status']['array'] = self::ticketStati();
        $filter['priority']['value'] = $prio;
        $filter['priority']['array'] = self::ticketPriority();
        $filter['category']['value'] = $category;
        $filter['category']['array'] = self::listAllErrorCategorie();
        $filter['type']['value'] = $type;
        $filter['type']['array'] = self::errorType();
        $filter['kpistatus']['value'] = $kpistatus;
        $filter['kpistatus']['array'] = self::kpiStatus();

        $queryBuilder = $ticketRepo->getWithSearchQueryBuilderNew($anlage, $editor, $id, $prio, $status, $category, $type, $inverter, $prooftam, $proofepc, $proofam, $sort, $direction, $ignoredBool, $TicketName, $kpistatus, $begin, $end);


        $pagination = $paginator->paginate($queryBuilder, $page,25 );
        $pagination->setParam('sort', $sort);
        $pagination->setParam('direction', $direction);
        // check if we get no result
        if ($pagination->count() == 0){
            $page = 1;
            $pagination = $paginator->paginate($queryBuilder, $page,25 );
            $pagination->setParam('sort', $sort);
            $pagination->setParam('direction', $direction);
        }
        $session->set('page', "$page");


        if ($anlage != null){

        }
        else $reasonArray = [];
        return $this->render('ticket/list.html.twig', [
            'pagination'    => $pagination,
            'anlage'        => $anlage,
            'anlagen'       => $anlagenRepo->findAllActiveAndAllowed(),
            'user'          => $editor,
            'id'            => $id,
            'TicketName'    => $TicketName,
            'inverter'      => $inverter,
            'filter'        => $filter,
            'prooftam'      => $prooftam,
            'sort'          => $sort,
            'direction'     => $direction,
            'begin'         => $begin,
            'end'           => $end,
            'reasonArray'   => $reasonArray,
        ]);
    }

    #[Route(path: '/ticket/delete/{id}', name: 'app_ticket_delete')]
    public function delete($id, TicketRepository $ticketRepo, TicketDateRepository $ticketDateRepo, Request $request, EntityManagerInterface $em, functionsService $functions): Response
    {

        $option = $request->query->get('value');
        $page = $request->query->getInt('page', 1);
        $ticketDate = $ticketDateRepo->findOneById($id);
        $ticket = $ticketRepo->findOneById($ticketDate->getTicket());


        if ($ticket) {
            switch ($option) {
                case 'Previous':
                    $previousDate = $this->findPreviousDate($ticketDate->getBegin()->format('Y-m-d H:i'), $ticket, $ticketDateRepo);
                    if ($previousDate) {
                        $previousDate->setEnd($ticketDate->getEnd());
                        $ticket->removeDate($ticketDate);
                        $em->persist($previousDate);
                    }
                    break;
                case 'Next':
                    $nextDate = $this::findNextDate($ticketDate->getEnd()->format('Y-m-d H:i'), $ticket, $ticketDateRepo);
                    if ($nextDate) {
                        $nextDate->setBegin($ticketDate->getBegin());
                        $ticket->removeDate($ticketDate);
                        $em->persist($nextDate);
                    }
                    break;
                case 'None':
                    $ticket->removeDate($ticketDate);
                    break;
            }
            $ticketDates = $ticket->getDates();
            if ($ticketDates->isEmpty()) {
                $ticketDates = null;
            }
            else{

                if ($ticketDates->last()->getEnd() < $ticket->getEnd()) {
                    $ticket->setEnd($ticketDates->last()->getEnd());

                }
                if ($ticketDates->first()->getBegin() > $ticket->getBegin()) {
                    $ticket->setBegin($ticketDates->first()->getBegin());

                }
            }
            $em->persist($ticket);
            $em->flush();



            return new Response(null, 204);
        }
        $anlage = $ticket->getAnlage();

        $nameArray = $anlage->getInverterFromAnlage();
        $selected = $ticket->getInverterArray();
        $indexSelect = 0;
        // I loop over the array with the real names and the array of selected inverters
        // of the inverter to create a 2-dimension array with the real name and the inverters that are selected
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
        $ticketDates = $ticket->getDates();
        if ($ticketDates->isEmpty()) {
            $ticketDates = null;
        }
        else{

            if ($ticketDates->last()->getEnd() < $ticket->getEnd()) {
                $ticket->setEnd($ticketDates->last()->getEnd());
            }
            if ($ticketDates->first()->getBegin() < $ticket->getBegin()) {
                $ticket->setBegin($ticketDates->first()->getBegin());
            }
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

        $newTicket->setStatus(10);
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
        $ticketDate = null; // = $ticketDateRepo->findOneByBeginTicket($stamp, $ticket);

        $found = false;
        while (($found !== true) && (strtotime($stamp) < $ticket->getEnd()->getTimestamp())) {
            $ticketDate = $ticketDateRepo->findOneByBeginTicket($stamp, $ticket);
            if ($ticketDate) $found = true;
            else  $stamp = date('Y-m-d H:i', strtotime($stamp) + 900);
        }

        return $ticketDate;
    }

    public function findPreviousDate($stamp, $ticket, $ticketDateRepo): ?TicketDate
    {
        $ticketDate = null; //$ticketDateRepo->findOneByEndTicket($stamp, $ticket); we cannot do this because if there is a gap between the intervals we will not be able to find the next interval to link with
        $found = false;
        while (($found !== true) && (strtotime($stamp) > $ticket->getBegin()->getTimestamp())) {
            $ticketDate = $ticketDateRepo->findOneByEndTicket($stamp, $ticket);
            if ($ticketDate) $found = true;
            else  $stamp = date('Y-m-d H:i', strtotime($stamp) - 900);
        }

        return $ticketDate;
    }


}
