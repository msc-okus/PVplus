<?php

namespace App\Service;

use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Repository\ForcastRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\GroupModulesRepository;
use App\Repository\GroupMonthsRepository;
use App\Repository\GroupsRepository;
use App\Repository\PVSystDatenRepository;
use PDO;

class WeatherFunctionsService
{
    use G4NTrait;

    private PVSystDatenRepository $pvSystRepo;

    private GroupMonthsRepository $groupMonthsRepo;

    private GroupModulesRepository $groupModulesRepo;

    private GroupsRepository $groupsRepo;

    private GridMeterDayRepository $gridMeterDayRepo;

    private ForcastRepository $forecastRepo;

    public function __construct(
        PVSystDatenRepository $pvSystRepo,
        GroupMonthsRepository $groupMonthsRepo,
        GroupModulesRepository $groupModulesRepo,
        GroupsRepository $groupsRepo,
        GridMeterDayRepository $gridMeterDayRepo,
        ForcastRepository $forecastRepo)
    {
        $this->pvSystRepo = $pvSystRepo;
        $this->groupMonthsRepo = $groupMonthsRepo;
        $this->groupModulesRepo = $groupModulesRepo;
        $this->groupsRepo = $groupsRepo;
        $this->gridMeterDayRepo = $gridMeterDayRepo;
        $this->forecastRepo = $forecastRepo;
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
}
