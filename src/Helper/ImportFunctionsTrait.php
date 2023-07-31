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
    public static function getPdoConnection(?string $dbdsn = null, ?string $dbusr = null, ?string $dbpass = null): PDO
    {
        // Config als Array
        // Check der Parameter wenn null dann nehme default Werte als fallback
        $config = [
            'database_dsn' => $dbdsn === null ? $_ENV["PLANT_DATABASE_URL"] : $dbdsn, // 'mysql:dbname=pvp_data;host=dedi6015.your-server.de'
            'database_user' => $dbusr === null ? 'pvpluy_2' : $dbusr,
            'database_pass' => $dbpass === null ? 'XD4R5XyVHUkK9U5i' : $dbpass,
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


    function getGroupsFromAnlage(string $anlId): array
    {
        $conn = getPdoConnectionAnlage();

        $sql = "SELECT * FROM pvp_base.anlage_groups WHERE anlage_id = " . $anlId . " ORDER BY dc_group*1;";
        $result = $conn->query($sql);

        return $result->fetchAll(PDO::FETCH_OBJ);
    }

    function getDcPNormPerInvereter(array $groups, array $modules): array
    {
        $conn = getPdoConnectionAnlage();

        $dcPNormPerInvereter = [];
        $pNormControlSum = 0;

        foreach ($groups as $row) {
            $index = $row->dc_group;
            $groupId = $row->id;
            $sql2 = "SELECT * FROM pvp_base.anlage_group_modules WHERE anlage_group_id = $groupId;";
            $result2 = $conn->query($sql2);
            $sumPNorm = 0;
            while ($row2 = $result2->fetch(PDO::FETCH_OBJ)) {
                $sumPNorm += $row2->num_strings_per_unit * $row2->num_modules_per_string * $modules[$row2->module_type_id]->power;
            }
            $dcPNormPerInvereter[$index] = $sumPNorm;
            $pNormControlSum += $sumPNorm;
        }

        return $dcPNormPerInvereter;
    }

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

    /**
     * Neueste Version des Datenimports AC IST Daten, es werden berücksichtigt
     * e_z_evu (Zählerstand Einspeisung Stromversoreger), Strahlungsdaten aus Anlage (JSON), dc_group (DC Gruppe)
     * 'inv' für GruppeAC wird ersetzt durch ac_group (im Übergang werden beide Werte den selben Inhalt haben, später wird dann 'inv' nicht mehr genutzt)
     * ACHTUNG: Werte aus e_z_evu und irr_anlage stehen bei jedem eintrag, dürfen aber NUR EINMAL pro Zeiteinheit ausgewertet werden
     * Stand: August 2020 - MRE
     * @param $stamp
     * @param $invGrpAc
     * @param $invGrpDc
     * @param $invnr
     * @param $pacout
     * @param $pdcout
     * @param $udc
     * @param $idc
     * @param $temp
     * @param $anlagenID
     * @param $anlagenTabelle
     * @param int $cosPhiKorrektur
     * @param int $eZEvu
     * @param string $dcCurrentMpp
     * @param string $dcVoltageMpp
     * @param string $irrAnlage
     * @param string $tempAnlage
     * @param string $windAnlage
     * @param string $tempInverter
     * @param int $powerAcBlind
     * @param int $powerAcApparent
     */
    function insertDataIntoPvIstAcV3($stamp, $invGrpAc, $invGrpDc, $invnr, $pacout, $pdcout, $udc, $idc, $temp, $anlagenID, $anlagenTabelle,
                                     $cosPhiKorrektur = '1', $eZEvu = '', $dcCurrentMpp = "{}", $dcVoltageMpp = "{}", $irrAnlage = "{}", $tempAnlage = "{}", $windAnlage = "{}", $tempInverter = "{}", $powerAcBlind = '', $powerAcApparent = '')
    {
        $DBDataConnection = getPdoConnection();
        $sql_sel_ins = "INSERT INTO db__pv_ist_$anlagenTabelle SET 
                    anl_id = $anlagenID, 
                    stamp = '$stamp', 
                    group_ac = $invGrpAc, inv = $invGrpAc, 
                    group_dc = $invGrpDc,
                    unit = $invnr, 
                    wr_idc = '$idc', 
                    wr_pac = '$pacout', 
                    p_ac_blind = '$powerAcBlind', 
                    p_ac_apparent = '$powerAcApparent', 
                    wr_udc = '$udc', 
                    wr_pdc = '$pdcout', 
                    wr_temp = '$temp', 
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp', 
                    wr_cos_phi_korrektur = '$cosPhiKorrektur',
                    e_z_evu = '$eZEvu',
                    irr_anlage = '$irrAnlage',
                    temp_anlage = '$tempAnlage',
                    temp_inverter = '$tempInverter',
                    wind_anlage = '$windAnlage'             
                   ON DUPLICATE KEY UPDATE 
                    wr_idc = '$idc', 
                    wr_pac = '$pacout', 
                    wr_udc = '$udc', 
                    wr_pdc = '$pdcout', 
                    p_ac_blind = '$powerAcBlind', 
                    p_ac_apparent = '$powerAcApparent', 
                    wr_temp = '$temp', 
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp', 
                    wr_cos_phi_korrektur = '$cosPhiKorrektur',
                    e_z_evu = '$eZEvu',
                    irr_anlage = '$irrAnlage',
                    temp_anlage = '$tempAnlage',
                    temp_inverter = '$tempInverter',
                    wind_anlage = '$windAnlage'";
        $DBDataConnection->exec($sql_sel_ins);
        $DBDataConnection = null;
    }


    /**
     * Neueste Version des Datenimports AC IST Daten, es werden berücksichtigt
     * e_z_evu (Zählerstand Einspeisung Stromversoreger), Strahlungsdaten aus Anlage (JSON), dc_group (DC Gruppe)
     * 'inv' für GruppeAC wird ersetzt durch ac_group (im Übergang werden beide Werte den selben Inhalt haben, später wird dann 'inv' nicht mehr genutzt)
     * ACHTUNG: Werte aus e_z_evu und irr_anlage stehen bei jedem eintrag, dürfen aber NUR EINMAL pro Zeiteinheit ausgewertet werden
     * Stand: August 2020 - MRE
     * @param $stamp
     * @param $invGrpAc
     * @param $invGrpDc
     * @param $invnr
     * @param $pacout
     * @param $pdcout
     * @param $udc
     * @param $idc
     * @param $temp
     * @param $anlagenID
     * @param $anlagenTabelle
     * @param int $cosPhiKorrektur
     * @param int $eZEvu
     * @param string $dcCurrentMpp
     * @param string $dcVoltageMpp
     * @param string $irrAnlage
     * @param string $tempAnlage
     * @param string $windAnlage
     * @param string $tempInverter
     * @param int $powerAcBlind
     * @param int $powerAcApparent
     */
    function insertDataIntoPvIstAcV4($stamp, $invGrpAc, $invGrpDc, $invnr, $pacout, $pdcout, $udc, $idc, $temp, $anlagenID, $anlagenTabelle,
                                     $cosPhiKorrektur = '1', $eZEvu = '0', $dcCurrentMpp = "{}", $dcVoltageMpp = "{}", $irrAnlage = "{}", $tempAnlage = "{}", $windAnlage = "{}", $tempInverter = "{}", $powerAcBlind = '0', $powerAcApparent = '0')
    {
        $DBDataConnection = getPdoConnection();

        $sql_sel_ins = "INSERT INTO db__pv_ist_$anlagenTabelle SET 
                    anl_id = $anlagenID, 
                    stamp = '$stamp', 
                    group_ac = $invGrpAc, inv = $invGrpAc, 
                    group_dc = $invGrpDc,
                    unit = $invnr, 
                    wr_idc = '$idc', 
                    wr_pac = '$pacout', 
                    p_ac_blind = '$powerAcBlind', 
                    p_ac_apparent = '$powerAcApparent', 
                    wr_udc = '$udc', 
                    wr_pdc = '$pdcout', 
                    wr_temp = '$temp', 
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp', 
                    wr_cos_phi_korrektur = '$cosPhiKorrektur',
                    e_z_evu = '$eZEvu',
                    irr_anlage = '$irrAnlage',
                    temp_anlage = '$tempAnlage',
                    temp_inverter = '$tempInverter',
                    wind_anlage = '$windAnlage'            
                   ON DUPLICATE KEY UPDATE 
                    wr_idc = '$idc', 
                    wr_pac = '$pacout', 
                    wr_udc = '$udc', 
                    wr_pdc = '$pdcout', 
                    p_ac_blind = '$powerAcBlind', 
                    p_ac_apparent = '$powerAcApparent', 
                    wr_temp = '$temp', 
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp', 
                    wr_cos_phi_korrektur = '$cosPhiKorrektur',
                    e_z_evu = '$eZEvu',
                    irr_anlage = '$irrAnlage',
                    temp_anlage = '$tempAnlage',
                    temp_inverter = '$tempInverter',
                    wind_anlage = '$windAnlage'";
        $DBDataConnection->exec($sql_sel_ins);
        $DBDataConnection = null;
    }

    /**
     * Neueste Version des Datenimports AC IST Daten, es werden berücksichtigt
     * e_z_evu (Zählerstand Einspeisung Stromversoreger), Strahlungsdaten aus Anlage (JSON), dc_group (DC Gruppe)
     * 'inv' für GruppeAC wird ersetzt durch ac_group (im Übergang werden beide Werte den selben Inhalt haben, später wird dann 'inv' nicht mehr genutzt)
     * ACHTUNG: Werte aus e_z_evu und irr_anlage stehen bei jedem eintrag, dürfen aber NUR EINMAL pro Zeiteinheit ausgewertet werden
     * Stand: August 2020 - MRE
     */
    function insertDataIntoPvIstAcV41($stamp, $invGrpAc, $invGrpDc, $invnr, $pacout, $acCurrent, $acVoltage, $pdcout, $udc, $idc, $temp, $anlagenID, $anlagenTabelle,
                                      $cosPhiKorrektur = '1', $eZEvu = '0', $dcCurrentMpp = "{}", $dcVoltageMpp = "{}", $irrAnlage = "{}", $tempAnlage = "{}", $windAnlage = "{}",
                                      $tempInverter = "{}", $powerAcBlind = '0', $powerAcApparent = '0', $frequency = '0', $tempCorr = '1', $theoPower = '0', $pa0 = 0, $pa1 = 0, $pa2 = 0, $pa0Reason = '', $pa1Reason = '', $pa2Reason = '',
                                      $iAcP1 = '0', $iAcP2 = '0', $iAcP3 = '0', $uAcP1 = '0', $uAcP2 = '0', $uAcP3 = '0')
    {
        $DBDataConnection = getPdoConnection();

        $sql_sel_ins = "INSERT INTO db__pv_ist_$anlagenTabelle SET 
                    anl_id = $anlagenID, 
                    stamp = '$stamp', 
                    group_ac = $invGrpAc, inv = $invGrpAc, 
                    group_dc = $invGrpDc,
                    unit = $invnr, 
                    wr_idc = '$idc', 
                    wr_pac = '$pacout', 
                    i_ac = '$acCurrent',
                    i_ac_p1 = '$iAcP1',
                    i_ac_p2 = '$iAcP2',
                    i_ac_p3 = '$iAcP3',
                    u_ac = '$acVoltage',       
                    u_ac_p1 = '$uAcP1',
                    u_ac_p2 = '$uAcP2',
                    u_ac_p3 = '$uAcP3',      
                    p_ac_blind = '$powerAcBlind', 
                    p_ac_apparent = '$powerAcApparent', 
                    frequency = '$frequency',             
                    wr_udc = '$udc', 
                    wr_pdc = '$pdcout', 
                    wr_temp = '$temp', 
                    temp_corr = '$tempCorr',
                    theo_power = '$theoPower',             
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp', 
                    wr_cos_phi_korrektur = '$cosPhiKorrektur',
                    e_z_evu = '$eZEvu',
                    irr_anlage = '$irrAnlage',
                    temp_anlage = '$tempAnlage',
                    temp_inverter = '$tempInverter',
                    wind_anlage = '$windAnlage',
                    pa_0 = '$pa0',             
                    pa_1 = '$pa1',             
                    pa_2 = '$pa2',
                    pa_0_reason = '$pa0Reason',             
                    pa_1_reason = '$pa1Reason',             
                    pa_2_reason = '$pa2Reason'                
                   ON DUPLICATE KEY UPDATE 
                    wr_idc = '$idc', 
                    wr_pac = '$pacout', 
                    i_ac = '$acCurrent',
                    i_ac_p1 = '$iAcP1',
                    i_ac_p2 = '$iAcP2',
                    i_ac_p3 = '$iAcP3',
                    u_ac = '$acVoltage',       
                    u_ac_p1 = '$uAcP1',
                    u_ac_p2 = '$uAcP2',
                    u_ac_p3 = '$uAcP3',       
                    p_ac_blind = '$powerAcBlind', 
                    p_ac_apparent = '$powerAcApparent', 
                    frequency = '$frequency',             
                    wr_udc = '$udc', 
                    wr_pdc = '$pdcout', 
                    wr_temp = '$temp', 
                    temp_corr = '$tempCorr',
                    theo_power = '$theoPower',             
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp', 
                    wr_cos_phi_korrektur = '$cosPhiKorrektur',
                    e_z_evu = '$eZEvu',
                    irr_anlage = '$irrAnlage',
                    temp_anlage = '$tempAnlage',
                    temp_inverter = '$tempInverter',
                    wind_anlage = '$windAnlage',
                    pa_0 = '$pa0',             
                    pa_1 = '$pa1',             
                    pa_2 = '$pa2',
                    pa_0_reason = '$pa0Reason',             
                    pa_1_reason = '$pa1Reason',             
                    pa_2_reason = '$pa2Reason';";
        $DBDataConnection->exec($sql_sel_ins);
        $DBDataConnection = null;
    }

    /**
     * Neueste Version des Datenimports AC IST Daten, es werden berücksichtigt
     * e_z_evu (Zählerstand Einspeisung Stromversoreger), Strahlungsdaten aus Anlage (JSON), dc_group (DC Gruppe)
     * 'inv' für GruppeAC wird ersetzt durch ac_group (im Übergang werden beide Werte den selben Inhalt haben, später wird dann 'inv' nicht mehr genutzt)
     * ACHTUNG: Werte aus e_z_evu und irr_anlage stehen bei jedem eintrag, dürfen aber NUR EINMAL pro Zeiteinheit ausgewertet werden
     * Stand: Mai 2022 - MRE
     */
    function insertDataIntoPvIstAcV42($stamp, $invGrpAc, $invGrpDc, $invnr, $pacout, $acCurrent, $acVoltage, $pdcout, $udc, $idc, $temp, $anlagenID, $anlagenTabelle,
                                      $cosPhi = null, $eZEvu = null, $dcCurrentMpp = "{}", $dcVoltageMpp = "{}", $irrAnlage = "{}", $tempAnlage = "{}", $windAnlage = "{}",
                                      $tempInverter = "{}", $powerAcBlind = null, $powerAcApparent = null, $frequency = null, $tempCorr = '1', $theoPower = '0', $pa0 = 0, $pa1 = 0, $pa2 = 0, $pa0Reason = '', $pa1Reason = '', $pa2Reason = '',
                                      $iAcP1 = null, $iAcP2 = null, $iAcP3 = null, $uAcP1 = null, $uAcP2 = null, $uAcP3 = null)
    {
        $DBDataConnection = getPdoConnection();

        $sql_sel_ins = "INSERT INTO db__pv_ist_$anlagenTabelle SET 
                    anl_id = $anlagenID, 
                    stamp = '$stamp', 
                    group_ac = $invGrpAc, inv = $invGrpAc, 
                    group_dc = $invGrpDc,
                    unit = $invnr,";
        $sql_sel_ins .= ($idc != '') ? "wr_idc = '$idc', " : "wr_idc = NULL, ";
        $sql_sel_ins .= ($pacout != '') ? "wr_pac = '$pacout', " : "wr_pac = NULL, ";
        $sql_sel_ins .= ($acCurrent != '') ? "i_ac = '$acCurrent', " : "i_ac = NULL, ";
        $sql_sel_ins .= ($iAcP1 != '') ? "i_ac_p1 = '$iAcP1', " : "i_ac_p1 = NULL, ";
        $sql_sel_ins .= ($iAcP2 != '') ? "i_ac_p2 = '$iAcP2', " : "i_ac_p2 = NULL, ";
        $sql_sel_ins .= ($iAcP3 != '') ? "i_ac_p3 = '$iAcP3', " : "i_ac_p3 = NULL, ";
        $sql_sel_ins .= ($acVoltage != '') ? "u_ac = '$acVoltage', " : "u_ac = NULL, ";
        $sql_sel_ins .= ($uAcP1 != '') ? "u_ac_p1 = '$uAcP1', " : "u_ac_p1 = NULL, ";
        $sql_sel_ins .= ($uAcP2 != '') ? "u_ac_p2 = '$uAcP2', " : "u_ac_p2 = NULL, ";
        $sql_sel_ins .= ($uAcP3 != '') ? "u_ac_p3 = '$uAcP3', " : "u_ac_p3 = NULL, ";
        $sql_sel_ins .= ($powerAcBlind != '') ? "p_ac_blind = '$powerAcBlind', " : "p_ac_blind = NULL, ";
        $sql_sel_ins .= ($powerAcApparent != '') ? "p_ac_apparent = '$powerAcApparent', " : "p_ac_apparent = NULL, ";
        $sql_sel_ins .= ($frequency != '') ? "frequency = '$frequency', " : "frequency = NULL, ";
        $sql_sel_ins .= ($udc != '') ? "wr_udc = '$udc', " : "wr_udc = NULL, ";
        $sql_sel_ins .= ($pdcout != '') ? "wr_pdc = '$pdcout', " : "wr_pdc = NULL, ";
        $sql_sel_ins .= ($temp != '') ? "wr_temp = '$temp', " : "wr_temp = NULL, ";
        $sql_sel_ins .= ($cosPhi != '') ? "wr_cos_phi_korrektur = '$cosPhi', " : "wr_cos_phi_korrektur = NULL, ";
        $sql_sel_ins .= ($eZEvu != '') ? "e_z_evu = '$eZEvu', " : "e_z_evu = NULL, ";
        $sql_sel_ins .= "
                    temp_corr = '$tempCorr',
                    theo_power = '$theoPower',       
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp', 
                    irr_anlage = '$irrAnlage',
                    temp_anlage = '$tempAnlage',
                    temp_inverter = '$tempInverter',
                    wind_anlage = '$windAnlage'
                             
                   ON DUPLICATE KEY UPDATE ";
        $sql_sel_ins .= ($idc != '') ? "wr_idc = '$idc', " : "wr_idc = NULL, ";
        $sql_sel_ins .= ($pacout != '') ? "wr_pac = '$pacout', " : "wr_pac = NULL, ";
        $sql_sel_ins .= ($acCurrent != '') ? "i_ac = '$acCurrent', " : "i_ac = NULL, ";
        $sql_sel_ins .= ($iAcP1 != '') ? "i_ac_p1 = '$iAcP1', " : "i_ac_p1 = NULL, ";
        $sql_sel_ins .= ($iAcP2 != '') ? "i_ac_p2 = '$iAcP2', " : "i_ac_p2 = NULL, ";
        $sql_sel_ins .= ($iAcP3 != '') ? "i_ac_p3 = '$iAcP3', " : "i_ac_p3 = NULL, ";
        $sql_sel_ins .= ($acVoltage != '') ? "u_ac = '$acVoltage', " : "u_ac = NULL, ";
        $sql_sel_ins .= ($uAcP1 != '') ? "u_ac_p1 = '$uAcP1', " : "u_ac_p1 = NULL, ";
        $sql_sel_ins .= ($uAcP2 != '') ? "u_ac_p2 = '$uAcP2', " : "u_ac_p2 = NULL, ";
        $sql_sel_ins .= ($uAcP3 != '') ? "u_ac_p3 = '$uAcP3', " : "u_ac_p3 = NULL, ";
        $sql_sel_ins .= ($powerAcBlind != '') ? "p_ac_blind = '$powerAcBlind', " : "p_ac_blind = NULL, ";
        $sql_sel_ins .= ($powerAcApparent != '') ? "p_ac_apparent = '$powerAcApparent', " : "p_ac_apparent = NULL, ";
        $sql_sel_ins .= ($frequency != '') ? "frequency = '$frequency', " : "frequency = NULL, ";
        $sql_sel_ins .= ($udc != '') ? "wr_udc = '$udc', " : "wr_udc = NULL, ";
        $sql_sel_ins .= ($pdcout != '') ? "wr_pdc = '$pdcout', " : "wr_pdc = NULL, ";
        $sql_sel_ins .= ($temp != '') ? "wr_temp = '$temp', " : "wr_temp = NULL, ";
        $sql_sel_ins .= ($cosPhi != '') ? "wr_cos_phi_korrektur = '$cosPhi', " : "wr_cos_phi_korrektur = NULL, ";
        $sql_sel_ins .= ($eZEvu != '') ? "e_z_evu = '$eZEvu', " : "e_z_evu = NULL, ";
        $sql_sel_ins .= "
                    temp_corr = '$tempCorr',
                    theo_power = '$theoPower',         
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp', 
                    irr_anlage = '$irrAnlage',
                    temp_anlage = '$tempAnlage',
                    temp_inverter = '$tempInverter',
                    wind_anlage = '$windAnlage';";

        $DBDataConnection->exec($sql_sel_ins);
        $DBDataConnection = null;
    }

    /**
     * Neueste Version des Datenimports AC IST Daten über ein Array als Prepare und Execute sollte deutlich schneller sein, weiterhin wird berücksichtigt
     * e_z_evu (Zählerstand Einspeisung Stromversoreger), Strahlungsdaten aus Anlage (JSON), dc_group (DC Gruppe)
     * 'inv' für GruppeAC wird ersetzt durch ac_group (im Übergang werden beide Werte den selben Inhalt haben, später wird dann 'inv' nicht mehr genutzt)
     * ACHTUNG: Werte aus e_z_evu und irr_anlage stehen bei jedem eintrag, dürfen aber NUR EINMAL pro Zeiteinheit ausgewertet werden
     * Stand: DEC 2022 - MS
     */
    function insertDataIntoPvIstAcV43($data_array, $anlagenTabelle)
    {
        $DBDataConnection = mysqli_connect('dedi6015.your-server.de', 'pvpluy_2', 'XD4R5XyVHUkK9U5i', 'pvp_data') or die(mysqli_connect_error());
        $DBDataConnection->query("set session wait_timeout=550");
        if ($data_array) {
            $sql_sel_ins = "INSERT INTO db__pv_ist_$anlagenTabelle (`anl_id`,`stamp`,`inv`,`group_ac`,`group_dc`,`unit`,`wr_idc`,`wr_pac`,`i_ac`,`i_ac_p1`,`i_ac_p2`,`i_ac_p3`,`u_ac`,`u_ac_p1`,`u_ac_p2`,
                              `u_ac_p3`,`p_ac_blind`,`p_ac_apparent`,`frequency`,`wr_udc`,`wr_pdc`,`wr_temp`,`wr_cos_phi_korrektur`,`e_z_evu`,`temp_corr`,`theo_power`,`wr_mpp_current`,`wr_mpp_voltage`,
                              `irr_anlage`,`temp_anlage`,`temp_inverter`,`wind_anlage`)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
                        `wr_idc` = VALUES(`wr_idc`), 
                        `wr_pac` = VALUES(`wr_pac`),
                        `i_ac` = VALUES(`i_ac`), 
                        `i_ac_p1` = VALUES(`i_ac_p1`), 
                        `i_ac_p2` = VALUES(`i_ac_p2`),
                        `i_ac_p3` = VALUES(`i_ac_p3`),
                        `u_ac` = VALUES(`u_ac`), 
                        `u_ac_p1` = VALUES(`u_ac_p1`), 
                        `u_ac_p2` = VALUES(`u_ac_p2`),
                        `u_ac_p3` = VALUES(`u_ac_p3`),
                        `p_ac_blind` = VALUES(`p_ac_blind`), 
                        `p_ac_apparent` = VALUES(`p_ac_apparent`), 
                        `frequency` = VALUES(`frequency`),
                        `wr_udc` = VALUES(`wr_udc`), 
                        `wr_pdc` = VALUES(`wr_pdc`), 
                        `wr_temp` = VALUES(`wr_temp`),
                        `wr_cos_phi_korrektur` = VALUES(`wr_cos_phi_korrektur`),    
                        `e_z_evu` = VALUES(`e_z_evu`), 
                        `temp_corr` = VALUES(`temp_corr`), 
                        `theo_power` = VALUES(`theo_power`),
                        `wr_mpp_current` = VALUES(`wr_mpp_current`),  
                        `wr_mpp_voltage` = VALUES(`wr_mpp_voltage`), 
                        `irr_anlage` = VALUES(`irr_anlage`),
                        `temp_anlage` = VALUES(`temp_anlage`), 
                        `temp_inverter` = VALUES(`temp_inverter`),
                        `wind_anlage` = VALUES(`wind_anlage`)                       
                        ";
            ##
            $stmt = mysqli_prepare($DBDataConnection, $sql_sel_ins) or die(mysqli_error($DBDataConnection));
            ##
            $con = count($data_array);
            $x = 0;
            foreach ($data_array as $key => $value) {
                // Check the Array values for valid
                $wr_idc = ($value['wr_idc'] != '') ? $value['wr_idc'] : 'NULL';
                $wr_pac = ($value['wr_pac'] != '') ? $value['wr_pac'] : 'NULL';
                $iac = ($value['i_ac'] != '') ? $value['i_ac'] : 'NULL';
                $iacp1 = ($value['i_ac_p1'] != '') ? $value['i_ac_p1'] : 'NULL';
                $iacp2 = ($value['i_ac_p2'] != '') ? $value['i_ac_p2'] : 'NULL';
                $iacp3 = ($value['i_ac_p3'] != '') ? $value['i_ac_p3'] : 'NULL';
                $uac = ($value['u_ac'] != '') ? $value['u_ac'] : 'NULL';
                $uacp1 = ($value['u_ac_p1'] != '') ? $value['u_ac_p1'] : 'NULL';
                $uacp2 = ($value['u_ac_p2'] != '') ? $value['u_ac_p2'] : 'NULL';
                $uacp3 = ($value['u_ac_p3'] != '') ? $value['u_ac_p3'] : 'NULL';
                $pacblind = ($value['p_ac_blind'] != '') ? $value['p_ac_blind'] : 'NULL';
                $pacapparent = ($value['p_ac_apparent'] != '') ? $value['p_ac_apparent'] : 'NULL';
                $frequency = ($value['frequency'] != '') ? $value['frequency'] : 'NULL';
                $wrudc = ($value['wr_udc'] != '') ? $value['wr_udc'] : 'NULL';
                $wrpdc = ($value['wr_pdc'] != '') ? $value['wr_pdc'] : 'NULL';
                $wrtemp = ($value['wr_temp'] != '') ? $value['wr_temp'] : 'NULL';
                $cosphikorr = ($value['wr_cos_phi_korrektur'] != '') ? $value['wr_cos_phi_korrektur'] : 'NULL';
                $ezevu = ($value['e_z_evu'] != '') ? $value['e_z_evu'] : 'NULL';
                $wrmppcurrent = ($value['wr_mpp_current'] != '') ? $value['wr_mpp_current'] : '{}';
                $wrmppvoltage = ($value['wr_mpp_voltage'] != '') ? $value['wr_mpp_voltage'] : '{}';
                // Bind the params with 32 values
                mysqli_stmt_bind_param($stmt, 'isiiiiddddddddddddddddddddssssss', $value['anl_id'], $value['stamp'], $value['group_ac'], $value['group_ac'], $value['group_dc'], $value['unit'], $wr_idc
                    , $wr_pac, $iac, $iacp1, $iacp2, $iacp3, $uac, $uacp1, $uacp2, $uacp3
                    , $pacblind, $pacapparent, $frequency, $wrudc, $wrpdc, $wrtemp, $cosphikorr, $ezevu
                    , $value['temp_corr'], $value['theo_power'], $wrmppcurrent, $wrmppvoltage, $value['irr_anlage'], $value['temp_anlage'], $value['temp_inverte'], $value['wind_anlage']);
                // Run the execute query
                mysqli_stmt_execute($stmt) or die(mysqli_stmt_error($stmt));
                // Allways 330 query send slepp any seconds
                if ($x % 330 == 0) {
                    sleep(3);
                }
                $x++;
            }
        }
    }

    function insertData($tableName, $data): void
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
     * Neueste Version des Datenimports AC IST Daten, es werden berücksichtigt
     * e_z_evu (Zählerstand Einspeisung Stromversoreger), Strahlungsdaten aus Anlage (JSON), dc_group (DC Gruppe)
     * 'inv' für GruppeAC wird ersetzt durch ac_group (im Übergang werden beide Werte den selben Inhalt haben, später wird dann 'inv' nicht mehr genutzt)
     * ACHTUNG: Werte aus e_z_evu und irr_anlage stehen bei jedem eintrag, dürfen aber NUR EINMAL pro Zeiteinheit ausgewertet werden
     * Stand: August 2020 - MRE
     * @param $stamp
     * @param $pvpGroupDc
     * @param $pvpInverter
     * @param $powerDc
     * @param $voltageDc
     * @param $currentDc
     * @param $temp
     * @param $anlagenId
     * @param $anlagenTabelle
     * @param $dcCurrentMpp
     * @param $dcVoltageMpp
     */
    function insertDataIntoPvIstDc($stamp, $pvpGroupDc, $pvpInverter, $powerDc, $voltageDc, $currentDc, $temp, $anlagenId, $anlagenTabelle, $dcCurrentMpp, $dcVoltageMpp)
    {
        $DBDataConnection = getPdoConnection();
        $sql_sel_ins = "INSERT INTO db__pv_dcist_$anlagenTabelle SET 
                    anl_id = $anlagenId, 
                    stamp = '$stamp', 
                    wr_group = $pvpGroupDc,
                    wr_num = $pvpInverter, 
                    wr_idc = $currentDc, 
                    wr_udc = $voltageDc, 
                    wr_pdc = $powerDc, 
                    wr_temp = $temp, 
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp'
                   ON DUPLICATE KEY UPDATE 
                    wr_idc = $currentDc, 
                    wr_udc = $voltageDc, 
                    wr_pdc = $powerDc, 
                    wr_temp = $temp, 
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp';";

        $DBDataConnection->exec($sql_sel_ins);
        $DBDataConnection = null;

    }

    /**
     * @param $stamp
     * @param $pvpGroupDc
     * @param $pvpGroupAc
     * @param $pvpInverter
     * @param $powerDc
     * @param $voltageDc
     * @param $currentDc
     * @param $temp
     * @param $anlagenId
     * @param $anlagenTabelle
     * @param $dcCurrentMpp
     * @param $dcVoltageMpp
     */
    function insertDataIntoPvIstDc2($stamp, $pvpGroupDc, $pvpGroupAc, $pvpInverter, $powerDc, $voltageDc, $currentDc, $temp, $anlagenId, $anlagenTabelle, $dcCurrentMpp, $dcVoltageMpp)
    {
        $DBDataConnection = getPdoConnection();
        $sql_sel_ins = "INSERT INTO db__pv_dcist_$anlagenTabelle SET 
                    anl_id = $anlagenId, 
                    stamp = '$stamp', 
                    wr_group = $pvpGroupDc,
                    group_ac = '$pvpGroupAc',
                    wr_num = $pvpInverter, 
                    wr_idc = $currentDc, 
                    wr_udc = $voltageDc, 
                    wr_pdc = $powerDc, 
                    wr_temp = $temp, 
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp'
                   ON DUPLICATE KEY UPDATE 
                    wr_idc = $currentDc, 
                    wr_udc = $voltageDc, 
                    wr_pdc = $powerDc, 
                    wr_temp = $temp, 
                    wr_mpp_current = '$dcCurrentMpp',  
                    wr_mpp_voltage = '$dcVoltageMpp';";

        $DBDataConnection->exec($sql_sel_ins);
        $DBDataConnection = null;

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

    /**
     * @param $ident
     * @param $stamp
     * @param string|null $gUpper
     * @param string|null $gLower
     * @param string|null $tempPanel
     * @param string|null $tempAmbient
     * @param string|null $windSpeed
     * @param string|null $gHorizontal
     * @param float|null $tCell
     * @param float|null $tCellMulipliedIrr
     * @param float|null $ftFactor
     * @param bool|null $irrFlag
     */
    function insertWeatherToWeatherDb(
        $ident, $stamp, ?string $gUpper = null, ?string $gLower = null, ?string $tempPanel = null,
        ?string $tempAmbient = null, ?string $windSpeed = null, ?string $gHorizontal = null,
        ?float $tCell = null, ?float $tCellMulipliedIrr = null, ?float $ftFactor = null, ?bool $irrFlag = null): void
    {
        $DBDataConnection = getPdoConnection();

        $sql_insert = "INSERT INTO db__pv_ws_$ident 
                    SET anl_intnr = '$ident', stamp = '$stamp', anl_id = '0',";
        $sql_insert .= $tempAmbient ? "at_avg = '$tempAmbient', temp_ambient = '$tempAmbient'," : "at_avg = '', temp_ambient = '',";
        $sql_insert .= $tempPanel ? "pt_avg = '$tempPanel', temp_pannel = '$tempPanel', " : "pt_avg = '', temp_pannel = '',";
        $sql_insert .= $gUpper ? "gmod_avg = '$gUpper', g_upper = '$gUpper', " : "gmod_avg = '', g_upper = '', ";
        $sql_insert .= $gLower ? "gi_avg = '$gLower', g_lower = '$gLower', " : "gi_avg = '', g_lower = '', ";
        $sql_insert .= $gHorizontal ? "g_horizontal = '$gHorizontal', " : "g_horizontal = '', ";
        $sql_insert .= $windSpeed ? "wind_speed = '$windSpeed', " : "wind_speed = '', ";
        $sql_insert .= $tCellMulipliedIrr ? "temp_cell_multi_irr = '$tCellMulipliedIrr', " : "temp_cell_multi_irr = '', ";
        $sql_insert .= $tCell ? "temp_cell_corr = '$tCell', " : "temp_cell_corr = '', ";
        $sql_insert .= $ftFactor ? "ft_factor = '$ftFactor', " : "";
        $sql_insert .= $irrFlag ? "irr_flag = '$irrFlag', " : "";
        $sql_insert .= "rso = '0',  gi = '0' ON DUPLICATE KEY UPDATE ";
        $sql_insert .= $tempAmbient ? "at_avg = '$tempAmbient', temp_ambient = '$tempAmbient'," : "at_avg = '', temp_ambient = '',";
        $sql_insert .= $tempPanel ? "pt_avg = '$tempPanel', temp_pannel = '$tempPanel', " : "pt_avg = '', temp_pannel = '',";
        $sql_insert .= $gUpper ? "gmod_avg = '$gUpper', g_upper = '$gUpper', " : "gmod_avg = '', g_upper = '', ";
        $sql_insert .= $gLower ? "gi_avg = '$gLower', g_lower = '$gLower', " : "gi_avg = '', g_lower = '', ";
        $sql_insert .= $gHorizontal ? "g_horizontal = '$gHorizontal', " : "g_horizontal = '', ";
        $sql_insert .= $windSpeed ? "wind_speed = '$windSpeed', " : "wind_speed = '', ";
        $sql_insert .= $tCellMulipliedIrr ? "temp_cell_multi_irr = '$tCellMulipliedIrr', " : "temp_cell_multi_irr = '', ";
        $sql_insert .= $tCell ? "temp_cell_corr = '$tCell', " : "temp_cell_corr = '', ";
        $sql_insert .= $ftFactor ? "ft_factor = '$ftFactor', " : "";
        $sql_insert .= $irrFlag ? "irr_flag = '$irrFlag', " : "";
        $sql_insert .= "rso = '0', gi = '0';";

        $DBDataConnection->exec($sql_insert);
        $DBDataConnection = null;
    }

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
     * Neueste Version des Datenimports der DC Daten über ein Array als SQL Prepare und Execute sollte deutlich schneller sein
     * Stand: DEC 2022 - MS
     */
    function insertDataIntoPvIstDc43($data_array, $ident)
    {
        $DBDataConnection = mysqli_connect('dedi6015.your-server.de', 'pvpluy_2', 'XD4R5XyVHUkK9U5i', 'pvp_data') or die(mysqli_connect_error());
        $DBDataConnection->query("set session wait_timeout=630");
        if ($data_array) {
            $sql_sel_ins = "INSERT INTO db__pv_dcist_$ident (`anl_id`,`stamp`,`wr_group`,`group_ac`,`wr_num`,`wr_idc`,`wr_udc`,`wr_pdc`,`wr_temp`,`wr_mpp_current`,`wr_mpp_voltage`)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
                        `wr_idc` = VALUES(`wr_idc`), 
                        `wr_udc` = VALUES(`wr_udc`),
                        `wr_pdc` = VALUES(`wr_pdc`),
                        `wr_temp` = VALUES(`wr_temp`),           
                        `wr_mpp_current` = VALUES(`wr_mpp_current`),
                        `wr_mpp_voltage` = VALUES(`wr_mpp_voltage`)";
            ##
            $stmt = mysqli_prepare($DBDataConnection, $sql_sel_ins) or die(mysqli_error($DBDataConnection));
            $x = 0;
            foreach ($data_array as $key => $value) {
                // Check the Array values for valid
                $stamp = $value['stamp'];
                $anlid = '0';
                $wrgroup = $value['wr_group'];
                $groupac = $value['group_ac'];
                $wrnum = $value['wr_num'];
                $wridc = ($value['wr_idc'] != '') ? $value['wr_idc'] : '0';
                $wrudc = ($value['wr_udc'] != '') ? $value['wr_udc'] : '0';
                $wrpdc = ($value['wr_pdc'] != '') ? $value['wr_pdc'] : '0';
                $wrtemp = ($value['wr_temp'] != '') ? $value['wr_temp'] : '0';
                $wrmppcurrent = ($value['wr_mpp_current'] != '') ? $value['wr_mpp_current'] : '{}';
                $wrmppvoltage = ($value['wr_mpp_voltage'] != '') ? $value['wr_mpp_voltage'] : '{}';
                // Bind the params with 11 values
                mysqli_stmt_bind_param($stmt, 'isiiiddddss', $ident, $stamp, $wrgroup, $groupac, $wrnum, $wridc, $wrudc, $wrpdc, $wrtemp, $wrmppcurrent, $wrmppvoltage);
                // Run the execute query
                mysqli_stmt_execute($stmt) or die(mysqli_stmt_error($stmt));
                if ($x % 330 == 0) {
                    sleep(3);
                }
                $x++;
            }
        }
    }

    /**
     * Neueste Version des Datenimports der Wetter Daten über ein Array als SQL Prepare und Execute sollte deutlich schneller sein
     * Stand: DEC 2022 - MS
     */
    function insertWeatherToWeatherDb43($data_array, $ident)
    {
        // Wenn Wind Speed = NULL dann kommt der Wind event von einer anderen Quelle, deshalb den Wind nicht anfassen, sonst wird der überschrieben
        $DBDataConnection = mysqli_connect('dedi6015.your-server.de', 'pvpluy_2', 'XD4R5XyVHUkK9U5i', 'pvp_data') or die(mysqli_connect_error());
        $DBDataConnection->query("set session wait_timeout=630");
        if ($data_array) {
            $sql_sel_ins = "INSERT INTO db__pv_ws_$ident (`anl_intnr`,`stamp`,`anl_id`,`at_avg`,`pt_avg`,`gi_avg`,`gmod_avg`,`g_upper`,`g_lower`,`g_horizontal`,`temp_pannel`,`temp_ambient`,`rso`,`gi`
                              ,`temp_cell_corr`,`temp_cell_multi_irr`,`wind_speed`)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
                        `at_avg` = VALUES(`at_avg`), 
                        `pt_avg` = VALUES(`pt_avg`),
                        `gi_avg` = VALUES(`gi_avg`), 
                        `gmod_avg` = VALUES(`gmod_avg`), 
                        `g_upper` = VALUES(`g_upper`),
                        `g_lower` = VALUES(`g_lower`),
                        `g_horizontal` = VALUES(`g_horizontal`), 
                        `temp_pannel` = VALUES(`temp_pannel`), 
                        `temp_ambient` = VALUES(`temp_ambient`),
                        `rso` = VALUES(`rso`),
                        `gi` = VALUES(`gi`), 
                        `temp_cell_corr` = VALUES(`temp_cell_corr`), 
                        `temp_cell_multi_irr` = VALUES(`temp_cell_multi_irr`),
                        `wind_speed` = VALUES(`wind_speed`)                       
                        ";
            ##
            $stmt = mysqli_prepare($DBDataConnection, $sql_sel_ins) or die(mysqli_error($DBDataConnection));
            ##["stamp"=>$row,"irr_upper"=>$irr_upper,"irr_lower"=>$irr_lower,"temp_panel"=>$tempPanel,"temp_ambient"=>$tempAmbient,"wind_Speed"=>$windSpeed,"irr_hori"=>$irrHori];
            foreach ($data_array as $key => $value) {
                // Check the Array values for valid
                $stamp = $value['stamp'];
                $anlid = '0';
                $gUpper = ($value['irr_upper'] != '') ? $value['irr_upper'] : '0';;
                $gLower = ($value['irr_lower'] != '') ? $value['irr_lower'] : '0';
                $tempPanel = ($value['temp_panel'] != '') ? $value['temp_panel'] : '0';
                $tempAmbient = ($value['temp_ambient'] != '') ? $value['temp_ambient'] : '0';
                $windSpeed = ($value['wind_Speed'] != '') ? $value['wind_Speed'] : '0';
                $gHorizontal = ($value['irr_hori'] != '') ? $value['irr_hori'] : '0';
                $tCell = 'null';
                $tCellMulipliedIrr = 'null';
                $rso = '0';
                $gi = '0';
                // Bind the params with 17 values
                mysqli_stmt_bind_param($stmt, 'isiiidddddddddddd', $ident, $stamp, $anlid, $tempAmbient, $tempPanel, $gLower
                    , $gUpper, $gUpper, $gLower, $gHorizontal, $tempPanel, $tempAmbient, $rso, $gi, $tCell, $tCellMulipliedIrr, $windSpeed);
                // Run the execute query
                mysqli_stmt_execute($stmt) or die(mysqli_stmt_error($stmt));
            }
        }
    }


    /**
     * @param $ident
     * @param $stamp
     * @param float $windSpeed
     */
    function insertWindToWeatherDb($ident, $stamp, float $windSpeed = 0)
    {
        $DBDataConnection = getPdoConnection();
        $sql_insert = "INSERT INTO db__pv_ws_$ident 
                        SET anl_intnr = '$ident', stamp = '$stamp', wind_speed = '$windSpeed'
                        ON DUPLICATE KEY UPDATE  
                            wind_speed = '$windSpeed'";
        $DBDataConnection->exec($sql_insert);
        $DBDataConnection = null;
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
     * Datenimport der PPC Daten in die Tabelle db__pv_ppc_Anlagenname
     * Es werden 15 Min Werte importiert.<br>
     * Sollte für diese 15 Min schon ein Wert vorliegen wird dieser aktualisiert (stamp ist unique key).
     * Stand: Sep 2022 - MR
     *
     */
    function insertDataIntoPPC($ident, $anlagenID, $stamp, $p_ac_inv, $pf_set, $p_set_rel, $p_set_gridop_rel, $p_set_rpc_rel, $q_set_rel, $p_set_ctrl_rel, $p_set_ctrl_rel_mean, $q_ac_inv = 0): void
    {
        $DBDataConnection = getPdoConnection();

        $sql_sel_ins = "INSERT INTO db__pv_ppc_$ident SET 
        anl_id      = '$anlagenID', 
        anl_intnr   = '$ident',
        stamp       = '$stamp',";
        $sql_sel_ins .= $p_ac_inv != '' ? "p_ac_inv = '$p_ac_inv', " : "p_ac_inv = NULL, ";
        $sql_sel_ins .= $q_ac_inv != '' ? "q_ac_inv = '$q_ac_inv', " : "q_ac_inv = NULL, ";
        $sql_sel_ins .= $pf_set != '' ? "pf_set = '$pf_set', " : "pf_set = NULL, ";
        $sql_sel_ins .= $p_set_rel != '' ? "p_set_rel = '$p_set_rel', " : "p_set_rel = NULL, ";
        $sql_sel_ins .= $p_set_gridop_rel != '' ? "p_set_gridop_rel = '$p_set_gridop_rel', " : "p_set_gridop_rel = NULL, ";
        $sql_sel_ins .= $p_set_rpc_rel != '' ? "p_set_rpc_rel = '$p_set_rpc_rel', " : "p_set_rpc_rel = NULL, ";
        $sql_sel_ins .= $q_set_rel != '' ? "q_set_rel = '$q_set_rel', " : "q_set_rel = NULL, ";
        $sql_sel_ins .= $p_set_ctrl_rel != '' ? "p_set_ctrl_rel = '$p_set_ctrl_rel', " : "p_set_ctrl_rel = NULL, ";
        $sql_sel_ins .= $p_set_ctrl_rel_mean != '' ? "p_set_ctrl_rel_mean = '$p_set_ctrl_rel_mean'" : "p_set_ctrl_rel_mean = NULL ";
        $sql_sel_ins .= " ON DUPLICATE KEY UPDATE ";
        $sql_sel_ins .= $p_ac_inv != '' ? "p_ac_inv = '$p_ac_inv', " : "p_ac_inv = NULL, ";
        $sql_sel_ins .= $q_ac_inv != '' ? "q_ac_inv = '$q_ac_inv', " : "q_ac_inv = NULL, ";
        $sql_sel_ins .= $pf_set != '' ? "pf_set = '$pf_set', " : "pf_set = NULL, ";
        $sql_sel_ins .= $p_set_rel != '' ? "p_set_rel = '$p_set_rel', " : "p_set_rel = NULL, ";
        $sql_sel_ins .= $p_set_gridop_rel != '' ? "p_set_gridop_rel = '$p_set_gridop_rel', " : "p_set_gridop_rel = NULL, ";
        $sql_sel_ins .= $p_set_rpc_rel != '' ? "p_set_rpc_rel = '$p_set_rpc_rel', " : "p_set_rpc_rel = NULL, ";
        $sql_sel_ins .= $q_set_rel != '' ? "q_set_rel = '$q_set_rel', " : "q_set_rel = NULL, ";
        $sql_sel_ins .= $p_set_ctrl_rel != '' ? "p_set_ctrl_rel = '$p_set_ctrl_rel', " : "p_set_ctrl_rel = NULL, ";
        $sql_sel_ins .= $p_set_ctrl_rel_mean != '' ? "p_set_ctrl_rel_mean = '$p_set_ctrl_rel_mean'" : "p_set_ctrl_rel_mean = NULL ";

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
    function mittelwert(array $werte, bool $ignoreZero = true): ?float
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
    function checkIfValueIsNotNull(?string $value, bool $convertToKWH = false): ?string
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

    function updatePvIst($stamp, $anlagenID, $anlagenTabelle, $eZEvu = '')
    {
        $DBDataConnection = getPdoConnection();
        $sql_sel_update = "update $anlagenTabelle set e_z_evu = $eZEvu WHERE anl_id = $anlagenID and stamp like '$stamp'";

        $DBDataConnection->exec($sql_sel_update);
        $DBDataConnection = null;
    }

    function getAnlageSensors(string $anlId): array
    {
        $conn = getPdoConnectionAnlage();

        $sql = "SELECT * FROM pvp_base.anlage_sensors  WHERE anlage_id = " . $anlId . ";";

        $result = $conn->query($sql);

        return $result->fetchAll(PDO::FETCH_OBJ);;
    }

    function getACGroups(string $anlId): array
    {
        $conn = getPdoConnectionAnlage();

        $sql = "SELECT * FROM pvp_base.anlage_groups_ac  WHERE anlage_id = " . $anlId . ";";

        $result = $conn->query($sql);

        return $result->fetchAll(PDO::FETCH_OBJ);;
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
                if ($anlageSensors[$i]->virtual_sensor == 'irr-hori' && $anlageSensors[$i]->use_to_calc == 1) {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->start_date_sensor != null) {
                        $start = strtotime($anlageSensors[$i]->start_date_sensor);
                    }
                    if ($anlageSensors[$i]->end_date_sensor != null) {
                        $end = strtotime($anlageSensors[$i]->end_date_sensor);
                    }
                    $now = strtotime($date);
                    if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                        array_push($gmPyHori, max($sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr], 0));
                        $gmPyHoriAnlage[$anlageSensors[$i]->name_short] = max($sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr], 0);
                    }

                }

                if ($anlageSensors[$i]->virtual_sensor == 'irr-west' && $anlageSensors[$i]->use_to_calc == 1) {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->start_date_sensor != null) {
                        $start = strtotime($anlageSensors[$i]->start_date_sensor);
                    }
                    if ($anlageSensors[$i]->end_date_sensor != null) {
                        $end = strtotime($anlageSensors[$i]->end_date_sensor);
                    }
                    $now = strtotime($date);
                    if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                        array_push($gmPyWest, max($sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr], 0));
                        $gmPyWestAnlage[$anlageSensors[$i]->name_short] = max($sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr], 0);
                    }

                }

                if ($anlageSensors[$i]->virtual_sensor == 'irr-east' && $anlageSensors[$i]->use_to_calc == 1) {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->start_date_sensor != null) {
                        $start = strtotime($anlageSensors[$i]->start_date_sensor);
                    }
                    if ($anlageSensors[$i]->end_date_sensor != null) {
                        $end = strtotime($anlageSensors[$i]->end_date_sensor);
                    }
                    $now = strtotime($date);
                    if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                        array_push($gmPyEast, max($sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr], 0));
                        $gmPyEastAnlage[$anlageSensors[$i]->name_short] = max($sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr], 0);
                    }

                }
            }

            $result[0] = [
                'irrHorizontal' => mittelwert($gmPyHori),
                'irrHorizontalAnlage' => $gmPyHoriAnlage,
                'irrLower' => mittelwert($gmPyWest),
                'irrLowerAnlage' => $gmPyWestAnlage,
                'irrUpper' => mittelwert($gmPyEast),
                'irrUpperAnlage' => $gmPyEastAnlage,
            ];
        } else {
            $gmPyHori = $gmPyHoriAnlage = $gmPyEast = $gmPyEastAnlage = [];

            for ($i = 0; $i < $length; $i++) {
                if ($anlageSensors[$i]->virtual_sensor == 'irr-hori' && $anlageSensors[$i]->use_to_calc == 1) {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->start_date_sensor != null) {
                        $start = strtotime($anlageSensors[$i]->start_date_sensor);
                    }
                    if ($anlageSensors[$i]->end_date_sensor != null) {
                        $end = strtotime($anlageSensors[$i]->end_date_sensor);
                    }
                    $now = strtotime($date);
                    if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                        array_push($gmPyHori, max($sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr], 0));
                        $gmPyHoriAnlage[$anlageSensors[$i]->name_short] = max($sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr], 0);
                    }

                }

                if ($anlageSensors[$i]->virtual_sensor == 'irr' && $anlageSensors[$i]->use_to_calc == 1) {
                    $start = 0;
                    $end = 0;
                    if ($anlageSensors[$i]->start_date_sensor != null) {
                        $start = strtotime($anlageSensors[$i]->start_date_sensor);
                    }
                    if ($anlageSensors[$i]->end_date_sensor != null) {
                        $end = strtotime($anlageSensors[$i]->end_date_sensor);
                    }
                    $now = strtotime($date);
                    array_push($gmPyEast, max($sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr], 0));
                    $gmPyEastAnlage[$anlageSensors[$i]->name_short] = max($sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr], 0);
                }

            }
            $result[0] = [
                'irrHorizontal' => mittelwert($gmPyHori),
                'irrHorizontalAnlage' => $gmPyHoriAnlage,
                'irrLower' => 0,
                'irrLowerAnlage' => [],
                'irrUpper' => mittelwert($gmPyEast),
                'irrUpperAnlage' => $gmPyEastAnlage,
            ];
        }

        //mNodulTemp, ambientTemp, windSpeed
        $tempModule = $tempAmbientArray = $tempAnlage = $windSpeedEWD = $windSpeedEWS = $windAnlage = [];
        for ($i = 0; $i < $length; $i++) {
            if ($anlageSensors[$i]->virtual_sensor == 'temp-modul' && $anlageSensors[$i]->use_to_calc == 1) {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]->start_date_sensor != null) {
                    $start = strtotime($anlageSensors[$i]->start_date_sensor);
                }
                if ($anlageSensors[$i]->end_date_sensor != null) {
                    $end = strtotime($anlageSensors[$i]->end_date_sensor);
                }
                $now = strtotime($date);
                if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                    array_push($tempModule, $sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr]);
                    $tempAnlage[$anlageSensors[$i]->name_short] = $sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr];
                }

            }
            if ($anlageSensors[$i]->virtual_sensor == 'temp-ambient' && $anlageSensors[$i]->use_to_calc == 1) {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]->start_date_sensor != null) {
                    $start = strtotime($anlageSensors[$i]->start_date_sensor);
                }
                if ($anlageSensors[$i]->end_date_sensor != null) {
                    $end = strtotime($anlageSensors[$i]->end_date_sensor);
                }
                $now = strtotime($date);
                if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                    array_push($tempAmbientArray, $sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr]);
                    $tempAnlage[$anlageSensors[$i]->name_short] = $sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr];
                }

            }
            if ($anlageSensors[$i]->virtual_sensor == 'wind-direction' && $anlageSensors[$i]->use_to_calc == 1) {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]->start_date_sensor != null) {
                    $start = strtotime($anlageSensors[$i]->start_date_sensor);
                }
                if ($anlageSensors[$i]->end_date_sensor != null) {
                    $end = strtotime($anlageSensors[$i]->end_date_sensor);
                }
                $now = strtotime($date);
                $x = (string)$anlageSensors[$i]->start_date_sensor;
                $y = (string)$anlageSensors[$i]->end_date_sensor;
                #echo "Sensor Start $date = $now /BE $x = $start \n\n";
                #echo "Sensor End $date = $now /BE $y = $end \n";
                if (($now >= $start && ($end == 0 || $now < $end)) || ($start == 0 && $end == 0)) {
                    array_push($windSpeedEWD, $sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr]);
                    $windAnlage[$anlageSensors[$i]->name_short] = $sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr];
                }

            }
            if ($anlageSensors[$i]->virtual_sensor == 'wind-speed' && $anlageSensors[$i]->use_to_calc == 1) {
                $start = 0;
                $end = 0;
                if ($anlageSensors[$i]->start_date_sensor != null) {
                    $start = strtotime($anlageSensors[$i]->start_date_sensor);
                }
                if ($anlageSensors[$i]->end_date_sensor != null) {
                    $end = strtotime($anlageSensors[$i]->end_date_sensor);
                }
                $now = strtotime($date);
                if (($now >= $start && ($end == 0 || $end <= $now)) || ($start == 0 && $end == 0)) {
                    array_push($windSpeedEWS, $sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr]);
                    $windAnlage[$anlageSensors[$i]->name_short] = $sensors[$date][$anlageSensors[$i]->vcom_id][$anlageSensors[$i]->vcom_abbr];
                }
            }
        }

        $result[1] = [
            'tempPanel' => mittelwert($tempModule),
            'tempAmbient' => mittelwert($tempAmbientArray),
            'anlageTemp' => $tempAnlage,
            'windDirection' => mittelwert($windSpeedEWD),
            'windSpeed' => mittelwert($windSpeedEWS),
            'anlageWind' => $windAnlage,
        ];

        return $result;

    }
}