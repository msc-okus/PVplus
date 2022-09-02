<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Eigner;
use App\Form\User\UserFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


class UserController extends BaseController
{
    #[Route(path: '/admin/user/new', name: 'app_admin_user_new')]
    #[IsGranted(['ROLE_G4N'])]
    public function new(EntityManagerInterface $em, Request $request, UserPasswordHasherInterface $userPasswordHasher, SecurityController $security): Response
    {
        $form = $this->createForm(UserFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var User $user */
            $selPlantList = $form->get('eignersPlantList')->getData();
            $savPlantList = (implode(",", $selPlantList));

            if ($this->isGranted('ROLE_ADMIN_USER') && $security->getUser()->getUsername() != "admin" ) {
                $eignerDn = $security->getUser()->getEigners()[0];
                $user->addEigner($eignerDn);
            } else {
               # $eignerDn = $security->getUser()->getEigners()[0];
               # $user->addEigner($eignerDn);
            }

            $roles =  $form->get('roles')->getData();
            $user = $form->getData();

            $user->setGrantedList($savPlantList);
            $user->setRoles($roles);

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
            'userForm' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/user/list', name: 'app_admin_user_list')]
    #[IsGranted(['ROLE_ADMIN_OWNER'])]
    public function list(Request $request, PaginatorInterface $paginator, UserRepository $userRepository): Response
    {
        /** @var User $user */
        /** @var Eigner $eigner */

        $q = $request->query->get('qu');
        if ($request->query->get('search') == 'yes' && $q == '') {
            $request->getSession()->set('qu', '');
        }
        if ($q) {
            $request->getSession()->set('qu', $q);
        }
        if ($q == '' && $request->getSession()->get('qu') != '') {
            $q = $request->getSession()->get('qu');
            $request->query->set('qu', $q);
        }

        $queryBuilder = $userRepository->getWithSearchQueryBuilderbyID($q);

        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1)  /* page number */,
            25                                         /* limit per page */
        );

        return $this->render('user/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/admin/user/edit/{id}', name: 'app_admin_user_edit')]
    #[IsGranted(['ROLE_ADMIN_OWNER'])]
    public function edit($id, EntityManagerInterface $em, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = $userRepository->find($id);
        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);
        $selPlantList = $form->get('eignersPlantList')->getData();
        $savPlantList = (implode(",", $selPlantList));

        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $user = $form->getData();
            $user->setGrantedList($savPlantList);

            if ($form['plainPassword']->getData() != '') {
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

        return $this->renderForm('user/edit.html.twig', [
            'userForm' => $form,
            ''
        ]);
    }

    #[Route(path: '/user/find', name: 'app_admin_user_find', methods: 'GET')]
    public function find(UserRepository $userRepo, Request $request): Response
    {
        $user = $userRepo->findByAllMatching($request->query->get('query'));

        return $this->json([
            'userss' => $user,
        ], 200, [], ['groups' => ['user_list']]);
    }


    #[Route(path: 'admin/user/delete/{id}', name: 'app_admin_user_delete')]
    #[IsGranted(['ROLE_G4N'])]
    public function delete($id, EntityManagerInterface $em, Request $request,  UserRepository $userRepository, SecurityController $security)
    {
        $user = $userRepository->find($id);

        if(!$id)
        {
            $user = $userRepository->find($id);
        }

        $rmoves = "";

        if($rmoves != null)
        {
            #$helper = $command->getHelper('question');
            #$question = new ConfirmationQuestion('Continue with this action?', false);

            #if (!$helper->ask($input, $output, $question)) {
            #    return Command::SUCCESS;
            #}

           // $em->remove($rmoves);
           // $em->flush();
            // To do Abfrage Yes No
        }

        $this->addFlash('warning', 'Canceled. No data was saved.');

       return $this->redirectToRoute('app_admin_user_list');
    }

}
