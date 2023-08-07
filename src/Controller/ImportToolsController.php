<?php

namespace App\Controller;
use App\Helper\G4NTrait;
use App\Message\Command\ImportData;
use App\Form\Model\ImportToolsModel;
use App\Form\ImportTools\ImportToolsFormType;
use App\Helper\ImportFunctionsTrait;
use App\Repository\AnlagenRepository;
use App\Service\LogMessagesService;
use PDO;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\MeteoControlService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ImportService;
/**
 * @IsGranted("ROLE_G4N")
 */
class ImportToolsController extends BaseController
{
    use ImportFunctionsTrait;
    use G4NTrait;

    #[Route('admin/import/tools', name: 'app_admin_import_tools')]
    public function importManuel(Request $request, MessageBusInterface $messageBus, LogMessagesService $logMessages, AnlagenRepository $anlagenRepo, EntityManagerInterface $entityManagerInterface, ImportService $importService): Response
    {
        $hasStringboxes = 0;
        //getDB-Connection
        $conn = $entityManagerInterface->getConnection();
        //get all Plants for Import via via Cron
        $readyToImport = self::getPlantsImportReady($conn);
        /*echo '<pre>';
        print_r($readyToImport);
        echo '</pre>';

        $key = array_search('216', array_column($readyToImport, 'anlage_id'));
        echo "<br>$key";
        exit;*/
        //Wenn der Import von Cron angestoßen wird.
        $cron = $request->query->get('cron');
        if ($cron == 1) {
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

        }

        if ($cron != 1) {
            //Wenn der Import aus dem Backend angestoßen wird
            $form = $this->createForm(ImportToolsFormType::class);
            $form->handleRequest($request);

            $output = '';
            $break = 0;
            // Wenn Calc gelickt wird mache dies:&& $form->get('calc')->isClicked() $form->isSubmitted() &&
            if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
                /* @var ImportToolsModel $importToolsModel */
                $importToolsModel = $form->getData();

                $importToolsModel->endDate->add(new \DateInterval('P1D'));
                $anlage = $anlagenRepo->findOneBy(['anlId' => $importToolsModel->anlage]);

                $importToolsModel->path = (string)$anlage->getPathToImportScript();
                $importToolsModel->importType = (string)$form->get('importType')->getData();
                $importToolsModel->readyToImport = (array)$readyToImport;
                // Start recalculation
                if ($form->get('importType')->getData() == null) {
                    $output .= 'Please select what you like to import.<br>';
                    $break = 1;
                }

                $hasPpc = $anlage->getHasPPC();

                if($hasPpc != 1 && (string) (string)$importToolsModel->importType == 'api-import-ppc'){
                    $output .= 'This plant has not PPC!<br>';
                    $break = 1;
                }

                if($break == 0){
                    if ($form->get('function')->getData() != null) {
                        switch ($form->get('function')->getData()) {
                            case 'api-import-data':
                                $output = '<h3>Import API Data:</h3>';
                                $job = 'Import API Data('.$importToolsModel->importType.') – from ' . $importToolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $importToolsModel->endDate->format('Y-m-d 00:00');
                                $logId = $logMessages->writeNewEntry($importToolsModel->anlage, 'Import API Data', $job);
                                $message = new ImportData($importToolsModel->anlage->getAnlId(), $importToolsModel->startDate, $importToolsModel->endDate, $importToolsModel->path, $importToolsModel->importType, $logId, $readyToImport);
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

        return new Response('This is used for import via cron job.', 200, array('Content-Type' => 'text/html'));
    }

}