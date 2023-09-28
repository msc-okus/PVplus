<?php

namespace App\Service\TicketsGeneration\TicketsGeneration\TicketsGeneration\Forecast;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;

class ForcastDEKService {
    /**
     * The constructor
     * @param string $input_gl
     * @param string $input_gb
     * @param string $input_mer
     * @param string $input_mn
     * @param string $input_ma
     * @param string $input_ab
     * @param array $datfile
     */
    function __construct($input_gl,$input_gb,$input_mer,$input_mn,$input_ma,$input_ab,$datfile) {
        $this->lat = $input_gb;
        $this->lon = $input_gl;
        $this->mn = $input_mn;
        $this->ma = $input_ma;
        $this->ab = $input_ab;
        $this->mer = $input_mer;
        $this->metoarray = $datfile;# $this->metoarray = $datfile->current();
    }

    public function get_DEK_Data($doy = 'all') {
        $valueofdayandhour = [];
        $SGES = 0;

// Erstelle den Deklationswinkel pro Tag
        for ($i = 1; $i <= 365; $i++) {

            $DEK = -23.45 * (COS((2*PI()/365.25)*($i+10)));
            $dekofday[$i] = ['DAY' => $i, 'DEK' => deg2rad($DEK)];

        }

// Erstelle den Stundenwinkel der Sonne anhand der MOZ mittlere Ortszeit pro Stunde
        for ($i = 0; $i <= 23; $i++) {

            $MOZ = (($this->lon - $this->mer) / 15) + $i; // Mittlere Ortszeit
            $SW = 15 * ($MOZ - 12); // Stundenwinkel der Sonne
            $mozofhour[$i] = ['HUR' => $i, 'MOZ' => $MOZ, 'SW' => $SW];

        }

// 365 Days with 0 - 23 Hour
        for ($i = 1; $i <= 365; $i++) {
            $RGES = $RGESBIF_LOWER = $RGESBIF_UPPER = $RGES_UPPER =  $RGES_LOWER = $DGES = 0;
            for ($h = 0; $h <= 23; $h++) {
                $DEK = $dekofday[$i]['DEK'];
                $DEKGD = rad2deg($DEK);
                $SW = deg2rad($mozofhour[$h]['SW']); // Stundenwinkel der Sonne
                $SWGD = rad2deg($SW);
                $SH = asin(sin($DEK) * sin(deg2rad($this->lat)) + cos($DEK) * cos(deg2rad($this->lat)) * cos($SW) ); // Sonnenhöhe in RAD
                $SHGD = rad2deg($SH); // Sonnenhöhe in Grad
                if ($SHGD < 3) {
                    $SH = 0.1; // Sonnenhöhe auf 0.1 setzen wenn SH kleiner 3 Grad
                }
                $SZ = deg2rad(90) - $SH; // Zenitwinkel der Sonne in RAD
                $SZGD = rad2deg($SZ); // Zenitwinkel der Sonne in Grad
                $AT = asin((-cos($DEK) * sin($SW)) / cos($SH) ); // Azimutwinkel in RAD
                $ATGD = rad2deg($AT); // Azimutwinkel in GRAD

                $AZW = ["180", "90", "270"]; // Modul Azimutwinkel Süd / Ost / West

                foreach ($AZW as $winkel) {

                    $SA = deg2rad(180) - $AT; // Sonnenazimut
                    // $AOI = -(cos(cos(cos($SZ) * cos(deg2rad($this->mn)) + sin($SZ) * sin(deg2rad($this->mn)) * cos($SA - deg2rad($this->ma))))); // Einfallwinkel Strahlung auf Modul
                    $AOI = -(cos(cos($SZ) * cos(deg2rad($this->mn)) + sin($SZ) * sin(deg2rad($this->mn)) * cos($SA - deg2rad($winkel)))); // Einfallwinkel Strahlung auf Modul
                    $AOIGD = rad2deg($AOI);

                    $IAM = 1 - 0.05 * (1 / cos($AOI) - 1);    //  Reflexionsverlust der Einstrahlung

                    $GDIR = @$this->metoarray[$i][$h]['gdir']; // Daten aus Metonorm GHI - DHI
                    $DHI = @$this->metoarray[$i][$h]['dh'];
                    $GHI = @$this->metoarray[$i][$h]['gh'];
                    $TMP = @$this->metoarray[$i][$h]['ta'];
                    $FF = @$this->metoarray[$i][$h]['ff'];

                    $CSZ = sin($SZ);

                    $DNI = $GDIR / $CSZ ; // Berechnung der Senkrechtstrahlung

                    $DIRpoa = $DNI * cos($AOI) * $IAM; // Direktstrahlung in Modulebene
                    $DIRtmp = $DNI * cos($AOI);
                    $DIFpoa = $DHI * ( (1 + cos(deg2rad($this->mn))) / 2) + $GHI *  (0.012 * $SZ - 0.04) * ((1 - cos(deg2rad($this->mn))) / 2); // Diffusstrahlung in Modulebene

                    $REFpoa = $GHI * $this->ab * ((1 - cos(deg2rad($this->mn))) / 2); // Reflektierende Strahlung
                    $BF = 80;
                    $gendoy = str_pad($i, 2, "0", STR_PAD_LEFT); // Zerro Number display 01 instead of 1 - 9

                    //   $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                    //   $BACPOA = ($DIFpoa * $this->ab / $DIFpoa) * $BF; // Gesamtstrahlung in der Modulebene W/m2 per Hour
                    //   $REFpoa + $BACPOA;// Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module

                    if ($DIFpoa > 0) {

                        switch ($winkel) {
                            case 180:
                                $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $BACPOA = round(($DIFpoa * $this->ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $RGESBIF = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                break;
                            case 90:
                                $RGES_UPPER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour OST
                                $BACPOA_UPPER = round(($DIFpoa * $this->ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $RGESBIF_UPPER = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA_UPPER, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                break;
                            case 270:
                                $RGES_LOWER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour West
                                $BACPOA_LOWER = round(($DIFpoa * $this->ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $RGESBIF_LOWER = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA_LOWER, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                break;
                        }

                    }

                   if ($RGES > 0 ) {
                    // Prüfen ob die Sonnenhöhe größer -1 Grad, wenn ja ist daher keine Strahlung
                    if ($SHGD > 1) {
                        $SGES += $RGES;
                        $DGES += $RGES;
                       # $valueofdayandhour[$i][$h] = array('DOY' => $i, 'SW' => $SW, 'SWGD' => $SWGD,'DEK' => $DEK ,'DEKGD' => $DEKGD ,'AT' => $AT,'ATGD' => $ATGD, 'SH' => $SH, 'SHGD' => $SHGD , 'SZ' => $SZ,'CSZ' => $CSZ, 'SZGD' => $SZGD, 'SA' => $SA, 'AOI' => $AOI,'AOIGD' => $AOIGD, 'IAM' => $IAM, 'GDIR' => $GDIR,'TMP' => $TMP ,'DNI' => $DNI,'DIRtmp' => $DIRtmp ,'DIRpoa' => $DIRpoa, 'DIFpoa' => $DIFpoa, 'REFpoa' => $REFpoa, 'RGES' => $RGES,'SUMDAY' =>  $DGES,'SUMYEAR' => $SGES);
                        $valueofdayandhour[$gendoy][$h] = ['DOY' => $gendoy, 'HR' => $h, 'TMP' => $TMP, 'FF' => $FF, "SUED" => ['RGES' => $RGES, 'RGESBIF' => $RGESBIF], "OSTWEST" => ['RGES_UPPER' => $RGES_UPPER, 'RGES_LOWER' => $RGES_LOWER, 'RGESBIF_UPPER' => $RGESBIF_UPPER, 'RGESBIF_LOWER' => $RGESBIF_LOWER]];
                    }

                  }

               }

            }

        }

        if (count($valueofdayandhour) > 0) {
            if ($doy == 'all') {
                return $valueofdayandhour;
            } else {
                return $valueofdayandhour[$doy];
            }
        } else {
            return false;
        }
    }

}