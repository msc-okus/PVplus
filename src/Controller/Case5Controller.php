<?php

namespace App\Controller;

use App\Helper\G4NTrait;
use App\Repository\Case5Repository;
use App\Service\AvailabilityService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Case5Controller extends BaseController
{
    use G4NTrait;
    /**
     * @Route("/dashboard/plants/case5/edit", methods="GET", name="app_dashboard_plant_case5_edit")
     */
    public function getCase5Api(Case5Repository $case5Repo, Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        $case5 = $case5Repo->findOneBy(['id' => $id]);

        return $this->json($case5, 200, [], ['groups' => ['case5']]);
    }

    /**
     * @Route("/dashboard/plants/case5/delete", methods="GET", name="app_dashboard_plant_case5_delete")
     */
    public function deleteCase5Api(Case5Repository $case5Repo, Request $request, EntityManagerInterface $em, AvailabilityService $availabilityService): RedirectResponse
    {
        $id = $request->query->get('id');
        $case5 = $case5Repo->findOneBy(['id' => $id]);

        $em->remove($case5);
        $em->flush();
        $availabilityService->checkAvailability($case5->getAnlage(), strtotime($case5->getStampFrom()));

        return $this->redirectToRoute('app_dashboard_plant');
    }
}