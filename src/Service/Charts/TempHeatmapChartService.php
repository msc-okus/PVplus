<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use App\Service\PdoService;
use App\Service\WeatherServiceNew;
use PDO;
use Symfony\Bundle\SecurityBundle\Security;

class TempHeatmapChartService
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly Security $security,
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
    // ToDo: please move to G4NTrait
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
     */
    // MS 06/2022
    public function getTempHeatmap(Anlage $anlage, $from, $to, $sets, bool $hour = false): ?array
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

        switch ($anlage->getConfigType()) {
            case 3:
            case 4:
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                $idArray = $this->functions->getIdArray($anlage, 'ac');
                $group = 'group_ac';
                break;
            default:
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
                $idArray = $this->functions->getIdArray($anlage, 'dc');
                $group = 'group_dc';
        }

        $groupct = count($nameArray);
        if ($groupct) {
            if ($sets == null) {
                $min = 1;
                $max = (($groupct > 100) ? (int)ceil($groupct / 10) : (int)ceil($groupct / 2));
                $max = (($max > 50) ? '50' : $max);
                $temp = '';
                for ($i = 0; $i < $max; ++$i) {
                    $invId = $i+1;
                    $temp = $temp.$group." = ".$invId." OR ";
                    $invIdArray[$i+1] =  $idArray[$i+1];
                    $invNameArray[$i+1] =  $nameArray[$i+1];
                }

                $temp = substr($temp, 0, -4);
                $sqladd = "AND ($temp) ";
            } else {
                $temp = '';
                $j = 1;
                for ($i = 0; $i < count($nameArray); ++$i) {
                    if(str_contains($sets, $nameArray[$i+1])){
                        $invId = $i+1;
                        $temp = $temp.$group." = ".$invId." OR ";
                        $invIdArray[$i+1] =  $idArray[$i+1];
                        $invNameArray[$j] =  $nameArray[$i+1];
                        $j++;
                    }
                }

                $temp = substr($temp, 0, -4);
                $sqladd = "AND ($temp) ";
            }
        } else {
            $min = 1;
            $max = 50;
            $sqladd = "AND $group BETWEEN '$min' AND '$max'";
        }

        $dataArray['invNames'] = $invNameArray;
        $dataArray['invIds'] = $invIdArray;
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
                $e = explode(' ', (string) $stamp);
                $dataArray['chart'][$counter]['ydate'] = $e[1];
                $value = round($rowActual['istTemp']);
                $value = ($value > 100) ?  100 : $value;
                $e = explode(' ', (string) $stamp);
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
