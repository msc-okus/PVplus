<?php

namespace App\Controller;

use App\Entity\AnlageModulesDB;
use App\Form\Anlage\AnlageModulesFormType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use App\Repository\AnlageModulesDBRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[IsGranted('ROLE_G4N')]
class ModulDatabaseController extends BaseController
{
    use G4NTrait;
    use PVPNameArraysTrait;

    #[Route(path: '/admin/moduldb/new', name: 'app_admin_moduldb_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AnlageModulesFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var AnlageModulesDB $modules */
            $modules = $form->getData();
            $em->persist($modules);
            $em->flush();
            $this->addFlash('success', 'New Modul Add');
            return $this->redirectToRoute('app_admin_moduldb_list');
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_moduldb_list');
        }

        return $this->render('modul_database/new.html.twig', [
            'modulesDBForm' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/moduldb/edit/{id}', name: 'app_admin_moduldb_edit')]
    public function edit($id, EntityManagerInterface $em, Request $request, AnlageModulesDBRepository $anlageModulesDBRepository): Response
    {
        $modules = $anlageModulesDBRepository->find($id);
       # dd(  $modules );
        $form = $this->createForm(AnlageModulesFormType::class, $modules);
       # dd( $form);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $em->persist($modules);
            $em->flush();
            $this->addFlash('success', 'Module saved!');
            if ($form->get('saveclose')->isClicked()) {
                return $this->redirectToRoute('app_admin_moduldb_list');
            }
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_moduldb_list');
        }

        return $this->render('modul_database/edit.html.twig', [
            'modulesDBForm' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/moduldb/list', name: 'app_admin_moduldb_list')]
    public function list(Request $request, PaginatorInterface $paginator, AnlageModulesDBRepository $anlageModulesDBRepository): Response
    {


        $q = $request->query->get('qw');
        if ($request->query->get('search') == 'yes' && $q == '') {
            $request->getSession()->set('qw', '');
        }
        if ($q) {
            $request->getSession()->set('qw', $q);
        }
        if ($q == '' && $request->getSession()->get('qw') != '') {
            $q = $request->getSession()->get('qw');
            $request->query->set('qw', $q);
        }
        $queryBuilder = $anlageModulesDBRepository->getWithSearchQueryBuilder($q);
        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /* page number */
            25                                         /* limit per page */
        );

        return $this->render('modul_database/list.html.twig', [
             'pagination' => $pagination,
        ]);
    }


}
