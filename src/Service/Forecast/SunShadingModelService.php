<?php

namespace App\Service\Forecast;

use App\Helper\G4NTrait;
use App\Repository\AnlageSunShadingRepository;
use App\Service\PdoService;

class SunShadingModelService {
    use G4NTrait;

    public function __construct(
        private readonly AnlageSunShadingRepository $anlageSunShadingRepository,
        private readonly PdoService $pdoService,
    )
    {

    }
    /**
     * Berechnung der Verschattungsverluste durch Reihenverschattung
     * MS 10/23 Co. TL - Guidelines: Verschattungsverluste.docx
     */
    public function genSSM_Data($shdata,$aoi): array
    {
        // Verarbeiten der Daten aus dem Objekt
        $out = array();

        if ($shdata) {
            foreach ($shdata as $shdaten) {
                $M = round($shdaten->getModTableHeight() / 1000,2); // Tischhöhe in der Neigungsebene [M]
                $LW = round($shdaten->getModWidth() / 1000,2); // Lichte Weite [LW]
                $NT = $shdaten->getModTilt();                               // Neigungswinkel Tisch [ß]
                $RT = round($shdaten->getModTableDistance() / 1000 ,2); // Reihenteilung [RT]
                $h = round($shdaten->getDistanceA() / 1000,2); // Lot [h]
                $d = round($shdaten->getDistanceB() / 1000,2); // Strecke unter Tisch [d]
                // The Row Tables Shading
                $hasrowshading = $shdaten->getHasRowShading(); // Wenn Daten eingegeben sind und Schalter auf aktive!
                $modalignment = $shdaten->getModAlignment(); // Modul Aurichtung - 0 = Portrait | 1 = Landscape
                $modlongpage = round($shdaten->getModLongPage() / 1000,2); // Masse des Modules Lange Seite
                $modshortpage = round($shdaten->getModShortPage() / 1000,2); //Masse des Modules kurze Seite
                $modrowtables = $shdaten->getModRowTables(); // Anzahl der Module auf dem Tisch
                // The ModulesDB
                ### $modulesdb = $shdaten->getModulesDB();  // Modul Datenbank
                $anlagenID = $shdaten->getAnlageId(); // Anzahl der Module auf dem Tisch
            }
        }
        // Prüfen ob daten numerisch sind!
        if (is_numeric($M) and is_numeric($LW) and is_numeric($NT)) {

            // Reihenteilung [RT] Berechnung, wenn RT nicht vorhanden daher muss LW vorhanden sein.
            if (!$RT and $LW) {
                $h = round(sin(deg2rad($NT)) * $M, 2);   // berechnetes Lot [h]
                $d = round(sqrt((($M * $M) - ($h * $h))), 2); // berechnete die Strecke unter Tisch [d]
                $RT = $LW + $d; // berechnet Reihenteilung [RT]
            } else {
                // [RT] Berechnung, wenn vorhanden
                $RT = $LW + $d; // Reihenteilung [RT]
            }
            // Das AOI aus den DEK Service [aE]
            $aoi = round(rad2deg(cos($aoi)), 0); // [aE]

            $y = 180 - $aoi; // Komplementärwinkel berechnen
            $a = 180 - $y - $NT; // Winkel zwischen der Ebene und Einfallrichtung IRR
            $L = round(sin(deg2rad($a)) * $RT,3); // Verschattungsfreie Strecke
            $S = ($L >= $M ?  0 : round($M - $L,3)); // Verschattete Strecke in Meter

            // Berechnung der Verschattung auf dem Modultisch
            if ($hasrowshading == 1) {
                $TH = ($modalignment == 0 ? $modrowtables * $modlongpage : $modrowtables * $modshortpage); // Tischhöhe [M] bei Portrait od. Landscape
                $TAV_PZ = round(( $S / $TH ) * 100,2); // Verschattung gesamter Tisch in Prozent - TAV
                $RSH_Array = Array();
                // Durchlauf der Tischreihen max. 8
                for ($mx = 1; $mx <= $modrowtables; $mx++) {
                    switch ($mx) {
                        case 1:
                            $MRV = ($modalignment == 0 ? $S / $modlongpage : $S / $modshortpage); // Modulreihenverschattung
                            $MRV_PZ = round(($MRV > 1 ? $MRV = 100 : $MRV * 100),2); // Modulreihenverschattung in Prozent - MRV
                            $RSH_Array[$mx] = ['MRV' => $MRV_PZ,'TAV' => $TAV_PZ,'S' =>  $S];
                            break;
                        case 2:
                            $MRV = ($modalignment == 0 ? ($S - (1 * $modlongpage)) / $modlongpage : ($S - (1 * $modshortpage)) / $modshortpage);
                            if ($modalignment == 0) {
                                ($S > (1 * $modlongpage) ? $MRV_PZ = round(($MRV > 1 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            } else {
                                ($S > (1 * $modshortpage) ? $MRV_PZ = round(($MRV > 1 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            }
                            $RSH_Array[$mx] = ['MRV' => $MRV_PZ,'TAV' => $TAV_PZ,'S' =>  $S];
                            break;
                        case 3:
                            $MRV = ($modalignment == 0 ? ($S - (2 * $modlongpage)) / $modlongpage : ($S - (2 * $modshortpage)) / $modshortpage);
                            if ($modalignment == 0) {
                                ($S > (2 * $modlongpage) ? $MRV_PZ = round(($MRV > 2 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            } else {
                                ($S > (2 * $modshortpage) ? $MRV_PZ = round(($MRV > 2 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            }
                            $RSH_Array[$mx] = ['MRV' => $MRV_PZ,'TAV' => $TAV_PZ,'S' =>  $S];
                            break;
                        case 4:
                            $MRV = ($modalignment == 0 ? ($S - (3 * $modlongpage)) / $modlongpage : ($S - (3 * $modshortpage)) / $modshortpage);
                            if ($modalignment == 0) {
                                ($S > (3 * $modlongpage) ? $MRV_PZ = round(($MRV > 3 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            } else {
                                ($S > (3 * $modshortpage) ? $MRV_PZ = round(($MRV > 3 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            }
                            $RSH_Array[$mx] = ['MRV' => $MRV_PZ,'TAV' => $TAV_PZ,'S' =>  $S];
                            break;
                        case 5:
                            $MRV = ($modalignment == 0 ? ($S - (4 * $modlongpage)) / $modlongpage : ($S - (4 * $modshortpage)) / $modshortpage);
                            if ($modalignment == 0) {
                                ($S > (4 * $modlongpage) ? $MRV_PZ = round(($MRV > 4 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            } else {
                                ($S > (4 * $modshortpage) ? $MRV_PZ = round(($MRV > 4 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            }
                            $RSH_Array[$mx] = ['MRV' => $MRV_PZ,'TAV' => $TAV_PZ,'S' =>  $S];
                            break;
                        case 6:
                            $MRV = ($modalignment == 0 ? ($S - (5 * $modlongpage)) / $modlongpage : ($S - (5 * $modshortpage)) / $modshortpage);
                            if ($modalignment == 0) {
                                ($S > (5 * $modlongpage) ? $MRV_PZ = round(($MRV > 5 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            } else {
                                ($S > (5 * $modshortpage) ? $MRV_PZ = round(($MRV > 5 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            }
                            $RSH_Array[$mx] = ['MRV' => $MRV_PZ,'TAV' => $TAV_PZ,'S' =>  $S];
                            break;
                        case 7:
                            $MRV = ($modalignment == 0 ? ($S - (6 * $modlongpage)) / $modlongpage : ($S - (6 * $modshortpage)) / $modshortpage);
                            if ($modalignment == 0) {
                                ($S > (6 * $modlongpage) ? $MRV_PZ = round(($MRV > 6 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            } else {
                                ($S > (6 * $modshortpage) ? $MRV_PZ = round(($MRV > 6 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            }
                            $RSH_Array[$mx] = ['MRV' => $MRV_PZ,'TAV' => $TAV_PZ,'S' =>  $S];
                            break;
                        case 8:
                            $MRV = ($modalignment == 0 ? ($S - (7 * $modlongpage)) / $modlongpage : ($S - (7 * $modshortpage)) / $modshortpage);
                            if ($hasrowshading == 1) {
                                ($S > (7 * $modlongpage) ? $MRV_PZ = round(($MRV > 7 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            } else {
                                ($S > (7 * $modshortpage) ? $MRV_PZ = round(($MRV > 7 ? $MRV = 100 : $MRV * 100), 2) : $MRV_PZ = 0);
                            }
                            $RSH_Array[$mx] = ['MRV' => $MRV_PZ,'TAV' => $TAV_PZ,'S' =>  $S];
                            break;
                        default:
                            $MRV = 0;
                            $MRV_PZ = 100;
                            $RSH_Array[$mx] = ['MRV' => $MRV_PZ,'TAV' => $TAV_PZ];
                    }
                }

            } else {
                $RSH_Array = [];
            }
            try {
                $SP = ($S / $M) * 100; // Verschattung in Prozent
            } catch (DivisionByZeroError $e) {
                echo "FAIL -> DivisionByZero Error -> SunShading Config Check! \n";
                $SP = 1;
            }

            $faktor = round((1 - ($SP / 100)), 3); // Verschattungsfaktor - FKR
            //
            ### echo "[aE]: $aoi - [y]: $y  - [a]: $a  - [l]:  $L  - [s]: $S - [TAV]: $TAV_PZ - [FKR]: $faktor \n";
            //
            return $out[] = ['FKR' => $faktor,'RSH' => $RSH_Array]; // Setzen des berechneten faktor und des Reihen abschattung als Array - RSK
        } else {
            $faktor = 1;
            $RSH_Array = array();
            return $out[] = ['FKR' => $faktor,'RSH' => $RSH_Array]; // Setze faktor und leere Array - RSK

        }
        // End Funktion genSSM_Data()
    }

    // Funktion zur berechnung der Modulverschattungsverluste bei Halbzelle, Vollzelle
    // Berechnung vom Verlustfaktor Strom
    /* todo:implement gdirprz*/
    public function modrow_shading_loss($RSKArray,$DIFFSAMA,$GDIRPRZ,$shdata) {
        // Vorrausetzung für die Verschattungsberechnung

        if ($shdata) {
            foreach ($shdata as $shdaten) {
                $modalignment = $shdaten->getModAlignment(); // Modul Ausrichtung - 0 = Landscape | 1 = Portrait Full | 2 = Portrait Half |
            }
        }

        if (is_array($RSKArray)) {
            $tablerows = count($RSKArray);
            if ($tablerows != 0) {
                foreach ($RSKArray as $key => $val) {
                    $SVL22 = $SVL12 = $SVL = $SVL00 = $SVL13 = $SVL23 = 0;
                    $mrv = $val['MRV'];
                    $mr = $key;
                    if ($mrv >= 0) {
                        // Verschattung in Prozent aus der Modulreihenverschattung
                        // Dreigliedrige Berechnung bis zum Durchschalten von 3 Bypassdioden - Potrait - Vollzelle
                        if ($modalignment == 1 ) {
                            if ($DIFFSAMA <= 80 && $DIFFSAMA >= -80) {
                                $SHT = $mrv;
                                // Hier Modell Vollzelle mit 3 Bypassdioden
                                if ($SHT >= 0 && $SHT <= 39) {
                                    $SVL23 = round((-0.0007 * pow($SHT, 3) - 0.005 * pow($SHT, 2) - 0.3833 * $SHT + 100) / 100, 4); // 1 Bypass
                                }
                                if ($SHT >= 40 && $SHT <= 69) {
                                    $SVL13 = round((-0.085 * pow($SHT, 2) + 7.15 * $SHT - 90) / 100, 4); // 2 Bypass
                                }
                                if ($SHT >= 70 && $SHT <= 100) {
                                    $SVL00 = round((-0.0325 * pow($SHT, 2) + 4.635 * $SHT - 137.85) / 100, 4); // 3 Bypass
                                }
                                //
                                echo "PV - Reihe: $mr - Schatten: $SHT ---> BP1: $SVL23  BP2: $SVL13 - BP3: $SVL00 \n";
                                //
                            }

                        } elseif($modalignment == 2 ) {

                            if ($DIFFSAMA <= 80 && $DIFFSAMA >= -80 ) {
                                $SHT = $mrv;
                                // Hier Modell Halbzell mit 2 Bypassdioden
                                #=(-3*10^-17*$B82^3-0,0089*$B82^2-0,5536*$B82+100)/100
                                if ($SHT >= 0 && $SHT <= 59) {
                                    $SVL22 = round(((-3 * pow(10,-17)) * pow($SHT, 3) - 0.0089 * pow($SHT, 2) - 0.5536 * $SHT + 100) / 100, 4); // 1 Bypass
                                }
                                if ($SHT >= 60 && $SHT <= 100) {
                                    $SVL12 = round((-0.0071 * pow($SHT, 2) + 0.0429 * $SHT + 67.714) / 100, 4); // 2 Bypass
                                }
                                //
                                echo "PH - Reihe: $mr - Schatten: $SHT ---> BP1: $SVL22  BP2: $SVL12 \n";
                                //
                            }

                        } else {

                            if ($DIFFSAMA <= 80 && $DIFFSAMA >= -80 ) {
                                $SHT = $mrv;
                                // Hier Modell ohne Bypassdioden Landscape
                                if ($SHT >= 0 && $SHT <= 100) {
                                    $SVL = round((-$SHT + 100) / 100, 4); //
                                }
                                //
                                echo "LA - Reihe: $mr - Schatten: $SHT ---> Verlust:  $SVL  \n";
                                //
                            }

                        }
                    }
                }
            }
        }
        // End Funktion modrow_shading_loss()
    }
}