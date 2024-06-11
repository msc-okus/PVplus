<?php
/**
 * MS 11/23
 * DEK Service zur Erstellung der Gesamtstrahlung in der Modulebene anhand
 * Wetterdaten aus der API von OpenMeteo.com
 * MS 01/24 Erweitert für Tracker
 */
namespace App\Service\Forecast;

use App\Entity\Anlage;
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
        $this->anlagenrepository = $anlagenRepository;
        $this->anlagesunshadingrepository = $anlageSunShadingRepository;
        $this->forecastCalcService = $forecastCalcService;
        $this->anlagenmodulesdbrepository = $anlageModulesDBRepository;
      }

    public function get_DEK_Data($input_gl,$input_mer,$input_gb,$input_mn,$input_ab,$datfile,$has_suns_model,$anlageId,$doy): bool|array {
        // Predefine the Vars
        /**
         * @var Anlage $anlage
         */
        $anlage = $this->anlagenrepository->findOneBy(['anlId' => $anlageId]);
        $sshrep = $this->anlagesunshadingrepository->findBy(['anlage' => $anlageId]);
        // ToDo Muss noch geändert werden in eine verknüpfung zur tabelle modules to Anlage die zur MudulesDB zeigt
        $modrep = $this->anlagenmodulesdbrepository->findBy(['id' => '1']);
        $valueofdayandhour = [];
        $faktorRVSued = $faktorRVOst = $faktorRVWest = $faktorRVTR = 1;
        $RGES = $RGESBIF_LOWER = $RGESBIF_UPPER = $RGES_UPPER =  $RGES_LOWER = $RGESBIF = $TR_RGES = $TR_RGESBIF = 0;
        $nowDay = strtotime(date('Y-m-d 00:00',time()));
        $next2Day = strtotime("+3 day", $nowDay);
        $cnd = 0;
        // Durchläuft das Array $datfile von der Open Meteo API # ['hourly'] a Stündlich # ['minute'] a 15 Minuten
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
                            $GHI2_C=$DNI_C=$DNI2=$GTI_C=$SWI_C=$DHI_C=$GHI_C=$TMP_C=$FF_C=$SWI = 0;
                            foreach ($valueout as $dam) {
                                $TMP_C += $dam['tmp'] ;
                                $FF_C += $dam['wds'] ;
                                $SWI_C += $dam['swi'] ; # Shortware Irradiation
                                $GTI_C += $dam['gti'] ; # global_tilted_irradiance
                                $GHI_C += $dam['swi'] ; # shortwave_radiation = $value['ghi'] + $value['dhi'];
                                $DHI_C += $dam['dhi'] ; # diffuse_radiation
                                $DNI_C += $dam['dni'] ; # direct_normal_radiation
                                $DNI2 += $dam['dni'] / 15; # direct_normal_radiation
                                $GHI2_C += $dam['ghi'] ; # direct_radiation
                            }

                            $DNI = $DNI_C / 15;
                            $SWI = $SWI_C / 15;
                            $GHI2 = $GHI2_C / 15;
                            $DHI = $DHI_C / 15;
                            $GHI = $GHI_C / 15;
                            $TMP = $TMP_C / 15;
                            $FF = $FF_C / 15;
                            $GTI = $GTI_C / 15;
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
                            $GHI2_C=$DNI_C=$DNI2=$SWI_C=$GTI_C=$DHI_C=$GHI_C=$TMP_C=$FF_C=$SWI = 0;
                            foreach ($valueout as $dah) {
                                $TMP_C += $dah['tmp'];
                                $FF_C += $dah['wds'];
                                $SWI_C += $dah['swi'] ; # Shortware Irradiation
                                $GTI_C += $dah['gti'] ; # global_tilted_irradiance
                                $GHI_C += $dah['swi'] ; # shortwave_radiation = $value['ghi'] + $value['dhi'];
                                $DHI_C += $dah['dhi'] ; # diffuse_radiation
                                $DNI_C += $dah['dni'] ; # direct_normal_radiation
                                $DNI2 += $dah['dni'] / 15; # direct_normal_radiation
                                $GHI2_C += $dah['ghi'] ; # direct_radiation
                            }
                            $DNI = $DNI_C / 15;
                            $SWI = $SWI_C / 15;
                            $GHI2 = $GHI2_C / 15;
                            $DHI = $DHI_C / 15;
                            $GHI = $GHI_C / 15;
                            $TMP = $TMP_C / 15;
                            $FF = $FF_C / 15;
                            $GTI = $GTI_C / 15;
                            $instep = '60min';
                        }
                    }

                    // Berechnung der Senkrechtstrahlung auf der Normal EBENE
                    $GDIR = $SWI;#$GTI;##$GHI - $DHI; #$SWI $GHI2; #$GHI - $DHI;
                    //Start Tracker OW Nachführung Berechnung
                    $TR_AOIarray = $this->forecastCalcService->getDataforAOIbyTracker($input_mn, $input_gb, $input_gl, $input_mer, $d, $h, $anlage);
                    $TR_MA = $TR_AOIarray['MA']; // Das Modul Azimut der Wert wird berechnet auf stundenbasis.
                    $TR_MN = $TR_AOIarray['MN']; // Die berechnete Modulneigung als Tracker per Stunde
                    $TR_SA = $TR_AOIarray['SA']; //
                    $TR_SZ = $TR_AOIarray['SZ']; // Zenitwinkel der Sonne

                    $TR_AOI = acos(cos($TR_SZ) * cos(deg2rad($TR_MN)) + sin($TR_SZ) * sin(deg2rad($TR_MN)) * cos($TR_SA - deg2rad($TR_MA))); // Einfallwinkel Strahlung auf Modul
                    $TR_IAM = 1 - 0.05 * (1 / cos($TR_AOI) - 1); //  Reflexionsverlust der Einstrahlung

                    // Berechnung der prozentualen Anteile der Strahlung
                    ($GDIR > 1) ? $TR_GDIRPRZ = round(($GDIR / $GHI) * 100, 0) : $TR_GDIRPRZ = 0; // in Prozent Tracker
                    $TR_SAGD = round(rad2deg($TR_SA), 0); // SA in Grad
                    $TR_DIFFSAMA = $TR_MA - $TR_SAGD;             // Differenz SA -SA

                    $TR_CSZ = sin($TR_SZ);
                    $TR_DNI = $SWI;#$GTI;##$GDIR / $TR_CSZ; // Berechnung der Senkrechtstrahlung

                    $TR_DIRpoa = $TR_DNI * cos($TR_AOI) * $TR_IAM; // Direktstrahlung in Modulebene
                    $TR_DIFpoa = $DHI * ((1 + cos(deg2rad($TR_MN))) / 2) + $GHI2 * (0.012 * $TR_SZ - 0.04) * ((1 - cos(deg2rad($TR_MN))) / 2); // Diffusstrahlung in Modulebene
                    $TR_REFpoa = $GHI2 * $input_ab * ((1 - cos(deg2rad($TR_MN))) / 2); // Reflektierende Strahlung
                    // Ende Tracker OW Nachführung
                    // Berechung aller Modul Azimut winkel Süd / Ost / West stationäre PV Anlagen
                    $AZW = ["180", "90", "270"];
                    foreach ($AZW as $winkel) {
                        $gendoy = str_pad($d, 2, "0", STR_PAD_LEFT); // Zerro Number display 01 instead of 1 - 9
                        // Hole den Einfallwinkel der Strahlung auf Modulebene, Sonnenazimut SA, der Zenitwinkel der Sonne in RAD, die Sonnenhöhe in RAD
                        $AOIarray = $this->forecastCalcService->getAOI($input_mn, $input_gb, $input_gl, $input_mer, $d, $h, $winkel);
                        $AOI = $AOIarray['AOI'];
                        $SA = $AOIarray['SA'];
                        $SZ = $AOIarray['SZ'];
                        $SH = $AOIarray['SH'];
                        $IAM = $AOIarray['IAM']; //  Reflexionsverlust der Einstrahlung
                        $SAGD = $AOIarray['SAGD']; // SA in Grad
                        $DIFFSAMA = $AOIarray['DIFFSAMA']; // Differenz von SA - SA
                        $SHGD = rad2deg($SH); // Sonnenhöhe in Grad

                        // Berechnung vom prozentualen Anteil der Strahlung

                        ($GDIR > 1) ? $GDIRPRZ = round(($GDIR / $GHI) * 100, 0) : $GDIRPRZ = 0; // in Prozent
                        $CSZ = cos($SZ);

                        $DNI = $SWI / $CSZ;#$GTI ;##$GDIR / $CSZ; // Berechnung der Senkrechtstrahlung

                        $DIRpoa = $DNI * cos($AOI) * $IAM;     // Direktstrahlung auf der Modulebene --
                        $DIFpoa = $DHI * ((1 + cos(deg2rad($input_mn))) / 2) + $GHI2 * (0.012 * $SZ - 0.04) * ((1 - cos(deg2rad($input_mn))) / 2); // Diffusstrahlung in Modulebene
                        $REFpoa = $GHI2 * $input_ab * ((1 - cos(deg2rad($input_mn))) / 2); // Reflektierende Strahlung
                        $BF = 80; // Bifazialitätsfaktor (Bereich 70-85)
                        $RGES33 = round($DIRpoa + $DIFpoa + $REFpoa, 3);

                        if ($DIFpoa > 0) {

                            // Tracker
                            $TR_RGES = round($TR_DIRpoa, 3);##round($TR_DIRpoa + $TR_DIFpoa + $TR_REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                            $TR_BACPOA = round(($TR_DIFpoa * $input_ab / $TR_DIFpoa) * $BF, 3); // Die Rueckseitenstrahlung in W/m2 per Hour
                            $TR_RGESBIF = round($TR_DIRpoa +  $TR_BACPOA, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module

                            switch ($winkel) {
                                case 180:
                                    $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3);// Gesamtstrahlung in der Modulebene Wh/m2 per Hour Süd
                                    $BACPOA = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour
                                    $RGESBIF = round($DIRpoa + $BACPOA, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour für Bifacial Module
                                    break;
                                case 90:
                                    $RGES_UPPER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour OST
                                    $BACPOA_UPPER = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour
                                    $RGESBIF_UPPER = round($DIRpoa  + $BACPOA_UPPER, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour für Bifacial Module
                                    break;
                                case 270:
                                    $RGES_LOWER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour West
                                    $BACPOA_LOWER = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour
                                    $RGESBIF_LOWER = round($DIRpoa + $BACPOA_LOWER, 3); // Gesamtstrahlung in der Modulebene Wh/m2 per Hour für Bifacial Module
                                    break;
                            }

                       }

                    }

                    $RGES = ($RGES < 0 ? 0 : $RGES);
                    $RGES_UPPER = ($RGES_UPPER < 0 ? 0 : $RGES_UPPER);
                    $RGES_LOWER = ($RGES_LOWER < 0 ? 0 : $RGES_LOWER);

                    echo "$d $h $m - $DNI - $RGES - $RGES_UPPER  \n";

                    $valueofdayandhour[$gendoy][$h][$m] =
                        [
                            'DOY' => $gendoy,
                            'HR' => $h,
                            'MIN' => $m,
                            'TIP' => $instep,
                            'TS' => $ts,
                            'TMP' => $TMP,
                            'FF' => $FF,
                            'GDIR' => $GDIR,
                            'RVF' => [
                                'SUED' => $faktorRVSued,
                                'OST' => $faktorRVOst,
                                'WEST' => $faktorRVWest,
                                'TRACKEROW' => $faktorRVTR],
                            "SUED" => [
                                'RGES' => $RGES,
                                'RGESBIF' => $RGESBIF],
                            "OSTWEST" => [
                                'RGES_UPPER' => $RGES_UPPER,
                                'RGES_LOWER' => $RGES_LOWER,
                                'RGESBIF_UPPER' => $RGESBIF_UPPER,
                                'RGESBIF_LOWER' => $RGESBIF_LOWER],
                            "TRACKEROW" => [
                            'RGES' => $TR_RGES,
                            'RGESBIF' => $TR_RGESBIF],
                        ];
                    $SWI = $GTI = $GHI2 = $DNI = $DHI = $GHI= 0;
                    $RGES_LOWER = $RGES_UPPER = $RGESBIF_LOWER = $RGESBIF_UPPER = $RGESBIF = $RGES = $TR_RGES = $TR_RGESBIF = 0;
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
