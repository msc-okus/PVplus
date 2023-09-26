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

class TempHeatmapChartService
{
    use G4NTrait;

    public function __construct(
        private PdoService $pdoService,
        private Security $security,
        private AnlagenStatusRepository $statusRepository,
        private InvertersRepository $invertersRepo,
        private IrradiationChartService $irradiationChart,
        private DCPowerChartService $DCPowerChartService,
        private ACPowerChartsService $ACPowerChartService,
        private WeatherServiceNew $weatherServiceNew,
        private FunctionsService $functions)
    {

    }

    // Help Function for Array search
    // MS
    private static function array_recursive_search_key_map($needle, $haystack): array|bool
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
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $sets
     * @param bool $hour
     * @return array|null [Heatmap]
     *
     * @throws \Exception
     */
    // MS 06/2022
    public function getTempHeatmap(Anlage $anlage, $from, $to, $sets, bool $hour = false): ?array
    {
        ini_set('memory_limit', '3G');
        $gmt_offset = 1;   // Unterschied von GMT zur eigenen Zeitzone in Stunden.
        $zenith = 90 + 50 / 60;
        $current_date = strtotime($from);
        $counter = 0;
        $sunset = date_sunset($current_date, SUNFUNCS_RET_TIMESTAMP, (float) $anlage->getAnlGeoLat(), (float) $anlage->getAnlGeoLon(), $zenith, $gmt_offset);
        $sunrise = date_sunrise($current_date, SUNFUNCS_RET_TIMESTAMP, (float) $anlage->getAnlGeoLat(), (float) $anlage->getAnlGeoLon(), $zenith, $gmt_offset);

        if ($hour) {
            $form = '%y%m%d%H';
        } else {
            $form = '%y%m%d%H%i';
        }

        // $sunArray = $this->WeatherServiceNew->getSunrise($anlage,$from);
        // $sunrise = $sunArray[$anlagename]['sunrise'];
        // $sunset = $sunArray[$anlagename]['sunset'];

        $from = date('Y-m-d H:00', $sunrise - 3600);
        $to = date('Y-m-d H:00', $sunset + 5400);

        $from = self::timeAjustment($from, $anlage->getAnlZeitzone());
        $to = self::timeAjustment($to, 1);

        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];

        switch ($anlage->getConfigType()) {
            case 3:
            case 4:
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                $group = 'group_ac';
            $groupct = count($anlage->getGroupsAc());
                break;
            default:
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
                $group = 'group_dc';
                $groupct = count($anlage->getGroupsDc());
        }

        if ($groupct) {
            if ($sets == null) {
                $min = 1;
                $max = (($groupct > 100) ? (int)ceil($groupct / 10) : (int)ceil($groupct / 2));
                $max = (($max > 50) ? '50' : $max);
                $sqladd = "AND $group BETWEEN '$min' AND '$max'";
            } else {
                $res = explode(',', $sets);
                $min = (int)ltrim($res[0], "[");
                $max = (int)rtrim($res[1], "]");
                (($max > $groupct) ? $max = $groupct : $max = $max);
                (($groupct > $min) ? $min = $min : $min = 1);
                $sqladd = "AND $group BETWEEN " . (empty($min) ? '1' : $min) . " AND " . (empty($max) ? '50' : $max) . "";
            }
        } else {
            $min = 1;
            $max = 50;
            $sqladd = "AND $group BETWEEN '$min' AND '$max'";
        }

        $dataArray['minSeries'] = $min;
        $dataArray['maxSeries'] = $max;
        $dataArray['sumSeries'] = $groupct;

        $sql = "SELECT T1.istTemp,T1.".$group.",T1.ts,T2.g_upper
            FROM (SELECT stamp as ts, wr_temp as istTemp, ".$group."  FROM ".$anlage->getDbNameACIst()." WHERE stamp BETWEEN '$from' and '$to'  ".$sqladd." GROUP BY ts, ".$group." ORDER BY ".$group." DESC)
            AS T1
            JOIN (SELECT stamp as ts, g_lower as g_lower , g_upper as g_upper FROM ".$anlage->getDbNameWeather()." WHERE stamp BETWEEN '$from' and '$to' ) 
            AS T2 
            on (T1.ts = T2.ts) ;";

        $resultActual = $conn->query($sql);
        $dataArray['inverterArray'] = $nameArray;

        if ($resultActual->rowCount() > 0) {
            #
            while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowActual['ts']; // self::timeShift($anlage,$rowActual['ts']);
                $dataIrr = $rowActual['g_upper'];
                (empty($dataIrr) ? $dataIrr = 0 : $dataIrr = $dataIrr);
                $e = explode(' ', $stamp);
                $dataArray['chart'][$counter]['ydate'] = $e[1];
                $value = round($rowActual['istTemp']);
                $value = ($value > 100) ?  100 : $value;
                $e = explode(' ', $stamp);
                $dataArray['chart'][$counter]['ydate'] = $e[1];
                $dataArray['chart'][$counter]['xinv'] = $nameArray[$rowActual[$group]];
                $dataArray['chart'][$counter]['value'] = $value;
                $dataArray['chart'][$counter]['irr'] = $dataIrr;
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
