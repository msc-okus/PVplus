<?php

namespace App\Service\Forecast;

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
        $this->metoarray = $datfile->current();
    }

    public function get_DEK_Data($doy = 'all') {
        $valueofdayandhour = Array();
// build Deklationswinkel pro Tag
        for ($i = 1; $i <= 365; $i++) {

            $DEK = -23.45 * (cos((2 * pi() / 365.25) * ($i + 10)));
            $dekofday[$i] = array('DAY' => $i, 'DEK' => deg2rad($DEK));

        }

// MOZ mittlere Ortszeit pro Stunde
        for ($i = 0; $i <= 23; $i++) {

            $MOZ = (($this->lon - $this->mer) / 15) + $i; // Mittlere Ortszeit
            $SW = 15 * ($MOZ - 12); // Stundenwinkel der Sonne
            $mozofhour[$i] = array('HUR' => $i, 'MOZ' => $MOZ, 'SW' => $SW);

        }

// 365 Days mit 0 - 23 Hour
        for ($i = 1; $i <= 365; $i++) {

            for ($h = 0; $h <= 23; $h++) {
                $DEK = $dekofday[$i]['DEK'];
                $SW = deg2rad($mozofhour[$h]['SW']); // Stundenwinkel der Sonne
                $SH = asin(sin($DEK) * sin(deg2rad($this->lat)) + cos($DEK) * cos(deg2rad($this->lat)) * cos($SW)); // Sonnenhöhe
                $SHGD = rad2deg($SH); // Sonnenhöhe in Grad
                if ($SHGD < 3) {
                    $SH = 0.1;
                } // Sonnenhöhe auf 0.1 setzen wenn SH kleiner 3 Grad
                $SZ = deg2rad(90) - $SH; // Zenitwinkel der Sonne
                $SZGD = rad2deg($SZ); // Zenitwinkel der Sonne in Grad
                $AT = asin((-(cos($DEK) * sin($SW))) / cos($SH)); // Azimutwinkel
                $SA = deg2rad(180) - $AT; // Sonnenazimut von Nord
                $AOI = cos(acos(cos($SZ) * cos(deg2rad($this->mn)) + sin($SZ) * sin(deg2rad($this->mn)) * cos($SA - deg2rad($this->ma)))); // Einfallwinkel Strahlung auf Modul
                $AOIGD = rad2deg($AOI);
                $IAM = 1 - 0.05 * (1 / cos($AOI) - 1); // Reflexionsverlust der Einstrahlung
                $GDIR = @$this->metoarray[$i][$h]['gdir'];  // Daten aus Metonorm GHI - DHI
                $DHI = @$this->metoarray[$i][$h]['dh'];
                $GHI = @$this->metoarray[$i][$h]['gh'];
                $TMP = @$this->metoarray[$i][$h]['ta'];
                $FF = @$this->metoarray[$i][$h]['ff'];
                $DNI = $GDIR / cos($SZ); // Senkrechtstrahlung
                $DIRpoa = $DNI * cos($AOI) * $IAM; // Direktstrahlung in Modulebene
                $DIFpoa = $DHI * ((1 + cos(deg2rad($this->mn))) / 2) + $GHI * ((0.012 * $SZ - 0.04) * (1 - cos(deg2rad($this->mn))) / 2); // Diffusstrahlung in Modulebene
                $REFpoa = $GHI * $this->ab * ((1 - cos(deg2rad($this->mn))) / 2); // Reflektierende Strahlung
                $BF = 80;
                $RGES = $DIRpoa + $DIFpoa + $REFpoa; // Gesamtstrahlung in der Modulebene W/m2 per Hour
                if ($RGES > 0) {
                    $BACPOA = ($DIFpoa * $this->ab / $DIFpoa) * $BF; // Gesamtstrahlung in der Modulebene W/m2 per Hour
                    $RGESBIF = $DIRpoa + $DIFpoa + $REFpoa + $BACPOA;
                    // $valueofdayandhour[$i][$h] = array('DOY' => $i, 'SW' => $SW,'DEK' => $DEK ,'AT' => $AT, 'SH' => $SH, 'SHGD' => $SHGD , 'SZ' => $SZ, 'SZGD' => $SZGD, 'SA' => $SA, 'AOI' => $AOI,'AOIGD' => $AOIGD, 'IAM' => $IAM, 'GDIR' => $GDIR,'TMP' => $TMP ,'DNI' => $DNI, 'DIRpoa' => $DIRpoa, 'DIFpoa' => $DIFpoa, 'REFpoa' => $REFpoa, 'RGES' => $RGES);
                    $valueofdayandhour[$i][$h] = array('DOY' => $i, 'HR' => $h,'TMP' => $TMP, 'FF' => $FF  ,'RGES' => $RGES, 'RGESBIF' => $RGESBIF);
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