<?php

namespace App\Controller;

use App\Entity\Eigner;
use App\Form\Owner\OwnerFormType;
use App\Repository\EignerRepository;
use App\Service\G4NSendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EignerController extends BaseController
{
    /**
     * @Route("/admin/owner/new", name="app_admin_owner_new")
     */
    public function new(EntityManagerInterface $em, Request $request): Response
    {
        $form = $this->createForm(OwnerFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var Eigner $owner */
            $owner = $form->getData();

            $em->persist($owner);
            $em->flush();

            $this->addFlash('success', 'New Owner created');

            return $this->redirectToRoute('app_admin_owner_list');

        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_owner_list');
        }


        return $this->render('owner/new.html.twig', [
            'ownerForm' => $form->createView(),
        ]);
    }


    /**
     * @Route("/admin/owner/list", name="app_admin_owner_list")
     */
    public function list(Request $request, PaginatorInterface $paginator, EignerRepository $ownerRepo): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $ownerRepo->getWithSearchQueryBuilder($q);

        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            20                                         /*limit per page*/
        );

        return $this->render('owner/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/admin/owner/edit/{id}", name="app_admin_owner_edit")
     */
    public function edit($id, EntityManagerInterface $em, Request $request, EignerRepository $ownerRepo): Response
    {
        $owner = $ownerRepo->find($id);
        $form = $this->createForm(OwnerFormType::class, $owner);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {

            $em->persist($owner);
            $em->flush();
            $this->addFlash('success', 'Owner saved!');
            if ($form->get('saveclose')->isClicked()) {
                return $this->redirectToRoute('app_admin_owner_list');
            }
        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_owner_list');
        }

        return $this->render('owner/edit.html.twig', [
            'ownerForm' => $form->createView(),
        ]);
    }
}

