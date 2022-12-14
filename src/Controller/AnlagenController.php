<?php

namespace App\Controller;

use App\Entity\EconomicVarNames;
use App\Entity\Eigner;
use App\Form\Anlage\AnlageCustomerFormType;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use App\Repository\AnlagenRepository;
use App\Repository\EconomicVarNamesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnlagenController extends BaseController
{
    use G4NTrait;

    use PVPNameArraysTrait;

    #[Route(path: '/anlagen/list', name: 'app_anlagen_list')]
    public function list(Request $request, PaginatorInterface $paginator, AnlagenRepository $anlagenRepository): Response
    {
        $grantedPlantList = $this->getUser()->getGrantedArray();
        $eigners = [];
        /** @var Eigner $eigner */
        foreach ($this->getUser()->getEigners()->toArray() as $eigner) {
            $eigners[] = $eigner->getId();
        }
        $q = $request->query->get('q');
        if ($request->query->get('search') == 'yes' && $q == '') {
            $request->getSession()->set('q', '');
        }
        if ($q) {
            $request->getSession()->set('q', $q);
        }
        if ($q == '' && $request->getSession()->get('q') != '') {
            $q = $request->getSession()->get('q');
            $request->query->set('q', $q);
        }
        $queryBuilder = $anlagenRepository->getWithSearchQueryBuilderOwner($q, $eigners, $grantedPlantList);
        $pagination = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), 25);

        return $this->render('anlagen/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/anlagen/edit/{id}', name: 'app_anlagen_edit')]
    public function editLegend($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository, EconomicVarNamesRepository $ecoNamesRepo)
    {
        $anlage = $anlagenRepository->find($id);
        $economicVarNames1 = new EconomicVarNames();
        if ($ecoNamesRepo->findByAnlage($id)[0] != null) {
            $economicVarNames1 = $ecoNamesRepo->findByAnlage($id)[0]; // will be used to load and display the already defined names
        }
        $form = $this->createForm(AnlageCustomerFormType::class, $anlage, [
            // 'anlagenId' => $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            if ($economicVarNames1 == null) {
                $economicVarNames = new EconomicVarNames();
            } else {
                $economicVarNames = $economicVarNames1;
            }
            $economicVarNames->setparams($anlage, $form->get('var_1')->getData(), $form->get('var_2')->getData(), $form->get('var_3')->getData(), $form->get('var_4')->getData(), $form->get('var_5')->getData(), $form->get('var_6')->getData(), $form->get('var_7')->getData(), $form->get('var_8')->getData(), $form->get('var_9')->getData(), $form->get('var_10')->getData(), $form->get('var_11')->getData(), $form->get('var_12')->getData(), $form->get('var_13')->getData(), $form->get('var_14')->getData(), $form->get('var_15')->getData());

            // TODO: think and work on the switches, they are quite complex!
            $anlage->setEconomicVarNames($economicVarNames);

            $successMessage = 'Plant data saved!';
            $em->persist($anlage);
            $em->flush();
            if ($form->get('saveclose')->isClicked()) {
                $this->addFlash('success', $successMessage);

                return $this->redirectToRoute('app_anlagen_list');
            }
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_anlagen_list');
        }

        return $this->render('anlagen/edit_customer.html.twig', [
            'anlageForm' => $form->createView(),
            'anlage' => $anlage,
            'econames' => $economicVarNames1,
        ]);
    }

    #[Route(path: '/anlagen/find', name: 'app_plants_find', methods: 'GET')]
    public function find(AnlagenRepository $anlagenRepository, Request $request): JsonResponse
    {
        $anlage = $anlagenRepository->findByAllMatching($request->query->get('query'));

        return $this->json([
            'anlagen' => $anlage,
        ], 200, [], ['groups' => ['main']]);
    }


}
