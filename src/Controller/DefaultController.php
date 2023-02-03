<?php

namespace App\Controller;

use App\Helper\G4NTrait;
use App\Service\CheckSystemStatusService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @IsGranted("ROLE_G4N")
 */
class DefaultController extends BaseController
{
    use G4NTrait;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    #[Route(path: '/test/systemstatus')]
    public function checkSystemStatus(CheckSystemStatusService $checkSystemStatus)
    {
        $output = $checkSystemStatus->checkSystemStatus();

        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Systemstatus',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }
}
