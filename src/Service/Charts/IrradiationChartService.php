<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use PDO;

class IrradiationChartService
{
    use G4NTrait;

    private FunctionsService $functions;
    private InvertersRepository $invertersRep;

    public function __construct(FunctionsService $functions,
                                InvertersRepository $invertersRep
                            )
    {
        $this->functions = $functions;
        $this->invertersRep = $invertersRep;
    }


    /**
     * Erzeugt Daten für das Strahlungsdiagramm Diagramm
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param string $mode
     * @return array
     *  // irradiation
     */
    public function getIrradiation(Anlage $anlage, $from, $to,?string $mode = 'all',  ?bool $hour = false): array
    {
        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $conn = self::connectToDatabase();
        $dataArray = [];
        if($hour)$sql2 = "SELECT a.stamp, sum(b.gi_avg)  as gi, sum(b.gmod_avg) as gmod FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP BY date_format(a.stamp, '$form')";
        else $sql2 = "SELECT a.stamp, b.gi_avg as gi , b.gmod_avg as gmod FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP BY date_format(a.stamp, '$form')";

        $res = $conn->query($sql2);
        dump($res->num_rows);
        if ($res->num_rows > 0) {
            $counter = 0;
            while ($ro = $res->fetch_assoc()) {
                // upper pannel
                $irr_upper = (float)str_replace(',', '.', $ro["gmod"]);
                if($hour) $irr_upper = $irr_upper/4;
                dump($irr_upper);
                if (!$irr_upper) $irr_upper = 0;
                // lower pannel
                $irr_lower = (float)str_replace(',', '.', $ro["gi"]);
                if($hour) $irr_lower = $irr_lower/4;
                if (!$irr_lower) $irr_lower = 0;
                $stamp = self::timeAjustment(strtotime($ro["stamp"]), (int)$anlage->getAnlZeitzoneIr());
                if ($anlage->getAnlIrChange() == "Yes") {
                    $swap = $irr_lower;
                    $irr_lower = $irr_upper;
                    $irr_upper = $swap;
                }
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]["date"] = self::timeShift($anlage, $stamp);
                if (!($irr_upper+$irr_lower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    switch ($mode) {
                        case 'all':
                            $dataArray['chart'][$counter]["val1"] = $irr_upper; // upper pannel
                            $dataArray['chart'][$counter]["val2"] = $irr_lower; // lower pannel
                            break;
                        case 'upper':
                            $dataArray['chart'][$counter]["val1"] = $irr_upper; // upper pannel
                            break;
                        case 'lower':
                            $dataArray['chart'][$counter]["val1"] = $irr_lower; // upper pannel
                            break;
                    }
                }
                $counter++;
            }
        }
        $conn = null;

        return $dataArray;
    }


    /**
     * Erzeuge Daten für die Stralung die direlt von der Anlage geliefert wir
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array|false
     *  // irradiation_plant
     */
    public function getIrradiationPlant(Anlage $anlage, $from, $to, bool $hour): array
    {

        if($hour) $form = '%y%m%d%H';
        else $form = '%y%m%d%H%i';
        $conn = self::getPdoConnection();
        $conn = self::connectToDatabase();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        // Strom für diesen Zeitraum und diesen Inverter
        if($hour) $sql_irr_plant = "SELECT a.stamp as stamp, (b.irr_anlage) AS irr_anlage FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameIst() . ") b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' group by date_format(a.stamp, '$form');";

        else $sql_irr_plant = "SELECT a.stamp as stamp, b.irr_anlage AS irr_anlage FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameIst() . ") b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' group by date_format(a.stamp, '$form');";
       dump($sql_irr_plant);
        $result = $conn->query($sql_irr_plant);
        if ($result != false) {
            if ($result->num_rows > 0) {
                $counter = 0;
                while ($row = $result->fetch_assoc()) {
                    $stamp = self::timeAjustment($row['stamp'], (int)$anlage->getAnlZeitzone(), true);
                    $stamp2 = self::timeAjustment($stamp, 1);
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);

                    if($hour) $sqlWeather = "SELECT * FROM " . $anlage->getDbNameWeather() . " WHERE stamp >= '$stamp' AND stamp < '$stamp2' group by date_format(stamp, '$form')";

                    else $sqlWeather = "SELECT * FROM " . $anlage->getDbNameWeather() . " WHERE stamp = '$stamp' group by date_format(stamp, '$form')";
                        //SELECT * FROM pvp_data.db__pv_ws_CX104  WHERE stamp >= '2021-10-27 09:00:00' AND stamp < '2021-10-27 10:00:00' group by date_format(stamp, '%y%m%d%H');
                    dump($sqlWeather);
                    $resultWeather = $conn->query($sqlWeather);

                    if ($resultWeather->num_rows == 1) {
                        $weatherRow = $resultWeather->fetch_assoc();
                        if ($anlage->getIsOstWestAnlage()) {
                            $dataArray['chart'][$counter]['g4n'] = (float)(($weatherRow["g_upper"] * $anlage->getPowerEast() + $weatherRow["g_lower"] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()));
                        } else {
                            if ($anlage->getWeatherStation()->getChangeSensor() == "Yes") {
                                dump((float)$weatherRow["g_lower"]);
                                $dataArray['chart'][$counter]['g4n'] = (float)$weatherRow["g_lower"]; // getauscht, nutze unterene Sensor
                            } else {
                                dump((float)$weatherRow["g_upper"]);
                                $dataArray['chart'][$counter]['g4n'] = (float)$weatherRow["g_upper"]; // nicht getauscht, nutze oberen Sensor
                            }
                        }
                    } else {
                        if (!(self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            $dataArray['chart'][$counter]['g4n'] = 0;
                        }
                    }

                    if($hour)$irrAnlageJson = $row['irr_anlage'];
                    else $irrAnlageJson = $row['irr_anlage'];
                    if ($irrAnlageJson != '') {

                        $irrAnlageArray = json_decode($irrAnlageJson);
                        $irrCounter = 1;
                        foreach ($irrAnlageArray as $irrAnlageItem => $irrAnlageValue) {
                            if (!($irrAnlageValue == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                if (!isset($irrAnlageValue)) $irrAnlageValue = 0;
                                $dataArray['chart'][$counter]["val$irrCounter"] = round(($irrAnlageValue < 0) ? 0 : $irrAnlageValue, 0);
                                if (!isset($dataArray["nameX"][$irrCounter])) $dataArray["nameX"][$irrCounter] = $irrAnlageItem;
                            }
                            if ($irrCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $irrCounter;
                            $irrCounter++;
                        }
                    }
                    $counter++;
                }
            }
        }
        $conn = null;

        return $dataArray;
    }
}