<?php

namespace App\Service\Functions;

use App\Entity\Anlage;
use App\Entity\TicketDate;
use App\Helper\G4NTrait;
use App\Repository\ReplaceValuesTicketRepository;
use App\Repository\TicketDateRepository;
use App\Repository\TicketRepository;
use App\Service\WeatherFunctionsService;
use DateTime;
use PDO;
use App\Service\GetPdoService;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

class IrradiationService
{
    use G4NTrait;

    public function __construct(
private GetPdoService $getPdoService,
        private TicketRepository $ticketRepo,
        private TicketDateRepository $ticketDateRepo,
        private ReplaceValuesTicketRepository $replaceValuesTicketRepo,
        private WeatherFunctionsService $weatherFunctionsService,
        private CacheInterface $cache
    )
    {

    }

    /**
     * @throws InvalidArgumentException
     */
    public function getIrrData(Anlage $anlage, String $from, String $to): array
    {
        return $this->cache->get('getIrrData_'.md5($anlage->getAnlId().$from.$to), function(CacheItemInterface $cacheItem) use ($from, $to, $anlage)
        {
            $cacheItem->expiresAfter(60); // Lifetime of cache Item

            $conn = $this->getPdoService->getPdoPlant();
            $irrData = [];
            $sqlIrrFlag = "";
            if ($anlage->getAnlId() == 181){ // Zwartowo
                $sqlIrrFlag = ", b.irr_flag ";
            }

            $sqlEinstrahlung = "SELECT a.stamp, b.g_lower, b.g_upper, b.wind_speed $sqlIrrFlag FROM (db_dummysoll a left JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' AND '$to'";
            $resultEinstrahlung = $conn->query($sqlEinstrahlung);

            if ($resultEinstrahlung->rowCount() > 0) {
                while ($row = $resultEinstrahlung->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = $row['stamp'];
                    $row['g_upper'] = (float) $row['g_upper'] > 0 ? (float) $row['g_upper'] : 0;
                    $row['g_lower'] = (float) $row['g_lower'] > 0 ? (float) $row['g_lower'] : 0;
                    if ($anlage->getIsOstWestAnlage()) {
                        $strahlung = ($row['g_upper'] * $anlage->getPowerEast() + $row['g_lower'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
                    } else {
                        $strahlung = $row['g_upper'];
                    }
                    $irrData[$stamp]['stamp'] = $stamp;
                    $irrData[$stamp]['irr'] = $strahlung;
                    if (isset($row['irr_flag'])) {
                        $irrData[$stamp]['irr_flag'] = (bool)$row['irr_flag'];
                    } else {
                        $irrData[$stamp]['irr_flag'] = true;
                    }
                }
            }
            unset($result);
            $conn = null;

            return self::correctIrrByTicket($anlage, $from, $to, $irrData);
        });
    }

    private function correctIrrByTicket(Anlage $anlage, string $from, string $to, array $irrData): array
    {
        $startDate = date_create($from);
        $endDate = date_create($to);
        // Suche alle Tickets (Ticketdates) die in den Zeitraum fallen
        // Es werden Nur Tickets mit Sensor Bezug gesucht (Performance Tickets mit ID = 71, 72, 73
        $ticketArray = $this->ticketDateRepo->performanceTickets($anlage, $startDate, $endDate);

        // Dursuche alle Tickets in Schleife
        // berechne Wert aus Original Daten und Subtrahiere vom Wert
        // berechne ersatz Wert und Addiere zum entsprechenden Wert
        /** @var TicketDate $ticket */
        foreach ($ticketArray as $ticket){ #loop über query result
            // Start und End Zeitpunkt ermitteln, es sollen keine Daten gesucht werden die auserhalb des Übergebenen Zeitaums liegen.
            // Ticket kann ja schon vor dem Zeitraum gestartet oder danach erst beendet werden
            $tempoStartDate = $startDate > $ticket->getBegin() ? $startDate : $ticket->getBegin();
            $tempoEndDate = $endDate < $ticket->getEnd() ? $endDate :$ticket->getEnd();

            switch ($ticket->getAlertType()) {
                // Exclude Sensors
                case '70':
                    // Funktionier in der ersten Version nur für Leek und Kampen
                    // es fehlt die Möglichkeit die gemittelte Strahlung, automatisiert aus den Sensoren zu berechnen
                    // ToDo: Sensor Daten müssen zur Wetter DB umgezogen werden, dann Code anpassen

                    // Search for sensor (irr) values in ac_ist database
                    $sensorValues = $this->weatherFunctionsService->getSensors($anlage, $tempoStartDate, $tempoEndDate);
                    // ermitteln welche Sensoren excludiert werden sollen
                    $mittelwertPyrHoriArray = $mittelwertPyroArray = $mittelwertPyroEastArray = $mittelwertPyroWestArray = [];
                    foreach ($sensorValues as $date => $sensorValue) {
                        foreach ($anlage->getSensorsInUse() as $sensor) {
                            if (!str_contains($ticket->getSensors(), $sensor->getNameShort())) {
                                switch ($sensor->getVirtualSensor()) {
                                    case 'irr-hori':
                                        $mittelwertPyrHoriArray[] = $sensorValue[$sensor->getNameShort()];
                                        break;
                                    case 'irr':
                                        $mittelwertPyroArray[] = $sensorValue[$sensor->getNameShort()];
                                        break;
                                    case 'irr-east':
                                        $mittelwertPyroEastArray[] = $sensorValue[$sensor->getNameShort()];
                                        break;
                                    case 'irr-west':
                                        $mittelwertPyroWestArray[] = $sensorValue[$sensor->getNameShort()];
                                        break;
                                }
                            }
                            // erechne neuen Mittelwert aus den Sensoren die genutzt werden sollen
                            if ($anlage->getIsOstWestAnlage()) {
                                $irrData[$date]['irr'] = (self::mittelwert($mittelwertPyroEastArray) * $anlage->getPowerEast() + self::mittelwert($mittelwertPyroWestArray) * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
                            } else {
                                $irrData[$date]['irr'] = self::mittelwert($mittelwertPyroArray);
                            }
                        }
                    }
                    break;

                // Replace Sensors
                case '71':
                    $replaceArray = $this->replaceValuesTicketRepo->getIrrArray($anlage, $tempoStartDate, $tempoEndDate);
                    foreach ($replaceArray as $replace) {
                        if ($anlage->getIsOstWestAnlage()) {
                            $irrData[$replace['stamp']]['irr'] = ($replace['irrEast'] * $anlage->getPowerEast() + $replace['irrWest'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
                        } else {
                            $irrData[$replace['stamp']]['irr'] = $replace['irrModul'];
                        }
                    }
                    break;
            }
        }


        return $irrData;
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
    #[Deprecated]
    public function Hglobal2Hmodul(Anlage $anlage, DateTime $stamp, ?float $ghi = 0.0, float $bezugsmeridian = 15, float $azimuthModul = 180, float $neigungModul = 20): ?float
    {
        if ($ghi === null) {
            return null;
        }

        $breite = $anlage->getAnlGeoLat();
        $laenge = $anlage->getAnlGeoLon();

        $limitAOI       = deg2rad(78);

        $tag = $stamp->format('z');
        $tag++; // Tag um eins erhöhen, da Formel annimmt das der erste Tag im Jahr = 1 ist und nicht 0 wie format('z') zurück gibt
        $stunde = (integer)$stamp->format('G');

        $moz            = (($laenge - $bezugsmeridian) / 15) + $stunde;
        $lo             = deg2rad(279.3 + 0.9856 * $tag);
        $zgl            = 0.1644 * SIN(2 * ($lo + deg2rad(1.92) * SIN($lo + deg2rad(77.3)))) - 0.1277 * SIN($lo + deg2rad(77.3));
        $woz            = $moz + rad2deg($zgl) / 60;
        $stdWink        = deg2rad(15 * ($woz - 12));
        $deklination    = deg2rad((-23.45) * COS ((2 * PI() / 365.25) * ( $tag + 10 )));

        $sonnenhoehe    = ASIN(SIN($deklination)*SIN(deg2rad($breite))+COS($deklination)*COS(deg2rad($breite))*COS($stdWink));
        $atheta         = ASIN((-(COS($deklination)*SIN($stdWink)))/COS($sonnenhoehe));
        $azimuth        = 180 - rad2deg($atheta);
        $zenitwinkel    = 90 - rad2deg($sonnenhoehe);
        $aoi            = 1 / COS(COS(deg2rad($zenitwinkel))*COS(deg2rad($neigungModul))+SIN(deg2rad($zenitwinkel))*SIN(deg2rad($neigungModul))*COS(deg2rad($azimuth-$azimuthModul)));
        ($aoi > $limitAOI) ? $aoiKorr = $limitAOI : $aoiKorr = $aoi;

        $dayAngel       = 6.283185*($tag-1)/365;
        $etr            = 1370*(1.00011+0.034221*COS($dayAngel)+0.00128*SIN($dayAngel)+0.000719*COS(2*$dayAngel)+0.000077*SIN(2*$dayAngel));
        ($zenitwinkel < 80) ? $am = (1/(COS(deg2rad($zenitwinkel))+0.15/(93.885-$zenitwinkel)**1.253)) : $am = 0;
        ($am > 0)           ? $kt = $ghi/(COS(deg2rad($zenitwinkel))*$etr) : $kt = 0.0;

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

            $dni = $etr*($knc-$dkn);
            $dniMod = $dni*COS($aoiKorr);

        }
        $diffusMod = $ghi - $dniMod;

        $gmod1          = $aoi * $dniMod + $diffusMod; // Modulstrahlung 1
        $iam            = 1-0.05*((1/COS($aoi)-1));
        $gmod2          = $gmod1-$iam; // Modulstrahlung 2
        if ($gmod2 < 0) $gmod2 = 0; // Negative Werte machen keinen Sinn

        return $gmod2;
    }

    /**
     * Calculation of temprature of cell (Tcell) according to NREL
     *
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

        return $tempModulBack + ($gPOA / 1000) * $deltaTcnd;
    }
}