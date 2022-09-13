<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlageGroupMonths;
use App\Entity\AnlageGroups;
use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Repository\ForcastRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\GroupModulesRepository;
use App\Repository\GroupMonthsRepository;
use App\Repository\GroupsRepository;
use App\Repository\PVSystDatenRepository;
use PDO;
use DateTime;

class WeatherFunctionsService
{
    use G4NTrait;

    public function __construct(
        private PVSystDatenRepository  $pvSystRepo,
        private GroupMonthsRepository  $groupMonthsRepo,
        private GroupModulesRepository $groupModulesRepo,
        private GroupsRepository       $groupsRepo,
        private GridMeterDayRepository $gridMeterDayRepo,
        private ForcastRepository      $forecastRepo)
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
     * @return array|null
     */
    public function getWeather(WeatherStation $weatherStation, $from, $to): ?array
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

        if ($weather['anzahl'] > 0) {
            $sql = "SELECT 
                        SUM(g_lower) as irr_lower, 
                        SUM(g_upper) as irr_upper, 
                        SUM(g_horizontal) as irr_horizontal, 
                        AVG(at_avg) AS air_temp, 
                        AVG(pt_avg) AS panel_temp, 
                        AVG(wind_speed) as wind_speed ,
                        SUM(temp_cell_corr) as temp_cell_corr,
                        SUM(temp_cell_multi_irr) as temp_cell_multi_irr
                    FROM $dbTable 
                    WHERE stamp BETWEEN '$from' AND '$to'";
            $res = $conn->query($sql);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $weather['airTempAvg'] = $row['air_temp'];
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
     * Function to retrieve weighted irradiation – NOT ready – DON'T USE
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
        // Rückfallwert sollte nichts anderes gefunden werden
        $gwoben = 0.5;
        $gwunten = 0.5;
        $month = $stamp->format('m');

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
        $conn = false;

        #dump("Upper: $irrUpper | Lower: $irrLower | GewOben: $gwoben | GewUnten: $gwunten | Irr: $irr");

        return $irr;
    }
}
