<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Reports\Goldbeck\EPCMonthlyPRGuaranteeReport;
use App\Reports\Goldbeck\EPCMonthlyYieldGuaranteeReport;
use App\Repository\AnlagenRepository;
use App\Repository\PVSystDatenRepository;
use App\Service\CheckSystemStatusService;
use App\Service\PVSystService;
use App\Service\ReportEpcService;
use App\Service\ReportService;
use App\Service\AssetManagementService;
use Doctrine\ORM\EntityManagerInterface;
use Nuzkito\ChromePdf\ChromePdf;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DefaultController extends BaseController
{
    use G4NTrait;

    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param $doctype ( 0 = PDF, 1 = Excel, 2 = PNG (Grafiken) )
     * @param $charttypetoexport (0 = , 1 = )
     * @param $storeDocument (true / false)
     * @Route("/test/report/{id}/{month}/{year}/{doctype}/{charttypetoexport}/{storeDocument}", defaults={"storeDocument"=false})
     * @deprecated
     */
    public function testReport($id, $month, $year, $doctype, $charttypetoexport, $storeDocument, ReportService $report, AnlagenRepository $anlagenRepository)
    {
        /** @var Anlage [] $anlagen */
        $anlagen = $anlagenRepository->findIdLike([$id]);
        $output = $report->monthlyReport($anlagen, $month, $year, $doctype, $charttypetoexport, $storeDocument);

        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Monthly Report',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }


    /**
     * @Route("/test/pvsyst")
     */
    public function pvsyst(PVSystDatenRepository $PVSystDatenRepository, PVSystService $PVSystService, EntityManagerInterface $em)
    {
        $pvSystDaten = $PVSystDatenRepository->findAll();
        $output = "";

        foreach ($pvSystDaten as $data) {
            $output .= $PVSystService->normalizeDate($data->getStamp()) . '<br>';
            $data->setStamp($PVSystService->normalizeDate($data->getStamp()));
            $em->persist($data);
        }
        $em->flush();

        return $this->render('cron/showResult.html.twig', [
            'headline' => 'PV-Syst Tests',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }

    /**
     * @Route("/test/systemstatus")
     */
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
