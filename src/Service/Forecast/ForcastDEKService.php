<?php
/*
 * MS 10/23
 * DEK Service zur Erstellung der Gesamtstrahlung in der Modulebene anhand
 * Wetterdaten aus Daten von PVSyst
 *
 */
namespace App\Service\Forecast;

use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use App\Repository\AnlageSunShadingRepository;
use App\Repository\AnlageModulesDBRepository;

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

    public function __construct(AnlageModulesDBRepository $anlageModulesDBRepository,ForecastCalcService $forecastCalcService,SunShadingModelService $shadingModelService,AnlagenRepository $anlagenRepository,AnlageSunShadingRepository $anlageSunShadingRepository) {
        $this->shadingmodelservice = $shadingModelService;
        $this->anlagenrepository = $anlagenRepository;
        $this->anlagesunshadingrepository = $anlageSunShadingRepository;
        $this->forecastCalcService = $forecastCalcService;
        $this->anlagenmodulesdbrepository = $anlageModulesDBRepository;
      }

    public function get_DEK_Data($input_gl,$input_mer,$input_gb,$input_mn,$input_ab,$datfile,$has_suns_model,$anlageId,$doy):array
    {
        /**
         * @var Anlage $anlage
         */
        // Predefine
        $anlage = $this->anlagenrepository->findOneBy(['anlId' => $anlageId]);
        $sshrep = $this->anlagesunshadingrepository->findBy(['anlage' => $anlageId]);
        // ToDo - Muss noch geändert werden in eine verknüpfung zur tabelle modules zur Anlage die zur neuen Mudules DB geht !
        $modrep = $this->anlagenmodulesdbrepository->findBy(['id' => '1']);
        $BF = 80; // Bifazialitätsfaktor (Bereich 70-85)

        if ($anlageId and $anlage) {

                $RGESBIF = $RGES = $RGESBIF_LOWER = $RGESBIF_UPPER = $RGES_UPPER =  $RGES_LOWER = 0;
                $faktorRVSued = $faktorRVOst = $faktorRVWest = $faktorRVTR = 1;
                // 365 Days with 0 - 23 Hour
                for ($d = 1; $d <= 365; $d++) {

                    for ($h = 0; $h <= 23; $h++) {

                        $GDIR = @$datfile[$d][$h]['gdir']; // Daten aus Metonorm GHI - DHI
                        $DHI = @$datfile[$d][$h]['dh'];
                        $GHI = @$datfile[$d][$h]['gh'];
                        $TMP = @$datfile[$d][$h]['ta'];
                        $FF = @$datfile[$d][$h]['ff'];

                        //Start Tracker OW Nachführung Berechnung
                        $TR_AOIarray = $this->forecastCalcService->getDataforAOIbyTracker($input_mn, $input_gb, $input_gl, $input_mer, $d, $h, $anlage);
                        $TR_MA = $TR_AOIarray['MA']; // Das Modul Azimut
                        $TR_MN = $TR_AOIarray['MN']; // Die berechnete Modulneigung als Tracker per Stunde
                        $TR_SA = $TR_AOIarray['SA'];
                        $TR_SZ = $TR_AOIarray['SZ'];

                        $TR_AOI = acos(cos($TR_SZ) * cos(deg2rad($TR_MN)) + sin($TR_SZ) * sin(deg2rad($TR_MN)) * cos($TR_SA - deg2rad($TR_MA))); // Einfallwinkel Strahlung auf Modul
                        $TR_IAM = 1 - 0.05 * (1 / cos($TR_AOI) - 1); //  Reflexionsverlust der Einstrahlung

                        // Berechnung der prozentualen Anteile der Strahlung
                        ($GDIR > 1) ? $TR_GDIRPRZ = round(($GDIR / $GHI) * 100, 0) : $TR_GDIRPRZ = 0; // in Prozent
                        $TR_SAGD = round(rad2deg($TR_SA), 0); // SA in Grad
                        $TR_DIFFSAMA = $TR_MA - $TR_SAGD;             // Differenz SA -SA

                        $TR_CSZ = sin($TR_SZ);
                        $TR_DNI = $GDIR / $TR_CSZ; // Berechnung der Senkrechtstrahlung

                        $TR_DIRpoa = $TR_DNI * cos($TR_AOI) * $TR_IAM; // Direktstrahlung in Modulebene
                        $TR_DIFpoa = $DHI * ((1 + cos(deg2rad($TR_MN))) / 2) + $GHI * (0.012 * $TR_SZ - 0.04) * ((1 - cos(deg2rad($TR_MN))) / 2); // Diffusstrahlung in Modulebene
                        $TR_REFpoa = $GHI * $input_ab * ((1 - cos(deg2rad($TR_MN))) / 2); // Reflektierende Strahlung
                        // Ende Tracker OW

                        $AZW = ["180", "90", "270"]; // Modul Azimutwinkel Süd / Ost / West
                        foreach ($AZW as $winkel) {
                            $gendoy = str_pad($d, 2, "0", STR_PAD_LEFT); // Zerro Number display 01 instead of 1 - 9
                            //Start feste ANLAGE
                            //Hole den Einfallwinkel der Strahlung auf Modulebene, Sonnenazimut SA, der Zenitwinkel der Sonne in RAD, die Sonnenhöhe in RAD
                            $AOIarray = $this->forecastCalcService->getDataforAOI($input_mn, $input_gb, $input_gl, $input_mer, $d, $h, $winkel);
                            $MA = $AOIarray['MA']; // Das Modul Azimut
                            $MN = $AOIarray['MN']; // Die Modulneigung von input_mn
                            $SA = $AOIarray['SA'];
                            $SZ = $AOIarray['SZ'];
                            $SH = $AOIarray['SH'];

                            $SHGD = rad2deg($SH); // Sonnenhöhe in Grad
                            $AOI = acos(cos($SZ) * cos(deg2rad($MN)) + sin($SZ) * sin(deg2rad($MN)) * cos($SA - deg2rad($MA))); // Einfallwinkel Strahlung auf Modul
                            $IAM = 1 - 0.05 * (1 / cos($AOI) - 1); //  Reflexionsverlust der Einstrahlung

                            // Berechnung der prozentualen Anteile der Strahlung
                            ($GDIR > 1) ? $GDIRPRZ = round(($GDIR / $GHI) * 100, 0) : $GDIRPRZ = 0; // in Prozent für Verschattungsberechnung
                            $SAGD = round(rad2deg($SA), 0); // SA in Grad
                            $DIFFSAMA = $winkel - $SAGD;            // Differenz SA -SA für Verschattungsberechung

                            $CSZ = sin($SZ);
                            $DNI = $GDIR / $CSZ; // Berechnung der Senkrechtstrahlung

                            $DIRpoa = $DNI * cos($AOI) * $IAM; // Direktstrahlung in Modulebene
                            $DIFpoa = $DHI * ((1 + cos(deg2rad($MN))) / 2) + $GHI * (0.012 * $SZ - 0.04) * ((1 - cos(deg2rad($MN))) / 2); // Diffusstrahlung in Modulebene
                            $REFpoa = $GHI * $input_ab * ((1 - cos(deg2rad($MN))) / 2); // Reflektierende Strahlung
                            //ENDE feste ANLAGE

                            if ($DIFpoa > 0) {
                                // Tracker
                                $TR_RGES = round($TR_DIRpoa + $TR_DIFpoa + $TR_REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour
                                $TR_BACPOA = round(($TR_DIFpoa * $input_ab / $TR_DIFpoa) * $BF, 3); // Die Rueckseitenstrahlung in W/m2 per Hour
                                $TR_RGESBIF = round($TR_DIRpoa + $TR_DIFpoa + $TR_REFpoa + $TR_BACPOA, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                // Todo hat Tracker verschattung ?
                                #$faktorRVTR = $this->shadingmodelservice->genSSM_Data($sshrep, $TR_AOI); // Verschattungsfaktor generieren // Return Array faktor RSH

                                switch ($winkel) {
                                    case 180:
                                        $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour Süd
                                        if ($has_suns_model) {
                                            if ($RGES >= 500) { // Wenn Strahlung größer 500 W/m2
                                                $faktorRVSued = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren // Return Array faktor RSH
                                                $DIRpoa = $DIRpoa * $faktorRVSued['FKR'];  // Neuer DIRpoa mit multiplikation des Verschattungs Faktor
                                                $RSHArray = $faktorRVSued['RSH']; // Array der Reihenabschattung
                                                $this->shadingmodelservice->modrow_shading_loss($RSHArray, $DIFFSAMA, $GDIRPRZ, $sshrep, $modrep);
                                                $RGES = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour zzg. Verschattungs Faktor
                                            }
                                        }
                                        $BACPOA = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3);  // Die Rueckseitenstrahlung in W/m2 per Hour
                                        $RGESBIF = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                        break;
                                    case 90:
                                        $RGES_UPPER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour OST
                                        if ($has_suns_model) {
                                            if ($RGES_UPPER >= 500) { // Wenn Strahlung größer 500 W/m2
                                                $faktorRVOst = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren // Return Array faktor RSH
                                                $DIRpoa = $DIRpoa * $faktorRVOst['FKR']; // Neuer DIRpoa mit multiplikation des Verschattungs Faktor
                                                $RSHArray = $faktorRVOst['RSH']; // Array der Reihenabschattung
                                                $this->shadingmodelservice->modrow_shading_loss($RSHArray, $DIFFSAMA, $GDIRPRZ, $sshrep, $modrep);
                                                $RGES_UPPER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour OST zzg. Verschattungs Faktor
                                            }
                                        }
                                        $BACPOA_UPPER = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // GDie Rueckseitenstrahlung in W/m2 per Hour
                                        $RGESBIF_UPPER = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA_UPPER, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                        break;
                                    case 270:
                                        $RGES_LOWER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour West
                                        if ($has_suns_model) {
                                            if ($RGES_LOWER >= 500) { // Wenn Strahlung größer 500 W/m2
                                                $faktorRVWest = $this->shadingmodelservice->genSSM_Data($sshrep, $AOI); // Verschattungsfaktor generieren // Return Array faktor RSH
                                                $DIRpoa = $DIRpoa * $faktorRVWest['FKR']; // Neuer DIRpoa mit multiplikation des Verschattungs Faktor
                                                $RSHArray = $faktorRVWest['RSH']; // Array der Reihenabschattung
                                                $this->shadingmodelservice->modrow_shading_loss($RSHArray, $DIFFSAMA, $GDIRPRZ, $sshrep, $modrep);
                                                $RGES_LOWER = round($DIRpoa + $DIFpoa + $REFpoa, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour West zzg. Verschattungs Faktor
                                            }
                                        }
                                        $BACPOA_LOWER = round(($DIFpoa * $input_ab / $DIFpoa) * $BF, 3); // Die Rueckseitenstrahlung in W/m2 per Hour
                                        $RGESBIF_LOWER = round($DIRpoa + $DIFpoa + $REFpoa + $BACPOA_LOWER, 3); // Gesamtstrahlung in der Modulebene W/m2 per Hour für Bifacial Module
                                        break;
                                }

                            }

                            if ($RGES > 0) {
                                // Prüft, ob die Sonnenhöhe größer -1 Grad ist, dann keine Strahlung!
                                if ($SHGD > 1) {
                                    $valueofdayandhour[$gendoy][$h] =
                                        [
                                            'DOY' => $gendoy,
                                            'HR' => $h,
                                            'TMP' => $TMP,
                                            'FF' => $FF,
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
                                }

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
        // End funktion get_DEK_Data()
    }

}