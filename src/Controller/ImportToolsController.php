<?php

namespace App\Controller;
use App\Message\Command\ImportData;
use App\Form\Model\ImportToolsModel;
use App\Form\ImportTools\ImportToolsFormType;
use App\Helper\G4NTrait;
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
/**
 * @IsGranted("ROLE_G4N")
 */
class ImportToolsController extends BaseController
{
    use ImportFunctionsTrait;

    #[Route('admin/import/tools', name: 'app_admin_import_tools')]
    public function importManuel(Request $request, MessageBusInterface $messageBus, LogMessagesService $logMessages, AnlagenRepository $anlagenRepo, EntityManagerInterface $entityManagerInterface): Response
    {
        $pdoAnlageData = self::getPdoConnectionAnlage();

        //Wenn der Import von Cron angestoßen wird.
        $cron = $request->query->get('cron');
        if ($cron == 1) {
            $time = time();
            $time -= $time % 900;
            $start = strtotime(date('Y-m-d H:i', $time - (4 * 3600)));
            $end = $time;

            //getDB-Connection
            $conn = $entityManagerInterface->getConnection();
            //get all Plants for Import via via Cron
            $readyToImport = self::getPlantsImportReady($conn);

            for ($i = 0; $i <= count($readyToImport)-1; $i++) {
                $plantId = $readyToImport[$i]['anlage_id'];
                $anlage = $anlagenRepo->findOneByIdAndJoin($plantId);
                $wetherStationId = $anlage->getWeatherStation()->getId();
                $modules = $anlage->getModules();
                $groups = $anlage->getGroups();
                $systemKey = $anlage->getCustomPlantId();
                $acGroups = self::getACGroups($conn, $plantId);
                $tempCorrParams['tempCellTypeAvg']  = (float)$anlage->temp_corr_cell_type_avg;
                $tempCorrParams['gamma']            = (float)$anlage->temp_corr_gamma;
                $tempCorrParams['a']                = (float)$anlage->temp_corr_a;
                $tempCorrParams['b']                = (float)$anlage->temp_corr_b;
                $tempCorrParams['deltaTcnd']        = (float)$anlage->temp_corr_delta_tcnd;

                $dcPNormPerInvereter = self::getDcPNormPerInvereter($conn, $groups->toArray(), $modules->toArray());
                $owner = $anlage->getEigner();
                $mcUser = $owner->getSettings()->getMcUser();
                $mcPassword = $owner->getSettings()->getMcPassword();
                $mcToken = $owner->getSettings()->getMcToken();

                #$bulkMeaserments = MeteoControlService::getSystemsKeyBulkMeaserments($mcUser, $mcPassword, $mcToken, $systemKey, $start, $end);
                print_r($dcPNormPerInvereter);

            }



        }
        exit;
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

                #$start = strtotime($importToolsModel->startDate->format('Y-m-d 00:00'));
                #$end = strtotime($importToolsModel->endDate->format('Y-m-d 23:59'));
                $importToolsModel->endDate->add(new \DateInterval('P1D'));
                $anlage = $anlagenRepo->findOneBy(['anlId' => $importToolsModel->anlage]);
                $wetherStationId = $anlage->getWeatherStation();
                $modules = $anlage->getModules();
                $groups = $anlage->getGroups();
                $dcPNormPerInvereter = self::getDcPNormPerInvereter($groups, $modules);
                $owner = $anlage->getEigner();


                $importToolsModel->path = (string)$anlage->getPathToImportScript();
                $importToolsModel->importType = (string)$form->get('importType')->getData();
                $importToolsModel->hasPpc = 0;
                // Start import
                if ($form->get('importType')->getData() == null) {
                    $output .= 'Please select what you like to import.<br>';
                    $break = 1;
                }

                $hasPpc = $anlage->getHasPPC();

                if ($hasPpc != 1 && (string)(string)$importToolsModel->importType == 'api-import-ppc') {
                    $output .= 'This plant has not PPC!<br>';
                    $break = 1;
                }

                if ($hasPpc == 1) {
                    $importToolsModel->hasPpc = 1;
                }

                if ($break == 0) {
                    if ($form->get('function')->getData() != null) {
                        switch ($form->get('function')->getData()) {
                            case 'api-import-data':
                                $output = '<h3>Import API Data:</h3>';
                                $job = 'Import API Data(' . $importToolsModel->importType . ') – from ' . $importToolsModel->startDate->format('Y-m-d 00:00') . ' until ' . $importToolsModel->endDate->format('Y-m-d 00:00');
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

        return $this->render('aaaaaa');
    }

}