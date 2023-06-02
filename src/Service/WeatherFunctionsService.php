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
    public function correctSensorsByTicket(Anlage $anlage, array $sensorData, DateTime $startDate, DateTime $endDate): ?array
    {
        // Suche alle Tickets (Ticketdates) die in den Zeitraum fallen
        // Es werden Nur Tickets mit Sensor Bezug gesucht (Performance Tickets mit ID =
        $ticketArray = $this->ticketDateRepo->performanceTickets($anlage, $startDate, $endDate);

        // Dursuche alle Tickets in Schleife
        // berechne Wert aus Original Daten und Subtrahiere vom Wert
        // berechne ersatz Wert und Addiere zum entsprechenden Wert
        /** @var TicketDate $ticket */
        foreach ($ticketArray as $ticket){ #loop über query result
            // Start und End Zeitpunkt ermitteln, es sollen keine Daten gesucht werden die auserhalb des Übergebenen Zeitaums liegen.
            // Ticket kann ja schon vor dem Zeitraum gestartet oder danach erst beendet werden
            if ($startDate > $ticket->getBegin()){
                $tempoStartDate = $startDate;
            } else {
                $tempoStartDate = $ticket->getBegin();
            }
            if ($endDate < $ticket->getEnd()){
                $tempoEndDate = $endDate;
            } else {
                $tempoEndDate = $ticket->getEnd();
            }

            $tempWeatherArray = $this->getWeather($anlage->getWeatherStation(), $tempoStartDate->format('Y-m-d H:i'), $tempoEndDate->format('Y-m-d H:i'));
            $replaceArray = $this->replaceValuesTicketRepo->getSum($anlage, $tempoStartDate, $tempoEndDate);

            // korriegiere Horizontal Irradiation
            if ($replaceArray['irrHorizotal'] && $replaceArray['irrHorizotal'] > 0) {
                $sensorData['horizontalIrr'] = $sensorData['horizontalIrr'] - $tempWeatherArray['horizontalIrr'] + $replaceArray['irrHorizotal'];
            }

            // korriegiere Irradiation auf Modulebene
            if (!$replaceArray['irrEast'] && !$replaceArray['irrWest']) {
                // eine Ausrichtung
                if ($replaceArray['irrModul'] && $replaceArray['irrModul'] > 0) {
                    $sensorData['upperIrr'] = $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'] + $replaceArray['irrModul'];
                }
            } else {
                // zwei Ausrichtungen (Ost / West)
                if ($replaceArray['irrEast'] && $replaceArray['irrEast'] > 0) {
                    $sensorData['upperIrr'] = $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'] + $replaceArray['irrEast'];
                }
                if ($replaceArray['irrWest'] && $replaceArray['irrWest'] > 0) {
                    $sensorData['lowerIrr'] = $sensorData['lowerIrr'] - $tempWeatherArray['lowerIrr'] + $replaceArray['irrWest'];
                }
            }
        }

        return $sensorData;
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


}
