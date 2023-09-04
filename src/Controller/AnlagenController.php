<?php

namespace App\Controller;
use App\Service\GetPdoService;

use App\Entity\EconomicVarNames;
use App\Entity\Eigner;
use App\Form\Anlage\AnlageCustomerFormType;
use App\Form\Anlage\AnlageFormType;
use App\Helper\G4NTrait;
use App\Helper\PVPNameArraysTrait;
use App\Repository\AnlagenRepository;
use App\Repository\EconomicVarNamesRepository;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnlagenController extends BaseController
{
    use G4NTrait;
    use PVPNameArraysTrait;

    #[Route(path: 'api/anlagen/list', name: 'api_anlagen_list', methods: ['GET','POST'])]
    public function api_list_analge(PaginatorInterface $paginator, AnlagenRepository $anlagenRepository): Response
    {
        $grantedPlantList = $this->getUser()->getGrantedArray();
        $eigners = [];
        /** @var Eigner $eigner */
        foreach ($this->getUser()->getEigners()->toArray() as $eigner) {
            $eigners[] = $eigner->getId();
        }

        $anlage = $anlagenRepository->findAllIDByEigner($eigners);
        $content[] = $anlage;

        if (is_array($content) or $content) {
            return new JsonResponse($content);
        } else {
            return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
        }
    }

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
    public function editLegend($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository, EconomicVarNamesRepository $ecoNamesRepo): Response
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

            if ($this->isGranted('ROLE_AM')) {
                $economicVarNames->setparams($anlage, $form->get('var_1')->getData(), $form->get('var_2')->getData(), $form->get('var_3')->getData(), $form->get('var_4')->getData(), $form->get('var_5')->getData(), $form->get('var_6')->getData(), $form->get('var_7')->getData(), $form->get('var_8')->getData(), $form->get('var_9')->getData(), $form->get('var_10')->getData(), $form->get('var_11')->getData(), $form->get('var_12')->getData(), $form->get('var_13')->getData(), $form->get('var_14')->getData(), $form->get('var_15')->getData());

                // TODO: think and work on the switches, they are quite complex!
                $anlage->setEconomicVarNames($economicVarNames);
            }

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

    //
    #[Route(path: '/anlagen/setting/edit/{id}', name: 'app_anlagen_setting_edit')]
    public function edit($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository, UploaderHelper $uploaderHelper ): RedirectResponse|Response
    {
        $anlage = $anlagenRepository->find($id);
        $form = $this->createForm(AnlageFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked() || $form->get('savecreatedb')->isClicked())) {

            // Forecast Tab Field Check
            if($form['useDayForecast']->getData() === true) {
                $checkfields = true;
                if ($form->get('bezMeridan')->isEmpty()) {
                    $this->addFlash('warning', 'Field Bezugs Meridan fail.');
                    $checkfields = false;
                }
                if ($form->get('modNeigung')->isEmpty()) {
                    $this->addFlash('warning', 'Field Modul Neigung fail.');
                    $checkfields = false;
                }
                if ($form->get('albeto')->isEmpty()) {
                    $this->addFlash('warning', 'Field Albeto fail.');
                    $checkfields = false;
                }
                if ($form->get('modAzimut')->isEmpty()) {
                    $this->addFlash('warning', 'Field Modul Azimut fail.');
                    $checkfields = false;
                }

                if ($checkfields === false){
                    return $this->render('anlagen/edit.html.twig', [
                        'anlageForm' => $form->createView(),
                        'anlage' => $anlage,
                    ]);
                }

            }

            $uploadedDatFile = $form['datFilename']->getData();

            if ($uploadedDatFile) {
                $uploadsPath = "/metodat";
                $newFile = $uploaderHelper->uploadAllFile($uploadedDatFile,$uploadsPath,'dat');
                if ($newFile) {
                    $anlage->setDatFilename($newFile);
                }
            }

            $successMessage = 'Plant data saved!';

            if ($form->get('savecreatedb')->isClicked()) {
                if ($this->createDatabasesForPlant($anlage)) {
                    $successMessage = 'Plant data saved and DB created.';
                }
            }

            $em->persist($anlage);
            $em->flush();
            $this->addFlash('success', $successMessage);

            if ($form->get('saveclose')->isClicked()) {
                return $this->redirectToRoute('app_anlagen_list');
            }
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_anlagen_list');
        }


        return $this->render('anlagen/edit.html.twig', [
            'anlageForm' => $form->createView(),
            'anlage' => $anlage,
        ]);
    }

    //
    #[Route(path: '/anlagen/find', name: 'app_plants_find', methods: 'GET')]
    public function find(AnlagenRepository $anlagenRepository, Request $request): JsonResponse
    {
        $anlage = $anlagenRepository->findByAllMatching($request->query->get('query'));

        return $this->json([
            'anlagen' => $anlage,
        ], 200, [], ['groups' => ['main']]);
    }


}
