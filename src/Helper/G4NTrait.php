<?php

namespace App\Helper;

require_once __DIR__ . '/../../public/config.php';

use PDO;
use PDOException;
use App\Entity\Anlage;
use Symfony\Component\Intl\Timezones;
use DateTimeZone;

trait G4NTrait
{

    /**
     * truncates decimal number after 2 decimal places
     *
     * @param float $value
     * @param int $decimal_places
     * @return float
     */
    public static function cutNumber(float $value, int $decimal_places = 1): float
    {
        $result = ((int)($value * 10^$decimal_places) / 10^$decimal_places);
        return (float)$result;
    }

    /**
     *  Removes control char from given string
     */
    public function removeControlChar(string $string): string
    {
        $trimChar = "\n\r\t\v\0"; // Zu entfernde Zeichen für 'trim'
        return trim($string, $trimChar);
    }


    public static function getCetTime($format = 'timestamp'){
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        if($date->format('I') == 1) {
            $date->modify('+2 hours');
        } else {
            $date->modify('+1 hours');
        }

        switch (strtoupper($format)) {
            case 'SQL':
                $_time = $date->format('Y-m-d H:i');
                break;
            case 'OBJECT':
                $_time = $date;
                break;
            default:
                $_time = $date->format('U') - 7200; // -7200 = Zeitzonen Korrektur
        }

        return $_time;
    }

    /**
     * Anpassung der Zeit (Korrektur der Zeit aud dem Backend)
     *
     * $timestamp = umzurechnender Zeitstempel als TimeStamp(INT) oder Zeit Sring
     * $val = interne Zeitzone der Anlage (Korrektur der Zeit)
     *
     * return Zeitstempel im SQL Format
     */
    public static function timeAjustment($timestamp, float $val = 0, $reverse = false): string
    {
        $format     = 'Y-m-d H:i:s';
        // Sollte die Zeit als String übergeben worden sein, dann wandele in TimeStamp um
        if (gettype($timestamp) != 'integer') $timestamp = strtotime($timestamp);
        $reverse ? $timestamp -= ($val * 3600) : $timestamp += ($val * 3600);

        return date($format, $timestamp);
    }

    public static function isDateToday($date) {
        return date("Y-m-d", strtotime($date)) == date('Y-m-d', self::getCetTime());
    }

    public static function isInTimeRange($stamp = "") {
        if ($stamp == "") {
            $currentTime = self::getCetTime();
        } else {
            $currentTime = strtotime($stamp);
        }
        $month = date('m', $currentTime);
        $currentHour = date('H', $currentTime);
        $start = $GLOBALS['StartEndTimesAlert'][$month]['start'];
        $end = $GLOBALS['StartEndTimesAlert'][$month]['end'];

        return ($currentHour >= $start && $currentHour <= $end);
    }

    public static function checkUnitAndConvert($value, $unit) {
        ($unit === 'w') ? $returnValue = ($value / 1000 / 4) : $returnValue = $value;

        return $returnValue;
    }

    //Formatiere angegebene Zeit für SQL
    function formatTimeStampToSql(Int $timestamp):string {
        return date('Y-m-d H:i:s', $timestamp);
    }



    /**
     * @return \mysqli
     * @deprecated
     * erstezen durch getPdoConnection()
     */
    public static function connectToDatabase():\mysqli
    {
        return new \mysqli('dedi6015.your-server.de', 'pvpluy_2', 'XD4R5XyVHUkK9U5i', 'pvp_data');
    }

    /**
     * @return \mysqli
     * @deprecated
     * ersetzen durch 'doctrine'
     */
    public static function connectToDatabaseAnlage():\mysqli
    {
        return new \mysqli('dedi6015.your-server.de', 'pvpbase', '04qjYWk1oTf9gb7k', 'pvp_base');
    }

