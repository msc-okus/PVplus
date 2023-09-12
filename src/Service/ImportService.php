<?php

namespace App\Service;

use App\Entity\Anlage;

use App\Helper\G4NTrait;
use App\Helper\ImportFunctionsTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use App\Repository\PVSystDatenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\Serializer\SerializerInterface;
class ImportService
{
    use ImportFunctionsTrait;
    use G4NTrait;

    public function __construct(
private GetPdoService $getPdoService,
        private PVSystDatenRepository $pvSystRepo,
        private AnlagenRepository $anlagenRepository,
        private AnlageAvailabilityRepository $anlageAvailabilityRepo,
        private FunctionsService $functions,
        private EntityManagerInterface $em,
        private AvailabilityService $availabilityService,
        private MeteoControlService $meteoControlService,
        private ManagerRegistry $doctrine,
        private SerializerInterface $serializer
    )
    {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function prepareForImport(Anlage|int $anlage, $start, $end, string $importType = ""): void
    {
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneByIdAndJoin($anlage);
        }
        $plantId = $anlage->getAnlId();

        $conn = $this->doctrine->getConnection();

        $weather = $anlage->getWeatherStation();
        $weatherDbIdent = $weather->getDatabaseIdent();

        $modules = $anlage->getModules();
        $groups = $anlage->getGroups();
        $systemKey = $anlage->getCustomPlantId();

        if ($anlage->getSettings()->getImportType() == 'withStringboxes') {
            $acGroups = $anlage->getAcGroups()->toArray();

            for ($i = 0; $i < count($acGroups); ++$i) {
                $acGroupsCleaned[$i]['importId'] = $acGroups[$i]->getImportId();
            }
        }

        $anlagenTabelle = $anlage->getAnlIntnr();

        $isEastWest = $anlage->getIsOstWestAnlage();
        $tempCorrParams['tempCellTypeAvg'] = (float)$anlage->getTempCorrCellTypeAvg();
        $tempCorrParams['gamma'] = (float)$anlage->getTempCorrGamma();
        $tempCorrParams['a'] = (float)$anlage->getTempCorrA();
        $tempCorrParams['b'] = (float)$anlage->getTempCorrB();
        $tempCorrParams['deltaTcnd'] = (float)$anlage->getTempCorrDeltaTCnd();

        $dcPNormPerInvereter = self::getDcPNormPerInvereter($conn, $groups->toArray(), $modules->toArray());

        $owner = $anlage->getEigner();
        $mcUser = $owner->getSettings()->getMcUser();
        $mcPassword = $owner->getSettings()->getMcPassword();
        $mcToken = $owner->getSettings()->getMcToken();

        //get the Data from vcom
        $bulkMeaserments = $this->meteoControlService->getSystemsKeyBulkMeaserments($mcUser, $mcPassword, $mcToken, $systemKey, $start, $end);


        $data_pv_ist = [];
        $data_pv_dcist = [];
        if ($bulkMeaserments) {
            $basics = $bulkMeaserments['basics'];
            $inverters = $bulkMeaserments['inverters'];
            $sensors = $bulkMeaserments['sensors'];

            $anlageSensors = $this->serializer->normalize($anlage->getSensors(), null);

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

                $irrAnlage = json_encode($irrAnlageArray);
                $tempAnlage = json_encode($tempAnlageArray);
                $windAnlage = json_encode($windAnlageArray);

                //Import different Types
                if ($anlage->getSettings()->getImportType() == 'standart') {
                    //Anzahl der Units in eines Inverters
                    $invertersUnits = $anlage->getSettings()->getInvertersUnits();

                    $result = self::loadData($inverters, $date, $plantId, $stamp, $eZEvu, $irrAnlage, $tempAnlage, $windAnlage, $groups, $invertersUnits);

                    //built array for pvist
                    for ($j = 0; $j <= count($result[0]) - 1; $j++) {
                        $data_pv_ist[] = $result[0][$j];
                    }
                }

                if ($anlage->getSettings()->getImportType() == 'withStringboxes') {
                    $stringBoxes = $bulkMeaserments['stringboxes'];
                    $stringBoxesTime = $stringBoxes[$date];

                    //Anzahl der Units in einer Stringbox
                    $stringBoxUnits = $anlage->getSettings()->getStringboxesUnits();

                    $result = self::loadDataWithStringboxes($stringBoxesTime, $acGroupsCleaned, $inverters, $date, $plantId, $stamp, $eZEvu, $irrAnlage, $tempAnlage, $windAnlage, $groups, $stringBoxUnits);

                    //built array for pvist
                    for ($j = 0; $j <= count($result[0]) - 1; $j++) {
                        $data_pv_ist[] = $result[0][$j];
                    }
                    //built array for pvist_dc
                    for ($j = 0; $j <= count($result[1]) - 1; $j++) {
                        $data_pv_dcist[] = $result[1][$j];
                    }
                }

                unset($result);
                //Anlage hatPPc
                if ($anlage->getHasPPC()) {
                    $ppcs = $bulkMeaserments['ppcs'];

                    $anlagePpcs = $anlage->getPpcs()->toArray();
                    for ($i = 0; $i < count($anlagePpcs); ++$i) {
                        $anlagePpcsCleaned[$i]['vcomId'] = $anlagePpcs[$i]->getVcomId();
                    }

                    $result = self::getPpc($anlagePpcsCleaned, $ppcs, $date, $stamp, $plantId, $anlagenTabelle);

                    for ($j = 0; $j <= count($result[0]) - 1; $j++) {
                        $data_ppc[] = $result[0][$j];
                    }
                }
            }
        }

        //write Data in the tables

        switch ($importType) {
            case 'api-import-weather':
                    $tableName = "db__pv_ws_$weatherDbIdent";
                self::insertData($tableName, $data_pv_weather, $this->host, $this->userPlant, $this->passwordPlant);
                break;
            case 'api-import-ppc':
                $tableName = "db__pv_ppc_$anlagenTabelle";
                self::insertData($tableName, $data_ppc, $this->host, $this->userPlant, $this->passwordPlant);
                break;
            case 'api-import-pvist':
                if ($anlage->getSettings()->getImportType() == 'withStringboxes') {
                    $tableName = "db__pv_dcist_$anlagenTabelle";
                    self::insertData($tableName, $data_pv_dcist, $this->host, $this->userPlant, $this->passwordPlant);
                }

                $tableName = "db__pv_ist_$anlagenTabelle";
                self::insertData($tableName, $data_pv_ist, $this->host, $this->userPlant, $this->passwordPlant);
                break;
            default:
                $tableName = "db__pv_ws_$weatherDbIdent";
                self::insertData($tableName, $data_pv_weather, $this->host, $this->userPlant, $this->passwordPlant);

                if ($anlage->getHasPPC()) {
                    $tableName = "db__pv_ppc_$anlagenTabelle";
                    self::insertData($tableName, $data_ppc, $this->host, $this->userPlant, $this->passwordPlant);
                }

                if ($anlage->getSettings()->getImportType() == 'withStringboxes') {
                    $tableName = "db__pv_dcist_$anlagenTabelle";
                    self::insertData($tableName, $data_pv_dcist, $this->host, $this->userPlant, $this->passwordPlant);
                }

                $tableName = "db__pv_ist_$anlagenTabelle";
                self::insertData($tableName, $data_pv_ist, $this->host, $this->userPlant, $this->passwordPlant);
                break;
        }
    }
}