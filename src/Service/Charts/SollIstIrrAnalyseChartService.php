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

class SollIstIrrAnalyseChartService
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
    {    }

    // Help Function for Array search
    // MS
    // ToDo: please move to G4NTrait
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
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int|null $inverter
     * @param int $filter
     * @param bool $hour
     * @return array|null
     */
    // MS 10 / 2022
    public function getSollIstIrrDeviationAnalyse(Anlage $anlage, $from, $to, ?int $inverter = 0, int $filter = 400, bool $hour = false): ?array
    {
        ini_set('memory_limit', '3G');
        $dataArray = [];
        $anlagename = $anlage->getAnlName();
        $conn = $this->pdoService->getPdoPlant();
        $tabelArray = [];

        switch ($filter) {
            case 400:
                $irr_from = '0';
                $irr_to =  '400';
                break;
            case 800:
                $irr_from = '400';
                $irr_to =  '800';
                break;
            case 1000:
                $irr_from = '800';
                $irr_to =  '1200';
                break;
        }

        $nameArray = match ($anlage->getConfigType()) {
            3, 4 => $this->functions->getNameArray($anlage, 'ac'),
            default => $this->functions->getNameArray($anlage, 'dc'),
        };

        if ($inverter >= 0) {
            $sql_add_where_b = "AND b.wr_num = '$inverter'";
            $sql_add_where_a = "AND c.unit = '$inverter'";
        } else {
            $maxinvert = $anlage->getAnzInverter();
            $sql_add_where_b = "";
            $sql_add_where_a = "";
        }
//fix the sql Query with an select statement in the join this ist much faster
// MS 01/23
        $sql = 'SELECT          
                as1.act_power_ac,
                as1.act_power_dc, 
                as3.avg_irr,
                CASE
                WHEN ROUND((as1.act_power_ac / as2.expected * 100),0) IS NULL THEN \'0\'
                WHEN ROUND((as1.act_power_ac / as2.expected * 100),0) > 100 THEN \'100\'
                ELSE ROUND((as1.act_power_ac / as2.expected * 100),0)
                END AS przac,
                CASE
                WHEN ROUND((as1.act_power_dc / as2.dcexpected * 100),0) IS NULL THEN \'0\'
                WHEN ROUND((as1.act_power_dc / as2.dcexpected * 100),0) > 100 THEN \'100\'
                ELSE ROUND((as1.act_power_dc / as2.dcexpected * 100),0)
                END AS przdc
                FROM
                (SELECT c.stamp as ts, sum(c.wr_pac) as act_power_ac, sum(c.wr_pdc) as act_power_dc FROM 
                 '.$anlage->getDbNameACIst().' c WHERE c.stamp 
                 BETWEEN \''.$from.'\' AND \''.$to.'\' '.$sql_add_where_a.'
                 AND c.wr_pac > 0
                 AND c.wr_pdc > 0
                 GROUP BY c.stamp ORDER BY NULL)
                AS as1
             JOIN
                (SELECT b.stamp as ts, sum(b.ac_exp_power) as expected,sum(b.dc_exp_power) as dcexpected FROM 
                 '.$anlage->getDbNameDcSoll().' b WHERE b.stamp 
                 BETWEEN \''.$from.'\' AND \''.$to.'\' '.$sql_add_where_b.'
                 AND b.ac_exp_power > 0
                 AND b.dc_exp_power > 0
                 GROUP BY b.stamp ORDER BY NULL)
                AS as2 
                on (as1.ts = as2.ts)
             JOIN
               (SELECT w.stamp as ts, w.g_upper as avg_irr FROM 
                 '.$anlage->getDbNameWeather().' w WHERE w.stamp 
                 BETWEEN \''.$from.'\' AND \''.$to.'\'
                 AND ROUND(w.g_upper, 0) BETWEEN \''.$irr_from.'\' AND \''.$irr_to.'\'
                 AND ROUND(w.g_upper, 0) > 0 
                 AND ROUND(w.g_upper, 0) > 0
                 GROUP BY w.stamp ORDER BY NULL)
                AS as3
                on (as1.ts = as3.ts)';

        $resultActual = $conn->query($sql);
        //$dataArray['inverterArray'] = $nameArray;
        $maxInverter = $resultActual->rowCount();

        if ($resultActual->rowCount() > 0) {
            //$dataArray['maxSeries'] = 0;
            $counter = 0;
            while ($rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {
                //$time = date('H:i', strtotime($rowActual['ts']));
                //$stamp = date('Y-m-d', strtotime($rowActual['ts']));
                $actPowerAC = $rowActual['act_power_ac'];
                $actPowerAC = $actPowerAC > 0 ? round($actPowerAC, 3) : 0; // neagtive Werte auschließen
                $actPowerAC = substr($actPowerAC, 0, 5);
                $actPowerDC = $rowActual['act_power_dc'];
                $actPowerDC = $actPowerDC > 0 ? round($actPowerDC, 3) : 0; // neagtive Werte auschließen
                $actPowerDC = substr($actPowerDC, 0, 5);
                $przac = $rowActual['przac'];
                $przac =  $przac > 0 ? $przac : 0;
                $przdc = $rowActual['przdc'];
                $przdc =  $przdc > 0 ? $przac : 0;
                $irr = $rowActual['avg_irr'];
                switch (TRUE) {
                    case ($przac >= 95 and $przac <= 100);
                    $colorAC = "#009900";
                    $ACsum100 += $actPowerAC;
                    break;
                    case ($przac >= 80 and $przac <= 94);
                    $colorAC = "#ffff00";
                    $ACsum95 += $actPowerAC;
                    break;
                    case ($przac > 0 and $przac <= 79);
                    $ACsum90 += $actPowerAC;
                    $colorAC = "#ff0000";
                    break;
                    default:
                    $colorAC = "#0DD00";
                }
                switch (TRUE){
                    case ($przdc >= 95 and $przdc <= 100);
                    $DCsum100 += $actPowerDC;
                    $colorDC = "#009900";
                    break;
                    case ($przdc >= 80 and $przdc <= 94);
                    $DCsum95 += $actPowerDC;
                    $colorDC = "#ffff00";
                    break;
                    case ($przdc > 0 and $przdc <= 79);
                    $DCsum90 += $actPowerDC;
                    $colorDC = "#ff0000";
                    break;
                    default:
                    $colorDC = "#0DD00";
                }

                $dataArray['maxSeries'] = $maxInverter;
                //$dataArray['chart'][$counter]['title'] = $anlagename;
                //$dataArray['chart'][$counter]['date'] = $stamp;
                //$dataArray['chart'][$counter]['time'] = $time;
                $dataArray['chart'][$counter]['irr'] = round($irr,2);
                $dataArray['chart'][$counter]['colorAC'] = $colorAC;
                $dataArray['chart'][$counter]['colorDC'] = $colorDC;
                $dataArray['chart'][$counter]['AC_kwh'] = $actPowerAC;
                $dataArray['chart'][$counter]['DC_kwh'] = $actPowerDC;
                $dataArray['chart'][$counter]['valueac'] = round((float)$przac,0);
                $dataArray['chart'][$counter]['valuedc'] = round((float)$przdc,0);
                ++$counter;
            }
          //  $dataArray['offsetLegend'] = 0;
        } else {
          //  $dataArray['offsetLegend'] = 0;
        }

        ($ACsum100 == NULL) ? $ACsum100 = '1':$ACsum100;
        ($ACsum95 == NULL) ? $ACsum95 = '1':$ACsum95;
        ($ACsum90 == NULL) ? $ACsum90 = '1':$ACsum90;
        ($DCsum100 == NULL) ? $DCsum100 = '1':$DCsum100;
        ($DCsum95 == NULL) ? $DCsum95 = '1':$DCsum95;
        ($DCsum90 == NULL) ? $DCsum90 = '1':$DCsum90;
        $ACsumall = round($ACsum100 + $ACsum95 + $ACsum90,2);
        $DCsumall = round($DCsum100 + $DCsum95 + $DCsum90,2);
        ($ACsumall == NULL) ? $ACsumall = '1':$ACsumall;
        ($DCsumall == NULL) ? $DCsumall = '1':$DCsumall;
        $ACprz90 = round($ACsum90 / $ACsumall * 100,2);
        $ACprz95 = round($ACsum95 / $ACsumall * 100,2);
        $ACprz100 = round($ACsum100 / $ACsumall * 100,2);
        $DCprz90 = round($DCsum90 / $DCsumall * 100,2);
        $DCprz95 = round($DCsum95 / $DCsumall * 100,2);
        $DCprz100 = round($DCsum100 / $DCsumall * 100,2);

        $tabelArray['tabel'][0]['Label'] = $filter;
        $tabelArray['tabel'][0]['ACsum'] =  "$ACsumall";
        $tabelArray['tabel'][0]['ACp90'] =  "$ACprz90";
        $tabelArray['tabel'][0]['ACp95'] =  "$ACprz95";
        $tabelArray['tabel'][0]['ACp100'] =  "$ACprz100";
        $tabelArray['tabel'][0]['ACsum90'] =  "$ACsum90";
        $tabelArray['tabel'][0]['ACsum95'] =  "$ACsum95";
        $tabelArray['tabel'][0]['ACsum100'] =  "$ACsum100";
        $tabelArray['tabel'][0]['DCsum'] =  "$DCsumall";
        $tabelArray['tabel'][0]['DCp90'] =  "$DCprz90";
        $tabelArray['tabel'][0]['DCp95'] =  "$DCprz95";
        $tabelArray['tabel'][0]['DCp100'] =  "$DCprz100";
        $tabelArray['tabel'][0]['DCsum90'] =  "$DCsum90";
        $tabelArray['tabel'][0]['DCsum95'] =  "$DCsum95";
        $tabelArray['tabel'][0]['DCsum100'] =  "$DCsum100";

        return [$dataArray, $tabelArray];
    }
}
