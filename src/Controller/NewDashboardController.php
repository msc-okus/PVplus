<?php

namespace App\Controller;

use App\Entity\Eigner;
use App\Repository\EignerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewDashboardController extends BaseController
{
    #[Route(path: '/new', name: 'app_newDashboard')]
    public function index(EignerRepository $eignerRepository): RedirectResponse|Response
    {
        /* @var Eigner [] $eigners */
        /* @var Eigner $owners */
        if ($this->isGranted('ROLE_G4N')) { // Benutzer ist administrator (sieht alle Eigner mit allen Anlagen)
            $eigners = $eignerRepository->findAllDashboard();

        } else {
            $eigners = $this->getUser()->getEigners();
        }
        foreach ($eigners as $eigner) {
            $owners[] = $eigner;
        }



        return $this->render('newDashboardAdmin/eignerShow.html.twig', [
            'content' => $owners,
        ]);
    }

    /**
     * Dashboard für den Eigner (nur Anlagen eines Eigners / standard Seite für Eigner).
     */
    #[Route(path: '/newDashboard/{eignerId}', name: 'app_newDashboard_eigner')]
    public function eignerDashboard($eignerId, Security $security, UserRepository $userRepository, EignerRepository $eignerRepository)
    {
    }
}
