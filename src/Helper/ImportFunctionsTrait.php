<?php
namespace App\Helper;

require_once __DIR__.'/../../public/config.php';

use App\Entity\Anlage;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use PDO;
use PDOException;
use Symfony\Component\Intl\Timezones;

trait ImportFunctionsTrait
{
    /**
     * @param string|null $dbdsn
     * @param string|null $dbusr
     * @param string|null $dbpass
     * @return PDO
     */
    public static function getPdoConnectionData(?string $dbdsn = null, ?string $dbusr = null, ?string $dbpass = null): PDO
    {
        // Config als Array
        // Check der Parameter wenn null dann nehme default Werte als fallback
        $config = [
            'database_dsn' => $dbdsn === null ? $_ENV["PLANT_DATABASE_URL"] : $dbdsn, // 'mysql:dbname=pvp_data;host=dedi6015.your-server.de'
            'database_user' => $dbusr === null ? 'pvpluy_2' : $dbusr,
            'database_pass' => $dbpass === null ? $_ENV["PLANT_DATABASE_PASSWORD"] : $dbpass,
        ];

        try {
            $pdo = new PDO(
                $config['database_dsn'],
                $config['database_user'],
                $config['database_pass'],
                [
                    PDO::ATTR_PERSISTENT => true
                ]
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Error!: '.$e->getMessage().'<br/>';
            exit;
        }

        return $pdo;
    }


    /**
     * Funktion g4nLog($meldung) zum schreiben eines Logfiles
     *
     * @param $meldung
     * @param $logfile
     */
    function g4nLog($meldung, $logfile = 'logfile')
    {
        if ($meldung) {
            $logdatei = fopen("./logs/" . $logfile . "-" . date("Y-m-d", time()) . ".txt", "a");
            fputs($logdatei, date("H:i:s", time()) . ' -- ' . $meldung . "\n");
            fclose($logdatei);
        }
    }


    //insert all data with one query
    function insertData($tableName, $data): void
    {
        // obtain column template
        $DBDataConnection = $this->getPdoConnectionData();
        $stmt = $DBDataConnection->prepare("SHOW COLUMNS FROM $tableName");
        $stmt->execute();
        $columns = [];
        $columns = array_fill_keys(array_values($stmt->fetchAll(PDO::FETCH_COLUMN)), null);
        unset($columns['db_id']);


        // multiple INSERT
        $rows = count($data);

        $j = 0;
        $i = 0;
        $rows = $rows - 1;
        while ($j <= $rows) {
            $values = array();
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
     * @param array $tempCorrParams // Parameter aus Anlage
     * @param float $tempCellTypeAvg // t_cell_avg
     * @param float|null $modulTemp // gemessene Modul Temperatur
     * @param float|null $gPOA // Strahlung
     * @param float $limitGPoa // limit der Strahlung (default: 0)
     * @return float
     */
    function tempCorrectionIEC(array $tempCorrParams, float $tempCellTypeAvg, ?float $modulTemp, ?float $gPOA, float $limitGPoa = 0): float
    {
        $gamma = $tempCorrParams['gamma'];
        $a = $tempCorrParams['a'];
        $b = $tempCorrParams['b'];
        $deltaTcnd = $tempCorrParams['deltaTcnd'];

        if ($gPOA !== null && $modulTemp !== null) {
            ($gPOA > $limitGPoa || $tempCellTypeAvg == 0) ? $tempCorrection = 1 - ($tempCellTypeAvg - $modulTemp) * $gamma / 100 : $tempCorrection = 1;
        } else {
            $tempCorrection = 0;
        }

        return $tempCorrection;
    }

    /**
     * @param array $tempCorrParams // Parameter aus Anlage
     * @param float $tempCellTypeAvg
     * @param float|null $windSpeed
     * @param float|null $airTemp
     * @param float|null $gPOA // Strahlung
     * @param float $limitGPoa // limit der Strahlung (default: 0)
     * @return float
     *
     * Temp Correction by NREL
     */
    function tempCorrection(array $tempCorrParams, float $tempCellTypeAvg, ?float $windSpeed, ?float $airTemp, ?float $gPOA, float $limitGPoa = 0): float
    {
        $gamma = $tempCorrParams['gamma'];
        $a = $tempCorrParams['a'];
        $b = $tempCorrParams['b'];
        $deltaTcnd = $tempCorrParams['deltaTcnd'];

        if ($windSpeed === null) {
            $windSpeed = 0;
        }

        if ($gPOA !== null && $windSpeed !== null && $airTemp !== null) {
            $tempModulBack = $gPOA * pow(M_E, $a + ($b * $windSpeed)) + $airTemp;
            $tempCell = $tempModulBack + ($gPOA / 1000) * $deltaTcnd;
            ($gPOA > $limitGPoa || $tempCellTypeAvg == 0) ? $tempCorrection = 1 - ($tempCellTypeAvg - $tempCell) * $gamma / 100 : $tempCorrection = 1;
        } else {
            $tempCorrection = 0;
        }

        return $tempCorrection;
    }

    /**
     * Calculation of temprature of cell (Tcell) according to NREL
     *
     * @param array $tempCorrParams
     * @param float|null $windSpeed
     * @param float|null $airTemp
     * @param float|null $gPOA
     * @return float|null
     */
    function tempCell(array $tempCorrParams, ?float $windSpeed, ?float $airTemp, ?float $gPOA): ?float
    {
        if (is_null($airTemp) || is_null($gPOA)) return null;
        if ($windSpeed < 0 || $windSpeed === null) $windSpeed = 0;

        $a = $tempCorrParams['a'];
        $b = $tempCorrParams['b'];
        $deltaTcnd = $tempCorrParams['deltaTcnd'];

        $tempModulBack = $gPOA * pow(M_E, $a + ($b * $windSpeed)) + $airTemp;

        return $tempModulBack + ($gPOA / 1000) * $deltaTcnd;
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
    function insertDataIntoGridMeterDay($anlagenID, $stamp, float $value)
    {
        $DBDataConnection = getPdoConnectionAnlage();

        $sql_sel_ins = "INSERT INTO anlage_grid_meter_day SET 
                    anlage_id = $anlagenID, stamp = '$stamp', grid_meter_value = $value 
                   ON DUPLICATE KEY UPDATE
                    grid_meter_value = $value";

        $DBDataConnection->exec($sql_sel_ins);
        $DBDataConnection = null;
    }

    //???
    /**
     * Schreibt Eintraege, in die Tabelle 'log'.
     * Stand: August 2021 - GSCH
     * @param $anlage_id
     * @param $created_at
     * @param $created_by
     * @param $type
     * @param $description
     * @param $stamp
     */
    function insertDataIntoLog($anlage_id, $created_at, $created_by, $type, $description, $stamp)
    {
        $DBBaseConnection = getPdoConnectionAnlage();
        $sql_insert = "INSERT INTO log SET 
                    anlage_id = $anlage_id, 
                    created_at = '$created_at', 
                    created_by = '$created_by',
                    type = '$type', 
                    description = '$description', 
                    stamp = '$stamp'
                   ON DUPLICATE KEY UPDATE 
                    anlage_id = '$anlage_id'";
        echo "Log: $sql_insert \n";
        $DBBaseConnection->exec($sql_insert);
        $DBBaseConnection = null;

    }


    /**
     * @param string|null $value
     * @param false $convertToKWH
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

    //Liest die Sensoren der Anlage aus dem Backend
    function getAnlageSensors($conn, string $anlId): array
    {
        $query = "SELECT * FROM pvp_base.anlage_sensors  WHERE anlage_id  = ".$anlId;
        $stmt = $conn->executeQuery($query);
        return $stmt->fetchAll();
    }

    function getACGroups($conn, string $anlId): array
    {
        $query = "SELECT * FROM `anlage_groups_ac` where `anlage_id` = ".$anlId;
        $stmt = $conn->executeQuery($query);
        return $stmt->fetchAll();
    }

    function checkSensors(array $anlageSensors, int $length, bool $istOstWest, $sensors, $date): array
    {
        if ($istOstWest) {
            $gmPyHori = [];
            $gmPyWest = [];
            $gmPyEast = [];
            $gmPyHori = $gmPyHoriAnlage = $gmPyWest = $gmPyWestAnlage = $gmPyEast = $gmPyEastAnlage = [];
            $result = [];
            for ($i = 0; $i < $length; $i++) {
                if ($anlageSensors[$i]['virtual_sensor'] == 'irr-hori' && $anlageSensors[$i]['use_to_calc'] == 1) {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]['start_date_sensor'] != null) {
                        $start = strtotime($anlageSensors[$i]['start_date_sensor']);
                    }
                    if ($anlageSensors[$i]['end_date_sensor'] != null) {
                        $end = strtotime($anlageSensors[$i]['end_date_sensor']);
                    }
                    $now = strtotime($date);
                    if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                        array_push($gmPyHori, max($sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']], 0));
                        $gmPyHoriAnlage[$anlageSensors[$i]['name_short']] = max($sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']], 0);
                    }

                }

                if ($anlageSensors[$i]['virtual_sensor'] == 'irr-west' && $anlageSensors[$i]['use_to_calc'] == 1) {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]['start_date_sensor'] != null) {
                        $start = strtotime($anlageSensors[$i]['start_date_sensor']);
                    }
                    if ($anlageSensors[$i]['end_date_sensor'] != null) {
                        $end = strtotime($anlageSensors[$i]['end_date_sensor']);
                    }
                    $now = strtotime($date);
                    if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                        array_push($gmPyWest, max($sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']], 0));
                        $gmPyWestAnlage[$anlageSensors[$i]['name_short']] = max($sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']], 0);
                    }

                }

                if ($anlageSensors[$i]['virtual_sensor'] == 'irr-east' && $anlageSensors[$i]['use_to_calc'] == 1) {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]['start_date_sensor'] != null) {
                        $start = strtotime($anlageSensors[$i]['start_date_sensor']);
                    }
                    if ($anlageSensors[$i]['end_date_sensor'] != null) {
                        $end = strtotime($anlageSensors[$i]['end_date_sensor']);
                    }
                    $now = strtotime($date);
                    if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                        array_push($gmPyEast, max($sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']], 0));
                        $gmPyEastAnlage[$anlageSensors[$i]['name_short']] = max($sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']], 0);
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
                if ($anlageSensors[$i]['virtual_sensor'] == 'irr-hori' && $anlageSensors[$i]['use_to_calc'] == 1) {

                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]['start_date_sensor'] != null) {
                        $start = strtotime($anlageSensors[$i]['start_date_sensor']);
                    }
                    if ($anlageSensors[$i]['end_date_sensor'] != null) {
                        $end = strtotime($anlageSensors[$i]['end_date_sensor']);
                    }
                    $now = strtotime($date);
                    if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                        array_push($gmPyHori, max($sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']], 0));
                        $gmPyHoriAnlage[$anlageSensors[$i]['name_short']] = max($sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']], 0);
                    }

                }

                if ($anlageSensors[$i]['virtual_sensor'] == 'irr' && $anlageSensors[$i]['use_to_calc'] == 1) {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]['start_date_sensor'] != null) {
                        $start = strtotime($anlageSensors[$i]['start_date_sensor']);
                    }
                    if ($anlageSensors[$i]['end_date_sensor'] != null) {
                        $end = strtotime($anlageSensors[$i]['end_date_sensor']);
                    }
                    $now = strtotime($date);
                    array_push($gmPyEast, max($sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']], 0));
                    $gmPyEastAnlage[$anlageSensors[$i]['name_short']] = max($sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']], 0);
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
        $tempModule = $tempAmbientArray = $tempAnlage = $windSpeedEWD = $windSpeedEWS = $windAnlage = [];
        for ($i = 0; $i < $length; $i++) {
            if ($anlageSensors[$i]['virtual_sensor'] == 'temp-modul' && $anlageSensors[$i]['use_to_calc'] == 1) {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]['start_date_sensor'] != null) {
                    $start = strtotime($anlageSensors[$i]['start_date_sensor']);
                }
                if ($anlageSensors[$i]['end_date_sensor'] != null) {
                    $end = strtotime($anlageSensors[$i]['end_date_sensor']);
                }
                $now = strtotime($date);
                if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                    array_push($tempModule, $sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']]);
                    $tempAnlage[$anlageSensors[$i]['name_short']] = $sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']];
                }

            }
            if ($anlageSensors[$i]['virtual_sensor'] == 'temp-ambient' && $anlageSensors[$i]['use_to_calc'] == 1) {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]['start_date_sensor'] != null) {
                    $start = strtotime($anlageSensors[$i]['start_date_sensor']);
                }
                if ($anlageSensors[$i]['end_date_sensor'] != null) {
                    $end = strtotime($anlageSensors[$i]['end_date_sensor']);
                }
                $now = strtotime($date);
                if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                    array_push($tempAmbientArray, $sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']]);
                    $tempAnlage[$anlageSensors[$i]['name_short']] = $sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']];
                }

            }
            if ($anlageSensors[$i]['virtual_sensor'] == 'wind-direction' && $anlageSensors[$i]['use_to_calc'] == 1) {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]['start_date_sensor'] != null) {
                    $start = strtotime($anlageSensors[$i]['start_date_sensor']);
                }
                if ($anlageSensors[$i]['end_date_sensor'] != null) {
                    $end = strtotime($anlageSensors[$i]['end_date_sensor']);
                }
                $now = strtotime($date);
                $x = (string)$anlageSensors[$i]['start_date_sensor'];
                $y = (string)$anlageSensors[$i]['end_date_sensor'];
                #echo "Sensor Start $date = $now /BE $x = $start \n\n";
                #echo "Sensor End $date = $now /BE $y = $end \n";
                if (($now >= $start && ($end == 0 || $now < $end)) || ($start == 0 && $end == 0)) {
                    array_push($windSpeedEWD, $sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']]);
                    $windAnlage[$anlageSensors[$i]['name_short']] = $sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']];
                }

            }
            if ($anlageSensors[$i]['virtual_sensor'] == 'wind-speed' && $anlageSensors[$i]['use_to_calc'] == 1) {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]['start_date_sensor'] != null) {
                    $start = strtotime($anlageSensors[$i]['start_date_sensor']);
                }
                if ($anlageSensors[$i]['end_date_sensor'] != null) {
                    $end = strtotime($anlageSensors[$i]['end_date_sensor']);
                }
                $now = strtotime($date);
                if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                    array_push($windSpeedEWS, $sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']]);
                    $windAnlage[$anlageSensors[$i]['name_short']] = $sensors[$date][$anlageSensors[$i]['vcom_id']][$anlageSensors[$i]['vcom_abbr']];
                }
            }
        }

        $result[1] = [
            'tempPanel' => $this->mittelwert($tempModule),
            'tempAmbient' => $this->mittelwert($tempAmbientArray),
            'anlageTemp' => $tempAnlage,
            'windDirection' => $this->mittelwert($windSpeedEWD),
            'windSpeed' => $this->mittelwert($windSpeedEWS),
            'anlageWind' => $windAnlage,
        ];

        return $result;

    }

    public function getPlantsImportReady($conn){
        $query = "SELECT `anlage_id` FROM `anlage_settings` where `symfony_import` = 1  ";
        $stmt = $conn->executeQuery($query);
        return $stmt->fetchAll();
    }

    function loadDataWithStringboxes($stringBoxesTime, $acGroups, $inverters, $date, $plantId, $stamp, $eZEvu, $irrAnlage, $tempAnlage, $windAnlage, $groups, $stringBoxUnits):array
    {
        $i = 0;
        foreach ($acGroups as $group_ac) {

            $pvpGroupAc = $acGroups[$i]->ac_group_id;
            $pvpGroupDc = $i + 1;
            $pvpInverter = $i + 1;

            if (is_array($inverters) && array_key_exists($date, $inverters)) {
                $custInverterKennung = $acGroups[$i]['import_id'];
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
                $temp = $this->mittelwert([$inverters[$date][$custInverterKennung]['T_WR1'], $inverters[$date][$custInverterKennung]['T_WR2'], $inverters[$date][$custInverterKennung]['T_WR3'], $inverters[$date][$custInverterKennung]['T_WR4']]);
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
            $i++;
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
                $currentDcSCB += ($stringBoxesTime[$scbNo][$key]);
            }

            $voltageDc = round($stringBoxesTime[$scbNo]['U_DC'], 4);
            $powerDc = round($currentDcSCB * $voltageDc / 1000 / 4, 4); // Umrechnung von W auf kW/h

            $dcCurrentMpp = json_encode($dcCurrentMppArray);
            $dcVoltageMpp = "{}";

            $data_pv_dcist[] = [
                'anl_id' => $plantId,
                'stamp' => $stamp,
                'wr_group' => $pvpGroupDc,
                'wr_num' => $pvpInverter,
                'wr_idc' => $currentDc,
                'wr_udc' => $voltageDc,
                'wr_pdc' => $powerDc,
                'wr_temp' => 0,
                'wr_mpp_current' => $dcCurrentMpp,
                'wr_mpp_voltage' => $dcVoltageMpp,
                'group_ac' => $pvpGroupAc,
            ];

        }
        $result[] = $data_pv_dcist;
        return $result;
    }

    function loadData($inverters, $date, $plantId, $stamp, $eZEvu, $irrAnlage, $tempAnlage, $windAnlage, $groups, $invertersUnits):array
    {
        $i = 0;
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
                $temp = $this->mittelwert([$inverters[$date][$custInverterKennung]['T_WR1'], $inverters[$date][$custInverterKennung]['T_WR2'], $inverters[$date][$custInverterKennung]['T_WR3'], $inverters[$date][$custInverterKennung]['T_WR4']]);
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
                if($invertersUnits >= 1){
                    for ($n = 1; $n <= $invertersUnits; $n++) {
                        $key = "I_DC$n";
                        $dcCurrentMppArray[$key] = $inverters[$date][$custInverterKennung][$key] * 4;
                    }
                    $dcCurrentMpp = json_encode($dcCurrentMppArray);

                    for ($n = 1; $n <= $invertersUnits; $n++) {
                        $key = "U_DC$n";
                        $dcVoltageMppArray[$key] = $inverters[$date][$custInverterKennung][$key] * 4;
                    }
                    $dcVoltageMpp = json_encode($dcVoltageMppArray);
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
            $i++;
        }
        $result[] = $data_pv_ist;
        return $result;
    }

    function getPpc($idPpc, $ppcs, $date, $stamp, $plantId, $anlagenTabelle){
        $p_ac_inv = $pf_set = $p_set_gridop_rel = $p_set_rel = null;
        $p_set_rpc_rel = $q_set_rel = $p_set_ctrl_rel = $p_set_ctrl_rel_mean = null;
        if (isset($ppcs[$date])) {
            $p_set_gridop_rel = $this->checkIfValueIsNotNull($ppcs[$date][$idPpc]['PPC_P_SET_GRIDOP_REL']); // Regelung durch Grid Operator
            $p_set_rel = $this->checkIfValueIsNotNull($ppcs[$date][$idPpc]['PPC_P_SET_REL']);#
            $p_set_rpc_rel = $this->checkIfValueIsNotNull($ppcs[$date][$idPpc]['PPC_P_SET_RPC_REL']); // Regelung durch Direktvermarkter
        }

        $data_ppc[] = [
            'anl_id' => $plantId,
            'anl_intnr' => $anlagenTabelle,
            'stamp' => $stamp,
            'p_ac_inv' => $p_ac_inv,
            'q_ac_inv' => NULL,
            'pf_set' => $pf_set,
            'p_set_gridop_rel' => $p_set_gridop_rel,
            'p_set_rel' => $p_set_rel,
            'p_set_rpc_rel' => $p_set_rpc_rel,
            'q_set_rel' => $q_set_rel,
            'p_set_ctrl_rel' => $p_set_ctrl_rel,
            'p_set_ctrl_rel_mean' => $p_set_ctrl_rel_mean,
        ];
        $result[] = $data_ppc;
        return $result;
    }
}