<?php

namespace App\Controller;

use App\Entity\Eigner;
use App\Entity\User;
use App\Repository\EignerRepository;
use App\Repository\UserRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class DashboardController extends BaseController
{
    /**
     * @Route("/", name="app_dashboard")
     */
    public function index(EignerRepository $eignerRepository)
    {
        /* @var Eigner [] $eigners */
        /* @var Eigner $owners */
        if ($this->isGranted('ROLE_G4N')) { // Benutzer ist administrator (sieht alle Eigner mit allen Anlagen)
            $eigners = $eignerRepository->findAllDashboard();
        } else {
            $eigners = $this->getUser()->getEigners();
            if ($eigners->count() === 1) {
                // wenn es nur einen Eigner gibt leite direkt auf die Anlagen Seite um
                foreach ($eigners as $eigner) { // leitet auf die Anlagen Seite um
                    return $this->redirectToRoute('app_dashboard_plant', ['eignerId' => $eigner->getEignerId(), 'anlageId' => '00']);
                }
           }
        }

        foreach ($eigners as $eigner) {
            $owners[] = $eigner;
        }
        return $this->render('dashboardAdmin/eignerShow.html.twig', [
            'content'   => $owners
        ]);
    }
    /**
     * Dashboard für den Eigner (nur Anlagen eines Eigners / standard Seite für Eigner)
     * @Route("/dashboard/{eignerId}", name="app_dashboard_eigner")
     */
    public function eignerDashboard($eignerId, Security $security, UserRepository $userRepository, EignerRepository $eignerRepository)
    {

    }
}