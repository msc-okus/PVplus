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

class SollIstIrrAnalyseChartService
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
     * @param $filter
     * @param int $group
     *
     * @return array
     */
     // MS 10 / 2022
    public function getSollIstIrrDeviationAnalyse(Anlage $anlage, $from, $to, $filter, bool $hour = false): ?array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        $anlagename = $anlage->getAnlName();
        $conn = self::getPdoConnection();
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
            default:
                $irr_from = '0';
                $irr_to =  '400';
                $filter = '400';
        }

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
                sum(c.wr_pac) as act_power_ac,sum(c.wr_pdc) as act_power_dc,
                c.wr_temp as wr_temp,w.g_upper,w.g_lower,(w.g_upper + w.g_lower) / 2 as avg_irr,
                CASE
                WHEN ROUND((sum(c.wr_pac) / sum(b.ac_exp_power) * 100),0) IS NULL THEN '0'
                WHEN ROUND((sum(c.wr_pac) / sum(b.ac_exp_power) * 100),0) > 100 THEN '100'
                ELSE ROUND((sum(c.wr_pac) / sum(b.ac_exp_power) * 100),0)
                END AS przac,
				CASE
                WHEN ROUND((sum(c.wr_pdc) / sum(b.dc_exp_power) * 100),0) IS NULL THEN '0'
                WHEN ROUND((sum(c.wr_pdc) / sum(b.dc_exp_power) * 100),0) > 100 THEN '100'
                ELSE ROUND((sum(c.wr_pdc) / sum(b.dc_exp_power) * 100),0)
                END AS przdc
                FROM pvp_data.db_dummysoll a 
                LEFT JOIN ".$anlage->getDbNameDcSoll().' b ON a.stamp = b.stamp 
                LEFT JOIN '.$anlage->getDbNameWeather().' w ON a.stamp = w.stamp 
                LEFT JOIN '.$anlage->getDbNameACIst()." c ON a.stamp = c.stamp 
                WHERE a.stamp BETWEEN '$from' AND '$to' 
                AND (w.g_upper + w.g_lower) / 2 > '$irr_from'
                AND (w.g_upper + w.g_lower) / 2 < '$irr_to'
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
                $actPowerAC = $rowActual['act_power_ac'];
                $actPowerAC = $actPowerAC > 0 ? round(self::checkUnitAndConvert($actPowerAC, $anlage->getAnlDbUnit()), 2) : 0; // neagtive Werte auschließen
                $actPowerDC = $rowActual['act_power_dc'];
                $actPowerDC = $actPowerDC > 0 ? round(self::checkUnitAndConvert($actPowerDC, $anlage->getAnlDbUnit()), 2) : 0; // neagtive Werte auschließen
                $przac = $rowActual['przac'];
                $przdc = $rowActual['przdc'];
                $irr = $rowActual['avg_irr'];
                switch (TRUE) {
                    case ($przac >= 95 and $przac <= 100);
                    $colorAC = "#009900";
                    $ACsum100 += $actPowerAC;
                    break;
                    case ($przac >= 90 and $przac <= 94);
                    $colorAC = "#ffff00";
                    $ACsum95 += $actPowerAC;
                    break;
                    case ($przac > 0 and $przac <= 89);
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
                    case ($przdc >= 90 and $przdc <= 94);
                    $DCsum95 += $actPowerDC;
                    $colorDC = "#ffff00";
                    break;
                    case ($przdc > 0 and $przdc <= 89);
                    $DCsum90 += $actPowerDC;
                    $colorDC = "#ff0000";
                    break;
                    default:
                    $colorDC = "#0DD00";
                }

                $dataArray['maxSeries'] = $maxInverter;
                $dataArray['chart'][$counter]['title'] = $anlagename;
                $dataArray['chart'][$counter]['date'] = $stamp;
                $dataArray['chart'][$counter]['time'] = $time;
                $dataArray['chart'][$counter]['irr'] = round($irr,2);
                $dataArray['chart'][$counter]['colorAC'] = $colorAC;
                $dataArray['chart'][$counter]['colorDC'] = $colorDC;
                $dataArray['chart'][$counter]['AC_kwh'] = (float)$actPowerAC;
                $dataArray['chart'][$counter]['DC_kwh'] = (float)$actPowerDC;
                $dataArray['chart'][$counter]['valueac'] = round((float)$przac,0);
                $dataArray['chart'][$counter]['valuedc'] = round((float)$przdc,0);
                ++$counter;
            }
            $dataArray['offsetLegend'] = 0;
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

        return array($dataArray,$tabelArray);
    }
}
