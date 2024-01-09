<?php

namespace App\Controller;

use App\Repository\AnlagenRepository;
use App\Repository\AnlagenStatusRepository;
use App\Repository\EignerRepository;
use Symfony\Component\Routing\Attribute\Route;

class AlertController extends BaseController
{
    public function __construct(
    )
    {
    }

    #[Route(path: '/alert/send', name: 'app_alert_send')]
    public function sendAlertMessages(AnlagenStatusRepository $anlagenStatusRepository, EignerRepository $ownerRepository, AnlagenRepository $anlagenRepository): void
    {
        $owners = $ownerRepository->findAll();
        foreach ($owners as $owner) {
            $anlagen = $anlagenRepository->findBy([
                'eignerId' => $owner->getEignerId(),
                'anlHidePlant' => 'No',
                'anlView' => 'Yes',
            ]);
            foreach ($anlagen as $anlage) {
                $status = $anlagenStatusRepository->findBy([
                    'anlId' => $anlage->getAnlId(),
                ]);
            }
        }

        return;
        // new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
}
