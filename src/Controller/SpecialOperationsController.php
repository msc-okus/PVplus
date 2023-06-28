<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Form\Model\WeatherToolsModel;
use App\Form\Tools\ImportExcelFormType;
use App\Message\Command\CalcExpected;
use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use App\Repository\WeatherStationRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\ExportService;
use App\Service\LogMessagesService;
use App\Service\Reports\ReportsMonthlyService;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Helper\simpleXLSX;
use App\Service\UploaderHelper;
use App\Helper\G4NTrait;
class SpecialOperationsController extends AbstractController
{
    use G4NTrait;

    #[IsGranted(['ROLE_G4N'])]
    #[Route(path: '/special/operations/bavelse/report', name: 'bavelse_report')]
    public function bavelseExport(Request $request, ExportService $bavelseExport, AnlagenRepository $anlagenRepository, AvailabilityByTicketService $availabilityByTicket): Response
    {
        $output = $output2 = $availability = '';

        $day = $request->request->get('day');
        $month = $request->request->get('month');
        $year = $request->request->get('year');
        $anlageId = $request->request->get('anlage-id');
        $submitted = $request->request->get('new-report') == 'yes' && isset($month) && isset($year);

        // Start individual part
        /** @var Anlage $anlage */
        $headline = 'Bavelse Berg Monats Bericht';
        $anlageId = '97';
        $anlagen = $anlagenRepository->findBy(['anlId' => $anlageId]);
        $anlage = $anlagenRepository->findOneBy(['anlId' => $anlageId]);

        if ($submitted && isset($anlageId)) {
            $from = date_create($year.'-'.$month.'-01 00:01');
            $daysInMonth = $from->format('t');
            $to = date_create($year.'-'.($month).'-'.$daysInMonth.' 23:59');
            $output        = $bavelseExport->gewichtetTagesstrahlungAsTable($anlage, $from, $to);
            $availability  = "<h3>Plant Availability from " . $from->format('Y-m-d') . " to " . $to->format('Y-m-d') . ": " . $availabilityByTicket->calcAvailability($anlage, $from, $to, null, 2) . "</h3>";
            $availability .= "<h3>Plant Availability from " . $anlage->getFacDateStart()->format('Y-m-d') . " to " . $to->format('Y-m-d') . ": " . $availabilityByTicket->calcAvailability($anlage, $anlage->getFacDateStart(), $to, null, 2) . "</h3>";
        }
        // End individual part

        return $this->render('special_operations/index.html.twig', [
            'headline'      => $headline,
            'anlagen'       => $anlagen,
            'availabilitys' => $availability,
            'output'        => $output,
            'output2'       => $output2,
            'status'        => $anlageId,
        ]);
    }

    /**
     * @throws Exception
     */
    #[IsGranted(['ROLE_G4N', 'ROLE_BETA'])]
    #[Route(path: '/special/operations/monthly', name: 'monthly_report_test')]
    public function monthlyReportTest(Request $request, AnlagenRepository $anlagenRepository, ReportsMonthlyService $reportsMonthly): Response
    {
        $output = null;
        $startDay = $request->request->get('start-day');
        $endDay = $request->request->get('end-day');
        $month = $request->request->get('month');
        $year = $request->request->get('year');
        $anlageId = $request->request->get('anlage-id');
        $submitted = $request->request->get('new-report') == 'yes' && isset($month) && isset($year);

        // Start individual part
        /** @var Anlage $anlage */
        $headline = 'Monats Bericht (Testumgebung)';
        $anlagen = $anlagenRepository->findAllActiveAndAllowed();

        if ($submitted && isset($anlageId)) {
            $anlage = $anlagenRepository->findOneByIdAndJoin($anlageId);
            $output = $reportsMonthly->buildMonthlyReportNewByDate($anlage, $startDay, $endDay, $month, $year);
        }

        return $this->render('report/reportMonthlyNew.html.twig', [
            'headline'      => $headline,
            'anlagen'       => $anlagen,
            'anlage'        => $anlage,
            'report'        => $output,
            'status'        => $anlageId,
        ]);

    }

