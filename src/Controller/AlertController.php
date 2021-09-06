<?php


namespace App\Controller;

use App\Entity\AnlagenStatus;
use App\Repository\AnlagenRepository;
use App\Repository\AnlagenStatusRepository;
use App\Repository\EignerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AlertController extends BaseController
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }
    /**
     * @Route("/alert/send", name="app_alert_send")
     */
    public function sendAlertMessages(AnlagenStatusRepository $anlagenStatusRepository, EignerRepository $ownerRepository, AnlagenRepository $anlagenRepository) {
        $owners = $ownerRepository->findAll();
        foreach ($owners as $owner) {
            $anlagen = $anlagenRepository->findBy([
                'eignerId' => $owner->getEignerId(),
                'anlHidePlant' => 'No',
                'anlView' => 'Yes'
            ]);
            foreach ($anlagen as $anlage) {
                $status = $anlagenStatusRepository->findBy([
                    'anlId' => $anlage->getAnlId(),
                ]);
            }
        }

        return;// new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
}