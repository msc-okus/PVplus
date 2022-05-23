<?php
namespace App\Controller;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\checkSystemStatusService;
use App\Service\MessageService;
use App\Service\ReportService;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[deprecated]
class CronController extends BaseController
{
    use G4NTrait;

    private $urlGenerator;
    private $messageService;

    public function __construct(UrlGeneratorInterface $urlGenerator, MessageService $messageService)
    {
        $this->urlGenerator = $urlGenerator;
        $this->messageService = $messageService;
    }

    #[Route(path: '/cron/checkSystemStatus', name: 'app_cron_checksystemstatus')]
    #[deprecated]
    public function checkSystemStatus(checkSystemStatusService $checkSystemStatus)
    {
        $output = $checkSystemStatus->checkSystemStatus();
        return $this->render('cron/showResult.html.twig', [
            'headline'      => 'Status',
            'availabilitys' => '',
            'output'        => $output,

        ]);
    }



    #[Route(path: '/cron/createMonthlyReport', name: 'app_cron_createmonthlyreport')]
    #[deprecated]
    public function report(ReportService $report, AnlagenRepository $anlagenRepository)
    {
        /** @var Anlage $anlagen */
        $anlagen = $anlagenRepository->findAll();
        $output = $report->monthlyReport($anlagen);
        return $this->render('cron/showResult.html.twig', [
            'headline'      => 'Monthly Report',
            'availabilitys' => '',
            'output'        => $output,
        ]);
    }


}