<?php

namespace App\Service\Forecast;
use App\Repository\AnlagenRepository;
use App\Repository\AnlageSunShadingRepository;

class ForecastCalcService {

    public function __construct(AnlagenRepository $anlagenRepository)
    {
        $this->anlagenRepository = $anlagenRepository;
    }
    // Erstelle den Deklationswinkel pro Tag
    // Übergabe day 1 - 365
    public function getDekofday($day):Array {
            $DEK = -23.45 * (COS((2*PI()/365.25)*($day+10)));
            $dekofday[$day] = ['DAY' => $day, 'DEK' => deg2rad($DEK)];
            return $dekofday;
    }
    // Erstelle den Stundenwinkel der Sonne anhand der MOZ (mittlere Ortszeit) pro Stunde
    // Übergabe Geo Länge / Longitude, Bezugsmeridan Mitteleuropa, hour 0 - 23
    public function getMozofday($input_gl,$input_mer,$hour):array {
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
   public function getAOI($input_mn,$input_gb,$input_gl,$input_mer,$day,$hour,$winkel) {
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
               #$AOI = -(cos(cos($SZ) * cos(deg2rad($input_mn)) + sin($SZ) * sin(deg2rad($input_mn)) * cos($SA - deg2rad($winkel)))); // Einfallwinkel Strahlung auf Modul
               $out = ['AOI' => $AOI,'SA' => $SA,'SZ' => $SZ,'SH' => $SH];
         } else {
           $out = [];
       }
       return $out;
   }

}
