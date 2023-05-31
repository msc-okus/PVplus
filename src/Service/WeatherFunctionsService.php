<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\TicketDate;
use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Repository\ForcastRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\GroupModulesRepository;
use App\Repository\GroupMonthsRepository;
use App\Repository\GroupsRepository;
use App\Repository\PVSystDatenRepository;
use App\Repository\ReplaceValuesTicketRepository;
use App\Repository\TicketDateRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\NonUniqueResultException;
use PDO;
use DateTime;

class WeatherFunctionsService
{
    use G4NTrait;

    public function __construct(
        private PVSystDatenRepository   $pvSystRepo,
        private GroupMonthsRepository   $groupMonthsRepo,
        private GroupModulesRepository  $groupModulesRepo,
        private GroupsRepository        $groupsRepo,
        private GridMeterDayRepository  $gridMeterDayRepo,
        private ForcastRepository       $forecastRepo,
        private TicketRepository        $ticketRepo,
        private TicketDateRepository    $ticketDateRepo,
        private ReplaceValuesTicketRepository $replaceValuesTicketRepo)
    {
    }

    /**
     * Function to retrieve WeatherData for the given Time (from - to)
     * $from and $to are in string format.
     *
     * $weather['airTempAvg']
     * $weather['panelTempAvg']
     * $weather['windSpeedAvg']
     * $weather['horizontalIrr']
     * $weather['horizontalIrrAvg']
     * $weather['lowerIrr']
     * $weather['temp_cell_corr']
     * $weather['temp_cell_multi_irr']
     *
     * @param WeatherStation $weatherStation
     * @param $from
     * @param $to
     * @param bool $ppc
     * @param Anlage|null $anlage
     * @return array|null
     */
    public function getWeather(WeatherStation $weatherStation, $from, $to, bool $ppc = false, ?Anlage $anlage = null): ?array
    {
        $conn = self::getPdoConnection();
        $weather = [];
        $dbTable = $weatherStation->getDbNameWeather();
        $sql = "SELECT COUNT(db_id) AS anzahl FROM $dbTable WHERE stamp BETWEEN '$from' and '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $weather['anzahl'] = $row['anzahl'];
        }
        unset($res);

        if ($ppc){
            $sqlPPCpart1 = " RIGHT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp ";
            $sqlPPCpart2 = " AND (ppc.p_set_gridop_rel = 100 OR ppc.p_set_gridop_rel is null) 
                        AND (ppc.p_set_rpc_rel = 100 OR ppc.p_set_rpc_rel is null) ";
        } else {
            $sqlPPCpart1 = $sqlPPCpart2 = "";
        }

