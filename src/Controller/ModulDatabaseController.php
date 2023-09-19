<?php

namespace App\Controller;

use App\Entity\AnlageModulesDB;
use App\Form\Anlage\AnlageModulesFormType;
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
    /*
     * Function new()
     * Build the New Form and write the Data in Database for ModulDB
     */
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
    /*
     * Function newcopy()
     * Reguest Data from {id} Build the Copy Form and copy the new Data in Database for ModulDB
     */
    #[Route(path: '/admin/moduldb/newcopy/{id}', name: 'app_admin_moduldb_copy')]
    public function newcopy($id, EntityManagerInterface $em, Request $request, AnlageModulesDBRepository $anlageModulesDBRepository): Response
    {
        $modulescp = $anlageModulesDBRepository->find($id);
        $form = $this->createForm(AnlageModulesFormType::class, clone $modulescp);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var AnlageModulesDb $modules */
            $modulesform = $form->getData();

            $em->detach($modulesform);
            $em->persist($modulesform);

            $em->flush();
            $this->addFlash('success', 'New modul add from a Copy');
            return $this->redirectToRoute('app_admin_moduldb_list');
        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');
            return $this->redirectToRoute('app_admin_moduldb_list');
        }

        return $this->render('modul_database/copy.html.twig', [
            'modulesDBForm' => $form->createView(),
        ]);
    }
    /*
    * Function edit()
    * Reguest Data from {id} Build and edit Form for ModulDB
    */
    #[Route(path: '/admin/moduldb/edit/{id}', name: 'app_admin_moduldb_edit')]
    public function edit($id, EntityManagerInterface $em, Request $request, AnlageModulesDBRepository $anlageModulesDBRepository): Response
    {
        $modules = $anlageModulesDBRepository->find($id);
        $form = $this->createForm(AnlageModulesFormType::class, $modules);
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
    /*
     * Function list()
     * Get all data and Build an Paginator controlled List View for ModulDB
     */
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
            $request->query->getInt('page', 1), /* page first number */
            25                                              /* limit paginator per page */
        );

        return $this->render('modul_database/list.html.twig', [
             'pagination' => $pagination,
        ]);
    }
    /*
    * Function delet()
    * Delet the data with {id} and return to List for ModulDB
    */
    #[Route(path: '/admin/moduldb/delet/{id}', name: 'app_admin_moduldb_delet', methods: ['GET','POST'])]
    public function delet($id, EntityManagerInterface $em, Request $request, AnlageModulesDBRepository $anlageModulesDBRepository): Response
    {

        if ($this->isCsrfTokenValid('deletemodulesdb'.$id, $request->query->get('token'))) {
            $modules = $anlageModulesDBRepository->find($id);
            $em->remove($modules);
            $em->flush();
            $this->addFlash('success', 'Data deleted !.');
         } else {
            $this->addFlash('warning', 'An error was detected');
            return $this->redirectToRoute('app_admin_moduldb_list');
        }
          return $this->redirectToRoute('app_admin_moduldb_list');
    }
}
