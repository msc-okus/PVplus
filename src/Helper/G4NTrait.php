<?php

namespace App\Helper;

require_once __DIR__.'/../../public/config.php';

use App\Entity\Anlage;
use DateTimeZone;
use Exception;
use PDO;
use PDOException;
use Symfony\Component\Intl\Timezones;
use Symfony\Polyfill\Intl\Normalizer\Normalizer;

trait G4NTrait
{
    /**
     *  Removes control char from given string.
     */
    public function removeControlChar(string $string): string
    {
        $trimChar = "\n\r\t\v\0"; // Zu entfernde Zeichen für 'trim'

        return trim($string, $trimChar);
    }

    public static function getCetTime($format = 'timestamp')
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        if ($date->format('I') == 1) {
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
     * Anpassung der Zeit (Korrektur der Zeit aud dem Backend).
     *
     * $timestamp = umzurechnender Zeitstempel als TimeStamp(INT) oder Zeit Sring
     * $val = interne Zeitzone der Anlage (Korrektur der Zeit)
     *
     * return Zeitstempel im SQL Format
     *
     * @param $timestamp
     * @param float $val
     * @param $reverse
     * @return string
     */
    public static function timeAjustment($timestamp, float $val = 0, $reverse = false): string
    {
        $format = 'Y-m-d H:i:s';
        // Sollte die Zeit als String übergeben worden sein, dann wandele in TimeStamp um
        if (gettype($timestamp) != 'integer') {
            $timestamp = strtotime($timestamp);
        }
        $reverse ? $timestamp -= ($val * 3600) : $timestamp += ($val * 3600);

        return date($format, $timestamp);
    }

    public static function isDateToday($date)
    {
        return date('Y-m-d', strtotime($date)) == date('Y-m-d', self::getCetTime());
    }

    public static function isInTimeRange($stamp = '')
    {
        if ($stamp == '') {
            $currentTime = self::getCetTime();
        } else {
            $currentTime = strtotime($stamp);
        }
        $month = date('m', $currentTime);
        $currentHour = date('H', $currentTime);
        $start = $GLOBALS['StartEndTimesAlert'][$month]['start'];
        $end = $GLOBALS['StartEndTimesAlert'][$month]['end'];

        return $currentHour >= $start && $currentHour <= $end;
    }

    /** @deprecated  */
    public static function checkUnitAndConvert($value, $unit)
    {
        ($unit === 'w') ? $returnValue = ($value / 1000 / 4) : $returnValue = $value;

        return $returnValue;
    }

    // Formatiere angegebene Zeit für SQL
    public function formatTimeStampToSql(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * @deprecated
     * erstezen durch getPdoConnection()
     */
    public static function connectToDatabase(): \mysqli
    {
        return new \mysqli('dedi6015.your-server.de', 'pvpluy_2', 'XD4R5XyVHUkK9U5i', 'pvp_data');
    }

    /**
     * @deprecated
     * ersetzen durch 'doctrine'
     */
    public static function connectToDatabaseAnlage(): \mysqli
    {
        return new \mysqli('dedi6015.your-server.de', 'pvpbase', '04qjYWk1oTf9gb7k', 'pvp_base');
    }

    /**
     * Replace all special chars to ascci, need the php normilzer-class.
     *
     * @throws Exception
     */
    public static function normalizeUtf8String(string $s): string
    {
        $original_string = $s;
        // Normalizer-class missing!
        if (!class_exists('Normalizer', $autoload = false)) {
            throw new Exception('Normalizer-class is missing ');
        }

        // maps German (umlauts) and other European characters onto two characters before just removing diacritics
        $s = preg_replace('@\x{00c4}@u', 'AE', $s);    // umlaut Ä => AE
        $s = preg_replace('@\x{00d6}@u', 'OE', $s);    // umlaut Ö => OE
        $s = preg_replace('@\x{00dc}@u', 'UE', $s);    // umlaut Ü => UE
        $s = preg_replace('@\x{00e4}@u', 'ae', $s);    // umlaut ä => ae
        $s = preg_replace('@\x{00f6}@u', 'oe', $s);    // umlaut ö => oe
        $s = preg_replace('@\x{00fc}@u', 'ue', $s);    // umlaut ü => ue
        $s = preg_replace('@\x{00f1}@u', 'ny', $s);    // ñ => ny
        $s = preg_replace('@\x{00ff}@u', 'yu', $s);    // ÿ => yu

        // maps special characters (characters with diacritics) on their base-character followed by the diacritical mark
        // exmaple:  Ú => U´,  á => a`
        $s = Normalizer::normalize($s, Normalizer::FORM_D);

        $s = preg_replace('@\pM@u', '', $s);    // removes diacritics

        $s = preg_replace('@\x{00df}@u', 'ss', $s);    // maps German ß onto ss
        $s = preg_replace('@\x{00c6}@u', 'AE', $s);    // Æ => AE
        $s = preg_replace('@\x{00e6}@u', 'ae', $s);    // æ => ae
        $s = preg_replace('@\x{0132}@u', 'IJ', $s);    // ? => IJ
        $s = preg_replace('@\x{0133}@u', 'ij', $s);    // ? => ij
        $s = preg_replace('@\x{0152}@u', 'OE', $s);    // Œ => OE
        $s = preg_replace('@\x{0153}@u', 'oe', $s);    // œ => oe

        $s = preg_replace('@\x{00d0}@u', 'D', $s);    // Ð => D
        $s = preg_replace('@\x{0110}@u', 'D', $s);    // Ð => D
        $s = preg_replace('@\x{00f0}@u', 'd', $s);    // ð => d
        $s = preg_replace('@\x{0111}@u', 'd', $s);    // d => d
        $s = preg_replace('@\x{0126}@u', 'H', $s);    // H => H
        $s = preg_replace('@\x{0127}@u', 'h', $s);    // h => h
        $s = preg_replace('@\x{0131}@u', 'i', $s);    // i => i
        $s = preg_replace('@\x{0138}@u', 'k', $s);    // ? => k
        $s = preg_replace('@\x{013f}@u', 'L', $s);    // ? => L
        $s = preg_replace('@\x{0141}@u', 'L', $s);    // L => L
        $s = preg_replace('@\x{0140}@u', 'l', $s);    // ? => l
        $s = preg_replace('@\x{0142}@u', 'l', $s);    // l => l
        $s = preg_replace('@\x{014a}@u', 'N', $s);    // ? => N
        $s = preg_replace('@\x{0149}@u', 'n', $s);    // ? => n
        $s = preg_replace('@\x{014b}@u', 'n', $s);    // ? => n
        $s = preg_replace('@\x{00d8}@u', 'O', $s);    // Ø => O
        $s = preg_replace('@\x{00f8}@u', 'o', $s);    // ø => o
        $s = preg_replace('@\x{017f}@u', 's', $s);    // ? => s
        $s = preg_replace('@\x{00de}@u', 'T', $s);    // Þ => T
        $s = preg_replace('@\x{0166}@u', 'T', $s);    // T => T
        $s = preg_replace('@\x{00fe}@u', 't', $s);    // þ => t
        $s = preg_replace('@\x{0167}@u', 't', $s);    // t => t

        // remove all non-ASCii characters
        $s = preg_replace('@[^\0-\x80]@u', '', $s);

        // possible errors in UTF8-regular-expressions
        if (empty($s)) {
            return $original_string;
        } else {
            return $s;
        }
    }

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
            'database_dsn' => 'mysql:dbname=pvp_data;host='.$dbdsn, // 'mysql:dbname=pvp_data;host=dedi6015.your-server.de'
            'database_user' => $dbusr,
            'database_pass' => $dbpass,
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

    public static function g4nLog($meldung, $logfile = 'logfile'): void
    {
        if ($meldung) {
            $currentDir = __DIR__;
            $logdatei = fopen("$currentDir/../../logs/" . $logfile . "-" . date("Y-m-d", time()) . ".txt", "a");
            fputs($logdatei, date("H:i:s", time()) . ' -- ' . $meldung . "\n");
            fclose($logdatei);
        }
    }

    public static function convertKeysToCamelCase($apiResponseArray): array
    {
        $arr = [];
        foreach ($apiResponseArray as $key => $value) {
            $key = lcfirst(implode('', array_map('ucfirst', explode('_', $key))));
            if (is_array($value)) {
                $value = self::convertKeysToCamelCase($value);
            }
            $arr[$key] = $value;
        }

        return $arr;
    }

    /**
     * Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms<br>
     * adjust Plant timestamp with offset from entity plant ($anlage->getAnlZeitzone()).
     *
     * @param Anlage $anlage
     * @param $stamp
     * @param false $reverse
     *
     * @return string
     * @throws Exception
     */
    public function timeShift(Anlage $anlage, $stamp, bool $reverse = false): string
    {
        $country = strtoupper($anlage->getCountry());
        $offset = Timezones::getRawOffset(self::getNearestTimezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(), $country));

        if (date('I', time()) == 1) {
            // summertime
            $offset -= 7200; // not sure why this is nessary
        } else {
            // wintertime
            $offset -= 3600; // not sure why this is nessary
        }
        $of = $offset / 3600;

        if ($of < 0) {
            $offset_time = strtotime($stamp) - $offset;
            if ($reverse) {
                $offset_time = strtotime($stamp) + $offset;
            }
            $result = date('Y-m-d H:i', $offset_time);
        } elseif ($of > 0) {
            $offset_time = strtotime($stamp) + $offset;
            if ($reverse) {
                $offset_time = strtotime($stamp) - $offset;
            }
            $result = date('Y-m-d H:i', $offset_time);
        } else {
            $result = date('Y-m-d H:i', strtotime($stamp));
        }

        return $result;
    }

    /**
     * as the name of the function describs, get the plants nearest timezone.
     *
     * @param float $cur_lat
     * @param float $cur_long
     * @param string $country_code
     * @return string
     */
    public function getNearestTimezone(float $cur_lat, float $cur_long, string $country_code = ''): string
    {
        $timezone_ids = ($country_code) ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code)
            : DateTimeZone::listIdentifiers();

        if ($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {
            $time_zone = '';
            $tz_distance = 0;

            // only one identifier?
            if (count($timezone_ids) == 1) {
                $time_zone = $timezone_ids[0];
            } else {
                foreach ($timezone_ids as $timezone_id) {
                    $timezone = new DateTimeZone($timezone_id);
                    $location = $timezone->getLocation();
                    $tz_lat = $location['latitude'];
                    $tz_long = $location['longitude'];

                    $theta = $cur_long - $tz_long;
                    $distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
                        + (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
                    $distance = acos($distance);
                    $distance = abs(rad2deg($distance));

                    if (!$time_zone || $tz_distance > $distance) {
                        $time_zone = $timezone_id;
                        $tz_distance = $distance;
                    }
                }
            }

            return $time_zone;
        }

        return 'unknown';
    }

    /**
     * Funktion um die Anzahle der ganzen Monate zwischen zwei Daten zu berecchnen
     * wobei z.B. der 1.3.2021 bis zum 31.3.2021 einem Monat entspricht.
     * Wenn die Anzahl der Tage zwischen den beiden Daten kleiner als die max Anzahl der Tage des Monats ($from) dann entspricht das 0 Monaten (Bsp: 1.3.2021.bis 25.3.2021).
     *
     * @param $from
     * @param $to
     * @return int
     */
    public function g4nDateDiffMonth($from, $to): int
    {
        $fromYear = (int) date('Y', strtotime($from));
        $fromMonth = (int) date('m', strtotime($from));
        $fromDay = (int) date('d', strtotime($from));
        $toYear = (int) date('Y', strtotime($to));
        $toMonth = (int) date('m', strtotime($to));
        $toDay = (int) date('d', strtotime($to));
        $daysInMonth = (int) date('t', strtotime($from));
        // prüfe, ob Start Monat und Jahr gleich dem End Monat und Jahr, wenn dann die Anzahl der Tage < max Tage des Monats dann kein ganzer Monat ($month = 0)
        if ($fromMonth == $toMonth && $fromYear == $toYear && $toDay - $fromDay < $daysInMonth) {
            $month = 1; // muss 1 damit auch Rumpf Monate bearbeitet werden ?? TODO: nicht sicher ob das immer passt - beobachetn
        } else {
            if ($fromMonth > $toMonth) {
                $month = 12 - $fromMonth + $toMonth + 1;
                if ($toYear - $fromYear > 1) {
                    $month += ($toYear - $fromYear) * 12;
                }
            } elseif ($fromMonth < $toMonth) {
                $month = $toMonth - $fromMonth + 1;
                if ($toYear - $fromYear > 0) {
                    $month += ($toYear - $fromYear) * 12;
                }
            } else {
                $month = 1;
                if ($toYear - $fromYear > 0) {
                    $month += ($toYear - $fromYear) * 12;
                }
            }
        }

        return $month;
    }

    public function printArrayAsTable(array $content): string
    {
        $precision = 4;
        $_html = "<div class='table-scroll'><table style='font-size: 90%'>";
        $_counter = 0;
        $_html .= '<thead>';
        foreach ($content as $key => $contentRow) {
            if ($_counter === 0) {
                $_html .= '<tr><th>Key</th>';
                foreach ($contentRow as $subkey => $subvalue) {
                    $_html .= '<th>'.substr($subkey, 0, 20).'</th>';
                }
                $_html .= '</tr>';
                $_html .= '</thead>';
                $_html .= '<tbody>';
            }
            $_html .= "<tr><td>$key</td>";
            foreach ($contentRow as $cell) {
                if (is_float($cell)) {
                    $_html .= '<td>'.round($cell, $precision).'</td>';
                } else {
                    $_html .= "<td>$cell</td>";
                }
            }
            $_html .= '</tr>';
            ++$_counter;
        }

        $_html .= '</tbody>';
        $_html .= '</table></div>';

        return $_html;
    }

    /**
     * Ermittelt aus dem übergebenen Array den Mittelwert, wobei 0 Werte nicht in die Berechnung einfließen.
     */
    public static function mittelwert(?array $werte): ?float
    {
        if ($werte === null) {
            return null;
        }
        $divisor = $divident = 0;
        foreach ($werte as $wert) {
            if ((float) $wert > 0) {
                ++$divisor;
                $divident += (float) $wert;
            }
        }

        return ($divisor > 0) ? $divident / $divisor : null;
    }

    /*
     * With this function we will remove the elements of the second array from the first one
     * we will return an array with 3 array
     * 1.- the elements left in the first array after the subtraction
     * 2.- the elements left in the second array after the subtraction
     * 3.- the elements that were removed (the intersection)
     */
    static public function subArrayFromArray(Array $array1, Array $array2) :mixed
    {
        $returnArray['intersection'] = array_intersect($array1, $array2);
        $returnArray['array1'] = array_diff($array1, $array2);
        $returnArray['array2'] = array_diff($array2, $array1);
        return $returnArray;
    }
}
