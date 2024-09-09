<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\ForcastDayRepository;
use App\Repository\InvertersRepository;
use App\Service\FunctionsService;
use App\Service\PdoService;
use DateTime;
use DateTimeZone;
use PDO;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;


class ForecastChartService
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly ForcastDayRepository $forcastDayRepo,
        private readonly InvertersRepository $invertersRepo,
        private readonly FunctionsService $functions
    ){
    }

    /**
     * @throws \Exception
     */
    public function getForecastDayClassic(Anlage $anlage, $to): array
    {
        $actPerDay = [];
        $dataArray = [];

        $form = '%y%m%d';
        $date = $anlage->getDataSince() === null ? $anlage->getAnlBetrieb() : $anlage->getDataSince();
        $localFrom = date('Y') . $date->format('-m-d');
        $localTo = (int)date('Y') + 1 . $date->format('-m-d');
        $conn = $this->pdoService->getPdoPlant();

        if ($anlage->getShowEvuDiag()) { //year(stamp) = '$currentYear'
            $sql = "SELECT date_format(stamp, '%j') AS startDay, sum(e_z_evu) AS power  
                FROM ".$anlage->getDbNameAcIst()." 
                WHERE unit = 1 AND stamp >= '$localFrom' AND stamp < '$localTo' GROUP BY date_format(stamp, '$form')  
                ORDER BY stamp;
            ";
        } else {
            $sql = "SELECT date_format(stamp, '%j') AS startDay, sum(wr_pac) as power  
                FROM ".$anlage->getDbNameAcIst()." 
                WHERE stamp >= '$localFrom' AND stamp < '$localTo'  GROUP BY date_format(stamp, '$form')
                ORDER BY stamp;
            ";
        }
        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $actPerDay[(int) $value['startDay']] = round($value['power'], 2);
        }
        $conn = null;
        /** @var [] AnlageForecastDay $forecasts */
        $forecasts = $this->forcastDayRepo->findBy(['anlage' => $anlage]);
        $forecastValue = $expectedDay = $divMinus = $divPlus = 0;

        $period = new \DatePeriod(new DateTime($localFrom), new \DateInterval('P1D'), new DateTime($localTo));
        foreach ($period as $stamp) {
            $year = date('Y', strtotime((string) $to));
            $day = $stamp->format('z');

            if (isset($actPerDay[$day])) {
                $expectedDay += $actPerDay[$day];
                $divMinus += $actPerDay[$day];
                $divPlus += $actPerDay[$day];
            } else {
                if (isset($forecasts[$day-1])) {
                    $expectedDay += $forecasts[$day-1]->getPowerDay();
                    $divMinus += $forecasts[$day-1]->getDivMinDay();
                    $divPlus += $forecasts[$day-1]->getDivMaxDay();
                }
            }
            if (isset($forecasts[$day])) $forecastValue += $forecasts[$day]->getPowerDay();

            $dataArray['chart'][] = [
                'forecast' => round($forecastValue, 1),
                'expected' => round($expectedDay, 1),
                'divMinus' => round($divMinus, 1),
                'divPlus' => round($divPlus, 1),
                'date' => $stamp->format('Y-m-d')
            ];
        }

        $dataArray['headline'] = "Forecast: ".$anlage->getAnlName() . " (from $localFrom until $localTo)";
        /*
        foreach ($forecasts as $count => $forecast) {
            $year = date('Y', strtotime((string) $to));
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
        */


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
        $currentYear = date('Y', strtotime((string) $to));
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
                if ($value['startDay'] < date('z', strtotime((string) $to))) {
                    $actPerDay[(int) $value['startDay']] = round($value['sumEvu'], 2);
                }
            } else {
                if ($value['startDay'] < date('z', strtotime((string) $to))) {
                    $actPerDay[(int) $value['startDay']] = round($value['sumInvOut'], 2);
                }
            }
        }
        $conn = null;

        /** @var [] AnlageForecastDay $forecasts */
        $forecasts = $this->forcastDayRepo->findBy(['anlage' => $anlage]);
        $counter = $forecastValue = $expectedDay = $divMinus = $divPlus = 0;

        foreach ($forecasts as $count => $forecast) {
            $year = date('Y', strtotime((string) $to));
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

    // Get the DayAhead Forecast data for Chart
    public function getForecastDayAhead(Anlage $anlage, $from, $view, $days ): array {
        $now = new DateTime('now', new DateTimeZone('Europe/Berlin'));
        // view = 0 = Day | 1 = Hour | 2 = 15 Minute
        switch ($view) {
            case 0 :
                $form = '%y%m%d';
                $dateform = '%Y-%m-%d';
                $datenowhour = '';
            break;
            case 1 :
                $form = '%y%m%d%H';
                $dateform = '%Y-%m-%d %H';
                $datenowhour = $now->format("Y-m-d H:00");
            break;
            case 2 :
                $form = '%y%m%d%H%i';
                $dateform = '%Y-%m-%d %H:%i';
                $datenowhour = $now->format("Y-m-d H:00");
                $days = '3';
            break;
            default :
                $form = '%y%m%d';
                $dateform = '%Y-%m-%d';
                $datenowhour = '';
        }

        switch ($days) {
            case 0 :
                $day = '+5 day';
                break;
            case 1 :
                $day = '+2 day';
                break;
            case 2 :
                $day = '+1 day';
                break;
            case 3 :
                $day = '+2 day';
                break;
            default :
                $day = '+5 day';
        }

        $nextDays = strtotime($day, strtotime($from));
        $enddate = date("Y-m-d 23:45", $nextDays);

        $counter = 0;
        $dataArray = [];
        $conn = $this->pdoService->getPdoPlant();

        if ($anlage->getUseDayaheadForecast()) {
            $SQL = "SELECT af1.date, af1.hour, af1.minute, af1.fc_pac, af1.irr, af1.temp,  af2.wr_pac FROM 
                    ( SELECT date_format(stamp, '".$dateform."') AS date, UNIX_TIMESTAMP(date_format(stamp, '%Y-%m-%d %H:%i')) AS unixstp, date_format(stamp, '%H') AS hour, date_format(stamp, '%i') AS minute, sum(fc_pac) AS fc_pac, sum(irr) AS irr, temp AS temp FROM ".$anlage->getDbNameForecastDayahead()." WHERE `stamp` >= '".$from."' AND stamp <= '".$enddate."' GROUP BY date_format(stamp, '".$form."')) 
                    AS af1 
                    LEFT JOIN 
                    ( SELECT date_format(stamp, '".$dateform."') AS date, UNIX_TIMESTAMP(date_format(stamp, '%Y-%m-%d %H:%i')) AS unixstp, date_format(stamp, '%H') AS hour, date_format(stamp, '%i') AS minute, sum(wr_pac) AS wr_pac FROM ".$anlage->getDbNameAcIst()." WHERE `stamp` >= '".$from."' AND stamp <= '".$enddate."' GROUP BY date_format(stamp, '".$form."') )
                    AS af2 on (af1.date = af2.date); ";

            $result = $conn->prepare($SQL);
            $result->execute();

            foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
                $dataArray['chart'][$counter]['date'] = $value['date'];
                $dataArray['chart'][$counter]['hour'] = $value['hour'];

                if ($datenowhour == $value['date']) {
                    $dataArray['chart'][$counter]['label'] = 'Now';
                    $dataArray['chart'][$counter]['color'] = 'am4core.color("#050")';
                    $dataArray['chart'][$counter]['opacity'] = '1';
                }
                ( ($view == 1) ? $irrvalue = round($value['irr'] / 4,2) : $irrvalue = round($value['irr'],2));
                $dataArray['chart'][$counter]['minute'] = $value['minute'];
                $dataArray['chart'][$counter]['irr'] = $irrvalue;
                $dataArray['chart'][$counter]['temp'] = round($value['temp'],2);
                $dataArray['chart'][$counter]['forecast'] = round($value['fc_pac'],2);
                $dataArray['chart'][$counter]['real'] = (is_null($value['wr_pac']) ? '0' : round($value['wr_pac'],2));
                $counter++;
            }

         } else {

            $dataArray['chart'];

        }
        return $dataArray;
    }
}