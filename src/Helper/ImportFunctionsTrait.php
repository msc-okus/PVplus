<?php
namespace App\Helper;

require_once __DIR__.'/../../public/config.php';

use App\Entity\WeatherStation;
use PDO;

trait ImportFunctionsTrait
{
    function getDcPNormPerInvereter($conn, array $groups, array $modules): array
    {

        $dcPNormPerInvereter = [];
        $pNormControlSum = 0;

        for ($i = 0; $i <= count($groups) - 1; $i++) {
            $index = $groups[$i]->getdcGroup();
            $groupId = $groups[$i]->getid();

            $query = "SELECT * FROM `anlage_group_modules` where `anlage_group_id` = $groupId  ";
            $stmt = $conn->query($query);
            $result = $stmt->fetchAll();
            $sumPNorm = 0;
            $power = 0;

            for ($k = 0; $k <= count($modules) - 1; $k++) {
                if ($modules[$k]->getId() == $result[0]['module_type_id']) {
                    (int)$power = $modules[$k]->getPower();
                }
            }
            $sumPNorm += (int)$result[0]['num_strings_per_unit'] * (int)$result[0]['num_modules_per_string'] * $power;


            $dcPNormPerInvereter[$index] = $sumPNorm;
            $pNormControlSum += $sumPNorm;
        }
        return $dcPNormPerInvereter;
    }

    /**
     * @param null $tableName
     * @param null $data
     * @param object|null $DBDataConnection
     */
    function insertData($tableName = NULL, $data = NULL, object $DBDataConnection = NULL): void
    {
        // obtain column template

        $stmt = $DBDataConnection->prepare("SHOW COLUMNS FROM $tableName");
        $stmt->execute();
        $columns = [];
        $columns = array_fill_keys(array_values($stmt->fetchAll(PDO::FETCH_COLUMN)), null);
        unset($columns['db_id']);

        /* ToDo: Following code could be a good altenative to the current used 'double while with breaks'

        foreach ($array as $key => $element) {
            if ($key === array_key_first($array)) {
                echo 'FIRST ELEMENT!';
            }

            if ($key === array_key_last($array)) {
                echo 'LAST ELEMENT!';
            }
        }
        */
        // multiple INSERT
        $rows = count($data);
        $j = 0;
        $i = 0;
        $rows = $rows - 1;
        while ($j <= $rows) {
            $values = [];
            while ($j <= $rows) {

                // reset row
                $row = $columns;

                // now fill our row with data
                foreach ($row as $key => $value) {
                    $row[$key] = $data[$j][$key];
                }

                // build INSERT array
                foreach ($row as $value) {
                    $values[] = $value;
                }

                $j++;
                // avoid memory kill
                if ($j > $rows) {
                    break;
                }
            }
            // build query
            $count_columns = count($columns);
            $placeholder = ',(' . substr(str_repeat(',?', $count_columns), 1) . ')';//,(?,?,?)
            $placeholder_group = substr(str_repeat($placeholder, count($values) / $count_columns), 1);//(?,?,?),(?,?,?)...
            $into_columns = implode(',', array_keys($columns));//col1,col2,col3
            // this part is optional:
            $on_duplicate = [];
            foreach ($columns as $column => $row) {
                $on_duplicate[] = $column;
                $on_duplicate[] = $column;
            }
            $on_duplicateSQL = ' ON DUPLICATE KEY UPDATE' . vsprintf(substr(str_repeat(', %s = VALUES(%s)', $count_columns), 1), $on_duplicate);
            // execute query
            $sql = 'INSERT INTO ' . $tableName . ' (' . $into_columns . ') VALUES' . $placeholder_group . $on_duplicateSQL;
            $stmt = $DBDataConnection->prepare($sql);//INSERT INTO towns (col1,col2,col3) VALUES(?,?,?),(?,?,?)... {ON DUPLICATE...}
            $stmt->execute($values);
            unset($on_duplicate);
        }
    }

