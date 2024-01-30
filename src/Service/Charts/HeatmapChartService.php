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
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;

class HeatmapChartService
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly AnlagenStatusRepository $statusRepository,
        private readonly InvertersRepository     $invertersRepo,
        private readonly IrradiationChartService $irradiationChart,
        private readonly DCPowerChartService     $DCPowerChartService,
        private readonly ACPowerChartsService    $ACPowerChartService,
        private readonly WeatherServiceNew       $weatherService,
        private readonly FunctionsService        $functions)
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
     * [Heatmap]
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $sets
     * @param bool $hour
     * @return array|null
     *
     * @throws InvalidArgumentException
     */
    // MS 05/2022
    public function getHeatmap(Anlage $anlage, $from, $to, $sets = 0, bool $hour = false): ?array
    {
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];
        $pnominverter = $anlage->getPnomInverterArray();

        $sunArray = $this->weatherService->getSunrise($anlage, $from);
        $sunrise = strtotime((string) $sunArray['sunrise']);
        $sunArray = $this->weatherService->getSunrise($anlage, $to);
        $sunset = strtotime((string) $sunArray['sunset']);

        $from = date('Y-m-d H:i', $sunrise);
        $to = date('Y-m-d H:i', $sunset + 3600);

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
                if ($max > $groupct) $max = $groupct;
                if (($groupct <= $min)) $min = 1;
                $sqladd = "AND $group BETWEEN ".(empty($min)? '0' : $min)." AND ".(empty($max)? '50' : $max)."";
            }
        } else {
               $min = 1;
               $max = 50;
               $sqladd = "AND $group BETWEEN '$min' AND '$max'";
        }

        $dataArray['minSeries'] = $min;
        $dataArray['maxSeries'] = $max;
        $dataArray['sumSeries'] = $groupct;

        //fix the sql Query with an select statement in the join this is much faster
        $sql = "SELECT T1.istPower,T1.$group,T1.ts,T2.g_upper
                FROM (SELECT stamp as ts, wr_pac as istPower, ".$group."  FROM ".$anlage->getDbNameACIst()." WHERE stamp BETWEEN '$from' and '$to' $sqladd GROUP BY ts, $group ORDER BY $group DESC)
                AS T1
                JOIN (SELECT stamp as ts, g_lower as g_lower , g_upper as g_upper FROM " . $anlage->getDbNameWeather() . " WHERE stamp BETWEEN '$from' and '$to') 
                AS T2 
                on (T1.ts = T2.ts);";
        $resultActual = $conn->query($sql);
        $dataArray['inverterArray'] = $nameArray;

        if ($resultActual->rowCount() > 0) {

            $dataArray['maxSeries'] = 0;
            $counter = 0;
            $counterInv = 1;

            while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowActual['ts'];
                $e = explode(' ', (string) $stamp);
                $dataArray['chart'][$counter]['ydate'] = $e[1];
                $dataIrr = $rowActual['g_upper'];
                $powerist = $rowActual['istPower'];

                if ($powerist != null) {
                    $poweristkwh = ($powerist * 4);
                } else {
                    $poweristkwh = 0;
                }
                $pnomkwh = $pnominverter[$rowActual[$group]];#/ (float) 1000;
                if ($dataIrr > 10) {
                    $theoreticalIRR = (($dataIrr / 1000) * $pnomkwh);
                    if ($poweristkwh == 0 or $theoreticalIRR == 0) {
                        $value = 0;
                    } else {
                        $value = round(($poweristkwh / $theoreticalIRR) * 100);
                    }
                } else {
                    $value = 0;
                }

                $value = ($value > 100.0) ? 100.0 : $value;
                $dataArray['chart'][$counter]['xinv'] = $nameArray[$rowActual[$group]];
                $dataArray['chart'][$counter]['value'] = $value;
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
