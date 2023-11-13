<?php

namespace App\Service\Forecast;
// MS 10/23
class APIOpenMeteoService {
     /**
     * The constructor
     * @param string $input_gl
     * @param string $input_gb
     */
    public function __construct($input_gl,$input_gb) {
        $this->lat = number_format($input_gb,2); // latitude
        $this->lon = number_format($input_gl,2); // longitude
    }
    // Curl ini
    public function get_json_data_curl($days = 0) {
        // Curl response from Open Meteo
        ($days > 0) ? $historydays = "&past_days=$days" :  $historydays = "";
        set_time_limit(550);
        $curl = curl_init();
        curl_setopt_array($curl,[CURLOPT_URL => 'https://api.open-meteo.com/v1/forecast?latitude='.$this->lat.'&longitude='.$this->lon.$historydays.'&minutely_15=temperature_2m,windspeed_10m,direct_normal_irradiance,diffuse_radiation,direct_radiation&hourly=temperature_2m,windspeed_10m,direct_normal_irradiance,diffuse_radiation,direct_radiation', CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '', CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'GET']);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    /*
      Sorting the curl request and build an array with 15 Minutes Data for 3 Days and hourly Data for 7 Days
    */
    public function make_sortable_data() {

        $dataarray = json_decode( (string) $this->get_json_data_curl(1), null, 512, JSON_THROW_ON_ERROR );

        $datis15 = $dataarray->minutely_15->time;
        $datis60 = $dataarray->hourly->time;

        foreach ($datis15 as $key => $value) {
            $thedate = date('Y-m-d-H-i-z',strtotime((string) $value));
            [$year, $month, $day, $hour, $minute, $dayofyear] = explode("-",$thedate);
            $sqldate = "$year-$month-$day $hour:$minute";
            $dadni15 = $dataarray->minutely_15->direct_normal_irradiance[$key];
            $dadhi15 = $dataarray->minutely_15->diffuse_radiation[$key];
            $daghi15 = $dataarray->minutely_15->direct_radiation[$key];
            $datmp15 = $dataarray->minutely_15->temperature_2m[$key];
            $dawds15 = $dataarray->minutely_15->windspeed_10m[$key];
            $minarray['minute'][$key] = ["ts" => $sqldate, "year" => $year, "month" => $month, "day" => $day, "doy" => $dayofyear + 1, "hour" => $hour, "dni" => $dadni15, "dhi" => $dadhi15, "ghi" => $daghi15, "tmp" => $datmp15, "wds" => $dawds15];
        }

        foreach ($datis60 as $key => $value) {
            $thedate = date('Y-m-d-H-i-z',strtotime((string) $value));
            [$year, $month, $day, $hour, $minute, $dayofyear] = explode("-",$thedate);
            $sqldate = "$year-$month-$day $hour:$minute";
            $dadni60 = $dataarray->hourly->direct_normal_irradiance[$key];
            $dadhi60 = $dataarray->hourly->diffuse_radiation[$key];
            $daghi60 = $dataarray->hourly->direct_radiation[$key];
            $datmp60 = $dataarray->hourly->temperature_2m[$key];
            $dawds60 = $dataarray->hourly->windspeed_10m[$key];
            $hrarray['hourly'][$key] = ["ts" => $sqldate, "year" => $year, "month" => $month, "day" => $day, "doy" => $dayofyear + 1, "hour" => $hour, "dni" => $dadni60, "dhi" => $dadhi60, "ghi" => $daghi60, "tmp" => $datmp60, "wds" => $dawds60];
        }

        $outarray = array_merge($minarray,$hrarray);
        return $outarray;

    }

}