<?php

namespace App\Controller;

use App\Entity\Eigner;
use App\Entity\User;
use App\Form\User\UserAccountFormType;
use App\Form\User\UserFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends BaseController
{
    #[Route(path: '/admin/user/new', name: 'app_admin_user_new')]
    #[IsGranted('ROLE_OWNER_ADMIN')]
    public function new(EntityManagerInterface $em, Request $request, UserPasswordHasherInterface $userPasswordHasher, SecurityController $security): Response
    {
        $form = $this->createForm(UserFormType::class);
        $form->handleRequest($request);
        $user['locked'] = false;

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');
            return $this->redirectToRoute('app_admin_user_list');
        }

        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var User $user */
            $selPlantList = $form->get('eignersPlantList')->getData();
            $savPlantList = (implode(",", $selPlantList));
            $roles = $form->get('roles')->getData();
            $user = $form->getData();


            if ($this->isGranted('ROLE_G4N')){
                $user->addEigner($form->get('eigners')->getData()[0]);
            } else {
                if ($this->isGranted('ROLE_OWNER_ADMIN')) { // && $security->getUser()->getUserIdentifier() != "admin") {
                    $user->addEigner($security->getUser()->getEigners()[0]);
                } else {
                    // somthing went wrong -> logout
                    return $this->redirectToRoute('app_logout');
                }
            }

            $user->setGrantedList($savPlantList);
            $user->setRoles($roles);
            if ($form['newPlainPassword']->getData() != '') {
                $user->setPassword($userPasswordHasher->hashPassword(
                    $user,
                    $form['newPlainPassword']->getData()
                ));
            }
            $em->persist($user);
            $em->flush();
            $lastId = $user->getId();

            $this->addFlash('success', 'New User created');

            if ($form->isSubmitted() && $form->isValid() && $form->get('saveclose')->isClicked()) {
                $this->addFlash('success', 'data was saved.');
                return $this->redirectToRoute('app_admin_user_list');
            }

            return $this->redirectToRoute('app_admin_user_edit',['id' => $lastId]);
        }

        return $this->render('user/new.html.twig', [
            'userForm' => $form,
            'user'  => $user,
        ]);
    }

    // USER Edit
    #[Route(path: '/admin/user/edit/{id}', name: 'app_admin_user_edit')]
    #[IsGranted('ROLE_OWNER_ADMIN')]
    public function edit($id, EntityManagerInterface $em, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        /** @var User $user */
        $user = $userRepository->find($id);
        // prüfen ob user existiert
        if ($user) {
            $originalG4NRoles = $user->getG4NRoles();
            $form = $this->createForm(UserFormType::class, $user);
            $form->handleRequest($request);
            $selEigner = $form->get('eigners')->getData()[0];
            $selSingleEigner = $form->get('singleeigners')->getData();
            /** @var User $user */
            $user = $form->getData();
            // für die Rolle G4N entfällt die Prüfung
            if (!$this->isGranted('ROLE_G4N')){
            // prüfen ob user und der eigner die gleichen sind
                 if (!in_array($user->getEigners()[0]->getID(), $selSingleEigner)) {
                    $this->addFlash('warning', 'You have no rights to do this.');
                    return $this->redirectToRoute('app_admin_user_list');
                 }
            }

            if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
                $selPlantList = $form->get('eignersPlantList')->getData();
                $savPlantList = (implode(",", $selPlantList));
                $user->setGrantedList($savPlantList);

                // für die Rolle G4N entfällt die Prüfung
                if (!$this->isGranted('ROLE_G4N')){
                    $user->setRoles(array_unique(array_merge($form->getData()->getRoles(), $originalG4NRoles)));
                }

                if ($form['newPlainPassword']->getData() != '') {
                    $user->setPassword($userPasswordHasher->hashPassword(
                        $user,
                        $form['newPlainPassword']->getData()
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
                'userForm' => $form,
                'user'  => $user,
            ]);

        } else {
            $this->addFlash('warning', 'You have no rights to do this.');
            return $this->redirectToRoute('app_admin_user_list');
        }
    }


    // USER List zur Listen Ansicht der User
    #[Route(path: '/admin/user/list', name: 'app_admin_user_list')]
    #[IsGranted('ROLE_OWNER_ADMIN')]
    public function list(Request $request, PaginatorInterface $paginator, UserRepository $userRepository, SecurityController $security): Response
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
        if ($q) {
            $term = $q;
        }

        $queryBuilder = $userRepository->getWithSearchQueryBuilder($term);

        $pagination = $paginator->paginate(
            $queryBuilder,                                  /* query NOT result */
            $request->query->getInt('page', 1)  /* page number */,
            25                                             /* limit per page */
        );

        return $this->render('user/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }


    /**
     * USER Show zum Anzeigen der eigenen Userverwalltung
     **/
    #[Route(path: '/admin/user/show/{id}', name: 'app_admin_user_show')]
    public function editUserAccount($id, EntityManagerInterface $em, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = $userRepository->find($id);
        // prüfen ob user existiert
        if ($user) {
            $form = $this->createForm(UserAccountFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
                if ($form['newPlainPassword']->getData() != '') {
                    $user->setPassword($userPasswordHasher->hashPassword(
                        $user,
                        $form['newPlainPassword']->getData()
                    ));
                }
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'User saved!');

                return $this->redirectToRoute('app_dashboard');
            }

            if ($form->isSubmitted() && $form->get('close')->isClicked()) {
                $this->addFlash('warning', 'Canceled. No data was saved.');
                return $this->redirectToRoute('app_dashboard');
            }

            return $this->render('user/show.html.twig', [
                'userForm' => $form,
            ]);

        } else {
            $this->addFlash('warning', 'You have no rights to do this.');
            return $this->redirectToRoute('app_logout');
        }
    }

    // USER Suche
    #[Route(path: '/user/find', name: 'app_admin_user_find', methods: 'GET')]
    public function findUser(UserRepository $userRepo, Request $request): Response
    {
        $user = $userRepo->findByAllMatching($request->query->get('query'));
        return $this->json([
            'userss' => $user,
        ], 200, [], ['groups' => ['user_list']]);
    }

    // USER Löschen
    #[Route(path: 'admin/user/delete/{id}', name: 'app_admin_user_delete', methods: 'GET')]
    #[IsGranted('ROLE_OWNER_ADMIN')]
    public function deleteUser($id, EntityManagerInterface $em, Request $request,  UserRepository $userRepository, SecurityController $security,): RedirectResponse
    {
        // To do Abfrage Yes No
        $user = $userRepository->find($id);
       // prüfen ob user existiert
        if ($user) {
            $form = $this->createForm(UserFormType::class, $user);
            $form->handleRequest($request);
            $selSingleEigner = $form->get('singleeigners')->getData();
            if (!$this->isGranted('ROLE_G4N')){
                // prüfen ob user und der eigner die gleichen sind
                if (!in_array($user->getEigners()[0]->getID(), $selSingleEigner)) {
                    $this->addFlash('warning', 'You have no rights to do this.');
                    return $this->redirectToRoute('app_admin_user_list');
                }
            }
            $em->remove($user);
            $em->flush();
            $this->addFlash('warning', 'User are deleted.');
        }

        return $this->redirectToRoute('app_admin_user_list');
    }

    /**
     * Lock a user. Means this user is no longer able to login to the Software
     * his email Address will be set to 'anonymous', but Reports, tickets and so on will show the User as last modified
     *
     * @param $id
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param UserRepository $userRepository
     * @param SecurityController $security
     * @return Response
     */
    #[Route(path: 'admin/user/lock/{id}', name: 'app_admin_user_lock')]
    #[IsGranted('ROLE_OWNER_ADMIN')]
    public function lockUser($id, EntityManagerInterface $em, Request $request,  UserRepository $userRepository, SecurityController $security,): Response
    {
        // To do Abfrage Yes No
        $user = $userRepository->find($id);
        // prüfen ob user existiert
        if ($user) {
            $form = $this->createForm(UserFormType::class, $user);
            $form->handleRequest($request);
            $selSingleEigner = $form->get('singleeigners')->getData();
            if (!$this->isGranted('ROLE_OWNER_ADMIN')){
                // prüfen ob user und der eigner die gleichen sind
                if (!in_array($user->getEigners()[0]->getID(), $selSingleEigner)) {
                    $this->addFlash('warning', 'You have no rights to do this.');
                    return $this->redirectToRoute('app_admin_user_list');
                }
            }

            $emailParts = explode('@', $user->getEmail());
            $user->setEmail("locked@".$emailParts[1]);
            $user->setPassword('');
            $user->setLocked(true);
            $em->flush();
            $this->addFlash('warning', 'User are locked.');

            return $this->redirectToRoute('app_admin_user_list');
        }

        return $this->redirectToRoute('app_admin_user_list');
    }
}