    #[IsGranted(['ROLE_G4N'])]
    #[Route(path: '/special/operations/loadweatherdata', name: 'load_weatherdata')]
    public function loadUPWeatherData(Request $request, AnlagenRepository $anlagenRepository, WeatherStationRepository $weatherStationRepo, WeatherServiceNew $weatherService, MessageBusInterface $messageBus, LogMessagesService $logMessages,): Response
    {
        $form = $this->createForm(WeatherToolsFormType::class);
        $form->handleRequest($request);

        $output = null;

        // Start individual part
        $headline = '';

        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
            /* @var WeatherToolsModel $toolsModel
             */
            $toolsModel = $form->getData();
            $weatherStation = $weatherStationRepo->findOneBy(['id' => $toolsModel->anlage]);
            $output = '<h3>Load Weather Data UP (all weather stations):</h3>';

            for ($stamp = $toolsModel->startDate->getTimestamp(); $stamp <= $toolsModel->endDate->getTimestamp(); $stamp = $stamp + (24 * 3600)) {
                if (str_starts_with($weatherStation->getType(), 'UP')) {
                    $output .= $weatherService->loadWeatherDataUP($weatherStation, $stamp);
                }
            }

            $toolsModel->endDate->add(new \DateInterval('P1D'));
            $anlagen = $anlagenRepository->findBy(['weatherStation' => $toolsModel->anlage, 'anlHidePlant' => 'No', 'excludeFromExpCalc' => false]);
            foreach ($anlagen as $anlage) {
                if ($anlage->getAnlBetrieb() !== null) {
                    $job = "Update 'G4N Expected' from " . $toolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $toolsModel->endDate->format('Y-m-d 00:00');
                    $logId = $logMessages->writeNewEntry($anlage, 'Expected', $job);
                    $message = new CalcExpected($anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                    $messageBus->dispatch($message);
                    $output .= 'Command was send to messenger! Will be processed in background. Plant: '.$anlage->getAnlName().'<br>';
                } else {
                    $output .= '<p style="color: red; font-weight: bold;">Could not be calculated. Missing installation date.</p>';
                }
            }

        }

        // Wenn Close geklickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('tools/weatherStations.html.twig', [
            'toolsForm'     => $form->createView(),
            'output'        => $output,
        ]);
    }


    #[IsGranted(['ROLE_G4N'])]
    #[Route(path: '/special/operations/deletetickets', name: 'delete_tickets')]
    public function deleteTickets(Request $request, AnlagenRepository $anlagenRepository, TicketRepository $ticketRepo, EntityManagerInterface $em): Response
    {
        $output = null;
        $month = $request->request->get('month');
        $year = $request->request->get('year');

        $anlageId = $request->request->get('anlage-id');
        $submitted = $request->request->get('new-report') == 'yes' && isset($month) && isset($year);

        // Start individual part
        /** @var Anlage $anlage */
        $headline = 'Monats Bericht (Testumgebung)';
        $anlagen = $anlagenRepository->findAllActiveAndAllowed();

        if ($submitted && isset($anlageId)) {
            $anlage = $anlagenRepository->findOneBy(['anlId' => $anlageId]);
            $ticketArray = $ticketRepo->findForSafeDelete($anlage, "$year-$month-01");

            foreach ($ticketArray as $ticket) {
                $em->remove($ticket);
            }
            $em->flush();
            $output = "done";
        }

        return $this->render('special_operations/index.html.twig', [
            'headline'      => $headline,
            'anlagen'       => $anlagen,
            'availabilitys' => '',
            'output'        => $output,
            'output2'       => '',
            'status'        => $anlageId,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/special/operations/import_excel', name: 'import_excel')]
    public function importExcel(Request $request, UploaderHelper $uploaderHelper, AnlagenRepository $anlagenRepository, MessageBusInterface $messageBus, LogMessagesService $logMessages, $uploadsPath): Response
    {
        $form = $this->createForm(ImportExcelFormType::class);
        $form->handleRequest($request);

        $output = null;

        // Start individual part
        $headline = '';

        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
            $anlageForm = $form['anlage']->getData();
            $anlage = $anlagenRepository->findOneBy(['anlId' => $anlageForm]);
            $anlageId = $anlage->getAnlagenId();
            $dataBaseNTable = $anlage->getDbNameIst();

            $uploadedFile = $form['File']->getData();
            if ($uploadedFile) {
                // Here we upload the file and read it
                $newFile = $uploaderHelper->uploadFile($uploadedFile, '/xlsx/1', 'xlsx');
                $conn = self::getPdoConnection();

                if ( $xlsx = simpleXLSX::parse($uploadsPath . '/xlsx/1/'.$newFile) ) {
                    $i = 0;
                    $ts = 0;

                    foreach( $xlsx->rows($ts) as $r ) {
                        if($i == 0) {
                            $data_fields = $r;
                            $indexStamp = array_search('stamp', $data_fields);
                            $indexEzevu = array_search('e_z_evu', $data_fields);
                            //echo $indexEzevu.'<br><br>';
                        }else{
                            $eZEvu = ($r[$indexEzevu] != '') ? $r[$indexEzevu] : NULL;
                            $stmt= $conn->prepare(
                                "UPDATE $dataBaseNTable SET $data_fields[$indexEzevu]=? WHERE $data_fields[$indexStamp]=?"
                            );
                            $stmt->execute([$eZEvu, $r[$indexStamp]]);
                        }

                        $i++;
                    }
                    unlink($uploadsPath . '/xlsx/1/'.$newFile);

                } else {
                    echo SimpleXLSX::parseError();
                }
            }
        }

        // Wenn Close geklickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('tools/importExcel.html.twig', [
            'toolsForm'     => $form->createView(),
            'output'        => $output,
        ]);
    }

}
