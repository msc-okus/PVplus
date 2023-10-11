<?php

namespace App\Service\Forecast;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlageSunShadingRepository;
use App\Service\PdoService;
use PDO;

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
    public function genSSM_Data($shdata,$aoi) {
        // Verarbeiten der Daten aus dem Objekt
        if ($shdata) {
            foreach ($shdata as $shdaten) {
                $M = round($shdaten->getModTableHeight() / 1000,2); // Tischhöhe in der Neigungsebene [M]
                $LW = round($shdaten->getModWidth() / 1000,2); // Lichte Weite [LW]
                $ß = $shdaten->getModTilt();   // Neigungswinkel Tisch [ß]
                $RT = round($shdaten->getModTableDistance() / 1000 ,2); // Reihenteilung [RT]
                $h = round($shdaten->getDistanceA() / 1000,2); // Lot [h]
                $d = round($shdaten->getDistanceB() / 1000,2); // Strecke unter Tisch [d]
            }
        }
        // Prüfen ob daten numerisch sind!
        if (is_numeric($M) and is_numeric($LW) and is_numeric($ß)) {

            // Reihenteilung [RT] Berechnung, wenn RT nicht vorhanden daher muss LW vorhanden sein.
            if (!$RT and $LW) {
                $h = round(sin(deg2rad($ß)) * $M, 2);    // berechnetes Lot [h]
                $d = round(sqrt((($M * $M) - ($h * $h))), 2); // berechnete Strecke unter Tisch [d]
                $RT = $LW + $d; // berechnet Reihenteilung [RT]
            } else {
                // [RT] Berechnung, wenn vorhanden
                $RT = $LW + $d; // Reihenteilung [RT]
            }
            // Das AOI aus den DEK Service
            $aoi = round(rad2deg(cos($aoi)), 0);

            $y = 180 - $aoi; // Komplementärwinkel
            $a = 180 - $y - $ß; // Winkel zwischen der Ebene und Einfallrichtung IRR
            $L = sin(deg2rad($a)) * $RT; // Verschattungsfreie Strecke

            if ($L >= $M) {
                $S = 0;
            } else {
                $S = $M - $L; // Verschattete Strecke
            }

            $SP = ($S / $M) * 100; // Verschattung in Prozent
            $faktor = round((1 - ($SP / 100)), 3); // Verschattungsfaktor

            //  echo " $aoi - - - $L - $M - $S - $SP - $faktor \n";
            return $faktor; // Übergabe des berechneten faktor
          } else {
            $faktor = 1;
            return $faktor; // Übergabe des gesetzten faktor
        }
    }

}