<?php

namespace App\Controller;

use App\Form\Model\ToolsModel;
use App\Form\Tools\ToolsFormType;
use App\Helper\G4NTrait;
use App\Message\Command\CalcExpected;
use App\Service\AvailabilityService;
use App\Service\ExpectedService;
use App\Service\LogMessagesService;
use App\Service\PRCalulationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class ToolsController extends BaseController
{
    use G4NTrait;
    /**
     * @Route("/admin/tools", name="app_admin_tools")
     */
    public function tools(Request $request,
                          PRCalulationService $PRCalulation,
                          AvailabilityService $availability,
                          ExpectedService $expectedService,
                          MessageBusInterface $messageBus, LogMessagesService $logMessages): Response
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

            // Print Headline
            switch ($toolsModel->function) {
                case ('expected'):
                    $output .= "<h3>Expected:</h3>";
                    break;
                case ('pr'):
                    $output = "<h3>PR:</h3>";
                    break;
                case('availability'):
                    $output = "<h3>Availability:</h3>";
                    break;
            }
            // Start recalculation

            switch ($toolsModel->function) {
                case ('expected'):
                    $job = "Calculate Expected from ".$toolsModel->startDate->format('Y-m-d')." until ". $toolsModel->endDate->format('Y-m-d');
                    $logId = $logMessages->writeNewEntry($toolsModel->anlage, 'Expected', $job);
                    $message = new CalcExpected($toolsModel->anlage->getAnlId(), $toolsModel->startDate, $toolsModel->endDate, $logId);

                    $messageBus->dispatch($message);

                    $output .= "Command was send to messenger! Will be processed in the background.<br>";
                    break;

                default:
                    for ($date = $start; $date < $end; $date += 86400) {
                        $from = date("Y-m-d 00:00", $date);
                        $to = date("Y-m-d 23:59", $date);
                        $fromShort  = date("Y-m-d 02:00", $date);
                        $toShort    = date("Y-m-d 22:00", $date);
                        $monat = date("m", $date);
                        switch ($toolsModel->function) {
                                /*
                            case ('expected'):
                                $message = new CalcExpected($toolsModel->anlage, $fromShort, $toShort);
                                $messageBus->dispatch($message);

                                //$output .= $expectedService->storeExpectedToDatabase($toolsModel->anlage, $fromShort, $toShort);
                                $output .= "Command was send to messenger! Will be processed in the background.<br>";
                                break;
                                */
                            case ('pr'):
                                $output .= $PRCalulation->calcPRAll($toolsModel->anlage, $from);
                                break;
                            case('availability'):
                                $output .= $availability->checkAvailability($toolsModel->anlage, $date, false);
                                if ($toolsModel->anlage->getShowAvailabilitySecond()) $output .= $availability->checkAvailability($toolsModel->anlage, $date, true);
                                break;
                        }
                    }
            }

        }

        // Wenn Close geklickt wird mache dies:
        if($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('tools/index.html.twig', [
            'toolsForm' => $form->createView(),
            'output'    => $output,
        ]);
    }
}
