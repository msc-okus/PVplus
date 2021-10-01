<?php

namespace App\Controller;

use App\Form\Model\ToolsModel;
use App\Form\Ticket\TicketFormType;
use App\Form\Tools\ToolsFormType;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Service\AvailabilityService;
use App\Service\ExpectedService;
use App\Service\PRCalulationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends BaseController
{
    /**
     * @Route("/ticket/create", name="Ticket_form")
     */
    public function create(EntityManagerInterface $em, Request $request)
    {
        $form = $this->createForm(TicketFormType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $ticket = $form->getData();
            $ticket->setEditor($this->getUser()->getUsername());
            $ticket->setTicketActivity($ticket->getBegin());
            $successMessage = 'Ticket data saved!';
            $em->persist($ticket);
            $em->flush();
            return $this->redirectToRoute('Ticket_list');
        }
        return $this->render('Ticket/create.html.twig',[
            'ticketForm'=>$form->createView()
    ]);
    }

    /**
     * @Route("/ticket/list", name="Ticket_list")
     */
    public function list (TicketRepository $ticketRepo, AnlagenRepository $anlagenRepo, UserRepository $userRepo, PaginatorInterface $paginator, Request $request){
        $tickets = $ticketRepo->findAll();
        $q = $request->query->get('qr');
        //this refers to macros.ticket.html.twig
        $searchstatus = $request->query->get('searchstatus');
        $begin = $request->query->get('begin');
        $end = $request->query->get('end');
        if ($request->query->get('search') == 'yes' && $q == '') $request->getSession()->set('qr', '');
        if ($q) $request->getSession()->set('qr', $q);
        if ($searchstatus) $request->getSession()->set('searchstatus', $searchstatus);
        if ($begin) $request->getSession()->set('begin', $begin);
        if ($end) $request->getSession()->set('end', $end);

        if ($q == "" && $request->getSession()->get('qr') != "") {
            $q = $request->getSession()->get('qr');
            $request->query->set('qr', $q);
        }
        if ($searchstatus == "" && $request->getSession()->get('$searchstatus') != "") {
            $searchstatus = $request->getSession()->get('searchstatus');
            $request->query->set('searchstatus', $searchstatus);
        }
        if ($begin == "" && $request->getSession()->get('begin') != "") {
            $begin = $request->getSession()->get('begin');
            $request->query->set('begin', $begin);
        }

        if ($end == "" && $request->getSession()->get('end') != "") {
            $end = $request->getSession()->get('end');
            $request->query->set('end', $end);
        }


/*
        $queryBuilder = $ticketRepo->getWithSearchQueryBuilder($q,$searchstatus,$begin,$end); adapt getWithSearchQueryBuilder to Ticket searchbar
        */
        $queryBuilder = $ticketRepo->findAll();
        $anlagen = $anlagenRepo->findAll();
        $user = $userRepo->findAll();
        $pagination = $paginator->paginate(
            $queryBuilder,                                    /* query NOT result */
            $request->query->getInt('page', 1),   /* page number*/
            20                                          /*limit per page*/
        );
        return $this->render('Ticket/list.html.twig',[
            'pagination' => $pagination,
            'anlagen'    => $anlagen,
            'tickets'    => $tickets
        ]);
    }
}