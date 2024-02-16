<?php

namespace App\Service\Forecast;
/**
 * MS 11/2023
 * Service zum abholen der Forecast daten über den Dienst Open-Meteo
 */

class APIOpenMeteoService {
    /**
     * The constructor
     * @param string $input_gl
     * @param string $input_gb
     */
    public function __construct($input_gl, $input_gb) {
        $this->lat = number_format($input_gb, 2); // latitude
        $this->lon = number_format($input_gl, 2); // longitude
    }

    // Curl ini -- making the reguest
    public function get_json_data_curl($hdays = 0, $fdays = 0, $lat, $lon) {
        // Curl response from Open Meteo
        ($hdays > 0) ? $historydays = "&past_days=$hdays" : $historydays = "";
        ($fdays > 0) ? $forecastdays = "&forecast_days=$fdays" : $forecastdays = "";
        set_time_limit(550);
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => 'https://api.open-meteo.com/v1/forecast?latitude=' . $lat . '&longitude=' . $lon . $historydays . $forecastdays . '&minutely_15=temperature_2m,windspeed_10m,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&hourly=temperature_2m,windspeed_10m,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&timezone=Europe%2FBerlin', CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '', CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'GET']);

        #curl_setopt_array($curl,[CURLOPT_URL => 'https://api.open-meteo.com/v1/forecast?latitude='.$lat.'&longitude='.$lon.'&minutely_15=temperature_2m,windspeed_10m,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&hourly=temperature_2m,windspeed_10m,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&timezone=Europe%2FBerlin&start_date=2023-08-08&end_date=2023-08-12', CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '', CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'GET']);

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    /**
     * Sorting the curl request and build an array with 15 Minutes Data for 3 Days and hourly Data for 7 Days
     */
    public function make_sortable_data() :array {
        // Um die Genauigkeit zu verbessern werden 10 weitere Standorte im Umkeis mit eingebunden.
        # $coords = $this->getBoundingRadius($this->lat, $this->lon, 5,9); # 5 Coordinaten vom 5/9 KM Radius von Standort
        $coords = $this->convert([$this->lat, $this->lon], 5, 10); # 10 Coordinaten vom 5 KM Radius von Standort
        $cn = 1;

        // Auslesen der Standorte im Umkeis.
        foreach ($coords[0] as $value) {
            $lat = $value['lat'];
            $lon = $value['lon'];

            $dataarray = json_decode((string)$this->get_json_data_curl(4, 7, $lat, $lon), null, 512, JSON_THROW_ON_ERROR);

            $datis15 = $dataarray->minutely_15->time;
            $datis60 = $dataarray->hourly->time;

            // build Array for Minutely Data
            foreach ($datis15 as $key => $value) {
                $thedate = date('Y-m-d-H-i-z', strtotime((string)$dataarray->minutely_15->time[$key]));
                [$year, $month, $day, $hour, $minute, $dayofyear] = explode("-", $thedate);
                $sqldate = "$year-$month-$day $hour:$minute";
                $date = "$year-$month-$day";
                $daswi15 = $dataarray->minutely_15->shortwave_radiation_instant[$key];
                $dadni15 = $dataarray->minutely_15->direct_normal_irradiance_instant[$key];
                $dadhi15 = $dataarray->minutely_15->diffuse_radiation_instant[$key];
                $daghi15 = $dataarray->minutely_15->direct_radiation_instant[$key];
                $datmp15 = $dataarray->minutely_15->temperature_2m[$key];
                $dawds15 = $dataarray->minutely_15->windspeed_10m[$key];
                #$minarray[$date]['minute'][$key] = ["ts" => $sqldate, "year" => $year, "month" => $month, "day" => $day, "doy" => $dayofyear + 1, "hour" => $hour, "minute" => $minute, "dni" => $dadni15, "dhi" => $dadhi15, "ghi" => $daghi15, "swi" => $daswi15, "tmp" => $datmp15, "wds" => $dawds15];
                $minarray[$date]['minute'][$sqldate][$cn] = ["dni" => $dadni15, "dhi" => $dadhi15, "ghi" => $daghi15, "swi" => $daswi15, "tmp" => $datmp15, "wds" => $dawds15];
             }

            // build Array for Hourly Data
            foreach ($datis60 as $key => $value) {
                $thedate = date('Y-m-d-H-i-z', strtotime((string)$dataarray->hourly->time[$key]));
                [$year, $month, $day, $hour, $minute, $dayofyear] = explode("-", $thedate);
                $date = "$year-$month-$day";
                $sqldate = "$year-$month-$day $hour:$minute";
                $daswi60 = $dataarray->hourly->shortwave_radiation_instant[$key];
                $dadni60 = $dataarray->hourly->direct_normal_irradiance_instant[$key];
                $dadhi60 = $dataarray->hourly->diffuse_radiation_instant[$key];
                $daghi60 = $dataarray->hourly->direct_radiation_instant[$key];
                $datmp60 = $dataarray->hourly->temperature_2m[$key];
                $dawds60 = $dataarray->hourly->windspeed_10m[$key];
                #$hrarray[$date]['hourly'][$key] = ["ts" => $sqldate, "year" => $year, "month" => $month, "day" => $day, "doy" => $dayofyear + 1, "hour" => $hour, "minute" => $minute, "dni" => $dadni60, "dhi" => $dadhi60, "ghi" => $daghi60, "swi" => $daswi60, "tmp" => $datmp60, "wds" => $dawds60];
                $hrarray[$date]['hourly'][$sqldate][$cn] = ["dni" => $dadni60, "dhi" => $dadhi60, "ghi" => $daghi60, "swi" => $daswi60, "tmp" => $datmp60, "wds" => $dawds60];
            }

            $cn++;

        }

        // Merge the two Array for output
        $outarray = array_merge_recursive($minarray, $hrarray);
        return $outarray;

    }
    /**
     * Funktion zur Umkreisberechung anhand Lat und Lon mittels Radius in Ecken
     */
    public function getBoundingRadius($lat, $lon, $radiusklein, $radiusgross) {
        $earth_radius = 6371;
        $maxLatr1 = $lat + rad2deg($radiusklein / $earth_radius);
        $minLatr1 = $lat - rad2deg($radiusklein / $earth_radius);
        $maxLonr1 = $lon + rad2deg($radiusklein / $earth_radius / cos(deg2rad($lat)));
        $minLonr1 = $lon - rad2deg($radiusklein / $earth_radius / cos(deg2rad($lat)));
        $maxLatr2 = $lat + rad2deg($radiusgross / $earth_radius);
        $minLatr2 = $lat - rad2deg($radiusgross / $earth_radius);
        $maxLonr2 = $lon + rad2deg($radiusgross / $earth_radius / cos(deg2rad($lat)));
        $minLonr2 = $lon - rad2deg($radiusgross / $earth_radius / cos(deg2rad($lat)));

        return array(
            "center" => array("lat" => $lat, "lon" => $lon),
            "nw1" => array("lat" => $maxLatr1, "lon" => $minLonr1),
            "ne1" => array("lat" => $maxLatr1, "lon" => $maxLonr1),
            "sw1" => array("lat" => $minLatr1, "lon" => $minLonr1),
            "se1" => array("lat" => $minLatr1, "lon" => $maxLonr1),
            "nw2" => array("lat" => $maxLatr2, "lon" => $minLonr2),
            "ne2" => array("lat" => $maxLatr2, "lon" => $maxLonr2),
            "sw2" => array("lat" => $minLatr2, "lon" => $minLonr2),
            "se2" => array("lat" => $minLatr2, "lon" => $maxLonr2)
        );
    }
    /**
     * Funktion zur Umkreisberechung anhand Lat und Lon mittels Radius als Kreis
     */
    public function convert($center, $radius, $numberOfSegments = 360) {
        $lat = $center[0];
        $lon = $center[1];
        $n = $numberOfSegments;
        $flatCoordinates = Array();
        for ($i = 0; $i < $n; $i++) {
            $bearing = 2 * M_PI * $i / $n;
            $flatCoordinates[$i] = $this->offset($center, $radius, $bearing);
        }

        $flatCoordinates[$n] = ["lon" => $lon, "lat" => $lat];
        return [$flatCoordinates];
    }

    public function offset($c1, $distance, $bearing) :array {
        $earth_radius = 6371;
        $lat1 = deg2rad($c1[0]);
        $lon1 = deg2rad($c1[1]);
        $dByR = $distance /  $earth_radius; // convert dist to angular distance in radians
        $lat = asin(sin($lat1) * cos($dByR) + cos($lat1) * sin($dByR) * cos($bearing));
        $lon = $lon1 + atan2(sin($bearing) * sin($dByR) * cos($lat1),cos($dByR) - sin($lat1) * sin($lat));
        $lon = fmod($lon + 3 * M_PI,2 * M_PI) - M_PI;

        return ["lon" =>rad2deg($lon), "lat" =>rad2deg($lat)];
    }

}
