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
use App\Service\LogMessagesService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_G4N")
 */
class ToolsController extends BaseController
{
    use G4NTrait;

    #[Route(path: '/admin/tools', name: 'app_admin_tools')]
    public function tools(Request $request, MessageBusInterface $messageBus, LogMessagesService $logMessages, AnlagenRepository $anlagenRepo): Response
    {
        $form = $this->createForm(ToolsFormType::class);
        $form->handleRequest($request);

        $output = '';
        // Wenn Calc gelickt wird mache dies:&& $form->get('calc')->isClicked() $form->isSubmitted() &&
        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
            /* @var ToolsModel $toolsModel */
            $toolsModel = $form->getData();
            $start = strtotime($toolsModel->startDate->format('Y-m-d 00:00'));
            $end = strtotime($toolsModel->endDate->format('Y-m-d 23:59'));
            $toolsModel->endDate->add(new \DateInterval('P1D'));
            $anlage = $anlagenRepo->findOneBy(['anlId' => $toolsModel->anlage]);
            // Start recalculation
            if ($form->get('function')->getData() != null) {
                switch ($form->get('function')->getData()) {
                    case 'expected':
                        $output .= '<h3>Expected:</h3>';
                        if ($anlage->getAnlBetrieb() !== null) {
                            $job = "Update 'G4N Expected' from " . $toolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $toolsModel->endDate->format('Y-m-d 00:00');
                            $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'Expected', $job);
                            $message = new CalcExpected($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                            $messageBus->dispatch($message);
                            $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        } else {
                            $output .= '<p style="color: red; font-weight: bold;">Could not be calculated. Missing installation date.</p>';
                        }
                        break;
                    case 'pr':
                        $output = '<h3>PR:</h3>';
                        $job = 'Update PR – from ' . $toolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $toolsModel->endDate->format('Y-m-d 00:00');
                        $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'PR', $job);
                        $message = new CalcPR($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                        $messageBus->dispatch($message);
                        $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        break;
                    case 'availability':
                        $output = '<h3>Availability:</h3>';
                        $job = 'Update Plant Availability – from ' . $toolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $toolsModel->endDate->format('Y-m-d 00:00');
                        $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'PA', $job);
                        $message = new CalcPlantAvailabilityNew($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                        $messageBus->dispatch($message);
                        $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        break;
                    case 'generate-tickets':
                        $output = '<h3>Generate Tickets:</h3>';
                        $job = 'Generate Tickets – from ' . $toolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $toolsModel->endDate->format('Y-m-d 00:00');
                        $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'GenerateTickets', $job);
                        $message = new GenerateTickets($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                        $messageBus->dispatch($message);
                        $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        break;
                    case 'api-load-data':
                        $output = '<h3>Load API Data:</h3>';
                        $job = 'Load API Data – from ' . $toolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $toolsModel->endDate->format('Y-m-d 00:00');
                        $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'Load API Data', $job);
                        $message = new LoadAPIData($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                        $messageBus->dispatch($message);
                        $output .= 'Command was send to messenger! Will be processed in background.<br>';
                        break;
                    case 'api-load-inax-data':
                        $output = '<h3>Load INAX Data:</h3>';
                        $job = 'Load INAX Data – from ' . $toolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $toolsModel->endDate->format('Y-m-d 00:00');
                        $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'Load INAX Data', $job);
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

        // Wenn Close geklickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->renderForm('tools/index.html.twig', [
            'toolsForm' => $form,
            'output' => $output,
        ]);
    }
}