        if ($weather['anzahl'] > 0) {
            $sql = "SELECT 
                    SUM(g_lower) as irr_lower, 
                    SUM(g_upper) as irr_upper, 
                    SUM(g_horizontal) as irr_horizontal, 
                    AVG(temp_ambient) AS ambient_temp, 
                    AVG(temp_pannel) AS panel_temp, 
                    AVG(wind_speed) as wind_speed ,
                    SUM(temp_cell_corr) as temp_cell_corr,
                    SUM(temp_cell_multi_irr) as temp_cell_multi_irr
                FROM $dbTable s
                    $sqlPPCpart1
                WHERE s.stamp BETWEEN '$from' AND '$to'
                    $sqlPPCpart2
            ";

            $res = $conn->query($sql);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $weather['airTempAvg'] = $row['ambient_temp'];
                $weather['panelTempAvg'] = $row['panel_temp'];
                $weather['windSpeedAvg'] = $row['wind_speed'];
                $weather['horizontalIrr'] = $row['irr_horizontal'];
                $weather['horizontalIrrAvg'] = $row['irr_horizontal'] / $weather['anzahl'];
                if ($weatherStation->getChangeSensor() == 'Yes') {
                    $weather['upperIrr'] = $row['irr_lower'];
                    $weather['lowerIrr'] = $row['irr_upper'];
                } else {
                    $weather['upperIrr'] = $row['irr_upper'];
                    $weather['lowerIrr'] = $row['irr_lower'];
                }
                $weather['temp_cell_corr'] = $row['temp_cell_corr'];
                $weather['temp_cell_multi_irr'] = $row['temp_cell_multi_irr'];
            }
            unset($res);
        } else {
            $weather = null;
        }
        $conn = null;

        return $weather;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function correctWeatherByTicket(Anlage $anlage, array $weather, DateTime $startDate, DateTime $endDate): ?array
    {
        // Suche alle Tickets (Ticketdates) die in den Zeitraum fallen
        // Es werden Nur Tickets mit Sensor Bezug gesucht (Performance Tickets mit ID =
        #query
        $ticketArray = $this->ticketDateRepo->performanceTickets($anlage, $startDate, $endDate);
        dump($ticketArray);

        // Dursuche alle Tickets in Schleife
        // berechne Wert aus Original Daten und Subtrahiere vom Wert
        // berechne ersatz Wert und Addiere zum entsprechenden Wert
        #loop über query result
        /** @var TicketDate $ticket */
        foreach ($ticketArray as $ticket){
            $tempWeatherArray = $this->getWeather($anlage->getWeatherStation(), $ticket->getBegin()->format('Y-m-d H:i'), $ticket->getEnd()->format('Y-m-d H:i'));
            dump($tempWeatherArray);
            $replaceArray = $this->replaceValuesTicketRepo->getSum($anlage, $ticket->getBegin(), $ticket->getEnd());
            dump($replaceArray);
            dump($weather);
            if ($replaceArray['irrHorizotal'] && $replaceArray['irrHorizotal'] > 0) {
               $weather['horizontalIrr'] = $weather['horizontalIrr'] - $tempWeatherArray['horizontalIrr'] + $replaceArray['irrHorizotal'];
            }
            if (!$replaceArray['irrEast'] && !$replaceArray['irrWest']) {
                if ($replaceArray['irrModul'] && $replaceArray['irrModul'] > 0) {
                    $weather['upperIrr'] = $weather['upperIrr'] - $tempWeatherArray['upperIrr'] + $replaceArray['irrModul'];
                }
            } else {
                if ($replaceArray['irrEast'] && $replaceArray['irrEast'] > 0) {
                    $weather['upperIrr'] = $weather['upperIrr'] - $tempWeatherArray['upperIrr'] + $replaceArray['irrEast'];
                }
                if ($replaceArray['irrWest'] && $replaceArray['irrWest'] > 0) {
                    $weather['lowerIrr'] = $weather['lowerIrr'] - $tempWeatherArray['lowerIrr'] + $replaceArray['irrWest'];
                }
            }
            dump($weather);
        }

        return $weather;
    }

    /**
     * Function to retrieve weighted irradiation
     * definition is optimized for ticket generation, have a look into ducumentation
     *
     * @param Anlage $anlage
     * @param DateTime $stamp
     * @return float
     */
    public function getIrrByStampForTicket(Anlage $anlage, DateTime $stamp): float
    {
        $conn = self::getPdoConnection();
        $irr = 0;

        $sqlw = 'SELECT g_lower, g_upper FROM ' . $anlage->getDbNameWeather() . " WHERE stamp = '" . $stamp->format('Y-m-d H:i') . "' ";
        $respirr = $conn->query($sqlw);

        if ($respirr->rowCount() > 0) {
            $pdataw = $respirr->fetch(PDO::FETCH_ASSOC);
            $irrUpper = (float)$pdataw['g_upper'];
            $irrLower = (float)$pdataw['g_lower'];
            if ($irrUpper < 0) $irrUpper = 0;
            if ($irrLower < 0) $irrLower = 0;

            // Sensoren sind vertauscht, Werte tauschen
            if ($anlage->getWeatherStation()->getChangeSensor()) {
                $irrHelp = $irrLower;
                $irrLower = $irrUpper;
                $irrUpper = $irrHelp;
            }
            if ($anlage->getIsOstWestAnlage() && $anlage->getPowerEast() > 0 && $anlage->getPowerWest() > 0) {
                $gwoben = $anlage->getPowerEast() / ($anlage->getPowerWest() + $anlage->getPowerEast());
                $gwunten = $anlage->getPowerWest() / ($anlage->getPowerWest() + $anlage->getPowerEast());

                $irr = $irrUpper * $gwoben + $irrLower * $gwunten;
            } else {
                if ($anlage->getWeatherStation()->getHasUpper() && !$anlage->getWeatherStation()->getHasLower()) {
                    $irr = $irrUpper;
                } elseif (!$anlage->getWeatherStation()->getHasUpper() && $anlage->getWeatherStation()->getHasLower()) {
                    // Station hat nur unteren Sensor => Die Strahlung OHNE Gewichtung zurückgeben, Verluste werden dann über die Verschattung berechnet
                    $irr = $irrLower;
                }
            }
        }
        $conn = null;

        return $irr;
    }

    /**
     * Umrechnung Globalstrahlung in Modulstrahlung
     * Methode ist NICHT geprüft – Verwendung ist nicht angeraten
     *
     * @param Anlage $anlage
     * @param DateTime $stamp (Zeitpunkt für den die Umrechnung erfolgen soll)
     * @param float|null $ghi (Globalstrahlung zu oben genantem Zeitpunkt)
     * @param float $bezugsmeridian
     * @param float $azimuthModul
     * @param float $neigungModul
     * @return float|null (Berechnete Modulstrahlung)
     */
    public function Hglobal2Hmodul(Anlage $anlage, DateTime $stamp, ?float $ghi = 0.0, float $bezugsmeridian = 15, float $azimuthModul = 180, float $neigungModul = 20): ?float
    {
        if ($ghi === null) {
            return null;
        }
        // $bezugsmeridian = 15;   muss auch aus Anlage kommen, Feld existiert aber noch nicht (kann man das aus breite / Länge berechnen?)
        // $azimuthModul = 180;    muss auch aus Anlage kommen Feld existiert aber noch nicht
        // $neigungModul = 20;     muss auch aus Anlage kommen Feld existiert aber noch nicht
        $breite = $anlage->getAnlGeoLat();
        $laenge = $anlage->getAnlGeoLon();

        $limitAOI       = deg2rad(78);

        $tag = $stamp->format('z');
        $tag++; // Tag um eins erhöhen, da Formel annimmt das der erste Tag im Jahr = 1 ist und nicht 0 wie format('z') zurück gibt
        $stunde = (integer)$stamp->format('G');

        #dump("Tag: $tag | Stunde: $stunde");
        $moz            = (($laenge - $bezugsmeridian) / 15) + $stunde;
        $lo             = deg2rad(279.3 + 0.9856 * $tag);
        $zgl            = 0.1644 * SIN(2 * ($lo + deg2rad(1.92) * SIN($lo + deg2rad(77.3)))) - 0.1277 * SIN($lo + deg2rad(77.3));
        $woz            = $moz + rad2deg($zgl) / 60;
        $stdWink        = deg2rad(15 * ($woz - 12));
        $deklination    = deg2rad((-23.45) * COS ((2 * PI() / 365.25) * ( $tag + 10 )));
        #dump("Deklination (rad): $deklination");
        $sonnenhoehe    = ASIN(SIN($deklination)*SIN(deg2rad($breite))+COS($deklination)*COS(deg2rad($breite))*COS($stdWink));
        $atheta         = ASIN((-(COS($deklination)*SIN($stdWink)))/COS($sonnenhoehe));
        $azimuth        = 180 - rad2deg($atheta);
        $zenitwinkel    = 90 - rad2deg($sonnenhoehe);
        $aoi            = 1 / COS(COS(deg2rad($zenitwinkel))*COS(deg2rad($neigungModul))+SIN(deg2rad($zenitwinkel))*SIN(deg2rad($neigungModul))*COS(deg2rad($azimuth-$azimuthModul)));
        ($aoi > $limitAOI) ? $aoiKorr = $limitAOI : $aoiKorr = $aoi;
        #dump("Azimuth: $azimuth | Zenit: $zenitwinkel | AOI: $aoi");
        $dayAngel       = 6.283185*($tag-1)/365;
        $etr            = 1370*(1.00011+0.034221*COS($dayAngel)+0.00128*SIN($dayAngel)+0.000719*COS(2*$dayAngel)+0.000077*SIN(2*$dayAngel));
        ($zenitwinkel < 80) ? $am = (1/(COS(deg2rad($zenitwinkel))+0.15/(93.885-$zenitwinkel)**1.253)) : $am = 0;
        ($am > 0)           ? $kt = $ghi/(COS(deg2rad($zenitwinkel))*$etr) : $kt = 0.0;
        #dump("ETR: $etr | AM: $am | KT: $kt");
        $dniMod = 0.0;
        if ($kt>0) {
            if ($kt>=0.6) {
                $a = -5.743+21.77*$kt-27.49*$kt**2+11.56*$kt**3;
                $b = 41.4-118.5*$kt+66.05*$kt**2+31.9*$kt**3;
                $c = -47.01+184.2*$kt-222*$kt**2+73.81*$kt**3;
            } elseif ($kt<0.6) {
                $a = 0.512-1.56*$kt+2.286*$kt**2-2.222*$kt**3;
                $b = 0.37+0.962*$kt;
                $c = -0.28+0.932*$kt-2.048*$kt**2;
            } else {
                $a = 0;
                $b = 0;
                $c = 0;
            }
            $dkn = $a+$b*EXP($c*$am);
            $knc = 0.886-0.122*$am+0.0121*($am)**2-0.000653*($am)**3+0.000014*($am)**4;
            #dump("a: $a | b: $b | c: $c | dkn: $dkn | knc: $knc");
            $dni = $etr*($knc-$dkn);
            $dniMod = $dni*COS($aoiKorr);
            #dump("DNI: $dni | DNImod: $dniMod");
        }
        $diffusMod = $ghi - $dniMod;

        $gmod1          = $aoi * $dniMod + $diffusMod; // Modulstrahlung 1
        $iam            = 1-0.05*((1/COS($aoi)-1));
        $gmod2          = $gmod1-$iam; // Modulstrahlung 2
        if ($gmod2 < 0) $gmod2 = 0; // Negative Werte machen keinen Sinn
        #dump("Stunde: $stunde Diffus: $diffusMod | Gmod1: $gmod1 | IAM: $iam | Gmod2: $gmod2 | GHI: $ghi");

        return $gmod2;
    }

    /**
     * Calculation of temprature of cell (Tcell) according to NREL
     * @param Anlage $anlage
     * @param float|null $windSpeed
     * @param float|null $airTemp
     * @param float|null $gPOA
     * @return float|null
     */
    public function tempCellNrel(Anlage $anlage, ?float $windSpeed, ?float $airTemp, ?float $gPOA): ?float
    {
        if (is_null($airTemp) || is_null($gPOA)) return null;
        if ($windSpeed < 0 || $windSpeed === null ) $windSpeed = 0;

        $a                  = $anlage->getTempCorrA();
        $b                  = $anlage->getTempCorrB();
        $deltaTcnd          = $anlage->getTempCorrDeltaTCnd();

        $tempModulBack  = $gPOA * pow(M_E, $a + ($b * $windSpeed)) + $airTemp;

        return $tempModulBack + ($gPOA / 1000) * $deltaTcnd;;
    }
}
