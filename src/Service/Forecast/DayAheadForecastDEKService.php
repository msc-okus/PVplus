<?php

namespace App\Service\Forecast;

use App\Repository\AnlagenRepository;
use App\Repository\AnlageSunShadingRepository;
use App\Repository\AnlageModulesDBRepository;

class DayAheadForecastDEKService {
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

    public function __construct(AnlageModulesDBRepository $anlageModulesDBRepository,ForecastCalcService $forecastCalcService,SunShadingModelService $shadingModelService,AnlagenRepository $anlagenRepository,AnlageSunShadingRepository $anlageSunShadingRepository) {
        $this->shadingmodelservice = $shadingModelService;
        $this->anlagenreository = $anlagenRepository;
        $this->anlagesunshadingrepository = $anlageSunShadingRepository;
        $this->forecastCalcService = $forecastCalcService;
        $this->anlagenmodulesdbrepository = $anlageModulesDBRepository;
      }

    public function get_DEK_Data($input_gl,$input_mer,$input_gb,$input_mn,$input_ma,$input_ab,$datfile,$has_suns_model,$anlageId,$doy) {
// Predefine
        $sshrep = $this->anlagesunshadingrepository->findBy(['anlage' => $anlageId]);
        // Muss noch geändert werden in eine verknüpfung zur tabelle modules to Anlage die zur MudulesDB geht
        $modrep = $this->anlagenmodulesdbrepository->findBy(['id' => '1']);
        $valueofdayandhour = [];
        $faktorRVSued=$faktorRVOst=$faktorRVWest = 1;
        $RGES = $RGESBIF_LOWER = $RGESBIF_UPPER = $RGES_UPPER =  $RGES_LOWER = 0;
// Durchläuft das Array $datfile['hourly'] a Stündlich von Api der Open Meteo es gibt noch ['minute'] a 15 Minute
        foreach ($datfile['hourly'] as $key => $value) {

            $d = $value['doy'];
            $h = $value['hour'];
            $TMP = $value['tmp'];
            $FF = $value['wds'];
            $GHI = $value['dni'] + $value['ghi'];
            $DHI = $value['dhi'];
           # $GHI = $value['ghi'];

                $AZW = ["180", "90", "270"]; // Berechung aller Modul Azimut winkel Süd / Ost / West
                foreach ($AZW as $winkel) {
                    $gendoy = str_pad($d, 2, "0", STR_PAD_LEFT); // Zerro Number display 01 instead of 1 - 9
                    // Hole den Einfallwinkel Strahlung auf Modulebene, Sonnenazimut SA, der Zenitwinkel der Sonne in RAD, die Sonnenhöhe in RAD
                    $AOIarray = $this->forecastCalcService->getAOI($input_mn,$input_gb,$input_gl,$input_mer,$d,$h,$winkel);
                    $AOI = $AOIarray['AOI'];
                    $SA = $AOIarray['SA'];
                    $SZ = $AOIarray['SZ'];
                    $SH = $AOIarray['SH'];
                    $SHGD = rad2deg($SH); // Sonnenhöhe in Grad
                    // Berechnung vom prozentualen Anteil der Strahlung
                    $GDIR = $GHI - $DHI; // $GHI - $DHI Daten aus Meteo GHI - DHI
                    ($GDIR > 1) ? $GDIRPRZ = round(($GDIR / $GHI) * 100,0) : $GDIRPRZ = 0; // in Prozent
                    $IAM = 1 - 0.05 * (1 / cos($AOI) - 1); //  Reflexionsverlust der Einstrahlung

                    $SAGD = round(rad2deg($SA),0); // SA in Grad
                    $DIFFSAMA = $winkel - $SAGD;           // Differenz von SA - SA

                    $CSZ = sin($SZ);
                    $DNI = $GDIR / $CSZ ; // Berechnung der Senkrechtstrahlung

                    $DIRpoa = $DNI * cos($AOI) * $IAM;     // Direktstrahlung in Modulebene
                    $DIFpoa = $DHI * ( (1 + cos(deg2rad($input_mn))) / 2) + $GHI *  (0.012 * $SZ - 0.04) * ((1 - cos(deg2rad($input_mn))) / 2); // Diffusstrahlung in Modulebene
                    $REFpoa = $GHI * $input_ab * ((1 - cos(deg2rad($input_mn))) / 2); // Reflektierende Strahlung
                    $BF = 80; // Bifazialitätsfaktor (Bereich 70-85)

                    if ($DIFpoa > 0) {

                        switch ($winkel) {
                            case 180:
                                $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour Süd
                                if ($has_suns_model) {
                                   if ($RGES >= 500) { // Wenn Strahlung größer 500 W/m2
                                       $faktorRVSued = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren // Return Array faktor RSH
                                       $DIRpoa = $DIRpoa * $faktorRVSued['FKR'];  // Neuer DIRpoa mit multiplikation des Verschattungs Faktor
                                       $RSHArray = $faktorRVSued['RSH']; // Array der Reihen abschattung
                                       $this->shadingmodelservice->modrow_shading_loss($RSHArray,$DIFFSAMA,$GDIRPRZ,$sshrep,$modrep);
                                       $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour zzg. Verschattungs Faktor
                                   }
                                }
                                $BACPOA = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $RGESBIF = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                break;
                            case 90:
                                $RGES_UPPER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour OST
                                if ($has_suns_model) {
                                    if ($RGES_UPPER >= 500) { // Wenn Strahlung größer 500 W/m2
                                        $faktorRVOst = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren // Return Array faktor RSH
                                        $DIRpoa = $DIRpoa * $faktorRVOst['FKR']; // Neuer DIRpoa mit multiplikation des Verschattungs Faktor
                                        $RSHArray = $faktorRVOst['RSH']; // Array der Reihen abschattung
                                        $this->shadingmodelservice->modrow_shading_loss($RSHArray,$DIFFSAMA,$GDIRPRZ,$sshrep,$modrep);
                                        $RGES_UPPER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour OST zzg. Verschattungs Faktor
                                    }
                                }
                                $BACPOA_UPPER = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $RGESBIF_UPPER = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA_UPPER, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                break;
                            case 270:
                                $RGES_LOWER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour West
                                if ($has_suns_model) {
                                    if ($RGES_LOWER >= 500) { // Wenn Strahlung größer 500 W/m2
                                        $faktorRVWest = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren // Return Array faktor RSH
                                        $DIRpoa = $DIRpoa * $faktorRVWest['FKR']; // Neuer DIRpoa mit multiplikation des Verschattungs Faktor
                                        $RSHArray = $faktorRVWest['RSH']; // Array der Reihen abschattung
                                        $this->shadingmodelservice->modrow_shading_loss($RSHArray,$DIFFSAMA,$GDIRPRZ,$sshrep,$modrep);
                                        $RGES_LOWER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour West zzg. Verschattungs Faktor
                                    }
                                }
                                $BACPOA_LOWER = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $RGESBIF_LOWER = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA_LOWER, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                break;
                        }

                    }

                   if ($RGES > 0) {
                          // Prüfe, ob die Sonnenhöhe größer -1 Grad ist, dann keine Strahlung!
                    if ($SHGD > 1) {
                           $valueofdayandhour[$gendoy][$h] = ['DOY' => $gendoy, 'HR' => $h, 'TMP' => $TMP, 'FF' => $FF, 'RVF' =>['SUED' => $faktorRVSued,'OST' => $faktorRVOst,'WEST' => $faktorRVWest], "SUED" => ['RGES' => $RGES, 'RGESBIF' => $RGESBIF], "OSTWEST" => ['RGES_UPPER' => $RGES_UPPER, 'RGES_LOWER' => $RGES_LOWER, 'RGESBIF_UPPER' => $RGESBIF_UPPER, 'RGESBIF_LOWER' => $RGESBIF_LOWER]];
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
    // End funktion get_DEK_Data()
    }

}