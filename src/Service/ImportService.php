<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Helper\ImportFunctionsTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use App\Repository\ApiConfigRepository;
use App\Repository\PVSystDatenRepository;
use App\Service;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class ImportService
{
    use ImportFunctionsTrait;
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly PVSystDatenRepository        $pvSystRepo,
        private readonly AnlagenRepository            $anlagenRepository,
        private readonly AnlageAvailabilityRepository $anlageAvailabilityRepo,
        private readonly ApiConfigRepository          $apiConfigRepository,
        private readonly FunctionsService             $functions,
        private readonly EntityManagerInterface       $em,
        private readonly AvailabilityService          $availabilityService,
        private readonly MeteoControlService          $meteoControlService,
        private readonly ManagerRegistry              $doctrine,
        private readonly WeatherServiceNew            $weatherService,
        private readonly externalApisService          $externalApis,
        #private readonly DayAheadForecastDEKService $dayAheadForecastDEKService,
        #private readonly Forecast\DayAheadForecastMALService $aheadForecastMALService
    )
    {
        $thisApi = $this->externalApis;
    }

    /**
     * @throws NonUniqueResultException
     * @throws \JsonException
     * @throws \Exception
     */
    public function prepareForImport(Anlage|int $anlage, $start, $end, string $importType = "", $fromCron = false): void
    {

        //beginn collect params from plant
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneByIdAndJoin($anlage);
        }
        $plantId = $anlage->getAnlId();
        $vcomId = $anlage->getCustomPlantId();

        //get all vcom ids in plant
        $arrayVcomIds = explode(',', $vcomId);
        $data_ppc = $data_pv_ist = $data_db_string_pv = $data_pv_dcist = $dataSensors = $data_pv_weather = [];

        $weather = $anlage->getWeatherStation();
        $weatherDbIdent = $weather->getDatabaseIdent();

        $groups = $anlage->getGroups();

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
        $timeZonePlant = $anlage->getNearestTimezone();
        $dateTimeZoneOfPlant = new \DateTimeZone($timeZonePlant);

        $apiconfigId = $anlage->getSettings()->getApiConfig();
        $apiconfig = $this->apiConfigRepository->findOneById($apiconfigId);

        $apiUser = $apiconfig->apiUser;
        $apiPassword = $this->unHashData($apiconfig->apiPassword);
        $apiToken = $apiconfig->apiToken;
        $apiType = $apiconfig->apiType;

        if($apiType == 'vcom'){
            $baseUrl = 'https://api.meteocontrol.de/v2/login';
            $postFileds = "grant_type=password&client_id=vcom-api&client_secret=AYB=~9_f-BvNoLt8+x=3maCq)>/?@Nom&username=$apiUser&password=$apiPassword";
            $headerFields = [
                "content-type: application/x-www-form-urlencoded",
                "X-API-KEY: ". $apiToken,
            ];
        }

        $apiAccessToken = $this->externalApis->getAccessToken($baseUrl, $apiToken, $postFileds, $headerFields);

        $useSensorsDataTable = $anlage->getSettings()->isUseSensorsData();
        $hasSensorsInBasics = $anlage->getSettings()->isSensorsInBasics();
        $hasSensorsFromSatelite = $anlage->getSettings()->isSensorsFromSatelite();
        $input_gb = (float)$anlage->getAnlGeoLat();       // Geo Breite / Latitute
        $input_gl = (float)$anlage->getAnlGeoLon();       // Geo Länge / Longitude
        $input_neigung = (float)$anlage->getModNeigung();       // Neigung
        $input_azimut = (float)$anlage->getModAzimut();       // Azimut

        //get all Sensors from Plant
        $anlageSensors = $anlage->getSensors();
        $isEastWest = $anlage->getIsOstWestAnlage();

        $dataDelay = $anlage->getSettings()->getDataDelay()*3600;
        //end collect params from plant

        $bulkMeaserments = [];
        $basics = [];
        $inverters = [];
        $sensors = [];
        $stringBoxes = [];
        $numberOfPlants = count($arrayVcomIds);

        $start = $start - $dataDelay;
        $end = $end - $dataDelay;

        $from = date('Y-m-d H:i', $start);
        $to = date('Y-m-d H:i', $end);

        //If Data comes from Satelite
        if($hasSensorsFromSatelite == 1){
            $importType = 'api-import-weather';
            $meteo_data = new Service\Forecast\APIOpenMeteoService($input_gl, $input_gb, $input_neigung, $input_azimut);
            $meteo_array = $meteo_data->make_sortable_data();
            $length = is_countable($anlageSensors) ? count($anlageSensors) : 0;
            $sensors = [];
            if ((is_countable($meteo_array) ? count($meteo_array) : 0) > 1) {
                foreach ($meteo_array as $interarray) {
                    foreach ($interarray['minute'] as $key => $value) {
                        $stamp = strtotime($key);

                        if($stamp <= $end && $stamp >= $start){
                            $isDay = $anlage->isDay($stamp);
                            $date = date_create_immutable($key, $dateTimeZoneOfPlant)->format('c');

                            $irrtemp = [];
                            $temperaturtemp = [];
                            $wdstemp = [];
                            for ($j = 1; $j <= count($interarray['minute'][$key]); $j++) {
                                $irrtemp[] = $interarray['minute'][$key][$j]['gti'];
                                $temperaturtemp[] = $interarray['minute'][$key][$j]['tmp'];
                                $wdstemp[] = $interarray['minute'][$key][$j]['wds'];

                            }
                            $irr = round($this->mittelwert($irrtemp), 3);
                            $tempAmbient = round($this->mittelwert($temperaturtemp), 3);
                            $windSpeed = round($this->mittelwert($wdstemp), 3);
                            $basics[$date]['date'] = $date;
                            $basics[$date]['stamp'] = $key;
                            $basics[$date]['isDay'] = $isDay;
                            if($isEastWest) {
                                $basics[$date]["GM_0_E"] = $irr;
                                $basics[$date]["GM_0_W"] = $irr;
                            }else{
                                $basics[$date]["GM_0"] = $irr;
                            }

                            $basics[$date]["TA_0"] = $tempAmbient;
                            $basics[$date]["WS_0"] = $windSpeed;
                            unset($irrtemp);
                            unset($temperaturtemp);
                            unset($wdstemp);
                        }
                    }
                }
            }
            //create Data Arrays and save them to DB
            foreach ($basics  as $key => $value) {
                if($isEastWest) {
                    $gMo = $value["GM_0_E"];
                }else{
                    $gMo = $value["GM_0"];
                }
                $result = self::getSensorsDataFromVcomResponse((array)$anlageSensors->toArray(), $length, $sensors, $basics, $value['stamp'], $key, (string)$gMo, $value['isDay']);
                //built array for sensordata
                for ($j = 0; $j <= count($result[0])-1; $j++) {
                    $dataSensors[] = $result[0][$j];
                }
                unset($result);
                $tempAmbient = $value["TA_0"];
                $tempPanel = '';
                $windSpeed = $value["WS_0"];
                if($isEastWest) {
                    $irrUpper = $value["GM_0_E"];
                    $irrLower = $value["GM_0_W"];
                }else{
                    $irrUpper = $value["GM_0"];
                    $irrLower = '';
                }
                $irrHorizontal = '';

                $data_pv_weather[] = [
                    'anl_intnr' => $weatherDbIdent,
                    'anl_id' => 0,
                    'stamp' => $value['stamp'],
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
            }

            $DBDataConnection = $this->pdoService->getPdoPlant();
            if($useSensorsDataTable == 1 && is_array($dataSensors) && count($dataSensors) > 0) {
                $tableName = "db__pv_sensors_data_$anlagenTabelle";
                self::insertData($tableName, $dataSensors, $DBDataConnection);
            }

            if(is_array($data_pv_weather) && count($data_pv_weather) > 0){
                $tableName = "db__pv_ws_$weatherDbIdent";
                self::insertData($tableName, $data_pv_weather, $DBDataConnection);
            }
        }

        $from = urlencode(date('c', (int)$start - 900)); // minus 14 Minute, API liefert seit mitte April wenn ich Daten für 5:00 Uhr abfrage erst daten ab 5:15, wenn ich 4:46 abfrage bekomme ich die Daten von 5:00
        $to = urlencode(date('c', (int)$end));


        $headerFields = [
            "X-API-KEY: ". $apiToken,
            "Authorization: Bearer $apiAccessToken"
        ];

        //get the Data from VCOM for all Plants are configured in the current plant

        for ($i = 0; $i < $numberOfPlants; ++$i) {
            $url = "https://api.meteocontrol.de/v2/systems/$arrayVcomIds[$i]/bulk/measurements?from=$from&to=$to&resolution=fifteen-minutes";
            $tempBulk = $this->externalApis->getData($url, $headerFields);
            if ($tempBulk !== false) $bulkMeaserments[$i] = $tempBulk;
        }

        //beginn collect all Data from all Plants
        $numberOfBulkMeaserments = count($bulkMeaserments);
        if ($numberOfBulkMeaserments > 0) {
            date_default_timezone_set($timeZonePlant);
            for ($i = 0; $i < $numberOfBulkMeaserments; ++$i) {
                for ($timestamp = $start; $timestamp <= $end; $timestamp += 900) {

                    $stamp = date('Y-m-d H:i:s', $timestamp);
                    $date = date_create_immutable($stamp, $dateTimeZoneOfPlant)->format('c');

                    if (array_key_exists($i, $bulkMeaserments)) {
                        if (array_key_exists('basics', $bulkMeaserments[$i])) {
                            if ($i === 0) {
                                $sensors[$date] = is_array($bulkMeaserments[$i]['sensors']) && array_key_exists($date, $bulkMeaserments[$i]['sensors']) ? $bulkMeaserments[$i]['sensors'][$date] : [];
                                $inverters[$date] = is_array($bulkMeaserments[$i]['inverters']) && array_key_exists($date, $bulkMeaserments[$i]['inverters']) ? $bulkMeaserments[$i]['inverters'][$date] : [];
                                $basics[$date] = is_array($bulkMeaserments[$i]['basics']) && array_key_exists($date, $bulkMeaserments[$i]['basics']) ? $bulkMeaserments[$i]['basics'][$date] : [];
                                if ($anlage->getSettings()->getImportType() == 'withStringboxes') {
                                    $stringBoxes[$date] = is_array($bulkMeaserments[$i]['stringboxes']) && array_key_exists($date, $bulkMeaserments[$i]['stringboxes']) ? $bulkMeaserments[$i]['stringboxes'][$date] : [];
                                }
                                $basics[$date]["E_Z_EVU"] = is_array($bulkMeaserments[$i]['basics']) && array_key_exists($date, $bulkMeaserments[$i]['basics']) ? $bulkMeaserments[$i]['basics'][$date]['E_Z_EVU'] : [];
                                $basics[$date]["G_M" . $i] = is_array($bulkMeaserments[$i]['basics']) && array_key_exists($date, $bulkMeaserments[$i]['basics']) ? $bulkMeaserments[$i]['basics'][$date]['G_M0'] : [];
                            } else {
                                $sensors[$date] = is_array($bulkMeaserments[$i]['sensors']) && array_key_exists($date, $bulkMeaserments[$i]['sensors']) ? $sensors[$date] + $bulkMeaserments[$i]['sensors'][$date] : [];
                                $inverters[$date] = is_array($bulkMeaserments[$i]['inverters']) && array_key_exists($date, $bulkMeaserments[$i]['inverters']) ? $inverters[$date] + $bulkMeaserments[$i]['inverters'][$date] : [];
                                $basics[$date] = is_array($bulkMeaserments[$i]['basics']) && array_key_exists($date, $bulkMeaserments[$i]['basics']) ? $basics[$date] + $bulkMeaserments[$i]['basics'][$date] : [];
                                if ($anlage->getSettings()->getImportType() == 'withStringboxes') {
                                    $stringBoxes[$date] = is_array($bulkMeaserments[$i]['stringboxes']) && array_key_exists($date, $bulkMeaserments[$i]['stringboxes']) ? $stringBoxes[$date] + $bulkMeaserments[$i]['stringboxes'][$date] : [];
                                }
                                $basics[$date]["E_Z_EVU"] = is_array($bulkMeaserments[$i]['basics']) && array_key_exists($date, $bulkMeaserments[$i]['basics']) ? $basics[$date]["E_Z_EVU"] + $bulkMeaserments[$i]['basics'][$date]['E_Z_EVU'] : [];
                                $basics[$date]["G_M" . $i] = is_array($bulkMeaserments[$i]['basics']) && array_key_exists($date, $bulkMeaserments[$i]['basics']) ? $basics[$date]["G_M" . $i] + $bulkMeaserments[$i]['basics'][$date]['G_M0'] : [];
                            }
                        }
                    }
                }
            }
            //end collect all Data from all Plants

            //beginn sort and seperate Data for writing into database
            for ($timestamp = $start; $timestamp <= $end; $timestamp += 900) {
                $stamp = date('Y-m-d H:i', $timestamp);
                $date = date_create_immutable($stamp, $dateTimeZoneOfPlant)->format('c');

                $irrUpper = $irrLower = $tempAmbient = $tempPanel = $windSpeed = $irrHorizontal = null;
                $eZEvu = 0.0;

                $tempAnlageArray = $windAnlageArray = $irrAnlageArrayGMO = $irrAnlageArray = [];

                if (is_array($basics) && array_key_exists($date, $basics)) {
                    $tempGm = [];
                    for ($i = 0; $i < $numberOfPlants; ++$i) {
                        if ($basics[$date]["G_M".$i] == ''){
                            $tempGm[] = 0.0;
                        } else {
                            $tempGm[] = (float)$basics[$date]["G_M".$i];
                        }
                    }

                    //Hier Mittelwert bilden
                    $irrAnlageGMO = $this->mittelwert($tempGm, true);   //

                    if ($basics[$date]['E_Z_EVU'] > 0){
                        $eZEvu = (float)$basics[$date]['E_Z_EVU'];
                    }
                }

                $isDay = $anlage->isDay($timestamp);
                if($timeZonePlant == 'Asia/Almaty' || $timeZonePlant == 'Asia/Qostanay'){
                    $stamp = date('Y-m-d H:i', $timestamp-3600);
                }

                //beginn get Sensors Data
                $length = is_countable($anlageSensors) ? count($anlageSensors) : 0;

                if ((is_array($sensors) && array_key_exists($date, $sensors) && $length > 0) || $hasSensorsInBasics == 1) {
                    //if plant is use sensors datatable get data for the table
                    if ($useSensorsDataTable){
                        $result = self::getSensorsDataFromVcomResponse((array)$anlageSensors->toArray(), $length, $sensors, $basics, $stamp, $date, (string)$irrAnlageGMO, $isDay);
                        //built array for sensordata
                        for ($j = 0; $j <= count($result[0])-1; $j++) {
                            $dataSensors[] = $result[0][$j];
                        }
                        unset($result);
                    }
                }

                $checkSensors = [];

                if ($length > 0 && $hasSensorsFromSatelite != 1){
                    $checkSensors = self::checkSensors($anlageSensors->toArray(), $length, $isEastWest, $sensors, $basics, $date);
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
                if (!$useSensorsDataTable){
                    $irrAnlage = json_encode($irrAnlageArray, JSON_THROW_ON_ERROR);
                    $tempAnlage = json_encode($tempAnlageArray, JSON_THROW_ON_ERROR);
                    $windAnlage = json_encode($windAnlageArray, JSON_THROW_ON_ERROR);
                } else {
                    //create emmpty anlage arrays to make shure import in pv_ist works
                    #$irrAnlageArray = [];
                    #$tempAnlageArray = [];
                    #$windAnlageArray = [];
                    $irrAnlage = json_encode($irrAnlageArray, JSON_THROW_ON_ERROR);
                    $tempAnlage = json_encode($tempAnlageArray, JSON_THROW_ON_ERROR);
                    $windAnlage = json_encode($windAnlageArray, JSON_THROW_ON_ERROR);
                }

                if (!$isDay){
                    $irrLower = 0;
                    $irrUpper = 0;
                    $irrHorizontal = 0;
                    $irrAnlage = 0;
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
                if ($anlage->getSettings()->getImportType() == 'withStringboxes' && array_key_exists($date, $stringBoxes)) {
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
                if ($anlage->getHasPPC() && array_key_exists('ppcs', $bulkMeaserments)) {
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

            date_default_timezone_set('Europe/Berlin');

            //beginn get Database Connections
            $DBDataConnection = $this->pdoService->getPdoPlant();
            $DBStbConnection = $this->pdoService->getPdoStringBoxes();
            //end get Database Connections

            //beginn write Data in the tables
            switch ($importType) {
                case 'api-import-weather':
                    if($useSensorsDataTable && $length > 0 && is_array($dataSensors) && count($dataSensors) > 0 && $importType != 'ftpPush') {
                        $tableName = "db__pv_sensors_data_$anlagenTabelle";
                        self::insertData($tableName, $dataSensors, $DBDataConnection);
                    }
                    if(is_array($data_pv_weather) && count($data_pv_weather) > 0 && $importType != 'ftpPush'){
                        $tableName = "db__pv_ws_$weatherDbIdent";
                        self::insertData($tableName, $data_pv_weather, $DBDataConnection);
                    }
                    break;
                case 'api-import-ppc':
                    if (is_array($data_ppc) && count($data_ppc) > 0 && $importType != 'ftpPush') {
                        $tableName = "db__pv_ppc_$anlagenTabelle";
                        self::insertData($tableName, $data_ppc, $DBDataConnection);
                    }
                    break;
                case 'api-import-pvist':
                    if ($anlage->getSettings()->getImportType() == 'withStringboxes' && is_array($data_pv_dcist) && count($data_pv_dcist) > 0 && $importType != 'ftpPush') {
                        $tableName = "db__pv_dcist_$anlagenTabelle";
                        self::insertData($tableName, $data_pv_dcist, $DBDataConnection);

                        $tableName = "db__string_pv_$anlagenTabelle";
                        self::insertData($tableName, $data_db_string_pv, $DBStbConnection);
                    }

                    if(is_array($data_pv_ist) && count($data_pv_ist) > 0 && $importType != 'ftpPush') {
                        $tableName = "db__pv_ist_$anlagenTabelle";
                        self::insertData($tableName, $data_pv_ist, $DBDataConnection);
                    }
                    break;
                default:
                    if($useSensorsDataTable == 1 && $length > 0 && is_array($dataSensors) && count($dataSensors) > 0 && $importType != 'ftpPush') {
                        $tableName = "db__pv_sensors_data_$anlagenTabelle";
                        self::insertData($tableName, $dataSensors, $DBDataConnection);
                    }
                    if(is_array($data_pv_weather) && count($data_pv_weather) > 0 && $importType != 'ftpPush'){
                        $tableName = "db__pv_ws_$anlagenTabelle";
                        self::insertData($tableName, $data_pv_weather, $DBDataConnection);
                    }

                    if ($anlage->getHasPPC() && is_array($data_ppc) && count($data_ppc) > 0 && $importType != 'ftpPush') {
                        $tableName = "db__pv_ppc_$anlagenTabelle";
                        self::insertData($tableName, $data_ppc, $DBDataConnection);
                    }

                    if ($anlage->getSettings()->getImportType() == 'withStringboxes' && is_array($data_pv_dcist) && count($data_pv_dcist) > 0 && $importType != 'ftpPush') {
                        $tableName = "db__pv_dcist_$anlagenTabelle";
                        self::insertData($tableName, $data_pv_dcist, $DBDataConnection);

                        $tableName = "db__string_pv_$anlagenTabelle";
                        self::insertData($tableName, $data_db_string_pv, $DBStbConnection);
                    }

                    if(is_array($data_pv_ist) && count($data_pv_ist) > 0 && $importType != 'ftpPush') {
                        $tableName = "db__pv_ist_$anlagenTabelle";
                        self::insertData($tableName, $data_pv_ist, $DBDataConnection);
                    }
                    break;
            }
            //end write Data in the tables
        }
    }

    public function unHashData(string $encodedData): string
    {
        return hex2bin(base64_decode($encodedData));
    }

}