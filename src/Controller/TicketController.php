<?php

namespace App\Controller;

use App\Form\Model\ToolsModel;
use App\Form\Reports\ReportsFormType;
use App\Form\Ticket\TicketEditFormType;
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
     * @Route("/ticket/create", name="Ticket_create")
     */
    public function create(EntityManagerInterface $em, Request $request)
    {
        $form = $this->createForm(TicketFormType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $ticket = $form->getData();
            $ticket->setEditor($this->getUser()->getUsername());
            $ticket->setTicketActivity($ticket->getBegin());
            $em->persist($ticket);
            $em->flush();
            return $this->redirectToRoute('Ticket_list');
        }
        return $this->render('Ticket/create.html.twig',[
            'ticketForm'=>$form->createView()
    ]);
    }

    /**
     * @Route("/ticket/edit/{id}", name="app_ticket_edit")
     */
    public function edit($id, TicketRepository $ticketRepo, EntityManagerInterface $em, Request $request){
        $ticket = $ticketRepo->find($id);

        $form = $this->createForm(TicketFormType::class, $ticket);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked() ) ) {

            $successMessage = 'Plant data saved!';
            $em->persist($ticket);
            $em->flush();

                return $this->redirectToRoute('app_ticket_list');

        }
        return $this->render('Ticket/edit.html.twig', [
            'ticketForm'    => $form->createView(),
            'ticket'        => $ticket,
        ]);
    }

    /**
     * @Route("/ticket/list", name="app_ticket_list")
     */
    public function list (TicketRepository $ticketRepo, PaginatorInterface $paginator, Request $request){
        $tickets = $ticketRepo->findAll();
        $q = $request->query->get('qr');
        $searchstatus = "";
        $editor = "";
        $anlage = "";

        if($request->query->get('anlage')!=null)$anlage = $request->query->get('anlage');
        if($request->query->get('user')!=null)$editor = $request->query->get('user');
        if($request->query->get('searchstatus')!=null)$searchstatus = $request->query->get('searchstatus');
        dump($searchstatus);
        dump($editor);
        dump($anlage);
        $queryBuilder = $ticketRepo->getWithSearchQueryBuilder($q,$searchstatus,$editor,$anlage);


        $pagination = $paginator->paginate(
            $queryBuilder,                                    /* query NOT result */
            $request->query->getInt('page', 1),   /* page number*/
            20                                          /*limit per page*/
        );

        return $this->render('Ticket/list.html.twig',[
            'pagination' => $pagination,
            'ticket'     => $tickets,
            'anlagep'    => $anlage,
            'userp'      => $editor,
            'status'     => $searchstatus
        ]);
    }

}