    /**
     * Datenimport der Grid Daten in die Tabelle anlage_grid_meter_day<br>
     * Es werden Tages werte importiert.<br>
     * Sollte für diesen Tag schon ein Wert vorliegen wird dieser aktualisiert (stamp ist unique key).<br>
     * Stand: Februar 2021 - GSchu
     *
     * @param $anlagenID
     * @param $stamp
     * @param float $value
     */
    function insertDataIntoGridMeterDay($anlagenID, $stamp, float $value): void
    {
        $DBDataConnection = $this->pdoService->getPdoBase();

        $sql_sel_ins = "INSERT INTO anlage_grid_meter_day SET 
                    anlage_id = $anlagenID, stamp = '$stamp', grid_meter_value = $value 
                   ON DUPLICATE KEY UPDATE
                    grid_meter_value = $value";

        $DBDataConnection->exec($sql_sel_ins);
        $DBDataConnection = null;
    }

    /**
     * @param string|null $value
     * @param bool $convertToKWH
     * @return string|null
     */
    public function checkIfValueIsNotNull(?string $value, bool $convertToKWH = false): ?string
    {
        if ($value === "" || $value === null) {
            return null;
        } else {
            if ($convertToKWH) {
                return $value / 4000; // Convert to kWh
            } else {
                return (float)$value;
            }
        }
    }

    //Holt die Werte aus der V-Com-Response und ordnet sie den Sensoren zu

