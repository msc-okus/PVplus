<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Repository\ForcastRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\GroupModulesRepository;
use App\Repository\GroupMonthsRepository;
use App\Repository\GroupsRepository;
use App\Repository\MonthlyDataRepository;
use App\Repository\PVSystDatenRepository;
use App\Repository\ReplaceValuesTicketRepository;
use App\Repository\TicketDateRepository;
use App\Repository\TicketRepository;
use DateTime;
use PDO;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

class WeatherFunctionsService
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly PVSystDatenRepository   $pvSystRepo,
        private readonly GroupMonthsRepository   $groupMonthsRepo,
        private readonly GroupModulesRepository  $groupModulesRepo,
        private readonly GroupsRepository        $groupsRepo,
        private readonly GridMeterDayRepository  $gridMeterDayRepo,
        private readonly TicketRepository        $ticketRepo,
        private readonly TicketDateRepository    $ticketDateRepo,
        private readonly ReplaceValuesTicketRepository $replaceValuesTicketRepo,
        private readonly CacheInterface $cache,
        private readonly MonthlyDataRepository $monthlyDataRepo,)
    {
    }

    /**
     * Function to retrieve WeatherData for the given Time (from - to)<br>
     * $from and $to are in string format.
     *
     * $weather['airTempAvg']<br>
     * $weather['panelTempAvg']<br>
     * $weather['windSpeedAvg']<br>
     * $weather['horizontalIrr']<br>
     * $weather['horizontalIrrAvg']<br>
     * $weather['upperIrr']<br>
     * $weather['lowerIrr']<br>
     * $weather['Irr0']<br>
     * $weather['Irr1']<br>
     * $weather['Irr2']<br>
     * $weather['Irr3']<br>
     * $weather['IrrEast0']<br>
     * $weather['IrrEast1']<br>
     * $weather['IrrEast2']<br>
     * $weather['IrrEast3']<br>
     * $weather['IrrWest0']<br>
     * $weather['IrrWest1']<br>
     * $weather['IrrWest2']<br>
     * $weather['IrrWest3']<br>
     * $weather['IrrHor0']<br>
     * $weather['IrrHor1']<br>
     * $weather['IrrHor2']<br>
     * $weather['IrrHor3']<br>
     * $weather['temp_cell_corr']<br>
     * $weather['temp_cell_multi_irr']<br>
     * $weather['theoPower'] Theoretical Energie RAW (Pnom * Irr)<br>
     * $weather['theoPowerDeg'] Theoretical Energie RAW (Pnom * Irr)<br>
     * $weather['theoPowerPA0'] Theoretical Energie for OpenBook<br>
     * $weather['theoPowerPA1'] Theoretical Energie for Dep 1 (O&M)<br>
     * $weather['theoPowerPA2'] Theoretical Energie for Dep 2 (EPC)<br>
     * $weather['theoPowerPA3'] Theoretical Energie for Dep 3 (AM)<br>
     * $weather['theoPowerTempCorr'] Theoretical Energie Tempertatur koriegiert<br>
     * $weather['theoPowerTempCorr'] Theoretical Energie Tempertatur koriegiert + degradation<br>
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
        return $this->cache->get('getWeather_'.md5($weatherStation->getId().$from.$to.$ppc.$anlage->getAnlId().$inverterID), function(CacheItemInterface $cacheItem) use ($weatherStation, $from, $to, $ppc, $anlage, $inverterID) {
            $cacheItem->expiresAfter(30);
            $conn = $this->pdoService->getPdoPlant();
            $weather = [];
            $dbTable = $weatherStation->getDbNameWeather();
            $sql = "SELECT COUNT(db_id) AS anzahl FROM $dbTable WHERE stamp > '$from' and stamp <= '$to'";
            $res = $conn->query($sql);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $weather['anzahl'] = $row['anzahl'];
            }
            unset($res);

            if ($ppc && $anlage->getUsePPC()) {
                $sqlPPCpart1 = " LEFT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp ";
                $sqlPPCpart2 = " AND (ppc.p_set_gridop_rel = 100 OR ppc.p_set_gridop_rel is null) 
                        AND (ppc.p_set_rpc_rel = 100 OR ppc.p_set_rpc_rel is null) ";
            } else {
                $sqlPPCpart1 = $sqlPPCpart2 = "";
            }
            if ($inverterID === null) {
                $pNom = $anlage->getPnom();
            } else {
                $inverterPowerDc = $anlage->getPnomInverterArray();
                $pNom = $inverterPowerDc[$inverterID];
            }

            $pNomEast = $anlage->getPowerEast();
            $pNomWest = $anlage->getPowerWest();

            // Temperatur Korrektur Daten vorbereiten
            $tModAvg = $anlage->getTempCorrCellTypeAvg() > 0 ? $anlage->getTempCorrCellTypeAvg() : 25;
            // ??? $this->determineTModAvg($anlage, $from, $to);
            $gamma = $anlage->getTempCorrGamma();
            $tempCorrFunctionIEC    = "(1 - ( (($tModAvg - temp_pannel) * ($gamma)) / 100))";

            if($anlage->getAnlBetrieb()) {
                $degradation = "POW(1-" . ($anlage->getDegradationPR() / 100) . ", CEIL((TO_DAYS(s.stamp)-TO_DAYS('" . $anlage->getAnlBetrieb()->format('Y-m-d') . "'))/365))";
            } else {
                $degradation = "1";
            }

            // depending on $department generate correct SQL code to calculate
            if ($anlage->getIsOstWestAnlage()) {
                if ($inverterID === null) {
                    // gewichtung nach Anlagen Gewichtung
                    $sqlTheoPowerPart = "
                        SUM(g_upper * $pNomEast + g_lower * $pNomWest)  as theo_power_raw,
                        SUM(g_upper * $pNomEast * $degradation + g_lower * $pNomWest * $degradation)  as theo_power_raw_deg,
                        SUM(g_upper * $tempCorrFunctionIEC * $pNomEast + g_lower * $tempCorrFunctionIEC * $pNomWest) as theo_power_temp_corr_iec,
                        SUM(g_upper * $tempCorrFunctionIEC * $pNomEast + g_lower * $tempCorrFunctionIEC * $pNomWest * $degradation) as theo_power_temp_corr_deg_iec,
                        SUM(g_upper * $pNomEast * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA3() . ", pa3, 1)) + 
                        SUM(g_lower * $pNomWest * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA3() . ", pa3, 1)) as theo_power_pa3,
                        SUM(g_upper * $pNomEast * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA2() . ", pa2, 1)) + 
                        SUM(g_lower * $pNomWest * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA2() . ", pa2, 1)) as theo_power_pa2,
                        SUM(g_upper * $pNomEast * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA1() . ", pa1, 1)) + 
                        SUM(g_lower * $pNomWest * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA1() . ", pa1, 1)) as theo_power_pa1,
                        SUM(g_upper * $pNomEast * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA0() . ", pa0, 1)) + 
                        SUM(g_lower * $pNomWest * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA0() . ", pa0, 1)) as theo_power_pa0,
                    ";
                } else {
                    // Gewichtung 50/50 wenn Inverter abgefragt
                    $sqlTheoPowerPart = "
                        SUM(((g_upper + g_lower) / 2) * $pNom)  as theo_power_raw,
                        SUM(((g_upper + g_lower) / 2) * $pNom * $degradation)  as theo_power_raw_deg,
                        SUM(((g_upper + g_lower) / 2) * $tempCorrFunctionIEC * $pNom) as theo_power_temp_corr_iec,
                        SUM(((g_upper + g_lower) / 2) * $tempCorrFunctionIEC * $pNom * $degradation) as theo_power_temp_corr_deg_iec,
                        SUM(((g_upper + g_lower) / 2) * $pNom * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA3() . ", pa3, 1)) as theo_power_pa3,
                        SUM(((g_upper + g_lower) / 2) * $pNom * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA2() . ", pa2, 1)) as theo_power_pa2,
                        SUM(((g_upper + g_lower) / 2) * $pNom * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA1() . ", pa1, 1)) as theo_power_pa1,
                        SUM(((g_upper + g_lower) / 2) * $pNom * IF(((g_upper + g_lower) / 2) > " . $anlage->getThreshold2PA0() . ", pa0, 1)) as theo_power_pa0,
                    ";
                }

            } else {
                $sqlTheoPowerPart = "
                SUM(g_upper * $pNom)  as theo_power_raw,
                SUM(g_upper * $pNom * $degradation)  as theo_power_raw_deg,
                SUM(g_upper * $tempCorrFunctionIEC * $pNom ) as theo_power_temp_corr_iec,
                SUM(g_upper * $tempCorrFunctionIEC * $pNom * $degradation) as theo_power_temp_corr_deg_iec,
                SUM(g_upper * $pNom * IF(g_upper > " . $anlage->getThreshold2PA3() . ", pa3, 1)) as theo_power_pa3,
                SUM(g_upper * $pNom * IF(g_upper > " . $anlage->getThreshold2PA2() . ", pa2, 1)) as theo_power_pa2,
                SUM(g_upper * $pNom * IF(g_upper > " . $anlage->getThreshold2PA1() . ", pa1, 1)) as theo_power_pa1,
                SUM(g_upper * $pNom * IF(g_upper > " . $anlage->getThreshold2PA0() . ", pa0, 1)) as theo_power_pa0,
            ";
            }
            if ($weather['anzahl'] > 0) {
                $sql = "SELECT 
                    SUM(IF(g_lower>0,g_lower,0)) as irr_lower, 
                    SUM(IF(g_upper>0,g_upper,0)) as irr_upper, 
                    SUM(IF(g_horizontal>0,g_horizontal,0)) as irr_horizontal, 
                    $sqlTheoPowerPart
                    AVG(temp_ambient) AS ambient_temp, 
                    AVG(temp_pannel) AS panel_temp, 
                    AVG(wind_speed) as wind_speed ,
                    SUM(temp_cell_corr) as temp_cell_corr,
                    SUM(temp_cell_multi_irr) as temp_cell_multi_irr
                    FROM $dbTable s
                        $sqlPPCpart1
                    WHERE s.stamp > '$from' AND s.stamp <= '$to'
                        $sqlPPCpart2;
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
                    if ($anlage->getIsOstWestAnlage()) {
                        $weather['irr0'] = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
                        $weather['irr1'] = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
                        $weather['irr2'] = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
                        $weather['irr3'] = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
                    } else {
                        $weather['irr0'] = $weather['upperIrr'];
                        $weather['irr1'] = $weather['upperIrr'];
                        $weather['irr2'] = $weather['upperIrr'];
                        $weather['irr3'] = $weather['upperIrr'];
                    }

                    $weather['irrEast0'] = $weather['upperIrr'];
                    $weather['irrEast1'] = $weather['upperIrr'];
                    $weather['irrEast2'] = $weather['upperIrr'];
                    $weather['irrEast3'] = $weather['upperIrr'];
                    $weather['irrWest0'] = $weather['lowerIrr'];
                    $weather['irrWest1'] = $weather['lowerIrr'];
                    $weather['irrWest2'] = $weather['lowerIrr'];
                    $weather['irrWest3'] = $weather['lowerIrr'];
                    $weather['irrHor0'] = $weather['horizontalIrr'];
                    $weather['irrHor1'] = $weather['horizontalIrr'];
                    $weather['irrHor2'] = $weather['horizontalIrr'];
                    $weather['irrHor3'] = $weather['horizontalIrr'];
                    $weather['temp_cell_corr'] = $row['temp_cell_corr'];
                    $weather['temp_cell_multi_irr'] = $row['temp_cell_multi_irr'];
                    $weather['theoPowerPA0'] = $row['theo_power_pa0'] / 1000 / 4;
                    $weather['theoPowerPA1'] = $row['theo_power_pa1'] / 1000 / 4;
                    $weather['theoPowerPA2'] = $row['theo_power_pa2'] / 1000 / 4;
                    $weather['theoPowerPA3'] = $row['theo_power_pa3'] / 1000 / 4;
                    $weather['theoPower'] = $row['theo_power_raw'] / 1000 / 4;
                    $weather['theoPowerDeg'] = $row['theo_power_raw_deg'] / 1000 / 4;
                    $weather['theoPowerTempCorr_NREL'] = $row['theo_power_temp_corr_iec'] / 1000 / 4;
                    $weather['theoPowerTempCorr_IEC'] = $row['theo_power_temp_corr_iec'] / 1000 / 4;
                    $weather['theoPowerTempCorDeg_IEC'] = $row['theo_power_temp_corr_deg_iec'] / 1000 / 4;
                }
                unset($res);
            } else {
                $weather = null;
            }
            $conn = null;

            return $weather;
        });
    }

    public function getIntervallPA(Anlage $anlage, DateTime $from, DateTime $to): array
    {
        $result = [];
        $conn = $this->pdoService->getPdoPlant();
        $dbTable = $anlage->getWeatherStation()->getDbNameWeather();
        $sql = "SELECT stamp, pa0, pa1, pa2, pa3 FROM $dbTable WHERE stamp > '".$from->format('Y-m-d H:i')."' and stamp <= '".$to->format('Y-m-d H:i')."'";
        $res = $conn->query($sql);
        if ($res->rowCount() > 0) {
            $rows = $res->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $result[$row['stamp']] = [
                    'pa0'   => (float)$row['pa0'],
                    'pa1'   => (float)$row['pa1'],
                    'pa2'   => (float)$row['pa2'],
                    'pa3'   => (float)$row['pa3'],
                ];
            }
        }

        return $result;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function determineTModAvg(Anlage $anlage, string|DateTime $from, string|DateTime $to): float
    {
        if (is_string($from)) $from = date_create($from);
        if (is_string($to)) $from = date_create($to);

        $startMonth = 1;
        $endMonth = 1;
        $startYear = 2023;
        $endYear = 2023;

        return $this->cache->get('determineTModAvg'.md5($anlage->getAnlId().$startMonth.$endMonth.$startYear.$endYear), function(CacheItemInterface $cacheItem) use ($anlage, $startMonth, $endMonth, $startYear, $endYear) {
            $cacheItem->expiresAfter(60);

            // default value
            $tModAvg = $anlage->getTempCorrCellTypeAvg() > 0 ? $anlage->getTempCorrCellTypeAvg() : 25; // Nutze tCellAVG wenn vorhanden, ansonsten setze auf STC (25°)

            if ($startMonth === $endMonth && $startYear === $endYear) {
                # Suche nach nachgerechneten monatswert für t_mod_avg, wenn gefunden nutze diesen
                $monthlyRecalculatedData = $this->monthlyDataRepo->findOneBy(['anlage' => $anlage, 'year' => $startYear, 'month' => $startMonth]);
                if ($monthlyRecalculatedData !== null && $monthlyRecalculatedData->getTModAvg() > 0) {
                    $tModAvg = $monthlyRecalculatedData->getTModAvg();
                } else {
                    # wenn nicht suche nach monats planwert für t_mod_avg, wenn gefunden nutze diesen
                    $pvSystData = $anlage->getPvSystMonthsArray();
                    if ($pvSystData !== null && $pvSystData[$startMonth]['tempAmbWeightedDesign'] > 0) {
                        $tModAvg = $pvSystData[$startMonth]['tempAmbWeightedDesign'];
                    }
                }
            } else {
                # handling wenn Zeiträume größer einem Monat abgefragt werden

            }
            #in allen anderen Fällen nutze 25 (STC bedingung)

            return $tModAvg;
        });

    }

    /**
     * Function to retrieve weighted irradiation
     * definition is optimized for ticket generation, have a look into ducumentation
     *
     * @param Anlage $anlage
     * @param DateTime $stamp
     * @return float|null
     */
    public function getIrrByStampForTicket(Anlage $anlage, DateTime $stamp): ?float
    {

        $conn = $this->pdoService->getPdoPlant();
        $irr = null;
        $sqlw = 'SELECT g_lower, g_upper FROM ' . $anlage->getDbNameWeather() . " WHERE stamp = '" . $stamp->format('Y-m-d H:i') . "' ";
        $respirr = $conn->query($sqlw);

        if ($respirr->rowCount() > 0) {
            $pdataw = $respirr->fetch(PDO::FETCH_ASSOC);
            $irrUpper =  $pdataw['g_upper'] !== ''  ? (float)$pdataw['g_upper'] : null;
            $irrLower =  $pdataw['g_lower'] !== ''  ? (float)$pdataw['g_lower'] : null;
          
            if ($irrUpper < 0) $irrUpper = 0;
            if ($irrLower < 0) $irrLower = 0;

            // Sensoren sind vertauscht, Werte tauschen
            if ($anlage->getWeatherStation()->getChangeSensor()) {
                $irrHelp = $irrLower;
                $irrLower = $irrUpper;
                $irrUpper = $irrHelp;
            }
            $irr = $irrUpper;
            if ($irrUpper !== null && $irrLower !== null) {
                if ($anlage->getIsOstWestAnlage() && $anlage->getPowerEast() > 0 && $anlage->getPowerWest() > 0) {
                    $gwoben = $anlage->getPowerEast() / ($anlage->getPowerWest() + $anlage->getPowerEast());
                    $gwunten = $anlage->getPowerWest() / ($anlage->getPowerWest() + $anlage->getPowerEast());
                    $irr = $irrUpper * $gwoben + $irrLower * $gwunten;
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
     * @throws \JsonException
     */
    public function getSensors(Anlage $anlage, DateTime $from, DateTime $to): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $result = [];

        $dbTable = $anlage->getDbNameIst();
        // Suche nur für einen Inverter, da bei allen das gleiche steht, deshalb Umzug zu den Wetter Daten
        $sql = "SELECT stamp, irr_anlage FROM $dbTable WHERE unit = 1 AND stamp >= '" .$from->format('Y-m-d H:i')."' and stamp < '".$to->format('Y-m-d H:i')."'";
        $res = $conn->query($sql);
        if ($res->rowCount() >= 1) {
            $rows = $res->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $result[$row['stamp']] = json_decode((string) $row['irr_anlage'], true, 512, JSON_THROW_ON_ERROR);
            }
        }
        unset($res);

        return $result;
    }
}