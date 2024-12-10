<?php
namespace App\Helper;

require_once __DIR__.'/../../public/config.php';

use App\Entity\WeatherStation;
use PDO;
use phpseclib3\Math\PrimeField\Integer;

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
    function insertData($tableName = null, array $data = [], object $DBDataConnection = null): void
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
            $placeholder_group = substr(
                str_repeat($placeholder, count($values) / $count_columns),
                1
            );//(?,?,?),(?,?,?)...
            $into_columns = implode(',', array_keys($columns));//col1,col2,col3
            // this part is optional:
            $on_duplicate = [];
            foreach ($columns as $column => $row) {
                $on_duplicate[] = $column;
                $on_duplicate[] = $column;
            }
            $on_duplicateSQL = ' ON DUPLICATE KEY UPDATE' . vsprintf(
                    substr(str_repeat(', %s = VALUES(%s)', $count_columns), 1),
                    $on_duplicate
                );
            // execute query
            $sql = 'INSERT INTO ' . $tableName . ' (' . $into_columns . ') VALUES' . $placeholder_group . $on_duplicateSQL;
            $stmt = $DBDataConnection->prepare(
                $sql
            );//INSERT INTO towns (col1,col2,col3) VALUES(?,?,?),(?,?,?)... {ON DUPLICATE...}
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
     * @param array $basics
     * @param  $date
     * @param $plantId
     * @return array
     */
    function checkSensors(
        array $anlageSensors,
        int $length,
        bool $istOstWest,
        array $sensors,
        array $basics,
        $date,
        $plantId
    ): array {
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
                        $start = strtotime((string)$anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                    }
                    if ($anlageSensors[$i]->getEndDateSensor() != null) {
                        $end = strtotime((string)$anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                    }
                    $now = strtotime((string)$date);
                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if ($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $gmPyHori[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort(
                            )] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            if (array_key_exists($date, $sensors)) {
                                if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                    $gmPyHori[] = max(
                                        $sensors[$date][$anlageSensors[$i]->getVcomId(
                                        )][$anlageSensors[$i]->getVcomAbbr()],
                                        0
                                    );
                                }
                                $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort()] = max(
                                    $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()],
                                    0
                                );
                            }
                        }
                    }
                }

                if ($anlageSensors[$i]->getvirtualSensor() == 'irr-west') {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->getStartDateSensor() != null) {
                        $start = strtotime((string)$anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                    }
                    if ($anlageSensors[$i]->getEndDateSensor() != null) {
                        $end = strtotime((string)$anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                    }
                    $now = strtotime((string)$date);
                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if ($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $gmPyWest[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $gmPyWestAnlage[$anlageSensors[$i]->getNameShort(
                            )] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            if (array_key_exists($date, $sensors)) {
                                if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                    $gmPyWest[] = max(
                                        $sensors[$date][$anlageSensors[$i]->getVcomId(
                                        )][$anlageSensors[$i]->getVcomAbbr()],
                                        0
                                    );
                                }
                                $gmPyWestAnlage[$anlageSensors[$i]->getNameShort()] = max(
                                    $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()],
                                    0
                                );
                            }
                        }
                    }
                }

                if ($anlageSensors[$i]->getvirtualSensor() == 'irr-east') {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->getStartDateSensor() != null) {
                        $start = strtotime((string)$anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                    }
                    if ($anlageSensors[$i]->getEndDateSensor() != null) {
                        $end = strtotime((string)$anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                    }
                    $now = strtotime((string)$date);
                    if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                        if ($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $gmPyEast[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $gmPyEastAnlage[$anlageSensors[$i]->getNameShort(
                            )] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            if (array_key_exists($date, $sensors)) {
                                if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                    $gmPyEast[] = max(
                                        $sensors[$date][$anlageSensors[$i]->getVcomId(
                                        )][$anlageSensors[$i]->getVcomAbbr()],
                                        0
                                    );
                                }
                                $gmPyEastAnlage[$anlageSensors[$i]->getNameShort()] = max(
                                    $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr()],
                                    0
                                );
                            }
                        }
                    }
                }
            }

            $result[0] = [
                'irrHorizontal'       => $this->mittelwert($gmPyHori),
                'irrHorizontalAnlage' => $gmPyHoriAnlage,
                'irrLower'            => $this->mittelwert($gmPyWest),
                'irrLowerAnlage'      => $gmPyWestAnlage,
                'irrUpper'            => $this->mittelwert($gmPyEast),
                'irrUpperAnlage'      => $gmPyEastAnlage,
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
                                $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort(
                                )] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                        } else {
                            if (array_key_exists($date, $sensors)) {
                                if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                    if (array_key_exists(
                                        $anlageSensors[$i]->getVcomAbbr(),
                                        $sensors[$date][$anlageSensors[$i]->getVcomId()]
                                    )) {
                                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                            $gmPyHori[] = max(
                                                $sensors[$date][$anlageSensors[$i]->getVcomId(
                                                )][$anlageSensors[$i]->getVcomAbbr()],
                                                0
                                            );
                                        }
                                        $gmPyHoriAnlage[$anlageSensors[$i]->getNameShort()] = max(
                                            $sensors[$date][$anlageSensors[$i]->getVcomId(
                                            )][$anlageSensors[$i]->getVcomAbbr()],
                                            0
                                        );
                                    }
                                }
                            }
                        }
                    }
                }

                if ($anlageSensors[$i]->getvirtualSensor() == 'irr' || $anlageSensors[$i]->getvirtualSensor(
                    ) == 'irr-ground') {
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
                                $gmPyEast[] = array_key_exists(
                                    $anlageSensors[$i]->getNameShort(),
                                    $basics[$date]
                                ) ? $basics[$date][$anlageSensors[$i]->getNameShort()] : null;
                            }
                            $gmPyEastAnlage[$anlageSensors[$i]->getNameShort(
                            )] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            if (array_key_exists($date, $sensors)) {
                                if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                    if (array_key_exists(
                                        $anlageSensors[$i]->getVcomAbbr(),
                                        $sensors[$date][$anlageSensors[$i]->getVcomId()]
                                    )) {
                                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                            $gmPyEast[] = max(
                                                $sensors[$date][$anlageSensors[$i]->getVcomId(
                                                )][$anlageSensors[$i]->getVcomAbbr()],
                                                0
                                            );
                                        }
                                        $gmPyEastAnlage[$anlageSensors[$i]->getNameShort()] = max(
                                            $sensors[$date][$anlageSensors[$i]->getVcomId(
                                            )][$anlageSensors[$i]->getVcomAbbr()],
                                            0
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($plantId == 233 || $plantId == 232) {
                $irrUpper = $gmPyEast[0] + $gmPyEast[1];
            } else {
                $irrUpper = $this->mittelwert($gmPyEast);
            }

            $result[0] = [
                'irrHorizontal'       => $this->mittelwert($gmPyHori),
                'irrHorizontalAnlage' => $gmPyHoriAnlage,
                'irrLower'            => 0,
                'irrLowerAnlage'      => [],
                'irrUpper'            => $irrUpper,
                'irrUpperAnlage'      => $gmPyEastAnlage,
            ];
        }

        //mNodulTemp, ambientTemp, windSpeed
        $tempModule = $tempAmbientArray = $tempAnlage = $windDirectionEWD = $windSpeedEWS = $windAnlage = [];
        for ($i = 0; $i < $length; $i++) {
            if ($anlageSensors[$i]->getvirtualSensor() == 'temp-modul') {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]->getStartDateSensor() != null) {
                    $start = strtotime((string)$anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                }
                if ($anlageSensors[$i]->getEndDateSensor() != null) {
                    $end = strtotime((string)$anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                }
                $now = strtotime((string)$date);
                if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                    if ($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                        if ($anlageSensors[$i]->getUseToCalc() == 1) {
                            $tempModule[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }
                        $tempAnlage[$anlageSensors[$i]->getNameShort(
                        )] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                    } else {
                        if (array_key_exists($date, $sensors)) {
                            if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                if (array_key_exists(
                                    $anlageSensors[$i]->getVcomAbbr(),
                                    $sensors[$date][$anlageSensors[$i]->getVcomId()]
                                )) {
                                    if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                        $tempModule[] = $sensors[$date][$anlageSensors[$i]->getVcomId(
                                        )][$anlageSensors[$i]->getVcomAbbr()];
                                    }
                                    $tempAnlage[$anlageSensors[$i]->getNameShort(
                                    )] = $sensors[$date][$anlageSensors[$i]->getVcomId(
                                    )][$anlageSensors[$i]->getVcomAbbr()];
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
                    $start = strtotime((string)$anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                }
                if ($anlageSensors[$i]->getEndDateSensor() != null) {
                    $end = strtotime((string)$anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                }
                $now = strtotime((string)$date);
                if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                    if ($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                        if (array_key_exists($anlageSensors[$i]->getNameShort(), $basics[$date])) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $tempAmbientArray[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $tempAnlage[$anlageSensors[$i]->getNameShort(
                            )] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }
                    } else {
                        if (array_key_exists($date, $sensors)) {
                            if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                if (array_key_exists(
                                    $anlageSensors[$i]->getVcomAbbr(),
                                    $sensors[$date][$anlageSensors[$i]->getVcomId()]
                                )) {
                                    if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                        $tempAmbientArray[] = $sensors[$date][$anlageSensors[$i]->getVcomId(
                                        )][$anlageSensors[$i]->getVcomAbbr()];
                                    }
                                    $tempAnlage[$anlageSensors[$i]->getNameShort(
                                    )] = $sensors[$date][$anlageSensors[$i]->getVcomId(
                                    )][$anlageSensors[$i]->getVcomAbbr()];
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
                    $start = strtotime((string)$anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                }
                if ($anlageSensors[$i]->getEndDateSensor() != null) {
                    $end = strtotime((string)$anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                }
                $now = strtotime((string)$date);
                if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                    if ($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                        if (array_key_exists($anlageSensors[$i]->getNameShort(), $basics[$date])) {
                            if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                $windSpeedEWS[] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                            }
                            $windAnlage[$anlageSensors[$i]->getNameShort(
                            )] = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        }
                    } else {
                        if (array_key_exists($date, $sensors)) {
                            if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                if (array_key_exists(
                                    $anlageSensors[$i]->getVcomAbbr(),
                                    $sensors[$date][$anlageSensors[$i]->getVcomId()]
                                )) {
                                    if ($anlageSensors[$i]->getUseToCalc() == 1) {
                                        $windSpeedEWS[] = $sensors[$date][$anlageSensors[$i]->getVcomId(
                                        )][$anlageSensors[$i]->getVcomAbbr()];
                                    }
                                    $windAnlage[$anlageSensors[$i]->getNameShort(
                                    )] = $sensors[$date][$anlageSensors[$i]->getVcomId(
                                    )][$anlageSensors[$i]->getVcomAbbr()];
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
                    $start = strtotime((string)$anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
                }
                if ($anlageSensors[$i]->getEndDateSensor() != null) {
                    $end = strtotime((string)$anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
                }
                $now = strtotime((string)$date);

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
                                    $windDirectionEWD[] = $sensors[$date][$anlageSensors[$i]->getVcomId(
                                    )][$anlageSensors[$i]->getVcomAbbr()];
                                }
                                $windAnlage[$anlageSensors[$i]->getNameShort(
                                )] = $sensors[$date][$anlageSensors[$i]->getVcomId()][$anlageSensors[$i]->getVcomAbbr(
                                )];
                            }
                        }
                    }
                }
            }
        }

        $result[1] = [
            'tempPanel'     => $this->mittelwert($tempModule),
            'tempAmbient'   => $this->mittelwert($tempAmbientArray),
            'anlageTemp'    => $tempAnlage,
            'windDirection' => $this->mittelwert($windDirectionEWD),
            'windSpeed'     => $this->mittelwert($windSpeedEWS),
            'anlageWind'    => $windAnlage,
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
    function getSensorsDataFromVcomResponse(
        array $anlageSensors,
        int $length,
        array $sensors,
        array $basics,
        $stamp,
        $date,
        string $gMo,
        bool $isDay
    ): array {
        $gmx = 0;
        $value = '';
        $sensorId = null;
        for ($i = 0; $i < $length; $i++) {
            $start = 0;
            $end = 0;

            if ($anlageSensors[$i]->getStartDateSensor() != null) {
                $start = strtotime((string)$anlageSensors[$i]->getStartDateSensor()->format('Y-m-d H:i:s'));
            }
            if ($anlageSensors[$i]->getEndDateSensor() != null) {
                $end = strtotime((string)$anlageSensors[$i]->getEndDateSensor()->format('Y-m-d H:i:s'));
            }
            $now = strtotime((string)$date);
            if (($now >= $start && ($end == 0 || $end >= $now)) || ($start == 0 && $end == 0)) {
                $sensorId = $anlageSensors[$i]->getId();
                $sensortype = $anlageSensors[$i]->getType();
                if ($anlageSensors[$i]->getName() != 'G_MX') {
                    if ($anlageSensors[$i]->getIsFromBasics() == 1 && array_key_exists($date, $basics)) {
                        if ($sensortype == 'temperature') {
                            $value = $basics[$date][$anlageSensors[$i]->getNameShort()];
                        } else {
                            $value = max($basics[$date][$anlageSensors[$i]->getNameShort()], 0);
                        }
                    } else {
                        if (array_key_exists($date, $sensors)) {
                            if (array_key_exists($anlageSensors[$i]->getVcomId(), $sensors[$date])) {
                                if (array_key_exists(
                                    $anlageSensors[$i]->getVcomAbbr(),
                                    $sensors[$date][$anlageSensors[$i]->getVcomId()]
                                )) {
                                    if ($sensortype == 'temperature') {
                                        $value = $sensors[$date][$anlageSensors[$i]->getVcomId(
                                        )][$anlageSensors[$i]->getVcomAbbr()];
                                    } else {
                                        $value = max(
                                            $sensors[$date][$anlageSensors[$i]->getVcomId(
                                            )][$anlageSensors[$i]->getVcomAbbr()],
                                            0
                                        );
                                    }
                                }
                            }
                        }
                    }
                    if ($sensortype == 'pyranometer' && !$isDay) {
                        $value = 0;
                    }
                } else {
                    if (array_key_exists($date, $basics)) {
                        $value = $basics[$date]['G_M' . $gmx];
                    }
                    $gmx++;
                }
            }

            if ($sensorId !== null) {
                $data_sensors[] = [
                    'date'      => $date,
                    'stamp'     => $stamp,
                    'id_sensor' => $sensorId,
                    'value'     => ($value != '') ? $value : 0,
                    'gmo'       => $gMo
                ];
            }
        }

        $result[] = $data_sensors;
        return $result;
    }

    /**
     * Prüft welche Anlagen für den Import via Symfony freigeschaltet sind
     *
     * @param object $conn
     * @return array
     */
    public function getPlantsImportReady(object $conn): array
    {
        $query = "SELECT `anlage_id` FROM `anlage_settings` where `symfony_import` = 1  ";
        $stmt = $stmt = $conn->query($query);
        return $stmt->fetchAll();
    }

    /**
     * importiert die Daten für Anlegen mit Stringboxes
     *
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
    function loadDataWithStringboxes(
        ?array $stringBoxesTime,
        array $acGroups,
        array $inverters,
        string $date,
        int $plantId,
        string $stamp,
        float $eZEvu,
        string $irrAnlage,
        string $tempAnlage,
        string $windAnlage,
        object $groups,
        int $stringBoxUnits
    ): array {
        for ($i = 0; $i <= count($acGroups) - 1; $i++) {
            $pvpGroupAc = $acGroups[$i]['group_ac'];
            $pvpGroupDc = $i + 1;
            $pvpInverter = $acGroups[$i]['group_ac'];

            $powerAc = $currentAc = $voltageAc = $powerDc = $voltageDc = $currentDc = $temp = null;
            $cosPhi = $blindLeistung = $frequenze = $currentAcP1 = $currentAcP2 = $currentAcP3 = $voltageAcP1 = $voltageAcP2 = $voltageAcP3 = null;

            if (array_key_exists($date, $inverters)) {
                $custInverterKennung = $acGroups[$i]['importId'];
                if (is_array($inverters[$date]) && array_key_exists($custInverterKennung, $inverters[$date])) {
                    $currentDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_DC']);
                    $currentAc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC']);
                    $currentAcP1 = array_key_exists(
                        'I_AC1',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC1']) : null;
                    $currentAcP2 = array_key_exists(
                        'I_AC2',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC2']) : null;
                    $currentAcP3 = array_key_exists(
                        'I_AC3',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC3']) : null;
                    $voltageDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['U_DC']);
                    $voltageAc = null;
                    $voltageAcP1 = array_key_exists(
                        'U_AC_L1L2',
                        $inverters[$date][$custInverterKennung]
                    ) ? $inverters[$date][$custInverterKennung]['U_AC_L1L2'] : null;
                    $voltageAcP2 = array_key_exists(
                        'U_AC_L2L3',
                        $inverters[$date][$custInverterKennung]
                    ) ? $inverters[$date][$custInverterKennung]['U_AC_L2L3'] : null;
                    $voltageAcP3 = array_key_exists(
                        'U_AC_L3L1',
                        $inverters[$date][$custInverterKennung]
                    ) ? $inverters[$date][$custInverterKennung]['U_AC_L3L1'] : null;
                    $blindLeistung = array_key_exists(
                        'Q_AC',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['Q_AC']) : null;
                    $frequenze = array_key_exists(
                        'F_AC',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['F_AC']) : null;
                    $powerAc = $this->checkIfValueIsNotNull(
                        $inverters[$date][$custInverterKennung]['P_AC'],
                        true
                    ); // Umrechnung von Watt auf kWh

                    $wrTempArray['T_WR'] = array_key_exists(
                        'T_WR',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR']) : null;
                    $wrTempArray['T_WR1'] = array_key_exists(
                        'T_WR1',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR1']) : null;
                    $wrTempArray['T_WR2'] = array_key_exists(
                        'T_WR2',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR2']) : null;
                    $wrTempArray['T_WR3'] = array_key_exists(
                        'T_WR3',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR3']) : null;
                    $wrTempArray['T_WR4'] = array_key_exists(
                        'T_WR4',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR4']) : null;
                    $temp = $this->mittelwert($wrTempArray);
                    unset($wrTempArray);

                    $cosPhi = array_key_exists(
                        'COS_PHI',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['COS_PHI']) : null;
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
                'anl_id'               => $plantId,
                'stamp'                => $stamp,
                'inv'                  => $pvpGroupAc,
                'group_dc'             => $pvpGroupDc,
                'group_ac'             => $pvpGroupAc,
                'unit'                 => $pvpInverter,
                'wr_num'               => $pvpInverter,
                'wr_idc'               => ($currentDc != '') ? $currentDc : null,
                'wr_pac'               => ($powerAc != '') ? $powerAc : null,
                'p_ac_blind'           => ($blindLeistung != '') ? $blindLeistung : null,
                'i_ac'                 => ($currentAc != '') ? $currentAc : null,
                'i_ac_p1'              => ($currentAcP1 != '') ? $currentAcP1 : null,
                'i_ac_p2'              => ($currentAcP2 != '') ? $currentAcP2 : null,
                'i_ac_p3'              => ($currentAcP3 != '') ? $currentAcP3 : null,
                'u_ac'                 => ($voltageAc != '') ? $voltageAc : null,
                'u_ac_p1'              => ($voltageAcP1 != '') ? $voltageAcP1 : null,
                'u_ac_p2'              => ($voltageAcP2 != '') ? $voltageAcP2 : null,
                'u_ac_p3'              => ($voltageAcP3 != '') ? $voltageAcP3 : null,
                'p_ac_apparent'        => 0,
                'frequency'            => ($frequenze != '') ? $frequenze : null,
                'wr_udc'               => ($voltageDc != '') ? $voltageDc : null,
                'wr_pdc'               => ($powerDc != '') ? $powerDc : null,
                'wr_temp'              => ($temp != '') ? $temp : null,
                'wr_cos_phi_korrektur' => ($cosPhi != '') ? $cosPhi : null,
                'e_z_evu'              => ($eZEvu != '') ? $eZEvu : null,
                'temp_corr'            => $tempCorr,
                'theo_power'           => $theoPower,
                'temp_cell'            => null,
                'temp_cell_multi_irr'  => null,
                'wr_mpp_current'       => $dcCurrentMpp,
                'wr_mpp_voltage'       => $dcVoltageMpp,
                'irr_anlage'           => $irrAnlage,
                'temp_anlage'          => $tempAnlage,
                'temp_inverter'        => $tempAnlage,
                'wind_anlage'          => $windAnlage,
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

            $voltageDc = array_key_exists($scbNo, $stringBoxesTime) && array_key_exists(
                'U_DC',
                $stringBoxesTime[$scbNo]
            ) ? $stringBoxesTime[$scbNo]['U_DC'] : null;

            if (array_key_exists($scbNo, $stringBoxesTime) && array_key_exists('P_DC', $stringBoxesTime[$scbNo])) {
                $powerDc = $stringBoxesTime[$scbNo]['P_DC'] / 1000 / 4; // Umrechnung von W auf kW/h
            } else {
                $powerDc = $currentDcSCB * $voltageDc / 1000 / 4;
            }

            $dcCurrentMpp = json_encode($dcCurrentMppArray, JSON_THROW_ON_ERROR);
            $dcVoltageMpp = "{}";

            $data_pv_dcist[] = [
                'anl_id'         => $plantId,
                'stamp'          => $stamp,
                'wr_group'       => $pvpGroupDc,
                'wr_num'         => $pvpInverter,
                'wr_idc'         => $currentDcSCB,
                'wr_udc'         => $voltageDc,
                'wr_pdc'         => $powerDc,
                'wr_temp'        => 0,
                'wr_mpp_current' => $dcCurrentMpp,
                'wr_mpp_voltage' => $dcVoltageMpp,
                'group_ac'       => $pvpGroupAc,
            ];

            for ($n = 1; $n <= $stringBoxUnits; $n++) {
                $key = "I$n";
                $data_db_string_pv[] = [
                    'anl_id'   => $plantId,
                    'stamp'    => $stamp,
                    'wr_group' => $pvpGroupDc,
                    'group_ac' => $pvpGroupAc,
                    'wr_num'   => $pvpInverter,
                    'channel'  => $n,
                    'I_value'  => array_key_exists($scbNo, $stringBoxesTime) && array_key_exists(
                        $key,
                        $stringBoxesTime[$scbNo]
                    ) ? $stringBoxesTime[$scbNo][$key] : null,
                    'U_value'  => null,
                ];
            }
        }

        $result[] = $data_pv_dcist;
        $result[] = $data_db_string_pv;

        return $result;
    }

    /**
     * importiert die Daten für Anlegen ohne Stringboxes
     *
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
    function loadData(
        array $inverters,
        string $date,
        int $plantId,
        string $stamp,
        float $eZEvu,
        string $irrAnlage,
        string $tempAnlage,
        string $windAnlage,
        object $groups,
        int $invertersUnits
    ): array {
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
                                if ($dcCurrentMppArray[$key] != null) {
                                    $currentDc = $currentDc + $inverters[$date][$custInverterKennung][$key];
                                }
                            }
                        }
                        $dcCurrentMpp = json_encode($dcCurrentMppArray, JSON_THROW_ON_ERROR);

                        for ($n = 1; $n <= $invertersUnits; $n++) {
                            $key = "U_DC$n";;
                            if (array_key_exists($key, $inverters[$date][$custInverterKennung])) {
                                $dcVoltageMppArray[$key] = $inverters[$date][$custInverterKennung][$key] * 4;
                                if ($dcVoltageMppArray[$key] != null) {
                                    $voltageDcTemp[] = $inverters[$date][$custInverterKennung][$key];
                                }
                            }
                        }

                        $dcVoltageMpp = json_encode($dcVoltageMppArray, JSON_THROW_ON_ERROR);
                    }

                    #print_r($voltageDcTemp);

                    if (count($voltageDcTemp) > 0) {
                        $voltageDc = $this->mittelwert($voltageDcTemp);
                    }

                    if (array_key_exists('I_DC', $inverters[$date][$custInverterKennung])) {
                        $currentDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_DC']);
                    }
                    $currentAc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC']);
                    $currentAcP1 = array_key_exists(
                        'I_AC1',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC1']) : null;
                    $currentAcP2 = array_key_exists(
                        'I_AC2',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC2']) : null;
                    $currentAcP3 = array_key_exists(
                        'I_AC3',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['I_AC3']) : null;
                    if (array_key_exists('U_DC', $inverters[$date][$custInverterKennung])) {
                        $voltageDc = $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['U_DC']);
                    }
                    $voltageAc = null;
                    $voltageAcP1 = array_key_exists(
                        'U_AC_L1L2',
                        $inverters[$date][$custInverterKennung]
                    ) ? $inverters[$date][$custInverterKennung]['U_AC_L1L2'] : null;
                    $voltageAcP2 = array_key_exists(
                        'U_AC_L2L3',
                        $inverters[$date][$custInverterKennung]
                    ) ? $inverters[$date][$custInverterKennung]['U_AC_L2L3'] : null;
                    $voltageAcP3 = array_key_exists(
                        'U_AC_L3L1',
                        $inverters[$date][$custInverterKennung]
                    ) ? $inverters[$date][$custInverterKennung]['U_AC_L3L1'] : null;
                    $blindLeistung = array_key_exists(
                        'Q_AC',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['Q_AC']) : null;
                    $frequenze = array_key_exists(
                        'F_AC',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['F_AC']) : null;
                    $powerAc = $this->checkIfValueIsNotNull(
                        $inverters[$date][$custInverterKennung]['P_AC'],
                        true
                    ); // Umrechnung von Watt auf kWh

                    $wrTempArray['T_WR'] = array_key_exists(
                        'T_WR',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR']) : null;
                    $wrTempArray['T_WR1'] = array_key_exists(
                        'T_WR1',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR1']) : null;
                    $wrTempArray['T_WR2'] = array_key_exists(
                        'T_WR2',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR2']) : null;
                    $wrTempArray['T_WR3'] = array_key_exists(
                        'T_WR3',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR3']) : null;
                    $wrTempArray['T_WR4'] = array_key_exists(
                        'T_WR4',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['T_WR4']) : null;
                    $temp = $this->mittelwert($wrTempArray);
                    unset($wrTempArray);

                    $cosPhi = array_key_exists(
                        'COS_PHI',
                        $inverters[$date][$custInverterKennung]
                    ) ? $this->checkIfValueIsNotNull($inverters[$date][$custInverterKennung]['COS_PHI']) : null;
                    if (is_numeric($currentDc) && is_numeric($voltageDc)) {
                        $powerDc = $currentDc * $voltageDc / 1000 / 4;
                    } else {
                        $powerDc = '';
                    }

                    if (array_key_exists(
                            'P_DC',
                            $inverters[$date][$custInverterKennung]
                        ) && $inverters[$date][$custInverterKennung]['P_DC'] > 0) {
                        $powerDc = $inverters[$date][$custInverterKennung]['P_DC'] / 4000;
                    }
                }
            }

            $theoPower = 0;
            $tempCorr = 0;

            $data_pv_ist[] = [
                'anl_id'               => $plantId,
                'stamp'                => $stamp,
                'inv'                  => $pvpGroupAc,
                'group_dc'             => $pvpGroupDc,
                'group_ac'             => $pvpGroupAc,
                'unit'                 => $pvpInverter,
                'wr_num'               => $pvpInverter,
                'wr_idc'               => ($currentDc != '') ? $currentDc : null,
                'wr_pac'               => ($powerAc != '') ? $powerAc : null,
                'p_ac_blind'           => ($blindLeistung != '') ? $blindLeistung : null,
                'i_ac'                 => ($currentAc != '') ? $currentAc : null,
                'i_ac_p1'              => ($currentAcP1 != '') ? $currentAcP1 : null,
                'i_ac_p2'              => ($currentAcP2 != '') ? $currentAcP2 : null,
                'i_ac_p3'              => ($currentAcP3 != '') ? $currentAcP3 : null,
                'u_ac'                 => ($voltageAc != '') ? $voltageAc : null,
                'u_ac_p1'              => ($voltageAcP1 != '') ? $voltageAcP1 : null,
                'u_ac_p2'              => ($voltageAcP2 != '') ? $voltageAcP2 : null,
                'u_ac_p3'              => ($voltageAcP3 != '') ? $voltageAcP3 : null,
                'p_ac_apparent'        => 0,
                'frequency'            => ($frequenze != '') ? $frequenze : null,
                'wr_udc'               => ($voltageDc != '') ? $voltageDc : null,
                'wr_pdc'               => ($powerDc != '') ? $powerDc : null,
                'wr_temp'              => ($temp != '') ? $temp : null,
                'wr_cos_phi_korrektur' => ($cosPhi != '') ? $cosPhi : null,
                'e_z_evu'              => ($eZEvu != '') ? $eZEvu : null,
                'temp_corr'            => $tempCorr,
                'theo_power'           => $theoPower,
                'temp_cell'            => null,
                'temp_cell_multi_irr'  => null,
                'wr_mpp_current'       => $dcCurrentMpp,
                'wr_mpp_voltage'       => $dcVoltageMpp,
                'irr_anlage'           => $irrAnlage,
                'temp_anlage'          => $tempAnlage,
                'temp_inverter'        => $tempAnlage,
                'wind_anlage'          => $windAnlage,
            ];
        }

        $result[] = $data_pv_ist;
        return $result;
    }

    /**
     * importiert die Daten für PPC
     *
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
                $p_set_gridop_rel = $this->checkIfValueIsNotNull(
                    $ppcs[$date][$anlagePpcs[0]['vcomId']]['PPC_P_SET_GRIDOP_REL']
                ); // Regelung durch Grid Operator
                $p_set_rel = $this->checkIfValueIsNotNull($ppcs[$date][$anlagePpcs[0]['vcomId']]['PPC_P_SET_REL']);#
                $p_set_rpc_rel = $this->checkIfValueIsNotNull(
                    $ppcs[$date][$anlagePpcs[0]['vcomId']]['PPC_P_SET_RPC_REL']
                ); // Regelung durch Direktvermarkter
            }

            $data_ppc[] = [
                'anl_id'              => $plantId,
                'anl_intnr'           => $anlagenTabelle,
                'stamp'               => $stamp,
                'p_ac_inv'            => $p_ac_inv,
                'q_ac_inv'            => null,
                'pf_set'              => $pf_set,
                'p_set_gridop_rel'    => ($p_set_gridop_rel != '') ? $p_set_gridop_rel : null,
                'p_set_rel'           => ($p_set_rel != '') ? $p_set_rel : null,
                'p_set_rpc_rel'       => ($p_set_rpc_rel != '') ? $p_set_rpc_rel : null,
                'q_set_rel'           => $q_set_rel,
                'p_set_ctrl_rel'      => $p_set_ctrl_rel,
                'p_set_ctrl_rel_mean' => $p_set_ctrl_rel_mean,
            ];
        }
        $result[] = $data_ppc;

        return $result;
    }

    function insertHuaweiData($groups, $data, $plantId): array
    {
        $i = 0;
        foreach ($groups as $group) {
            $pvpGroupDc = $group->getDcGroup();
            $pvpGroupAc = $group->getAcGroup();;
            $importId = $group->getImportId();
            $inv_name = $group->getDcGroupName();
            $unit_from = $group->getUnitFirst();
            $unit_to = $group->getUnitLast();
            $timestamp = time();
            $timeStampHR = date('Y-m-d H:i:00', $timestamp);
            $CND = '27'; #The for count
            $sumacPower = 0;
            $mkk = 'pv';

            if (is_array($data['data'][$i])) {
                $dataMap = $data['data'][$i]['dataItemMap'];

                $array_temp = [];

                for ($j = 1; $j <= $CND; ++$j) {
                    $pufinder = "$mkk$j" . "_u";  #Build a Finder for u
                    $pifinder = "$mkk$j" . "_i";  #Build a Finder for i
                    $dcvoltage += $dataMap[$pufinder];     #Volt
                    $dcelectricity += $dataMap[$pifinder]; #Amp

                    $array_temp[$pufinder] = $dataMap[$pufinder];
                    $array_temp[$pifinder] = $dataMap[$pifinder];

                    $dcPower += ($dataMap[$pufinder] * $dataMap[$pifinder]); #Watt
                    (($dataMap[$pufinder] <= 0.0) ? $dx++ : $dx);
                }

                $mwx = $CND - $dx; # Mittelwert Teiler aus nicht vorhandenen Records
                # mwx darf nicht 0 sein
                # der Mittelwert aus DCVoltage der einzelen Stränge
                (($mwx > 0) ? $dcvoltage = $dcvoltage / $mwx : $dcvoltage = $dcvoltage);

                $data_real = [
                    'PlantID'       => $plantId,
                    'DeviceID'      => $importId,
                    'DCGRP'         => $pvpGroupDc,
                    'DCcnd'         => $unit_to,
                    'INVName'       => $inv_name,
                    'ACPower'       => $dataMap['active_power'],
                    'DCPower'       => $dcPower,
                    'DCElectricity' => ($dcelectricity != '') ? $dcelectricity : null,
                    'DCVoltage'     => ($dcvoltage != '') ? $dcvoltage : null,
                    'Frequenze'     => ($dataMap['elec_freq'] != '') ? $dataMap['elec_freq'] : null,
                    'RePower'       => ($dataMap['reactive_power'] != '') ? $dataMap['reactive_power'] : null,
                    'MpptTCAP'      => ($dataMap['mppt_total_cap'] != '') ? $dataMap['mppt_total_cap'] : null,
                    'GridAB'        => ($dataMap['ab_u'] != '') ? $dataMap['ab_u'] : null,
                    'GridBC'        => ($dataMap['bc_u'] != '') ? $dataMap['bc_u'] : null,
                    'GridCA'        => ($dataMap['ca_u'] != '') ? $dataMap['ca_u'] : null,
                    'Ph_u1'         => ($dataMap['a_u'] != '') ? $dataMap['a_u'] : null,
                    'Ph_u2'         => ($dataMap['b_u'] != '') ? $dataMap['b_u'] : null,
                    'Ph_u3'         => ($dataMap['c_u'] != '') ? $dataMap['c_u'] : null,
                    'Ph_i1'         => ($dataMap['a_i'] != '') ? $dataMap['a_i'] : null,
                    'Ph_i2'         => ($dataMap['b_i'] != '') ? $dataMap['b_i'] : null,
                    'Ph_i3'         => ($dataMap['c_i'] != '') ? $dataMap['c_i'] : null,
                    'TStamp'        => ($timestamp != '') ? $timestamp : null,
                    'TimeStampHR'   => ($timeStampHR != '') ? $timeStampHR : null,
                    'TempAmb'       => ($dataMap['temperature'] != '') ? $dataMap['temperature'] : null,
                ];

                $array_final[] = array_merge($data_real, $array_temp);
                $array_final[$i]['StatusCode'] = ($dataMap['inverter_state'] != '') ? $dataMap['inverter_state'] : null;
            } else {
                echo 'Mist';
                exit;
            }

            $i++;
        }

        $result = $array_final;
        return $result;
    }

    function insertHuaweiDataEMI($sensors, $data, $plantId): array
    {
        $i = 0;
        foreach ($sensors as $sensor) {
            $sensorId = $sensor->getVcomId();
            $timestamp = time();
            $timeStampHR = date('Y-m-d H:i:00', $timestamp);
            #$timeStampSensor = date('Y-m-d H:i:00', $timestamp);

            if (is_array($data['data'][$i])) {
                $dataMap = $data['data'][$i]['dataItemMap'];

                $data_emi = [
                    'PlantID'        => $plantId,
                    'DeviceID'       => $sensorId,
                    'TotalRadiation' => $dataMap['radiant_line'],
                    'AmbientTemp'    => $dataMap['pv_temperature'],
                    'PanelTemp'      => $dataMap['temperature'],
                    'TStamp'         => ($timestamp != '') ? $timestamp : null,
                    'TimeStampHR'    => ($timeStampHR != '') ? $timeStampHR : null
                ];

                $array_final[] = $data_emi;
            } else {
                echo 'Mist';
                exit;
            }
            $i++;
        }

        $result = $array_final;
        return $result;
    }

    ############################################################
    # Collect Weather Data
    function writeinweatherdb($plantId, $anlageSensors, $useSensorsDataTable, $DBDataConnection)
    {
        $i = 1;
        $j = 1;
        $k = 1;

        $timestamp = time() - 900;
        $stamp = (string)date('Y-m-d H:i:00');

        $irrValueArray[0] = ['1'];
        $irrValueArray[1] = ['2'];
        #$irrValueArray[2] = ['3'];
        foreach ($anlageSensors as $sensor) {
            $sensorId = $sensor->getVcomId();
            $vSensor = $sensor->getvirtualSensor();
            if ($sensor->getvirtualSensor() == 'irr-east') {
                $irrValueArray[0][$i] = $sensorId;
            }
            if ($sensor->getvirtualSensor() == 'irr-west') {
                $irrValueArray[1][$j] = $sensorId;
                $j++;
            }

            $i++;
        }

        $irrValueArray[0][$j] = 'East';
        $irrValueArray[0][$j + 1] = 'upper';
        $irrValueArray[1][$j] = 'West';
        $irrValueArray[1][$j + 1] = 'lower';
        #$irrValueArray[2][$k] = 'Temp';
        #$irrValueArray[2][$k+1] = 'temp';

        foreach ($irrValueArray as $irrval) {
            $sqlbc = "Select STR_TO_DATE(TimeStampHR, '%Y-%m-%d %h:%i:%i') || substr('00' || ((cast(STR_TO_DATE(timestampHR, '%m') as int) / 15) * 15), -2) || ':00' period, count(*) counter, ID, TStamp ,timestampHR, avg(TotalRadiation) as irr, avg(AmbientTemp) as ATemp, avg(PanelTemp) as PTemp from RealTimeDataEMI WHERE `DeviceID` IN ('$irrval[1]','$irrval[2]','$irrval[3]') AND TStamp > $timestamp AND PlantID = $plantId group by period order by TStamp";
            #$sqlbc = "SELECT * FROM RealTimeDataEMI";

            $resultbc = $DBDataConnection->query($sqlbc);

            while ($rowbc = $resultbc->fetch(PDO::FETCH_ASSOC)) {
                if ($rowbc['counter'] >= 1) {
                    if ($irrval[5] == 'upper') {
                        $irrarray[$rowbc['period']][] = array(
                            "sqldate"     => $rowbc['period'],
                            "stampa"      => $rowbc['TStamp'],
                            "irr_upper"   => round($rowbc['irr'], 2),
                            "tempAmbient" => round($rowbc['ATemp'], 2),
                            "tempPanel"   => round($rowbc['PTemp'], 2)
                        );
                    }
                    if ($irrval[5] == 'lower') {
                        $irrarray[$rowbc['period']][] = array(
                            "sqldate"     => $rowbc['period'],
                            "stampa"      => $rowbc['TStamp'],
                            "irr_lower"   => round($rowbc['irr'], 2),
                            "tempAmbient" => round($rowbc['ATemp'], 2),
                            "tempPanel"   => round($rowbc['PTemp'], 2)
                        );
                    }
                    if ($irrval[5] == 'temp') {
                        $irrarray[$rowbc['period']][] = array(
                            "sqldate"     => $rowbc['period'],
                            "stampa"      => $rowbc['TStamp'],
                            "irr_lower"   => round($rowbc['irr'], 2),
                            "tempAmbient" => round($rowbc['ATemp'], 2),
                            "tempPanel"   => round($rowbc['PTemp'], 2)
                        );
                    }
                }
            }
        }


        #$tempPanel = 0;
        $windSpeed = 0;
        $irrHori = 0;

        foreach ($irrarray as $row => $val) {
            $xcount++;
            foreach ($irrarray[$row] as $inval) {
                if (array_key_exists("irr_upper", $inval)) {
                    $irr_upper = $inval["irr_upper"];
                }
                if (array_key_exists("irr_lower", $inval)) {
                    $irr_lower = $inval["irr_lower"];
                }
                $tempAmbient = $inval["tempAmbient"];
                $tempPanel = $inval["tempPanel"];
            }

            #  echo "-> ( $xcount ) -- $weatherDbIdent, $row, $irr_upper, $irr_lower, $tempPanel, $tempAmbient, $windSpeed, $irrHori \n";
            $dataws[] = [
                "anl_id"       => 0,
                "anl_intnr"    => 0,
                "stamp"        => $stamp,
                "at_avg"       => 0,
                "pt_avg"       => 0,
                "gi_avg"       => $irr_lower,
                "gmod_avg"     => $irr_upper,
                "g_upper"      => $irr_upper,
                "g_lower"      => $irr_lower,
                "g_horizontal" => 0,
                "temp_pannel"  => $tempPanel,
                "temp_ambient" => $tempAmbient,
                "rso"          => 0,
                "gi"           => 0,
                "wind_speed"   => $windSpeed,
                "irr_hori"     => $irrHori
            ];
            #  insertWeatherToWeatherDb($weatherDbIdent, $row, $irr_upper, $irr_lower, $tempPanel, $tempAmbient, $windSpeed, $irrHori);
        }
        if (is_array($dataws)) {
            $tableName = "db__pv_ws_CX$plantId";
            self::insertData($tableName, $dataws, $DBDataConnection);
        }

    }

    ######################################################
    # Collect Inverter Data
    function writeininverterdb(int $plantId, object $DBDataConnection, object $DBBaseConnection)
    {
        $timestamp = time() - 900;
        $stamp = (string)date('Y-m-d H:i:00');
        // Array mit Inverter ID's der API Erstellen aus Anlage SQL
        $pNormControlSum = 0;
        $api_id_delim = '';
        $sql = "SELECT `ac_group_id`,`ac_group_name`,`unit_first`,`unit_last` FROM `anlage_groups_ac` WHERE `anlage_id` = " . $plantId . ";";

        $result = $DBBaseConnection->query($sql);
        while ($row = $result->fetch(PDO::FETCH_OBJ)) {
            $acgp_id = $row->ac_group_id;
            $inv_name = $row->ac_group_name;
            $unit_from = $row->unit_first;
            $unit_to = $row->unit_last;
            $sql2 = "SELECT `import_id` FROM `anlage_groups` WHERE `anlage_id` = " . $plantId . " AND `unit_first` = " . $unit_from . ";";
            #$index = $row->dc_group;
            #$groupId = $row->id;
            #$sql2 = "SELECT * FROM pvp_base.anlage_group_modules WHERE anlage_group_id = $groupId;";
            $result2 = $DBBaseConnection->query($sql2);
            #$sumPNorm = 0;
            $api_id = '';
            while ($row2 = $result2->fetch(PDO::FETCH_OBJ)) {
                $api_id_delim .= $row2->import_id . ',';
                $api_id = $row2->import_id;
                #$sumPNorm += $row2->num_strings_per_unit * $row2->num_modules_per_string * $modules[$row2->module_type_id]->power;
            }
            $cnd_unit = ($unit_to - $unit_from) + 1;
            $importarray[$api_id] = [
                '' . $acgp_id . '',
                '' . $inv_name . '',
                '' . $unit_from . '',
                '' . $unit_to . '',
                '' . $cnd_unit . ''
            ];
            #$dcPNormPerInvereter[$index] = $sumPNorm;
            #$pNormControlSum += $sumPNorm;
        }
        // Delete the last entry
        $devIds = substr($api_id_delim, 0, -1);
        $d = 0;
        $CND = 27;
        $mkk = 'pvs';
        foreach ($importarray as $idkey => $valueArray) {
            $dcelectricity = $dcvoltage = 0;
            $devid = $idkey;

            $AC_gp = $importarray[$devid][0];    #AC Gruppe
            $INV_name = $importarray[$devid][1]; #INV Name
            $DC_gbv = $importarray[$devid][2]; #DC Gruppe von
            $DC_gbb = $importarray[$devid][3]; #DC Gruppe bis
            $DCcnd = $importarray[$devid][4];  #DC counter

            $sqlac = "Select STR_TO_DATE(TimeStampHR, '%Y-%m-%d %h:%i:%i') || substr('00' || ((cast(STR_TO_DATE(timestampHR, '%m') as int) / 15) * 15), -2) || ':00' period,
              count(*) counter, 
              ID, 
              TStamp,
              TimeStampHR, 
              avg(DCElectricity) as idc, 
              avg(DCVoltage) as udc, 
              avg(ACPower) as pac,  
              avg(DCPower) as pdc, 
              avg(TempAmb) as temamb, 
              avg(MpptTCAP) as mppttcap, 
              avg(GridAB) as gridab,  
              avg(GridBC) as gridbc, 
              avg(GridCA) as gridac, 
              avg(RePower) as repower, 
			  avg(Ph_u1) as ph_u1, 
			  avg(Ph_u2) as ph_u2, 
			  avg(Ph_u3) as ph_u3, 
			  avg(Ph_i1) as ph_i1, 
			  avg(Ph_i2) as ph_i2, 
			  avg(Ph_i3) as ph_i3, 
              Frequenze,
              avg(pv1_i) as pvs1_i,
              avg(pv2_i) as pvs2_i,
              avg(pv3_i) as pvs3_i,
              avg(pv4_i) as pvs4_i,
              avg(pv5_i) as pvs5_i,
              avg(pv6_i) as pvs6_i,
              avg(pv7_i) as pvs7_i,
              avg(pv8_i) as pvs8_i,
              avg(pv9_i) as pvs9_i,
              avg(pv10_i) as pvs10_i,
              avg(pv11_i) as pvs11_i,
              avg(pv12_i) as pvs12_i,
              avg(pv13_i) as pvs13_i,
              avg(pv14_i) as pvs14_i,
              avg(pv15_i) as pvs15_i,
              avg(pv16_i) as pvs16_i,
              avg(pv17_i) as pvs17_i,
              avg(pv18_i) as pvs18_i,
              avg(pv19_i) as pvs19_i,
              avg(pv20_i) as pvs20_i,
              avg(pv21_i) as pvs21_i,
              avg(pv22_i) as pvs22_i,
              avg(pv23_i) as pvs23_i,
              avg(pv24_i) as pvs24_i,
              avg(pv25_i) as pvs25_i,
              avg(pv26_i) as pvs26_i,
              avg(pv27_i) as pvs27_i,    
              avg(pv1_u) as pvs1_u,
              avg(pv2_u) as pvs2_u,
              avg(pv3_u) as pvs3_u,
              avg(pv4_u) as pvs4_u,
              avg(pv5_u) as pvs5_u,
              avg(pv6_u) as pvs6_u,
              avg(pv7_u) as pvs7_u,
              avg(pv8_u) as pvs8_u,
              avg(pv9_u) as pvs9_u,
              avg(pv10_u) as pvs10_u,
              avg(pv11_u) as pvs11_u,
              avg(pv12_u) as pvs12_u,
              avg(pv13_u) as pvs13_u,
              avg(pv14_u) as pvs14_u,
              avg(pv15_u) as pvs15_u,
              avg(pv16_u) as pvs16_u,
              avg(pv17_u) as pvs17_u,
              avg(pv18_u) as pvs18_u,
              avg(pv19_u) as pvs19_u,
              avg(pv20_u) as pvs20_u,
              avg(pv21_u) as pvs21_u,
              avg(pv22_u) as pvs22_u,
              avg(pv23_u) as pvs23_u,
              avg(pv24_u) as pvs24_u,
              avg(pv25_u) as pvs25_u,
              avg(pv26_u) as pvs26_u,
              avg(pv27_u) as pvs27_u,  
              StatusCode
              from RealTimeData WHERE `DeviceID` = '$devid' AND TStamp > $timestamp AND PlantID = $plantId group by period order by TStamp";
            #echo $sqlac;  exit;
            $resultac = $DBDataConnection->query($sqlac);
            if ($resultac == false) {
                echo "DEV -> Error in A fetch ";
                sleep(3);
                #endscript();
                ##
            } else {
                while ($rowac = $resultac->fetch(PDO::FETCH_ASSOC)) {
                    if ($rowac['counter'] >= 4) {
                        $x = 1;
                        $sqldate = (string)date('Y-m-d H:i:00');
                        $powerAc = round($rowac['pac'] / 4, 4);   #KW
                        $temp = round($rowac['temamb'], 4);# ändern in tempamb
                        $frequenze = $rowac['Frequenze'];
                        $blindLeistung = round($rowac['repower'], 4);
                        $irrAnlage = '{}';
                        $tempAnlage = '{}';
                        $tempInverter = "{}";
                        $windAnlage = '{}';
                        $cosPhi = '';
                        $eZEvu = '';
                        $currentAcP1 = round($rowac['ph_i1'], 4);
                        $currentAcP2 = round($rowac['ph_i2'], 4);
                        $currentAcP3 = round($rowac['ph_i3'], 4);
                        $voltageAcP1 = round($rowac['ph_u1'], 4);
                        $voltageAcP2 = round($rowac['ph_u2'], 4);
                        $voltageAcP3 = round($rowac['ph_u3'], 4);
                        $currentAc = 0;
                        $voltageAc = 0;
                        $pacapparent = '0';
                        $tempCorr = '1';
                        $theoPower = '0';
                        #DC
                        for ($DCgrp = $DC_gbv; $DCgrp <= $DC_gbb; ++$DCgrp) {
                            $pufinder = "$mkk$x" . "_u";  #Build a finder
                            $pifinder = "$mkk$x" . "_i";
                            $pvpGroupDc = $DCgrp;
                            $pvpInverter = $DCgrp;
                            $voltageDc = round($rowac[$pufinder], 4); #U
                            $currentDc = round($rowac[$pifinder], 4); #I
                            $voltageDcMath = round($rowac[$pufinder], 4); #U
                            $currentDcMath = round($rowac[$pifinder], 4); #I
                            $powerDc = round(($voltageDcMath * $currentDcMath) / 1000 / 4, 4); #P KWatt
                            $x++;

                            # $voltageDc = round($rowac['udc']  / 1000, 4); #
                            # $currentDc = round($rowac['idc']  / 1000, 4); #
                            # $powerDc = round($rowac['pdc'] / 4 / 1000, 4); #KW
                            # $dcCurrentMpp = trim(str_replace(',}', '}', $imppstrg));# Current Array {}
                            # $dcVoltageMpp = trim(str_replace(',}', '}', $umppstrg));# Voltage Array {}

                            $dcCurrentMppDummy = '{}';
                            $dcVoltageMppDummy = '{}';

                            $dataDC[] = [
                                "anl_id"         => $plantId,
                                "stamp"          => $stamp,
                                "wr_group"       => $pvpGroupDc,
                                "group_ac"       => $AC_gp,
                                "wr_num"         => $pvpGroupDc,
                                "wr_idc"         => $currentDc,
                                "wr_udc"         => $voltageDc,
                                "wr_pdc"         => $powerDc,
                                "wr_temp"        => $temp,
                                "wr_mpp_current" => $dcCurrentMppDummy,
                                "wr_mpp_voltage" => $dcVoltageMppDummy
                            ];
                        }
                        $dataAC[] = [
                            "anl_id"               => $plantId,
                            "stamp"                => $sqldate,
                            'inv'                  => $AC_gp,
                            "group_ac"             => $AC_gp,
                            "group_dc"             => $AC_gp,
                            "unit"                 => $AC_gp,
                            "wr_idc"               => $currentDc,
                            "wr_pac"               => $powerAc
                            ,
                            "i_ac"                 => $currentAc,
                            "i_ac_p1"              => $currentAcP1,
                            "i_ac_p2"              => $currentAcP2,
                            "i_ac_p3"              => $currentAcP3,
                            "u_ac"                 => $voltageAc,
                            "u_ac_p1"              => $voltageAcP1,
                            "u_ac_p2"              => $voltageAcP2
                            ,
                            "u_ac_p3"              => $voltageAcP3,
                            "p_ac_blind"           => $blindLeistung,
                            "p_ac_apparent"        => $pacapparent,
                            "frequency"            => $frequenze,
                            "wr_udc"               => $voltageDc,
                            "wr_pdc"               => $powerDc,
                            "wr_temp"              => $temp
                            ,
                            "wr_cos_phi_korrektur" => $cosPhi,
                            "e_z_evu"              => $eZEvu,
                            "temp_corr"            => $tempCorr,
                            "theo_power"           => $theoPower,
                            "wr_mpp_current"       => $dcCurrentMppDummy,
                            "wr_mpp_voltage"       => $dcVoltageMppDummy
                            ,
                            "irr_anlage"           => $irrAnlage,
                            "temp_anlage"          => $tempAnlage,
                            "temp_inverte"         => $tempInverter,
                            "wind_anlage"          => $windAnlage
                        ];
                    }
                }
            }
            $d++;
        }

        // Write the Database
        if (is_array($dataAC)) {
            $tableName = "db__pv_ist_CX$plantId";
            self::insertData($tableName, $dataAC, $DBDataConnection);
        }

        if (is_array($dataDC)) {
            $tableName = "db__pv_dcist_CX$plantId";
            self::insertData($tableName, $dataDC, $DBDataConnection);
        }


        echo "sssss<pre>";
        print_r($dataAC);
        echo "<pre>";
        #exit;
        // Write the Database

        // Delete all data how older than to day
        echo "DEV -> Delete OLD values from database A Sqlite <br>\n";
        $delRTD = $DBDataConnection->exec("DELETE FROM RealTimeData WHERE TStamp < '$stamp'");
        $delEMI = $DBDataConnection->exec("DELETE FROM RealTimeDataEMI WHERE TStamp < '$stamp'");
    }

}