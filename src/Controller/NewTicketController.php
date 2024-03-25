<?php

namespace App\Controller;

use _PHPStan_adbc35a1c\Nette\Utils\DateTime;
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
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewTicketController extends BaseController
{

    public function __construct(
        private readonly TranslatorInterface $translator)
    {
    }

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

            //$ticket->getDates()->first()->setBegin($ticket->getBegin());
            //$ticket->getDates()->last()->setEnd($ticket->getEnd());
            $ticket->setEditor($this->getUser()->getUserIdentifier());
            $dates = $ticket->getDates();
            foreach ($dates as $date) {
                $date->copyTicket($ticket);
               /*if ($date->getAlertType() == 20) {
                    $date->setKpiPaDep1(10);
                    $date->setKpiPaDep2(10);
                    $date->setKpiPaDep3(10);
                }
               */
                //if ($ticket->getAlertType() == 20) $ticket->getDates()[0]->setDataGapEvaluation(10);
            }
            $em->persist($ticket);

            $em->flush();

            return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);

        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $anlage = $form->getData()->getAnlage();
        }

        $nameArray = $anlage->getInverterFromAnlage();
        $inverterArray = [];
        $namesSensors = $anlage->getSensors();
        $sensorArray = [];
        // I loop over the array with the real names and the array of selected inverters
        // of the inverter to create a 2-dimension array with the real name and the inverters that are selected
        //In this case there will  be none selected
        foreach ($nameArray as $key => $value){
            $inverterArray[$key]["inv"] = $value;
            $inverterArray[$key]["select"] = "";
        }
        foreach ($namesSensors as $key => $sensor){
            $sensorArray[$key]['name'] = $sensor->getName();
            $sensorArray[$key]['nameS'] = $sensor->getNameShort();
            $sensorArray[$key]['checked'] = "";
        }

        return $this->render('ticket/_inc/_edit.html.twig', [
            'ticketForm'    => $form,
            'ticket'        => $ticket,
            'anlage'        => $anlage,
            'edited'        => false,
            'invArray'      => $inverterArray,
            'sensorArray'   => $sensorArray,
            'performanceTicket' => false
        ]);
    }

    #[Route(path: '/ticket/edit/{id}', name: 'app_ticket_edit')]
    public function edit($id, TicketRepository $ticketRepo, EntityManagerInterface $em, Request $request, functionsService $functions ): Response
    {
        $ticket = $ticketRepo->find($id);
        $sensorArray = [];
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
                $ticket->setWhoHided($this->getUser()->getUserIdentifier());
                $ticket->setWhenHidded(date("Y-m-d H:i:s"));
            }
            else{
                $ticket->setWhoHided("");
                $ticket->setWhenHidded("");
            }
            $ticketDates = $ticket->getDates();
            $ticket->setEditor($this->getUser()->getUserIdentifier());

            if ($ticket->getStatus() === 30 && $ticket->getEnd() === null) {
                $ticket->setEnd(new \DateTime('now'));
            }
            // Adjust, if neccesary, the start and end Date of the master Ticket, depending on the TicketDates

            if (count($ticketDates) > 0) { // cambiar aqui para que si el primer y ultimo date estan fuera del ticket se expande el ticket
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
            else{
                $date = new ticketDate();
                $date->copyTicket($ticket);
                $ticket->addDate($date);
                $date->setTicket($ticket);
                $ticket->setNeedsProof(true);
                $ticket->setNeedsProofEPC(true);
                $ticket->setNeedsProofTAM(true);
                $ticket->setDescription($ticket->getDescription(). "<br> The sub Tickets were lost because of an unknown error, a new sub Ticket has been created");
            }

            $namesSensors = $anlage->getSensors();

            $sensorString = $ticketDates->first()->getSensors();
            foreach ($namesSensors as $key => $sensor){
                $sensorArray[$key]['name'] = $sensor->getName();
                $sensorArray[$key]['nameS'] = $sensor->getNameShort();
                if ((str_contains($sensorString, $sensor->getNameShort()) !== false)) $sensorArray[$key]['checked'] = "checked";
                else  $sensorArray[$key]['checked'] = "";
            }
            if ($ticket->getStatus() == '10') $ticket->setStatus(30); // If 'New' Ticket change to work in Progress
            $ticket->setUpdatedAt(new DateTime('now'));
            $em->persist($ticket);
            $em->flush();

            return new Response(null, Response::HTTP_NO_CONTENT);
        }
        if ($ticket->getAlertType() >=70 && $ticket->getAlertType() < 80) $performanceTicket =  true;
        else $performanceTicket = false;

        if ($ticket->getDates()->count() === 0){
            $date = new ticketDate();
            $date->copyTicket($ticket);
            $date->setAnlage($anlage);
            $ticket->addDate($date);
            $ticket->setNeedsProof(true);
            $ticket->setNeedsProofEPC(true);
            $ticket->setNeedsProofTAM(true);
            $ticket->setDescription($ticket->getDescription(). "<br> The sub Tickets have been automatically generated");
            $form = $this->createForm(TicketFormType::class, $ticket);
            $em->persist($ticket);
            $em->persist($date);
            $em->flush();
        }
        $namesSensors = $anlage->getSensors();

        $sensorString = $ticketDates->first()->getSensors();
        foreach ($namesSensors as $key => $sensor){
            $sensorArray[$key]['name'] = $sensor->getName();
            $sensorArray[$key]['nameS'] = $sensor->getNameShort();
            if ((str_contains($sensorString, $sensor->getNameShort()) !== false)) $sensorArray[$key]['checked'] = "checked";
            else  $sensorArray[$key]['checked'] = "";
        }

        return $this->render('ticket/_inc/_edit.html.twig', [
            'ticketForm' => $form,
            'ticket' => $ticket,
            'anlage' => $anlage,
            'edited' => true,
            'sensorArray'   => $sensorArray,
            'invArray' => $inverterArray,
            'performanceTicket' => $performanceTicket
        ]);
    }

    #[Route(path: '/ticket/list/{anlageId}', name: 'app_ticket_list_anlId')]
    public function list(TicketRepository $ticketRepo, int $anlageId,AnlagenRepository $anlagenRepository): Response
    {

       $anlage_tickets=$ticketRepo->findByAnlageId($anlageId);

        return $this->render('newTicket/list.html.twig', [
            'tickets'    => $anlage_tickets,
            'anlage'       => $anlagenRepository->findOneBy(['anlId'=>$anlageId])

        ]);
    }

    #[Route(path: '/ticket/split/{id}', name: 'app_ticket_split', methods: ['GET', 'POST'])]
    public function split($id, TicketDateRepository $ticketDateRepo, TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em): Response
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
            $ticket->setUpdatedAt(new DateTime('now'));
            $em->persist($ticket);
            $em->flush();
        }

        $ticketDates = $ticket->getDates()->getValues();
        if (count($ticketDates) == 0) {
            $ticketDates = null;
        }
        $form = $this->createForm(TicketFormType::class, $ticket);
        $namesSensors = $anlage->getSensors();

        $sensorString = $ticketDates[0]->getSensors();
        foreach ($namesSensors as $key => $sensor){
            $sensorArray[$key]['name'] = $sensor->getName();
            $sensorArray[$key]['nameS'] = $sensor->getNameShort();
            if ((str_contains($sensorString, $sensor->getNameShort()) !== false)) $sensorArray[$key]['checked'] = "checked";
            else  $sensorArray[$key]['checked'] = "";
        }

        return $this->render('ticket/_inc/_edit.html.twig', [
            'ticketForm' => $form,
            'ticket' => $ticket,
            'edited' => true,
            'anlage' => $anlage,
            'dates' => $ticketDates,
            'page' => $page,
            'invArray' => $inverterArray,
            'sensorArray'   => $sensorArray,
            'performanceTicket' => false
        ]);
    }
    #[Route(path: '/ticket/deleteTicket/{id}', name: 'app_ticket_deleteticket')]
    public function deleteTicket($id, TicketRepository $ticketRepo,  PaginatorInterface $paginator, Request $request, AnlagenRepository $anlagenRepo, EntityManagerInterface $em, RequestStack $requestStack): Response
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
        $proofg4n = $request->query->get('proofg4n', 0);
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
        $filter['category']['array'] = self::listAllErrorCategorie($this->isGranted('ROLE_G4N'));
        $filter['type']['value'] = $type;
        $filter['type']['array'] = self::errorType();
        $filter['kpistatus']['value'] = $kpistatus;
        $filter['kpistatus']['array'] = self::kpiStatus();

        $queryBuilder = $ticketRepo->getWithSearchQueryBuilderNew($anlage, $editor, $id, $prio, $status, $category, $type, $inverter, $prooftam, $proofepc, $proofam, $proofg4n, $sort, $direction, $ignoredBool, $TicketName, $kpistatus, $begin, $end);


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
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
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



            return new Response(null, Response::HTTP_NO_CONTENT);
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

        return $this->render('ticket/_inc/_edit.html.twig', [
            'ticketForm' => $form,
            'ticket' => $ticket,
            'edited' => true,
            'anlage' => $anlage,
            'dates' => $ticketDates,
            'page' => $page,
            'invArray' => $inverterArray
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
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

        $ticket->setEditor($this->getUser()->getUserIdentifier());
        $newTicket->setEditor($this->getUser()->getUserIdentifier());

        $newTicket->setStatus(10);
        $em->persist($ticket);
        $em->persist($newTicket);
        $em->flush();
        $ticket->setDescription($ticket->getDescription()." Ticket splited into Ticket: ". $newTicket->getId());
        $ticket->setUpdatedAt(new DateTime('now'));
        $em->persist($ticket);
        $em->flush();

        $form = $this->createForm(TicketFormType::class, $ticket);

        $anlage = $ticket->getAnlage();
        $nameArray = $anlage->getInverterFromAnlage();
        $selected = $ticket->getInverterArray();
        $indexSelect = 0;
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
        if ($ticket->getDates()->isEmpty()) {
            $inverterArray = null;
        }
        $namesSensors = $anlage->getSensors();

        $ticketDates = $ticket->getDates();
        $sensorString = $ticketDates->first()->getSensors();
        foreach ($namesSensors as $key => $sensor){
            $sensorArray[$key]['name'] = $sensor->getName();
            $sensorArray[$key]['nameS'] = $sensor->getNameShort();
            if ((str_contains($sensorString, $sensor->getNameShort()) !== false)) {
                $sensorArray[$key]['checked'] = "checked";
            } else {
                $sensorArray[$key]['checked'] = "";
            }
        }
        return $this->render('ticket/_inc/_edit.html.twig', [
            'ticketForm' => $form,
            'ticket' => $ticket,
            'anlage' => $anlage,
            'edited' => true,
            'invArray' => $inverterArray,
            'performanceTicket' => false,
            'sensorArray'   => $sensorArray,
        ]);
    }

    #[Route(path: '/ticket/join', name: 'app_ticket_join', methods: ['GET', 'POST'])]
    public function join(TicketRepository $ticketRepo, EntityManagerInterface $em): Response
    {
        $tickets = json_decode(file_get_contents('php://input'), null, 512, JSON_THROW_ON_ERROR);
        $masterTicket = new Ticket();
        if ((is_countable($tickets) ? count($tickets) : 0) > 0) {
            $ticket = $ticketRepo->findOneById($tickets[0]);
            $ticketdate = new TicketDate();
            $anlage = $ticket->getAnlage();
            $begin = $ticket->getBegin();
            $end = $ticket->getEnd();
            $ticketdate->copyTicket($ticket);
            $masterTicket->addDate($ticketdate);
            for ($i = 1; $i < (is_countable($tickets) ? count($tickets) : 0); ++$i) {
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
        while (($found !== true) && (strtotime((string) $stamp) < $ticket->getEnd()->getTimestamp())) {
            $ticketDate = $ticketDateRepo->findOneByBeginTicket($stamp, $ticket);
            if ($ticketDate) $found = true;
            else  $stamp = date('Y-m-d H:i', strtotime((string) $stamp) + 900);
        }

        return $ticketDate;
    }

    public function findPreviousDate($stamp, $ticket, $ticketDateRepo): ?TicketDate
    {
        $ticketDate = null; //$ticketDateRepo->findOneByEndTicket($stamp, $ticket); we cannot do this because if there is a gap between the intervals we will not be able to find the next interval to link with
        $found = false;
        while (($found !== true) && (strtotime((string) $stamp) > $ticket->getBegin()->getTimestamp())) {
            $ticketDate = $ticketDateRepo->findOneByEndTicket($stamp, $ticket);
            if ($ticketDate) $found = true;
            else  $stamp = date('Y-m-d H:i', strtotime((string) $stamp) - 900);
        }

        return $ticketDate;
    }


}