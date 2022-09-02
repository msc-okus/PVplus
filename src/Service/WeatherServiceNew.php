<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\DayLightDataRepository;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;

class WeatherServiceNew
{
    use G4NTrait;

    private DayLightDataRepository $dayrepo;

    private EntityManagerInterface $em;

    private AnlagenRepository $anlRepo;

    public function __construct(DayLightDataRepository $dayrepo, EntityManagerInterface $em, AnlagenRepository $anlRepo)
    {
        $this->dayrepo = $dayrepo;
        $this->em = $em;
        $this->anlRepo = $anlRepo;
    }

    public function loadWeatherDataUP(WeatherStation $weatherStation, $date = 0): string
    {
        $output = '';
        $conn = self::getPdoConnection();  // DB Connection herstellen
        // Variablen festlegen bzw. generieren aus der db
        if ($date > 0) {
            $timestamp = $date;
        } else {
            $timestamp = self::getCetTime(); // g4nTimeCET();
        }

        $datum = date('ymd', $timestamp);
        $dateminus = date('Y-m-d', strtotime($datum.' -1 days'));
        $verzeichnis = 'http://upgmbh-logstar.de/daten/180927-upls-58/'; // Verzeichnis der Daten bei UP

        $slash = '/';
        $dateianfang = 'TD';   // Dateianfang Tagesdaten
        $dateiendung = '.dat'; // Dateiendung
        $trenner = ' ';        // Trennzeichen Leer=Tabstop

        $weatherStationIdent = $weatherStation->getDatabaseIdent();

        $output .= "Wetterstation: $weatherStationIdent<br>";
        $dateiname = $dateianfang.$datum.$dateiendung; // generierte Dateiname
        $urlfile = $verzeichnis.$weatherStationIdent.$slash.$dateiname;
        $spalte = [];
        $csvInhalt = file("$urlfile", FILE_SKIP_EMPTY_LINES);

        if (!$this->array_empty($csvInhalt)) {
            // TODO: erstezen durch 'str_getcsv'
            foreach ($csvInhalt as $inhalt) { // Die Zeilen der CSV Datei lesen und in Array schreiben
                $spalte[] = explode($trenner, $inhalt);
            }

            $last = count($spalte);
            $zeit = '';
            $date = '';
            $at_avg = '';
            $pt_avg = '';
            $gmod_avg = '';
            $gi_avg = '';
            $wind = '';
            $sqlstamp = '';
            $sql_array = [];
            // Zuordnung der cvs daten in Variablen.
            foreach ($spalte as $out) {
                if ($weatherStation->getType() === 'UPold') {
                    $zeit = $out[1];
                    $date = $out[2];
                    $sqlstamp = '20'.substr($date, 6, 2).'-'.substr($date, 3, 2).'-'.substr($date, 0, 2)." $zeit";
                    $at_avg = $out[4];
                    $pt_avg = $out[7];
                    $gi_avg = $out[10];
                    $gmod_avg = $out[13];
                    $wind = $out[17];
                    if ($gi_avg < 0) {
                        $gi_avg = 0;
                    }
                    if ($gmod_avg < 0) {
                        $gmod_avg = 0;
                    }
                } elseif ($weatherStation->getType() === 'UPnew') {
                    $zeit = $out[1];
                    $date = $out[2];
                    $sqlstamp = '20'.substr($date, 6, 2).'-'.substr($date, 3, 2).'-'.substr($date, 0, 2)." $zeit";
                    $at_avg = $out[4];
                    $pt_avg = $out[7];
                    $gi_avg = $out[13];
                    $gmod_avg = $out[10];
                    $wind = 0;
                } elseif ($weatherStation->getType() === 'UPv1120') {
                    $zeit = $out[1];
                    $date = $out[2];
                    $sqlstamp = '20'.substr($date, 6, 2).'-'.substr($date, 3, 2).'-'.substr($date, 0, 2)." $zeit";
                    $at_avg = $out[10];  // Ambient (Luft) Temperature
                    $pt_avg = $out[19];  // Pannel Temperature
                    $gi_avg = $out[7];   // unterer Sensor
                    $gmod_avg = $out[6]; // oberer Sensor
                    $wind = 0;
                    if ($gi_avg < 0) {
                        $gi_avg = 0;
                    }
                    if ($gmod_avg < 0) {
                        $gmod_avg = 0;
                    }
                }
                // correct stamp if DLS
                if (date('I', strtotime($sqlstamp)) == 1) {
                    $sqlstamp = date('Y-m-d H:i', strtotime($sqlstamp) + 3600);
                }

                $output .= $weatherStation->getType()." -> $zeit $date -- $at_avg | $pt_avg | $gi_avg | $gmod_avg | $wind <br>";
                $sql_array[] = [
                    'anl_intnr' => $weatherStationIdent,
                    'stamp' => $sqlstamp,
                    'at_avg' => str_replace(',', '.', $at_avg),
                    'pt_avg' => str_replace(',', '.', $pt_avg),
                    'gi_avg' => str_replace(',', '.', $gi_avg),
                    'gmod_avg' => str_replace(',', '.', $gmod_avg),
                    'wind_speed' => str_replace(',', '.', $wind),
                ];
            }

            foreach ($sql_array as $row) {
                $anlIntNr = $row['anl_intnr'];
                $stamp = $row['stamp'];
                $tempAmbientAvg = $row['at_avg'];
                $tempPannleAvg = $row['pt_avg'];
                $gLower = $row['gi_avg'];
                if ($gLower < 0) {
                    $gLower = 0;
                }
                $gUpper = $row['gmod_avg'];
                if ($gUpper < 0) {
                    $gUpper = 0;
                }
                $windSpeed = $row['wind_speed'];
                $sql_insert = 'INSERT INTO '.$weatherStation->getDbNameWeather()." 
                        SET anl_intnr = '$anlIntNr', stamp = '$stamp', 
                            at_avg = '$tempAmbientAvg', pt_avg = '$tempPannleAvg', gi_avg = '$gLower', gmod_avg = '$gUpper', wind_speed = '$windSpeed',
                            g_upper = '$gUpper', g_lower = '$gLower', temp_pannel = '$tempPannleAvg', temp_ambient = '$tempAmbientAvg'
                        ON DUPLICATE KEY UPDATE  
                            at_avg = '$tempAmbientAvg', pt_avg = '$tempPannleAvg', gi_avg = '$gLower', gmod_avg = '$gUpper', wind_speed = '$windSpeed',
                            g_upper = '$gUpper', g_lower = '$gLower', temp_pannel = '$tempPannleAvg', temp_ambient = '$tempAmbientAvg'";

                $conn->exec($sql_insert);
            }
            unset($sql_array);
            unset($spalte);
        } else {
            $output .= 'FEHLER: csvinhalt leer';
        }
        $output .= '<h3>END Weather import</h3>';
        $conn = null;

        return $output;
    }

    // Prüfen ob ARRAY LEER IST
    private function array_empty($arr)
    {
        foreach ($arr as $val) {
            if ($val != '') {
                return false;
            }
        }

        return true;
    }

    /** Given a plant and no date it will return the sunrise info of the given plant for the current day
     * Given a plant and a time it will return the sunrise info of the given plant for the given date.
     */
    public function getSunrise(Anlage $anlage, ?string $time = null): array
    {
        $sunrisedata = date_sun_info(strtotime($time), (float) $anlage->getAnlGeoLat(), (float) $anlage->getAnlGeoLon());

        $returnArray['sunrise'] = $time.' '.date('H:i', $sunrisedata['sunrise']);
        $returnArray['sunset'] = $time.' '.date('H:i', $sunrisedata['sunset']);

        return $returnArray;
    }

    public function getNearestTimezone($cur_lat, $cur_long, $country_code = ''): string
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
}
