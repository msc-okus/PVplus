<?php

namespace App\Service\Forecast;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use App\Repository\AnlageSunShadingRepository;

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

    public function __construct(SunShadingModelService $shadingModelService,AnlagenRepository $anlagenRepository,AnlageSunShadingRepository $anlageSunShadingRepository) {
        $this->shadingmodelservice = $shadingModelService;
        $this->anlagenreository = $anlagenRepository;
        $this->anlagesunshadingrepository = $anlageSunShadingRepository;
      }

    public function get_DEK_Data($doy = 'all',$input_gl,$input_gb,$input_mer,$input_mn,$input_ma,$input_ab,$datfile,$has_suns_model,$anlageId) {
// Predefine
        $sshrep = $this->anlagesunshadingrepository->findBy(['anlage' => $anlageId]);

        $valueofdayandhour = [];
        $SGES = 0;
        $faktorRV = 1;

// Erstelle den Deklationswinkel pro Tag
        for ($i = 1; $i <= 365; $i++) {

            $DEK = -23.45 * (COS((2*PI()/365.25)*($i+10)));
            $dekofday[$i] = ['DAY' => $i, 'DEK' => deg2rad($DEK)];

        }

// Erstelle den Stundenwinkel der Sonne anhand der MOZ mittlere Ortszeit pro Stunde
        for ($i = 0; $i <= 23; $i++) {

            $MOZ = (($input_gl - $input_mer) / 15) + $i; // Mittlere Ortszeit
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
                $SH = asin(sin($DEK) * sin(deg2rad($input_gb)) + cos($DEK) * cos(deg2rad($input_gb)) * cos($SW) ); // Sonnenhöhe in RAD
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
                    $gendoy = str_pad($i, 2, "0", STR_PAD_LEFT); // Zerro Number display 01 instead of 1 - 9
                    $SA = deg2rad(180) - $AT; // Sonnenazimut
                    // $AOI = -(cos(cos(cos($SZ) * cos(deg2rad($this->mn)) + sin($SZ) * sin(deg2rad($this->mn)) * cos($SA - deg2rad($this->ma))))); // Einfallwinkel Strahlung auf Modul
                    $AOI = -(cos(cos($SZ) * cos(deg2rad($input_mn)) + sin($SZ) * sin(deg2rad($input_mn)) * cos($SA - deg2rad($winkel)))); // Einfallwinkel Strahlung auf Modul

                    $AOIGD = rad2deg($AOI);

                    $IAM = 1 - 0.05 * (1 / cos($AOI) - 1);  //  Reflexionsverlust der Einstrahlung

                    $GDIR = @$datfile[$i][$h]['gdir']; // Daten aus Metonorm GHI - DHI
                    $DHI = @$datfile[$i][$h]['dh'];
                    $GHI = @$datfile[$i][$h]['gh'];
                    $TMP = @$datfile[$i][$h]['ta'];
                    $FF = @$datfile[$i][$h]['ff'];

              //      $SAGD = round(rad2deg($SA),0);
              //      $DIFFSAMA = $winkel - $SAGD;
              //      if ($DIFFSAMA <= 80 && $DIFFSAMA >= -80 && $GDIR >= 5) {
              //
              //      }

                    $CSZ = sin($SZ);

                    $DNI = $GDIR / $CSZ ; // Berechnung der Senkrechtstrahlung

                    $DIRpoa = $DNI * cos($AOI) * $IAM; // Direktstrahlung in Modulebene
                    $DIRtmp = $DNI * cos($AOI);
                    $DIFpoa = $DHI * ( (1 + cos(deg2rad($input_mn))) / 2) + $GHI *  (0.012 * $SZ - 0.04) * ((1 - cos(deg2rad($input_mn))) / 2); // Diffusstrahlung in Modulebene

                    $REFpoa = $GHI * $input_ab * ((1 - cos(deg2rad($input_mn))) / 2); // Reflektierende Strahlung
                    $BF = 80;

                    if ($DIFpoa > 0) {

                        switch ($winkel) {
                            case 180:
                                $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                if ($has_suns_model) {
                                   if ($RGES >= 200 && $RGES <= 400) { // Wenn Strahlung größer 200 kleiner 400 W/m2
                                       $faktorRV = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren
                                       $DIRpoa = $DIRpoa * $faktorRV;  // Neuer DIRpoa mit Faktor
                                       $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour zzg. Verschattungs Faktor
                                   }
                                }
                                $BACPOA = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $RGESBIF = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                break;
                            case 90:
                                $RGES_UPPER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour OST
                                if ($has_suns_model) {
                                    if ($RGES_UPPER >= 200 && $RGES_UPPER <= 400) { // Wenn Strahlung größer 200 kleiner 400 W/m2
                                        $faktorRV = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren
                                        $DIRpoa = $DIRpoa * $faktorRV; // Neuer DIRpoa mit Faktor
                                        $RGES_UPPER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour OST zzg. Verschattungs Faktor
                                    }
                                }
                                $BACPOA_UPPER = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $RGESBIF_UPPER = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA_UPPER, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                break;
                            case 270:
                                $RGES_LOWER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour West
                                if ($has_suns_model) {
                                    if ($RGES_LOWER >= 200 && $RGES_UPPER <= 400) { // Wenn Strahlung größer 200 kleiner 400 W/m2
                                        $faktorRV = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren
                                        $DIRpoa = $DIRpoa * $faktorRV; // Neuer DIRpoa mit Faktor
                                        $RGES_LOWER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour West zzg. Verschattungs Faktor
                                    }
                                }
                                $BACPOA_LOWER = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $RGESBIF_LOWER = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA_LOWER, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                break;
                        }

                    }

                   if ($RGES > 0 ) {
                    // Prüfe die Sonnenhöhe ist größer -1 Grad, wenn ja dann ist keine Strahlung
                    if ($SHGD > 1) {
                        $SGES += $RGES;
                        $DGES += $RGES;
                       # $valueofdayandhour[$i][$h] = array('DOY' => $i, 'SW' => $SW, 'SWGD' => $SWGD,'DEK' => $DEK ,'DEKGD' => $DEKGD ,'AT' => $AT,'ATGD' => $ATGD, 'SH' => $SH, 'SHGD' => $SHGD , 'SZ' => $SZ,'CSZ' => $CSZ, 'SZGD' => $SZGD, 'SA' => $SA, 'AOI' => $AOI,'AOIGD' => $AOIGD, 'IAM' => $IAM, 'GDIR' => $GDIR,'TMP' => $TMP ,'DNI' => $DNI,'DIRtmp' => $DIRtmp ,'DIRpoa' => $DIRpoa, 'DIFpoa' => $DIFpoa, 'REFpoa' => $REFpoa, 'RGES' => $RGES,'SUMDAY' =>  $DGES,'SUMYEAR' => $SGES);
                        $valueofdayandhour[$gendoy][$h] = ['DOY' => $gendoy, 'HR' => $h, 'TMP' => $TMP, 'FF' => $FF, 'RVF' => $faktorRV, "SUED" => ['RGES' => $RGES, 'RGESBIF' => $RGESBIF], "OSTWEST" => ['RGES_UPPER' => $RGES_UPPER, 'RGES_LOWER' => $RGES_LOWER, 'RGESBIF_UPPER' => $RGESBIF_UPPER, 'RGESBIF_LOWER' => $RGESBIF_LOWER]];
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