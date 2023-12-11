<?php

namespace App\Service\Forecast;
// MS 10/2023
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
    public function get_json_data_curl($hdays = 0,$fdays = 0) {
        // Curl response from Open Meteo
        ($hdays > 0) ? $historydays = "&past_days=$hdays" :  $historydays = "";
        ($fdays > 0) ? $forecastdays = "&forecast_days=$fdays" :  $forecastdays = "";
        set_time_limit(550);
        $curl = curl_init();
        curl_setopt_array($curl,[CURLOPT_URL => 'https://api.open-meteo.com/v1/forecast?latitude='.$this->lat.'&longitude='.$this->lon.$historydays.$forecastdays.'&minutely_15=temperature_2m,windspeed_10m,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&hourly=temperature_2m,windspeed_10m,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant', CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '', CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'GET']);
        #curl_setopt_array($curl,[CURLOPT_URL => 'https://api.open-meteo.com/v1/forecast?latitude='.$this->lat.'&longitude='.$this->lon.'&minutely_15=temperature_2m,windspeed_10m,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&hourly=temperature_2m,windspeed_10m,direct_normal_irradiance_instant,diffuse_radiation_instant,direct_radiation_instant,shortwave_radiation_instant&timezone=Europe%2FBerlin&start_date=2023-08-01&end_date=2023-08-31', CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '', CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'GET']);

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    /*
      Sorting the curl request and build an array with 15 Minutes Data for 3 Days and hourly Data for 7 Days
    */
    public function make_sortable_data() {

        $dataarray = json_decode((string) $this->get_json_data_curl(4,7), null, 512, JSON_THROW_ON_ERROR );

        $datis15 = $dataarray->minutely_15->time;
        $datis60 = $dataarray->hourly->time;

        // build Array for Minutely Data
        foreach ($datis15 as $key => $value) {
            $thedate = date('Y-m-d-H-i-z',strtotime((string) $dataarray->minutely_15->time[$key]));
            [$year, $month, $day, $hour, $minute, $dayofyear] = explode("-",$thedate);
            $sqldate = "$year-$month-$day $hour:$minute";
            $date =  "$year-$month-$day";
            $daswi15 = $dataarray->minutely_15->shortwave_radiation_instant[$key];
            $dadni15 = $dataarray->minutely_15->direct_normal_irradiance_instant[$key];
            $dadhi15 = $dataarray->minutely_15->diffuse_radiation_instant[$key];
            $daghi15 = $dataarray->minutely_15->direct_radiation_instant[$key];
            $datmp15 = $dataarray->minutely_15->temperature_2m[$key];
            $dawds15 = $dataarray->minutely_15->windspeed_10m[$key];
            $minarray[$date]['minute'][$key] = ["ts" => $sqldate, "year" => $year, "month" => $month, "day" => $day, "doy" => $dayofyear + 1, "hour" => $hour, "minute" => $minute, "dni" => $dadni15, "dhi" => $dadhi15, "ghi" => $daghi15, "swi" => $daswi15, "tmp" => $datmp15, "wds" => $dawds15];
        }
        // build Array for Hourly Data
        foreach ($datis60 as $key => $value) {
            $thedate = date('Y-m-d-H-i-z',strtotime((string) $dataarray->hourly->time[$key]));
            [$year, $month, $day, $hour, $minute, $dayofyear] = explode("-",$thedate);
            $date =  "$year-$month-$day";
            $sqldate = "$year-$month-$day $hour:$minute";
            $daswi60 = $dataarray->hourly->shortwave_radiation_instant[$key];
            $dadni60 = $dataarray->hourly->direct_normal_irradiance_instant[$key];
            $dadhi60 = $dataarray->hourly->diffuse_radiation_instant[$key];
            $daghi60 = $dataarray->hourly->direct_radiation_instant[$key];
            $datmp60 = $dataarray->hourly->temperature_2m[$key];
            $dawds60 = $dataarray->hourly->windspeed_10m[$key];
            $hrarray[$date]['hourly'][$key] = ["ts" => $sqldate, "year" => $year, "month" => $month, "day" => $day, "doy" => $dayofyear + 1, "hour" => $hour, "minute" => $minute, "dni" => $dadni60, "dhi" => $dadhi60, "ghi" => $daghi60, "swi" => $daswi60, "tmp" => $datmp60, "wds" => $dawds60];
        }
        // merge the two Array for output
        $outarray = array_merge_recursive($minarray,$hrarray);
        return $outarray;

    }

}