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
        private readonly FunctionsService $functions,
        private readonly InvertersRepository $invertersRep,
        private readonly PdoService $pdoService,
    )
    {
    }

    /**
     * Erzeugt Daten für das Strahlungs Diagramm.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param string|null $mode
     * @param bool|null $hour
     * @return array
     */
    public function getIrradiation(Anlage $anlage, $from, $to, ?string $mode = 'all', ?bool $hour = false): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        if ($hour) {
            $sql2 = 'SELECT a.stamp, sum(b.g_lower) as g_lower, sum(b.g_upper) as g_upper FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) WHERE a.stamp > '$from' and a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";
        } else {
            $sql2 = 'SELECT a.stamp, b.g_lower as g_lower , b.g_upper as g_upper FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) WHERE a.stamp > '$from' and a.stamp <= '$to' GROUP BY date_format(a.stamp, '$form')";
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
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param string|null $mode
     * @param bool|null $hour
     * @return array
     */
    public function getIrradiationFromSensorsData(Anlage $anlage, $from, $to, ?string $mode = 'all', ?bool $hour = false): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $isEastWest = $anlage->getIsOstWestAnlage();
        $anlageSensors = $anlage->getSensors()->toArray();
        $length = is_countable($anlageSensors) ? count($anlageSensors) : 0;
        $sensorsArray = self::getSensorsData($anlageSensors, $length);
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        $dataArray['chart'] = [];
        if ($hour) {
            //zu from eine Stunde + da sonst Diagrammm nicht erscheint
            $fromPlusOneHour = strtotime($from)+3600;
            $from = date('Y-m-d H:i', $fromPlusOneHour);
            $sql_irr_plant = "SELECT stamp, id_sensor, avg(value) as value, avg(gmo) as gmo FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to'  group by id_sensor, date_format(stamp, '$form') order by stamp, id_sensor;";
            $timeStepp = 3600;
        }else{
            $sql_irr_plant = "SELECT stamp, id_sensor, avg(value) as value, avg(gmo) as gmo FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to'  group by id_sensor, date_format(stamp, '$form') order by stamp, id_sensor;";
            $timeStepp = 900;
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
                            $gmPyWest[] = $row['value'];
                        }
                        if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr-west'){
                            $gmPyEast[] = $row['value'];
                        }
                    }else{
                        if($sensorsArray[$row['id_sensor']]['usetocalc_sensor'] && $sensorsArray[$row['id_sensor']]['type_sensor'] == 'irr'){
                            $gmPyEast[] = $row['value'];
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
        if(is_array($dataArray) && count($dataArray) > 0) {
            for ($dayStamp = $fromObj->getTimestamp(); $dayStamp <= $endObj->getTimestamp(); $dayStamp += $timeStepp) {

                #echo "$dayStamp <br>";
                $date = date('Y-m-d H:i', $dayStamp);
                $dataArray['chart'][count($dataArray['chart'])] = [
                    'date' => $date
                ];
            }
        }

        if(is_array($dataArray) && count($dataArray) == 0){
            $x = [];
            $from = $date = date('Y-m-d 00:00', time());;

            $fromObj = date_create($from);
            $endObj  = date_create($to);
            $dataArray['maxSeries'] = 1;
            //fil up rest of day
            $i = 0;
            for ($dayStamp = $fromObj->getTimestamp(); $dayStamp <= $endObj->getTimestamp(); $dayStamp += $timeStepp) {
                $date = date('Y-m-d H:i', $dayStamp);
                $dataArray['chart'][$i] = [
                    'date'              =>  $date,
                    'val1'=>0
                ];
                $i++;
            }
            $dataArray['nameX'][0] = 'a';
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
        $dataArrayTemp = [];
        $dataArrayFinal = [];
        $gmPyEast = [];
        $dataArray['maxSeries'] = 0;
        $isEastWest = $anlage->getIsOstWestAnlage();
        $anlageSensors = $anlage->getSensors()->toArray();
        $length = is_countable($anlageSensors) ? count($anlageSensors) : 0;
        $sensorsArray = self::getSensorsData($anlageSensors, $length);
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';

        if ($hour) {
            //zu from eine Stunde + da sonst Diagrammm nicht erscheint
            $fromPlusOneHour = strtotime($from) + 3600;
            $from = date('Y-m-d H:i', $fromPlusOneHour);
            $sql_irr_plant = "SELECT stamp, id_sensor, avg(value) as value FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to'  group by id_sensor, date_format(stamp, '$form') order by stamp, id_sensor;";
            $timeStepp = 3600;
        }else{
            $sql_irr_plant = "SELECT stamp, id_sensor, avg(value) as value FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to'  group by id_sensor, date_format(stamp, '$form') order by stamp, id_sensor;";
            $timeStepp = 900;
        }

        $result = $conn->query($sql_irr_plant);

        if ($result) {
            if ($result->rowCount() > 0) {
                $i = 0;
                $gmO = null;
                while ($i = $result->fetch(PDO::FETCH_ASSOC)) {

                    if($sensorsArray[$i['id_sensor']]['type_sensor'] == 'irr-west' || $sensorsArray[$i['id_sensor']]['type_sensor'] == 'irr-east' || $sensorsArray[$i['id_sensor']]['type_sensor'] == 'irr' || $sensorsArray[$i['id_sensor']]['type_sensor'] == 'irr-hori'){
                        $dataArray[] = [
                            #'gmo'               =>  $i['gmo'],
                            'stamp'             =>  $i['stamp'],
                            'sensorID'          =>  $i['id_sensor'],
                            'value'             =>  $i['value'],
                            'sensorType'        =>  $sensorsArray[$i['id_sensor']]['type_sensor'],
                            'sensorShortName'   =>  $sensorsArray[$i['id_sensor']]['shortname_sensor'],
                            'useToCalc'         =>  $sensorsArray[$i['id_sensor']]['usetocalc_sensor']
                        ];
                    }
                }


                $arrayTemp = [];
                $counter = 0;
                #$dataArrayTemp['nameX'][1] = 'G_M0';
                for ($i = 0; $i < count($dataArray); $i++) {
                    if ($i > 0 && $stampTemp != $dataArray[$i]['stamp']) {
                        $dataArrayTemp['maxSeries'] = $i;
                        $counter = $i - 1;
                        break;
                    }
                    $dataArrayTemp['nameX'][$i+1] = $dataArray[$i]['sensorShortName'];
                    $stampTemp = $dataArray[$i]['stamp'];
                }

                $j = 0;
                $valcounter = 0;
                for ($i = 0; $i < count($dataArray); $i++) {
                    if($dataArray[$i]['useToCalc'] && $dataArray[$i]['sensorType'] == 'irr-hori'){
                        $gmPyHori[] = $dataArray[$i]['value'];
                    }
                    if($isEastWest){
                        if($dataArray[$i]['useToCalc'] && $dataArray[$i]['sensorType'] == 'irr-west'){
                            $gmPyWest[] = $dataArray[$i]['value'];
                        }
                        if($dataArray[$i]['useToCalc'] && $dataArray[$i]['sensorType'] == 'irr-east'){
                            $gmPyEast[] = $dataArray[$i]['value'];
                        }
                    }else{
                        $gmPyEast[] = 0;
                        if($dataArray[$i]['useToCalc'] && $dataArray[$i]['sensorType'] == 'irr'){
                            $gmPyEast[] = $dataArray[$i]['value'];
                        }
                        $gmPyWest = [];
                    }

                    if($dataArray[$i]['startDateSensor'] != 0){
                        $start = strtotime($dataArray[$i]['startDateSensor']);
                    }else{
                        $start = $dataArray[$i]['startDateSensor'];
                    }

                    if($dataArray[$i]['endDateSensor'] != 0){
                        $end = strtotime($dataArray[$i]['endDateSensor']);
                    }else{
                        $end = $dataArray[$i]['endDateSensor'];
                    }
                    $nameXString= '';
                    foreach($dataArrayTemp['nameX'] as $value)
                    {
                        $nameXString = $nameXString.$value;
                    }

                    $nameXString= '';
                    foreach($dataArrayTemp['nameX'] as $value)
                    {
                        $nameXString = $nameXString.$value;
                    }

                    if (!str_contains($nameXString,$dataArray[$i]['sensorShortName'])) {
                        #echo 'YES:'.$dataArray[$i]['sensorShortName'].' / '.$dataArray[$i]['stamp'] .' / '. $dataArray[$i]['value'].' <br>';
                        $innArray = count($dataArrayTemp['nameX']);
                        $dataArrayTemp['nameX'][$innArray+1] = $dataArray[$i]['sensorShortName'];
                        $dataArrayTemp['maxSeries']++;
                        $valcounter++;
                    }

                    $arrayTemp[$j+1+$valcounter] = $dataArray[$i]['value'];

                    if ($j == $counter) {
                        for ($k = 0; $k < $valcounter; $k++) {
                            $x = $k+1;
                            #echo 'YES:'.$x.' / '.$valcounter.'<br>';
                            $arrayTemp[$x] = 0;
                        }
                        $dataArrayTemp[] = [
                            'irrHoriz'          => $this->mittelwert($gmPyHori),
                            'irrLower'          => $this->mittelwert($gmPyWest),
                            'irrUpper'          => $this->mittelwert($gmPyEast),
                            'stamp'             => $dataArray[$i]['stamp'],
                            #'gmo'               => $dataArray[$i]['gmo'],
                            'values'            => $arrayTemp
                        ];
                        unset($gmPyHori);
                        unset($gmPyEast);
                        unset($gmPyWest);
                        unset($arrayTemp);
                        $j = 0;
                    }else{
                        $j++;
                    }
                }



                //create the output Array
                $dataArrayFinal['maxSeries'] = $dataArrayTemp['maxSeries'];
                $updateMaxSeries = 0;
                $inDataArray = count($dataArrayTemp);

                $dateLastEntry = $dataArrayTemp[$inDataArray-3]['stamp'];

                for ($i = 0; $i < $inDataArray; $i++) {

                    $dataArrayFinal['chart'][$i]['date'] = $dataArrayTemp[$i]['stamp'];
                    if ($anlage->getIsOstWestAnlage()) {
                        $dataArrayFinal['chart'][$i]['g4n'] = round(((float) $dataArrayTemp[$i]['irrUpper'] * $anlage->getPowerEast() + (float) $dataArrayTemp[$i]['irrLower'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()), 3);
                        if ($dataArrayFinal['chart'][$i]['g4n'] < 0) {
                            $dataArrayFinal['chart'][$i]['g4n'] = 0;
                        }
                    } else {
                        if ($anlage->getWeatherStation()->getChangeSensor() == 'Yes') {
                            $dataArrayFinal['chart'][$i]['g4n'] = round((float) $dataArrayTemp[$i]['irrLower'],3); // getauscht, nutze unterene Sensor
                        } else {
                            $dataArrayFinal['chart'][$i]['g4n'] = round((float) $dataArrayTemp[$i]['irrUpper'],3); // nicht getauscht, nutze oberen Sensor
                        }
                    }

                    #$dataArrayFinal['chart'][$i]["val1"] = round($dataArrayTemp[$i]['gmo'], 3);
                    if(is_array($dataArrayTemp[$i]['values']) && count($dataArrayTemp[$i]['values']) > 0){
                        $k = 1;

                        //adding the single values frpm an row to an array
                        for ($j = 0; $j < count($dataArrayTemp[$i]['values']); $j++) {
                            $dataArrayValues['val'.$j + $k] =  round($dataArrayTemp[$i]['values'][$j+$k], 3);
                        }
                        if ($anlage->getIsOstWestAnlage()) {
                            $dataArrayValues['val' . $dataArrayTemp['maxSeries'] + 1] = $dataArrayTemp[$i]['irrUpper'];
                            $dataArrayValues['val' . $dataArrayTemp['maxSeries'] + 2] = $dataArrayTemp[$i]['irrLower'];

                        }
                        $dataArrayFinal['chart'][$i] = $dataArrayFinal['chart'][$i] + $dataArrayValues;
                        unset($dataArrayValues);
                    }
                }

                $dataArrayFinal['nameX'] = $dataArrayTemp['nameX'];

                if ($anlage->getIsOstWestAnlage()) {
                    $dataArrayFinal['maxSeries'] = $dataArrayTemp['maxSeries'] + 2;
                    array_push($dataArrayFinal['nameX'], 'GM_Py_East');
                    array_push($dataArrayFinal['nameX'], 'GM_Py_West');
                }

                $from = substr($dateLastEntry, 0, -3);

                $fromObj = date_create($from);
                $endObj  = date_create($to);
                //fil up rest of day
                for ($dayStamp = $fromObj->getTimestamp(); $dayStamp <= $endObj->getTimestamp(); $dayStamp += $timeStepp) {
                    $date = date('Y-m-d H:i:s', $dayStamp);
                    $dataArrayFinal['chart'][count($dataArrayFinal['chart'])] = [
                        'date'          =>  $date
                    ];
                }
            }
        }

        $conn = null;

        if(is_array($dataArrayFinal) && count($dataArrayFinal) == 0){
            $from = $date = date('Y-m-d 00:00', time());;

            $fromObj = date_create($from);
            $endObj  = date_create($to);
            $dataArrayFinal['maxSeries'] = 1;
            //fil up rest of day
            $i = 0;
            for ($dayStamp = $fromObj->getTimestamp(); $dayStamp <= $endObj->getTimestamp(); $dayStamp += $timeStepp) {
                $date = date('Y-m-d H:i:s', $dayStamp);
                $dataArrayFinal['chart'][$i] = [
                    'date'              =>  $date,
                    'val1'=>0
                ];
                $i++;
            }
            $dataArrayFinal['nameX'][0] = 'a';
        }

        return $dataArrayFinal;
    }
}