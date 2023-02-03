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

        switch ($anlage->getConfigType()) {
            case 3:
            case 4:
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                $group = 'wr_group';
                break;
            default:
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
                $group = 'group_dc';
        }
       /* if ($anlage->getUseNewDcSchema()) {
            $sql = "SELECT a.stamp as ts, c.wr_idc as istCurrent, b.soll_imppwr as sollCurrent, b.dc_exp_power as expected, c.$group as inv FROM db_dummysoll a 
                    LEFT JOIN ".$anlage->getDbNameDcSoll().' b ON a.stamp = b.stamp 
                    LEFT JOIN '.$anlage->getDbNameDCIst()." c ON b.stamp = c.stamp 
                    WHERE a.stamp BETWEEN '$from' AND '$to'
                    GROUP BY a.stamp, c.$group;";
        } else {
            $sql = "SELECT a.stamp as ts, c.wr_idc as istCurrent, b.soll_imppwr as sollCurrent, b.dc_exp_power as expected, c.group_dc as inv FROM db_dummysoll a 
                    LEFT JOIN ".$anlage->getDbNameDcSoll().' b ON a.stamp = b.stamp 
                    LEFT JOIN '.$anlage->getDbNameACIst()." c ON b.stamp = c.stamp 
                    WHERE a.stamp BETWEEN '$from' AND '$to'
                    GROUP BY a.stamp, c.group_dc;";
        }
     */
        $groupct = count($anlage->getGroupsDc());
        if ($groupct > 50) {
            if ($sets == null) {
                $sqladd = "AND c.$group BETWEEN '1' AND '50'";
            }
            if ($sets != null) {
                $res = explode(',', $sets);
                $min = ltrim($res[0], "[");
                $max = rtrim($res[1], "]");
                $sqladd = "AND c.$group BETWEEN '$min' AND '$max'";
            }
        } else {
            $sqladd = "";
        }

        $sql = 'SELECT 
                as1.ts,
                as1.inv,
                as1.istCurrent,
                as2.sollCurrent,
                as2.expected
                FROM (SELECT c.stamp as ts, c.wr_idc as istCurrent, c.'.$group.' as inv FROM 
                 '.$anlage->getDbNameACIst().' c WHERE c.stamp 
                 BETWEEN \''.$from.'\' AND \''.$to.'\' 
                 '.$sqladd.'  
                 GROUP BY c.stamp,c.'.$group.' ORDER BY NULL)
                AS as1
             JOIN
                (SELECT b.stamp as ts, b.soll_imppwr as sollCurrent, b.dc_exp_power as expected FROM 
                 '.$anlage->getDbNameDcSoll().' b WHERE b.stamp 
                 BETWEEN \''.$from.'\' AND \''.$to.'\' 
                 GROUP BY b.stamp ORDER BY NULL)
                AS as2  
                on (as1.ts = as2.ts)';

        $resultActual = $conn->query($sql);
        $dataArray['inverterArray'] = $nameArray;
        $maxInverter = $resultActual->rowCount();

        // SOLL Strom für diesen Zeitraum und diese Gruppe

        if ($resultActual->rowCount() > 0) {
            $dataArray['maxSeries'] = 0;
            $counter = 0;

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
                $dataArray['maxSeries'] = $maxInverter;
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