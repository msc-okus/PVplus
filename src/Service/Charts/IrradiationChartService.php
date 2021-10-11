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
    public function getIrradiation(Anlage $anlage, $from, $to, string $mode = 'all'): array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $sql2 = "SELECT a.stamp, b.gi_avg , b.gmod_avg  FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' ORDER BY a.stamp";
        $res = $conn->query($sql2);
        if ($res->rowCount() > 0) {
            $counter = 0;
            while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
                // upper pannel
                $irr_upper = (float)str_replace(',', '.', $ro["gmod_avg"]);
                if (!$irr_upper) $irr_upper = 0;
                // lower pannel
                $irr_lower = (float)str_replace(',', '.', $ro["gi_avg"]);
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
    public function getIrradiationPlant(Anlage $anlage, $from, $to): array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        // Strom für diesen Zeitraum und diesen Inverter
        $sql_irr_plant = "SELECT a.stamp as stamp, b.irr_anlage AS irr_anlage FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameIst() . ") b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' group by a.stamp;";
        $result = $conn->query($sql_irr_plant);
        if ($result != false) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = self::timeAjustment($row['stamp'], (int)$anlage->getAnlZeitzone(), true);
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                    $sqlWeather = "SELECT * FROM " . $anlage->getDbNameWeather() . " WHERE stamp = '$stamp'";
                    $resultWeather = $conn->query($sqlWeather);
                    if ($resultWeather->rowCount() == 1) {
                        $weatherRow = $resultWeather->fetch(PDO::FETCH_ASSOC);
                        if ($anlage->getIsOstWestAnlage()) {
                            $dataArray['chart'][$counter]['g4n'] = (float)(($weatherRow["g_upper"] * $anlage->getPowerEast() + $weatherRow["g_lower"] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()));
                        } else {
                            if ($anlage->getWeatherStation()->getChangeSensor() == "Yes") {
                                $dataArray['chart'][$counter]['g4n'] = (float)$weatherRow["g_lower"]; // getauscht, nutze unterene Sensor
                            } else {
                                $dataArray['chart'][$counter]['g4n'] = (float)$weatherRow["g_upper"]; // nicht getauscht, nutze oberen Sensor
                            }
                        }
                    } else {
                        if (!(self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            $dataArray['chart'][$counter]['g4n'] = 0;
                        }
                    }
                    $irrAnlageJson = $row['irr_anlage'];
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