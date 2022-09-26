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

class SollIstTempAnalyseChartService
{
    use G4NTrait;

    public function __construct(
        private Security $security,
        private AnlagenStatusRepository $statusRepository,
        private InvertersRepository $invertersRepo,
        private IrradiationChartService $irradiationChart,
        private DCPowerChartService $DCPowerChartService,
        private ACPowerChartsService $ACPowerChartService,
        private WeatherServiceNew $weatherService,
        private FunctionsService $functions)
    {    }

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
     */
     // MS 08 / 2022
    public function getSollIstTempDeviationAnalyse(Anlage $anlage, $from, $to, bool $hour = false): ?array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        $anlagename = $anlage->getAnlName();
        $conn = self::getPdoConnection();
        $dataArray = [];
        switch ($anlage->getConfigType()) {
            case 3:
            case 4:
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                break;
            default:
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
        }
        $sql = "SELECT 
                date_format(a.stamp, '%Y-%m-%d% %H:%i') as ts, 
                sum(c.wr_pac) as actPower,sum(b.ac_exp_power) as expected,
                c.wr_temp as wr_temp,
                CASE 
                WHEN ROUND((sum(c.wr_pac) / sum(b.ac_exp_power) * 100),0) IS NULL THEN '0'
                WHEN ROUND((sum(c.wr_pac) / sum(b.ac_exp_power) * 100),0) > 100 THEN '100'
                ELSE ROUND((sum(c.wr_pac) / sum(b.ac_exp_power) * 100),0)
                END AS prz
                FROM pvp_data.db_dummysoll a 
                LEFT JOIN ".$anlage->getDbNameDcSoll().' b ON a.stamp = b.stamp 
                LEFT JOIN '.$anlage->getDbNameACIst()." c ON a.stamp = c.stamp 
                WHERE a.stamp BETWEEN '$from' AND ' $to' 
                GROUP BY a.stamp ORDER BY NULL";

        $resultActual = $conn->query($sql);

        $dataArray['inverterArray'] = $nameArray;
        $maxInverter = $resultActual->rowCount();


        if ($resultActual->rowCount() > 0) {
            $dataArray['maxSeries'] = 0;
            $counter = 0;
            while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {
                $time = date('H:i', strtotime($rowActual['ts']));
                $stamp = date('Y-m-d', strtotime($rowActual['ts']));
                $actPower = $rowActual['actPower'];
                $actPower = $actPower > 0 ? round(self::checkUnitAndConvert($actPower, $anlage->getAnlDbUnit()), 2) : 0; // neagtive Werte auschließen
                $prz = $rowActual['prz'];
                $temp = $rowActual['wr_temp'];
                switch (TRUE){
                    case ($prz >= 95 and $prz <= 100);
                    $color = "#009900";
                    break;
                    case ($prz >= 90 and $prz <= 94);
                    $color = "#ffff00";
                    break;
                    case ($prz >= 85 and $prz <= 89);
                    $color = "#ff8800";
                    break;
                    case ($prz > 0 and $prz <= 84);
                    $color = "#f30000";
                    break;
                    default:
                    $color = "#0DD00";
                }

                $dataArray['maxSeries'] = $maxInverter;
                $dataArray['chart'][$counter]['title'] = $anlagename;
                $dataArray['chart'][$counter]['temp'] = round($temp,2);
                $dataArray['chart'][$counter]['time'] = $time;
                $dataArray['chart'][$counter]['color'] = $color;
                $dataArray['chart'][$counter]['kwh'] = (float)$actPower;
                $dataArray['chart'][$counter]['value'] = round((float)$prz,0);
                ++$counter;
            }
            $dataArray['offsetLegend'] = 0;
        }
        return $dataArray;
    }
}