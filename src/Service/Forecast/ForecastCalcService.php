<?php

namespace App\Service\Forecast;

use App\Repository\AnlagenRepository;
use App\Repository\AnlageSunShadingRepository;
use App\Service\WeatherServiceNew;

class ForecastCalcService {

    public function __construct(AnlagenRepository $anlagenRepository,WeatherServiceNew $weatherServiceNew)
    {
        $this->anlagenRepository = $anlagenRepository;
        $this->weatherService = $weatherServiceNew;
    }
    // Erstelle den Deklationswinkel pro Tag
    // Übergabe day 1 - 365
    public function getDekofday($day): array
    {
            $DEK = -23.45 * (COS((2*PI()/365.25)*($day+10)));
            $dekofday[$day] = ['DAY' => $day, 'DEK' => deg2rad($DEK)];
            return $dekofday;
    }
    // Erstelle den Stundenwinkel der Sonne anhand der MOZ (mittlere Ortszeit) pro Stunde
    // Übergabe Geo Länge / Longitude, Bezugsmeridan Mitteleuropa, hour 0 - 23
    public function getMozofday($input_gl,$input_mer,$hour): array
    {
        if ($input_gl && $input_mer) {
               $MOZ = (($input_gl - $input_mer) / 15) + $hour; // Mittlere Ortszeit
               $SW = 15 * ($MOZ - 12); // Stundenwinkel der Sonne
               $mozofhour[$hour] = ['HUR' => $hour, 'MOZ' => $MOZ, 'SW' => $SW];
               $out = $mozofhour;
          } else {
            $out = [];
        }
        return $out;
    }
    // Erstelle den Einfallswinkel der Strahlung auf die Modulebene
    // Übergabe Modulneigung Grad, Geo Breite / Latitute, Geo Länge / Longitude, Bezugsmeridan Mitteleuropa, day 1-365, hour 0-23, winkel 90 - 180 - 270,
    public function getAOI($input_mn,$input_gb,$input_gl,$input_mer,$day,$hour,$winkel): array
    {
        if ($input_gl && $input_mer) {
            $dekofday = $this->getDekofday($day);
            $mozofhour = $this->getMozofday($input_gl,$input_mer,$hour);
            $DEK = $dekofday[$day]['DEK'];
            $SW = deg2rad($mozofhour[$hour]['SW']); // Stundenwinkel der Sonne
            $SH = asin(sin($DEK) * sin(deg2rad($input_gb)) + cos($DEK) * cos(deg2rad($input_gb)) * cos($SW) ); // Sonnenhöhe in RAD
            $SHGD = rad2deg($SH); // Sonnenhöhe in Grad
            if ($SHGD < 2) {
                $SH = 0.1; // Sonnenhöhe auf 0.1 setzen wenn SH kleiner 2 Grad
            }
            $SZ = deg2rad(90) - $SH; // Zenitwinkel der Sonne in RAD
            $AT = asin((-cos($DEK) * sin($SW)) / cos($SH) ); // Azimutwinkel in RAD
            $SA = deg2rad(180) - $AT; // Sonnenazimut
            $AOI = acos(cos($SZ) * cos(deg2rad($input_mn)) + sin($SZ) * sin(deg2rad($input_mn)) * cos($SA - deg2rad($winkel))); // Einfallwinkel Strahlung auf Modul
            $out = ['AOI' => $AOI,'SA' => $SA,'SZ' => $SZ,'SH' => $SH];
        } else {
            $out = [];
        }
        return $out;
    }
    // Erstelle den Einfallswinkel der Strahlung auf die Modulebene
    // Übergabe Modulneigung Grad, Geo Breite / Latitute, Geo Länge / Longitude, Bezugsmeridan Mitteleuropa, day 1-365, hour 0-23, winkel 90 - 180 - 270,
   public function getDataforAOI($input_mn,$input_gb,$input_gl,$input_mer,$day,$hour,$winkel):array {
       if ($input_gl && $input_mer) {
               $dekofday = $this->getDekofday($day);
               $mozofhour = $this->getMozofday($input_gl,$input_mer,$hour);
               $DEK = $dekofday[$day]['DEK'];
               $SW = deg2rad($mozofhour[$hour]['SW']); // Stundenwinkel der Sonne
               $SH = asin(sin($DEK) * sin(deg2rad($input_gb)) + cos($DEK) * cos(deg2rad($input_gb)) * cos($SW) ); // Sonnenhöhe in RAD
               $SHGD = rad2deg($SH); // Sonnenhöhe in Grad
               if ($SHGD < 2) {
                   $SH = 0.1; // Sonnenhöhe auf 0.1 setzen wenn SH kleiner 2 Grad
               }
               $SZ = deg2rad(90) - $SH; // Zenitwinkel der Sonne in RAD
               $AT = asin((-cos($DEK) * sin($SW)) / cos($SH) ); // Azimutwinkel in RAD
               $SA = deg2rad(180) - $AT; // Sonnenazimut
               #$AOI = acos(cos($SZ) * cos(deg2rad($input_mn)) + sin($SZ) * sin(deg2rad($input_mn)) * cos($SA - deg2rad($winkel))); // Einfallwinkel Strahlung auf Modul
               $out = ['MA' => $winkel,'MN' => $input_mn,'SA' => $SA,'SZ' => $SZ,'SH' => $SH];
         } else {
           $out = [];
       }
       return $out;
   }

