<?php

namespace App\Service;


use App\Entity\Anlage;
use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;


class WeatherServiceNew
{
    use G4NTrait;

    public function __construct()
    {

    }

    public function loadWeatherDataUP(WeatherStation $weatherStation, $date = 0)
    {
        $output = '';
        $conn = self::getPdoConnection();  // DB Connection herstellen
        // Variablen festlegen bzw. generieren aus der db
        if ($date > 0) {
            $timestamp = $date;
        } else {
            $timestamp = self::getCetTime();//g4nTimeCET();
        }

        $datum = date("ymd", $timestamp);
        $dateminus = date('Y-m-d', strtotime($datum . " -1 days"));
        $verzeichnis = "http://upgmbh-logstar.de/daten/180927-upls-58/"; //Verzeichnis der Daten bei UP

        $slash = "/";
        $dateianfang = "TD";   //Dateianfang Tagesdaten
        $dateiendung = ".dat"; //Dateiendung
        $trenner = " ";        //Trennzeichen Leer=Tabstop

        $weatherStationIdent = $weatherStation->getDatabaseIdent();


        $output .= "Wetterstation: $weatherStationIdent<br>";
        $dateiname = $dateianfang . $datum . $dateiendung; //generierte Dateiname
        $urlfile = $verzeichnis . $weatherStationIdent . $slash . $dateiname;
        $spalte = [];
        $csvInhalt = file("$urlfile", FILE_SKIP_EMPTY_LINES);

        if (! $this->array_empty($csvInhalt)) {
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
            $sqlstamp = '';
            $sql_array = [];
            // Zuordnung der cvs daten in Variablen.
            foreach ($spalte as $out) {
                if ($weatherStation->getType() === "UPold") {
                    $zeit = $out[1];
                    $date = $out[2];
                    $sqlstamp = '20' . substr($date, 6, 2) . '-' . substr($date, 3, 2) . '-' . substr($date, 0, 2) . " $zeit";
                    $at_avg = $out[4];
                    $pt_avg = $out[7];
                    $gi_avg = $out[10];
                    $gmod_avg = $out[13];
                    $wind = @$out[17];
                    if ($gi_avg < 0) $gi_avg = 0;
                    if ($gmod_avg < 0) $gmod_avg = 0;
                }
                elseif ($weatherStation->getType() === "UPnew") {
                    $zeit = $out[1];
                    $date = $out[2];
                    $sqlstamp = '20' . substr($date, 6, 2) . '-' . substr($date, 3, 2) . '-' . substr($date, 0, 2) . " $zeit";
                    $at_avg = $out[4];
                    $pt_avg = $out[7];
                    $gi_avg = $out[13];
                    $gmod_avg = $out[10];
                    $wind = 0;
                }
                elseif ($weatherStation->getType() === "UPv1120") {
                    $zeit = $out[1];
                    $date = $out[2];
                    $sqlstamp = '20' . substr($date, 6, 2) . '-' . substr($date, 3, 2) . '-' . substr($date, 0, 2) . " $zeit";
                    $at_avg = $out[5];
                    $pt_avg = $out[19];
                    $gi_avg = $out[16];
                    $gmod_avg = $out[13];
                    $wind = 0;
                    if ($gi_avg < 0) $gi_avg = 0;
                    if ($gmod_avg < 0) $gmod_avg = 0;
                }

                // wenn ein Strahlungswert 0 ist und der andere kleienr als 50 dann setzte beide auf 0
                // soll positive Strahlungswerte mitten in der nacht verhindern
                if ($gmod_avg == 0 && $gi_avg <= 30) $gi_avg = 0;
                if ($gi_avg == 0 && $gmod_avg <= 30) $gmod_avg = 0;


                $output .= $weatherStation->getType() . " -> $zeit $date -- $at_avg | $pt_avg | $gi_avg | $gmod_avg | $wind <br>";

                $sql_array[] = [
                    "anl_intnr" => $weatherStationIdent,
                    "stamp" => $sqlstamp,
                    "at_avg" => $at_avg,
                    "pt_avg" => $pt_avg,
                    "gi_avg" => $gi_avg,
                    "gmod_avg" => $gmod_avg,
                    "wind_speed" => $wind
                ];
            }
            $spalte = [];

            foreach ($sql_array as $row) {
                $anlIntNr = $row['anl_intnr'];
                $stamp = $row['stamp'];
                $tempAmbientAvg     = str_replace(',', '.', $row['at_avg']);
                $tempPannleAvg      = str_replace(',', '.', $row['pt_avg']);
                $gLower             = str_replace(',', '.', $row['gi_avg']);
                if ($gLower < 0) $gLower = 0;
                $gUpper             = str_replace(',', '.', $row['gmod_avg']);
                if ($gUpper < 0) $gUpper = 0;
                $windSpeed          = str_replace(',', '.', $row['wind_speed']);
                $sql_insert = "INSERT INTO " . $weatherStation->getDbNameWeather() . " 
                        SET anl_intnr = '$anlIntNr', stamp = '$stamp', 
                            at_avg = '$tempAmbientAvg', pt_avg = '$tempPannleAvg', gi_avg = '$gLower', gmod_avg = '$gUpper', wind_speed = '$windSpeed',
                            g_upper = '$gUpper', g_lower = '$gLower', temp_pannel = '$tempPannleAvg', temp_ambient = '$tempAmbientAvg'
                        ON DUPLICATE KEY UPDATE  
                            at_avg = '$tempAmbientAvg', pt_avg = '$tempPannleAvg', gi_avg = '$gLower', gmod_avg = '$gUpper', wind_speed = '$windSpeed',
                            g_upper = '$gUpper', g_lower = '$gLower', temp_pannel = '$tempPannleAvg', temp_ambient = '$tempAmbientAvg'";

               $conn->exec($sql_insert);
            }
            $sql_array = [];

        } else {
            $output .= "FEHLER: csvinhalt leer";
        }
        $output .= "<h3>END Weather import</h3>";
        $conn = null;

        return $output;
    }

    // Prüfen ob ARRAY LEER IST
    private function array_empty($arr)
    {
        foreach ($arr as $val) {
            if ($val != '') { return false; }
        }
        return true;
    }
}