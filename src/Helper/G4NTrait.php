<?php

namespace App\Helper;

require_once __DIR__ . '/../../public/config.php';

use PDO;
use PDOException;
use App\Entity\Anlage;
use Symfony\Component\Intl\Timezones;
use Symfony\Component\Validator\Constraints\Timezone;
use DateTimeZone;
use PhpOffice\PhpSpreadsheet\Calculation\DateTime;

trait G4NTrait
{
    public static function reportStati(): array
    {
        // Values for Report Status
        $reportStati[0]  = 'final';
        $reportStati[5]  = 'proof reading';
        $reportStati[9]  = 'archive (g4n only)';
        $reportStati[10] = 'draft (g4n only)';
        $reportStati[11] = 'wrong (g4n only)';

        return $reportStati;
    }

    public static function getCetTime($format = 'timestamp'){
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify('+1 hours');

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
     * $vall = interne Zeitzohne der Anlage (Korrektur der Zeit)
     *
     * return Zeitstempel im SQL Format
     */
    public static function timeAjustment($timestamp, float $val = 0, $reverse = false)
    {
        $format     = 'Y-m-d H:i:s';
        // Sollte die Zeit als String übergeben worden sein, dann wandele in TimeStamp um
        if (gettype($timestamp) != 'integer') $timestamp = strtotime($timestamp);
        ($reverse) ? $timestamp -= ($val * 3600) : $timestamp += ($val * 3600);

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
        ($unit === 'w') ? $returnValue = round($value / 1000 / 4, 2) : $returnValue = round($value,2);

        return $returnValue;
    }

    //Formatiere angegebene Zeit für SQL
    function formatTimeStampToSql(Int $timestamp):string {
        return date('Y-m-d H:i:s', $timestamp);
    }


    /**
     * Umrechnung Globalstrahlung in Modulstrahlung
     * @param Anlage $anlage (Anlage aus der dieverse Parameter gelesen werden)
     * @param \DateTime $stamp (Zeitpunkt für den die Umrechnung erfolgen soll)
     * @param float $ghi (Globalstrahlung zu oben genantem Zeitpunkt)
     * @return float (Berechnete Modulstrahlung)
     */
    public function Hglobal2Hmodul(Anlage $anlage, \DateTime $stamp, float $ghi = 0.0): float
    {

        $breite = $anlage->getAnlGeoLat();
        $laenge = $anlage->getAnlGeoLon();
        $bezugsmeridian = 15;   // muss auch aus Anlage kommen, Feld existiert aber noch nicht (kann man da aus breite / Länge berechnen?)
        $azimuthModul = 180;    // muss auch aus Anlage kommen Feld existiert aber noch nicht
        $neigungModul = 20;     // muss auch aus Anlage kommen Feld existiert aber noch nicht

        $limitAOI       = deg2rad(78);

        $tag = $stamp->format('z');
        $tag++; // Tag um eins erhöhen, da Formel annimmt das der erste Tag im Jahr = 1 ist und nicht 0 wie format('z') zurück gibt
        $stunde = (integer)$stamp->format('G');

        $moz            = (($laenge - $bezugsmeridian) / 15) + $stunde;
        $lo             = deg2rad(279.3 + 0.9856 * $tag);
        $zgl            = 0.1644 * SIN(2 * ($lo + deg2rad(1.92) * SIN($lo + deg2rad(77.3)))) - 0.1277 * SIN($lo + deg2rad(77.3));
        $woz            = $moz + rad2deg($zgl) / 60;
        $stdWink        = deg2rad(15 * ($woz - 12));
        $deklination    = deg2rad((-23.45) * COS ((2 * PI() / 365.25) * ( $tag + 10 )));
        $sonnenhoehe    = ASIN(SIN($deklination)*SIN(deg2rad($breite))+COS($deklination)*COS(deg2rad($breite))*COS($stdWink));
        $atheta         = ASIN((-(COS($deklination)*SIN($stdWink)))/COS($sonnenhoehe));
        $azimuth        = 180 - rad2deg($atheta);
        $zenitwinkel    = 90 - rad2deg($sonnenhoehe);
        $aoi            = 1 / COS(COS(deg2rad($zenitwinkel))*COS(deg2rad($neigungModul))+SIN(deg2rad($zenitwinkel))*SIN(deg2rad($neigungModul))*COS(deg2rad($azimuth-$azimuthModul)));
        ($aoi > $limitAOI) ? $aoiKorr = $limitAOI : $aoiKorr = $aoi;
        $dayAngel       = 6.283185*($tag-1)/365;
        $etr            = 1370*(1.00011+0.034221*COS($dayAngel)+0.00128*SIN($dayAngel)+0.000719*COS(2*$dayAngel)+0.000077*SIN(2*$dayAngel));
        ($zenitwinkel < 80) ? $am = (1/(COS(deg2rad($zenitwinkel))+0.15/(93.885-$zenitwinkel)**1.253)) : $am = 0;
        ($am > 0)           ? $kt = $ghi/(COS(deg2rad($zenitwinkel))*$etr) : $kt = 0.0;
        $dniMod = 0.0;
        if ($kt>0) {
            if ($kt>=0.6) {
                $a = -5.743+21.77*$kt-27.49*$kt**2+11.56*$kt**3;
                $b = 41.4-118.5*$kt+66.05*$kt**2+31.9*$kt**3;
                $c = -47.01+184.2*$kt-222*$kt**2+73.81*$kt**3;
            } elseif ($kt<0.6) {
                $a = 0.512-1.56*$kt+2.286*$kt**2-2.222*$kt**3;
                $b = 0.37+0.962*$kt;
                $c = -0.28+0.932*$kt-2.048*$kt**2;
            } else {
                $a = 0;
                $b = 0;
                $c = 0;
            }
            $dkn = $a+$b*EXP($c*$am);
            $knc = 0.886-0.122*$am+0.0121*($am)**2-0.000653*($am)**3+0.000014*($am)**4;
            $dni = $etr*($knc-$dkn);
            $dniMod = $dni*COS($aoiKorr);
        }
        $diffusMod = $ghi - $dniMod;

        $gmod1          = $aoi * $dniMod + $diffusMod; // Modulstrahlung 1
        $iam            = 1-0.05*((1/COS($aoi)-1));
        $gmod2          = $gmod1-$iam; // Modulstrahlung 2
        if ($gmod2 < 0) $gmod2 = 0; // Negative Werte machen keinen Sinn

        return $gmod2;
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

    public static function getPdoConnection():\PDO
    {
        $config = [
            'database_dsn' => 'mysql:dbname=pvp_data;host=dedi6015.your-server.de',
            'database_user' => 'pvpluy_2',
            'database_pass' => 'XD4R5XyVHUkK9U5i'
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
     * Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
     **/
    public function timeShift(Anlage $anlage, $stamp, $reverse = false)
    {
        $country = strtoupper($anlage->getCountry());

        $offset = Timezones::getRawOffset(self::get_nearest_timezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(), $country));
        $offset = $offset - 7200;

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
            $result = $stamp;
        }

        return $result;
    }

    //as the name of the function describs, get the plants nearest timezone
    public function get_nearest_timezone($cur_lat, $cur_long, $country_code = '')
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
        $fromYear = (int)date('Y', strtotime($from));
        $fromMonth = (int)date('m', strtotime($from));
        $fromDay = (int)date('d', strtotime($from));
        $toYear = (int)date('Y', strtotime($to));
        $toMonth = (int)date('m', strtotime($to));
        $toDay = (int)date('d', strtotime($to));
        $daysInMonth = (int)date('t', strtotime($from));

        //prüfe ob Start Monat und Jahr gleich dem End Monat und Jahr, wenn dann die Anzahl der Tage < max Tage des Monats dann kein ganzer Monat ($month = 0)
        if ($fromMonth == $toMonth && $fromYear == $toYear && $toDay - $fromDay < $daysInMonth) {
            $month = 1; // muss 1 damit auch Rumpf Monate bearbeitet werden ?? TODO: nicht sicher ob das immer passt - beobachetn
        } else {
            if ($fromMonth > $toMonth) {
                $month = 12 - $fromMonth + $toMonth + 1;
                if ($fromYear - $toYear > 1) $month += ($toYear - $fromYear) * 12;
            } elseif ($fromMonth < $toMonth) {
                $month = $toMonth - $fromMonth + 1;
                if ($fromYear - $toYear > 0) $month += ($toYear - $fromYear) * 12;
            } else {
                $month = 1;
                if ($fromYear - $toYear > 0) $month += ($toYear - $fromYear) * 12;
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
                    $_html .= "<td>" . round($cell,6) . "</td>";
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
}