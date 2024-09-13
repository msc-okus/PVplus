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
    function insertData($tableName = NULL, array $data = [], object $DBDataConnection = NULL): void
    {
        // obtain column template

        $stmt = $DBDataConnection->prepare("SHOW COLUMNS FROM $tableName");
        $stmt->execute();
        $columns = [];
        $columns = array_fill_keys(array_values($stmt->fetchAll(PDO::FETCH_COLUMN)), null);
        unset($columns['db_id']);

        if (str_contains($tableName, 'pv_ws')) {
            unset($columns['pa0']);
            unset($columns['pa1']);
            unset($columns['pa2']);
            unset($columns['pa3']);
        }

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
    function checkSensors(array $anlageSensors, int $length, bool $istOstWest, array $sensors, array $basics, $date, $plantId): array
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
                        if($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $gmPyHori[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            if (array_key_exists($date, $sensors)) {
                                if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                    $gmPyHori[] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                                }
                                $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort()] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                            }
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
                        if($anlageSensors[$i]->getIsFromBasics() == 1  && array_key_exists($date, $basics)) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $gmPyWest[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $gmPyWestAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            if(array_key_exists($date, $sensors)) {
                                if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                    $gmPyWest[] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                                }
                                $gmPyWestAnlage[$anlageSensors[$i]->getNameShort()] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                            }
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
                        if ($anlageSensors[$i]->getIsFromBasics() == 1  && array_key_exists($date, $basics)) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $gmPyEast[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $gmPyEastAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            if (array_key_exists($date, $sensors)) {
                                if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                    $gmPyEast[] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                                }
                                $gmPyEastAnlage[$anlageSensors[$i]->getNameShort()] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                            }
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
                        $start = strtotime($anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                    }
                    if ($anlageSensors[$i]->getEndDateSensor() != null) {
                        $end = strtotime($anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                    }
                    $now = strtotime($date);
                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if ($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                            if (array_key_exists($anlageSensors[$i]->getNameShort(), $basics[$date])) {
                                if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                    $gmPyHori[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                                }
                                $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                        } else {
                            if (array_key_exists($date, $sensors)) {
                                if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                    if (array_key_exists($anlageSensors[$i]->getVcomAbbr(), $sensors[$date][$anlageSensors[$i]->getVcomId()])) {
                                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                            $gmPyHori[] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                                        }
                                        $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort()] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                                    }
                                }
                            }
                        }
                    }

                }

                if ($anlageSensors[$i]->getvirtualSensor() == 'irr') {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->getStartDateSensor() != null) {
                        $start = strtotime($anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                    }
                    if ($anlageSensors[$i]->getEndDateSensor() != null) {
                        $end = strtotime($anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                    }
                    $now = strtotime($date);

                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if ($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $gmPyEast[] = array_key_exists($anlageSensors[$i]->getNameShort(), $basics[$date]) ? $basics[$date][$anlageSensors[$i]->getNameShort()] : null;
                            }
                            $gmPyEastAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            if (array_key_exists($date, $sensors)) {
                                if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                    if (array_key_exists($anlageSensors[$i]->getVcomAbbr(), $sensors[$date][$anlageSensors[$i]->getVcomId()])) {
                                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                            $gmPyEast[] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                                        }
                                        $gmPyEastAnlage[$anlageSensors[$i]->getNameShort()] = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if($plantId == 233 || $plantId == 232){
                $irrUpper = $gmPyEast[0] + $gmPyEast[1];
                #echo "$date /". $gmPyEast[0] .' / '.  $gmPyEast[1]. ' / '.$irrUpper."<br>";
            }else{
                $irrUpper = $this->mittelwert($gmPyEast);
            }

            $result[0] = [
                'irrHorizontal' => $this->mittelwert($gmPyHori),
                'irrHorizontalAnlage' => $gmPyHoriAnlage,
                'irrLower' => 0,
                'irrLowerAnlage' => [],
                'irrUpper' => $irrUpper,
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
                    if($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                            $tempModule[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }
                        $tempAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                    } else {
                        if (array_key_exists($date, $sensors)) {
                            if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                if (array_key_exists($anlageSensors[$i]->getVcomAbbr() , $sensors[$date][$anlageSensors[$i]->getVcomId()])) {
                                    if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                        $tempModule[] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                                    }
                                    $tempAnlage[$anlageSensors[$i]->getNameShort()] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                                }
                            }
                        }
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
                    if($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                        if (array_key_exists($anlageSensors[$i]->getNameShort(), $basics[$date])) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $tempAmbientArray[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $tempAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }
                    } else {
                        if (array_key_exists($date, $sensors)) {
                            if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                if (array_key_exists($anlageSensors[$i]->getVcomAbbr(), $sensors[$date][$anlageSensors[$i]->getVcomId()])) {
                                    if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                        $tempAmbientArray[] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                                    }
                                    $tempAnlage[$anlageSensors[$i]->getNameShort()] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                                }
                            }
                        }
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
                    if($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                        if (array_key_exists($anlageSensors[$i]->getNameShort(), $basics[$date])) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $windSpeedEWS[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $windAnlage[$anlageSensors[$i]->getNameShort()] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }
                    }else{
                        if (array_key_exists($date, $sensors)) {
                            if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                if (array_key_exists($anlageSensors[$i]->getVcomAbbr(), $sensors[$date][$anlageSensors[$i]->getVcomId()])) {
                                    if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                        $windSpeedEWS[] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                                    }
                                    $windAnlage[$anlageSensors[$i]->getNameShort()] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                                }
                            }
                        }
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
                            if ($anlageSensors[$i]->getUseToCalc() == 1 && array_key_exists($date, $basics)) {
                                $windDirectionEWD[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $windAnlage[$anlageSensors[$i]->getNameShort(
                            )] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            if (array_key_exists($date, $sensors)) {
                                if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                    $windDirectionEWD[] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                                }
                                $windAnlage[$anlageSensors[$i]->getNameShort()] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                            }
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
     * @param array $basics
     * @param  $stamp
     * @param  $date
     * @param string $gMo
     * @param bool $isDay
     * @return array
     */
    function getSensorsDataFromVcomResponse(array $anlageSensors, int $length, array $sensors, array $basics, $stamp, $date, string $gMo, bool $isDay): array
    {
        $gmx = 0;
        $value = '';
        $sensorId = null;
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
                $sensortype = $anlageSensors[$i]->getType();
                if ($anlageSensors[$i]->getName() != 'G_MX'){
                    if($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)){
                        if ($sensortype == 'temperature'){
                            $value = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            $value = max($basics[$date][$anlageSensors[$i]->getNameShort()], 0);
                        }
                    } else {
                        if (array_key_exists($date, $sensors)) {
                            if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                if (array_key_exists($anlageSensors[$i]->getVcomAbbr(), $sensors[$date][$anlageSensors[$i]->getVcomId()])) {
                                    if ($sensortype == 'temperature') {
                                        $value = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()];
                                    } else {
                                        $value = max($sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()], 0);
                                    }
                                }
                            }
                        }
                    }
                    if ($sensortype == 'pyranometer' && !$isDay){
                        $value = 0;
                    }
                } else {
                    if (array_key_exists($date, $basics)) {
                        $value = $basics[$date]['G_M' . $gmx];
                    }
                    $gmx++;
                }
            }

            if($sensorId !== null){
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
     * @param array|null $stringBoxesTime
     * @param array $acGroups
     * @param array $inverters
     * @param string $date
     * @param int $plantId
     * @param string $stamp
     * @param float $eZEvu
     * @param string $irrAnlage
     * @param string $tempAnlage
     * @param string $windAnlage
     * @param object $groups
     * @param int $stringBoxUnits
     * @return array
     * @throws \JsonException
     */
    function loadDataWithStringboxes(?array $stringBoxesTime, array $acGroups, array $inverters, string $date, int $plantId, string $stamp, float $eZEvu, string $irrAnlage, string $tempAnlage, string $windAnlage, object $groups, int $stringBoxUnits): array
    {
        for ($i = 0; $i <= count($acGroups)-1; $i++) {

            $pvpGroupAc = $acGroups[$i]['group_ac'];
            $pvpGroupDc = $i+1;
            $pvpInverter = $acGroups[$i]['group_ac'];

            $powerAc = $currentAc = $voltageAc = $powerDc = $voltageDc = $currentDc = $temp = null;
            $cosPhi = $blindLeistung = $frequenze = $currentAcP1 = $currentAcP2 = $currentAcP3 = $voltageAcP1 = $voltageAcP2 = $voltageAcP3 = null;

            if (array_key_exists($date, $inverters)) {
                $custInverterKennung = $acGroups[$i]['importId'];
                if (is_array($inverters[$date]) && array_key_exists($custInverterKennung, $inverters[$date])) {
                    $currentDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_DC']);
                    $currentAc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC']);
                    $currentAcP1 = array_key_exists('I_AC1', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC1']) : null;
                    $currentAcP2 = array_key_exists('I_AC2', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC2']) : null;
                    $currentAcP3 = array_key_exists('I_AC3', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC3']) : null;
                    $voltageDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['U_DC']);
                    $voltageAc = NULL;
                    $voltageAcP1 = array_key_exists('U_AC_L1L2', $inverters[$date][$custInverterKennung]) ? $inverters[$date][$custInverterKennung]['U_AC_L1L2'] : null;
                    $voltageAcP2 = array_key_exists('U_AC_L2L3', $inverters[$date][$custInverterKennung]) ? $inverters[$date][$custInverterKennung]['U_AC_L2L3'] : null;
                    $voltageAcP3 = array_key_exists('U_AC_L3L1', $inverters[$date][$custInverterKennung]) ? $inverters[$date][$custInverterKennung]['U_AC_L3L1'] : null;
                    $blindLeistung = array_key_exists('Q_AC', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['Q_AC']) : null;
                    $frequenze = array_key_exists('F_AC', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['F_AC']) : null;
                    $powerAc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['P_AC'], true); // Umrechnung von Watt auf kWh

                    $wrTempArray['T_WR'] = array_key_exists('T_WR', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR']): null;
                    $wrTempArray['T_WR1'] = array_key_exists('T_WR1', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR1']): null;
                    $wrTempArray['T_WR2'] = array_key_exists('T_WR2', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR2']): null;
                    $wrTempArray['T_WR3'] = array_key_exists('T_WR3', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR3']): null;
                    $wrTempArray['T_WR4'] = array_key_exists('T_WR4', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR4']): null;
                    $temp = $this->mittelwert($wrTempArray);
                    unset($wrTempArray);

                    $cosPhi = array_key_exists('COS_PHI', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['COS_PHI']) : null;
                    if (is_numeric($currentDc) && is_numeric($voltageDc)) {
                        $powerDc = $currentDc * $voltageDc / 1000 / 4;
                    } else {
                        $powerDc = '';
                    }
                }
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
                if (array_key_exists($scbNo, $stringBoxesTime) && array_key_exists($key, $stringBoxesTime[$scbNo])) {
                    $dcCurrentMppArray[$key] = $stringBoxesTime[$scbNo][$key];
                    $currentDcSCB += $stringBoxesTime[$scbNo][$key];
                }
            }

            $voltageDc = array_key_exists($scbNo, $stringBoxesTime) && array_key_exists('U_DC', $stringBoxesTime[$scbNo]) ? $stringBoxesTime[$scbNo]['U_DC'] : null;

            if (array_key_exists($scbNo, $stringBoxesTime) && array_key_exists('P_DC', $stringBoxesTime[$scbNo])){
                $powerDc = $stringBoxesTime[$scbNo]['P_DC'] / 1000 / 4; // Umrechnung von W auf kW/h
            } else {
                $powerDc = $currentDcSCB * $voltageDc / 1000 / 4;
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
                    'I_value' => array_key_exists($scbNo, $stringBoxesTime) && array_key_exists($key, $stringBoxesTime[$scbNo]) ? $stringBoxesTime[$scbNo][$key] : null,
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
     * @param string $irrAnlage
     * @param string $tempAnlage
     * @param string $windAnlage
     * @param object $groups
     * @param int $invertersUnits
     * @return array
     * @throws \JsonException
     */
    function loadData(array $inverters, string $date, int $plantId, string $stamp, float $eZEvu, string $irrAnlage, string $tempAnlage, string $windAnlage, object $groups, int $invertersUnits): array
    {

        foreach ($groups as $group) {

            $pvpInverter = $group->getDcGroup();
            $pvpGroupDc = $group->getDcGroup();
            $pvpGroupAc = $group->getAcGroup();

            $powerAc = $currentAc = $voltageAc = $powerDc = $voltageDc = $currentDc = $temp = null;
            $cosPhi = $blindLeistung = $frequenze = $currentAcP1 = $currentAcP2 = $currentAcP3 = $voltageAcP1 = $voltageAcP2 = $voltageAcP3 = null;
            $dcCurrentMpp = $dcVoltageMpp = '{}';

            if (array_key_exists($date, $inverters)) {
                $custInverterKennung = $group->getImportId();
                if (is_array($inverters[$date]) && array_key_exists($custInverterKennung, $inverters[$date])) {
                    $dcCurrentMppArray = [];
                    $dcVoltageMppArray = [];
                    $currentDc = 0;
                    $voltageDc = 0;
                    $voltageDcTemp = [];
                    if ($invertersUnits >= 1) {
                        for ($n = 1; $n <= $invertersUnits; $n++) {
                            $key = "I_DC$n";
                            if (array_key_exists($key, $inverters[$date][$custInverterKennung])) {
                                $dcCurrentMppArray[$key] = $inverters[$date][$custInverterKennung][$key] * 4;
                                if($dcCurrentMppArray[$key] != null){
                                    $currentDc = $currentDc + $inverters[$date][$custInverterKennung][$key];
                                }
                            }
                        }
                        $dcCurrentMpp = json_encode($dcCurrentMppArray, JSON_THROW_ON_ERROR);

                        for ($n = 1; $n <= $invertersUnits; $n++) {
                            $key = "U_DC$n";;
                            if (array_key_exists($key, $inverters[$date][$custInverterKennung])) {
                                $dcVoltageMppArray[$key] = $inverters[$date][$custInverterKennung][$key] * 4;
                                if($dcVoltageMppArray[$key] != null){
                                    $voltageDcTemp[] = $inverters[$date][$custInverterKennung][$key];
                                }
                            }
                        }

                        $dcVoltageMpp = json_encode($dcVoltageMppArray, JSON_THROW_ON_ERROR);
                    }

                    #print_r($voltageDcTemp);

                    if(count($voltageDcTemp) > 0){
                        $voltageDc = $this->mittelwert($voltageDcTemp);
                    }

                    if(array_key_exists('I_DC', $inverters[$date][$custInverterKennung])){
                        $currentDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_DC']);
                    }
                    $currentAc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC']);
                    $currentAcP1 = array_key_exists('I_AC1', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC1']) : null;
                    $currentAcP2 = array_key_exists('I_AC2', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC2']) : null;
                    $currentAcP3 = array_key_exists('I_AC3', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC3']) : null;
                    if(array_key_exists('U_DC', $inverters[$date][$custInverterKennung])){
                        $voltageDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['U_DC']);
                    }
                    $voltageAc = NULL;
                    $voltageAcP1 = array_key_exists('U_AC_L1L2', $inverters[$date][$custInverterKennung]) ? $inverters[$date][$custInverterKennung]['U_AC_L1L2'] : null;
                    $voltageAcP2 = array_key_exists('U_AC_L2L3', $inverters[$date][$custInverterKennung]) ? $inverters[$date][$custInverterKennung]['U_AC_L2L3'] : null;
                    $voltageAcP3 = array_key_exists('U_AC_L3L1', $inverters[$date][$custInverterKennung]) ? $inverters[$date][$custInverterKennung]['U_AC_L3L1'] : null;
                    $blindLeistung = array_key_exists('Q_AC', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['Q_AC']) : null;
                    $frequenze = array_key_exists('F_AC', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['F_AC']) : null;
                    $powerAc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['P_AC'], true); // Umrechnung von Watt auf kWh

                    $wrTempArray['T_WR'] = array_key_exists('T_WR', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR']) : null;
                    $wrTempArray['T_WR1'] = array_key_exists('T_WR1', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR1']) : null;
                    $wrTempArray['T_WR2'] = array_key_exists('T_WR2', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR2']) : null;
                    $wrTempArray['T_WR3'] = array_key_exists('T_WR3', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR3']) : null;
                    $wrTempArray['T_WR4'] = array_key_exists('T_WR4', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR4']) : null;
                    $temp = $this->mittelwert($wrTempArray);
                    unset($wrTempArray);

                    $cosPhi = array_key_exists('COS_PHI', $inverters[$date][$custInverterKennung]) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['COS_PHI']) : null;
                    if (is_numeric($currentDc) && is_numeric($voltageDc)) {
                        $powerDc = $currentDc * $voltageDc / 1000 / 4;
                    } else {
                        $powerDc = '';
                    }

                    if(array_key_exists('P_DC', $inverters[$date][$custInverterKennung]) && $inverters[$date][$custInverterKennung]['P_DC'] > 0){
                        $powerDc = $inverters[$date][$custInverterKennung]['P_DC'] / 4000;
                    }
                }

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