<?php

namespace App\Controller;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatableMessage;


$session = new Session();


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

        $session=$this->container->get('session');
        $ticket = $ticketRepo->find($id);
        $ticketDates = $ticket->getDates();
        if($ticketDates->isEmpty()) $ticketDates = null;
        //reading data from session
        $form = $this->createForm(TicketFormType::class, $ticket);
        $searchstatus = $session->get('search');
        $editor = $session->get('editor');
        $anlage = $session->get('anlage');
        $id = $session->get('id');
        $prio = $session->get('prio');

        $form->handleRequest($request);
      
        //Creating the route with the query
        $Route = $this->generateUrl('app_ticket_list',[], UrlGeneratorInterface::ABS_PATH);
        $Route = $Route."?anlage=".$anlage."&user=".$editor."&id=".$id."&prio=".$prio."&searchstatus=".$searchstatus."&search=yes";

        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $ticket = $form->getData();
            $ticket->setEditor($this->getUser()->getUsername());
            if($ticket->getStatus() === 30 && $ticket->getend()===null)$ticket->setEnd(new \DateTime("now"));

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

        return $this->render('ticket/edit.html.twig', [
            'ticketForm'    => $form->createView(),
            'ticket'        => $ticket,
            'edited'        => true,
            'dates'         => $ticketDates
        ]);
    }

    #[Route(path: '/ticket/list', name: 'app_ticket_list')]
    public function list(TicketRepository $ticketRepo, PaginatorInterface $paginator, Request $request) : Response
    {
        $filter = [];

        //Reading data from request
        $anlage     = $request->query->get('anlage');
        $status     = $request->query->get('status');
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

        $queryBuilder = $ticketRepo->getWithSearchQueryBuilderNew($anlage, $editor, $id, $prio, $status, $category, $type, $inverter);
        $pagination = $paginator->paginate(
            $queryBuilder,                                    /* query NOT result */
            $request->query->getInt('page', 1),   /* page number*/
            100                                          /*limit per page*/
        );

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
            100
        );

        return $this->render('ticket/_inc/_listTickets.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/ticket/split/{id}', name: 'app_ticket_split')]
    public function Split($id, TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em) : Response
    {
        $ticket = $ticketRepo->findOneById($id);
        $beginTime = $request->query->get('begin-time');
        $endTime = $request->query->get('end-time');

             $text = "";

                $Route = $this->generateUrl('app_ticket_list', [], UrlGeneratorInterface::ABS_PATH);
                if ($ticket != null && $beginTime && $endTime) {
                    if ($beginTime > $ticket->getBegin()->format("Y/m/d H:i")){
                        $firstDate = new TicketDate();
                        $firstDate->setBegin($ticket->getBegin()->format("Y/m/d H:i"));
                        $firstDate->setEnd($beginTime);
                        $firstDate->setTicket($ticket);
                        $firstDate->setAnlage($ticket->getAnlage());
                        $firstDate->setStatus($ticket->getStatus());
                        $firstDate->setErrorType($ticket->getErrorType());
                        $firstDate->setFreeText($text);
                        $firstDate->setDescription($ticket->getDescription());
                        $firstDate->setSystemStatus($ticket->getSystemStatus());
                        $firstDate->setPriority($ticket->getPriority());
                        $firstDate->setAnswer($ticket->getAnswer());
                        $firstDate->setInverter($ticket->getInverter());
                        $firstDate->setAlertType($ticket->getAlertType());
                        $ticket->addDate($firstDate);
                        $em->persist($firstDate);
                    }
                    $mainDate = new TicketDate();
                    $mainDate->setBegin($beginTime);
                    $mainDate->setEnd($endTime);
                    $mainDate->setAnlage($ticket->getAnlage());
                    $mainDate->setTicket($ticket);
                    $mainDate->setStatus($ticket->getStatus());
                    $mainDate->setErrorType($ticket->getErrorType());
                    $mainDate->setFreeText($text);
                    $mainDate->setDescription($ticket->getDescription());
                    $mainDate->setSystemStatus($ticket->getSystemStatus());
                    $mainDate->setPriority($ticket->getPriority());
                    $mainDate->setAnswer($ticket->getAnswer());
                    $mainDate->setInverter($ticket->getInverter());
                    $mainDate->setAlertType($ticket->getAlertType());
                    $ticket->addDate($mainDate);

                    $em->persist($mainDate);
                    if ($endTime < $ticket->getEnd()->format("Y/m/d H:i")){
                        $secondDate = new TicketDate();
                        $secondDate->setBegin($endTime);
                        $secondDate->setEnd($ticket->getEnd()->format("Y/m/d H:i"));
                        $secondDate->setTicket($ticket);
                        $secondDate->setAnlage($ticket->getAnlage());
                        $secondDate->setStatus($ticket->getStatus());
                        $secondDate->setErrorType($ticket->getErrorType());
                        $secondDate->setFreeText($text);
                        $secondDate->setDescription($ticket->getDescription());
                        $secondDate->setSystemStatus($ticket->getSystemStatus());
                        $secondDate->setPriority($ticket->getPriority());
                        $secondDate->setAnswer($ticket->getAnswer());
                        $secondDate->setInverter($ticket->getInverter());
                        $secondDate->setAlertType($ticket->getAlertType());
                        $ticket->addDate($secondDate);
                        $em->persist($secondDate);
                    }
                    $ticket->setSplitted(true);
                    $em->persist($ticket);
                    $em->flush();
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
    #[Route(path: '/ticket/split/edit/{id}', name: 'app_ticket_split_edit')]
    public function SplitEdit( $id, TicketRepository $ticketRepo, Request $request, EntityManagerInterface $em) : Response
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
        $text = "";
        if ($ticket != null && $beginTime && $endTime) {
            if ($beginTime > $ticket->getBegin()->format("Y/m/d H:i")){
                $firstDate = new TicketDate();
                $firstDate->setBegin($ticket->getBegin()->format("Y/m/d H:i"));
                $firstDate->setEnd($beginTime);
                $firstDate->setTicket($ticket);
                $firstDate->setAnlage($ticket->getAnlage());
                $firstDate->setStatus($ticket->getStatus());
                $firstDate->setErrorType($ticket->getErrorType());
                $firstDate->setFreeText($text);
                $firstDate->setDescription($ticket->getDescription());
                $firstDate->setSystemStatus($ticket->getSystemStatus());
                $firstDate->setPriority($ticket->getPriority());
                $firstDate->setAnswer($ticket->getAnswer());
                $firstDate->setInverter($ticket->getInverter());
                $firstDate->setAlertType($ticket->getAlertType());
                $ticket->addDate($firstDate);
                $em->persist($firstDate);
            }
            $mainDate = new TicketDate();
            $mainDate->setBegin($beginTime);
            $mainDate->setEnd($endTime);
            $mainDate->setAnlage($ticket->getAnlage());
            $mainDate->setTicket($ticket);
            $mainDate->setStatus($ticket->getStatus());
            $mainDate->setErrorType($ticket->getErrorType());
            $mainDate->setFreeText($text);
            $mainDate->setDescription($ticket->getDescription());
            $mainDate->setSystemStatus($ticket->getSystemStatus());
            $mainDate->setPriority($ticket->getPriority());
            $mainDate->setAnswer($ticket->getAnswer());
            $mainDate->setInverter($ticket->getInverter());
            $mainDate->setAlertType($ticket->getAlertType());
            $ticket->addDate($mainDate);

            $em->persist($mainDate);
            if ($endTime < $ticket->getEnd()->format("Y/m/d H:i")){
                $secondDate = new TicketDate();
                $secondDate->setBegin($endTime);
                $secondDate->setEnd($ticket->getEnd()->format("Y/m/d H:i"));
                $secondDate->setTicket($ticket);
                $secondDate->setAnlage($ticket->getAnlage());
                $secondDate->setStatus($ticket->getStatus());
                $secondDate->setErrorType($ticket->getErrorType());
                $secondDate->setFreeText($text);
                $secondDate->setDescription($ticket->getDescription());
                $secondDate->setSystemStatus($ticket->getSystemStatus());
                $secondDate->setPriority($ticket->getPriority());
                $secondDate->setAnswer($ticket->getAnswer());
                $secondDate->setInverter($ticket->getInverter());
                $secondDate->setAlertType($ticket->getAlertType());
                $ticket->addDate($secondDate);
                $em->persist($secondDate);
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
        $MasterTicket = new Ticket();
        if (count($tickets) > 0) {
            $ticket = $tickets[0];
            $ticketdate = new TicketDate();
            $ticketdate->setBegin($ticket->getBegin());
            $ticketdate->setEnd($ticket->getEnd());
            $begin = $ticket->getBegin();
            $end = $ticket->getEnd();
            $ticketdate->setAlertType($ticket->getAlertType());
            $ticketdate->setInverter($ticket->getInverter());
            $ticketdate->setPriority($ticket->getPriority());
            $ticketdate->setSystemStatus($ticket->getStatus());
            $ticketdate->setErrorType($ticket->getErrorType());
            $ticketdate->setDescription($ticket->getDescription());
            $ticketdate->setAnlage($ticket->getAnlge());
            $ticketdate->setAnswer($ticket->getAnswer());
            $ticketdate->setTicket($MasterTicket);
            $ticketdate->setStatus($ticket->getStatus());
            $ticketdate->setFreeText($ticket->getFreeText());
            for ($i = 1; $i < count($tickets); $i++) {
                $ticket = $ticketRepo->findOneById($tickets[$i]);
                $ticketdate = new TicketDate();
                $ticketdate->setBegin($ticket->getBegin());
                $ticketdate->setEnd($ticket->getEnd());

                if ($ticket->getBegin() < $begin) $begin = $ticket->getBegin();

                if ($ticket->getEnd() > $end) $end = $ticket->getEnd();
                $ticketdate->setAlertType($ticket->getAlertType());
                $ticketdate->setInverter($ticket->getInverter());
                $ticketdate->setPriority($ticket->getPriority());
                $ticketdate->setSystemStatus($ticket->getStatus());
                $ticketdate->setErrorType($ticket->getErrorType());
                $ticketdate->setDescription($ticket->getDescription());
                $ticketdate->setAnlage($ticket->getAnlge());
                $ticketdate->setAnswer($ticket->getAnswer());
                $ticketdate->setTicket($MasterTicket);
                $ticketdate->setStatus($ticket->getStatus());
                $ticketdate->setFreeText($ticket->getFreeText());
                $MasterTicket->addDate($ticketdate);
                dump($ticket);
            }
            $MasterTicket->setEnd($end);
            $MasterTicket->setBegin($begin);
            $em->flush();
            dump($ticket);
        }
        return $this->render('/ticket/join.html.twig', [
            'text' => "estamos aqui"
        ]);
    }

}