<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\ForcastDayRepository;
use App\Repository\ForcastRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use DateTime;
use PDO;
use App\Service\PdoService;

class ForecastChartService
{
    use G4NTrait;

    public function __construct(
private PdoService $pdoService,
        private ForcastRepository $forcastRepo,
        private ForcastDayRepository $forcastDayRepo,
        private InvertersRepository $invertersRepo,
        private FunctionsService $functions)
    {

    }

    public function getForecastFac(Anlage $anlage, $to): array
    {
        $actPerWeek = [];
        $dataArray = [];

        // FAC Date bzw letztes FAC Jahr berechnen
        $facDateForecast = clone $anlage->getFacDate();
        $facDateForecastMinusOneYear = clone $anlage->getFacDate();
        $facDateForecastMinusOneYear->modify('-1 Year');
        if ($facDateForecastMinusOneYear > self::getCetTime('object')) {
            $facDateForecast->modify('-1 Year');
            $facDateForecastMinusOneYear->modify('-1 Year');
        }
        $facWeek = $facDateForecast->format('W'); // Woche des FAC Datums

        /** @var [] AnlageForcast $forcasts */
        $forcasts = $this->forcastRepo->findBy(['anlage' => $anlage]);
        $forcastArray = [];
        // Kopiere alle Forcast Werte in ein Array mit dem Index der Kalenderwoche
        foreach ($forcasts as $forcast) {
            $forcastArray[$forcast->getWeek()] = $forcast;
        }

        $conn = $this->pdoService->getPdoPlant();
        $sql = 'SELECT (dayofyear(stamp)-mod(dayofyear(stamp),7))+1 AS startDayWeek, sum(e_z_evu) AS sumEvu  
                FROM '.$anlage->getDbNameAcIst()." 
                WHERE stamp BETWEEN '".$facDateForecastMinusOneYear->format('Y-m-d')."' AND '".$to."' AND unit = 1 GROUP BY (dayofyear(stamp)-mod(dayofyear(stamp),7)) 
                ORDER BY stamp;";
        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $actPerWeek[$value['startDayWeek']] = $value['sumEvu'];
        }
        $conn = null;

        $forecastValue = 0;
        $expectedWeek = 0;
        $divMinus = 0;
        $divPlus = 0;
        $week = $facWeek;
        $year = $facDateForecastMinusOneYear->format('Y');
        for ($counter = 1; $counter <= 52; ++$counter) {
            if ($week >= 52) {
                $week = 1;
                ++$year;
            } else {
                ++$week;
            }

            $stamp = strtotime($year.'W'.str_pad($forcastArray[$week]->getWeek(), 2, '0', STR_PAD_LEFT));

            if (isset($actPerWeek[$forcastArray[$week]->getDay()])) {
                $expectedWeek += $actPerWeek[$forcastArray[$week]->getDay()];
                $divMinus += $actPerWeek[$forcastArray[$week]->getDay()];
                $divPlus += $actPerWeek[$forcastArray[$week]->getDay()];
            } else {
                $expectedWeek += $forcastArray[$week]->getPowerWeek();
                $divMinus += $forcastArray[$week]->getDivMinWeek();
                $divPlus += $forcastArray[$week]->getDivMaxWeek();
            }
            $forecastValue += $forcastArray[$week]->getPowerWeek();

            $dataArray['chart'][] = [
                'date' => date('Y-m-d', $stamp),
                'forecast' => round($forecastValue),
                'expected' => round($expectedWeek),
                'divMinus' => round($divMinus),
                'divPlus' => round($divPlus),
            ];
        }

