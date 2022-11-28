<?php

namespace App\Controller;

use App\Form\Model\ToolsModel;
use App\Form\Tools\ToolsFormType;
use App\Helper\G4NTrait;
use App\Message\Command\CalcExpected;
use App\Message\Command\CalcPlantAvailability;
use App\Message\Command\CalcPlantAvailabilityNew;
use App\Message\Command\CalcPR;
use App\Service\LogMessagesService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class ToolsController extends BaseController
{
    use G4NTrait;

    #[Route(path: '/admin/tools', name: 'app_admin_tools')]
    public function tools(Request $request, MessageBusInterface $messageBus, LogMessagesService $logMessages): Response
    {
        $form = $this->createForm(ToolsFormType::class);
        $form->handleRequest($request);
        $output = '';
        // Wenn Calc gelickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
            /* @var ToolsModel $toolsModel */
            $toolsModel = $form->getData();
            $start = strtotime($toolsModel->startDate->format('Y-m-d 00:00'));
            $end = strtotime($toolsModel->endDate->format('Y-m-d 23:59'));
            $toolsModel->endDate->add(new \DateInterval('P1D'));

            // Start recalculation
            switch ($toolsModel->function) {
                case 'expected':
                    $output .= '<h3>Expected:</h3>';
                    $job = "Update 'G4N Expected' from ".$toolsModel->startDate->format('Y-m-d 00:00').' until '.$toolsModel->endDate->format('Y-m-d 00:00');
                    $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'Expected', $job);
                    $message = new CalcExpected($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                    $messageBus->dispatch($message);
                    break;
                case 'pr':
                    $output = '<h3>PR:</h3>';
                    $job = 'Update PR – from '.$toolsModel->startDate->format('Y-m-d 00:00').' until '.$toolsModel->endDate->format('Y-m-d 00:00');
                    $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'PR', $job);
                    $message = new CalcPR($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                    $messageBus->dispatch($message);
                    break;
                case 'availability':
                    $output = '<h3>Availability:</h3>';
                    $job = 'Update Plant Availability – from '.$toolsModel->startDate->format('Y-m-d 00:00').' until '.$toolsModel->endDate->format('Y-m-d 00:00');
                    $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'PA', $job);
                    $message = new CalcPlantAvailability($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                    $messageBus->dispatch($message);
                    break;
                case 'availability-new':
                    $output = '<h3>Availability New:</h3>';
                    $job = 'Update Plant Availability (new) – from '.$toolsModel->startDate->format('Y-m-d 00:00').' until '.$toolsModel->endDate->format('Y-m-d 00:00');
                    $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'PA', $job);
                    $message = new CalcPlantAvailabilityNew($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);
                    $messageBus->dispatch($message);
                    break;
                default:
                    $output .= 'something went wrong!<br>';
            }
            $output .= 'Command was send to messenger! Will be processed in background.<br>';
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
