<?php

namespace App\Service\Forecast;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;


class APINasaGovService {
     /**
     * The constructor
     * @param string $input_gl
     * @param string $input_gb
     * @param string $startdate
     * @param string $enddate
     */
    private function __construct($input_gl,$input_gb,$startdate,$enddate) {
        $this->lat = $input_gb;
        $this->lon = $input_gl;
        $this->start = $startdate;
        $this->ende = $enddate;
    }
    // Curl ini
    public function get_json_data_curl() {
        // Curl response from NASA Gov - ALLSKY_SFC_SW_DWN
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://power.larc.nasa.gov/api/temporal/daily/point?parameters=ALLSKY_SFC_SW_DWN&community=RE&longitude='.$this->lon.'&latitude='.$this->lat.'&start='.$this->start.'&end='.$this->ende.'&format=JSON&user=DAV',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    /* Sorting the curl request and build array
     make_sortable_data(sort) -> switch
     non: return the value from API Nasa Gov
     avg: return the AVG value from doy (day of yaer)
     stabw:
     faktor: return the faktor min max
    */
    public function make_sortable_data($sort = "non") {
        $dataarray = json_decode( $this->get_json_data_curl() );
        $dasa = $dataarray->properties->parameter->ALLSKY_SFC_SW_DWN;

        foreach ($dasa as $datekey => $value) {
            $orderdate = date('Y-m-d-z',strtotime($datekey));
            list($year, $month, $day, $dayofyear) = explode("-",$orderdate);
            $outarray[$datekey] = array("year" => $year,"month" => $month,"day" => $day,"doy" => $dayofyear + 1,"value" => ($value == -999) ? "2.0" : $value);
        }

        switch ($sort){
            case 'non':
                return $outarray;
                break;
            case 'avg':
                return $this->sort_avg_val_doy($outarray);
                break;
            case 'stabw':
                return $this->sort_avg_stabw($outarray);
                break;
            case 'faktor':
                return $this->GHIfaktorMinMax($outarray);
                break;
        }

    }
    // Calculate the Min and Max faktor for Doy (days of year)
    public function GHIfaktorMinMax($outarray) {

        for ($x = 1; $x <= 365; $x++) {

            $gendoy = str_pad($x, 2, "0", STR_PAD_LEFT);
            $searcharray_A = $this->multiSearch($this->sort_avg_val_doy($outarray), array('doy' => $gendoy));
            $searcharray_B = $this->multiSearch($this->sort_avg_stabw($outarray), array('doy' => $gendoy));

            $value_avg_day = $searcharray_A[$x]['value'];
            $value_stabw_day = $searcharray_B[$x]['value'];

            $min_set = round($value_avg_day - $value_stabw_day,2);
            $max_set = round($value_avg_day + $value_stabw_day,2);

            $faktor_min = round(($min_set / $value_avg_day),2);
            $faktor_max = round(($max_set / $value_avg_day),2);

            $outfaktor[$gendoy] = array("avg_day" => $value_avg_day,"stabw_day" => $value_stabw_day,"min_set" => $min_set,"max_set" => $max_set,"faktor_min" => $faktor_min,"faktor_max" => $faktor_max,"doy" => $gendoy);
        }

        return $outfaktor;

    }
    //
    public function sort_avg_stabw($outarray) {

        if (is_array($outarray)) {

            for ($x = 1; $x <= 365; $x++) {

                $gendoy = str_pad($x, 2, "0", STR_PAD_LEFT);
                $searcharray = $this->multiSearch($outarray, array('doy' => $gendoy));

                foreach ($searcharray as $key => $value) {
                    $doyarray[]  = $value['value'];
                    $doy = $value['doy'];
                }

                $devi = $this->calculateDeviation($doyarray);
                $outdevi[$doy] = array("value" => $devi, "doy" => $doy);
                $doyarray = array();

            }

            return $outdevi;

        }

    }
    // Sort and Average irr middel value by doy (day of year)
    // return AVG value and doy (day of year) as array
    public function sort_avg_val_doy($outarray) {
        $avg = 0;
        if (is_array($outarray)) {

            for ($x = 1; $x <= 365; $x++) {

                $gendoy = str_pad($x, 2, "0", STR_PAD_LEFT);
                $searcharray = $this->multiSearch($outarray, array('doy' => $gendoy));
                $cn = sizeof($searcharray);

                foreach ($searcharray as $key => $value) {
                    $avg += $value['value'];
                    $doy = $value['doy'];
                }

                $cnavg = round(($avg / $cn),2);

                $outavg[$doy] = array("value" => $cnavg, "doy" => $doy);
                $avg = 0;
            }

            return $outavg;

        }
    }
    // Standart deviation STABW.S in PHP
    public function calculateDeviation($ar) {

        $num = 0;
        $avg = 0;
        $abw = 0;
        $num = sizeof($ar);
        $avg = array_sum($ar) / $num;

        foreach ($ar as $item) {
            $abw += ($item - $avg) * ($item - $avg);
        }

        $out = sqrt((1 / ($num - 1)) * $abw);
        return  $out;

    }
    // Array Key Search Helper
    public static function multiSearch(array $array, array $pairs) {
        $found = array();
        foreach ($array as $aKey => $aVal) {
            $coincidences = 0;
            foreach ($pairs as $pKey => $pVal) {
                if (array_key_exists($pKey, $aVal) && $aVal[$pKey] == $pVal) {
                    $coincidences++;
                }
            }
            if ($coincidences == count($pairs)) {
                $found[$aKey] = $aVal;
            }
        }
        return $found;
    }
}