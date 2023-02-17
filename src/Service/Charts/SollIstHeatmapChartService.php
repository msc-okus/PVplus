<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use App\Service\WeatherServiceNew;
use PDO;
use Symfony\Component\Security\Core\Security;

class SollIstHeatmapChartService
{
    use G4NTrait;

    private Security $security;

    private AnlagenStatusRepository $statusRepository;

    private InvertersRepository $invertersRepo;

    public functionsService $functions;

    private IrradiationChartService $irradiationChart;

    private WeatherServiceNew $weatherService;

    public function __construct(Security $security,
        AnlagenStatusRepository $statusRepository,
        InvertersRepository $invertersRepo,
        IrradiationChartService $irradiationChart,
        DCPowerChartService $DCPowerChartService,
        ACPowerChartsService $ACPowerChartService,
        WeatherServiceNew $weatherService,
        FunctionsService $functions)
    {
        $this->security = $security;
        $this->statusRepository = $statusRepository;
        $this->invertersRepo = $invertersRepo;
        $this->functions = $functions;
        $this->irradiationChart = $irradiationChart;
        $this->DCPowerChartService = $DCPowerChartService;
        $this->ACPowerChartService = $ACPowerChartService;
        $this->WeatherServiceNew = $weatherService;
    }

    // Help Function for Array search
    // MS
    public static function array_recursive_search_key_map($needle, $haystack)
    {
        foreach ($haystack as $first_level_key => $value) {
            if ($needle === $value) {
                return [$first_level_key];
            } elseif (is_array($value)) {
                $callback = self::array_recursive_search_key_map($needle, $value);
                if ($callback) {
                    return array_merge([$first_level_key], $callback);
                }
            }
        }

        return false;
    }

