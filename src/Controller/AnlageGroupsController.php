<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlageGroups;
use App\Form\Groups\AnlageGroupsTypeForm;
use App\Form\Groups\DcGroupsSearchFormType;
use App\Form\Groups\DcGroupsSFGUpdateFormType;
use App\Repository\GroupsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/anlage/groups')]
class AnlageGroupsController extends AbstractController
{
    #[Route('/', name: 'app_anlage_groups_index', methods: ['GET','POST'])]
    public function index(GroupsRepository $groupsRepository ,Request $request,PaginatorInterface $paginator ): Response
    {
        $form = $this->createForm(DcGroupsSearchFormType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->getData()['anlage'] !==null){

            return $this->redirectToRoute('app_anlage_groups_anlage_index',['anlage'=>$form->getData()['anlage']]);
        }

        $querybuilder=$groupsRepository->findByAnlageQueryBuilder();
        $pagination=$paginator->paginate(
            $querybuilder,
            $request->query->getInt('page',1),
            25
        );
        return $this->render('anlage_groups/index.html.twig', [
            'form' => $form->createView(),
            'anlage_groups'=>$pagination,
            'show_form2'=>false,
            'anlage'=>null
        ]);
    }

    #[Route('/anlage/{anlage}/{param}', name: 'app_anlage_groups_anlage_index', methods: ['GET','POST'])]
    public function index2(GroupsRepository $groupsRepository ,Request $request, Anlage $anlage, PaginatorInterface $paginator, string $param=null ): Response
    {

        $searchTerm = $request->query->get('q');
        if($param){
            $searchTerm = $param;
        }

        $form = $this->createForm(DcGroupsSearchFormType::class,['anlage'=>$anlage]);
        $form2 = $this->createForm(DcGroupsSFGUpdateFormType::class, ['term'=>$searchTerm]);

        $form->handleRequest($request);
        $form2->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            if($form->getData()['anlage'] === null){
              return $this->redirectToRoute('app_anlage_groups_index');
            }
            return $this->redirectToRoute('app_anlage_groups_anlage_index',['anlage'=>$form->getData()['anlage']]);
        }

        if ($form2->isSubmitted() && $form2->isValid()) {

            $groups=$groupsRepository->searchGroupByAnlageQueryBuilder($anlage,$form2->getData()['term'])->getQuery()->getResult();

            foreach ($groups  as $group){
                if($form2->getData()['secureLoss'] !==null){
                    $group->setSecureLoss($form2->getData()['secureLoss']);
                }
                if($form2->getData()['factorAC'] !==null){
                    $group->setFactorAC($form2->getData()['factorAC']);
                }
                if($form2->getData()['gridLoss'] !==null){
                    $group->setGridLoss($form2->getData()['gridLoss']);
                }


                $groupsRepository->save($group, true);
            }

            return $this->redirectToRoute('app_anlage_groups_anlage_index',['anlage'=>$anlage, 'param'=>$form2->getData()['term']]);


        }


        $querybuilder=$groupsRepository->findByAnlageQueryBuilder($anlage);
        $pagination=$paginator->paginate(
            $querybuilder,
            $request->query->getInt('page',1),
            25
        );

        if($request->query->get('search')){
            $querybuilder=$groupsRepository->searchGroupByAnlageQueryBuilder($anlage, $searchTerm);

            if($request->query->get('page')){

                $pagination=$paginator->paginate(
                    $querybuilder,
                    $request->query->getInt('page',$request->query->get('page')),
                    25
                );
                return $this->render('anlage_groups/index.html.twig', [
                    'form' => $form->createView(),
                    'anlage_groups'=>$pagination,
                    'form2'=>$form2->createView(),
                    'show_form2'=>true,
                    'searchTerm'=>$searchTerm,
                    'anlage'=>$anlage
                ]);
            }

            $pagination=$paginator->paginate(
                $querybuilder,
                $request->query->getInt('page',1),
                25
            );
                return $this->render('anlage_groups/_searchPreview.html.twig', [

                    'anlage_groups'=>$pagination,


                ]);

        }
        if($param){
            $querybuilder=$groupsRepository->searchGroupByAnlageQueryBuilder($anlage, $searchTerm);

            $pagination=$paginator->paginate(
                $querybuilder,
                $request->query->getInt('page',1),
                25
            );


        }

        return $this->render('anlage_groups/index.html.twig', [
            'form' => $form->createView(),
            'anlage_groups'=>$pagination,
            'form2'=>$form2->createView(),
            'show_form2'=>true,
            'searchTerm'=>$searchTerm,
            'anlage'=>$anlage
        ]);
    }




    #[Route('/{id}/edit', name: 'app_anlage_groups_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AnlageGroups $anlageGroup,GroupsRepository $groupsRepository, EntityManagerInterface $entityManager): Response
    {
       //dd($anlageGroup->getModules());
        $form = $this->createForm(AnlageGroupsTypeForm::class, $anlageGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_anlage_groups_edit', ['id'=>$anlageGroup->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('anlage_groups/edit.html.twig', [
            'anlage_group' => $anlageGroup,
            'form' => $form,
        ]);
    }

}
