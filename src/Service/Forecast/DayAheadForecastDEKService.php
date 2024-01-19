<?php
/**
 * MS 11/23
 * DEK Service zur Erstellung der Gesamtstrahlung in der Modulebene anhand
 * Wetterdaten aus der API OpenMeteo.com
 *
 */
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
        // Predefine Vars
        $sshrep = $this->anlagesunshadingrepository->findBy(['anlage' => $anlageId]);
        // Muss noch geändert werden in eine verknüpfung zur tabelle modules to Anlage die zur MudulesDB geht
        $modrep = $this->anlagenmodulesdbrepository->findBy(['id' => '1']);
        $valueofdayandhour = [];
        $faktorRVSued = $faktorRVOst = $faktorRVWest = 1;
        $RGES = $RGESBIF_LOWER = $RGESBIF_UPPER = $RGES_UPPER =  $RGES_LOWER = $RGESBIF = 0;
        // Durchläuft das Array $datfile['hourly'] a Stündlich von Api der Open Meteo es gibt noch ['minute'] a 15 Minute
        $nowDay = strtotime(date('Y-m-d 00:00',time()));
        $next2Day = strtotime("+3 day", $nowDay);
        $cnd = 0;
        foreach ($datfile as $key => $value) {
            $dataDay = strtotime($key);
            foreach ($value as $keyin => $valuein ) {
                foreach ($valuein as $sqlstamp => $valueout ) {
                    if ($dataDay < $next2Day) {
                        if ($keyin == 'minute') {
                            $stampday = date('Y-m-d-H-i-z', strtotime((string)$sqlstamp));
                            [$year, $month, $day, $hour, $minute, $dayofyear] = explode("-", $stampday);

                            $ts = $sqlstamp;
                            $d = $dayofyear + 1;
                            $h = $hour;
                            $m = $minute;
                            $GHI2=$DNI2=$DHI=$GHI=$FF=$TMP = 0;
                            foreach ($valueout as $dam) {
                                $TMP += $dam['tmp'] / 11;
                                $FF += $dam['wds'] / 11;
                                $GHI += $dam['swi'] / 11; # shortwave_radiation = $value['ghi'] + $value['dhi'];
                                $DHI += $dam['dhi'] / 11; # diffuse_radiation
                                # $DNI = $value['dni']; # direct_normal_radiation
                                $DNI2 += $dam['dni'] / 11; # direct_normal_radiation
                                $GHI2 += $dam['ghi'] / 11; # direct_radiation
                            }
                            $instep = '15min';
                        }

                    } else {

                        if ($keyin == 'hourly') {
                            $stampday = date('Y-m-d-H-i-z', strtotime((string)$sqlstamp));
                            [$year, $month, $day, $hour, $minute, $dayofyear] = explode("-", $stampday);
                            $ts = $sqlstamp;
                            $d = $dayofyear + 1;
                            $h = $hour;
                            $m = $minute;
                            $GHI2=$DNI2=$DHI=$GHI=$FF=$TMP = 0;
                            foreach ($valueout as $dah) {
                                $TMP += $dah['tmp'] / 11;
                                $FF += $dah['wds'] / 11;
                                $GHI += $dah['swi'] / 11; # shortwave_radiation = $value['ghi'] + $value['dhi'];
                                $DHI += $dah['dhi'] / 11; # diffuse_radiation
                                # $DNI = $value['dni']; # direct_normal_radiation
                                $DNI2 += $dah['dni'] / 11; # direct_normal_radiation
                                $GHI2 += $dah['ghi'] / 11; # direct_radiation
                            }
                            $instep = '60min';
                        }
                    }

                 #   echo " $sqlstamp  $TMP $FF $GHI $DHI $DNI2 $GHI2 \n";

                    $AZW = ["180", "90", "270"]; // Berechung aller Modul Azimut winkel Süd / Ost / West
                    foreach ($AZW as $winkel) {
                        $gendoy = str_pad($d, 2, "0", STR_PAD_LEFT); // Zerro Number display 01 instead of 1 - 9
                        // Hole den Einfallwinkel Strahlung auf Modulebene, Sonnenazimut SA, der Zenitwinkel der Sonne in RAD, die Sonnenhöhe in RAD
                        $AOIarray = $this->forecastCalcService->getAOI($input_mn, $input_gb, $input_gl, $input_mer, $d, $h, $winkel);
                        $AOI = $AOIarray['AOI'];
                        $SA = $AOIarray['SA'];
                        $SZ = $AOIarray['SZ'];
                        $SH = $AOIarray['SH'];
                        $SHGD = rad2deg($SH); // Sonnenhöhe in Grad
                        // Berechnung vom prozentualen Anteil der Strahlung

                        // Berechnung der Senkrechtstrahlung auf der Normal EBENE
                        if ($DNI2 > $GHI) {
                            $GDIR = $DNI2 - $DHI; // $GHI - $DHI / /Daten aus Meteo GHI - DHI
                           } else {
                            $GDIR = $GHI - $DHI  ; // $GHI - $DHI / /Daten aus Meteo GHI - DHI
                        }
                            ($GDIR > 1) ? $GDIRPRZ = round(($GDIR / $GHI) * 100, 0) : $GDIRPRZ = 0; // in Prozent
                            $IAM = 1 - 0.05 * (1 / cos($AOI) - 1); //  Reflexionsverlust der Einstrahlung
                            $SAGD = round(rad2deg($SA), 0); // SA in Grad
                            $DIFFSAMA = $winkel - $SAGD;           // Differenz von SA - SA
                            $CSZ = sin($SZ);

                        if ($DNI2 > $GHI) {
                            $DNI = $GDIR / $CSZ;
                           } else {
                            $DNI =  $GDIR / $CSZ;
                        }

                        $DIRpoa = $DNI * cos($AOI) * $IAM;     // Direktstrahlung auf der Modulebene --
                        $DIFpoa = $DHI * ((1 + cos(deg2rad($input_mn))) / 2) + $GHI2 * (0.012 * $SZ - 0.04) * ((1 - cos(deg2rad($input_mn))) / 2); // Diffusstrahlung in Modulebene
                        $REFpoa = $GHI2 * $input_ab * ((1 - cos(deg2rad($input_mn))) / 2); // Reflektierende Strahlung
                        $BF = 80; // Bifazialitätsfaktor (Bereich 70-85)
                        $RGES33 = round($DIRpoa + $DIFpoa + $REFpoa, 3);

                        if ($DIFpoa > 0) {

                            switch ($winkel) {
                                case 180:
                                    $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour Süd
                                    if ($has_suns_model) {
                                        if ($RGES >= 500) { // Wenn Strahlung größer 500 Wh/m2 dann Verschattungsfaktor
                                            $faktorRVSued = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren // Return Array faktor RSH
                                            $DIRpoa = $DIRpoa * $faktorRVSued['FKR'];  // Neuer DIRpoa mit multiplikation des Verschattungs Faktor
                                            $RSHArray = $faktorRVSued['RSH']; // Array der Reihen abschattung
                                            $this->shadingmodelservice->modrow_shading_loss($RSHArray, $DIFFSAMA, $GDIRPRZ, $sshrep, $modrep);
                                            $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour zzg. Verschattungs Faktor
                                        }
                                    }
                                    $BACPOA = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour
                                    $RGESBIF = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour für Bifacial Module
                                    break;
                                case 90:
                                    $RGES_UPPER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour OST
                                    if ($has_suns_model) {
                                        if ($RGES_UPPER >= 500) { // Wenn Strahlung größer 500 W/m2 dann Verschattungsfaktor
                                            $faktorRVOst = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren // Return Array faktor RSH
                                            $DIRpoa = $DIRpoa * $faktorRVOst['FKR']; // Neuer DIRpoa mit multiplikation des Verschattungs Faktor
                                            $RSHArray = $faktorRVOst['RSH']; // Array der Reihen abschattung
                                            $this->shadingmodelservice->modrow_shading_loss($RSHArray, $DIFFSAMA, $GDIRPRZ, $sshrep, $modrep);
                                            $RGES_UPPER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour OST zzg. Verschattungs Faktor
                                        }
                                    }
                                    $BACPOA_UPPER = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour
                                    $RGESBIF_UPPER = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA_UPPER, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour für Bifacial Module
                                    break;
                                case 270:
                                    $RGES_LOWER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour West
                                    if ($has_suns_model) {
                                        if ($RGES_LOWER >= 500) { // Wenn Strahlung größer 500 W/m2 dann Verschattungsfaktor
                                            $faktorRVWest = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren // Return Array faktor RSH
                                            $DIRpoa = $DIRpoa * $faktorRVWest['FKR']; // Neuer DIRpoa mit multiplikation des Verschattungs Faktor
                                            $RSHArray = $faktorRVWest['RSH']; // Array der Reihen abschattung
                                            $this->shadingmodelservice->modrow_shading_loss($RSHArray, $DIFFSAMA, $GDIRPRZ, $sshrep, $modrep);
                                            $RGES_LOWER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour West zzg. Verschattungs Faktor
                                        }
                                    }
                                    $BACPOA_LOWER = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour
                                    $RGESBIF_LOWER = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA_LOWER, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour für Bifacial Module
                                    break;
                            }

                       }

                    }
                    $valueofdayandhour[$gendoy][$h][$m] = ['DOY' => $gendoy, 'HR' => $h, 'MIN' => $m, 'TIP' => $instep, 'TS' => $ts, 'TMP' => $TMP, 'FF' => $FF, 'GDIR' => $GDIR, 'RVF' => ['SUED' => $faktorRVSued, 'OST' => $faktorRVOst, 'WEST' => $faktorRVWest], "SUED" => ['RGES' => $RGES, 'RGESBIF' => $RGESBIF], "OSTWEST" => ['RGES_UPPER' => $RGES_UPPER, 'RGES_LOWER' => $RGES_LOWER, 'RGESBIF_UPPER' => $RGESBIF_UPPER, 'RGESBIF_LOWER' => $RGESBIF_LOWER]];
                    $RGES_LOWER = $RGES_UPPER = $RGESBIF_LOWER = $RGESBIF_UPPER = $RGESBIF = $RGES = 0;
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
