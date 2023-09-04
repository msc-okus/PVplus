<?php

namespace App\Controller;
use App\Controller\BaseController;
use App\Form\ImportTools\ImportToolsFormType;
use App\Form\Model\ImportToolsModel;
use App\Helper\G4NTrait;
use App\Helper\ImportFunctionsTrait;
use App\Message\Command\ImportData;
use App\Repository\AnlagenRepository;
use App\Service\ImportService;
use App\Service\LogMessagesService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;


class ImportToolsController extends BaseController
{
    use ImportFunctionsTrait;
    use G4NTrait;

    /**
     * @throws \Exception
     */
    #[Route('admin/import/tools', name: 'app_admin_import_tools')]
    public function importManuel(Request $request, MessageBusInterface $messageBus, LogMessagesService $logMessages, AnlagenRepository $anlagenRepo, EntityManagerInterface $entityManagerInterface, ImportService $importService): Response
    {
        //Wenn der Import aus dem Backend angestoßen wird
        $form = $this->createForm(ImportToolsFormType::class);
        $form->handleRequest($request);

        $output = '';
        $start = true;
        // Wenn Calc gelickt wird mache dies:&& $form->get('calc')->isClicked() $form->isSubmitted() &&
        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
            /* @var ImportToolsModel $importToolsModel */
            $importToolsModel = $form->getData();
            $importToolsModel->endDate = new \DateTime($importToolsModel->endDate->format('Y-m-d 23:59'));
            $importToolsModel->path = $importToolsModel->anlage->getPathToImportScript();
            $importToolsModel->importType = (string)$form->get('importType')->getData();
            // Start recalculation
            if ($form->get('importType')->getData() == null) {
                $output .= 'Please select what you like to import.<br>';
                $start = false;
            }

            if ($importToolsModel->anlage->getHasPPC() != 1 && $importToolsModel->importType == 'api-import-ppc'){
                $output .= 'This plant has not PPC!<br>';
                $start = false;
            }
            if ($start){
                if ($form->get('function')->getData() != null) {
                    switch ($form->get('function')->getData()) {
                        case 'api-import-data':
                            $output = '<h3>Import API Data:</h3>';
                            $job = 'Import API Data('.$importToolsModel->importType.') – from ' . $importToolsModel->startDate->format('Y-m-d H:i') . ' until ' . $importToolsModel->endDate->format('Y-m-d H:i');
                            $job .= " - " . $this->getUser()->getname();
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

    #[Route('/import/cron', name: 'import_cron')]
    public function importCron(Request $request, AnlagenRepository $anlagenRepo, EntityManagerInterface $entityManagerInterface, ImportService $importService): Response
    {
        //getDB-Connection
        $conn = $entityManagerInterface->getConnection();
        //get all Plants for Import via via Cron
        $readyToImport = self::getPlantsImportReady($conn);

        $time = time();
        $time -= $time % 900;
        $start = strtotime(date('Y-m-d H:i', $time - (4 * 3600)));
        $end = $time;


        sleep(5);
        for ($i = 0; $i <= count($readyToImport)-1; $i++) {
            $plantId = $readyToImport[$i]['anlage_id'];
            $anlage = $anlagenRepo->findOneByIdAndJoin($plantId);
            #self::prepareForImport($plantId, $start, $end, '');
            $importService->prepareForImport($plantId, $start, $end, '');
        }

        return new Response('This is used for import via cron job.', 200, array('Content-Type' => 'text/html'));
    }

}