<?php
/**
 * MS 11/2023 G4N
 * Service zum abholen der Forecast IRR Daten über die API Open-Meteo
 * Um die Genauigkeit zu verbessern wurden weitere Standorte in einem Umkeis mit einbezogen.
 * wert global_tilted_irradiance hinzu gefuegt neu
 * Tilt und Azimut hinzugefügt
 */

namespace App\Service\Forecast;

class APIOpenMeteoService {
    private string $lon;
    private string $lat;

    /**
     * The constructor
     * @param string $input_gl
     * @param string $input_gb
     * @param int $azzimut
     * @param int $tilt
     */
    public function __construct(string $input_gl, string $input_gb,int $tilt = 0,int $azzimut = 0) {
        $this->lat = number_format($input_gb, 2); // Latitude
        $this->lon = number_format($input_gl, 2); // Longitude
        $this->azzimut = $azzimut; // Azzimut
        $this->tilt = $tilt;  // Tilt
    }

    // Curl ini -- making the Request
    public function get_json_data_curl($hdays = 0, $fdays = 0, $lat = '', $lon = '', $tilt = 0, $azzimut = 0): bool|string
    {
        // Curl response from Open Meteo
        ($hdays > 0) ? $historydays = "&past_days=$hdays" : $historydays = "";
        ($fdays > 0) ? $forecastdays = "&forecast_days=$fdays" : $forecastdays = "";
        $set_tilt_azimut = "&tilt=$tilt&azimuth=$azzimut";
        set_time_limit(550);
        $curl = curl_init();

        curl_setopt_array($curl, [CURLOPT_URL => 'https://api.open-meteo.com/v1/forecast?latitude=' . $lat . '&longitude=' . $lon . $historydays . $forecastdays . '&minutely_15=temperature_2m,windspeed_10m,global_tilted_irradiance_instant,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&hourly=temperature_2m,windspeed_10m,global_tilted_irradiance_instant,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&timezone=Europe%2FBerlin'.$set_tilt_azimut, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '', CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'GET', CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0]);
        #curl_setopt_array($curl,[CURLOPT_URL => 'https://api.open-meteo.com/v1/forecast?latitude='.$lat.'&longitude='.$lon.'&minutely_15=temperature_2m,windspeed_10m,global_tilted_irradiance,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&hourly=temperature_2m,windspeed_10m,global_tilted_irradiance,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&timezone=Europe%2FBerlin&start_date=2023-08-08&end_date=2023-08-12', CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '', CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'GET']);
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            echo curl_error($curl);
        }
        curl_close($curl);

        return $response;
    }
    /**
     * Sorting the curl request and build an array with 15 Minutes Data for 3 Days and hourly Data for 7 Days
     */
    public function make_sortable_data(): array
    {
        # $coords = $this->getBoundingRadius($this->lat, $this->lon, $nos,10); # x Coordinaten vom x/9 KM Radius von Standort
        $nos = 14; #NumberOfSegments - weitere Standorte
        $cn = 0;

        $coords = $this->convert([$this->lat, $this->lon], 4, $nos); # errechnet 14 Coordinaten im 4 KM Radius von und mit Standort
        // Auslesen der Standorte im Umkeis.
        foreach ($coords[0] as $value) {
            $lat = $value['lat'];
            $lon = $value['lon'];
            $daswi15 = $dadni15 = $dadhi15 = $daghi15 = $datmp15 = $dawds15 = $dagti15 = 0;
            $daswi60 = $dadni60 = $dadhi60 = $daghi60 = $datmp60 = $dawds60 = $dagti60 = 0;
            $dataarray = json_decode((string)$this->get_json_data_curl(4, 7, $lat, $lon, $this->tilt, $this->azzimut), null, 512, JSON_THROW_ON_ERROR);

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
                $dagti15 = $dataarray->minutely_15->global_tilted_irradiance_instant[$key];
                $minarray[$date]['minute'][$sqldate][$cn] = ["gti" => $dagti15,"dni" => $dadni15, "dhi" => $dadhi15, "ghi" => $daghi15, "swi" => $daswi15, "tmp" => $datmp15, "wds" => $dawds15];
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
                $dagti60 = $dataarray->hourly->global_tilted_irradiance_instant[$key];
                $hrarray[$date]['hourly'][$sqldate][$cn] = ["gti" => $dagti60 , "dni" => $dadni60 , "dhi" => $dadhi60 , "ghi" => $daghi60 , "swi" => $daswi60 , "tmp" => $datmp60 , "wds" => $dawds60 ];
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
    public function getBoundingRadius($lat, $lon, $radiusklein, $radiusgross): array
    {
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
    public function convert($center, $radius, $numberOfSegments = 360): array
    {
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