    public function getDataforAOIbyTracker($input_mn,$input_gb,$input_gl,$input_mer,$day,$hour,$anlage):array {
        // Berechnung der Modulneigung bei Tracker, daher ist Eingabe der Modulneigung input_mn nicht benötigt.
        $MA=$MN=$SA=$SZ=$SH=0;
        if ($input_gl && $input_mer) {

            $mozofhour = $this->getMozofday($input_gl,$input_mer,$hour);
            $dekofday = $this->getDekofday($day);
            $year = date('Y');
            $daym = $day - 1;
            $theday = date('Y-m-d',strtotime("1 Jan $year +$daym day")); #von Doy zum Datum
            $sunArray = $this->weatherService->getSunrise($anlage,$theday);
            $sunrise = preg_replace("/^0{*}./","",(float)date('H', strtotime((string) $sunArray['sunrise']))); #Sonnenaufgang
            $sunset = preg_replace("/^0{*}./","",(float)date('H', strtotime((string) $sunArray['sunset']))); #Sonnenuntergang
            $mozofhoursunrise = $this->getMozofday($input_gl,$input_mer, $sunrise);
            $mozofhoursunset = $this->getMozofday($input_gl,$input_mer, $sunset);
            $dayview = ($mozofhoursunset[$sunset]['MOZ'] - $mozofhoursunrise[$sunrise]['MOZ']); #Tageslänge MOZ Su - MOZ Sa
            $CN = -1;
            $XC = (45 / ($dayview / 2));
            $findhr = $hour;

            for ($s = $sunrise ; $s <= $sunset; $s++) {
                if ((int)$s <= 12) {

                    $VC = 45 - ($XC * $CN);

                    if ($s == 12) {
                        $A[$day][(int)$s] = ['HR' => $s,'MA' => '180','MN' => 0];# MN Fest auf 0 da in der Berechnung in den Sommer Monaten MN um die 5 ist. #$VC - (45 / ($dayview / 2))
                     } else {
                        $A[$day][(int)$s] = ['HR' => $s,'MA' => '90','MN' => $VC - (45 / ($dayview / 2))]; #MA = Modulazimut, MN = Modulneigung
                    }
                    $CN++;

                } else {

                    $VC = 45 - ($XC * $CN);
                    $A[$day][(int)$s] = ['HR' => $s,'MA' => '270','MN' => $VC  + (45 / ($dayview  / 2))];
                    $CN --;

                }
            }

            if (array_key_exists($findhr,$A[$day])) {
                $MN = $A[$day][$findhr]['MN'];
                $MA = $A[$day][$findhr]['MA'];
            } else {
               if ($findhr < 12) {
                   $MN =   45;
                   $MA =   90;
               } else {
                   $MN =   45;
                   $MA =  270;
               }
            }

            $DEK = $dekofday[$day]['DEK'];
            $SW = deg2rad($mozofhour[$hour]['SW']); // Stundenwinkel der Sonne
            $SH = asin(sin($DEK) * sin(deg2rad($input_gb)) + cos($DEK) * cos(deg2rad($input_gb)) * cos($SW) ); // Sonnenhöhe in RAD
            $SHGD = rad2deg($SH); // Sonnenhöhe in Grad

            if ($SHGD < 2) {
                $SH = 0.1; // Sonnenhöhe auf 0.1 setzen wenn SH kleiner 2 Grad
            }

            $SZ = deg2rad(90) - $SH; // Zenitwinkel der Sonne in RAD
            $AT = asin((-cos($DEK) * sin($SW)) / cos($SH) ); // Azimutwinkel in RAD
            $SA = deg2rad(180) - $AT; // Sonnenazimut
            #$AOI = acos(cos($SZ) * cos(deg2rad($MN)) + sin($SZ) * sin(deg2rad($MN)) * cos($SA - deg2rad($MN))); // Einfallwinkel Strahlung auf Modul
            $out = ['MA' => $MA,'MN' => $MN,'SA' => $SA,'SZ' => $SZ,'SH' => $SH];

        } else {
            $out = [];
        }
        return $out;
    }

}