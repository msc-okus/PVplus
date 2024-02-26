<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\NotificationInfo;
use App\Entity\Ticket;
use App\Entity\ticketDate;
use App\Form\Notification\NotificationConfirmFormType;
use App\Form\Notification\NotificationEditFormType;
use App\Form\Ticket\TicketFormType;
use App\Helper\PVPNameArraysTrait;
use App\Repository\AcGroupsRepository;
use App\Repository\AnlagenRepository;
use App\Repository\ContactInfoRepository;
use App\Repository\TicketDateRepository;
use App\Repository\TicketRepository;
use App\Service\FunctionsService;
use App\Service\MessageService;
use App\Service\PiiCryptoService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class TicketController extends BaseController
{

    use PVPNameArraysTrait;
    public function __construct(
        private readonly TranslatorInterface $translator)
    {
    }

    use PVPNameArraysTrait;
    #[Route(path: '/ticket/create', name: 'app_ticket_create')]
    public function create(EntityManagerInterface $em, Request $request, AnlagenRepository $anlRepo, functionsService $functions, AcGroupsRepository $acRepo): Response
    {
        if ($request->query->get('anlage') !== null) {
            $anlage = $anlRepo->find($request->query->get('anlage'));
        } else {
            $anlage = null;
        }
        if ($anlage != null) {
            $trafoArray = $this->getTrafoArray($anlage, $acRepo);
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
            $ticket->setEditor($this->getUser()->getUsername() != '' ? $this->getUser()->getUsername() : $this->getUser()->getUserIdentifier());
            if ($ticket->getStatus() == 90) $ticket->setWhenClosed(new DateTime('now'));
            $dates = $ticket->getDates();
            foreach ($dates as $date) {
                $date->copyTicket($ticket);
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
            'performanceTicket' => false,
            'trafoArray'    => $trafoArray
        ]);
    }

    #[Route(path: '/ticket/edit/{id}', name: 'app_ticket_edit')]
    public function edit($id, TicketRepository $ticketRepo, EntityManagerInterface $em, Request $request, functionsService $functions, AcGroupsRepository $acRepo ): Response
    {
        $ticket = $ticketRepo->find($id);
        $anlage = $ticket->getAnlage();

        $sensorArray = [];
        $ticketDates = $ticket->getDates();
        if ($anlage != null) {
            $trafoArray = $this->getTrafoArray($anlage, $acRepo);
        }
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
            #$ticket->setEditor($this->getUser()->getUserIdentifier());
            $ticket->setEditor($this->getUser()->getUsername() != '' ? $this->getUser()->getUsername() : $this->getUser()->getUserIdentifier());
            if ($ticket->getStatus() == 90) {$ticket->setWhenClosed(new DateTime('now')); }
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
            'performanceTicket' => $performanceTicket,
            'trafoArray' => $trafoArray
        ]);
    }

    #[Route(path: '/ticket/list', name: 'app_ticket_list')]
    public function list(TicketRepository $ticketRepo, PaginatorInterface $paginator, Request $request, AnlagenRepository $anlagenRepo): Response
    {

        //here we will count the number of different "proof by tickets"
        $countProofByTam = $ticketRepo->countByProof();
        $countProofByEPC = $ticketRepo->countByProofEPC();
        $countByProofAM = $ticketRepo->countByProofAM();
        $countByProofG4N = $ticketRepo->countByProofG4N();
        $countByProofMaintenance = $ticketRepo->countByProofMaintenance();
        $countIgnored = $ticketRepo->countIgnored();
        $filter = [];
        $session = $request->getSession();
        $pageSession = $session->get('page');
        $page = $request->query->getInt('page');


        if ($request->query->get('filtering') == 'filtered')
        {
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
        $proofMaintenance = $request->get('proofmaintenance', 0);
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
        $queryBuilder = $ticketRepo->getWithSearchQueryBuilderNew($anlage, $editor, $id, $prio, $status, $category, $type, $inverter, $prooftam, $proofepc, $proofam, $proofg4n, $proofMaintenance, $sort, $direction, $ignoredBool, $TicketName, $kpistatus, $begin, $end);


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
            'countProofByAM'  => $countByProofAM,
            'countProofByEPC'  => $countProofByEPC,
            'countProofByTAM'  => $countProofByTam,
            'countProofByG4N'  => $countByProofG4N,
            'countIgnored'     => $countIgnored,
            'countProofByMaintenance' => $countByProofMaintenance
        ]);
    }

    #[Route(path: '/ticket/contact/create/{id}', name: 'app_ticket_create_contact', methods: ['GET', 'POST'])]
    public function createContact($id, TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em): Response
    {
        $ticket = $ticketRepo->findOneById($id);
        $eigner = $ticket->getAnlage()->getEigner();
        $notifications = $ticket->getNotificationInfos();
        $form = $this->createForm(\App\Form\Owner\OwnerContactFormType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eigner->addContactInfo($form->getData());
            $em->persist($eigner);
            $em->flush();
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
        return $this->render('ticket/_inc/_contact_create.html.twig', [
            'ticket'            => $ticket,
            'modalId'           => $ticket->getId(),
            'creationForm'      => $form,
        ]);
    }

    #[Route(path: '/ticket/notify/{id}', name: 'app_ticket_notify', methods: ['GET', 'POST'])]
    public function notify($id, TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em, ContactInfoRepository $contactRepo, MessageService $messageService, PiiCryptoService $encryptService): Response
    {
        $ticket = $ticketRepo->findOneById($id);
        $notifications = $ticket->getNotificationInfos();
        $actualNotification = "";
        $timeDiff = null;
        if (!$notifications->isEmpty()){
            $actualNotification = $notifications->last();
            $actualTime = new DateTime();
            $timeDiff = $actualNotification->getDate()->diff($actualTime)->format("%d days %h hours");
        }
        $eigner = $ticket->getAnlage()->getEigner();
        $form = $this->createForm(\App\Form\Notification\NotificationFormType::class, null, ['eigner' => $eigner]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setNotified(true);
            $contact = $contactRepo->findBy(["id" => $form->getData()['contacted']])[0];
            $key = uniqid($ticket->getId());
            $notification = new NotificationInfo();
            $notification->setTicket($ticket);
            $notification->setStatus(10);
            $notification->setContactedPerson($contact);
            $notification->setWhoNotified($this->getUser());
            $notification->setDate(new DateTime('now'));
            $notification->setFreeText($form->getData()['freeText']);
            $ticket->setSecurityToken($key);
            $ticket->addNotificationInfo($notification);
            $em->persist($notification);
            $em->persist($ticket);
            $em->flush();
            $message = "Maintenance is needed in ". $ticket->getAnlage()->getAnlName().". Please click the button bellow to respond. <br> Message from TAM: <br>".$form->getData()['freeText'];
            $messageService->sendMessageToMaintenance(  $this->translator->trans("ticket.error.category.".$ticket->getAlertType()) . " in ". $ticket->getAnlage()->getAnlName() . "- Ticket: " . $ticket->getId(), $message, $contact->getEmail(), $contact->getName(), $this->getUser()->getname(), false, $ticket);
        }

        return $this->render('ticket/_inc/_notification.html.twig', [
            'ticket'            => $ticket,
            'actualNotification'=> $actualNotification,
            'notificationForm'  => $form,
            'owner'             => $eigner,
            'modalId'           => $ticket->getId(),
            'timeDiff'          => $timeDiff,
            'notifications'     => $ticket->getNotificationInfos()
        ]);
    }

    #[Route(path: '/ticket/proofCount', name: 'app_ticket_proof_count', methods: ['GET', 'POST'])]
    public function getProofCount(TicketRepository $ticketRepo):Response
    {
        $countProofByTam = $ticketRepo->countByProof();
        $countProofByEPC = $ticketRepo->countByProofEPC();
        $countByProofAM = $ticketRepo->countByProofAM();
        $countByProofG4N = $ticketRepo->countByProofG4N();
        $countIgnored = $ticketRepo->countIgnored();
        $countByProofMaintenance = $ticketRepo->countByProofMaintenance();
        return new JsonResponse([
            'countProofByAM'  => $countByProofAM,
            'countProofByEPC'  => $countProofByEPC,
            'countProofByTAM'  => $countProofByTam,
            'countProofByG4N'  => $countByProofG4N,
            'countIgnored'     => $countIgnored,
            'countProofByMaintenance' => $countByProofMaintenance
        ]);
    }

    /**
     * Split Tickets by Time
     *
     * @param $id
     * @param TicketDateRepository $ticketDateRepo
     * @param TicketRepository $ticketRepo
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param AcGroupsRepository $acRepo
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route(path: '/ticket/split/{id}', name: 'app_ticket_split', methods: ['GET', 'POST'])]
    public function split($id, TicketDateRepository $ticketDateRepo, TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em, AcGroupsRepository $acRepo): Response
    {
        $page = $request->query->getInt('page', 1);

        $ticketDate = $ticketDateRepo->findOneById($id);

        $ticket = $ticketRepo->findOneById($ticketDate->getTicket());
        $splitTime = date_create($request->query->get('begin-time'));
        $anlage = $ticket->getAnlage();
        $nameArray = $anlage->getInverterFromAnlage();
        $selected = $ticket->getInverterArray();
        if ($anlage != null) {
            $trafoArray = $this->getTrafoArray($anlage, $acRepo);
        }
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
            'performanceTicket' => false,
            'trafoArray' => $trafoArray
        ]);
    }

    /**
     * Split Ticket by Inverter
     *
     * @param TicketRepository $ticketRepo
     * @param TicketDateRepository $ticketDateRepo
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param AcGroupsRepository $acRepo
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route(path: '/ticket/splitbyinverter', name: 'app_ticket_split_inverter')]
    public function splitByInverter(TicketRepository $ticketRepo, TicketDateRepository $ticketDateRepo, Request $request, EntityManagerInterface $em, AcGroupsRepository $acRepo): Response
    {
        $ticket = $ticketRepo->findOneById($request->query->get('id'));
        $anlage = $ticket->getAnlage();
        if ($anlage != null) {
            $trafoArray = $this->getTrafoArray($anlage, $acRepo);
        }

        $newTicket = new Ticket();
        $newTicket->setInverter($request->query->get('inverterb'));
        $ticket->setInverter($request->query->get('invertera'));
        $newTicket->copyTicket($ticket);
        $newTicket->setInverter($request->query->get('inverterb'));
        $ticket->setInverter($request->query->get('invertera'));

        $ticket->setEditor($this->getUser()->getUsername() != '' ? $this->getUser()->getUsername() : $this->getUser()->getUserIdentifier());
        $ticket->setEditor($this->getUser()->getUsername() != '' ? $this->getUser()->getUsername() : $this->getUser()->getUserIdentifier());
        if ($ticket->getStatus() == 90) $ticket->setWhenClosed(new DateTime('now'));
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
        $inverterArray = [];
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
        $sensorArray = [];
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
            'trafoArray' => $trafoArray
        ]);
    }

    /**
     * Delete hole Ticket
     *
     * @param $id
     * @param TicketRepository $ticketRepo
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @param AnlagenRepository $anlagenRepo
     * @param EntityManagerInterface $em
     * @param RequestStack $requestStack
     * @return Response
     */
    #[Route(path: '/ticket/deleteTicket/{id}', name: 'app_ticket_deleteticket')]
    public function deleteTicket($id, TicketRepository $ticketRepo,  PaginatorInterface $paginator, Request $request, AnlagenRepository $anlagenRepo, EntityManagerInterface $em, RequestStack $requestStack): Response
    {
        //here we will count the number of different "proof by tickets"
        $countProofByTam = $ticketRepo->countByProof();
        $countProofByEPC = $ticketRepo->countByProofEPC();
        $countByProofAM = $ticketRepo->countByProofAM();
        $countByProofG4N = $ticketRepo->countByProofG4N();

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
        $proofmaintenance = $request->query->get('proofmaintenance', 0);
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

        $queryBuilder = $ticketRepo->getWithSearchQueryBuilderNew($anlage, $editor, $id, $prio, $status, $category, $type, $inverter, $prooftam, $proofepc, $proofam, $proofg4n, $proofmaintenance, $sort, $direction, $ignoredBool, $TicketName, $kpistatus, $begin, $end);


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
            'countProofByAM'  => $countByProofAM,
            'countProofByEPC'  => $countProofByEPC,
            'countProofByTAM'  => $countProofByTam,
            'countProofByG4N'  => $countByProofG4N

        ]);
    }

    /**
     * Delete Intervall
     *
     * @param $id
     * @param TicketRepository $ticketRepo
     * @param TicketDateRepository $ticketDateRepo
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param FunctionsService $functions
     * @param AcGroupsRepository $acRepo
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route(path: '/ticket/delete/{id}', name: 'app_ticket_delete')]
    public function delete($id, TicketRepository $ticketRepo, TicketDateRepository $ticketDateRepo, Request $request, EntityManagerInterface $em, functionsService $functions, AcGroupsRepository $acRepo): Response
    {
        $option = $request->query->get('value');
        $page = $request->query->getInt('page', 1);
        $ticketDate = $ticketDateRepo->findOneById($id);
        $ticket = $ticketRepo->findOneById($ticketDate->getTicket());
        $anlage = $ticket->getAnlage();
        if ($anlage != null) {
            $trafoArray = $this->getTrafoArray($anlage, $acRepo);
        }
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
            'invArray' => $inverterArray,
            'trafoArray' => $trafoArray
        ]);
    }

    #[Route(path: '/notification/confirm/{id}', name: 'app_ticket_notification_confirm')]
    public function confirmNotification($id, TicketRepository $ticketRepo, Request $request, PiiCryptoService $encryptService, MessageService $messageService, EntityManagerInterface $em) :Response{
        $ticketId = $encryptService->unHashData($id);
        $ticket = $ticketRepo->findOneBy(['securityToken' => $ticketId]);
        $form = $this->createForm(NotificationConfirmFormType::class, null);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $notification = $ticket->getNotificationInfos()->last();
            $notification->setCloseDate(new DateTime('now'));
            $notification->setStatus($form->getData()['answers'] );
            $notification->setCloseFreeText($form->getData()['freeText']);
            if ($form->getData()['answers'] == 50){
                $messageService->sendRawMessage("Reparation finished - Ticket: " . $ticket->getId(),
                    "The maintenance provider has finished the reparation job.". "<br> Maintenance answer: ". $notification->getCloseFreeText(),
                    $notification->getWhoNotified()->getEmail(), $ticket->getNotificationInfos()->last()->getWhoNotified()->getname(),
                    false);
            }else{
                $messageService->sendRawMessage("Reparation could not be finished - Ticket: " . $ticket->getId(),
                    "There was an unexpected problem and the maintenance provider could not fulfill the reparation request, we recommend contacting someone else for ticket ".$ticket->getId(). ".<br> Maintenance answer: ". $notification->getCloseFreeText(),
                    $notification->getWhoNotified()->getEmail(),
                    $ticket->getNotificationInfos()->last()->getWhoNotified()->getname(),
                    false);
            }
            $em->persist($notification);
            $em->flush();
            return $this->render('/ticket/confirmNotification.html.twig', [
                'ticket'              => $ticket,
                'notificationConfirmForm'  => $form,
                'answered' => true,
            ]);
        }
        return $this->render('/ticket/confirmNotification.html.twig', [
            'ticket'              => $ticket,
            'notificationConfirmForm'  => $form,
            'answered' => false,
        ]);
    }

    /**
     * @param $id
     * @param TicketRepository $ticketRepo
     * @param Request $request
     * @param PiiCryptoService $encryptService
     * @param MessageService $messageService
     * @param EntityManagerInterface $em
     * @param AcGroupsRepository $acRepo
     * @return Response
     */
    #[Route(path: '/notification/edit/{id}', name: 'app_ticket_notification_edit')]
    public function changeNotificationStatus($id, TicketRepository $ticketRepo, Request $request, PiiCryptoService $encryptService, MessageService $messageService, EntityManagerInterface $em, AcGroupsRepository $acRepo) :Response{
        $ticketId = $encryptService->unHashData($id);
        $ticket = $ticketRepo->findOneBy(['securityToken' => $ticketId]);

        $form = $this->createForm(NotificationEditFormType::class, null);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $notification = $ticket->getNotificationInfos()->last();
            $notification->setStatus($form->getData()['answers']);
            $notification->setAnswerDate(new DateTime('now'));
            $notification->setAnswerFreeText($form->getData()['freeText']);
            $em->persist($ticket);

            if ($form->getData()['answers'] == 40){
                $ticket->setStatus(40);
                $notification->setCloseDate(new DateTime('now'));
                $em->persist($notification);
                $em->flush();
                $messageService->sendRawMessage("Request rejected - Ticket: " . $ticket->getId(), "The maintenance provider that was contacted will not be able to fulfill the request, thus we recommend contacting someone else for ticket ". "<br> Maintenance answer: <br>".  $notification->getAnswerFreeText(). $ticket->getId(), $notification->getWhoNotified()->getEmail(), $notification->getWhoNotified()->getname());
                return $this->render('/ticket/editNotification.html.twig', [
                    'ticket'              => $ticket,
                    'notificationEditForm'  => $form,
                    'answered' => true,
                ]);
            }
            else{
                if ($form->getData()['answers'] == 20){
                    $messageService->sendRawMessage("Request accepted - Ticket: " . $ticket->getId(), "The maintenance provider accepted the request and will start working as soon as possible. ". "<br> Maintenance answer: <br>".  $notification->getAnswerFreeText(), $notification->getWhoNotified()->getEmail(), $notification->getWhoNotified()->getname());
                }
                else{
                    $messageService->sendRawMessage("Request accepted but delayed - Ticket: " . $ticket->getId(), "The maintenance provider that was contacted has accepted the request but will need some extra time to start doing it.". "<br> Maintenance answer: <br>". $notification->getAnswerFreeText(), $notification->getWhoNotified()->getEmail(), $notification->getWhoNotified()->getname());
                }

                $messageService->sendConfirmationMessageToMaintenance("Maintenance confirmation - Ticket: " . $ticket->getId(), "Thanks for accepting the request, please report in the link in the button below when the reparations are ready.", $notification->getContactedPerson()->getEmail(), $notification->getContactedPerson()->getName(), false, $ticket);
                $ticket->setStatus(30);
                $em->persist($notification);
                $em->persist($ticket);
                $em->flush();
                return $this->render('/ticket/editNotification.html.twig', [
                    'ticket'              => $ticket,
                    'notificationEditForm'  => $form,
                    'answered' => true,
                ]);
            }
        }

        return $this->render('/ticket/editNotification.html.twig', [
            'ticket'              => $ticket,
            'notificationEditForm'  => $form,
            'answered' => false,
        ]);

    }

    /**
     * @param TicketRepository $ticketRepo
     * @param EntityManagerInterface $em
     * @return Response
     * @throws \JsonException
     */
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
            'text' => 'text',
        ]);
    }

    /**
     * @param $id
     * @param TicketRepository $ticketRepo
     * @return Response
     */
    #[Route(path: '/notification/timeline/{id}', name: 'app_ticket_notification_timeline')]
    public function getTimeline($id, TicketRepository $ticketRepo): Response
    {
        $ticket = $ticketRepo->findOneBy(['id' => $id]);
        $notifications = $ticket->getNotificationInfos();
        if (!$notifications->isEmpty()){
            $beginDate = $notifications->first()->getDate();
            if ($ticket->getStatus() == 90){
                $endTime = $ticket->getWhenClosed();
            }
            else{
                $endTime = new DateTime('now');
            }
            $timeDiff = $beginDate->diff($endTime)->format("%d days %h hours %i minutes");
        }

        return $this->render('/ticket/_inc/_timeline.html.twig', [
            'ticket' => $ticket,
            'notifications' => $notifications,
            'timeElapsed' => $timeDiff,
        ]);
    }

    /**
     * @param $stamp
     * @param $ticket
     * @param $ticketDateRepo
     * @return TicketDate|null
     */
    private function findNextDate($stamp, $ticket, $ticketDateRepo): ?TicketDate
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


    /**
     * @param $stamp
     * @param $ticket
     * @param $ticketDateRepo
     * @return TicketDate|null
     */
    private function findPreviousDate($stamp, $ticket, $ticketDateRepo): ?TicketDate
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

    #[Route(path: '/list/getinverterarray/{id}', name: 'app_tlist_get_inverter_array')]
    public function getInverterArray($id, AnlagenRepository $anlRepo, AcGroupsRepository $acRepo):Response
    {

        $anlage = $anlRepo->findOneBy(['anlId' => $id]);
        $trafoArray = [];
        $inverterArray = [];
        if ($anlage != null){
            $trafoArray = $this->getTrafoArray($anlage, $acRepo);
            $nameArray = $anlage->getInverterFromAnlage();
            foreach ($nameArray as $key => $value){
                $inverterArray[$key]["inv"] = $value;
            }
        }

        return $this->render('/ticket/_inc/_inverter_dropdown.html.twig', [
            'trafoArray'    => $trafoArray,
            'invArray' => $inverterArray,
        ]);
    }
    private function getTrafoArray(Anlage $anlage, AcGroupsRepository $acRepo) :Array{
        $totalTrafoGroups = $acRepo->getAllTrafoNr($anlage);
        $trafoArray = [];
        foreach ($totalTrafoGroups as $trafoGroup) {
            if ($trafoGroup->getTrafoNr() !== null){
                $trafoGroupNr = $trafoGroup->getTrafoNr();
                $acGroup = $acRepo->findByAnlageTrafoNr($anlage, $trafoGroupNr);
                if ($acGroup != []) {
                    if ($anlage->getConfigType() == 3){
                        $trafoArray[$trafoGroupNr]['first'] = $acGroup[0]->getAcGroup();
                        $trafoArray[$trafoGroupNr]['last'] = $acGroup[sizeof($acGroup) - 1]->getAcGroup();
                    }
                    else {
                        $trafoArray[$trafoGroupNr]['first'] = $acGroup[0]->getUnitFirst();
                        $trafoArray[$trafoGroupNr]['last'] = $acGroup[sizeof($acGroup) - 1]->getUnitLast();
                    }
                }
            }
        }
        return $trafoArray;
    }


}
