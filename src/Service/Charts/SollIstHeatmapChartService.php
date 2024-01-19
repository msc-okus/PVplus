<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use App\Service\WeatherServiceNew;
use PDO;
use App\Service\PdoService;
use Symfony\Bundle\SecurityBundle\Security;

class SollIstHeatmapChartService
{
    use G4NTrait;

    public function __construct(
private readonly PdoService $pdoService,
        private readonly AnlagenStatusRepository $statusRepository,
        private readonly InvertersRepository $invertersRepo,
        private readonly IrradiationChartService $irradiationChart,
        private readonly DCPowerChartService $DCPowerChartService,
        private readonly ACPowerChartsService $ACPowerChartService,
        private readonly WeatherServiceNew $weatherService,
        private readonly FunctionsService $functions)
    {
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
     * DC Curtrent Heatmap
     * @param $from
     * @param $to
     * @param null|int $sets
     * @return array [Heatmap]
     *
     */
    // MS 06/2022
    public function getSollIstHeatmap(Anlage $anlage, $from, $to, $sets = 0, bool $hour = false): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];
        $counter = 0;

        $sunArray = $this->weatherService->getSunrise($anlage, $from);
        $sunrise = strtotime((string) $sunArray['sunrise']);
        $sunArray = $this->weatherService->getSunrise($anlage, $to);
        $sunset = strtotime((string) $sunArray['sunset']);

        $from = date('Y-m-d H:i', $sunrise);
        $to = date('Y-m-d H:i', $sunset + 3600);

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
                    $sqladb = "AND b.group_dc BETWEEN '$min' AND '$max '";
                } else {
                    $res = explode(',', $sets);
                    $min = (int)ltrim($res[0], "[");
                    $max = (int)rtrim($res[1], "]");
                    (($max > $groupct) ? $max = $groupct:$max = $max);
                    (($groupct > $min) ? $min = $min:$min = 1);
                    $sqladd = "AND c.wr_group BETWEEN " . (empty($min) ? '1' : $min) . " AND " . (empty($max) ? '50' : $max) . "";
                    $sqladb = "AND b.group_dc BETWEEN " . (empty($min) ? '1' : $min) . " AND " . (empty($max) ? '50' : $max) . "";
                }
            } else {
                $min = 1;
                $max = 50;
                $sqladd = "AND c.wr_group BETWEEN '$min' AND '$max '";
                $sqladb = "AND b.group_dc BETWEEN '$min' AND '$max '";
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
                (SELECT b.group_dc as grp_dc, b.stamp as ts, b.soll_imppwr as sollCurrent, b.dc_exp_power as expected FROM 
                 " . $anlage->getDbNameDcSoll() . " b WHERE b.stamp 
                 BETWEEN '$from' AND '$to'
                 $sqladb
                 GROUP BY b.stamp,b.group_dc ORDER BY NULL)
                AS as2  
                on (as1.ts = as2.ts and as1.inv = as2.grp_dc)";

        } else {
            $nameArray = $this->functions->getNameArray($anlage, 'dc');
            $groupct = count($anlage->getGroupsDc());
            if ($groupct) {
                if ($sets == null) {
                    $min = 1;
                    $max = (($groupct > 100) ? (int)ceil($groupct / 10) : (int)ceil($groupct / 2));
                    $max = (($max > 50) ? '50' : $max);
                    $sqladd = "AND c.group_dc BETWEEN '$min' AND '$max'";
                    $sqladb = "AND b.group_dc BETWEEN '$min' AND '$max'";
                } else {
                    $res = explode(',', $sets);
                    $min = (int)ltrim($res[0], "[");
                    $max = (int)rtrim($res[1], "]");
                    (($max > $groupct) ? $max = $groupct:$max = $max);
                    (($groupct > $min) ? $min = $min:$min = 1);
                    $sqladd = "AND c.group_dc BETWEEN " . (empty($min) ? '1' : $min) . " AND " . (empty($max) ? '50' : $max) . "";
                    $sqladb = "AND b.group_dc BETWEEN " . (empty($min) ? '1' : $min) . " AND " . (empty($max) ? '50' : $max) . "";
                }
            } else {
                $min = 1;
                $max = 50;
                $sqladd = "AND c.group_dc BETWEEN '$min' AND '$max '";
                $sqladb = "AND b.group_dc BETWEEN '$min' AND '$max '";
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
                (SELECT b.stamp as ts,b.group_dc, b.soll_imppwr as sollCurrent, b.dc_exp_power as expected FROM 
                 " . $anlage->getDbNameDcSoll() . " b WHERE b.stamp 
                 BETWEEN '$from' AND '$to'
                 $sqladb
                 GROUP BY b.stamp,b.group_dc ORDER BY NULL)
                AS as2  
                on (as1.ts = as2.ts and as1.inv = as2.group_dc);";
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
                $stamp = $rowActual['ts']; // self::timeShift($anlage,$rowActual['ts']);
                $e = explode(' ', (string) $stamp);
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