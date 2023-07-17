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
use Psr\Cache\InvalidArgumentException;

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
     * @param Anlage $anlage
     * @param int|null $inverterID
     * @return array|null
     * @throws InvalidArgumentException
     */
    public function getWeather(WeatherStation $weatherStation, $from, $to, bool $ppc, Anlage $anlage, ?int $inverterID = null): ?array
    {
        $conn = self::getPdoConnection();
        $weather = [];
        $dbTable = $weatherStation->getDbNameWeather();
        $sql = "SELECT COUNT(db_id) AS anzahl FROM $dbTable WHERE stamp >= '$from' and stamp < '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $weather['anzahl'] = $row['anzahl'];
        }
        unset($res);

        if ($ppc && $anlage->getUsePPC()){
            $sqlPPCpart1 = " LEFT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp ";
            $sqlPPCpart2 = " AND (ppc.p_set_gridop_rel = 100 OR ppc.p_set_gridop_rel is null) 
                        AND (ppc.p_set_rpc_rel = 100 OR ppc.p_set_rpc_rel is null) ";
        } else {
            $sqlPPCpart1 = $sqlPPCpart2 = "";
        }

        $pNom = $anlage->getPnom();
        $pNomEast = $anlage->getPowerEast();
        $pNomWest = $anlage->getPowerWest();
        $inverterPowerDc = $anlage->getPnomInverterArray();
        // depending on $department generate correct SQL code to calculate
        if($anlage->getIsOstWestAnlage()){
            if ($inverterID === null){
                $sqlTheoPowerPart = "
                    SUM(g_upper * $pNomEast * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA3().", pa3, 1)) + 
                    SUM(g_lower * $pNomWest * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA3().", pa3, 1)) as theo_power_pa3,
                    SUM(g_upper * $pNomEast * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA2().", pa2, 1)) + 
                    SUM(g_lower * $pNomWest * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA2().", pa2, 1)) as theo_power_pa2,
                    SUM(g_upper * $pNomEast * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA1().", pa1, 1)) + 
                    SUM(g_lower * $pNomWest * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA1().", pa1, 1)) as theo_power_pa1,
                    SUM(g_upper * $pNomEast * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA0().", pa0, 1)) + 
                    SUM(g_lower * $pNomWest * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA0().", pa0, 1)) as theo_power_pa0,
                ";
            } else {
                $pNomInv = $inverterPowerDc[$inverterID];
                $sqlTheoPowerPart = "
                    SUM(((g_upper + g_lower) / 2) * $pNomInv * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA3().", pa3, 1)) as theo_power_pa3,
                    SUM(((g_upper + g_lower) / 2) * $pNomInv * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA2().", pa2, 1))  as theo_power_pa2,
                    SUM(((g_upper + g_lower) / 2) * $pNomInv * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA1().", pa1, 1))  as theo_power_pa1,
                    SUM(((g_upper + g_lower) / 2) * $pNomInv * IF(((g_upper + g_lower) / 2) > ".$anlage->getThreshold2PA0().", pa0, 1))  as theo_power_pa0,
                ";
            }

        } else {
            if ($inverterID === null) {
                $sqlTheoPowerPart = "SUM(g_upper * $pNom * IF(g_upper > " . $anlage->getThreshold2PA3() . ", pa3, 1)) as theo_power_pa3,
                            SUM(g_upper * $pNom * IF(g_upper > " . $anlage->getThreshold2PA2() . ", pa2, 1)) as theo_power_pa2,
                            SUM(g_upper * $pNom * IF(g_upper > " . $anlage->getThreshold2PA1() . ", pa1, 1)) as theo_power_pa1,
                            SUM(g_upper * $pNom * IF(g_upper > " . $anlage->getThreshold2PA0() . ", pa0, 1)) as theo_power_pa0,";
            } else {
                $pNomInv = $inverterPowerDc[$inverterID];
                $sqlTheoPowerPart = "SUM(g_upper * $pNomInv * IF(g_upper > " . $anlage->getThreshold2PA3() . ", pa3, 1)) as theo_power_pa3,
                            SUM(g_upper * $pNomInv * IF(g_upper > " . $anlage->getThreshold2PA2() . ", pa2, 1)) as theo_power_pa2,
                            SUM(g_upper * $pNomInv * IF(g_upper > " . $anlage->getThreshold2PA1() . ", pa1, 1)) as theo_power_pa1,
                            SUM(g_upper * $pNomInv * IF(g_upper > " . $anlage->getThreshold2PA0() . ", pa0, 1)) as theo_power_pa0,";
            }
        }
        if ($weather['anzahl'] > 0) {
            $sql = "SELECT 
                    SUM(g_lower) as irr_lower, 
                    SUM(g_upper) as irr_upper, 
                    SUM(g_horizontal) as irr_horizontal, 
                    $sqlTheoPowerPart
                    AVG(temp_ambient) AS ambient_temp, 
                    AVG(temp_pannel) AS panel_temp, 
                    AVG(wind_speed) as wind_speed ,
                    SUM(temp_cell_corr) as temp_cell_corr,
                    SUM(temp_cell_multi_irr) as temp_cell_multi_irr
                FROM $dbTable s
                    $sqlPPCpart1
                WHERE s.stamp >= '$from' AND s.stamp < '$to'
                    $sqlPPCpart2;
            ";
            dump($sql);
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
                $weather['theoPowerPA0'] = $row['theo_power_pa0'] / 1000 / 4;
                $weather['theoPowerPA1'] = $row['theo_power_pa1'] / 1000 / 4;
                $weather['theoPowerPA2'] = $row['theo_power_pa2'] / 1000 / 4;
                $weather['theoPowerPA3'] = $row['theo_power_pa3'] / 1000 / 4;
            }
            unset($res);
        } else {
            $weather = null;
        }
        $conn = null;

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
     * Function to retrieve All Sensor (Irr) Data from Databse 'db_ist' for selected Daterange
     * Return Array with
     *
     * @param Anlage $anlage
     * @param DateTime $from
     * @param DateTime $to
     * @return array
     */
    public function getSensors(Anlage $anlage, DateTime $from, DateTime $to): array
    {
        $conn = self::getPdoConnection();
        $result = [];

        $dbTable = $anlage->getDbNameIst();
        // Suche nur für einen Inverter, da bei allen das gleiche steht, deshalb Umzug zu den Wetter Daten
        $sql = "SELECT stamp, irr_anlage FROM $dbTable WHERE unit = 1 AND stamp >= '" .$from->format('Y-m-d H:i')."' and stamp < '".$to->format('Y-m-d H:i')."'";
        $res = $conn->query($sql);
        if ($res->rowCount() >= 1) {
            $rows = $res->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $result[$row['stamp']] = json_decode($row['irr_anlage'], true);
            }
        }
        unset($res);

        return $result;
    }
}