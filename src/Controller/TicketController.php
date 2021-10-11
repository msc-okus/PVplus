<?php

namespace App\Controller;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
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
use Carbon\Doctrine\DateTimeType;
use Doctrine\ORM\EntityManagerInterface;
use http\Url;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;


$session = new Session();


class TicketController extends BaseController
{
    /**
     * @Route("/ticket/create", name="Ticket_create")
     */
    public function create(EntityManagerInterface $em, Request $request)
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
            return $this->redirect($Route);
            $this->addFlash('success', 'User saved!');
            if ($form->get('saveclose')->isClicked()) {
                return $this->redirectToRoute('app_ticket_list');
            }
        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_ticket_list');
        }
        return $this->render('Ticket/create.html.twig',[
            'ticketForm'=>$form->createView()
    ]);
    }

    /**
     * @Route("/ticket/edit/{id}", name="app_ticket_edit")
     */
    public function edit($id, TicketRepository $ticketRepo, EntityManagerInterface $em, Request $request){
        $session=$this->container->get('session');
        $ticket = $ticketRepo->find($id);
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

            $em->persist($ticket);
            $em->flush();
            return $this->redirect($Route);
            $this->addFlash('success', 'User saved!');
            if ($form->get('saveclose')->isClicked()) {
                return $this->redirectToRoute('app_ticket_list');
            }
        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_ticket_list');
        }
        return $this->render('Ticket/edit.html.twig', [
            'ticketForm'    => $form->createView(),
            'ticket'        => $ticket,
        ]);
    }

    /**
     * @Route("/ticket/list", name="app_ticket_list")
     *
     */
    public function list (TicketRepository $ticketRepo, PaginatorInterface $paginator, Request $request){
        $session=$this->container->get('session');
        $tickets = $ticketRepo->findAll();
        //Reading data from request
        if($request->query->get('anlage')!=null & $request->query->get('anlage')!="")$anlage = $request->query->get('anlage');
        if($request->query->get('user')!=null & $request->query->get('user')!="")$editor = $request->query->get('user');
        if($request->query->get('searchstatus')!=null & $request->query->get('searchstatus')!="")$searchstatus = $request->query->get('searchstatus');
        if($request->query->get('id')!=null)$id = $request->query->get('id');
        if($request->query->get('prio')!=null)$prio = $request->query->get('prio');

        $queryBuilder = $ticketRepo->getWithSearchQueryBuilder($searchstatus,$editor,$anlage, $id, $prio);
        $pagination = $paginator->paginate(
            $queryBuilder,                                    /* query NOT result */
            $request->query->getInt('page', 1),   /* page number*/
            20                                          /*limit per page*/
        );

        $session->set('search', $searchstatus);
        $session->set('editor', $editor);
        $session->set('anlage', $anlage);
        $session->set('id', $id);
        $session->set('prio', $prio);
        return $this->render('Ticket/list.html.twig',[
            'pagination' => $pagination,
            'ticket'     => $tickets,
            'anlagep'    => $anlage,
            'userp'      => $editor,
            'status'     => $searchstatus,
            'id'         => $id,
            'prio'       => $prio,

        ]);
    }

}