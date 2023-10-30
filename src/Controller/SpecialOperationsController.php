<?php

namespace App\Controller;

use App\Service\ExportService;
use App\Entity\Anlage;
use App\Form\Model\WeatherToolsModel;
use App\Form\Tools\CalcToolsFormType;
use App\Form\Tools\ImportExcelFormType;
use App\Form\Tools\WeatherToolsFormType;
use App\Message\Command\CalcExpected;
use App\Message\Command\CalcPlantAvailabilityNew;
use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use App\Repository\UserLoginRepository;
use App\Repository\WeatherStationRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\LogMessagesService;
use App\Service\Reports\ReportsMonthlyV2Service;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Helper\simpleXLSX;
use App\Service\UploaderHelper;
use App\Helper\G4NTrait;
use Knp\Component\Pager\PaginatorInterface;

class SpecialOperationsController extends AbstractController
{
    use G4NTrait;

    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private $userPlant,
        private $passwordPlant,

    )
    {
    }
    /**
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    #[IsGranted('ROLE_G4N')]
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
            $from = date_create($year.'-'.$month.'-01 00:00');
            $daysInMonth = $from->format('t');
            #$daysInMonth = 22;
            $to = date_create($year.'-'.$month.'-'.$daysInMonth.' 23:59');
            #$to = date_create($year.'-'.$month.'-01 23:59');
            $output        = $bavelseExport->gewichtetTagesstrahlungAsTable($anlage, $from, $to);
            #$output        = $bavelseExport->gewichtetBavelseValuesExport($anlage, $from, $to);

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
     * @throws InvalidArgumentException
     */
    #[IsGranted('ROLE_BETA')]
    #[Route(path: '/special/operations/monthly', name: 'monthly_daily_report')]
    public function monthlyReportWithDays(Request $request, AnlagenRepository $anlagenRepository, ReportsMonthlyV2Service $reportsMonthly): Response
    {
        $output = $table = null;
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

        if ($submitted && $anlageId !== null) {
            $anlage = $anlagenRepository->findOneByIdAndJoin($anlageId);
            $output['days'] = $reportsMonthly->buildTable($anlage, $startDay, $endDay, $month, $year);
        }

        return $this->render('special_operations/reportMonthlyNew.html.twig', [
            'headline' => $headline,
            'anlagen' => $anlagen,
            'anlage' => $anlage,
            'report' => $output,
            'status' => $anlageId,
            'datatable' => $table,
        ]);

    }

    /**
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     */
    #[IsGranted('ROLE_BETA')]
    #[Route(path: '/special/operations/report', name: 'month_report')]
    public function reportIndividual(Request $request, AnlagenRepository $anlagenRepository, ReportsMonthlyV2Service $reportsMonthly, AvailabilityByTicketService $availabilityByTicket): Response
    {
        $output = $table = null;

        $anlageId = $request->request->get('anlage-id');
        $startDate = date_create($request->request->get('start-day'));
        $endDate = date_create($request->request->get('end-day'));

        $submittedNew = $request->request->get('new-report') == 'yes' && $anlageId !== "";
        $submittedPA = $request->request->get('recalc-PA') == 'yes' && $anlageId !== "";

        // Start individual part
        /** @var Anlage $anlage */
        $headline = 'Report – individual date, but only monthly values.';
        $anlagen = $anlagenRepository->findAllActiveAndAllowed();

        if ($submittedPA) {
            $anlage = $anlagenRepository->findOneByIdAndJoin($anlageId);
            // recalculate Availability
            for ($stamp = $startDate->getTimestamp(); $stamp <= $endDate->getTimestamp(); $stamp += (24 * 3600)) {
                $day = date_create(date("Y-m-d 12:00", $stamp));
                $availabilityByTicket->checkAvailability($anlage, $day, 0);
                if (!$anlage->getSettings()->isDisableDep1()) $availabilityByTicket->checkAvailability($anlage, $day, 1);
                if (!$anlage->getSettings()->isDisableDep2()) $availabilityByTicket->checkAvailability($anlage, $day, 2);
                if (!$anlage->getSettings()->isDisableDep3()) $availabilityByTicket->checkAvailability($anlage, $day, 3);
            }
        }
        if ($submittedNew) {
            $anlage = $anlagenRepository->findOneByIdAndJoin($anlageId);
            $output['days'] = $reportsMonthly->buildTable2($anlage, $startDate, $endDate);
        }

        return $this->render('special_operations/reportIndividualNew.html.twig', [
            'headline' => $headline,
            'message'  => '',
            'startday' => $startDate->format('Y-m-d'),
            'endday' => $endDate->format('Y-m-d'),
            'anlagen' => $anlagen,
            'anlage' => $anlage,
            'report' => $output,
            'status' => $anlageId,
        ]);

    }

    #[IsGranted('ROLE_G4N')]
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
            'toolsForm'     => $form,
            'output'        => $output,
        ]);
    }

    /**
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    #[IsGranted('ROLE_BETA')]
    #[Route(path: '/special/operations/calctools', name: 'calc_tools')]
    public function toolsCalc(Request $request, AnlagenRepository $anlagenRepo, AvailabilityByTicketService $availabilityByTicket, MessageBusInterface $messageBus, LogMessagesService $logMessages,): Response
    {
        $form = $this->createForm(CalcToolsFormType::class);
        $form->handleRequest($request);
        $output = null;

        // Start individual part
        $headline = '';

        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
            /* @var WeatherToolsModel $toolsModel */
            $toolsModel = $form->getData();
            $toolsModel->endDate->add(new \DateInterval('P1D')); //->sub(new \DateInterval('PT1S'))
            $anlage = $anlagenRepo->findOneBy(['anlId' => $toolsModel->anlage]);

            if ($form->get('function')->getData() != null) {
                switch ($form->get('function')->getData()) {
                    case 'updatePA':
                        $output = '<h3>Recalculate Plant Availability:</h3>';
                        $job = 'Update Plant Availability – from ' . $toolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $toolsModel->endDate->format('Y-m-d 00:00');
                        $job .= " - " . $this->getUser()->getname();
                        $logId = $logMessages->writeNewEntry($anlage, 'recalculate PA', $job);
                        $message = new CalcPlantAvailabilityNew($anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                        $messageBus->dispatch($message);
                        $output .= 'Command will be processed in background.<br> If calculation is DONE (green), you can start PA calculation.';
                        break;
                    case 'calcPA':
                        $output  = "<h3>Plant Availability " . $anlage->getAnlName() . " from " . $toolsModel->startDate->format('Y-m-d H:i') . " to " . $toolsModel->endDate->format('Y-m-d H:i') . "</h3>";
                        $output .= "
                            <table style='width: 50%; text-align: left;'>
                                <tr>
                                    <th>
                                        PA OpenBook
                                    </th>
                                    <th>
                                        PA O&M
                                    </th>
                                    <th>
                                        PA EPC
                                    </th>
                                    <th>
                                        PA AM
                                    </th>
                                </tr>
                                <tr>
                                    <td>
                                        ".round($availabilityByTicket->calcAvailability($anlage, $toolsModel->startDate, $toolsModel->endDate, null, 0), 3)."
                                    </td>
                                    <td>
                                        ".round($availabilityByTicket->calcAvailability($anlage, $toolsModel->startDate, $toolsModel->endDate, null, 1), 3)."
                                    </td>
                                    <td>
                                        ".round($availabilityByTicket->calcAvailability($anlage, $toolsModel->startDate, $toolsModel->endDate, null, 2), 3)."
                                    </td>
                                    <td>
                                        ".round($availabilityByTicket->calcAvailability($anlage, $toolsModel->startDate, $toolsModel->endDate, null, 3), 3)."
                                    </td>
                                <tr>
                            </table><hr>
                         
                        ";
                        break;
                    default:
                        $output = "nothing to do.";
                }
            }
        }

        // Wenn Close geklickt wird, mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('tools/index.html.twig', [
            'toolsForm'     => $form,
            'output'        => $output,
        ]);
    }


    #[IsGranted('ROLE_DEV')]
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

        $output = '';

        // Start individual part
        $headline = '';

        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
            $anlageForm = $form['anlage']->getData();
            $anlage = $anlagenRepository->findOneBy(['anlId' => $anlageForm]);
            $anlageId = $anlage->getAnlagenId();
            $dataBaseNTable = $anlage->getDbNameIst();
            echo $dataBaseNTable;

            $timezones = \DateTimeZone::listIdentifiers();
            print_r($timezones);

            $timezone = new \DateTimeZone('Europe/Berlin');
            $transitions = $timezone->getTransitions();

            foreach($transitions as $transition) {
                echo "Transition: " . date('Y-m-d H:i:s', $transition['ts']) . " (offset: " . $transition['offset'] . " seconds)<br>";
            }
            exit;
            $plantoffset = new \DateTimeZone($this->getNearestTimezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(), strtoupper($anlage->getCountry())));
            $x =  (string)$plantoffset->getName();
            echo $x;
            $datetime = new \DateTime(date('Y/m/d H:i:s'), new \DateTimeZone('Europe/Amsterdam'));
            $offset = $datetime->getOffset();
            if($datetime->format('I')) { // Check if DST is in effect
                echo "<br>Test<br>";
                $offset -= 3600; // Adjust offset by one hour if DST is in effect
            }
            $datetime->setTimezone(new \DateTimeZone('UTC'));
            $datetime->modify("$offset seconds");
            echo $datetime->format('Y-m-d H:i:s');
            exit;
            $uploadedFile = $form['File']->getData();
            if ($uploadedFile) {
                // Here we upload the file and read it
                $newFile = $uploaderHelper->uploadFile($uploadedFile, '/xlsx/1', 'xlsx');

                $conn = $this->pdoService->getPdoPlant();

                if ( $xlsx = simpleXLSX::parse($uploadsPath . '/xlsx/1/'.$newFile) ) {
                    $i = 0;
                    $ts = 0;

                    foreach ( $xlsx->rows($ts) as $row ) {
                        if ($i == 0) {
                            $data_fields = $row;
                            $indexStamp = array_search('stamp', $data_fields);
                            $indexEzevu = array_search('e_z_evu', $data_fields);
                        } else {
                            $eZEvu = ($row[$indexEzevu] != '') ? $row[$indexEzevu] : NULL;
                            $stmt= $conn->prepare(
                                "UPDATE $dataBaseNTable SET $data_fields[$indexEzevu]=? WHERE $data_fields[$indexStamp]=?"
                            );
                            $stmt->execute([$eZEvu, $row[$indexStamp]]);
                        }

                        $i++;
                    }
                    unlink($uploadsPath . '/xlsx/1/'.$newFile);

                } else {
                    $output .= "No valid XLSX File.<br>";
                    $output .= "(" . SimpleXLSX::parseError() . ")";
                }
            }
        }

        // Wenn Close geklickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('tools/importExcel.html.twig', [
            'toolsForm'     => $form,
            'output'        => $output,
        ]);
    }

    /**
     * Reports the logins from Users
     *
     * @throws Exception
     */
    #[Route(path: '/userloginreport', name: 'user_login_report')]
    public function userLoginReport(Request $request, PaginatorInterface $paginator, UserLoginRepository $userLogin): Response
    {
        $q = $request->query->get('q');

        $queryBuilder = $userLogin->getWithSearchQueryBuilder($q);
        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /* page number */
            20                                         /* limit per page */
        );



        return $this->render('loguserlogin/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