        return $dataArray;
    }

    public function getForecastClassic(Anlage $anlage, $to): array
    {
        $actPerWeek = [];
        $dataArray = [];

        $conn = $this->pdoService->getPdoPlant();
        $currentYear = date('Y', strtotime($to));
        if ($anlage->getShowEvuDiag()) {
            $sql = 'SELECT (dayofyear(stamp)-mod(dayofyear(stamp),7))+1 AS startDayWeek, sum(e_z_evu) AS sumEvu, sum(wr_pac) as sumInvOut  
                FROM '.$anlage->getDbNameAcIst()." 
                WHERE year(stamp) = '$currentYear' AND unit = 1 GROUP BY (dayofyear(stamp)-mod(dayofyear(stamp),7)) 
                ORDER BY stamp;";
        } else {
            $sql = 'SELECT (dayofyear(stamp)-mod(dayofyear(stamp),7))+1 AS startDayWeek, sum(e_z_evu) AS sumEvu, sum(wr_pac) as sumInvOut  
                FROM '.$anlage->getDbNameAcIst()." 
                WHERE year(stamp) = '$currentYear' GROUP BY (dayofyear(stamp)-mod(dayofyear(stamp),7)) 
                ORDER BY stamp;";
        }
        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            if ($anlage->getShowEvuDiag()) {
                if ($value['startDayWeek'] < date('z', strtotime($to))) {
                    $actPerWeek[$value['startDayWeek']] = $value['sumEvu'];
                }
            } else {
                if ($value['startDayWeek'] < date('z', strtotime($to))) {
                    $actPerWeek[$value['startDayWeek']] = $value['sumInvOut'];
                }
            }
        }
        $conn = null;

        /** @var [] AnlageForecast $forecasts */
        $forecasts = $this->forcastRepo->findBy(['anlage' => $anlage]);
        $counter = 0;
        $forecastValue = 0;
        $expectedWeek = 0;
        $divMinus = 0;
        $divPlus = 0;
        foreach ($forecasts as $forecast) {
            $year = date('Y', strtotime($to));
            $stamp = strtotime($year.'W'.str_pad($forecast->getWeek(), 2, '0', STR_PAD_LEFT));

            $dataArray['chart'][$counter]['date'] = date('Y-m-d', $stamp);

            if (isset($actPerWeek[$forecast->getDay()])) {
                $expectedWeek += $actPerWeek[$forecast->getDay()];
                $divMinus += $actPerWeek[$forecast->getDay()];
                $divPlus += $actPerWeek[$forecast->getDay()];
            } else {
                $expectedWeek += $forecast->getPowerWeek();
                $divMinus += $forecast->getDivMinWeek();
                $divPlus += $forecast->getDivMaxWeek();
            }
            $forecastValue += $forecast->getPowerWeek();
            $dataArray['chart'][$counter]['forecast'] = round($forecastValue);
            $dataArray['chart'][$counter]['expected'] = round($expectedWeek);
            $dataArray['chart'][$counter]['divMinus'] = round($divMinus);
            $dataArray['chart'][$counter]['divPlus'] = round($divPlus);
            ++$counter;
        }

        return $dataArray;
    }

    // ###########
    // # By Day ##
    // ###########

    public function getForecastDayClassic(Anlage $anlage, $to): array
    {
        $actPerDay = [];
        $dataArray = [];

        $form = '%y%m%d';

        $conn = $this->pdoService->getPdoPlant();
        $currentYear = date('Y', strtotime($to));
        if ($anlage->getShowEvuDiag()) {
            $sql = "SELECT date_format(stamp, '%j') AS startDay, sum(e_z_evu) AS sumEvu, sum(wr_pac) as sumInvOut  
                FROM ".$anlage->getDbNameAcIst()." 
                WHERE year(stamp) = '$currentYear' AND unit = 1 GROUP BY date_format(stamp, '$form') 
                ORDER BY stamp;";
        } else {
            $sql = "SELECT date_format(stamp, '%j') AS startDay, sum(e_z_evu) AS sumEvu, sum(wr_pac) as sumInvOut  
                FROM ".$anlage->getDbNameAcIst()." 
                WHERE year(stamp) = '$currentYear' GROUP BY date_format(stamp, '$form')
                ORDER BY stamp;";
        }
        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            if ($anlage->getShowEvuDiag()) {
                if ($value['startDay'] < date('z', strtotime($to))) {
                    $actPerDay[(int) $value['startDay']] = round($value['sumEvu'], 2);
                }
            } else {
                if ($value['startDay'] < date('z', strtotime($to))) {
                    $actPerDay[(int) $value['startDay']] = round($value['sumInvOut'], 2);
                }
            }
        }
        $conn = null;

        /** @var [] AnlageForecastDay $forecasts */
        $forecasts = $this->forcastDayRepo->findBy(['anlage' => $anlage]);
        $counter = 0;
        $forecastValue = 0;
        $expectedDay = 0;
        $divMinus = 0;
        $divPlus = 0;
        foreach ($forecasts as $count => $forecast) {
            $year = date('Y', strtotime($to));
            $stamp = DateTime::createFromFormat('Y z', $year.' '.$forecast->getDay()-1);
            $dataArray['chart'][$counter]['date'] = $stamp->format('Y-m-d');

            if (isset($actPerDay[$forecast->getDay()])) {
                $expectedDay += $actPerDay[$forecast->getDay()];
                $divMinus += $actPerDay[$forecast->getDay()];
                $divPlus += $actPerDay[$forecast->getDay()];
            } else {
                $expectedDay += $forecast->getPowerDay();
                $divMinus += $forecast->getDivMinDay();
                $divPlus += $forecast->getDivMaxDay();
            }
            $forecastValue += $forecast->getPowerDay();
            $dataArray['chart'][$counter]['forecast'] = round($forecastValue);
            $dataArray['chart'][$counter]['expected'] = round($expectedDay);
            $dataArray['chart'][$counter]['divMinus'] = round($divMinus);
            $dataArray['chart'][$counter]['divPlus'] = round($divPlus);
            ++$counter;
        }
        return $dataArray;
    }

    // ################
    // ## By Day PR  ##
    // ################

    public function getForecastDayPr(Anlage $anlage, $to): array
    {
        $actPerDay = [];
        $dataArray = [];

        $form = '%y%m%d';

        $conn = $this->pdoService->getPdoPlant();
        $currentYear = date('Y', strtotime($to));
        if ($anlage->getShowEvuDiag()) {
            $sql = "SELECT date_format(stamp, '%j') AS startDay, sum(e_z_evu) AS sumEvu, sum(wr_pac) as sumInvOut  
                FROM ".$anlage->getDbNameAcIst()." 
                WHERE year(stamp) = '$currentYear' AND unit = 1 GROUP BY date_format(stamp, '$form') 
                ORDER BY stamp;";
        } else {
            $sql = "SELECT date_format(stamp, '%j') AS startDay, sum(e_z_evu) AS sumEvu, sum(wr_pac) as sumInvOut  
                FROM ".$anlage->getDbNameAcIst()." 
                WHERE year(stamp) = '$currentYear' GROUP BY date_format(stamp, '$form')
                ORDER BY stamp;";
        }
        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            if ($anlage->getShowEvuDiag()) {
                if ($value['startDay'] < date('z', strtotime($to))) {
                    $actPerDay[(int) $value['startDay']] = round($value['sumEvu'], 2);
                }
            } else {
                if ($value['startDay'] < date('z', strtotime($to))) {
                    $actPerDay[(int) $value['startDay']] = round($value['sumInvOut'], 2);
                }
            }
        }
        $conn = null;

        /** @var [] AnlageForecastDay $forecasts */
        $forecasts = $this->forcastDayRepo->findBy(['anlage' => $anlage]);
        $counter = 0;
        $forecastValue = 0;
        $expectedDay = 0;
        $divMinus = 0;
        $divPlus = 0;

        foreach ($forecasts as $count => $forecast) {
            $year = date('Y', strtotime($to));
            $stamp = DateTime::createFromFormat('Y z', $year.' '.$forecast->getDay()-1);
            $dataArray['chart'][$counter]['date'] = $stamp->format('Y-m-d');

            if (isset($actPerDay[$forecast->getDay()])) {
                $expectedDay += $actPerDay[$forecast->getDay()];
                $divMinus += $actPerDay[$forecast->getDay()];
                $divPlus += $actPerDay[$forecast->getDay()];
            } else {
                $expectedDay += $forecast->getPowerDay();
                $divMinus += $forecast->getDivMinDay();
                $divPlus += $forecast->getDivMaxDay();
            }

            $forecastValue += $forecast->getPowerDay();

            $dataArray['chart'][$counter]['prKumuliert'] = $forecast->getPrKumuliert();
            $dataArray['chart'][$counter]['prDay'] = $forecast->getPrDay();
            $dataArray['chart'][$counter]['prKumuliertFt'] = $forecast->getPrKumuliertFt();
            $dataArray['chart'][$counter]['forecast'] = round($forecastValue);
            ++$counter;
        }

        return $dataArray;
    }
}
