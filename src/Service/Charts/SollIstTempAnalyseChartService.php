<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use App\Service\WeatherServiceNew;
use PDO;
use Symfony\Bundle\SecurityBundle\Security;

class SollIstTempAnalyseChartService
{
    use G4NTrait;

    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private $userPlant,
        private $passwordPlant,
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
    // MS first development 08 / 2022
    //  - Update Select 12 / 2022
    public function getSollIstTempDeviationAnalyse(Anlage $anlage, $from, $to, ?int $inverter = 0, bool $hour = false): ?array
    {
        ini_set('memory_limit', '3G');
        $anlagename = $anlage->getAnlName();
        $conn = self::getPdoConnection($this->host, $this->userPlant, $this->passwordPlant);
        $dataArray = [];
        switch ($anlage->getConfigType()) {
            case 3:
            case 4:
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                break;
            default:
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
        }
        if ($inverter >= 0) {
            $sql_add_where_b = "AND b.wr_num = '$inverter'";
            $sql_add_where_a = "AND c.unit = '$inverter'";
        } else {
            $maxinvert = $anlage->getAnzInverter();
            $sql_add_where_b = "";
            $sql_add_where_a = "";
        }
//fix the sql Query with an select statement in the join this is much faster
// MS 01/23
        $sql = 'SELECT 
                as1.act_power_ac,
                as2.expected,
                as1.wr_temp,
                CASE 
                WHEN ROUND((as1.act_power_ac / as2.dcexpected * 100),0) IS NULL THEN \'0\'
                WHEN ROUND((as1.act_power_ac / as2.dcexpected * 100),0) > 100 THEN \'100\'
                ELSE ROUND((as1.act_power_ac / as2.dcexpected * 100),0)
                END AS prz
                FROM (SELECT c.stamp as ts, sum(c.wr_pac) as act_power_ac, sum(c.wr_pdc) as act_power_dc, c.wr_temp as wr_temp FROM 
                 '.$anlage->getDbNameACIst().' c WHERE c.stamp 
                 BETWEEN \''.$from.'\' AND \''.$to.'\' '.$sql_add_where_a.'
                 AND c.wr_pac > 0
                 GROUP BY c.stamp ORDER BY NULL)
                AS as1
             JOIN
                (SELECT b.stamp as ts, sum(b.ac_exp_power) as expected, sum(b.dc_exp_power) as dcexpected FROM 
                 '.$anlage->getDbNameDcSoll().' b WHERE b.stamp 
                 BETWEEN \''.$from.'\' AND \''.$to.'\' '.$sql_add_where_b.'
                 GROUP BY b.stamp ORDER BY NULL)
                AS as2  
                on (as1.ts = as2.ts)';

        $resultActual = $conn->query($sql);
        $dataArray['inverterArray'] = $nameArray;
        $maxInverter = $resultActual->rowCount();

        if ($resultActual->rowCount() > 0) {
            $dataArray['maxSeries'] = 0;
            $counter = 0;
            while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {
                //$time = date('H:i', strtotime($rowActual['ts']));
                //$stamp = date('Y-m-d', strtotime($rowActual['ts']));
                $time = date('H:i', strtotime(self::timeShift($anlage,$rowActual['ts'])));
                $actPower = $rowActual['act_power_ac'];
                $actPower = $actPower > 0 ? round($actPower, 2) : 0; // neagtive Werte auschließen
                $prz = $rowActual['prz'];
                $temp = $rowActual['wr_temp'];
                switch (TRUE){
                    case ($prz >= 95 and $prz <= 100);
                    $color = "#009900";
                    break;
                    case ($prz >= 90 and $prz <= 94);
                    $color = "#ffff00";
                    break;
                   // case ($prz >= 85 and $prz <= 89);
                   // $color = "#ff8800";
                   // break;
                    case ($prz > 0 and $prz <= 89);
                    $color = "#f30000";
                    break;
                    default:
                    $color = "#0DD00";
                }
                //$dataArray['maxSeries'] = $maxInverter;
                //$dataArray['chart'][$counter]['title'] = $anlagename;
                $dataArray['chart'][$counter]['temp'] = round($temp,2);
                $dataArray['chart'][$counter]['time'] = $time;
                $dataArray['chart'][$counter]['color'] = $color;
                $dataArray['chart'][$counter]['kwh'] = (float)$actPower;
                $dataArray['chart'][$counter]['value'] = round((float)$prz,0);
                ++$counter;
            }
            $dataArray['offsetLegend'] = 0;
            return $dataArray;
        } else {
            return $dataArray;
        }
    }
}
