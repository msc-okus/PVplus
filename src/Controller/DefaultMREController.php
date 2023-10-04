<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\AvailabilityService;
use App\Service\CheckSystemStatusService;
use App\Service\ExportService;
use App\Service\ExpectedService;
use App\Service\PRCalulationService;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[IsGranted('ROLE_G4N')]
class DefaultMREController extends BaseController
{
    use G4NTrait;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PRCalulationService $prCalulation,
        private readonly AvailabilityByTicketService $availabilityByTicket,
        private readonly AvailabilityService $availabilityService
    )
    {
    }

    #[Route(path: '/mr/status')]
    public function updateStatus(CheckSystemStatusService $checkSystemStatus): Response
    {
        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Update Systemstatus',
            'availabilitys' => '',
            'output' => $checkSystemStatus->checkSystemStatus(),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route(path: '/mr/expected/{plant}', defaults: ['plant' => 57])]
    public function updateExpected($plant, ExpectedService $expectedService, AnlagenRepository $anlagenRepository): Response
    {
        $anlage = $anlagenRepository->find($plant);
        $from = '2023-06-02 13:00'; //date('Y-m-d 00:00');
        $to = date('Y-m-d 13:59');

        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Update Systemstatus',
            'availabilitys' => '',
            'output' => $expectedService->storeExpectedToDatabase($anlage, $from, $to),
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(path: '/mr/pa/{plant}', defaults: ['plant' => 211])]
    public function updatePA($plant, AvailabilityByTicketService $availability, AnlagenRepository $anlagenRepository): Response
    {
        $anlage = $anlagenRepository->find($plant);
        $from = '2023-06-01 00:00'; //date('Y-m-d 00:00');
        $to =  '2023-09-30 23:59';// date('Y-m-d 13:59');
        $ergebniss = "";
        for ($stamp = strtotime($from); $stamp <= strtotime($to); $stamp = $stamp + (24 * 3600)) {
            $from = date('Y-m-d 00:00', $stamp);
            #$ergebniss .= $availability->checkAvailability($anlage, $from, 0) . "<br>";
            #$ergebniss .= $availability->checkAvailability($anlage, $from, 1) . "<br>";
            $ergebniss .= $availability->checkAvailability($anlage, $from, 2) . "<br>";
            #$ergebniss .= $availability->checkAvailability($anlage, $from, 3) . "<hr>";
        }
        return $this->render('cron/showResult.html.twig', [
            'headline' => 'Update PA',
            'availabilitys' => '',
            'output' => $ergebniss,
        ]);
    }

    /**
     * @throws \Exception|InvalidArgumentException
     */
    #[Route(path: '/mr/pa/test/{plant}/{year}/{month}/{day}', defaults: ['plant' => 108, 'year' => 2022, 'month' => 3, 'day' => 31])]
    public function testPA(int $plant, int $year, int $month, int $day, AvailabilityService $availability, AvailabilityByTicketService $availabilityByTicket, AnlagenRepository $anlagenRepository): Response
    {
        $anlage = $anlagenRepository->find($plant);
        $output = "";
        $date = date_create("$year-$month-01 12:00");
        if ($day === 0) {
            $daysInMonth = 23;
            $daysInMonth = $date->format("t");
            $startday = 1;
        } else {
            $startday = $daysInMonth = $day;
        }
        for ($day = $startday; $day <= $daysInMonth; $day++) {
            $from = date_create("$year-$month-$day 12:00");

            #$output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 0)."<br>";
            #$output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 1)."<br>";
            $output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 2)."<br>";
            #$output .= $this->availabilityByTicket->checkAvailability($anlage, $from, 3)."<br>";
            $output .= "PA: " . number_format(round($this->availabilityByTicket->calcAvailability($anlage, date_create("$year-$month-$day"), date_create("$year-$month-$day"), null, 2), 3),'3') . "<br>";

            #
        }

        $availability = $this->availabilityByTicket->calcAvailability($anlage, date_create("$year-$month-01"), date_create("$year-$month-$daysInMonth"), null, 2);

        return $this->render('cron/showResult.html.twig', [
            'headline' => " PA Dep 2 – Year: $year Month: $month Days in month: $daysInMonth",
            'availabilitys' => '',
            'output' => "<hr>".$output."<hr>PA: $availability",
        ]);
    }


    #[Route(path: '/mr/export/rawdata/{id}')]
    public function exportRawDataExport($id, ExportService $exportService, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);
        $from = $anlage->getEpcReportStart();
        $to = $anlage->getEpcReportEnd();
        $from = date_create("2022-08-01 00:00");
        $to = date_create("2022-08-31 23:55");
        $output = $exportService->getRawData($anlage, $from, $to);

        return $this->render('cron/showResult.html.twig', [
            'headline' => $anlage->getAnlName().' RawData Export',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     */
    #[Route(path: '/mr/export/bavelse/rawdata')]
    public function exportBavelseRawDataExport(ExportService $exportService, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => '97']);
        $from = $anlage->getEpcReportStart();
        $to = $anlage->getEpcReportEnd();
        $from = date_create("2023-01-01 00:00");
        $to = date_create("2023-01-31 23:55");
        $output = $exportService->gewichtetBavelseValuesExport($anlage, $from, $to);

        return $this->render('cron/showResult.html.twig', [
            'headline' => $anlage->getAnlName().' RawData Export',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }

    #[Route(path: '/mr/export/facRawData/{id}', name: 'export_fac_daily')]
    public function exportFacRawDataExport($id, ExportService $export, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);
        $output .= self::printArrayAsTable($export->getFacPRData($anlage, $anlage->getEpcReportStart(), $anlage->getEpcReportEnd()));
        $output .= '<hr>';
        // $output .= self::printArrayAsTable($export->getFacPAData($anlage, $from, $to));
        $output .= '<hr>';

        return $this->render('cron/showResult.html.twig', [
            'headline' => $anlage->getAnlName().' FacData Export',
            'availabilitys' => '',
            'output' => $output,
        ]);
    }


}
