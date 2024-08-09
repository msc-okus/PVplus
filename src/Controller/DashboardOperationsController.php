<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\Eigner;
use App\Repository\EignerRepository;
use App\Service\SystemStatus2;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardOperationsController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[IsGranted('ROLE_OPERATIONS_G4N')]
    #[Route(path: '/operations/dashboard', name: 'app_operations_dashboard')]
    public function operations(EignerRepository $eignerRepository, SystemStatus2 $checkSystemStatus): Response
    {
        $owners = $status = [];
        $eigners = $eignerRepository->findOperations();

        /** @var Eigner $eigner */
        /** @var Anlage $anlage */
        foreach ($eigners as $eigner) {
            $owners[] = $eigner;
            $anlagen = $eigner->getAnlagen();
            foreach ($anlagen as $anlage){
                $status[$anlage->getAnlId()] = $checkSystemStatus->systemStatus($anlage);
            }
        }

        return $this->render('dashboardOperations/Show.html.twig', [
            'content'   => $owners,
            'stati'     => $status
        ]);
    }
}