    /**
     * @param $from
     * @param $to
     * @param int $group
     *
     * @return array
     *               [Heatmap]
     */
    // MS 06/2022
    public function getSollIstHeatmap(Anlage $anlage, $from, $to, $sets = 0, bool $hour = false): ?array
    {
        ini_set('memory_limit', '3G');
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $counter = 0;

        $gmt_offset = 1;   // Unterschied von GMT zur eigenen Zeitzone in Stunden.
        $zenith = 90 + 50 / 60;

        $current_date = strtotime($from);
        $sunset = date_sunset($current_date, SUNFUNCS_RET_TIMESTAMP, (float) $anlage->getAnlGeoLat(), (float) $anlage->getAnlGeoLon(), $zenith, $gmt_offset);
        $sunrise = date_sunrise($current_date, SUNFUNCS_RET_TIMESTAMP, (float) $anlage->getAnlGeoLat(), (float) $anlage->getAnlGeoLon(), $zenith, $gmt_offset);

        $from = date('Y-m-d H:00', $sunrise - 3600);
        $to = date('Y-m-d H:00', $sunset + 5400);

        $conn = self::getPdoConnection();
        $dataArray = [];

// fix the sql Query with an select statement in the join this is much faster
        if ($anlage->getUseNewDcSchema()) {
            $nameArray = $this->functions->getNameArray($anlage, 'dc');
            $groupct = count($anlage->getGroupsDc());
            if ($groupct) {
                if ($sets == null) {
                    $min = 1;
                    $max = (($groupct > 100) ? (int)ceil($groupct / 10) : (int)ceil($groupct / 2));
                    $max = (($max > 50) ? '50' : $max);
                    $sqladd = "AND c.wr_group BETWEEN '$min' AND '$max'";
                } else {
                    $res = explode(',', $sets);
                    $min = (int)ltrim($res[0], "[");
                    $max = (int)rtrim($res[1], "]");
                    (($max > $groupct) ? $max = $groupct:$max = $max);
                    (($groupct > $min) ? $min = $min:$min = 1);
                    $sqladd = "AND c.wr_group BETWEEN " . (empty($min) ? '1' : $min) . " AND " . (empty($max) ? '50' : $max) . "";
                }
            } else {
                $min = 1;
                $max = 50;
                $sqladd = "AND c.wr_group BETWEEN '$min' AND '$max '";
            }
// fix the sql Query with an select statement in the join this is much faster
            $sql = "SELECT 
                as1.ts,
                as1.inv,
                as1.istCurrent,
                as2.sollCurrent,
                as2.expected
                FROM (SELECT c.stamp as ts, c.wr_idc as istCurrent, c.wr_group as inv FROM
                 " . $anlage->getDbNameDCIst() . " c WHERE c.stamp 
                 BETWEEN '$from' AND '$to' 
                 $sqladd
                 GROUP BY c.stamp,c.wr_group ORDER BY NULL)
                AS as1
             JOIN
                (SELECT b.stamp as ts, b.soll_imppwr as sollCurrent, b.dc_exp_power as expected FROM 
                 " . $anlage->getDbNameDcSoll() . " b WHERE b.stamp 
                 BETWEEN '$from' AND '$to'
                 GROUP BY b.stamp ORDER BY NULL)
                AS as2  
                on (as1.ts = as2.ts)";
        } else {
            $nameArray = $this->functions->getNameArray($anlage, 'dc');
            $groupct = count($anlage->getGroupsDc());
            if ($groupct) {
                if ($sets == null) {
                    $min = 1;
                    $max = (($groupct > 100) ? (int)ceil($groupct / 10) : (int)ceil($groupct / 2));
                    $max = (($max > 50) ? '50' : $max);
                    $sqladd = "AND c.group_dc BETWEEN '$min' AND '$max'";
                } else {
                    $res = explode(',', $sets);
                    $min = (int)ltrim($res[0], "[");
                    $max = (int)rtrim($res[1], "]");
                    (($max > $groupct) ? $max = $groupct:$max = $max);
                    (($groupct > $min) ? $min = $min:$min = 1);
                    $sqladd = "AND c.group_dc BETWEEN " . (empty($min) ? '1' : $min) . " AND " . (empty($max) ? '50' : $max) . "";
                }
            } else {
                $min = 1;
                $max = 50;
                $sqladd = "AND c.group_dc BETWEEN '$min' AND '$max '";
            }
// fix the sql Query with an select statement in the join this is much faster
            $sql = "SELECT 
                as1.ts,
                as1.inv,
                as1.istCurrent,
                as2.sollCurrent,
                as2.expected
                FROM (SELECT c.stamp as ts, c.wr_idc as istCurrent, c.group_dc as inv FROM
                 " . $anlage->getDbNameACIst() . " c WHERE c.stamp 
                 BETWEEN '$from' AND '$to' 
                 $sqladd
                 GROUP BY c.stamp,c.group_dc ORDER BY NULL)
                AS as1
             JOIN
                (SELECT b.stamp as ts, b.soll_imppwr as sollCurrent, b.dc_exp_power as expected FROM 
                 " . $anlage->getDbNameDcSoll() . " b WHERE b.stamp 
                 BETWEEN '$from' AND '$to'
                 GROUP BY b.stamp ORDER BY NULL)
                AS as2  
                on (as1.ts = as2.ts)";
        }
//
        $dataArray['minSeries'] = $min;
        $dataArray['maxSeries'] = $max;
        $dataArray['sumSeries'] = $groupct;

        $resultActual = $conn->query($sql);
        $dataArray['inverterArray'] = $nameArray;
        // SOLL Strom für diesen Zeitraum und diese Gruppe
        if ($resultActual->rowCount() > 0) {
            while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {
                $stamp = self::timeShift($anlage,$rowActual['ts']);
                $e = explode(' ', $stamp);
                $dataArray['chart'][$counter]['ydate'] = $e[1];
                ($rowActual['sollCurrent'] == null) ? $powersoll = 0 : $powersoll = $rowActual['sollCurrent'];
                ($rowActual['istCurrent'] == null) ? $powerist = 0 : $powerist = $rowActual['istCurrent'];

                if ($powersoll != 0) {
                    $value = round(($powerist / $powersoll) * (float) 100);
                 } else {
                    $value = 0;
                }
                $value = ($value > (float) 100) ? (float) 100 : $value;
                $value = ($value < (float) 0) ? (float) 100 : $value;
                ($nameArray[$rowActual['inv']]) ? $value = $value : $value = -1;
                $dataArray['chart'][$counter]['xinv'] = $nameArray[$rowActual['inv']];
                $dataArray['chart'][$counter]['value'] = $value;
                $dataArray['chart'][$counter]['ist'] = $powerist;
                $dataArray['chart'][$counter]['expected'] = $powersoll;
                /*
                $dataArray['chart'][$counter]['irr'] =  $dataIrr;
                $dataArray['chart'][$counter]['thirr'] =  $theoreticalIRR;
                $dataArray['chart'][$counter]['pnomkwh'] =  $pnomkwh;
                $dataArray['chart'][$counter]['ist'] =  $powerist ;
                $dataArray['chart'][$counter]['istkwh'] =  $poweristkwh ;
                */
                ++$counter;
            }
            $dataArray['offsetLegend'] = 0;
        }
        return $dataArray;
    }
}