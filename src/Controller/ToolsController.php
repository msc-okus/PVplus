<?php

namespace App\Controller;

use App\Form\Model\ToolsModel;
use App\Form\Tools\ToolsFormType;
use App\Helper\G4NTrait;
use App\Message\Command\CalcExpected;
use App\Message\Command\CalcPlantAvailabilityNew;
use App\Message\Command\CalcPR;
use App\Message\Command\GenerateTickets;
use App\Message\Command\LoadAPIData;
use App\Message\Command\LoadINAXData;
use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use App\Service\LogMessagesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_G4N')]
class ToolsController extends BaseController
{
    use G4NTrait;

    #[Route(path: '/admin/tools', name: 'app_admin_tools')]
    public function tools(Request $request, MessageBusInterface $messageBus, LogMessagesService $logMessages, AnlagenRepository $anlagenRepo, TicketRepository $ticketRepo, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ToolsFormType::class);
        $form->handleRequest($request);

        $output = '';
        // Wenn Calc gelickt wird mache dies:&& $form->get('calc')->isClicked() $form->isSubmitted() &&
        if ($form->isSubmitted() && $form->isValid() && $request->getMethod() == 'POST') {
            /* @var ToolsModel $toolsModel */
            $toolsModel = $form->getData();
            $toolsModel->endDate = new \DateTime($toolsModel->endDate->format('Y-m-d 23:59'));
            $anlage = $anlagenRepo->findOneBy(['anlId' => $toolsModel->anlage]);
            $uid = $this->getUser()->getUserId();
            // Start recalculation
            if ($form->get('function')->getData() != null) {
                switch ($form->get('function')->getData()) {
                    case 'expected':
                        $output .= '<h3>Expected:</h3>';
                        if ($anlage->getAnlBetrieb() !== null) {
                            $job = "Update 'G4N Expected' from " . $toolsModel->startDate->format('Y-m-d H:i') . ' until ' . $toolsModel->endDate->format('Y-m-d H:i');
                            $job .= " - " . $this->getUser()->getname();
                            $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'Expected', $job, $uid);
                            $message = new CalcExpected($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                            $messageBus->dispatch($message);
                            $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        } else {
                            $output .= '<p style="color: red; font-weight: bold;">Could not be calculated. Missing installation date.</p>';
                        }
                        break;
                    case 'pr':
                        $output = '<h3>PR:</h3>';
                        $job = 'Update PR – from ' . $toolsModel->startDate->format('Y-m-d H:i') . ' until ' . $toolsModel->endDate->format('Y-m-d H:i');
                        $job .= " - " . $this->getUser()->getname();
                        $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'PR', $job, $uid);
                        $message = new CalcPR($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                        $messageBus->dispatch($message);
                        $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        break;
                    case 'availability':
                        $output = '<h3>Availability:</h3>';
                        $job = 'Update Plant Availability – from ' . $toolsModel->startDate->format('Y-m-d H:i') . ' until ' . $toolsModel->endDate->format('Y-m-d H:i');
                        $job .= " - " . $this->getUser()->getname();
                        $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'PA', $job, $uid);
                        $message = new CalcPlantAvailabilityNew($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                        $messageBus->dispatch($message);
                        $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        break;
                    case 'generate-tickets':
                        $output = '<h3>Generate Tickets:</h3>';
                        $job = 'Generate Tickets – from ' . $toolsModel->startDate->format('Y-m-d H:i') . ' until ' . $toolsModel->endDate->format('Y-m-d H:i');
                        $job .= " - " . $this->getUser()->getname();
                        $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'GenerateTickets', $job, $uid);
                        $tickets = $ticketRepo->findForSafeDelete($anlage, $toolsModel->startDate->format('Y-m-d H:i'), $toolsModel->endDate->format('Y-m-d H:i'));
                        foreach ($tickets as $ticket) {
                            $dates = $ticket->getDates();
                            foreach ($dates as $date) {
                                $em->remove($date);
                            }
                            $em->remove($ticket);
                        }
                        $em->flush();
                        $message = new GenerateTickets($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                        $messageBus->dispatch($message);
                        $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        break;
                    case 'api-load-data':
                        $output = '<h3>Load API Data:</h3>';
                        $job = 'Load API Data – from ' . $toolsModel->startDate->format('Y-m-d H:i') . ' until ' . $toolsModel->endDate->format('Y-m-d H:i');
                        $job .= " - " . $this->getUser()->getname();
                        $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'Load API Data', $job, $uid);
                        $message = new LoadAPIData($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                        $messageBus->dispatch($message);
                        $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        break;
                    case 'api-load-inax-data':
                        $output = '<h3>Load INAX Data:</h3>';
                        $job = 'Load INAX Data – from ' . $toolsModel->startDate->format('Y-m-d H:i') . ' until ' . $toolsModel->endDate->format('Y-m-d H:i');
                        $job .= " - " . $this->getUser()->getname();
                        $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'Load INAX Data', $job, $uid);
                        $message = new LoadINAXData($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                        $messageBus->dispatch($message);
                        $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        break;
                    default:
                        $output .= 'something went wrong!<br>';
                }

            } else {
                $output .= 'Please select a function.<br>';
            }
        }

        return $this->render('tools/basicTool.html.twig', [
            'toolsForm' => $form,
            'output' => $output,
        ]);
    }
}