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
        $dataArray = [];
        if ($hour) {
            if($isEastWest) {
                $sql_irr_plant = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to' AND (type_sensor like 'irr-west' OR type_sensor like 'irr-east') and stamp like '%:00:00';";
            }else{
                $sql_irr_plant = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to' AND type_sensor like 'irr' and stamp like '%:00:00';";
            }
        }else{
            if($isEastWest) {
                $sql_irr_plant = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to' AND (type_sensor like 'irr-west' OR type_sensor like 'irr-east');";
            }else{
                $sql_irr_plant = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to' AND type_sensor like 'irr';";
            }
        }

        $result = $conn->query($sql_irr_plant);

        if ($result) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                $irrCounter = 1;
                $gmPyEast = $gmPyWest = $irrValueArray = [];
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    if($counter == 0){
                        $stampTemp = $row['stamp'];
                    }
                    $dataArray['nameX'][1] = 'G_M0';
                    $irrValueArray["val1"] = $row['gmo'];
                    if($stampTemp != $row['stamp']){
                        $dataArray[] = [
                            'stamp' =>          $stampTemp,
                            'values' =>         $irrValueArray
                        ];
                        unset($gmPyWest);
                        unset($gmPyEast);
                        unset($irrValueArray);
                        $gmPyEast = $gmPyWest = $irrValueArray = [];
                        $irrCounter = 1;
                    }


                    $stamp = self::timeAjustment(strtotime((string) $ro['stamp']), $anlage->getWeatherStation()->gettimeZoneWeatherStation());
                    if($isEastWest){
                        if (!$anlage->getWeatherStation()->getChangeSensor() == 'Yes') {
                            if($row['usetocalc_sensor'] && $row['type_sensor'] == 'irr-east'){
                                array_push($gmPyWest, $row['value']);
                            }
                            if($row['usetocalc_sensor'] && $row['type_sensor'] == 'irr-west'){
                                array_push($gmPyEast, $row['value']);
                            }
                        }else{
                            if($row['usetocalc_sensor'] && $row['type_sensor'] == 'irr-west'){
                                array_push($gmPyWest, $row['value']);
                            }
                            if($row['usetocalc_sensor'] && $row['type_sensor'] == 'irr-east'){
                                array_push($gmPyEast, $row['value']);
                            }
                        }

                    }else{
                        if($row['usetocalc_sensor'] && $row['type_sensor'] == 'irr'){
                            array_push($gmPyEast, $row['value']);
                        }
                        $gmPyWest = [];
                    }

                    if($row['usetocalc_sensor'] && ($row['type_sensor'] == 'irr' || $row['type_sensor'] == 'irr-east' || $row['type_sensor'] == 'irr-west')){

                        $irrValueArray["val".$irrCounter] = $row['value'];

                        $irrCounter++;
                    }
                    $stampTemp = $row['stamp'];

                    $counter++;
                }

                $irrCounter = 1;
                for ($i = 0; $i < count($dataArray); $i++) {
                    $dataArray2['chart'][$i]['date'] = $dataArray[$i]['stamp'];


                    if(is_array($dataArray[$i]['values']) && count($dataArray[$i]['values']) > 0){
                        $k = 1;
                        $valueSumm = 0;
                        for ($j = 0; $j < count($dataArray[$i]['values']); $j++) {
                            $dataArrayValues['val'.$k] =  $dataArray[$i]['values']['val'.$k];
                            $valueSumm = $valueSumm+$dataArray[$i]['values']['val'.$k];
                            $k++;
                        }
                        if($valueSumm > 0){
                            $dataArray2['chart'][$i] = $dataArray2['chart'][$i] + $dataArrayValues;
                        }

                        unset($dataArrayValues);
                    }

                    $irrCounter++;

                }


            }
        }
        echo '<pre>';
        print_r($dataArray2);
        echo '</pre>';
        exit;
        $conn = null;

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
        $dataArray['maxSeries'] = 0;
        $isEastWest = $anlage->getIsOstWestAnlage();

        // Strom für diesen Zeitraum und diesen Inverter

        if ($hour) {
            $sql_irr_plant = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to' and stamp like '%:00:00';";
        }else{
            $sql_irr_plant = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$from' AND stamp <= '$to';";
        }

        $result = $conn->query($sql_irr_plant);

        if ($result) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                $irrCounter = 2;
                $gmPyHori = $gmPyEast = $gmPyWest = $irrValueArray = [];
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    #echo 'XX '.$row['shortname_sensor'].'<br>';
                    if($counter == 0){
                        $stampTemp = $row['stamp'];
                    }
                    $dataArray['nameX'][1] = 'G_M0';
                    $irrValueArray["val1"] = $row['gmo'];
                    if($stampTemp != $row['stamp']){
                        $dataArray[] = [
                            'irrHorizontal' =>  $this->mittelwert($gmPyHori),
                            'irrLower' =>       $this->mittelwert($gmPyWest),
                            'irrUpper' =>       $this->mittelwert($gmPyEast),
                            'stamp' =>          $stampTemp,
                            'values' =>         $irrValueArray
                        ];
                        unset($gmPyHori);
                        unset($gmPyWest);
                        unset($gmPyEast);
                        unset($irrValueArray);
                        $gmPyHori = $gmPyEast = $gmPyWest = $irrValueArray = [];
                        $irrCounter = 2;
                    }

                    if($row['usetocalc_sensor'] && $row['type_sensor'] == 'irr-hori'){
                            array_push($gmPyHori, $row['value']);
                    }

                    if($isEastWest){
                        if($row['usetocalc_sensor'] && $row['type_sensor'] == 'irr-west'){
                            array_push($gmPyWest, $row['value']);
                        }
                        if($row['usetocalc_sensor'] && $row['type_sensor'] == 'irr-east'){
                            array_push($gmPyEast, $row['value']);
                        }
                    }else{
                        if($row['usetocalc_sensor'] && $row['type_sensor'] == 'irr'){
                            array_push($gmPyEast, $row['value']);
                        }
                        $gmPyWest = [];
                    }

                    if($row['usetocalc_sensor'] && ($row['type_sensor'] == 'irr' || $row['type_sensor'] == 'irr-east' || $row['type_sensor'] == 'irr-west')){

                        if (!isset($dataArray['nameX'][$irrCounter])) {
                            $dataArray['nameX'][$irrCounter] = $row['shortname_sensor'];
                        }
                        $irrValueArray["val".$irrCounter] = $row['value'];
                        if ($irrCounter > $dataArray['maxSeries']) {
                            $dataArray['maxSeries'] = $irrCounter;
                        }
                        $irrCounter++;
                    }
                    $stampTemp = $row['stamp'];

                    $counter++;
                }

                $dataArray2['maxSeries'] = $dataArray['maxSeries'];
                $irrCounter = 1;
                for ($i = 0; $i < count($dataArray); $i++) {
                    $dataArray2['chart'][$i]['date'] = $dataArray[$i]['stamp'];
                    if ($anlage->getIsOstWestAnlage()) {

                        $dataArray2['chart'][$i]['g4n'] = (((float) $dataArray[$i]['irUpper'] * $anlage->getPowerEast() + (float) $dataArray[$i]['irrLower'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()));
                        if ($dataArray2['chart'][$i]['g4n'] < 0) {
                            $dataArray2['chart'][$i]['g4n'] = 0;
                        }
                    } else {
                        if ($anlage->getWeatherStation()->getChangeSensor() == 'Yes') {
                            $dataArray2['chart'][$i]['g4n'] = (float) $dataArray[$i]['irrLower']; // getauscht, nutze unterene Sensor
                        } else {
                            $dataArray2['chart'][$i]['g4n'] = (float) $dataArray[$i]['irrUpper']; // nicht getauscht, nutze oberen Sensor
                        }
                    }


                    if(is_array($dataArray[$i]['values']) && count($dataArray[$i]['values']) > 0){
                        #array_push($dataArray2['chart'][$i], $dataArray[$i]['values']);
                        $k = 1;
                        $valueSumm = 0;
                        for ($j = 0; $j < count($dataArray[$i]['values']); $j++) {
                            #array_push($dataArray2['chart'][$i], $dataArray[$i]['values']['val'.$k]);
                            $dataArrayValues['val'.$k] =  $dataArray[$i]['values']['val'.$k];
                            $valueSumm = $valueSumm+$dataArray[$i]['values']['val'.$k];
                            $k++;
                        }
                        if($valueSumm > 0){
                            $dataArray2['chart'][$i] = $dataArray2['chart'][$i] + $dataArrayValues;
                        }

                        unset($dataArrayValues);
                    }

                    $irrCounter++;

                }
                $dataArray2['nameX'] = $dataArray['nameX'];


            }
        }
        $conn = null;

        return $dataArray2;
    }

}
