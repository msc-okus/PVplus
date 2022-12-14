<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlageGroups;
use App\Form\Anlage\AnlageGroupsType;
use App\Form\Groups\DcGroupsSearchFormType;
use App\Repository\AnlagenRepository;
use App\Repository\GroupsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Route('/anlage/groups')]
class AnlageGroupsController extends AbstractController
{
    #[Route('/', name: 'app_anlage_groups_index', methods: ['GET','POST'])]
    public function index(GroupsRepository $groupsRepository, Request $request): Response
    {
        $form = $this->createForm(DcGroupsSearchFormType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
           $id=  $form->getData()['dcGroup']->getId();
            return $this->render('anlage_groups/index.html.twig', [
                'anlage_groups' => $groupsRepository->findById($id),
                'form' => $form->createView()
            ]);
        }

        return $this->render('anlage_groups/index.html.twig', [
            'form' => $form->createView()
        ]);
    }




    #[Route('/{id}/edit', name: 'app_anlage_groups_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AnlageGroups $anlageGroup, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AnlageGroupsType::class, $anlageGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_anlage_groups_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('anlage_groups/edit.html.twig', [
            'anlage_group' => $anlageGroup,
            'form' => $form,
        ]);
    }

}
