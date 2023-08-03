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

    //???
    function getDcPNormPerInvereter($conn, array $groups, array $modules): array
    {

        $dcPNormPerInvereter = [];
        $pNormControlSum = 0;

        for ($i = 0; $i <= count($groups)-1; $i++) {
            $index = $groups[$i]->getdcGroup();
            $groupId = $groups[$i]->getid();

            $query = "SELECT * FROM `anlage_group_modules` where `anlage_group_id` = $groupId  ";
            $stmt = $conn->executeQuery($query);
            $result = $stmt->fetchAll();
            $sumPNorm = 0;
            $power = 0;

            for ($k = 0; $k <= count($modules)-1; $k++) {
                if($modules[$k]->getId() == $result[0]['module_type_id']){
                    $power = $modules[$k]->getPower();
                }
            }
            $sumPNorm += $result[0]['num_strings_per_unit'] * $result[0]['num_modules_per_string'] * $power;


            $dcPNormPerInvereter[$index] = $sumPNorm;
            $pNormControlSum += $sumPNorm;
        }
        return $dcPNormPerInvereter;
    }

    //???
    /**
     * @param string|DateTime $dateTime
     * @return int
     */
    function calcYearOfOperation(DateTime $currentDate, DateTime $installationDate): int
    {
        $years = ($currentDate->getTimestamp() - $installationDate->getTimestamp()) / (60 * 60 * 24 * 356);
        #echo (int)$years.'<br>';

        return (int)$years; //(int)$currentDate->format('Y') - (int)$installationDate->format('Y'); // betriebsjahre;
    }


    /**
     * Funktion g4nTimeCET() um immer Winterzeit zu bekommen
     *
     * @return int
     */
    function g4nTimeCET()
    {
        if (date("I") == "1") {
            //wir haben Sommerzeit
            $_time = time() - 3600;
        } else {
            // wir haben Winterzeit
            $_time = time();
        }

        return $_time;
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

    /**
     * Prüft ob Ornder vorhanden, wenn NICHT vorhanden wir er angelegt
     *
     * @param $path
     * @param int $chmod
     *
     * @return bool
     */
    function exists_dir($path, $chmod = 0777)
    {
        if (!(is_dir($path) or is_file($path) or is_link($path)))
            return mkdir($path, $chmod);
        else
            return true;
    }

    /**
     * Erstellt eine Liste der Dateine im angegebenen Verzeichniss ($path), gefilter durch den Wert in $filter
     * In die Liste der Dateien werden nur die Dateien aufgenommen in denen der gesuchte Filter ($filter) enthalten ist.
     * @param $path
     * @param $filter
     * @param $order - Sortiere die zurückgegbene Liste
     *
     * @return array|boolean
     */
    function getDirectoryListing($path, string $filter = '.csv', bool $order = false)
    {
        $filelist = [];
        $dir = opendir($path);
        while (false !== ($file = readdir($dir))) {
            if (false !== stripos($file, $filter, 0)) {
                $filelist[] = $file;
            }
        }
        if (!empty($filelist)) {
            if ($order) natsort($filelist);
            return $filelist;
        }
        return false;
    }

    /**
     * @param $shellBefehl
     * @return mixed
     */
    function execShell($shellBefehl)
    {
        $shellBefehl = escapeshellcmd($shellBefehl);
        exec($shellBefehl, $nu);
        return $nu;
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
     * Umrechnung Globalstrahlung in Modulstrahlung
     * @param $breite
     * @param $laenge
     * @param DateTime $stamp (Zeitpunkt für den die Umrechnung erfolgen soll)
     * @param float|null $ghi (Globalstrahlung zu oben genantem Zeitpunkt)
     * @param float $bezugsmeridian
     * @param float $azimuthModul
     * @param float $neigungModul
     * @return float|null (Berechnete Modulstrahlung)
     */
    function Hglobal2Hmodul($breite, $laenge, DateTime $stamp, ?float $ghi = 0.0, float $bezugsmeridian = 15, float $azimuthModul = 180, float $neigungModul = 20): ?float
    {
        if ($ghi === null) {
            return null;
        }
        // $bezugsmeridian = 15;   muss auch aus Anlage kommen, Feld existiert aber noch nicht (kann man da aus breite / Länge berechnen?)
        // $azimuthModul = 180;    muss auch aus Anlage kommen Feld existiert aber noch nicht
        // $neigungModul = 20;     muss auch aus Anlage kommen Feld existiert aber noch nicht
        $limitAOI = deg2rad(78);

        $tag = $stamp->format('z');
        $tag++; // Tag um eins erhöhen, da Formel annimmt das der erste Tag im Jahr = 1 ist und nicht 0 wie format('z') zurück gibt
        $stunde = (integer)$stamp->format('G');
        $debug = false;

        if ($debug) echo "Tag: $tag | Stunde: $stunde<br>";
        $moz = (($laenge - $bezugsmeridian) / 15) + $stunde;
        $lo = deg2rad(279.3 + 0.9856 * $tag);
        $zgl = 0.1644 * SIN(2 * ($lo + deg2rad(1.92) * SIN($lo + deg2rad(77.3)))) - 0.1277 * SIN($lo + deg2rad(77.3));
        $woz = $moz + rad2deg($zgl) / 60;
        $stdWink = deg2rad(15 * ($woz - 12));
        $deklination = deg2rad((-23.45) * COS((2 * PI() / 365.25) * ($tag + 10)));
        if ($debug) echo "Deklination (rad): $deklination<br>";
        $sonnenhoehe = ASIN(SIN($deklination) * SIN(deg2rad($breite)) + COS($deklination) * COS(deg2rad($breite)) * COS($stdWink));
        $atheta = ASIN((-(COS($deklination) * SIN($stdWink))) / COS($sonnenhoehe));
        $azimuth = 180 - rad2deg($atheta);
        $zenitwinkel = 90 - rad2deg($sonnenhoehe);
        $aoi = 1 / COS(COS(deg2rad($zenitwinkel)) * COS(deg2rad($neigungModul)) + SIN(deg2rad($zenitwinkel)) * SIN(deg2rad($neigungModul)) * COS(deg2rad($azimuth - $azimuthModul)));
        ($aoi > $limitAOI) ? $aoiKorr = $limitAOI : $aoiKorr = $aoi;
        if ($debug) echo "Azimuth: $azimuth | Zenit: $zenitwinkel | AOI: $aoi<br>";
        $dayAngel = 6.283185 * ($tag - 1) / 365;
        $etr = 1370 * (1.00011 + 0.034221 * COS($dayAngel) + 0.00128 * SIN($dayAngel) + 0.000719 * COS(2 * $dayAngel) + 0.000077 * SIN(2 * $dayAngel));
        ($zenitwinkel < 80) ? $am = (1 / (COS(deg2rad($zenitwinkel)) + 0.15 / (93.885 - $zenitwinkel) ** 1.253)) : $am = 0;
        ($am > 0) ? $kt = $ghi / (COS(deg2rad($zenitwinkel)) * $etr) : $kt = 0.0;
        if ($debug) echo "ETR: $etr | AM: $am | KT: $kt<br>";
        $dniMod = 0.0;
        if ($kt > 0) {
            if ($kt >= 0.6) {
                $a = -5.743 + 21.77 * $kt - 27.49 * $kt ** 2 + 11.56 * $kt ** 3;
                $b = 41.4 - 118.5 * $kt + 66.05 * $kt ** 2 + 31.9 * $kt ** 3;
                $c = -47.01 + 184.2 * $kt - 222 * $kt ** 2 + 73.81 * $kt ** 3;
            } elseif ($kt < 0.6) {
                $a = 0.512 - 1.56 * $kt + 2.286 * $kt ** 2 - 2.222 * $kt ** 3;
                $b = 0.37 + 0.962 * $kt;
                $c = -0.28 + 0.932 * $kt - 2.048 * $kt ** 2;
            } else {
                $a = 0;
                $b = 0;
                $c = 0;
            }
            $dkn = $a + $b * EXP($c * $am);
            $knc = 0.886 - 0.122 * $am + 0.0121 * ($am) ** 2 - 0.000653 * ($am) ** 3 + 0.000014 * ($am) ** 4;
            if ($debug) echo "a: $a | b: $b | c: $c | dkn: $dkn | knc: $knc<br>";
            $dni = $etr * ($knc - $dkn);
            $dniMod = $dni * COS($aoiKorr);
            if ($debug) echo "DNI: $dni | DNImod: $dniMod<br>";
        }
        $diffusMod = $ghi - $dniMod;

        $gmod1 = $aoi * $dniMod + $diffusMod; // Modulstrahlung 1
        $iam = 1 - 0.05 * ((1 / COS($aoi) - 1));
        $gmod2 = $gmod1 - $iam; // Modulstrahlung 2
        if ($gmod2 < 0) $gmod2 = 0; // Negative Werte machen keinen Sinn
        if ($debug) echo "Stunde: $stunde Diffus: $diffusMod | Gmod1: $gmod1 | IAM: $iam | Gmod2: $gmod2 | GHI: $ghi<br>";

        return $gmod2;
    }


    //???
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
     * @param $array
     * @param $on
     * @param int $order
     *
     * @return array
     */
    function array_sort($array, $on, int $order = SORT_ASC): array
    {
        $new_array = array();
        $sortable_array = array();
        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }
            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }
            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }
        return array_values($new_array);
    }


    /**
     * @param array $content
     * @return string
     */
    function printArrayAsTable(array $content): string
    {
        $_html = "<style>table, th, td {border: 1px solid black; }</style>";
        $_html .= "<table>";
        $_counter = 0;
        foreach ($content as $key => $contentRow) {
            if ($_counter == 0) {
                $_html .= "<tr><th>Key</th>";
                foreach ($contentRow as $subkey => $subvalue) {
                    $_html .= '<th>' . substr($subkey, 0, 20) . '</th>';
                }
                $_html .= "</tr>";
            }
            $_html .= "<tr><td>$key</td>";
            foreach ($contentRow as $cell) {
                if (is_float($cell)) str_replace('.', ',', round($cell, 2));
                $_html .= "<td>" . $cell . "</td>";
            }
            $_html .= "</tr>";
            $_counter++;
        }
        $_html .= "</table><hr>";

        return $_html;
    }

    //???
    function insertDataIntoPPC_New($tableName, $data)
    {

// obtain column template
        $DBDataConnection = getPdoConnection();
        $stmt = $DBDataConnection->prepare("SHOW COLUMNS FROM $tableName");
        $stmt->execute();
        $columns = [];
        $columns = array_fill_keys(array_values($stmt->fetchAll(PDO::FETCH_COLUMN)), null);
        unset($columns['db_id']);

// multiple INSERT
        $rows = count($data);

        for ($j = 0; $j < $rows; $j++) {
            $values = array();
            for ($i = 0; $i < $rows; $i++) {
                // reset row
                $row = $columns;

                // now fill our row with data
                foreach ($row as $key => $value) {
                    $row[$key] = $data[$i][$key];
                }

                // build INSERT array
                foreach ($row as $value) {
                    $values[] = $value;
                }

                // avoid memory kill
                if ($i >= $rows) {
                    break;
                }
            }
            // build query
            $count_columns = count($columns);
            $placeholder = ',(' . substr(str_repeat(',?', $count_columns), 1) . ')';//,(?,?,?)
            $placeholder_group = substr(str_repeat($placeholder, count($values) / $count_columns), 1);//(?,?,?),(?,?,?)...
            $into_columns = implode(',', array_keys($columns));//col1,col2,col3
            // this part is optional:
            $on_duplicate = array();
            foreach ($columns as $column => $row) {
                $on_duplicate[] = $column;
                $on_duplicate[] = $column;
            }
            $on_duplicate = ' ON DUPLICATE KEY UPDATE' . vsprintf(substr(str_repeat(', %s = VALUES(%s)', $count_columns), 1), $on_duplicate);
            // execute query
            $stmt = $DBDataConnection->prepare('INSERT INTO ' . $tableName . ' (' . $into_columns . ') VALUES' . $placeholder_group . $on_duplicate);//INSERT INTO towns (col1,col2,col3) VALUES(?,?,?),(?,?,?)... {ON DUPLICATE...}
            $stmt->execute($values);
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


    /**
     * Datenimport der Sections Daten in die Tabelle db__pv_section_Anlagenname
     * Es werden 15 Min Werte importiert.<br>
     * Sollte für diese 15 Min schon ein Wert vorliegen wird dieser aktualisiert (stamp + section ist unique key).
     * Stand: August 2022 - MR
     *
     * @param $ident
     * @param $stamp
     * @param $section
     * @param null $acPower
     * @param null $dcPower
     * @param null $gridPower
     * @param null $theoPower
     * @param null $theoPowerFt
     * @param null $ftCorFactor
     * @param null $tempModul
     * @param null $tempModulNrel
     */
    function insertDataIntoSection($ident, $stamp, $section, $acPower = null, $dcPower = null, $gridPower = null, $theoPower = null, $theoPowerFt = null, $ftCorFactor = null, $tempModul = null, $tempModulNrel = null)
    {
        $DBDataConnection = getPdoConnection();

        $sql_sel_ins = "INSERT INTO db__pv_section_$ident SET 
					stamp = '$stamp', 
					`section` = '$section',
					ac_power = '$acPower',
					dc_power = '$dcPower',
					grid_power = '$gridPower',
					theo_power = '$theoPower',
					theo_power_ft = '$theoPowerFt',
					ft_cor_factor = '$ftCorFactor',
					temp_module = '$tempModul',
					temp_module_nrel = '$tempModulNrel'
                   ON DUPLICATE KEY UPDATE
                    ac_power = '$acPower',
					dc_power = '$dcPower',
					grid_power = '$gridPower',
					theo_power = '$theoPower',
					theo_power_ft = '$theoPowerFt',
					ft_cor_factor = '$ftCorFactor',
					temp_module = '$tempModul',
					temp_module_nrel = '$tempModulNrel'";

        $DBDataConnection->exec($sql_sel_ins);
        $DBDataConnection = null;
    }

    //????
    /**
     * @param $meteringPointId
     * @param $customName
     * @param $time
     * @param $list
     * @return mixed
     */
    function loadDataFromMetersForExport($meteringPointId, $customName, $time, $list): mixed
    {
        $metaDataNL = getMetaDataMeaserments((string)$meteringPointId, (int)date("Y", $time), (int)date("m", $time), (int)date("d", $time));

        if (array_key_exists('10280', $metaDataNL)) {
            $value = 0;
            for ($k = 0; $k < count($metaDataNL['10280']); $k++) {
                array_push($list, array(date("Y-m-d", $metaDataNL['10280'][$k]['timestamp']), date("G:i:s", $metaDataNL['10280'][$k]['timestamp']), $metaDataNL['10280'][$k]['value'], $customName));

            }
        }
        return $list;
    }

    /**
     * Erzeuge Mittelwert aus den übergebenen Werten, Nutze nur Werte für den Mittelwert die gräßer 0 sind
     * @param array $werte
     * @param bool $ignoreZero
     * @return float|null
     */
    public function mittelwert(array $werte, bool $ignoreZero = true): ?float
    {
        $divisor = $divident = 0;
        foreach ($werte as $wert) {
            if ($ignoreZero) {
                if ((float)$wert !== 0.0 && $wert !== null) {
                    $divisor++;
                    $divident += (float)$wert;
                }
            } else {
                if ($wert !== null) {
                    $divisor++;
                    $divident += (float)$wert;
                }
            }
        }
        return ($divisor > 0) ? $divident / $divisor : null;
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

    //????
    /**
     * Kapt fehlehafte Spitzen fuer eZEvu und schreibt Eintrag, in die Tabelle 'log'
     * Stand: August 2021 - GSCH
     * @param $anlage
     * @param $stamp
     * @param $eZEvu
     * @param  $irrUpper
     * @return float
     * @deprecated
     */
    function correcktE_Z_EVUSpikes($anlage, $stamp, $eZEvu, $irrUpper): float
    {
        if ($eZEvu > $anlage->power * $irrUpper / 1000) {
            $conn = getPdoConnection();
            $anlagenTabelle = $anlage->anl_intnr;
            $stampMinus15Minutes = date("Y-m-d h:i:s", (strtotime($stamp) - 900));
            $sql = "SELECT e_z_evu, irr_anlage FROM db__pv_ist_$anlagenTabelle WHERE stamp = '$stampMinus15Minutes' ORDER BY db_id DESC LIMIT 1";
            #echo $sql."\n";
            $result = $conn->query($sql);
            $istDatas = $result->fetchAll(PDO::FETCH_OBJ);
            #echo json_decode($istDatas[0]->irr_anlage, true)['G_M0']."\n";
            $irrOld = json_decode($istDatas[0]->irr_anlage, true)['G_M0'];
            ($irrOld > 0) ? $factor = $irrUpper / $irrOld : $factor = 1;
            $eZEvuOrigin = $eZEvu;
            $eZEvu = $istDatas[0]->e_z_evu * $factor;
            $description = "
                The eZEvu is corrected because it was far out of range from the plant capacity.
                May be an failure in the sensor.
                Original value: $eZEvuOrigin
                New value: $eZEvu
                How is the new vlue calculated?
                We use the irradiation from the previous data set and compare it with the current value.
                Its ratio forms the factor with which the new value is calculated.
                Formula: new value = " . $eZEvuOrigin . " * (" . $irrUpper . "/" . json_decode($istDatas[0]->irr_anlage, true)['G_M0'] . ")
                ";
            if ($eZEvu > 0) {
                insertDataIntoLog($anlage->id, (new DateTime)->format("Y-m-d h:i:s"), 'cron', 'Correction imported value', $description, $stamp);
            }
            $conn = null;
        }

        return ($eZEvu);
    }

    /**
     * Opens a csv file, read the content and return the content as array
     *
     * @param String $csvFile
     * @param String $seperator
     * @return array
     */
    function csvhandle(string $csvFile, string $seperator = ','): array
    {
        #chmod($csvFile, 777);
        $handle = fopen($csvFile, "r");
        $doa = [];
        while ($data = fgetcsv($handle, 0, $seperator)) {
            $doa[] = $data;
        }
        fclose($handle);

        return $doa;
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

    //???
    function updatePvIst($stamp, $anlagenID, $anlagenTabelle, $eZEvu = '')
    {
        $DBDataConnection = getPdoConnection();
        $sql_sel_update = "update $anlagenTabelle set e_z_evu = $eZEvu WHERE anl_id = $anlagenID and stamp like '$stamp'";

        $DBDataConnection->exec($sql_sel_update);
        $DBDataConnection = null;
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

    function loadData($inverters, $date, $plantId, $stamp, $eZEvu, $irrAnlage, $tempAnlage, $windAnlage, $groups):array
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
                for ($n = 1; $n <= 9; $n++) {
                    $key = "I_DC$n";
                    $dcCurrentMppArray[$key] = $inverters[$date][$custInverterKennung][$key] * 4;
                }
                $dcCurrentMpp = json_encode($dcCurrentMppArray);

                $dcVoltageMppArray = [];
                for ($n = 1; $n <= 9; $n++) {
                    $key = "U_DC$n";
                    $dcVoltageMppArray[$key] = $inverters[$date][$custInverterKennung][$key] * 4;
                }
                $dcVoltageMpp = json_encode($dcVoltageMppArray);
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