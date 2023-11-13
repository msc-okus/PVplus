<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use PDO;
use App\Service\PdoService;

class IrradiationChartService
{
    use G4NTrait;

    public function __construct(
        private $host,
        private $userPlant,
        private $passwordPlant,
        private readonly FunctionsService $functions,
        private readonly InvertersRepository $invertersRep,
        private readonly PdoService $pdoService,

    )
    {
    }

    /**
     * Erzeugt Daten für das Strahlungs Diagramm.
     *
     * @param $from
     * @param $to
     * @param string|null $mode
     * @param bool|null $hour
     * @return array
     * @throws \Exception
     */
    public function getIrradiation(Anlage $anlage, $from, $to, ?string $mode = 'all', ?bool $hour = false): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        if ($hour) {
            $sql2 = 'SELECT a.stamp, sum(b.g_lower) as g_lower, sum(b.g_upper) as g_upper FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP BY date_format(a.stamp, '$form')";
        } else {
            $sql2 = 'SELECT a.stamp, b.g_lower as g_lower , b.g_upper as g_upper FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP BY date_format(a.stamp, '$form')";
        }

        $res = $conn->query($sql2);
        if ($res->rowCount() > 0) {
            $counter = 0;
            while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
                // upper pannel
                if ($ro['g_upper'] == "") {
                    $irr_upper = null;
                } else {
                    $irr_upper = (float) str_replace(',', '.', (string) $ro['g_upper']);
                    if ($hour) $irr_upper = $irr_upper / 4;
                }

                // lower pannel
                if ($ro['g_lower'] == "") {
                    $irr_lower = null;
                } else {
                    $irr_lower = (float) str_replace(',', '.', (string) $ro['g_lower']);
                    if ($hour) $irr_lower = $irr_lower / 4;
                }

                $stamp = self::timeAjustment(strtotime((string) $ro['stamp']), $anlage->getWeatherStation()->gettimeZoneWeatherStation());
                if ($anlage->getWeatherStation()->getChangeSensor() == 'Yes') {
                    $swap = $irr_lower;
                    $irr_lower = $irr_upper;
                    $irr_upper = $swap;
                }
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = $stamp; // self::timeShift($anlage, $stamp);
                if (!($irr_upper + $irr_lower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    switch ($mode) {
                        case 'all':
                            $dataArray['chart'][$counter]['val1'] = $irr_upper < 0 ? 0 : $irr_upper; // upper pannel
                            $dataArray['chart'][$counter]['val2'] = $irr_lower < 0 ? 0 : $irr_lower; // lower pannel
                            break;
                        case 'upper':
                            $dataArray['chart'][$counter]['val1'] = $irr_upper < 0 ? 0 : $irr_upper; // upper pannel
                            break;
                        case 'lower':
                            $dataArray['chart'][$counter]['val1'] = $irr_lower < 0 ? 0 : $irr_lower; // upper pannel
                            break;
                    }
                }
                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten für das Strahlungs Diagramm.
     *
     * @param $from
     * @param $to
     * @param string|null $mode
     * @param bool|null $hour
     * @return array
     * @throws \Exception
     */
    public function getIrradiationFromSensorsData(Anlage $anlage, $from, $to, ?string $mode = 'all', ?bool $hour = false): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $isEastWest = $anlage->getIsOstWestAnlage();
        $anlageSensors = $anlage->getSensors()->toArray();
        $length = is_countable($anlageSensors) ? count($anlageSensors) : 0;
        $sensorsArray = self::getSensorsData($anlageSensors, $length);
        $dataArray = [];
        if ($hour) {
            $sql_irr_plant = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to' AND stamp like '%:00:00' order by stamp;";
        }else{
            $sql_irr_plant = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to' order by stamp;";
        }

        $result = $conn->query($sql_irr_plant);

        if ($result) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                $counter2 = 0;
                $gmPyEast = $gmPyWest =  [];
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    if($counter == 0){
                        $stampTemp = $row['stamp'];
                    }
                    if($stampTemp != $row['stamp']){
                        if($isEastWest) {
                            $dataArray['chart'][] = [
                                'date' =>          $stampTemp,
                                'val1' =>       $this->mittelwert($gmPyEast), //irrUpper
                                'val2' =>       $this->mittelwert($gmPyWest), //irrLower
                            ];
                        }else{
                            $gmPyEast[] = 0;
                            $dataArray['chart'][] = [
                                'date' =>          $stampTemp,
                                'val1' =>       $this->mittelwert($gmPyEast), //irrUpper
                                'val2' =>       $this->mittelwert($gmPyWest), //irrLower
                            ];

                        }

                        if (!($this->mittelwert($gmPyEast) + $this->mittelwert($gmPyWest) == 0 && self::isDateToday($stampTemp) && self::getCetTime() - strtotime($stampTemp) < 7200)) {
                            switch ($mode) {
                                case 'all':
                                    $dataArray['chart'][$counter2]['val1'] = $this->mittelwert($gmPyEast) < 0 ? 0 : $this->mittelwert($gmPyEast); // upper pannel
                                    $dataArray['chart'][$counter2]['val2'] = $this->mittelwert($gmPyWest) < 0 ? 0 : $this->mittelwert($gmPyWest); // lower pannel
                                    break;
                                case 'upper':
                                    $dataArray['chart'][$counter2]['val1'] = $this->mittelwert($gmPyEast) < 0 ? 0 : $this->mittelwert($gmPyEast); // upper pannel
                                    unset($dataArray['chart'][$counter2]['val2']);
                                    break;
                                case 'lower':
                                    $dataArray['chart'][$counter2]['val1'] = $this->mittelwert($gmPyWest) < 0 ? 0 : $this->mittelwert($gmPyWest); // upper pannel
                                    unset($dataArray['chart'][$counter2]['val1']);
                                    break;
                            }
                        }

                        unset($gmPyWest);
                        unset($gmPyEast);
                        $gmPyEast = $gmPyWest = [];
                        $counter2 ++;
                    }

                    if($isEastWest){
                        if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr-east'){
                            array_push($gmPyWest, $row['value']);
                        }
                        if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr-west'){
                            array_push($gmPyEast, $row['value']);
                        }
                    }else{
                        if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr'){
                            array_push($gmPyEast, $row['value']);
                        }
                    }

                    $stampTemp = $row['stamp'];

                    $counter++;
                }
            }
        }


        $conn = null;
        $from = substr($stampTemp, 0, -3);

        $fromObj = date_create($from);
        $endObj  = date_create($to);

        //fil up rest of day
        for ($dayStamp = $fromObj->getTimestamp(); $dayStamp <= $endObj->getTimestamp(); $dayStamp += 900) {

            #echo "$dayStamp <br>";
            $date = date('Y-m-d H:i', $dayStamp);
            $dataArray['chart'][count($dataArray['chart'])] = [
                'date'          =>  $date,
                'nal1'           =>  null,
                'nal2'           =>  null
            ];
        }

        if(is_array($dataArray) && count($dataArray) == 0){
            $x = [];

            $fromObj = date_create($from);
            $endObj  = date_create($to);

            //fil up rest of day
            $i = 0;
            for ($dayStamp = $fromObj->getTimestamp(); $dayStamp <= $endObj->getTimestamp(); $dayStamp += 900) {
                $date = date('Y-m-d H:i', $dayStamp);
                $dataArray['chart'][$i] = [
                    'date'              =>  $date,
                    'nal1'           =>  null,
                    'nal2'           =>  null
                ];
                $i++;
            }
        }
        return $dataArray;
    }

    /**
     * Erzeuge Daten für die Strahlung die direkt von der Anlage geliefert wird.
     *
     * @param $from
     * @param $to
     * @return array
     * @throws \Exception
     */
    public function getIrradiationPlant(Anlage $anlage, $from, $to, bool $hour): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        // Strom für diesen Zeitraum und diesen Inverter
        if ($hour) {
            $sql_irr_plant = 'SELECT a.stamp as stamp, (b.irr_anlage) AS irr_anlage FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameIst().") b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' group by date_format(a.stamp, '$form');";
        } else {
            $sql_irr_plant = 'SELECT a.stamp as stamp, b.irr_anlage AS irr_anlage FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameIst().") b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' group by date_format(a.stamp, '$form');";
        }
        $result = $conn->query($sql_irr_plant);
        if ($result) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = self::timeAjustment($row['stamp'], (int) $anlage->getAnlZeitzone(), true);
                    $stamp2 = self::timeAjustment($stamp, 1);
                    // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    $dataArray['chart'][$counter]['date'] = $stamp; // self::timeShift($anlage, $stamp);

                    if ($hour) {
                        $sqlWeather = 'SELECT * FROM '.$anlage->getDbNameWeather()." WHERE stamp >= '$stamp' AND stamp < '$stamp2' group by date_format(stamp, '$form')";
                    } else {
                        $sqlWeather = 'SELECT * FROM '.$anlage->getDbNameWeather()." WHERE stamp = '$stamp' group by date_format(stamp, '$form')";
                    }
                    $resultWeather = $conn->query($sqlWeather);

                    if ($resultWeather->rowCount() == 1) {
                        $weatherRow = $resultWeather->fetch(PDO::FETCH_ASSOC);
                        if ($anlage->getIsOstWestAnlage()) {
                            $dataArray['chart'][$counter]['g4n'] = (((float) $weatherRow['g_upper'] * $anlage->getPowerEast() + (float) $weatherRow['g_lower'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()));
                            if ($dataArray['chart'][$counter]['g4n'] < 0) $dataArray['chart'][$counter]['g4n'] = 0;
                        } else {
                            if ($anlage->getWeatherStation()->getChangeSensor() == 'Yes') {
                                $dataArray['chart'][$counter]['g4n'] = (float) $weatherRow['g_lower']; // getauscht, nutze unterene Sensor
                            } else {
                                $dataArray['chart'][$counter]['g4n'] = (float) $weatherRow['g_upper']; // nicht getauscht, nutze oberen Sensor
                            }
                        }
                    } else {
                        if (!(self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            $dataArray['chart'][$counter]['g4n'] = null;
                        }
                    }

                    if ($row['irr_anlage'] != '') {

                        $irrAnlageArray = json_decode((string) $row['irr_anlage'], null, 512, JSON_THROW_ON_ERROR);
                        $irrCounter = 1;
                        foreach ($irrAnlageArray as $irrAnlageItem => $irrAnlageValue) {

                            if (!($irrAnlageValue == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                if (!isset($irrAnlageValue) or is_array($irrAnlageValue)) {
                                    $irrAnlageValue = 0;
                                }

                                $dataArray['chart'][$counter]["val$irrCounter"] = round(max($irrAnlageValue, 0), 2);
                                if (!isset($dataArray['nameX'][$irrCounter])) {
                                    $dataArray['nameX'][$irrCounter] = $irrAnlageItem;
                                }
                            }
                            if ($irrCounter > $dataArray['maxSeries']) {
                                $dataArray['maxSeries'] = $irrCounter;
                            }
                            ++$irrCounter;
                        }

                    }
                    ++$counter;
                }
            }
        }
        $conn = null;
        return $dataArray;
    }

    /**
     * Erzeuge Daten für die Strahlung die direkt von der Anlage geliefert wird aus SensorsData Tabelle.
     *
     * @param $from
     * @param $to
     * @return array
     * @throws \Exception
     */
    public function getIrradiationPlantFromSensorsData(Anlage $anlage, $from, $to, bool $hour): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];
        $dataArrayFinal = [];
        $dataArray['maxSeries'] = 0;
        $isEastWest = $anlage->getIsOstWestAnlage();
        $anlageSensors = $anlage->getSensors()->toArray();
        $length = is_countable($anlageSensors) ? count($anlageSensors) : 0;
        $sensorsArray = self::getSensorsData($anlageSensors, $length);

        // Strom für diesen Zeitraum und diesen Inverter

        if ($hour) {
            $sql_irr_plant = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to' and stamp like '%:00:00' order by stamp;";
        }else{
            $sql_irr_plant = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to' order by stamp;";
        }

        $result = $conn->query($sql_irr_plant);

        if ($result) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                $irrCounter = 1;
                $gmO = null;
                $gmPyHori = $gmPyEast = $gmPyWest = $irrValueArray = [];
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $dataArray['nameX'][1] = 'G_M0';
                    //create the data for each timepoint
                    if($stampTemp != $row['stamp']){
                        $dataArray[$counter] = [
                            'gmo'               =>  $gmO[0],
                            'irrHorizontal'     =>  $this->mittelwert($gmPyHori),
                            'irrLower'          =>  $this->mittelwert($gmPyWest),
                            'irrUpper'          =>  $this->mittelwert($gmPyEast),
                            'stamp'             =>  $stampTemp,
                            'values'            =>  $irrValueArray,
                            'sensorShortName'   =>  $shortNameTemp //this is for sensors they are activated by date-from in plant-sensors-table
                        ];
                        unset($gmPyHori);
                        unset($gmPyWest);
                        unset($gmPyEast);
                        unset($irrValueArray);
                        $gmPyHori = $gmPyEast = $gmPyWest = $irrValueArray = [];
                        $irrCounter = 2;
                        $counter++;
                    }

                    //$sensorsArray[$row['id_sensor']['usetocalc_sensor']
                    if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr-hori'){
                            array_push($gmPyHori, $row['value']);
                    }

                    if($isEastWest){
                        if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr-west'){
                            array_push($gmPyWest, $row['value']);
                        }
                        if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr-east'){
                            array_push($gmPyEast, $row['value']);
                        }

                    }else{
                        if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr'){
                            array_push($gmPyEast, $row['value']);
                        }
                        $gmPyWest = [];
                    }

                    if($sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr' || $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr-hori' || $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr-east' || $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr-west'){

                        if (!isset($dataArray['nameX'][$irrCounter])) {
                            $dataArray['nameX'][$irrCounter] = $sensorsArray[$row['id_sensor']]['shortname_sensor'];
                        }
                        if (!in_array($sensorsArray[$row['id_sensor']]['shortname_sensor'], $dataArray['nameX'])) {
                            $innArray = count( $dataArray['nameX']);

                            $dataArray['nameX'][$innArray+1] = $sensorsArray[$row['id_sensor']]['shortname_sensor'];
                            $dataArray['shortName'][] = $sensorsArray[$row['id_sensor']]['shortname_sensor'];
                        }
                        $irrValueArray["val".$irrCounter] = $row['value'];
                        if ($irrCounter > $dataArray['maxSeries']) {
                            $dataArray['maxSeries'] = $irrCounter;
                        }

                        $irrCounter++;
                    }
                    
                    $gmO[0] = $row['gmo'];
                    $shortNameTemp = $dataArray['shortName'];
                    $stampTemp = $row['stamp'];

                }

                unset($dataArray['shortName']);

                //create the output Array
                $dataArrayFinal['maxSeries'] = $dataArray['maxSeries'];
                $updateMaxSeries = 0;
                $inDataArray = count($dataArray)-3;

                if(is_array($dataArray[$inDataArray]['sensorShortName'])){
                    $updateMaxSeriesReal = count($dataArray[$inDataArray]['sensorShortName']);
                }else{
                    $updateMaxSeriesReal = 0;
                }
                $dateLastEntry = $dataArray[$inDataArray]['stamp'];

                for ($i = 0; $i < $inDataArray; $i++) {
                    if(is_array($dataArray[$i]['sensorShortName']) && count($dataArray[$i]['sensorShortName']) > 0 && $updateMaxSeries == 0){
                        $updateMaxSeries = $updateMaxSeriesReal;

                    }
                    $dataArrayFinal['chart'][$i]['date'] = $dataArray[$i]['stamp'];
                    if ($anlage->getIsOstWestAnlage()) {
                        $dataArrayFinal['chart'][$i]['g4n'] = (((float) $dataArray[$i]['irrUpper'] * $anlage->getPowerEast() + (float) $dataArray[$i]['irrLower'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()));
                        if ($dataArrayFinal['chart'][$i]['g4n'] < 0) {
                            $dataArrayFinal['chart'][$i]['g4n'] = 0;
                        }
                    } else {
                        if ($anlage->getWeatherStation()->getChangeSensor() == 'Yes') {
                            $dataArrayFinal['chart'][$i]['g4n'] = (float) $dataArray[$i]['irrLower']; // getauscht, nutze unterene Sensor
                        } else {
                            $dataArrayFinal['chart'][$i]['g4n'] = (float) $dataArray[$i]['irrUpper']; // nicht getauscht, nutze oberen Sensor
                        }
                    }

                    $dataArrayFinal['chart'][$i]["val1"] = $dataArray[$i]['gmo'];
                    if(is_array($dataArray[$i]['values']) && count($dataArray[$i]['values']) > 0){
                        $k = 2;
                        $valueSumm = 0;
                        //adding the single values frpm an row to an array
                        for ($j = 0; $j < count($dataArray[$i]['values']); $j++) {
                            $l = $updateMaxSeries + $k;
                            $dataArrayValues['val'.$l] =  $dataArray[$i]['values']['val'.$k];
                            $valueSumm = $valueSumm+$dataArray[$i]['values']['val'.$k];

                            $k++;
                        }
                        if ($anlage->getIsOstWestAnlage()) {
                                #echo "$l <br>";
                                $dataArrayValues['val' . $dataArray['maxSeries'] + $updateMaxSeriesReal + 1] = $dataArray[$i]['irrUpper'];
                                $dataArrayValues['val' . $dataArray['maxSeries'] + $updateMaxSeriesReal + 2] = $dataArray[$i]['irrLower'];

                        }
                        $dataArrayFinal['chart'][$i] = $dataArrayFinal['chart'][$i] + $dataArrayValues;
                        unset($dataArrayValues);
                    }
                }

                $dataArrayFinal['nameX'] = $dataArray['nameX'];


                if($updateMaxSeries > 0){
                    $dataArrayFinal['maxSeries'] = $dataArray['maxSeries'] + $updateMaxSeries;
                }
                if ($anlage->getIsOstWestAnlage()) {
                    $dataArrayFinal['maxSeries'] = $dataArray['maxSeries'] + 2 + $updateMaxSeries;
                    array_push($dataArrayFinal['nameX'], 'GM_Py_East');
                    array_push($dataArrayFinal['nameX'], 'GM_Py_West');
                }

                $dataArrayValues['val'] = [];
                $from = substr($dateLastEntry, 0, -3);

                $fromObj = date_create($from);
                $endObj  = date_create($to);
                //fil up rest of day
                for ($dayStamp = $fromObj->getTimestamp(); $dayStamp <= $endObj->getTimestamp(); $dayStamp += 900) {

                    #echo "$dayStamp <br>";
                    $date = date('Y-m-d H:i', $dayStamp);
                    $dataArrayFinal['chart'][count($dataArrayFinal['chart'])] = [
                        'date'          =>  $date,
                        'g4n'           =>  null,
                        'irrHorizontal' =>  null,
                        'irrLower'      =>  null,
                        'irrUpper'      =>  null
                    ];
                }
            }
        }

        $conn = null;
        if(is_array($dataArrayFinal) && count($dataArrayFinal) == 0){
            $x = [];
            $from = $date = date('Y-m-d 00:00', time());;

            $fromObj = date_create($from);
            $endObj  = date_create($to);

            //fil up rest of day
            $i = 0;
            for ($dayStamp = $fromObj->getTimestamp(); $dayStamp <= $endObj->getTimestamp(); $dayStamp += 900) {
                $date = date('Y-m-d H:i', $dayStamp);
                $dataArrayFinal['chart'][$i] = [
                    'date'              =>  $date,
                    'g4n'               =>  null,
                    'chart'             =>  $x
                ];
                $i++;
            }
        }

        return $dataArrayFinal;
    }
}
