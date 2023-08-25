<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlageForcastDay;
use App\Entity\AnlageGroupMonths;
use App\Entity\AnlageGroups;
use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Repository\AcGroupsRepository;
use App\Repository\ForcastDayRepository;
use App\Repository\ForcastRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\GroupModulesRepository;
use App\Repository\GroupMonthsRepository;
use App\Repository\GroupsRepository;
use App\Repository\InvertersRepository;
use App\Repository\MonthlyDataRepository;
use App\Repository\PVSystDatenRepository;
use DateTime;
use PDO;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use function Symfony\Component\String\u;

class FunctionsService
{
    use G4NTrait;

    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private $userPlant,
        private $passwordPlant,
        private PVSystDatenRepository $pvSystRepo,
        private GroupMonthsRepository $groupMonthsRepo,
        private GroupModulesRepository $groupModulesRepo,
        private GroupsRepository $groupsRepo,
        private AcGroupsRepository $acGroupsRepo,
        private InvertersRepository $inverterRepo,
        private GridMeterDayRepository $gridMeterDayRepo,
        private ForcastRepository $forcastRepo,
        private ForcastDayRepository $forcastDayRepo,
        private MonthlyDataRepository $monthlyDataRepo,
        private WeatherFunctionsService $weatherFunctions,
        private CacheInterface $cache)
    {
    }

    /**
     * @param $from
     * @param $to
     * @param $pacDateStart
     * @param $pacDateEnd
     *
     * @deprecated
     */
    public function getSumPowerAcAct(Anlage $anlage, $from, $to, $pacDateStart, $pacDateEnd): array
    {
        $conn = self::getPdoConnection($this->host, $this->userPlant, $this->passwordPlant);
        $result = [];
        $irrAnlage = [];
        $tempAnlage = [];
        $windAnlage = [];
        $dbTable = $anlage->getDbNameAcIst();
        $wsTable = $anlage->getWeatherStation()->getDbNameWeather();

        // Lade die Strahlung für jeden Zeitstempel (stamp) aus dem JSON Array in ein php Array
        $sqlIrr = "SELECT stamp, irr_anlage, temp_anlage, wind_anlage FROM $dbTable WHERE stamp BETWEEN '$from' AND '$to'";
        $resIrr = $conn->query($sqlIrr);
        if ($resIrr->rowCount() > 0) {
            while ($row = $resIrr->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $row['stamp'];
                $irrAnlage[$stamp]  = json_decode($row['irr_anlage']);
                $tempAnlage[$stamp] = json_decode($row['temp_anlage'], true);
                $windAnlage[$stamp] = json_decode($row['wind_anlage'], true);
            }
        }
        unset($res);

        // Lade die Leistung aus dem EVU Zähler (wird aber für jede Gruppe gespeichert, deshalb Limit 1 und Group by 'group_ac')
        $powerEvu = $powerEvuYear = $powerEvuPac = $powerEvuMonth = $powerAct = 0;
        $powerActYear = $powerActPac = $powerActMonth = 0;
        $theoPowerMonth = $theoPowerPac = $theoPowerYear = 0;

        // ############ für das ganze Jahr #############
        $jahresanfang = date('Y-01-01 00:00', strtotime($from)); // für das ganze Jahr - Zeitraum
        // LIMIT 1 muss sein, da der evu Wert in jedem Datensatz gespeichert ist (Wert entspricht summe aller Gruppen), er darf aber nur einaml pro Zeiteinheit abgefragt werden.
        if ($anlage->isIgnoreNegativEvu()) {
            $sql_year = "SELECT sum(e_z_evu) as power_evu_year FROM $dbTable where stamp between '$jahresanfang' and '$to' AND e_z_evu > 0 group by unit LIMIT 1";
        } else {
            $sql_year = "SELECT sum(e_z_evu) as power_evu_year FROM $dbTable where stamp between '$jahresanfang' and '$to' group by unit LIMIT 1";
        }
        $res = $conn->query($sql_year);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerEvuYear = round($row['power_evu_year'], 4);
        }
        unset($res);
        $powerEvuYear = $this->checkAndIncludeMonthlyCorrectionEVU($anlage, $powerEvuYear, $jahresanfang, $to);

        $sql_year = "SELECT sum(wr_pac) as power_act_year FROM $dbTable where stamp between '$jahresanfang' and '$to' and wr_pac > 0";
        $res = $conn->query($sql_year);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerActYear = round($row['power_act_year'], 4);
        }
        unset($res);

        $sql_year = "SELECT sum(theo_power) as theo_power FROM $dbTable where stamp between '$jahresanfang' and '$to'";
        $res = $conn->query($sql_year);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $theoPowerYear = round($row['theo_power'], 4);
        }
        unset($res);

        // ################# PAC ################
        if ($anlage->getUsePac()) {
            // beginnend bei PAC Date
            // group_ac = 1 muss sein, da der evu Wert in jedem Datensatz gespeichert ist (Wert entspricht summe aller Gruppen), er darf aber nur einaml pro Zeiteinheit abgefragt werden.
            if ($anlage->isIgnoreNegativEvu()) {
                $sql_pac = "SELECT sum(e_z_evu) as power_evu FROM $dbTable where stamp between '$pacDateStart' and '$to' AND e_z_evu > 0 GROUP BY unit LIMIT 1";
            } else {
                $sql_pac = "SELECT sum(e_z_evu) as power_evu FROM $dbTable where stamp between '$pacDateStart' and '$to' GROUP BY unit LIMIT 1";
            }
            $res = $conn->query($sql_pac);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $powerEvuPac = round($row['power_evu'], 4);
            }
            unset($res);
            $powerEvuPac = $this->checkAndIncludeMonthlyCorrectionEVU($anlage, $powerEvuPac, $pacDateStart, $to);

            $sql_pac = "SELECT sum(wr_pac) as power_act_pac FROM $dbTable where stamp between '$pacDateStart' and '$to' AND wr_pac > 0";
            $res = $conn->query($sql_pac);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $powerActPac = round($row['power_act_pac'], 4);
            }
            unset($res);

            $sql_pac = "SELECT sum(theo_power) as theo_power FROM $dbTable where stamp between '$pacDateStart' and '$to'";
            $res = $conn->query($sql_pac);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $theoPowerPac = round($row['theo_power'], 4);
            }
            unset($res);
        }

        // ################# Month ################
        $startMonth = date('Y-m-01 00:00', strtotime($from));
        if ($anlage->isIgnoreNegativEvu()) {
            $sql = "SELECT sum(e_z_evu) as power_evu FROM $dbTable WHERE stamp BETWEEN '$startMonth' AND '$to' AND e_z_evu > 0 GROUP BY unit LIMIT 1";
        } else {
            $sql = "SELECT sum(e_z_evu) as power_evu FROM $dbTable WHERE stamp BETWEEN '$startMonth' AND '$to' GROUP BY unit LIMIT 1";
        }
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerEvuMonth = round($row['power_evu'], 4);
        }
        unset($res);
        $powerEvuMonth = $this->checkAndIncludeMonthlyCorrectionEVU($anlage, $powerEvuMonth, $startMonth, $to);

        $sql = "SELECT sum(wr_pac) as power_act FROM $dbTable WHERE stamp BETWEEN '$startMonth' AND '$to' AND wr_pac > 0";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerActMonth = round($row['power_act'], 4);
        }
        unset($res);

        $sql = "SELECT sum(theo_power) as theo_power FROM $dbTable WHERE stamp BETWEEN '$startMonth' AND '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $theoPowerMonth = round($row['theo_power'], 4);
        }
        unset($res);

        $result['powerEvuYear'] = $powerEvuYear;
        $result['powerActYear'] = $powerActYear;
        $result['powerEvuPac'] = $powerEvuPac;
        $result['powerActPac'] = $powerActPac;
        $result['powerEvuMonth'] = $powerEvuMonth;
        $result['powerActMonth'] = $powerActMonth;

        $result['theoPowerMonth'] = $theoPowerMonth;
        $result['theoPowerPac'] = $theoPowerPac;
        $result['theoPowerYear'] = $theoPowerYear;

        // Lade AC Power
        // ############ für den angeforderten Zeitraum #############
        $sql = "SELECT sum(e_z_evu) as power_evu FROM $dbTable WHERE stamp >= '$from' AND stamp <= '$to' AND e_z_evu >= 0 GROUP BY unit LIMIT 1";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerEvu = round($row['power_evu'], 4);
        }
        unset($res);
        $sql = "SELECT sum(wr_pac) as sum_power_ac FROM $dbTable WHERE stamp >= '$from' AND stamp <= '$to' AND wr_pac >= 0";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerAct = round($row['sum_power_ac'], 4);
        }
        unset($res);

        $sql = "SELECT sum(theo_power) as theo_power FROM $dbTable WHERE stamp >= '$from' AND stamp <= '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $result['powerEvu'] = $powerEvu;
            $result['powerAct'] = $powerAct;
            $result['irrAnlage'] = $irrAnlage;
            $result['tempAnlage'] = $tempAnlage;
            $result['windAnlage'] = $windAnlage;
            $result['sumPower'] = $powerAct;
            $result['theoPower'] = round($row['theo_power'], 4);
        }
        unset($res);
        $conn = null;

        return $result;
    }

    /**
     * @param $from
     * @param $to
     * @param $pacDate
     * @param $pacDateEnd
     */
    public function getSumPowerAcExp(Anlage $anlage, $from, $to, $pacDate, $pacDateEnd): array
    {
        $conn = self::getPdoConnection($this->host, $this->userPlant, $this->passwordPlant);
        $dbTable = $anlage->getDbNameDcSoll();
        $sumPowerExp = $sumPowerExpMonth = $sumPowerExpPac = $sumPowerExpYear = 0;
        $sumPowerExpEVU = $sumPowerExpEVUMonth = $sumPowerExpEVUPac = $sumPowerExpEVUYear = 0;

        // Day
        $sql = "SELECT sum(ac_exp_power) as sum_power_ac, sum(ac_exp_power_evu) as sum_power_ac_evu FROM $dbTable WHERE stamp BETWEEN '$from' AND '$to'";
        $res = $conn->query($sql);
        if ($res) {
            while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
                $sumPowerExp = $ro['sum_power_ac'];
                $sumPowerExpEVU = $ro['sum_power_ac_evu'];
            }
        }
        unset($res);

        // Month
        $startMonth = date('Y-m-01 00:00', strtotime($to));
        $sql = "SELECT sum(ac_exp_power) as sum_power_ac, sum(ac_exp_power_evu) as sum_power_ac_evu FROM $dbTable WHERE stamp BETWEEN '$startMonth' AND '$to'";
        $res = $conn->query($sql);
        if ($res) {
            while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
                $sumPowerExpMonth = $ro['sum_power_ac'];
                $sumPowerExpEVUMonth = $ro['sum_power_ac_evu'];
            }
        }
        unset($res);

        // Pac
        if ($anlage->getUsePac()) {
            $sql = "SELECT sum(ac_exp_power) as sum_power_ac, sum(ac_exp_power_evu) as sum_power_ac_evu FROM $dbTable WHERE stamp BETWEEN '$pacDate' AND '$to'";
            $res = $conn->query($sql);
            if ($res) {
                while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
                    $sumPowerExpPac = $ro['sum_power_ac'];
                    $sumPowerExpEVUPac = $ro['sum_power_ac_evu'];
                }
            }
            unset($res);
        }

        // Year
        $startYear = date('Y-01-01 00:00', strtotime($to));
        $sql = "SELECT sum(ac_exp_power) as sum_power_ac, sum(ac_exp_power_evu) as sum_power_ac_evu FROM $dbTable WHERE stamp BETWEEN '$startYear' AND '$to'";
        $res = $conn->query($sql);
        if ($res) {
            while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
                $sumPowerExpYear = $ro['sum_power_ac'];
                $sumPowerExpEVUYear = $ro['sum_power_ac_evu'];
            }
        }
        unset($res);

        $powerExpArray['sumPowerExp'] = round($sumPowerExp, 4);
        $powerExpArray['sumPowerExpMonth'] = round($sumPowerExpMonth, 4);
        $powerExpArray['sumPowerExpPac'] = round($sumPowerExpPac, 4);
        $powerExpArray['sumPowerExpYear'] = round($sumPowerExpYear, 4);

        $powerExpArray['sumPowerEvuExp'] = round($sumPowerExpEVU, 4);
        $powerExpArray['sumPowerEvuExpMonth'] = round($sumPowerExpEVUMonth, 4);
        $powerExpArray['sumPowerEvuExpPac'] = round($sumPowerExpEVUPac, 4);
        $powerExpArray['sumPowerEvuExpYear'] = round($sumPowerExpEVUYear, 4);

        $conn = null;

        return $powerExpArray;
    }

    /**
     * Calculate forcast for given plant and given month. Base aare the data from entity 'AnlagenForecastDay'.
     */
    public function getForcastByMonth(Anlage $anlage, int $month, ?int $betriebsJahre = null): float
    {

        $sum = (float) 0;
        if ($betriebsJahre === null || $betriebsJahre < 0) {
            $betriebsJahre = (int) date('Y') - (int) $anlage->getAnlBetrieb()->format('Y');
        }
        /** @var AnlageForcastDay $forcast*/
        $forcasts = $this->forcastDayRepo->findForcastDayByMonth($anlage, $month + 1 );
        foreach ($forcasts as $forcast) {
            $sum +=$forcast->getPowerDay();
        }

        return $sum;
    }

    /**
     * @param $startdate
     * @param $enddate
     * @param $day
     */
    public function getFacForcast(Anlage $anlage, $startdate, $enddate, $day): array
    {
        $forcastResultArray = [];

        $forcasts = $this->forcastRepo->findBy(['anlage' => $anlage]);
        // Kopiere alle Forcast Werte in ein Array mit dem Index der Kalenderwoche
        $forcastResultArray['sumForecast'] = $forcastResultArray['divMinus'] = $forcastResultArray['divPlus'] = $forcastResultArray['sumActual'] = 0;
        foreach ($forcasts as $forcast) {
            $forcastResultArray['sumForecast'] += $forcast->getPowerWeek();
            $forcastResultArray['divMinus'] += $forcast->getDivMinWeek();
            $forcastResultArray['divPlus'] += $forcast->getDivMaxWeek();
        }

        $conn = self::getPdoConnection($this->host, $this->userPlant, $this->passwordPlant);
        $sql = "SELECT week(date_format(stamp, '2000-%m-%d'),3) as kw, sum(e_z_evu) AS sumEvu  
                FROM ".$anlage->getDbNameAcIst()."
                WHERE stamp BETWEEN '$startdate' AND '$day' AND unit = 1 GROUP BY week(date_format(stamp, '2000-%m-%d'),3) 
                ORDER BY kw;";
        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $actPerWeek[$value['kw']] = $value['sumEvu'];
        }
        $conn = null;

        foreach ($forcasts as $week => $forcast) {
            if (isset($actPerWeek[$forcast->getWeek()])) {
                $forcastResultArray['sumActual'] += $actPerWeek[$forcast->getWeek()];
                $forcastResultArray['divMinus'] -= $forcast->getDivMinWeek();
                $forcastResultArray['divPlus'] -= $forcast->getDivMaxWeek();
            } else {
                $forcastResultArray['sumActual'] += $forcast->getPowerWeek();
            }
        }

        return $forcastResultArray;
    }

    /**
     * @param $from
     * @param $to
     * @param $pacDate
     */
    public function getPvSyst(Anlage $anlage, $from, $to, $pacDate): array
    {
        $startYear = date('Y-01-01 00:00', strtotime($to));
        $powerPvSystArray['powerPvSyst'] = 0;
        $powerPvSystArray['powerPvSystYear'] = 0;
        $powerPvSystArray['powerPvSystPac'] = 0;

        $powerPvSystArray['powerPvSyst'] += $this->pvSystRepo->sumByDateRange($anlage, $from, $to);
        $powerPvSystArray['powerPvSystYear'] += $this->pvSystRepo->sumByDateRange($anlage, $startYear, $to);
        $powerPvSystArray['powerPvSystPac'] += $this->pvSystRepo->sumByDateRange($anlage, $pacDate, $to);

        return $powerPvSystArray;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $pacDateStart
     * @param $pacDateEnd
     * @param string $select ('all', 'date, 'month', 'year', 'pac')
     * @return array
     * @deprecated
     *
     */
    public function getSumPowerEGridExt(Anlage $anlage, $from, $to, $pacDateStart, $pacDateEnd, string $select = 'all'): array
    {
        $startYear = date('Y-01-01 00:00', strtotime($from));
        $startMonth = date('Y-m-01 00:00', strtotime($from));
        $from = date('Y-m-d', strtotime($from));
        $powerGridEvuArray['powerGridEvu'] = 0;
        $powerGridEvuArray['powerGridEvuMonth'] = 0;
        $powerGridEvuArray['powerGridEvuYear'] = 0;
        $powerGridEvuArray['powerGridEvuPac'] = 0;

        if ($select == 'date' || $select == 'all') {
            $powerGridEvuArray['powerGridEvu'] += $this->gridMeterDayRepo->sumByDate($anlage, $from);
        }
        if ($select == 'month' || $select == 'all') {
            $powerGridEvuArray['powerGridEvuMonth'] += $this->gridMeterDayRepo->sumByDateRange($anlage, $startMonth, $to);
        }
        if ($select == 'year' || $select == 'all') {
            $powerGridEvuArray['powerGridEvuYear'] += $this->gridMeterDayRepo->sumByDateRange($anlage, $startYear, $to);
        }
        if ($select == 'pac' || $select == 'all') {
            $powerGridEvuArray['powerGridEvuPac'] += $this->gridMeterDayRepo->sumByDateRange($anlage, $pacDateStart, $to);
        }

        return $powerGridEvuArray;
    }

    /**
     * @param Anlage $anlage
     * @param WeatherStation $weatherStation
     * @param $from
     * @param $to
     * @param $pacDateStart
     * @param $pacDateEnd
     * @return array
     * @deprecated
     */
    public function getWeather(Anlage $anlage, WeatherStation $weatherStation, $from, $to, $pacDateStart, $pacDateEnd): array
    {
        $conn = self::getPdoConnection($this->host, $this->userPlant, $this->passwordPlant);
        $jahresanfang = date('Y-01-01 00:00', strtotime($from)); // für das ganze Jahr - Zeitraum
        $startMonth = date('Y-m-01 00:00', strtotime($to));
        $weather = [];
        $dbTable = $weatherStation->getDbNameWeather();
        $sql = "SELECT COUNT(db_id) AS anzahl FROM $dbTable WHERE stamp BETWEEN '$from' and '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $weather['anzahl'] = $row['anzahl'];
        }
        unset($res);

        // from - to
        if ($anlage->getHasPPC()){
            $sql = "SELECT sum(g_lower) as irr_lower, sum(g_upper) as irr_upper, sum(g_horizontal) as irr_horizontal, avg(g_horizontal) as irr_horizontal_avg, AVG(at_avg) AS air_temp, AVG(pt_avg) AS panel_temp, AVG(wind_speed) as wind_speed 
                    FROM ".$weatherStation->getDbNameWeather()." w  
                    LEFT JOIN " . $anlage->getDbNamePPC() . " ppc ON w.stamp = ppc.stamp 
                    WHERE w.stamp BETWEEN '$from' AND '$to' AND ppc.p_set_gridop_rel = 100 and ppc.p_set_rpc_rel = 100";
            $res = $conn->query($sql);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $weather['airTempPpc'] = round($row['air_temp'], 2);
                $weather['panelTempPpc'] = round($row['panel_temp'], 2);
                $weather['windSpeedPpc'] = round($row['wind_speed']);
                $weather['horizontalIrrPpc'] = round($row['irr_horizontal']);
                $weather['horizontalIrrAvgPpc'] = round($row['irr_horizontal_avg']);
                if ($weatherStation->getChangeSensor() == 'Yes') {
                    $weather['upperIrrPpc'] = round($row['irr_lower'], 2);
                    $weather['lowerIrrPpc'] = round($row['irr_upper'], 2);
                } else {
                    $weather['upperIrrPpc'] = round($row['irr_upper'], 2);
                    $weather['lowerIrrPpc'] = round($row['irr_lower'], 2);
                }
            }
            unset($res);
        }

        $sql = "SELECT sum(g_lower) as irr_lower, sum(g_upper) as irr_upper, sum(g_horizontal) as irr_horizontal, avg(g_horizontal) as irr_horizontal_avg, AVG(at_avg) AS air_temp, AVG(pt_avg) AS panel_temp, AVG(wind_speed) as wind_speed FROM $dbTable WHERE stamp BETWEEN '$from' and '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $weather['airTemp'] = round($row['air_temp'], 2);
            $weather['panelTemp'] = round($row['panel_temp'], 2);
            $weather['windSpeed'] = round($row['wind_speed']);
            $weather['horizontalIrr'] = round($row['irr_horizontal']);
            $weather['horizontalIrrAvg'] = round($row['irr_horizontal_avg']);
            if ($weatherStation->getChangeSensor() == 'Yes') {
                $weather['upperIrr'] = round($row['irr_lower'], 2);
                $weather['lowerIrr'] = round($row['irr_upper'], 2);
            } else {
                $weather['upperIrr'] = round($row['irr_upper'], 2);
                $weather['lowerIrr'] = round($row['irr_lower'], 2);
            }
        }
        unset($res);

        // Month
        $sql = "SELECT sum(g_lower) as irr_lower, sum(g_upper) as irr_upper, sum(g_horizontal) as irr_horizontal, AVG(at_avg) AS air_temp, AVG(pt_avg) AS panel_temp, AVG(wind_speed) as wind_speed FROM $dbTable where stamp between '$startMonth' and '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $weather['airTempMonth'] = $row['air_temp'];
            $weather['panelTempMonth'] = $row['panel_temp'];
            $weather['windSpeedMonth'] = $row['wind_speed'];
            $weather['horizontalIrrMonth'] = $row['irr_horizontal'];
            if ($weatherStation->getChangeSensor() == 'Yes') {
                $weather['upperIrrMonth'] = $row['irr_lower'];
                $weather['lowerIrrMonth'] = $row['irr_upper'];
            } else {
                $weather['upperIrrMonth'] = $row['irr_upper'];
                $weather['lowerIrrMonth'] = $row['irr_lower'];
            }
        }
        unset($res);
        // PAC
        if ($pacDateStart != null) {
            $sql = "SELECT sum(g_lower) as irr_lower, sum(g_upper) as irr_upper, sum(g_horizontal) as irr_horizontal, AVG(at_avg) AS air_temp, AVG(pt_avg) AS panel_temp, AVG(wind_speed) as wind_speed FROM $dbTable where stamp between '$pacDateStart' and '$to'";
            $res = $conn->query($sql);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $weather['airTempPac'] = round($row['air_temp'], 2);
                $weather['panelTempPac'] = round($row['panel_temp'], 2);
                $weather['windSpeedPac'] = round($row['wind_speed']);
                $weather['horizontalIrrPac'] = round($row['irr_horizontal']);
                if ($weatherStation->getChangeSensor() == 'Yes') {
                    $weather['upperIrrPac'] = round($row['irr_lower'], 2);
                    $weather['lowerIrrPac'] = round($row['irr_upper'], 2);
                } else {
                    $weather['upperIrrPac'] = round($row['irr_upper'], 2);
                    $weather['lowerIrrPac'] = round($row['irr_lower'], 2);
                }
            }
            unset($res);
        } else {
            $weather['lowerIrrPac'] = $weather['horizontalIrrPac'] = $weather['panelTempPac'] = $weather['airTempPac'] = $weather['upperIrrPac'] = 0;
        }

        // Year
        $sql = "SELECT sum(g_lower) as irr_lower, sum(g_upper) as irr_upper, sum(g_horizontal) as irr_horizontal, AVG(at_avg) AS air_temp, AVG(pt_avg) AS panel_temp, AVG(wind_speed) as wind_speed FROM $dbTable where stamp between '$jahresanfang' and '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $weather['airTempYear'] = round($row['air_temp'], 2);
            $weather['panelTempYear'] = round($row['panel_temp'], 2);
            $weather['windSpeedYear'] = round($row['wind_speed']);
            $weather['horizontalIrrYear'] = round($row['irr_horizontal']);
            if ($weatherStation->getChangeSensor() == 'Yes') {
                $weather['upperIrrYear'] = round($row['irr_lower'], 2);
                $weather['lowerIrrYear'] = round($row['irr_upper'], 2);
            } else {
                $weather['upperIrrYear'] = round($row['irr_upper'], 2);
                $weather['lowerIrrYear'] = round($row['irr_lower'], 2);
            }
        }
        unset($res);

        $conn = null;

        return $weather;
    }

    /**
     * @param Anlage $anlage
     * @param WeatherStation $weatherStation
     * @param $from
     * @param $to
     * @return array
     */
    public function getWeatherNew(Anlage $anlage, WeatherStation $weatherStation, $from, $to): array
    {
        $conn = self::getPdoConnection($this->host, $this->userPlant, $this->passwordPlant);
        $weather = [];
        $dbTable = $weatherStation->getDbNameWeather();


        // from -> to | with PPC
        if ($anlage->getHasPPC()){
            $sql = "SELECT COUNT(w.db_id) AS anzahl 
                    FROM ". $weatherStation->getDbNameWeather(). " w  
                    LEFT JOIN " . $anlage->getDbNamePPC() . " ppc ON w.stamp = ppc.stamp 
                    WHERE w.stamp > '$from' AND w.stamp <= '$to' AND (ppc.p_set_gridop_rel = 100 or ppc.p_set_gridop_rel is null) and (ppc.p_set_rpc_rel = 100 or ppc.p_set_rpc_rel is null)";
            $res = $conn->query($sql);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $weather['anzahlPpc'] = $row['anzahl'];
            }
            unset($res);

            $sql = "SELECT sum(g_lower) as irr_lower, sum(g_upper) as irr_upper, sum(g_horizontal) as irr_horizontal, avg(g_horizontal) as irr_horizontal_avg, AVG(at_avg) AS air_temp, AVG(pt_avg) AS panel_temp, AVG(wind_speed) as wind_speed 
                    FROM ".$weatherStation->getDbNameWeather()." w  
                    LEFT JOIN " . $anlage->getDbNamePPC() . " ppc ON w.stamp = ppc.stamp 
                    WHERE w.stamp > '$from' AND w.stamp <= '$to' AND (ppc.p_set_gridop_rel = 100 or ppc.p_set_gridop_rel is null) and (ppc.p_set_rpc_rel = 100 or ppc.p_set_rpc_rel is  null)";
            $res = $conn->query($sql);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $weather['airTempPpc'] = round($row['air_temp'], 4);
                $weather['panelTempPpc'] = round($row['panel_temp'], 4);
                $weather['windSpeedPpc'] = round($row['wind_speed'],4);
                $weather['horizontalIrrPpc'] = round($row['irr_horizontal'],4);
                $weather['horizontalIrrAvgPpc'] = round($row['irr_horizontal_avg'],4);
                if ($weatherStation->getChangeSensor() == 'Yes') {
                    $weather['upperIrrPpc'] = round($row['irr_lower'], 4);
                    $weather['lowerIrrPpc'] = round($row['irr_upper'], 4);
                } else {
                    $weather['upperIrrPpc'] = round($row['irr_upper'], 4);
                    $weather['lowerIrrPpc'] = round($row['irr_lower'], 4);
                }
            }
            unset($res);
        }

        // from -> to | without PPC
        $sql = "SELECT COUNT(db_id) AS anzahl FROM $dbTable WHERE stamp > '$from' AND stamp <= '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $weather['anzahl'] = $row['anzahl'];
        }
        unset($res);

        $sql = "SELECT sum(g_lower) as irr_lower, sum(g_upper) as irr_upper, sum(g_horizontal) as irr_horizontal, avg(g_horizontal) as irr_horizontal_avg, AVG(at_avg) AS air_temp, AVG(pt_avg) AS panel_temp, AVG(wind_speed) as wind_speed 
                FROM $dbTable 
                WHERE stamp > '$from' AND stamp <= '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $weather['airTemp'] = round($row['air_temp'], 4);
            $weather['panelTemp'] = round($row['panel_temp'], 4);
            $weather['windSpeed'] = round($row['wind_speed'],4);
            $weather['horizontalIrr'] = round($row['irr_horizontal'],4);
            $weather['horizontalIrrAvg'] = round($row['irr_horizontal_avg'],4);
            if ($weatherStation->getChangeSensor() == 'Yes') {
                $weather['upperIrr'] = round($row['irr_lower'], 4);
                $weather['lowerIrr'] = round($row['irr_upper'], 4);
            } else {
                $weather['upperIrr'] = round($row['irr_upper'], 4);
                $weather['lowerIrr'] = round($row['irr_lower'], 4);
            }
        }
        unset($res);
        $conn = null;

        return $weather;
    }

    /**
     * @param $array
     * @param int $umrechnung = Faktor zum Umrechenen auf Wh (4) oder kWh (4000) etc
     */
    public function buildSumFromArray($array, int $umrechnung = 1): array
    {
        $sumArray = [];
        if (is_array($array)) {
            foreach ($array as $stamp => $irr) {
                if (is_array($irr) || is_object($irr)) {
                    foreach ($irr as $key => $value) {
                        if (isset($sumArray[$key])) {
                            $sumArray[$key] += round((float) $value / $umrechnung, 3);
                        } else {
                            $sumArray[$key] = round((float) $value / $umrechnung, 3);
                        }
                    }
                }
            }
        }

        return $sumArray;
    }

    /**
     * @param $array
     */
    public function buildAvgFromArray($array, int $umrechnung = 1): array
    {
        $sumArray = [];
        $counter = 0;

        if (is_array($array)) {
            foreach ($array as $irr) {
                if (is_array($irr)) {
                    ++$counter;
                    foreach ($irr as $key => $value) {
                        if (is_float($value) || is_integer($value)) {
                            if (isset($sumArray[$key])) {
                                $sumArray[$key] += $value;
                            } else {
                                $sumArray[$key] = $value;
                            }
                        }
                    }
                }
            }
        }
        foreach ($sumArray as $key => $value) {
            $sumArray[$key] = $value / $counter / $umrechnung;
        }

        return $sumArray;
    }

    /**
     * @param float $irrUpper
     * @param float $irrLower
     * @param $date
     * @param Anlage $anlage
     * @param AnlageGroups $group
     * @param WeatherStation $weatherStation
     * @param AnlageGroupMonths|null $groupMonth
     * @return float
     * @deprecated
     * no Function to replace at the moment
     * todo : check how we can replace
     */
    public function calcIrr(float $irrUpper, float $irrLower, $date, Anlage $anlage, AnlageGroups $group, WeatherStation $weatherStation, ?AnlageGroupMonths $groupMonth): float
    {
        $gewichtetStrahlung = 0;
        $month = date('m', strtotime($date));

        if ($irrUpper < 0) {
            $irrUpper = 0;
        }
        if ($irrLower < 0) {
            $irrLower = 0;
        }
        // Sensoren sind vertauscht, Werte tauschen
        if ($weatherStation->getChangeSensor()) {
            $irrHelp = $irrLower;
            $irrLower = $irrUpper;
            $irrUpper = $irrHelp;
        }

        // Rückfallwert sollte nichts anderes gefunden werden
        $gwoben = 0.5;
        $gwunten = 0.5;

        // Werte aus Gruppe, wenn gesetzt
        if ($group->getIrrUpper()) {
            $gwoben = $group->getIrrUpper();
        }
        if ($group->getIrrLower()) {
            $gwunten = $group->getIrrLower();
        }

        // Werte aus Monat, wenn gesetzt
        if ($groupMonth) {
            if ($groupMonth->getIrrUpper()) {
                $gwoben = $groupMonth->getIrrUpper();
            }
            if ($groupMonth->getIrrLower()) {
                $gwunten = $groupMonth->getIrrLower();
            }
        }
        if ($weatherStation->getHasUpper() && $weatherStation->getHasLower()) {
            // Station hat oberen und unteren Sensor => Strahlung wird MIT Gewichtung zurückgegeben, es können trotzdem noch Verluste über die Verschattung berechnet werden
            $gewichtetStrahlung = $irrUpper * $gwoben + $irrLower * $gwunten;
        } elseif ($weatherStation->getHasUpper() && !$weatherStation->getHasLower()) {
            // Station hat nur oberen Sensor => Die Strahlung OHNE Gewichtung zurückgeben, Verluste werden dann über die Verschattung berechnet
            $gewichtetStrahlung = $irrUpper;
        } elseif (!$weatherStation->getHasUpper() && $weatherStation->getHasLower()) {
            // Station hat nur unteren Sensor => Die Strahlung OHNE Gewichtung zurückgeben, Verluste werden dann über die Verschattung berechnet
            $gewichtetStrahlung = $irrLower;
        }

        return $gewichtetStrahlung;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param bool $day
     * @return float
     */
    public function getSumeGridMeter(Anlage $anlage, $from, $to, bool $day = false): float
    {
        if ($anlage->getUseGridMeterDayData()) {
            // Berechnung der externen Zählerwerte unter Berücksichtigung der Manuel eingetragenen Monatswerte.
            // Darüber kann eine Koorektur der Zählerwerte erfolgen.
            // Wenn für einen Monat Manuel Zählerwerte eingegeben wurden, wird der Wert der Tageszählwer wieder subtrahiert und der Manuel eingebene Wert addiert.
            $powerEGridExt = $this->gridMeterDayRepo->sumByDateRange($anlage, $from, $to);

            if (!$powerEGridExt) $powerEGridExt = 0;

            // prüfe ob $from Datum und das $to datum weniger al einen Monat auseinaderliegen
            // wenn das so ist darf die korrektur nicht ausgeführt werden
            if (!$day) $day = strtotime($to) - strtotime($from) <= (3600 *25); // mal 25 um Schaltung auf Winterzeit zu berücksichtigen

            // wenn Tageswerte angefordert, dann nicht mit Monatswerten verrechnen, wenn keine Tageswerte vorhanden sind, wird 0 zurückgegeben.
            if (!$day) {
                $year = (int) date('Y', strtotime($from));
                $month = (int) date('m', strtotime($from));
                $monthes = self::g4nDateDiffMonth($from, $to);
                for ($n = 1; $n <= $monthes; ++$n) {
                    $monthlyData = $this->monthlyDataRepo->findOneBy(['anlage' => $anlage, 'year' => $year, 'month' => $month]);// calculate the first and the last day of the given month and year in $monthlyData
                    if ($monthlyData != null && $monthlyData->getExternMeterDataMonth() > 0) {
                        $currentDate = strtotime($year.'-'.$month.'-01');
                        $lastDayMonth = date('t', $currentDate);
                        $currentMonthFrom = date('Y-m-01 00:00', $currentDate);
                        $currentMonthTo = date('Y-m-'.$lastDayMonth.' 23:59', $currentDate);
                        $powerEGridExt -= $this->gridMeterDayRepo->sumByDateRange($anlage, $currentMonthFrom, $currentMonthTo); // subtrahiere die Summe der Tageswerte für diesen Monat
                        $powerEGridExt += $monthlyData->getExternMeterDataMonth(); // addiere den manuel eingetragenen Monatswert
                    }
                    ++$month;
                    if ($month > 12) {
                        $month = 1;
                        ++$year;
                    }
                }
            }
        } else {
            $powerEGridExt = 0;
        }

        return $powerEGridExt;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     *
     * @return array
     */
    public function getSumAcPower(Anlage $anlage, $from, $to): array
    {
        $conn = self::getPdoConnection($this->host, $this->userPlant, $this->passwordPlant);
        $result = [];
        $powerEvu = $powerExp = $powerExpEvu = $powerTheo = $tCellAvg = $tCellAvgMultiIrr = 0;
        // ############ für den angeforderten Zeitraum #############

        // Wenn externe Tagesdaten genutzt werden, sollen lade diese aus der DB und ÜBERSCHREIBE die Daten aus den 15Minuten Werten
        $powerEGridExt = $this->getSumeGridMeter($anlage, $from, $to);

        // EVU Leistung ermitteln –
        // dieser Wert kann der offiziele Grid Zähler wert sein, kann aber auch nur ein interner Wert sein. Siehe Konfiguration $anlage->getUseGridMeterDayData()
        if ($anlage->isIgnoreNegativEvu()) {
            $sql = 'SELECT sum(e_z_evu) as power_evu FROM '.$anlage->getDbNameAcIst()." WHERE stamp >= '$from' AND stamp <= '$to' AND e_z_evu > 0 GROUP BY unit LIMIT 1";
        } else {
            $sql = 'SELECT sum(e_z_evu) as power_evu FROM '.$anlage->getDbNameAcIst()." WHERE stamp >= '$from' AND stamp <= '$to' GROUP BY unit LIMIT 1";
        }
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerEvu = $row['power_evu'];
        }
        unset($res);

        $powerEvu = $this->checkAndIncludeMonthlyCorrectionEVU($anlage, $powerEvu, $from, $to);

        // Expected Leistung ermitteln
        $sql = 'SELECT SUM(ac_exp_power) AS sum_power_ac, SUM(ac_exp_power_evu) AS sum_power_ac_evu FROM '.$anlage->getDbNameDcSoll()." WHERE stamp >= '$from' AND stamp <= '$to'";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerExp = $row['sum_power_ac'];
            $powerExpEvu = $row['sum_power_ac_evu'];
        }
        unset($res);

        // Theoretic Power (TempCorr)
        $sql = 'SELECT SUM(theo_power) AS theo_power FROM '.$anlage->getDbNameAcIst()." WHERE stamp >= '$from' AND stamp <= '$to' AND theo_power > 0";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerTheo = $row['theo_power'];
        }
        unset($res);

        // Actual (Inverter Out) Leistung ermitteln
        $sql = 'SELECT sum(wr_pac) as sum_power_ac, sum(theo_power) as theo_power FROM '.$anlage->getDbNameAcIst()." WHERE stamp >= '$from' AND stamp <= '$to' AND wr_pac > 0";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $result['powerEvu']         = (float)$powerEvu;
            $result['powerAct']         = (float)$row['sum_power_ac'];
            $result['powerExp']         = (float)$powerExp;
            $result['powerExpEvu']      = (float)$powerExpEvu;
            $result['powerEGridExt']    = (float)$powerEGridExt;
            $result['powerTheo']        = (float)$powerTheo;
            $result['tCellAvg']         = (float)$tCellAvg;
            $result['tCellAvgMultiIrr'] = (float)$tCellAvgMultiIrr;
        }
        unset($res);

        return $result;
    }

    public function checkAndIncludeMonthlyCorrectionEVU(Anlage $anlage, ?float $evu, $from, $to): ?float
    {
        $conn = self::getPdoConnection($this->host, $this->userPlant, $this->passwordPlant);

        $fromObj = date_create($from);
        $toObj = date_create($to);
        if ($evu) {
            if ($anlage->getUseGridMeterDayData() === false) {
                $monthlyDatas = $this->monthlyDataRepo->findByDateRange($anlage, $fromObj, $toObj);
                $countMonthes = count($monthlyDatas);

                foreach ($monthlyDatas as $monthlyData) {
                    // calculate the first and the last day of the given month and year in $monthlyData
                    $firstDayMonth = date_create($monthlyData->getYear() . "-". $monthlyData->getMonth()."-01");
                    $lastDayMonth  = date_create($monthlyData->getYear() . "-". $monthlyData->getMonth()."-".$firstDayMonth->format("t"));

                    // check if the time period is the hole month. Only if we get 1 whole Month we can use this correction
                    // or if we get the starting or ending Month from an epc Report ($epcStartEndMonth == true)

                    $epcStartMonth = $anlage->getEpcReportStart()->format('Ym') ===  $firstDayMonth->format('Ym');
                    $epcEndMonth   = $anlage->getEpcReportEnd()->format('Ym') ===  $firstDayMonth->format('Ym');
                    $wholeMonth = ($toObj->getTimestamp() - $fromObj->getTimestamp()) / 86400 >= 28; // looks like this is not only one Day
                    $wholeReport = $anlage->getEpcReportStart()->format('Ymd') === $fromObj->format('Ymd') && $anlage->getEpcReportEnd()->format('Ymd') === $toObj->format('Ymd');

                    if (($firstDayMonth->format("Y-m-d 00:00") === $from && $lastDayMonth->format("Y-m-d 23:59") === $to) || $epcStartMonth || $epcEndMonth || $wholeReport || $wholeMonth) {
                        if ($monthlyData->getExternMeterDataMonth() && $monthlyData->getExternMeterDataMonth() > 0) {
                            if ($epcStartMonth) {
                                $tempFrom = new DateTime($monthlyData->getYear() . '-' . $monthlyData->getMonth() . '-' . $anlage->getFacDateStart()->format('d') . ' 00:00');
                            } else {
                                $tempFrom = new DateTime($monthlyData->getYear() . '-' . $monthlyData->getMonth() . '-01 00:00');
                            }
                            $tempDaysInMonth = $tempFrom->format('t');
                            if ($epcEndMonth) {
                                $tempTo = new DateTime($monthlyData->getYear() . '-' . $monthlyData->getMonth() . '-' . $anlage->getFacDate()->format('d') . ' 23:59');
                            } else {
                                $tempTo = new DateTime($monthlyData->getYear() . '-' . $monthlyData->getMonth() . '-' . $tempDaysInMonth . ' 23:59');
                            }
                            if ($anlage->isIgnoreNegativEvu()) {
                                $sql = 'SELECT sum(e_z_evu) as power_evu FROM ' . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '" . $tempFrom->format('Y-m-d H:i') . "' AND '" . $tempTo->format('Y-m-d H:i') . "' AND e_z_evu > 0 GROUP BY unit LIMIT 1";
                            } else {
                                $sql = 'SELECT sum(e_z_evu) as power_evu FROM ' . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '" . $tempFrom->format('Y-m-d H:i') . "' AND '" . $tempTo->format('Y-m-d H:i') . "' GROUP BY unit LIMIT 1";
                            }

                            $res = $conn->query($sql);
                            if ($res->rowCount() == 1) {
                                $row = $res->fetch(PDO::FETCH_ASSOC);
                                $evu -= $row['power_evu'];
                                $evu += $monthlyData->getExternMeterDataMonth();
                            }
                            unset($res);
                        }
                    }
                }
            }
        }

        return $evu;
    }

    /**
     * @throws \Exception
     */
    public function checkAndIncludeMonthlyCorrectionIrr(Anlage $anlage, float $irr, $from, $to): float
    {
        $monthlyDatas = $this->monthlyDataRepo->findByDateRange($anlage, date_create($from), date_create($to));

        foreach ($monthlyDatas as $monthlyData) {
            // calculate the first and the last day of the given month and year in $monthlyData
            $firstDayMonth = date_create($monthlyData->getYear() . "-". $monthlyData->getMonth()."-01");
            $lastDayMonth  = date_create($monthlyData->getYear() . "-". $monthlyData->getMonth()."-".$firstDayMonth->format("t"));

            // check if the time period is the hole month. Only if we get 1 whole Month we can use this correction
            // or if we get the starting or ending Month from an epc Report ($epcStartEndMonth == true)
            $fromObj = date_create($from);
            $toObj = date_create($to);
            $epcStartMonth = $anlage->getFacDateStart()->format('Ym') ===  $fromObj->format('Ym');
            $epcEndMonth   = $anlage->getFacDate()->format('Ym') ===  $toObj->format('Ym') ;
            if (($firstDayMonth->format("Y-m-d 00:00") === $from && $lastDayMonth->format("Y-m-d 23:59") === $to) || $epcStartMonth || $epcEndMonth) {
                if ($monthlyData->getIrrCorrectedValuMonth() && $monthlyData->getIrrCorrectedValuMonth() > 0) {
                    if ($epcStartMonth) {
                        $tempFrom = $anlage->getFacDateStart();
                    } else {
                        $tempFrom = new DateTime($monthlyData->getYear() . '-' . $monthlyData->getMonth() . '-01 00:00');
                    }
                    $tempDaysInMonth = $tempFrom->format('t');
                    if ($epcEndMonth) {
                        $tempTo = date_create($anlage->getFacDate()->format('Y-m-d 23:59'));
                    } else {
                        $tempTo = new DateTime($monthlyData->getYear() . '-' . $monthlyData->getMonth() . '-' . $tempDaysInMonth . ' 23:59');
                    }

                    $weather = $this->weatherFunctions->getWeather($anlage->getWeatherStation(), $tempFrom->format("Y-m-d H:i"), $tempTo->format("Y-m-d H:i"), false, $anlage);
                    if ($anlage->getIsOstWestAnlage()) {
                        $irrTemp = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
                    } else {
                        $irrTemp = $weather['upperIrr'] / 4 / 1000; // Umrechnug zu kWh
                    }

                    if ($irrTemp > 0) {
                        $irr -= $irrTemp;
                        $irr += $monthlyData->getIrrCorrectedValuMonth();
                    }
                }
            }
        }


        return $irr;
    }


    /**
     * Funktion um in Abhänigkeit von 'configType'  die entsprechenden Namen für die bezeichnung der SCB, Gruppen und Inverter zu generieren.
     */
    public function getNameArray(Anlage $anlage, string $type = 'ac', bool $startArrayAtOne = true): array
    {
        $nameArray['ac'] = [];
        $nameArray['dc'] = [];
        $nameArray['scb'] = [];
        switch ($anlage->getConfigType()) {
            case 1:
                // In diesem Fall gibt es keine SCBs
                foreach ($this->groupsRepo->findBy(['anlage' => $anlage->getAnlId()]) as $inverter) {
                    $nameArray['ac'][$inverter->getDcGroup()] = $inverter->getDcGroupName();
                    $nameArray['dc'][$inverter->getDcGroup()] = $inverter->getDcGroupName();
                }
                break;
            case 2:
                // In diesem Fall gibt es keine SCBs
                foreach ($this->acGroupsRepo->findBy(['anlage' => $anlage->getAnlId()]) as $inverter) {
                    $nameArray['ac'][$inverter->getAcGroup()] = $inverter->getAcGroupName();
                    $nameArray['dc'][$inverter->getAcGroup()] = $inverter->getAcGroupName();
                }
                break;
            case 3: // Groningen
            case 4:
                foreach ($this->acGroupsRepo->findBy(['anlage' => $anlage->getAnlId()]) as $inverter) {
                    $nameArray['ac'][$inverter->getAcGroup()] = $inverter->getAcGroupName();
                }
                foreach ($this->groupsRepo->findBy(['anlage' => $anlage->getAnlId()]) as $inverter) {
                    $nameArray['dc'][$inverter->getDcGroup()] = $inverter->getDcGroupName();
                }
                foreach ($this->inverterRepo->findBy(['anlage' => $anlage->getAnlId()]) as $inverter) {
                    $nameArray['scb'][$inverter->getInvNr()] = $inverter->getInverterName();
                }
                break;
        }
        // we do this to make the array start by 0 when required
        if (!$startArrayAtOne) {
            array_unshift($nameArray[$type], '1');
            array_shift($nameArray[$type]);
        }

        return $nameArray[$type];
    }

    public function print2DArrayAsTable(array $content, int $decimal = 2): string
    {
        $_html = '<style>
                    table, th, td {border: 1px solid black; }
                    thead {background-color: #b4c6e7;}  
                  </style>';
        $_html .= "<div class='table-scroll'><table style='font-size: 80%'>";
        $_counter = 0;
        for ($key = 1; $key <= count($content); ++$key) {
            if ($_counter == 0) {
                $_html .= '<thead><tr><th>Key</th>';
                foreach ($content[$key] as $subkey => $subvalue) {
                    $_html .= '<th>'.substr($subkey, 0, 30).'</th>';
                }
                $_html .= '</tr></thead>';
            }
            $_html .= "<tbody><tr><td>$key</td>";
            foreach ($content[$key] as $cell) {
                if (is_numeric($cell)) {
                    $_html .= "<td style='text-align: right;'>".number_format($cell, $decimal, '.', ',').'</td>';
                } else {
                    $_html .= "<td>$cell</td>";
                }
            }
            $_html .= '</tr>';
            ++$_counter;
        }
        $_html .= '</tbody></table></div><hr>';

        return $_html;
    }

    public function printArrayAsTable(array $content, int $decimal = 2): string
    {
        $_html = '<style>
                    table, th, td {border: 1px solid black; }
                    thead {background-color: #b4c6e7;}  
                  </style>';
        $_html .= "<div class='table-scroll'><table style='font-size: 80%'>";
        foreach ($content as $key => $cell) {
            $_html .= "<tr><td>$key</td>";
            if (is_numeric($cell)) {
                $_html .= "<td style='text-align: right;'>".number_format($cell, $decimal, ',', '.').'</td>';
            } else {
                $_html .= "<td>$cell</td>";
            }
        }
        $_html .= '</tr>';
        $_html .= '</table></div><hr>';

        return $_html;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function readInverters(string $invS, Anlage $anlage): array
    {
        return $this->cache->get('readInverters_'.md5($invS.$anlage->getAnlId()), function(CacheItemInterface $cacheItem) use ($invS, $anlage)
        {
            $cacheItem->expiresAfter(900); // Lifetime of cache Item

            $returnArray = [];
            $maxInv = count($this->getNameArray($anlage));
            $invS = u($invS)->replace(' ', '');
            $tempArray = u($invS)->split(',');

            if ($invS != '*') {

                foreach ($tempArray as $item) {
                    if (u($item)->containsAny('-')) {
                        $nums[] = u($item)->split('-');
                        $from = (int) ((string) $nums[0][0]);
                        $to = (int) ((string) $nums[0][1]);
                        $i = $from;
                        while ($i <= $to) {
                            $returnArray[] = (string) u($i);
                            ++$i;
                        }
                        unset($nums);
                    } else {
                        $returnArray[] = (string) $item;
                    }
                }
            } else {
                $i = 1;
                while ($i <= $maxInv) {
                    $returnArray[] = (string) $i;
                    ++$i;
                }
            }

            return $returnArray;
        });
    }
}
