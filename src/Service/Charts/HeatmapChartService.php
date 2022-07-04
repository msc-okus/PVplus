<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Entity\AnlagenStatus;
use App\Helper\G4NTrait;
use App\Repository\AnlagenStatusRepository;
use App\Repository\InvertersRepository;
use App\Service\WeatherServiceNew;
use App\Service\Charts\{DCPowerChartService, ACPowerChartsService};
use App\Service\FunctionsService;
use PDO;
use Symfony\Component\Security\Core\Security;
use ContainerXGGeorm\getConsole_ErrorListenerService;

class HeatmapChartService
{
    use G4NTrait;
    private Security $security;
    private AnlagenStatusRepository $statusRepository;
    private InvertersRepository $invertersRepo;
    public functionsService $functions;
    private IrradiationChartService $irradiationChart;
    private WeatherServiceNew $weatherService;

    public function __construct(Security                $security,
                                AnlagenStatusRepository $statusRepository,
                                InvertersRepository     $invertersRepo,
                                IrradiationChartService $irradiationChart,
                                DCPowerChartService     $DCPowerChartService,
                                ACPowerChartsService    $ACPowerChartService,
                                WeatherServiceNew       $weatherService,
                                FunctionsService        $functions)
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
    #Help Function for Array search
    #MS
    static function array_recursive_search_key_map($needle, $haystack) {
        foreach($haystack as $first_level_key=>$value) {
            if ($needle === $value) {
                return array($first_level_key);
            } elseif (is_array($value)) {
                $callback = self::array_recursive_search_key_map($needle, $value);
                if ($callback) {
                    return array_merge(array($first_level_key), $callback);
                }
            }
        }
        return false;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @param bool $hour
     * @return array
     * [Heatmap]
     */
    //MS 05/2022
    public function getHeatmap(Anlage $anlage, $from, $to,  bool $hour = false): ?array
    {
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $dataArray = [];
        $group = 1;
        $anlagename = $anlage->getAnlName();
        $pnominverter = $anlage->getPnomInverterArray();
        $counter = 0;

        $gmt_offset = 1;   // Unterschied von GMT zur eigenen Zeitzone in Stunden.
        $zenith = 90+50/60;

        $current_date = strtotime($from);
        $sunset = date_sunset($current_date, SUNFUNCS_RET_TIMESTAMP,  (float)$anlage->getAnlGeoLat(), (float)$anlage->getAnlGeoLon(), $zenith, $gmt_offset);
        $sunrise = date_sunrise($current_date, SUNFUNCS_RET_TIMESTAMP,  (float)$anlage->getAnlGeoLat(), (float)$anlage->getAnlGeoLon(), $zenith, $gmt_offset);

        if ($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';

       // $sunArray = $this->WeatherServiceNew->getSunrise($anlage,$from);
       // $sunrise = $sunArray[$anlagename]['sunrise'];
       // $sunset = $sunArray[$anlagename]['sunset'];

        $from = date('Y-m-d H:00',$sunrise - 3600);
        $to = date('Y-m-d H:00',$sunset+ 5400);

        $conn = self::getPdoConnection();
        $dataArray = [];
        $inverterNr = 0;
        switch ($anlage->getConfigType()) {
            case 3:

            case 4:
                $nameArray = $this->functions->getNameArray($anlage, 'ac');
                $group = "group_ac";
                 break;
            default:
                $nameArray = $this->functions->getNameArray($anlage, 'dc');
                $group = "group_dc";
        }

            $sql = "SELECT wr_pac as istPower,$group as group_dc,date_format(a.stamp, '%Y-%m-%d% %H:%i') as ts 
                                    FROM (db_dummysoll a LEFT JOIN  " . $anlage->getDbNameACIst() . " b ON a.stamp = b.stamp)
                                    WHERE a.stamp BETWEEN '$from' AND '$to' 
                                    GROUP BY a.stamp, b.$group";

        $resultActual = $conn->query($sql);

        $dataArray['inverterArray'] = $nameArray;

        if ($resultActual->rowCount() > 0) {

            if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false || $anlage->getUseCustPRAlgorithm() == "Groningen") {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'upper', $hour);
            } else {
                $dataArrayIrradiation = $this->irradiationChart->getIrradiation($anlage, $from, $to, 'all', $hour);
            }

            $dataArray['maxSeries'] = 0;
            $counter = 0;
            $counterInv = 1;

            while ( $rowActual = $resultActual->fetch(PDO::FETCH_ASSOC)) {

                $stamp = $rowActual["ts"];
                $stampAd = date('Y-m-d H:i',strtotime(self::timeAjustment( $stamp, $anlage->getAnlZeitzoneWs())));

                // Find Key in Array
                $keys = self::array_recursive_search_key_map( $stampAd, $dataArrayIrradiation);

                // fetch Irradiation
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false) {
                    $key = $keys[1];
                    $dataIrr = $dataArrayIrradiation['chart'][$key]['val1'];
                } else {
                    $key = $keys[1];
                    $dataIrr = ($dataArrayIrradiation['chart'][$key]['val1'] + $dataArrayIrradiation['chart'][$key]['val2']) / 2;
                }

                $e = explode(" ", $stamp);
                $dataArray['chart'][$counter]['ydate'] = $e[1];

                    $powerist = $rowActual['istPower'];

                    if($powerist != null) $poweristkwh = $powerist * (float)4;
                    else $poweristkwh = 0;

                        $pnomkwh = $pnominverter[$rowActual['group_dc']] / (float)1000;
                        if ($dataIrr > 10) {
                            $theoreticalIRR = ($dataIrr / (float)1000) * $pnomkwh;
                            if ($poweristkwh == 0 or $theoreticalIRR == 0) {
                                $value = 0;
                            } else {
                                $value = round(($poweristkwh / $theoreticalIRR) * (float)100);
                              }
                        } else {
                                $value = 0;
                        }

                        $value = ($value > (float)100) ? (float)100: $value;
                        $dataArray['chart'][$counter]['xinv'] = $nameArray[$rowActual['group_dc']] ;
                        $dataArray['chart'][$counter]['value'] =  $value ;
                        /*
                        $dataArray['chart'][$counter]['irr'] =  $dataIrr;
                        $dataArray['chart'][$counter]['thirr'] =  $theoreticalIRR;
                        $dataArray['chart'][$counter]['pnomkwh'] =  $pnomkwh;
                        $dataArray['chart'][$counter]['ist'] =  $powerist ;
                        $dataArray['chart'][$counter]['istkwh'] =  $poweristkwh ;
                        */
                $counter++;
            }
            $dataArray['offsetLegend'] = 0;
        }
       # dd(print_r($dataArray));
        return $dataArray;
    }
}