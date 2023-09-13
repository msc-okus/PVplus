<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use PDO;
use App\Service\PdoService;

class IrradiationChartService
{
    use G4NTrait;

    public function __construct(
        private $host,
        private $userPlant,
        private $passwordPlant,
        private FunctionsService $functions,
        private InvertersRepository $invertersRep
    )
    {
    }

    /**
     * Erzeugt Daten für das Strahlungs Diagramm.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param string|null $mode
     * @param bool|null $hour
     * @return array
     * @throws \Exception
     */
    public function getIrradiation(Anlage $anlage, $from, $to, ?string $mode = 'all', ?bool $hour = false): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        if ($hour) {
            $sql2 = 'SELECT a.stamp, sum(b.g_lower) as g_lower, sum(b.g_upper) as g_upper FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP BY date_format(a.stamp, '$form')";
        } else {
            $sql2 = 'SELECT a.stamp, b.g_lower as g_lower , b.g_upper as g_upper FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP BY date_format(a.stamp, '$form')";
        }

        $res = $conn->query($sql2);
        if ($res->rowCount() > 0) {
            $counter = 0;
            while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
                // upper pannel
                $irr_upper = (float) str_replace(',', '.', $ro['g_upper']);
                if ($hour) $irr_upper = $irr_upper / 4;
                if ($ro['g_upper'] = "") $irr_upper = null;

                // lower pannel
                $irr_lower = (float) str_replace(',', '.', $ro['g_lower']);
                if ($hour) $irr_lower = $irr_lower / 4;
                if ($ro['g_lower'] = "") $irr_lower = null;

                $stamp = self::timeAjustment(strtotime($ro['stamp']), $anlage->getWeatherStation()->gettimeZoneWeatherStation());
                if ($anlage->getWeatherStation()->getChangeSensor() == 'Yes') {
                    $swap = $irr_lower;
                    $irr_lower = $irr_upper;
                    $irr_upper = $swap;
                }
                // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                if (!($irr_upper + $irr_lower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    switch ($mode) {
                        case 'all':
                            $dataArray['chart'][$counter]['val1'] = $irr_upper > 0 ? $irr_upper: 0; // upper pannel
                            $dataArray['chart'][$counter]['val2'] = $irr_lower > 0 ? $irr_lower : 0; // lower pannel
                            break;
                        case 'upper':
                            $dataArray['chart'][$counter]['val1'] = $irr_upper > 0 ? $irr_upper: 0; // upper pannel
                            break;
                        case 'lower':
                            $dataArray['chart'][$counter]['val1'] = $irr_lower > 0 ? $irr_lower : 0; // upper pannel
                            break;
                    }
                }
                ++$counter;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeuge Daten für die Strahlung die direkt von der Anlage geliefert wir.
     *
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param bool $hour
     * @return array
     * @throws \Exception
     */
    public function getIrradiationPlant(Anlage $anlage, $from, $to, bool $hour): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $form = $hour ? '%y%m%d%H' : '%y%m%d%H%i';
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        // Strom für diesen Zeitraum und diesen Inverter
        if ($hour) {
            $sql_irr_plant = 'SELECT a.stamp as stamp, (b.irr_anlage) AS irr_anlage FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameIst().") b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' group by date_format(a.stamp, '$form');";
        } else {
            $sql_irr_plant = 'SELECT a.stamp as stamp, b.irr_anlage AS irr_anlage FROM (db_dummysoll a left JOIN (SELECT * FROM '.$anlage->getDbNameIst().") b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' group by date_format(a.stamp, '$form');";
        }
        $result = $conn->query($sql_irr_plant);
        if ($result) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = self::timeAjustment($row['stamp'], (int) $anlage->getAnlZeitzone(), true);
                    $stamp2 = self::timeAjustment($stamp, 1);
                    // Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);

                    if ($hour) {
                        $sqlWeather = 'SELECT * FROM '.$anlage->getDbNameWeather()." WHERE stamp >= '$stamp' AND stamp < '$stamp2' group by date_format(stamp, '$form')";
                    } else {
                        $sqlWeather = 'SELECT * FROM '.$anlage->getDbNameWeather()." WHERE stamp = '$stamp' group by date_format(stamp, '$form')";
                    }
                    $resultWeather = $conn->query($sqlWeather);

                    if ($resultWeather->rowCount() == 1) {
                        $weatherRow = $resultWeather->fetch(PDO::FETCH_ASSOC);
                        if ($anlage->getIsOstWestAnlage()) {
                            $dataArray['chart'][$counter]['g4n'] = (((float) $weatherRow['g_upper'] * $anlage->getPowerEast() + (float) $weatherRow['g_lower'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()));
                            if ($dataArray['chart'][$counter]['g4n'] < 0) $dataArray['chart'][$counter]['g4n'] = 0;
                        } else {
                            if ($anlage->getWeatherStation()->getChangeSensor() == 'Yes') {
                                $dataArray['chart'][$counter]['g4n'] = (float) $weatherRow['g_lower']; // getauscht, nutze unterene Sensor
                            } else {
                                $dataArray['chart'][$counter]['g4n'] = (float) $weatherRow['g_upper']; // nicht getauscht, nutze oberen Sensor
                            }
                        }
                    } else {
                        if (!(self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            $dataArray['chart'][$counter]['g4n'] = null;
                        }
                    }

                    if ($row['irr_anlage'] != '') {
                        $irrAnlageArray = json_decode($row['irr_anlage']);
                        $irrCounter = 1;
                        foreach ($irrAnlageArray as $irrAnlageItem => $irrAnlageValue) {
                            if (!($irrAnlageValue == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                if (!isset($irrAnlageValue) or is_array($irrAnlageValue)) {
                                    $irrAnlageValue = 0;
                                }
                                $dataArray['chart'][$counter]["val$irrCounter"] = round(max($irrAnlageValue, 0), 2);
                                if (!isset($dataArray['nameX'][$irrCounter])) {
                                    $dataArray['nameX'][$irrCounter] = $irrAnlageItem;
                                }
                            }
                            if ($irrCounter > $dataArray['maxSeries']) {
                                $dataArray['maxSeries'] = $irrCounter;
                            }
                            ++$irrCounter;
                        }
                    }
                    ++$counter;
                }
            }
        }
        $conn = null;

        return $dataArray;
    }
}
