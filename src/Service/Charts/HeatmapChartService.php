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

class HeatmapChartService
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
    // MS 05/2022
    public function getHeatmap(Anlage $anlage, $from, $to, $sets = 0 ,bool $hour = false): ?array
    {
        ini_set('memory_limit', '3G');
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        $group = 1;
        $anlagename = $anlage->getAnlName();
        $pnominverter = $anlage->getPnomInverterArray();
        $counter = 0;
        $gmt_offset = 1;   // Unterschied von GMT zur eigenen Zeitzone in Stunden.
        $zenith = 90 + 50 / 60;
        $current_date = strtotime(str_replace("T", "", $from));
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

        $conn = self::getPdoConnection();
        $dataArray = [];
        $inverterNr = 0;
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


        if ($groupct > 50) {
            if ($sets == null) {
                $sqladd = "AND $group BETWEEN '1' AND '50'";
            }
            if ($sets != null) {
                $res = explode(',', $sets);
                $min = ltrim($res[0], "[");
                $max = rtrim($res[1], "]");
                $sqladd = "AND $group BETWEEN '$min' AND '$max'";
            }
        } else {
            $sqladd = "";
        }

      $sql = "SELECT T1.istPower,T1.".$group.",T1.ts,T2.g_upper
            FROM (SELECT stamp as ts, wr_pac as istPower, ".$group."  FROM ".$anlage->getDbNameACIst()." WHERE stamp BETWEEN '$from' and '$to'  ".$sqladd." GROUP BY ts, ".$group.")
            AS T1
            JOIN (SELECT stamp as ts, g_lower as g_lower , g_upper as g_upper FROM ".$anlage->getDbNameWeather()." WHERE stamp BETWEEN '$from' and '$to' ) 
            AS T2 
            on (T1.ts = T2.ts);";
#
        $resultActual = $conn->query($sql);
        $dataArray['inverterArray'] = $nameArray;

        if ($resultActual->rowCount() > 0) {

            $dataArray['maxSeries'] = 0;
            $counter = 0;
            $counterInv = 1;

            while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {

                $stamp = $rowActual['ts'];
                $e = explode(' ', $stamp);
                $dataArray['chart'][$counter]['ydate'] = $e[1];
                $dataIrr = $rowActual['g_upper'];
                $powerist = $rowActual['istPower'];

                if ($powerist != null) {
                    $poweristkwh =  ($powerist * (float) 4) ;
                } else {
                    $poweristkwh = 0;
                }
                $pnomkwh = $pnominverter[$rowActual[$group]] ;#/ (float) 1000;
                if ($dataIrr > 10) {
                    $theoreticalIRR = (($dataIrr / (float) 1000) * $pnomkwh );
                    if ($poweristkwh == 0 or $theoreticalIRR == 0) {
                        $value = 0;
                    } else {
                        $value = round(($poweristkwh / $theoreticalIRR) * (float) 100);
                    }
                } else {
                    $value = 0;
                }

                $value = ($value > (float) 100) ? (float) 100 : $value;
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
