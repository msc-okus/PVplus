<?php

namespace App\Controller;

use App\Entity\Eigner;
use App\Form\Anlage\AnlageCustomerFormType;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AnlagenController extends BaseController
{
    use G4NTrait;

    /**
     * @Route("/anlagen/list", name="app_anlagen_list")
     */
    public function list(Request $request, PaginatorInterface $paginator, AnlagenRepository $anlagenRepository)
    {
        $grantedPlantList = explode(',', $this->getUser()->getGrantedList());
        $eigners = [];
        /** @var Eigner $eigner */
        foreach ($this->getUser()->getEigners()->toArray() as $eigner) { $eigners[] = $eigner->getId(); }

        $q = $request->query->get('q');
        if ($request->query->get('search') == 'yes' && $q == '') $request->getSession()->set('q', '');
        if ($q) $request->getSession()->set('q', $q);

        if ($q == "" && $request->getSession()->get('q') != "") {
            $q = $request->getSession()->get('q');
            $request->query->set('q', $q);
        }

        $queryBuilder = $anlagenRepository->getWithSearchQueryBuilderOwner($q, $eigners, $grantedPlantList);

        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            25                                         /*limit per page*/
        );

        return $this->render('anlagen/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }


    /**
     * @Route("/anlagen/edit/{id}", name="app_anlagen_edit")
     */
    public function editLegend($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository)
    {
        $anlage = $anlagenRepository->find($id);
        $form = $this->createForm(AnlageCustomerFormType::class, $anlage, [
            //'anlagenId' => $id,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked() ) ) {

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
            'anlageForm'    => $form->createView(),
            'anlage'        => $anlage,
        ]);
    }
}
