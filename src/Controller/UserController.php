<?php

namespace App\Controller;

use App\Entity\Eigner;
use App\Entity\User;
use App\Form\Owner\OwnerFormType;
use App\Form\User\UserFormType;
use App\Repository\AnlagenRepository;
use App\Repository\EignerRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends BaseController
{
    /**
     * @IsGranted("ROLE_G4N")
     */
    #[Route(path: '/admin/user/new', name: 'app_admin_user_new')]
    public function new(EntityManagerInterface $em, Request $request, UserPasswordHasherInterface $userPasswordHasher) : Response
    {
        $form = $this->createForm(UserFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var User $user */
            $user = $form->getData();

            $user->setPassword($userPasswordHasher->hashPassword(
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
     * @IsGranted("ROLE_G4N")
     */
    #[Route(path: '/admin/user/list', name: 'app_admin_user_list')]
    public function list(Request $request, PaginatorInterface $paginator, UserRepository $userRepository) : Response
    {
        $q = $request->query->get('qu');
        if ($request->query->get('search') == 'yes' && $q == '') $request->getSession()->set('qu', '');
        if ($q) $request->getSession()->set('qu', $q);
        if ($q == "" && $request->getSession()->get('qu') != "") {
            $q = $request->getSession()->get('qu');
            $request->query->set('qu', $q);
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
     * @IsGranted("ROLE_G4N")
     */
    #[Route(path: '/admin/user/edit/{id}', name: 'app_admin_user_edit')]
    public function edit($id, EntityManagerInterface $em, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher) : Response
    {
        $user = $userRepository->find($id);
        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $user = $form->getData();
            if ($form['plainPassword']->getData() != "") {
                $user->setPassword($userPasswordHasher->hashPassword(
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
    #[Route(path: '/user/find', name: 'app_admin_user_find', methods: 'GET')]
    public function find(UserRepository $userRepo, Request $request) : Response
    {
        $user = $userRepo->findByAllMatching($request->query->get('query'));
        return $this->json([
            'userss' => $user
        ], 200, [], ['groups' => ['user_list']]);
    }

}