    public static function getPdoConnection($dbdsn = null, $dbusr = null, $dbpass = null):\PDO
    {
        // Check der Parameter wenn null dann nehme default Werte als fallback
        $dbdsn === null ? $dbdsn = 'mysql:dbname=pvp_data;host=dedi6015.your-server.de' : $dbdsn = $dbdsn;
        $dbusr === null ? $dbusr = 'pvpluy_2' : $dbusr = $dbusr;
        $dbpass === null ? $dbpass = 'XD4R5XyVHUkK9U5i' : $dbpass = $dbpass;
        // Config als Array
        $config = [
            'database_dsn' => $dbdsn,
            'database_user' => $dbusr,
            'database_pass' => $dbpass
        ];

        try {
            $pdo = new PDO(
                $config['database_dsn'],
                $config['database_user'],
                $config['database_pass']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

        return $pdo;
    }

    public static function getPdoConnectionTest():\PDO
    {
        $config = [
            'database_dsn' => 'mysql:dbname=pvp_base;host=dedi6015.your-server.de',
            'database_user' => 'pvpbase',
            'database_pass' => '04qjYWk1oTf9gb7k'
        ];

        try {
            $pdo = new PDO(
                $config['database_dsn'],
                $config['database_user'],
                $config['database_pass']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

        return $pdo;
    }

    public static function convertKeysToCamelCase($apiResponseArray): array
    {
        $arr = [];
        foreach ($apiResponseArray as $key => $value) {
            $key = lcfirst(implode('', array_map('ucfirst', explode('_', $key))));
            if (is_array($value)) $value = self::convertKeysToCamelCase($value);
            $arr[$key] = $value;
        }

        return $arr;
    }

    /**
     * Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms<br>
     * adjust Plant timestamp with offset from entity plant ($anlage->getAnlZeitzone())
     *
     * @param Anlage $anlage
     * @param $stamp
     * @param false $reverse
     * @return string
     * @throws \Exception
     */
    public function timeShift(Anlage $anlage, $stamp, bool $reverse = false): string
    {
        $country = strtoupper($anlage->getCountry());

        $offset = Timezones::getRawOffset(self::getNearestTimezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(), $country));

        if (date('I',strtotime($stamp)) == 1) {
            //summertime
            $offset = $offset - 7200; // not sure why this is nessary
        } else {
            //wintertime
            $offset = $offset - 3600; // not sure why this is nessary
        }

        $of = $offset / 3600;

        if ($of < 0) {
            $offset_time = strtotime($stamp) - $offset;
            if ($reverse) $offset_time = strtotime($stamp) + $offset;
            $result = date('Y-m-d H:i', $offset_time);

        } elseif ($of > 0) {
            $offset_time = strtotime($stamp) + $offset;
            if ($reverse) $offset_time = strtotime($stamp) - $offset;
            $result = date('Y-m-d H:i', $offset_time);
        } else {
            $result = date('Y-m-d H:i', strtotime($stamp));
        }

        return $result;
    }

    /**
     * as the name of the function describs, get the plants nearest timezone
     * 
     * @param $cur_lat
     * @param $cur_long
     * @param string $country_code
     * @return string
     */
    public function getNearestTimezone($cur_lat, $cur_long, $country_code = ''): string
    {
        $timezone_ids = ($country_code) ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code)
            : DateTimeZone::listIdentifiers();

        if($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {

            $time_zone = '';
            $tz_distance = 0;

            //only one identifier?
            if (count($timezone_ids) == 1) {
                $time_zone = $timezone_ids[0];
            } else {
                foreach($timezone_ids as $timezone_id) {
                    $timezone = new DateTimeZone($timezone_id);
                    $location = $timezone->getLocation();
                    $tz_lat   = $location['latitude'];
                    $tz_long  = $location['longitude'];

                    $theta    = $cur_long - $tz_long;
                    $distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
                        + (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
                    $distance = acos($distance);
                    $distance = abs(rad2deg($distance));

                    if (!$time_zone || $tz_distance > $distance) {
                        $time_zone   = $timezone_id;
                        $tz_distance = $distance;
                    }
                }
            }
            return  $time_zone;
        }
        return 'unknown';
    }

    /**
     * Funktion um die Anzahle der ganzen Monate zwischen zwei Daten zu berecchnen
     * wobei z.B. der 1.3.2021 bis zum 31.3.2021 einem Monat entspricht.
     * Wenn die Anzahl der Tage zwischen den beiden Daten kleiner als die max Anzahl der Tage des Monats ($from) dann entspricht das 0 Monaten (Bsp: 1.3.2021.bis 25.3.2021)
     *
     * @param $from
     * @param $to
     * @return int
     */
    public function g4nDateDiffMonth($from, $to) : int
    {
        $fromYear       = (int)date('Y', strtotime($from));
        $fromMonth      = (int)date('m', strtotime($from));
        $fromDay        = (int)date('d', strtotime($from));
        $toYear         = (int)date('Y', strtotime($to));
        $toMonth        = (int)date('m', strtotime($to));
        $toDay          = (int)date('d', strtotime($to));
        $daysInMonth    = (int)date('t', strtotime($from));
        //prüfe, ob Start Monat und Jahr gleich dem End Monat und Jahr, wenn dann die Anzahl der Tage < max Tage des Monats dann kein ganzer Monat ($month = 0)
        if ($fromMonth == $toMonth && $fromYear == $toYear && $toDay - $fromDay < $daysInMonth) {
            $month = 1; // muss 1 damit auch Rumpf Monate bearbeitet werden ?? TODO: nicht sicher ob das immer passt - beobachetn
        } else {
            if ($fromMonth > $toMonth) {
                $month = 12 - $fromMonth + $toMonth + 1;
                if ($toYear - $fromYear > 1) $month += ($toYear - $fromYear) * 12;
            } elseif ($fromMonth < $toMonth) {
                $month = $toMonth - $fromMonth + 1;
                if ($toYear - $fromYear > 0) $month += ($toYear - $fromYear) * 12;
            } else {
                $month = 1;
                if ($toYear - $fromYear > 0) $month += ($toYear - $fromYear) * 12;
            }
        }

        return $month;
    }
   
    /**
     * @param array $content
     * @return string
     */
    public function printArrayAsTable(array $content): string
    {
        $precision = 4;
        $_html = "<div class='table-scroll'><table style='font-size: 90%'>";
        $_counter = 0;
        $_html .= "<thead>";
        foreach ($content as $key => $contentRow) {
            if ($_counter === 0) {
                $_html .= "<tr><th>Key</th>";
                foreach ($contentRow as $subkey => $subvalue) {
                    $_html .= '<th>' . substr($subkey, 0, 20) . '</th>';
                }
                $_html .= "</tr>";
                $_html .= "</thead>";
                $_html .= "<tbody>";
            }
            $_html .= "<tr><td>$key</td>";
            foreach ($contentRow as $cell) {
                if (is_float($cell)){
                    $_html .= "<td>" . round($cell,$precision) . "</td>";
                } else {
                    $_html .= "<td>$cell</td>";
                }
            }
            $_html .= "</tr>";
            $_counter++;
        }

        $_html .= "</tbody>";
        $_html .= "</table></div>";

        return $_html;

    }

    /**
     * Ermittelt aus dem übergebenen ARray den Mittelwert, wobei 0 Werte nicht in die Berechnung einfließen
     *
     * @param array $werte
     * @return float
     */
    public function mittelwert(array $werte): ?float
    {
        $divisor = $divident = 0;
        foreach ($werte as $wert) {
            if ((float)$wert > 0) {
                $divisor++;
                $divident += (float)$wert;
            }
        }
        return ($divisor > 0) ? $divident / $divisor : null;
    }

}