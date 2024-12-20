<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Form\Model\WeatherToolsModel;
use App\Form\Tools\CalcToolsFormType;
use App\Form\Tools\ImportExcelFormType;
use App\Form\Tools\WeatherToolsFormType;
use App\Helper\G4NTrait;
use App\Message\Command\CalcExpected;
use App\Message\Command\CalcPlantAvailabilityNew;
use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use App\Repository\UserLoginRepository;
use App\Repository\WeatherStationRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\ExportService;
use App\Service\LogMessagesService;
use App\Service\PdoService;
use App\Service\UploaderHelper;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use League\Flysystem\FilesystemException;
use Psr\Cache\InvalidArgumentException;
use Shuchkin\SimpleXLSX;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SpecialOperationsController extends BaseController
{
    use G4NTrait;

    public function __construct()
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

    #[IsGranted('ROLE_G4N')]
    #[Route(path: '/special/operations/loadweatherdata', name: 'load_weatherdata')]
    public function loadUPWeatherData(Request $request, AnlagenRepository $anlagenRepository, WeatherStationRepository $weatherStationRepo, WeatherServiceNew $weatherService, MessageBusInterface $messageBus, LogMessagesService $logMessages,): Response
    {
        $form = $this->createForm(WeatherToolsFormType::class);
        $form->handleRequest($request);

        $output = null;
        $uid = $this->getUser()->getUserId();
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
                    $logId = $logMessages->writeNewEntry($anlage, 'Expected', $job, $uid);
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
    #[Route(path: '/special/operations/calctools', name: 'calc_tools')]
    public function toolsCalc(Request $request, AnlagenRepository $anlagenRepo, AvailabilityByTicketService $availabilityByTicket, MessageBusInterface $messageBus, LogMessagesService $logMessages,): Response
    {
        $form = $this->createForm(CalcToolsFormType::class);
        $form->handleRequest($request);
        $output = null;
        $uid = $this->getUser()->getUserId();

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
                        $logId = $logMessages->writeNewEntry($anlage, 'recalculate PA', $job, $uid);
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

        return $this->render('basicTool.html.twig', [
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
     * @throws FilesystemException
     */
    #[Route(path: '/special/operations/import_excel', name: 'import_excel')]
    public function importExcel(Request $request, UploaderHelper $uploaderHelper, AnlagenRepository $anlagenRepository, PdoService $pdoService, $uploadsPath): Response
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
            $uploadedFile = $form['File']->getData();
            if ($uploadedFile) {
                if ($xlsx = simpleXLSX::parse($uploadedFile->getPathname())) {
                    $conn = $pdoService->getPdoPlant();
                    foreach ($xlsx->rows(0) as $key => $row) {
                        if ($key === 0) {
                            $data_fields = $row;
                            $indexStamp = array_search('stamp', $data_fields);
                            $indexEzevu = array_search('e_z_evu', $data_fields);
                            if ($indexEzevu === false) $indexEzevu  = array_search('eGridValue', $data_fields);
                        } else {
                            $eZEvu = $row[$indexEzevu] != '' ? $row[$indexEzevu] : NULL;
                            $stamp = $row[$indexStamp];
                            $stmt= $conn->prepare("UPDATE $dataBaseNTable SET e_z_evu = ? WHERE stamp = ?");
                            $stmt->execute([$eZEvu, $stamp]);
                        }
                    }

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
