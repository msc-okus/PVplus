<?php

namespace App\Controller;
use App\Message\Command\ImportData;
use App\Form\Model\ImportToolsModel;
use App\Form\ImportTools\ImportToolsFormType;
use App\Helper\G4NTrait;
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
class ImportToolsController extends BaseController
{

    use G4NTrait;

    #[Route('admin/import/tools', name: 'app_admin_import_tools')]
    public function importManuel(Request $request, MessageBusInterface $messageBus, LogMessagesService $logMessages, AnlagenRepository $anlagenRepo): Response
    {
        $form = $this->createForm(ImportToolsFormType::class);
        $form->handleRequest($request);

        $output = '';
        // Wenn Calc gelickt wird mache dies:&& $form->get('calc')->isClicked() $form->isSubmitted() &&
        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
            /* @var ImportToolsModel $importToolsModel */
            $importToolsModel = $form->getData();

            $start = strtotime($importToolsModel->startDate->format('Y-m-d 00:00'));
            $end = strtotime($importToolsModel->endDate->format('Y-m-d 23:59'));
            $importToolsModel->endDate->add(new \DateInterval('P1D'));
            $anlage = $anlagenRepo->findOneBy(['anlId' => $importToolsModel->anlage]);

            $importToolsModel->path = (string)$anlage->getPathToImportScript();

            // Start recalculation
            if ($form->get('importType')->getData() != null) {
                $importToolsModel->importType = (string)$form->get('importType')->getData();
                } else {
                $output .= 'Please select what you like to import.<br>';
            }

            if ($form->get('function')->getData() != null) {
                switch ($form->get('function')->getData()) {
                    case 'api-import-data':
                        $output = '<h3>Import API Data:</h3>';
                        $job = 'Import API Data – from ' . $importToolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $importToolsModel->endDate->format('Y-m-d 00:00');
                        $logId = $logMessages->writeNewEntry($importToolsModel->anlage, 'Import API Data', $job);
                        $message = new ImportData($importToolsModel->anlage->getAnlId(), $importToolsModel->startDate, $importToolsModel->endDate, $importToolsModel->path, $importToolsModel->importType, $logId);
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

        return $this->renderForm('import_tools/index.html.twig', [
            'importToolsForm' => $form,
            'output' => $output,
        ]);
    }
}