    /**
     * @param array $anlageSensors
     * @param int $length
     * @param bool $istOstWest
     * @param array $sensors
     * @param  $date
     * @return array
     */
    function checkSensors(array $anlageSensors, int $length, bool $istOstWest, array $sensors, array $basics, $date): array
    {
        if ($istOstWest) {
            $gmPyHori = [];
            $gmPyWest = [];
            $gmPyEast = [];
            $gmPyHori = $gmPyHoriAnlage = $gmPyWest = $gmPyWestAnlage = $gmPyEast = $gmPyEastAnlage = [];
            $result = [];
            for ($i = 0; $i < $length; $i++) {
                if ($anlageSensors[$i]->getvirtualSensor() == 'irr-hori') {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->getStartDateSensor() != null) {
                        $start = strtotime((string) $anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                    }
                    if ($anlageSensors[$i]->getEndDateSensor() != null) {
                        $end = strtotime((string) $anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                    }
                    $now = strtotime((string) $date);
                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if($anlageSensors[$i]->getIsFromBasics() == 1) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                array_push($gmPyHori, $basics[$date][$anlageSensors[$i]->getNameShort()]);
                            }
                            $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }else{
                            if($anlageSensors[$i]->getUseToCalc() == 1){
                                array_push($gmPyHori, max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0));
                            }
                            $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort()] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                        }
                    }
                }

                if ($anlageSensors[$i]->getvirtualSensor() == 'irr-west') {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->getStartDateSensor() != null) {
                        $start = strtotime((string) $anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                    }
                    if ($anlageSensors[$i]->getEndDateSensor() != null) {
                        $end = strtotime((string) $anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                    }
                    $now = strtotime((string) $date);
                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if($anlageSensors[$i]->getIsFromBasics() == 1) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                array_push($gmPyWest, $basics[$date][$anlageSensors[$i]->getNameShort()]);
                            }
                            $gmPyWestAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }else{
                            if($anlageSensors[$i]->getUseToCalc() == 1){
                                array_push($gmPyWest, max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0));
                            }
                            $gmPyWestAnlage[$anlageSensors[$i]->getNameShort()] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                        }
                    }

                }

                if ($anlageSensors[$i]->getvirtualSensor() == 'irr-east') {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->getStartDateSensor() != null) {
                        $start = strtotime((string) $anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                    }
                    if ($anlageSensors[$i]->getEndDateSensor() != null) {
                        $end = strtotime((string) $anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                    }
                    $now = strtotime((string) $date);
                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if($anlageSensors[$i]->getIsFromBasics() == 1) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                array_push($gmPyEast, $basics[$date][$anlageSensors[$i]->getNameShort()]);
                            }
                            $gmPyEastAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }else{
                            if($anlageSensors[$i]->getUseToCalc() == 1){
                                array_push($gmPyEast, max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0));
                            }
                            $gmPyEastAnlage[$anlageSensors[$i]->getNameShort()] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                        }
                    }
                }
            }

            $result[0] = [
                'irrHorizontal' => $this->mittelwert($gmPyHori),
                'irrHorizontalAnlage' => $gmPyHoriAnlage,
                'irrLower' => $this->mittelwert($gmPyWest),
                'irrLowerAnlage' => $gmPyWestAnlage,
                'irrUpper' => $this->mittelwert($gmPyEast),
                'irrUpperAnlage' => $gmPyEastAnlage,
            ];
        } else {
            $gmPyHori = $gmPyHoriAnlage = $gmPyEast = $gmPyEastAnlage = [];

            for ($i = 0; $i < $length; $i++) {
                if ($anlageSensors[$i]->getvirtualSensor() == 'irr-hori') {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->getStartDateSensor() != null) {
                        $start = strtotime((string) $anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                    }
                    if ($anlageSensors[$i]->getEndDateSensor() != null) {
                        $end = strtotime((string) $anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                    }
                    $now = strtotime((string) $date);
                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if($anlageSensors[$i]->getIsFromBasics() == 1) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                array_push($gmPyHori, $basics[$date][$anlageSensors[$i]->getNameShort()]);
                            }
                            $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }else{
                            if($anlageSensors[$i]->getUseToCalc() == 1){
                                array_push($gmPyHori, max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0));
                            }
                            $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort()] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                        }
                    }

                }

                if ($anlageSensors[$i]->getvirtualSensor() == 'irr') {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->getStartDateSensor() != null) {
                        $start = strtotime((string) $anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                    }
                    if ($anlageSensors[$i]->getEndDateSensor() != null) {
                        $end = strtotime((string) $anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                    }
                    $now = strtotime((string) $date);

                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if($anlageSensors[$i]->getIsFromBasics() == 1) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                array_push($gmPyEast, $basics[$date][$anlageSensors[$i]->getNameShort()]);
                            }
                            $gmPyEastAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }else{
                            if($anlageSensors[$i]->getUseToCalc() == 1){
                                array_push($gmPyEast, max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0));
                            }
                            $gmPyEastAnlage[$anlageSensors[$i]->getNameShort()] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                        }
                    }
                }

            }

            $result[0] = [
                'irrHorizontal' => $this->mittelwert($gmPyHori),
                'irrHorizontalAnlage' => $gmPyHoriAnlage,
                'irrLower' => 0,
                'irrLowerAnlage' => [],
                'irrUpper' => $this->mittelwert($gmPyEast),
                'irrUpperAnlage' => $gmPyEastAnlage,
            ];

        }

        //mNodulTemp, ambientTemp, windSpeed
        $tempModule = $tempAmbientArray = $tempAnlage = $windDirectionEWD = $windSpeedEWS = $windAnlage = [];
        for ($i = 0; $i < $length; $i++) {
            if ($anlageSensors[$i]->getvirtualSensor() == 'temp-modul') {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]->getStartDateSensor() != null) {
                    $start = strtotime((string) $anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                }
                if ($anlageSensors[$i]->getEndDateSensor() != null) {
                    $end = strtotime((string) $anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                }
                $now = strtotime((string) $date);
                if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                    if($anlageSensors[$i]->getIsFromBasics() == 1) {
                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                            array_push($tempModule, $basics[$date][$anlageSensors[$i]->getNameShort()]);
                        }
                        $tempAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                    }else{
                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                            array_push($tempModule, $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()]);
                        }
                        $tempAnlage[$anlageSensors[$i]->getNameShort()] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                    }
                }
            }
            if ($anlageSensors[$i]->getvirtualSensor() == 'temp-ambient') {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]->getStartDateSensor() != null) {
                    $start = strtotime((string) $anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                }
                if ($anlageSensors[$i]->getEndDateSensor() != null) {
                    $end = strtotime((string) $anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                }
                $now = strtotime((string) $date);
                if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                    if($anlageSensors[$i]->getIsFromBasics() == 1) {
                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                            array_push($tempAmbientArray, $basics[$date][$anlageSensors[$i]->getNameShort()]);
                        }
                        $tempAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                    }else{
                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                            array_push($tempAmbientArray, $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()]);
                        }
                        $tempAnlage[$anlageSensors[$i]->getNameShort()] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                    }
                }

            }

            if ($anlageSensors[$i]->getvirtualSensor() == 'wind-speed') {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]->getStartDateSensor() != null) {
                    $start = strtotime((string) $anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                }
                if ($anlageSensors[$i]->getEndDateSensor() != null) {
                    $end = strtotime((string) $anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                }
                $now = strtotime((string) $date);
                if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                    if($anlageSensors[$i]->getIsFromBasics() == 1) {
                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                            array_push($windSpeedEWS, $basics[$date][$anlageSensors[$i]->getNameShort()]);
                        }
                        $windAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                    }else{
                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                            array_push($windSpeedEWS, $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()]);
                        }
                        $windAnlage[$anlageSensors[$i]->getNameShort()] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                    }
                }
            }

            if ($anlageSensors[$i]->getvirtualSensor() == 'wind-direction') {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]->getStartDateSensor() != null) {
                    $start = strtotime((string) $anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                }
                if ($anlageSensors[$i]->getEndDateSensor() != null) {
                    $end = strtotime((string) $anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                }
                $now = strtotime((string) $date);

                if (($now >= $start && ($end == 0 || $now < $end)) || ($start == 0 && $end == 0)) {
                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if ($anlageSensors[$i]->getIsFromBasics() == 1) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                array_push($windDirectionEWD, $basics[$date][$anlageSensors[$i]->getNameShort()]);
                            }
                            $windAnlage[$anlageSensors[$i]->getNameShort(
                            )] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                array_push(
                                    $windDirectionEWD,
                                    $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()]
                                );
                            }
                            $windAnlage[$anlageSensors[$i]->getNameShort(
                            )] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                        }
                    }
                }

            }
        }

        $result[1] = [
            'tempPanel' => $this->mittelwert($tempModule),
            'tempAmbient' => $this->mittelwert($tempAmbientArray),
            'anlageTemp' => $tempAnlage,
            'windDirection' => $this->mittelwert($windDirectionEWD),
            'windSpeed' => $this->mittelwert($windSpeedEWS),
            'anlageWind' => $windAnlage,
        ];

        return $result;

    }

    //Ordnet die Werte aus der V-Com-Response den Sensoren zu um sie dann in pv_sensors_data_... zu speichern(aktuell auch noch in die alten ws Tabellen)

    /**
     * @param array $anlageSensors
     * @param int $length
     * @param array $sensors
     * @param  $stamp
     * @param  $date
     * @param $gMo
     * @return array
     */
    function getSensorsDataFromVcomResponse(array $anlageSensors, int $length, array $sensors, array $basics, $stamp, $date, string $gMo, bool $isDay): array
    {
        $gmx = 0;
        for ($i = 0; $i < $length; $i++) {
            $start = 0;
            $end = 0;

            if ($anlageSensors[$i]->getStartDateSensor() != null) {
                $start = strtotime((string) $anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
            }
            if ($anlageSensors[$i]->getEndDateSensor() != null) {
                $end = strtotime((string) $anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
            }
            $now = strtotime((string) $date);
            if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                $sensorId = $anlageSensors[$i]->getId();
                if($anlageSensors[$i]->getName() != 'G_MX'){
                    if($anlageSensors[$i]->getIsFromBasics() == 1){
                        $sensortype = $anlageSensors[$i]->getType();
                        if($sensortype == 'temperature'){
                            $value = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }else{
                            $value = max($basics[$date][$anlageSensors[$i]->getNameShort()], 0);
                        }
                        if($sensortype == 'pyranometer' && $isDay != 1){
                            $value = 0;
                        }
                    }else{
                        $sensortype = $anlageSensors[$i]->getType();
                        if($sensortype == 'temperature'){
                            $value = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                        }else{
                            $value = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                        }
                        if($sensortype == 'pyranometer' && $isDay != 1){
                            $value = 0;
                        }
                    }
                }else{
                    $value = $basics[$date]['G_M'.$gmx];
                    $gmx++;
                }
            }

            if($sensorId != null){
                $data_sensors[] = [
                    'date'                  => $date,
                    'stamp'                 => $stamp,
                    'id_sensor'             => $sensorId,
                    'value'                 => ($value != '') ? $value : 0,
                    'gmo'                   => $gMo
                ];
            }

        }

        $result[] = $data_sensors;
        return $result;
    }

    //Prüft welche Anlagen für den Import via Symfony freigeschaltet sind
    /**
     * @param object $conn
     * @return array
     */
    public function getPlantsImportReady(object $conn): array
    {
        $query = "SELECT `anlage_id` FROM `anlage_settings` where `symfony_import` = 1  ";
        $stmt = $stmt = $conn->query($query);
        return $stmt->fetchAll();
    }

    //importiert die Daten für Anlegen mit Stringboxes

    /**
     * @param \DateTime $stringBoxesTime
     * @param array $acGroups
     * @param array $inverters
     * @param string $date
     * @param int $plantId
     * @param string $stamp
     * @param float $eZEvu
     * @param bool|string $irrAnlage
     * @param bool|string $tempAnlage
     * @param bool|string $windAnlage
     * @param object $groups
     * @param int $stringBoxUnits
     * @return array
     * @throws \JsonException
     */
    function loadDataWithStringboxes($stringBoxesTime, array $acGroups, array $inverters, string $date, int $plantId, string $stamp, float $eZEvu, string $irrAnlage, string $tempAnlage, string $windAnlage, object $groups, int $stringBoxUnits): array
    {
        for ($i = 1; $i <= count($acGroups); $i++) {

            $pvpGroupAc = $acGroups[$i-1]['group_ac'];
            $pvpGroupDc = $i;
            $pvpInverter = $acGroups[$i-1]['group_ac'];

            if (is_array($inverters) && array_key_exists($date, $inverters)) {
                $custInverterKennung = $acGroups[$i-1]['importId'];
                $currentDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_DC']);
                $currentAc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC']);
                $currentAcP1 = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC1']);
                $currentAcP2 = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC2']);
                $currentAcP3 = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC3']);
                $voltageDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['U_DC']);
                $voltageAc = NULL;
                $voltageAcP1 = $inverters[$date][$custInverterKennung]['U_AC_L1L2'];
                $voltageAcP2 = $inverters[$date][$custInverterKennung]['U_AC_L2L3'];
                $voltageAcP3 = $inverters[$date][$custInverterKennung]['U_AC_L3L1'];
                $blindLeistung = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['Q_AC']);
                $frequenze = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['F_AC']);
                $powerAc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['P_AC'], true); // Umrechnung von Watt auf kWh
                $temp = $this->mittelwert([$inverters[$date][$custInverterKennung]['T_WR'], $inverters[$date][$custInverterKennung]['T_WR1'], $inverters[$date][$custInverterKennung]['T_WR2'], $inverters[$date][$custInverterKennung]['T_WR3'], $inverters[$date][$custInverterKennung]['T_WR4']]);

                $cosPhi = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['COS_PHI']);
                if (is_numeric($currentDc) && is_numeric($voltageDc)) {
                    $powerDc = $currentDc * $voltageDc / 1000 / 4;
                } else {
                    $powerDc = '';
                }
            } else {
                $powerAc = $currentAc = $voltageAc = $powerDc = $voltageDc = $currentDc = $temp = null;
                $cosPhi = $blindLeistung = $frequenze = $currentAcP1 = $currentAcP2 = $currentAcP3 = $voltageAcP1 = $voltageAcP2 = $voltageAcP3 = null;
            }

            $theoPower = 0;
            $tempCorr = 0;
            $dcCurrentMpp = $dcVoltageMpp = '{}';

            $data_pv_ist[] = [
                'anl_id' => $plantId,
                'stamp' => $stamp,
                'inv' => $pvpGroupAc,
                'group_dc' => $pvpGroupDc,
                'group_ac' => $pvpGroupAc,
                'unit' => $pvpInverter,
                'wr_num' => $pvpInverter,
                'wr_idc' => ($currentDc != '') ? $currentDc : NULL,
                'wr_pac' => ($powerAc != '') ? $powerAc : NULL,
                'p_ac_blind' => ($blindLeistung != '') ? $blindLeistung : NULL,
                'i_ac' => ($currentAc != '') ? $currentAc : NULL,
                'i_ac_p1' => ($currentAcP1 != '') ? $currentAcP1 : NULL,
                'i_ac_p2' => ($currentAcP2 != '') ? $currentAcP2 : NULL,
                'i_ac_p3' => ($currentAcP3 != '') ? $currentAcP3 : NULL,
                'u_ac' => ($voltageAc != '') ? $voltageAc : NULL,
                'u_ac_p1' => ($voltageAcP1 != '') ? $voltageAcP1 : NULL,
                'u_ac_p2' => ($voltageAcP2 != '') ? $voltageAcP2 : NULL,
                'u_ac_p3' => ($voltageAcP3 != '') ? $voltageAcP3 : NULL,
                'p_ac_apparent' => 0,
                'frequency' => ($frequenze != '') ? $frequenze : NULL,
                'wr_udc' => ($voltageDc != '') ? $voltageDc : NULL,
                'wr_pdc' => ($powerDc != '') ? $powerDc : NULL,
                'wr_temp' => ($temp != '') ? $temp : NULL,
                'wr_cos_phi_korrektur' => ($cosPhi != '') ? $cosPhi : NULL,
                'e_z_evu' => ($eZEvu != '') ? $eZEvu : NULL,
                'temp_corr' => $tempCorr,
                'theo_power' => $theoPower,
                'temp_cell' => NULL,
                'temp_cell_multi_irr' => NULL,
                'wr_mpp_current' => $dcCurrentMpp,
                'wr_mpp_voltage' => $dcVoltageMpp,
                'irr_anlage' => $irrAnlage,
                'temp_anlage' => $tempAnlage,
                'temp_inverter' => $tempAnlage,
                'wind_anlage' => $windAnlage,

            ];
        }

        $result[] = $data_pv_ist;

        foreach ($groups as $group) {
            $scbNo = $group->getImportId(); // $assign[0] = SCB # VCOM
            $pvpInverter = $group->getUnitFirst(); // $assign[2] = Inverter PV+
            $pvpGroupDc = $group->getDcGroup(); // $assign[3] = Gruppen Nr PV+
            $pvpGroupAc = $group->getAcGroup(); // $assign[4] = AC Gruppen Nr
            $currentDcSCB = 0;
            $dcCurrentMppArray = [];
            for ($n = 1; $n <= $stringBoxUnits; $n++) {
                $key = "I$n";

                $dcCurrentMppArray[$key] = $stringBoxesTime[$scbNo][$key];
                $currentDcSCB += $stringBoxesTime[$scbNo][$key];
                #echo "$date / $scbNo / $key".' / '.$stringBoxesTime[$scbNo][$key]." / $currentDcSCB".'<br>';
            }
            $voltageDc = $stringBoxesTime[$scbNo]['U_DC'];
            #$powerDc = $currentDcSCB * $voltageDc / 1000 / 4; // Umrechnung von W auf kW/h

            If(is_array($stringBoxesTime[$scbNo]) && array_key_exists('P_DC', $stringBoxesTime[$scbNo])){
                $powerDc = $stringBoxesTime[$scbNo]['P_DC'] / 1000 / 4; // Umrechnung von W auf kW/h
            }else{
                $powerDc = $currentDc * $voltageDc / 1000 / 4;
            }

            $dcCurrentMpp = json_encode($dcCurrentMppArray, JSON_THROW_ON_ERROR);
            $dcVoltageMpp = "{}";

            $data_pv_dcist[] = [
                'anl_id' => $plantId,
                'stamp' => $stamp,
                'wr_group' => $pvpGroupDc,
                'wr_num' => $pvpInverter,
                'wr_idc' => $currentDcSCB,
                'wr_udc' => $voltageDc,
                'wr_pdc' => $powerDc,
                'wr_temp' => 0,
                'wr_mpp_current' => $dcCurrentMpp,
                'wr_mpp_voltage' => $dcVoltageMpp,
                'group_ac' => $pvpGroupAc,
            ];

            for ($n = 1; $n <= $stringBoxUnits; $n++) {
                $key = "I$n";
                $data_db_string_pv[] = [
                    'anl_id' => $plantId,
                    'stamp' => $stamp,
                    'wr_group' => $pvpGroupDc,
                    'group_ac' => $pvpGroupAc,
                    'wr_num' => $pvpInverter,
                    'channel' => $n,
                    'I_value' => $stringBoxesTime[$scbNo][$key],
                    'U_value' => NULL,
                ];
            }

        }

        $result[] = $data_pv_dcist;
        $result[] = $data_db_string_pv;

        return $result;
    }

    //importiert die Daten für Anlegen ohne Stringboxes
    /**
     * @param array $inverters
     * @param string $date
     * @param int $plantId
     * @param string $stamp
     * @param float $eZEvu
     * @param bool|string $irrAnlage
     * @param bool|string $tempAnlage
     * @param bool|string $windAnlage
     * @param object $groups
     * @param $invertersUnits
     * @return array
     * @throws \JsonException
     */
    function loadData(array $inverters, string $date, int $plantId, string $stamp, float $eZEvu, string $irrAnlage, string $tempAnlage, string $windAnlage, object $groups, int $invertersUnits): array
    {

        foreach ($groups as $group) {

            $pvpInverter = $group->getDcGroup();
            $pvpGroupDc = $group->getDcGroup();
            $pvpGroupAc = $group->getAcGroup();

            if (is_array($inverters) && array_key_exists($date, $inverters)) {
                $custInverterKennung = $group->getImportId();
                $currentDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_DC']);
                $currentAc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC']);
                $currentAcP1 = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC1']);
                $currentAcP2 = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC2']);
                $currentAcP3 = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC3']);
                $voltageDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['U_DC']);
                $voltageAc = NULL;
                $voltageAcP1 = $inverters[$date][$custInverterKennung]['U_AC_L1L2'];
                $voltageAcP2 = $inverters[$date][$custInverterKennung]['U_AC_L2L3'];
                $voltageAcP3 = $inverters[$date][$custInverterKennung]['U_AC_L3L1'];
                $blindLeistung = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['Q_AC']);
                $frequenze = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['F_AC']);
                $powerAc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['P_AC'], true); // Umrechnung von Watt auf kWh
                $temp = $this->mittelwert([$inverters[$date][$custInverterKennung]['T_WR'], $inverters[$date][$custInverterKennung]['T_WR1'], $inverters[$date][$custInverterKennung]['T_WR2'], $inverters[$date][$custInverterKennung]['T_WR3'], $inverters[$date][$custInverterKennung]['T_WR4']]);
                $cosPhi = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['COS_PHI']);
                if (is_numeric($currentDc) && is_numeric($voltageDc)) {
                    $powerDc = $currentDc * $voltageDc / 1000 / 4;
                } else {
                    $powerDc = '';
                }

                // tempCorr nach NREL und dann theoPower berechnen
                // prüfe auf OST / WEST Sensoren und Strahlung ermitteln
                /*
                                $irr = ($irrUpper * $anlage->power_east + $irrLower * $anlage->power_west) / $anlage->power ;
                                echo "($irrUpper * $anlage->power_east + $irrLower * $anlage->power_west) / $anlage->power<br>";
                                $tempCorr = tempCorrection($tempCorrParams, $tempCorrParams['tempCellTypeAvg'], $windSpeed, $tempAmbient, $irr);
                                $theoPower = (($irr / 1000) * $dcPNormPerInvereter[$pvpGroupDc] * $tempCorr) / 1000 / 4;
                */

                $dcCurrentMppArray = [];
                $dcVoltageMppArray = [];
                if ($invertersUnits >= 1) {
                    for ($n = 1; $n <= $invertersUnits; $n++) {
                        $key = "I_DC$n";
                        $dcCurrentMppArray[$key] = $inverters[$date][$custInverterKennung][$key] * 4;
                    }
                    $dcCurrentMpp = json_encode($dcCurrentMppArray, JSON_THROW_ON_ERROR);

                    for ($n = 1; $n <= $invertersUnits; $n++) {
                        $key = "U_DC$n";
                        $dcVoltageMppArray[$key] = $inverters[$date][$custInverterKennung][$key] * 4;
                    }
                    $dcVoltageMpp = json_encode($dcVoltageMppArray, JSON_THROW_ON_ERROR);
                }

            } else {
                $powerAc = $currentAc = $voltageAc = $powerDc = $voltageDc = $currentDc = $temp = null;
                $cosPhi = $blindLeistung = $frequenze = $currentAcP1 = $currentAcP2 = $currentAcP3 = $voltageAcP1 = $voltageAcP2 = $voltageAcP3 = null;
                $dcCurrentMpp = $dcVoltageMpp = '{}';
            }

            $theoPower = 0;
            $tempCorr = 0;

            $data_pv_ist[] = [
                'anl_id' => $plantId,
                'stamp' => $stamp,
                'inv' => $pvpGroupAc,
                'group_dc' => $pvpGroupDc,
                'group_ac' => $pvpGroupAc,
                'unit' => $pvpInverter,
                'wr_num' => $pvpInverter,
                'wr_idc' => ($currentDc != '') ? $currentDc : NULL,
                'wr_pac' => ($powerAc != '') ? $powerAc : NULL,
                'p_ac_blind' => ($blindLeistung != '') ? $blindLeistung : NULL,
                'i_ac' => ($currentAc != '') ? $currentAc : NULL,
                'i_ac_p1' => ($currentAcP1 != '') ? $currentAcP1 : NULL,
                'i_ac_p2' => ($currentAcP2 != '') ? $currentAcP2 : NULL,
                'i_ac_p3' => ($currentAcP3 != '') ? $currentAcP3 : NULL,
                'u_ac' => ($voltageAc != '') ? $voltageAc : NULL,
                'u_ac_p1' => ($voltageAcP1 != '') ? $voltageAcP1 : NULL,
                'u_ac_p2' => ($voltageAcP2 != '') ? $voltageAcP2 : NULL,
                'u_ac_p3' => ($voltageAcP3 != '') ? $voltageAcP3 : NULL,
                'p_ac_apparent' => 0,
                'frequency' => ($frequenze != '') ? $frequenze : NULL,
                'wr_udc' => ($voltageDc != '') ? $voltageDc : NULL,
                'wr_pdc' => ($powerDc != '') ? $powerDc : NULL,
                'wr_temp' => ($temp != '') ? $temp : NULL,
                'wr_cos_phi_korrektur' => ($cosPhi != '') ? $cosPhi : NULL,
                'e_z_evu' => ($eZEvu != '') ? $eZEvu : NULL,
                'temp_corr' => $tempCorr,
                'theo_power' => $theoPower,
                'temp_cell' => NULL,
                'temp_cell_multi_irr' => NULL,
                'wr_mpp_current' => $dcCurrentMpp,
                'wr_mpp_voltage' => $dcVoltageMpp,
                'irr_anlage' => $irrAnlage,
                'temp_anlage' => $tempAnlage,
                'temp_inverter' => $tempAnlage,
                'wind_anlage' => $windAnlage,
            ];
        }

        $result[] = $data_pv_ist;
        return $result;
    }

    //importiert die Daten für PPC
    /**
     * @param $anlagePpcs
     * @param array $ppcs
     * @param string $date
     * @param string $stamp
     * @param int $plantId
     * @param string $anlagenTabelle
     * @return array
     */
    function getPpc($anlagePpcs, $ppcs, $date, $stamp, $plantId, $anlagenTabelle): array
    {
        foreach ($anlagePpcs as $anlagePpc) {
            $p_ac_inv = $pf_set = $p_set_gridop_rel = $p_set_rel = null;
            $p_set_rpc_rel = $q_set_rel = $p_set_ctrl_rel = $p_set_ctrl_rel_mean = null;
            if (isset($ppcs[$date])) {
                $p_set_gridop_rel = $this->checkIfValueIsNotNull($ppcs[$date][$anlagePpcs[0]['vcomId']]['PPC_P_SET_GRIDOP_REL']); // Regelung durch Grid Operator
                $p_set_rel = $this->checkIfValueIsNotNull($ppcs[$date][$anlagePpcs[0]['vcomId']]['PPC_P_SET_REL']);#
                $p_set_rpc_rel = $this->checkIfValueIsNotNull($ppcs[$date][$anlagePpcs[0]['vcomId']]['PPC_P_SET_RPC_REL']); // Regelung durch Direktvermarkter
            }

            $data_ppc[] = [
                'anl_id' => $plantId,
                'anl_intnr' => $anlagenTabelle,
                'stamp' => $stamp,
                'p_ac_inv' => $p_ac_inv,
                'q_ac_inv' => NULL,
                'pf_set' => $pf_set,
                'p_set_gridop_rel' => ($p_set_gridop_rel != '') ? $p_set_gridop_rel : NULL,
                'p_set_rel' => ($p_set_rel != '') ? $p_set_rel : NULL,
                'p_set_rpc_rel' => ($p_set_rpc_rel != '') ? $p_set_rpc_rel : NULL,
                'q_set_rel' => $q_set_rel,
                'p_set_ctrl_rel' => $p_set_ctrl_rel,
                'p_set_ctrl_rel_mean' => $p_set_ctrl_rel_mean,
            ];
        }
        $result[] = $data_ppc;
        return $result;
    }


}