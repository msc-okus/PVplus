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
        $pdoAnlageData = self::getPdoConnectionData();
        $hasStringboxes = 0;

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

            sleep(5);
            for ($i = 0; $i <= count($readyToImport)-1; $i++) {
                $plantId = $readyToImport[$i]['anlage_id'];
                $anlage = $anlagenRepo->findOneByIdAndJoin($plantId);
                $weather    = $anlage->getWeatherStation($anlage->getWeatherStation()->getId());
                $weatherDbIdent = $weather->getDatabaseIdent();

                $modules = $anlage->getModules();
                $groups = $anlage->getGroups();
                $systemKey = $anlage->getCustomPlantId();
                $acGroups = self::getACGroups($conn, $plantId);
                $hasPpc = $anlage->getHasPPC();

                $anlagenTabelle = $anlage->getAnlIntnr();

                $isEastWest = $anlage->getIsOstWestAnlage();
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

                $bulkMeaserments = MeteoControlService::getSystemsKeyBulkMeaserments($mcUser, $mcPassword, $mcToken, $systemKey, $start, $end);

                $data_pv_ist = [];
                $data_pv_dcist = [];
                if ($bulkMeaserments) {
                    $basics = $bulkMeaserments['basics'];
                    $inverters = $bulkMeaserments['inverters'];
                    $sensors = $bulkMeaserments['sensors'];

                    $hasStringboxes = 0;
                    if(is_array($bulkMeaserments['stringboxes'])) {
                        $stringBoxes = $bulkMeaserments['stringboxes'];
                        $hasStringboxes = 1;
                    }
                    $anlageSensors = self::getAnlageSensors($conn, $plantId);

                    for ($timestamp = $start; $timestamp <= $end; $timestamp += 900) {
                        $stamp = date('Y-m-d H:i', $timestamp);
                        $date = date('c', $timestamp);

                        $eZEvu = $irrUpper = $irrLower = $tempAmbient = $tempPanel = $windSpeed = $irrHorizontal = null;
                        $tempAnlageArray = $windAnlageArray = $irrAnlageArrayGMO = $irrAnlageArray = [];

                        if (array_key_exists($date, $basics)) {
                            $irrAnlageArrayGMO['G_M0'] = $basics[$date]['G_M0'] > 0 ? round($basics[$date]['G_M0'], 4) : 0;   //
                            $eZEvu = round($basics[$date]['E_Z_EVU'], 0);
                        }

                        if (is_array($sensors) && array_key_exists($date, $sensors)) {
                            $length = count($anlageSensors);

                            $checkSensors = self::checkSensors($anlageSensors, $length, (bool)$isEastWest, $sensors, $date);


                            $irrAnlageArray = array_merge_recursive($irrAnlageArrayGMO, $checkSensors[0]['irrHorizontalAnlage'], $checkSensors[0]['irrLowerAnlage'], $checkSensors[0]['irrUpperAnlage']);
                            $irrHorizontal = $checkSensors[0]['irrHorizontal'];
                            $irrLower = $checkSensors[0]['irrLower'];
                            $irrUpper = $checkSensors[0]['irrUpper'];

                            $tempPanel = $checkSensors[1]['tempPanel'];

                            $tempAmbient = $checkSensors[1]['tempAmbient'];

                            $tempAnlageArray = $checkSensors[1]['anlageTemp'];

                            $wSEwd = $checkSensors[1]['windDirection'];

                            $windSpeed = $checkSensors[1]['windSpeed'];

                            $windAnlageArray = $checkSensors[1]['anlageWind'];

                        }
                        $data_pv_weather[] = [
                            'anl_intnr' => $weatherDbIdent,
                            'anl_id' => 0,
                            'stamp' => $stamp,
                            'at_avg' => $tempAmbient,
                            'temp_ambient' => $tempAmbient,
                            'pt_avg' => $tempPanel,
                            'temp_pannel' => $tempPanel,
                            'gi_avg' => $irrLower,
                            'g_lower' => $irrLower,
                            'gmod_avg' => $irrUpper,
                            'g_upper' => $irrUpper,
                            'g_horizontal' => $irrHorizontal,
                            'rso' => '0',
                            'gi' => '0',
                            'wind_speed' => $windSpeed,
                            'temp_cell_multi_irr' => NULL,
                            'temp_cell_corr' => NULL,
                            'ft_factor' => NULL,
                            'irr_flag' => NULL
                        ];

                        $irrAnlage  = json_encode($irrAnlageArray);
                        $tempAnlage = json_encode($tempAnlageArray);
                        $windAnlage = json_encode($windAnlageArray);

                        if($hasStringboxes == 1){
                            $stringBoxesTime = $stringBoxes[$date];

                            //Anzahl der Units in einer Stringbox
                            $stringBoxUnits = $anlage->getSettings()->getStringboxesUnits();

                            $result = self::loadDataWithStringboxes($stringBoxesTime, $acGroups, $inverters, $date, $plantId, $stamp, $eZEvu, $irrAnlage, $tempAnlage, $windAnlage, $groups, $stringBoxUnits);
                            //built array for pvist
                            for ($j = 0; $j <= count($result[0])-1; $j++) {
                                $data_pv_ist[] = $result[0][$j];
                            }
                            //built array for pvist_dc
                            for ($j = 0; $j <= count($result[1])-1; $j++) {
                                $data_pv_dcist[] = $result[1][$j];
                            }
                        }else{
                            $result = self::loadData($inverters, $date, $plantId, $stamp, $eZEvu, $irrAnlage, $tempAnlage, $windAnlage, $groups);
                            //built array for pvist
                            for ($j = 0; $j <= count($result[0])-1; $j++) {
                                $data_pv_ist[] = $result[0][$j];
                            }
                        }

                        unset($result);
                        //Anlage hat eigene DC-Ist Tabelle(Stringboxes)
                        if($hasPpc){
                            $ppcs = $bulkMeaserments['ppcs'];
                            $idPpc = $anlage->getSettings()->getIdPpc();
                            $result = self::getPpc($idPpc, $ppcs, $date, $stamp, $plantId, $anlagenTabelle);
                            for ($j = 0; $j <= count($result[0])-1; $j++) {
                                $data_ppc[] = $result[0][$j];
                            }
                        }
                    }


                }

                if($hasPpc){
                    $tableName = "db__pv_ppc_$anlagenTabelle".'_copy';
                    self::insertData($tableName, $data_ppc);
                    echo "<br>$tableName <br>";
                }

                if($hasStringboxes == 1){
                    $tableName = "db__pv_dcist_$anlagenTabelle".'_copy';
                    self::insertData($tableName, $data_pv_dcist);
                    echo "<br>$tableName <br>";
                }

                $tableName = "db__pv_ws_$weatherDbIdent".'_copy';
                self::insertData($tableName, $data_pv_weather);
                echo "$tableName <br>";
                $tableName = "db__pv_ist_$anlagenTabelle".'_copy';
                self::insertData($tableName, $data_pv_ist);
                echo "$tableName <br>";
                #print_r($anlageSensors);
                echo "<br>$plantId<pre>";

                echo 'PPC';
                #print_r($data_ppc);
                echo '</pre>';
                sleep(5);
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