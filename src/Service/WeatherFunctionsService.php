<?php


namespace App\Service;


use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Repository\ForecastRepository;
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
    private ForecastRepository $forecastRepo;

    public function __construct(
        PVSystDatenRepository $pvSystRepo,
        GroupMonthsRepository $groupMonthsRepo,
        GroupModulesRepository $groupModulesRepo,
        GroupsRepository $groupsRepo,
        GridMeterDayRepository $gridMeterDayRepo,
        ForecastRepository $forecastRepo)
    {
        $this->pvSystRepo = $pvSystRepo;
        $this->groupMonthsRepo = $groupMonthsRepo;
        $this->groupModulesRepo = $groupModulesRepo;
        $this->groupsRepo = $groupsRepo;
        $this->gridMeterDayRepo = $gridMeterDayRepo;
        $this->forecastRepo = $forecastRepo;
    }

    public function getWeather(WeatherStation $weatherStation, $from, $to) :array
    {
        $conn = self::getPdoConnection();

        $weather = [];
        $dbTable = $weatherStation->getDbNameWeather();
        $sql = "SELECT COUNT(db_id) AS anzahl FROM $dbTable WHERE stamp BETWEEN '$from' and '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $weather['anzahl'] = $row["anzahl"];
        }
        unset($res);

        $sql = "SELECT sum(g_lower) as irr_lower, sum(g_upper) as irr_upper, sum(g_horizontal) as irr_horizontal, AVG(at_avg) AS air_temp, AVG(pt_avg) AS panel_temp, AVG(wind_speed) as wind_speed FROM $dbTable WHERE stamp BETWEEN '$from' and '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $weather['airTempAvg'] = $row["air_temp"];
            $weather['panelTempAvg'] = $row["panel_temp"];
            $weather['windSpeedAvg'] = $row['wind_speed'];
            $weather['horizontalIrr'] = $row['irr_horizontal'];
            $weather['horizontalIrrAvg'] = $row['irr_horizontal'] / $weather['anzahl'];
            if ($weatherStation->getChangeSensor() == "Yes") {
                $weather['upperIrr'] = $row["irr_lower"];
                $weather['lowerIrr'] = $row["irr_upper"];
            } else {
                $weather['upperIrr'] = $row["irr_upper"];
                $weather['lowerIrr'] = $row["irr_lower"];
            }
        }
        unset($res);
        $conn = null;

        return $weather;
    }
}