<?php

namespace App\Controller;

use App\Entity\Eigner;
use App\Entity\User;
use App\Form\Owner\OwnerFormType;
use App\Form\User\UserFormType;
use App\Repository\EignerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends BaseController
{
    /**
     * @Route("/admin/user/new", name="app_admin_user_new")
     * @IsGranted("ROLE_G4N")
     */
    public function new(EntityManagerInterface $em, Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $form = $this->createForm(UserFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var User $user */
            $user = $form->getData();

            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $form['plainPassword']->getData()
            ));

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'New User created');

            return $this->redirectToRoute('app_admin_user_list');

        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_user_list');
        }


        return $this->render('user/new.html.twig', [
            'userForm'  => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/user/list", name="app_admin_user_list")
     * @IsGranted("ROLE_G4N")
     */
    public function list(Request $request, PaginatorInterface $paginator, UserRepository $userRepository)
    {
        $q = $request->query->get('q');
        if ($request->query->get('search') == 'yes' && $q == '') $request->getSession()->set('q', '');
        if ($q) $request->getSession()->set('q', $q);

        if ($q == "" && $request->getSession()->get('q') != "") {
            $q = $request->getSession()->get('q');
            $request->query->set('q', $q);
        }
        $queryBuilder = $userRepository->getWithSearchQueryBuilder($q);

        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1)  /*page number*/,
            25                                         /*limit per page*/
        );

        return $this->render('user/list.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("/admin/user/edit/{id}", name="app_admin_user_edit")
     * @IsGranted("ROLE_G4N")
     */
    public function edit($id, EntityManagerInterface $em, Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $userRepository->find($id);
        $form = $this->createForm(UserFormType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $user = $form->getData();
            if ($form['plainPassword']->getData() != "") {
                $user->setPassword($passwordEncoder->encodePassword(
                    $user,
                    $form['plainPassword']->getData()
                ));
            }

            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'User saved!');
            if ($form->get('saveclose')->isClicked()) {
                return $this->redirectToRoute('app_admin_user_list');
            }
        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_user_list');
        }

        return $this->render('user/edit.html.twig', [
            'userForm' => $form->createView(),
        ]);
    }
}
