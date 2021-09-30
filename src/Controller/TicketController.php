<?php

namespace App\Controller;

use App\Form\Model\ToolsModel;
use App\Form\Ticket\TicketFormType;
use App\Form\Tools\ToolsFormType;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use App\Service\AvailabilityService;
use App\Service\ExpectedService;
use App\Service\PRCalulationService;
use Doctrine\ORM\EntityManagerInterface;
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

        }
        return $this->render('Ticket/create.html.twig',[
            'ticketForm'=>$form->createView()
    ]);
    }

    public function list (TicketRepository $ticketRepo){
        $tickets = $ticketRepo->findAll();


        return $this->render('Ticket/list.html.twig',[

            'tickets' => $tickets
        ]);
    }
}