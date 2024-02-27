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

class ImportService
{
    use ImportFunctionsTrait;
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly PVSystDatenRepository $pvSystRepo,
        private readonly AnlagenRepository $anlagenRepository,
        private readonly AnlageAvailabilityRepository $anlageAvailabilityRepo,
        private readonly FunctionsService $functions,
        private readonly EntityManagerInterface $em,
        private readonly AvailabilityService $availabilityService,
        private readonly MeteoControlService $meteoControlService,
        private readonly ManagerRegistry $doctrine
    )
    {
    }

    /**
     * @throws NonUniqueResultException
     * @throws \JsonException
     */
    public function prepareForImport(Anlage|int $anlage, $start, $end, string $importType = ""): void
    {
        //beginn collect params from plant
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneByIdAndJoin($anlage);
        }
        $plantId = $anlage->getAnlId();
        $vcomId = $anlage->getCustomPlantId();

        //get all vcom ids in plant
        $arrayVcomIds = explode(',', $vcomId);

        $conn = $this->doctrine->getConnection();

        $weather = $anlage->getWeatherStation();
        $weatherDbIdent = $weather->getDatabaseIdent();

        $modules = $anlage->getModules();
        $groups = $anlage->getGroups();
        $systemKey = $anlage->getCustomPlantId();

        #check if the plant use Stringboxes
        if ($anlage->getSettings()->getImportType() == 'withStringboxes') {
            $acGroups = $anlage->getAcGroups()->toArray();

            for ($i = 0; $i < count($acGroups); ++$i) {
                $acGroupsCleaned[$i]['importId'] = $acGroups[$i]->getImportId();
                $acGroupsCleaned[$i]['group_ac'] = $acGroups[$i]->getAcGroup();
            }
        }

        $anlagenTabelle = $anlage->getAnlIntnr();

        $isEastWest = $anlage->getIsOstWestAnlage();
        $timeZonePlant = $this->getNearestTimezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(), strtoupper($anlage->getCountry()));


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
        $useSensorsDataTable = $anlage->getSettings()->isUseSensorsData();
        $hasSensorsInBasics = $anlage->getSettings()->isSensorsInBasics();
        //end collect params from plans

        $bulkMeaserments = [];

        //get the Data from vcom
        $curl = curl_init();

        $bulkMeaserments = [];
        $basics = [];
        $inverters = [];
        $sensors = [];
        $stringBoxes = [];
        $numberOfPlants = count($arrayVcomIds);

        //get the Data from VCOM for all Plants are configured in the current plant
        for ($i = 0; $i < $numberOfPlants; ++$i) {
            $bulkMeaserments[$i] = $this->meteoControlService->getSystemsKeyBulkMeaserments($mcUser, $mcPassword, $mcToken, $arrayVcomIds[$i], $start, $end, "fifteen-minutes", $timeZonePlant, $curl);
        }
        curl_close($curl);
        $data_pv_ist = [];
        $data_pv_dcist = [];
        echo '<pre>';
        print_r($bulkMeaserments[0]['sensors']['2024-02-14T13:00:00-06:00']['511822']);
        echo '</pre>';
        //beginn collect all Data from all Plants
        if (count($bulkMeaserments) > 0) {
            for ($i = 0; $i < count($bulkMeaserments); ++$i) {
                for ($timestamp = $start; $timestamp <= $end; $timestamp += 900) {
                    $date = date('c', $timestamp);
                    if($i == 0){
                        $sensors[$date] = $bulkMeaserments[$i]['sensors'][$date];
                        $inverters[$date] = $bulkMeaserments[$i]['inverters'][$date];
                        $basics[$date] = $bulkMeaserments[$i]['basics'][$date];
                        if ($anlage->getSettings()->getImportType() == 'withStringboxes') {
                            $stringBoxes[$date] = $bulkMeaserments[$i]['stringboxes'][$date];
                        }
                        $basics[$date]["E_Z_EVU"] = $bulkMeaserments[$i]['basics'][$date]['E_Z_EVU'];
                        $basics[$date]["G_M".$i] = $bulkMeaserments[$i]['basics'][$date]['G_M0'];
                    }else{
                        $sensors[$date] = $sensors[$date] + $bulkMeaserments[$i]['sensors'][$date];
                        $inverters[$date] = $inverters[$date] + $bulkMeaserments[$i]['inverters'][$date];
                        $basics[$date] = $basics[$date] + $bulkMeaserments[$i]['basics'][$date];
                        if ($anlage->getSettings()->getImportType() == 'withStringboxes') {
                            $stringBoxes[$date] = $stringBoxes[$date] + $bulkMeaserments[$i]['stringboxes'][$date];
                        }
                        $basics[$date]["E_Z_EVU"] = $basics[$date]["E_Z_EVU"] + $bulkMeaserments[$i]['basics'][$date]['E_Z_EVU'];
                        $basics[$date]["G_M".$i] = $basics[$date]["G_M".$i] + $bulkMeaserments[$i]['basics'][$date]['G_M0'];
                    }
                }

            }
            //end collect all Data from all Plants

            //get all Sensors from Plant
            $anlageSensors = $anlage->getSensors();

            //beginn sort and seperate Data for writing into database
            for ($timestamp = $start; $timestamp <= $end; $timestamp += 900) {
                $stamp = date('Y-m-d H:i', $timestamp);
                $date = date('c', $timestamp);

                $eZEvu = $irrUpper = $irrLower = $tempAmbient = $tempPanel = $windSpeed = $irrHorizontal = null;
                $tempAnlageArray = $windAnlageArray = $irrAnlageArrayGMO = $irrAnlageArray = [];

                if (is_array($basics) && array_key_exists($date, $basics)) {
                    $tempGm = [];
                    for ($i = 0; $i < $numberOfPlants; ++$i) {
                        if($basics[$date]["G_M".$i] == ''){
                            $tempGm[] = 0.0;
                        }else{
                            $tempGm[] = (float)$basics[$date]["G_M".$i];
                        }

                    }

                    //Hier Mittelwert bilden
                    $irrAnlageGMO = $this->mittelwert($tempGm, true);   //

                    if($basics[$date]['E_Z_EVU'] > 0){
                        (float)$eZEvu = $basics[$date]['E_Z_EVU'];
                    }else{
                        (float)$eZEvu = 0.0;
                    }
                }

                //beginn get Sensors Data
                (int)$length = is_countable($anlageSensors) ? count($anlageSensors) : 0;
                if ((is_array($sensors) && array_key_exists($date, $sensors) && $length > 0) || $hasSensorsInBasics == 1) {
                    //if plant is use sensors datatable get data for the table
                    if($useSensorsDataTable){
                        $result = self::getSensorsDataFromVcomResponse((array)$anlageSensors->toArray(), (int)$length, (array)$sensors, (array)$basics, $stamp, $date, (string)$irrAnlageGMO);
                        //built array for sensordata
                        for ($j = 0; $j <= count($result[0])-1; $j++) {
                            $dataSensors[] = $result[0][$j];
                        }
                        unset($result);
                    }
                }

                $checkSensors = [];

                if($length > 0){
                    $checkSensors = self::checkSensors($anlageSensors->toArray(), (int)$length, (bool)$isEastWest, (array)$sensors, (array)$basics, $date);
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
                /*if plant use not sensors datatable store data into the weather table
                Diese Abfrage ist aktuell nicht wirklich relevant da in beiden Fällen das gleich geschieht.
                TODO: die entsprechenden Skripte wie berechnung expected anpassen(Daten kommen aus db__pv_sensors_data_...)
                */
                if(!$useSensorsDataTable){
                    $irrAnlage = json_encode($irrAnlageArray, JSON_THROW_ON_ERROR);
                    $tempAnlage = json_encode($tempAnlageArray, JSON_THROW_ON_ERROR);
                    $windAnlage = json_encode($windAnlageArray, JSON_THROW_ON_ERROR);
                }else{
                    //create emmpty anlage arrays to make shure import in pv_ist works
                    #$irrAnlageArray = [];
                    #$tempAnlageArray = [];
                    #$windAnlageArray = [];
                    $irrAnlage = json_encode($irrAnlageArray, JSON_THROW_ON_ERROR);
                    $tempAnlage = json_encode($tempAnlageArray, JSON_THROW_ON_ERROR);
                    $windAnlage = json_encode($windAnlageArray, JSON_THROW_ON_ERROR);
                }

                $data_pv_weather[] = [
                    'anl_intnr' => $weatherDbIdent,
                    'anl_id' => 0,
                    'stamp' => $stamp,
                    'at_avg' => ($tempAmbient != '') ? $tempAmbient : NULL,
                    'temp_ambient' => ($tempAmbient != '') ? $tempAmbient : NULL,
                    'pt_avg' => ($tempPanel != '') ? $tempPanel : NULL,
                    'temp_pannel' => ($tempPanel != '') ? $tempPanel : NULL,
                    'gi_avg' => ($irrLower != '') ? $irrLower : NULL,
                    'g_lower' => ($irrLower != '') ? $irrLower : NULL,
                    'gmod_avg' => ($irrUpper != '') ? $irrUpper : NULL,
                    'g_upper' => ($irrUpper != '') ? $irrUpper : NULL,
                    'g_horizontal' => ($irrHorizontal != '') ? $irrHorizontal : NULL,
                    'rso' => '0',
                    'gi' => '0',
                    'wind_speed' => ($windSpeed != '') ? $windSpeed : NULL,
                    'temp_cell_multi_irr' => NULL,
                    'temp_cell_corr' => NULL,
                    'ft_factor' => NULL,
                    'irr_flag' => NULL
                ];

                //end get Sensors Data

                //beginn Import different Types(plant have stringboxes or not)
                //Inverters only
                if ($anlage->getSettings()->getImportType() == 'standart') {
                    //Anzahl der Units eines Inverters
                    $invertersUnits = $anlage->getSettings()->getInvertersUnits();

                    //get Data from Inverters
                    $result = self::loadData($inverters, $date, $plantId, $stamp, $eZEvu, $irrAnlage, $tempAnlage, $windAnlage, $groups, $invertersUnits);

                    //built array for pvist
                    $sizeResult = count($result[0]) - 1;
                    for ($j = 0; $j <= $sizeResult; $j++) {
                        $data_pv_ist[] = $result[0][$j];
                    }
                    unset($result);
                }

                //with Stringboxes
                if ($anlage->getSettings()->getImportType() == 'withStringboxes') {
                    $stringBoxesTime = $stringBoxes[$date];

                    //Anzahl der Units einer Stringbox
                    $stringBoxUnits = $anlage->getSettings()->getStringboxesUnits();

                    //get Data from Inverters and Stringboxes
                    $result = self::loadDataWithStringboxes($stringBoxesTime, $acGroupsCleaned, $inverters, $date, $plantId, $stamp, $eZEvu, $irrAnlage, $tempAnlage, $windAnlage, $groups, $stringBoxUnits);

                    //built array for pvist
                    $sizeResult = count($result[0]) - 1;
                    for ($j = 0; $j <= $sizeResult; $j++) {
                        $data_pv_ist[] = $result[0][$j];
                    }

                    //built array for pvist_dc
                    $sizeResult = count($result[1]) - 1;
                    for ($j = 0; $j <= $sizeResult; $j++) {
                        $data_pv_dcist[] = $result[1][$j];
                    }

                    //built array for stringBoxUnits
                    $sizeResult = count($result[2]) - 1;
                    for ($j = 0; $j <= $sizeResult; $j++) {
                        $data_db_string_pv[] = $result[2][$j];
                    }

                    unset($result);
                }
                //end Import different Types(plant have stringboxes or not)

                //beginn Anlage hat PPC
                if ($anlage->getHasPPC()) {
                    $ppcs = $bulkMeaserments['ppcs'];

                    $anlagePpcs = $anlage->getPpcs()->toArray();
                    for ($i = 0; $i < count($anlagePpcs); ++$i) {
                        $anlagePpcsCleaned[$i]['vcomId'] = $anlagePpcs[$i]->getVcomId();
                    }

                    $result = self::getPpc($anlagePpcsCleaned, $ppcs, $date, $stamp, $plantId, $anlagenTabelle, $vcomId);
                    $sizeResult = count($result[0]) - 1;
                    for ($j = 0; $j <= $sizeResult; $j++) {
                        $data_ppc[] = $result[0][$j];
                    }
                    unset($result);
                }
                //end Anlage hat PPC
            }

            //beginn get Database Connections
            $DBDataConnection = $this->pdoService->getPdoPlant();
            $DBStbConnection = $this->pdoService->getPdoStringBoxes();
            //end get Database Connections

            //beginn write Data in the tables
            switch ($importType) {
                case 'api-import-weather':
                    if($useSensorsDataTable && $length > 0 && is_array($dataSensors) && count($dataSensors) > 0) {
                        $tableName = "db__pv_sensors_data_$anlagenTabelle";
                        self::insertData($tableName, $dataSensors, $DBDataConnection);
                    }
                    if(is_array($data_pv_weather) && count($data_pv_weather) > 0){
                        $tableName = "db__pv_ws_$weatherDbIdent";
                        self::insertData($tableName, $data_pv_weather, $DBDataConnection);
                    }
                    break;
                case 'api-import-ppc':
                    if (is_array($data_ppc) && count($data_ppc) > 0) {
                        $tableName = "db__pv_ppc_$anlagenTabelle";
                        self::insertData($tableName, $data_ppc, $DBDataConnection);
                    }
                    break;
                case 'api-import-pvist':
                    if ($anlage->getSettings()->getImportType() == 'withStringboxes' && is_array($data_pv_dcist) && count($data_pv_dcist) > 0) {
                        $tableName = "db__pv_dcist_$anlagenTabelle";
                        self::insertData($tableName, $data_pv_dcist, $DBDataConnection);

                        $tableName = "db__string_pv_$anlagenTabelle";
                        self::insertData($tableName, $data_db_string_pv, $DBStbConnection);
                    }

                    if(is_array($data_pv_ist) && count($data_pv_ist) > 0) {
                        $tableName = "db__pv_ist_$anlagenTabelle";
                        self::insertData($tableName, $data_pv_ist, $DBDataConnection);
                    }
                    break;
                default:
                    if($useSensorsDataTable == 1 && $length > 0 && is_array($dataSensors) && count($dataSensors) > 0) {
                        $tableName = "db__pv_sensors_data_$anlagenTabelle";
                        self::insertData($tableName, $dataSensors, $DBDataConnection);
                    }
                    if(is_array($data_pv_weather) && count($data_pv_weather) > 0){
                        $tableName = "db__pv_ws_$anlagenTabelle";
                        self::insertData($tableName, $data_pv_weather, $DBDataConnection);
                    }

                    if ($anlage->getHasPPC() && is_array($data_ppc) && count($data_ppc) > 0) {
                        $tableName = "db__pv_ppc_$anlagenTabelle";
                        self::insertData($tableName, $data_ppc, $DBDataConnection);
                    }

                    if ($anlage->getSettings()->getImportType() == 'withStringboxes' && is_array($data_pv_dcist) && count($data_pv_dcist) > 0) {
                        $tableName = "db__pv_dcist_$anlagenTabelle";
                        self::insertData($tableName, $data_pv_dcist, $DBDataConnection);

                        $tableName = "db__string_pv_$anlagenTabelle";
                        self::insertData($tableName, $data_db_string_pv, $DBStbConnection);
                    }

                    if(is_array($data_pv_ist) && count($data_pv_ist) > 0) {
                        $tableName = "db__pv_ist_$anlagenTabelle";
                        self::insertData($tableName, $data_pv_ist, $DBDataConnection);
                    }
                    break;
            }
            //end write Data in the tables
        }
    }

}