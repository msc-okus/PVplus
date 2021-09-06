<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\ForecastRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use PDO;

class ForecastChartService
{
    use G4NTrait;

    private ForecastRepository $forecastRepo;
    private InvertersRepository $invertersRepo;
    private FunctionsService $functions;

    public function __construct(
        ForecastRepository $forecastRepo,
        InvertersRepository $invertersRepo,
        FunctionsService $functions)
    {

        $this->forecastRepo = $forecastRepo;
        $this->invertersRepo = $invertersRepo;
        $this->functions = $functions;
    }

    public function getForecastFac(Anlage $anlage, $to):array
    {
        $actPerWeek = [];
        $dataArray = [];
        /**/
        //FAC Date bzw letztes FAC Jahr berechnen
        $facDateForecast = clone $anlage->getFacDate();
        $facDateForecastMinusOneYear = clone $anlage->getFacDate();
        $facDateForecastMinusOneYear->modify('-1 Year');
        if ($facDateForecastMinusOneYear > self::getCetTime('object')) { //
            $facDateForecast->modify('-1 Year');
            $facDateForecastMinusOneYear->modify('-1 Year');
        }
        $facWeek = $facDateForecast->format('W'); // Woche des FAC Datums

        /** @var [] AnlageForecast $forecasts */
        $forecasts = $this->forecastRepo->findBy(['anlage' => $anlage]);
        $forecastArray = [];
        //Kopiere alle Forcast Werte in ein Array mit dem Index der Kalenderwoche
        foreach ($forecasts as $forecast) {
            $forecastArray[$forecast->getWeek()] = $forecast;
        }

        $conn = self::getPdoConnection();
        $sql = "SELECT (dayofyear(stamp)-mod(dayofyear(stamp),7))+1 AS startDayWeek, sum(e_z_evu) AS sumEvu  
                FROM " . $anlage->getDbNameAcIst() . " 
                WHERE stamp BETWEEN '" . $facDateForecastMinusOneYear->format('Y-m-d') . "' AND '" . $to . "' AND unit = 1 GROUP BY (dayofyear(stamp)-mod(dayofyear(stamp),7)) 
                ORDER BY stamp;";
        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $actPerWeek[$value['startDayWeek']] = $value['sumEvu'];
        }
        $conn = null;

        $forecastValue  = 0;
        $expectedWeek   = 0;
        $divMinus       = 0;
        $divPlus        = 0;
        $week = $facWeek;
        $year = $facDateForecastMinusOneYear->format('Y');
        for ($counter = 1; $counter <=52; $counter++) {
            if ($week >= 52) {
                $week = 1;
                $year++;
            } else {
                $week++;
            }

            $stamp = strtotime($year . 'W' . str_pad($forecastArray[$week]->getWeek(), 2, '0', STR_PAD_LEFT));
            //$dataArray['chart'][$counter]['date'] = date('Y-m-d', $stamp);

            if (isset($actPerWeek[$forecastArray[$week]->getDay()])) {
                $expectedWeek += $actPerWeek[$forecastArray[$week]->getDay()];
                $divMinus += $actPerWeek[$forecastArray[$week]->getDay()];
                $divPlus += $actPerWeek[$forecastArray[$week]->getDay()];
            } else {
                $expectedWeek += $forecastArray[$week]->getFactorWeek() * $anlage->getContractualPower();
                $divMinus += $forecastArray[$week]->getFactorWeek() * $anlage->getContractualPower() * $forecast->getFactorMin();
                $divPlus += $forecastArray[$week]->getFactorWeek() * $anlage->getContractualPower() * $forecast->getFactorMax();
            }
            $forecastValue += $forecastArray[$week]->getFactorWeek() * $anlage->getContractualPower();
            $dataArray['chart'][] = [
                'date'      => date('Y-m-d', $stamp),
                'forecast'  => $forecastValue,
                'expected'  => $expectedWeek,
                'divMinus'  => $divMinus,
                'divPlus'   => $divPlus,
            ];

        }

        return $dataArray;
    }

    public function getForecastClassic(Anlage $anlage, $to):array
    {
        $actPerWeek = [];
        $dataArray = [];

        $conn = self::getPdoConnection();
        $currentYear = date('Y', strtotime($to));
        $sql = "SELECT (dayofyear(stamp)-mod(dayofyear(stamp),7))+1 AS startDayWeek, sum(e_z_evu) AS sumEvu  
                FROM ".$anlage->getDbNameAcIst()." 
                WHERE year(stamp) = '$currentYear' AND unit = 1 GROUP BY (dayofyear(stamp)-mod(dayofyear(stamp),7)) 
                ORDER BY stamp;";
        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value){
            if ($value['startDayWeek'] < date('z', strtotime($to))) $actPerWeek[$value['startDayWeek']] = $value['sumEvu'];
        }
        $conn = null;

        /** @var [] AnlageForecast $forecasts */
        $forecasts = $this->forecastRepo->findBy(['anlage' => $anlage]);
        $counter = 0;
        $forecastValue  = 0;
        $expectedWeek   = 0;
        $divMinus       = 0;
        $divPlus        = 0;
        foreach ($forecasts as $forecast) {
            $year = date('Y', strtotime($to));
            $stamp = strtotime($year.'W'.str_pad($forecast->getWeek(), 2, '0', STR_PAD_LEFT));

            $dataArray['chart'][$counter]['date']       = date('Y-m-d', $stamp);

            if (isset($actPerWeek[$forecast->getDay()])) {
                $expectedWeek   += $actPerWeek[$forecast->getDay()];
                $divMinus       += $actPerWeek[$forecast->getDay()];
                $divPlus        += $actPerWeek[$forecast->getDay()];
            } else {
                $expectedWeek   += $forecast->getFactorWeek() * $anlage->getContractualPower();
                $divMinus       += $forecast->getFactorWeek() * $anlage->getContractualPower() * $forecast->getFactorMin();
                $divPlus        += $forecast->getFactorWeek() * $anlage->getContractualPower() * $forecast->getFactorMax();
            }
            $forecastValue += $forecast->getFactorWeek() * $anlage->getContractualPower();
            $dataArray['chart'][$counter]['forecast']   = $forecastValue;
            $dataArray['chart'][$counter]['expected']   = $expectedWeek;
            $dataArray['chart'][$counter]['divMinus']   = $divMinus;
            $dataArray['chart'][$counter]['divPlus']    = $divPlus;
            $counter++;
        }

        return $dataArray;
    }
}