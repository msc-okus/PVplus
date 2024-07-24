<?php
/*
 * MS 12/23
 * Using PHP Machine Learning for Multible Liniare Regression
 */

namespace App\Service\Forecast;
use App\Entity\AnlageGroupMonths;
use Phpml\Regression\LeastSquares;
use App\Repository\AnlagenRepository;
use App\Service;
use PDO;
use App\Service\PdoService;
use App\Service\Functions\IrradiationService;

class DayAheadForecastMALService
{
    public function __construct(
        private readonly PdoService $pdoService,
        private readonly AnlagenRepository $anlagenRepository,
        private readonly IrradiationService $irradiationService
    ) {

    }

  public function calcforecastout($anlageId,$decarray): array
  {

      $conn = $this->pdoService->getPdoPlant();
      $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlageId]);
      $samples = array();
      $targets = array();
      $resultArray = array();
      $modulisbif = false;   // todo : Sollte aus der Modul DB kommen muss noch gemacht werden

      if ($anlage->getUseDayaheadForecast()) {
          // Suche ersten und letzten Eintrag in der DB
          $SQLSEF = "SELECT f1.start,f2.ende FROM
                    (SELECT `stamp` as start FROM " . $anlage->getDbNameAcIst() . " ORDER by `stamp` ASC limit 1) as f1 
                    JOIN
                    (SELECT `stamp` as ende FROM " . $anlage->getDbNameAcIst() . " ORDER by `stamp` DESC limit 1) as f2";
          $result = $conn->prepare($SQLSEF);
          $result->execute();

          foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
              $fromdate = $value['start'];
              $enddate = $value['ende'];
          }

          // Hole die daten für die Regressionsachsen
          $SQL = "SELECT af1.date, af1.hour, af1.minute, af1.g_upper, af1.temp_pannel, af2.wr_pac FROM 
                  ( SELECT date_format(stamp, '%Y-%m-%d %H:%i') AS date, UNIX_TIMESTAMP(date_format(stamp, '%Y-%m-%d %H:%i')) AS unixstp, date_format(stamp, '%H') AS hour, date_format(stamp, '%i') AS minute, g_upper, temp_pannel FROM " . $anlage->getDbNameWeather() . " WHERE `stamp` BETWEEN '" . $fromdate . "' AND '" . $enddate . "' GROUP BY date_format(stamp, '%y%m%d%H%i')) AS af1 
                  LEFT JOIN
                  ( SELECT date_format(stamp, '%Y-%m-%d %H:%i') AS date, UNIX_TIMESTAMP(date_format(stamp, '%Y-%m-%d %H:%i')) AS unixstp,date_format(stamp, '%H') AS hour, date_format(stamp, '%i') AS minute, sum(wr_pac) AS wr_pac FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '" . $fromdate . "' AND '" . $enddate . "' GROUP BY date_format(stamp, '%y%m%d%H%i') ) AS af2 on(af1.date = af2.date);";

          $result = $conn->prepare($SQL);
          $result->execute();

          foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {

              if (!empty($value['temp_pannel']) or $value['temp_pannel'] > 0) {
                  $samples[] = [(empty($value['g_upper'])) ? 0.0 : $value['g_upper'], (empty($value['temp_pannel'])) ? 0.0 : $value['temp_pannel']];
                  $targets[] = (empty($value['wr_pac'])) ? 0.0 : $value['wr_pac'];
              }

          }
          //  Prüfen das Array Samples[] ob es geschieben wird es benötigt die PannelTemp zwingend.
          if (count($samples) > 1) {

              $regression = new LeastSquares();
              $regression->train($samples, $targets);

              if ((is_countable($decarray) ? count($decarray) : 0) > 0) {
                  foreach ($decarray as $keyout => $valout) {

                      foreach ($valout as $keyin => $valin) {

                          foreach ($valin as $key => $val) {

                              isset($val['TMP']) ? $airTemp = $val['TMP'] : $airTemp = '0.0';
                              isset($val['FF']) ? $windSpeed = $val['FF'] : $windSpeed = '0.0';
                              isset($val['GDIR']) ? $gdir = $val['GDIR'] : $gdir = '0.0';
                              isset($val['DOY']) ? $doy = $val['DOY'] : $doy = '0';
                              isset($val['HR']) ? $hr = $val['HR'] : $hr = '0';
                              isset($val['MIN']) ? $m = $val['MIN'] : $m = '0';
                              isset($val['TS']) ? $ts = $val['TS'] : $ts = '0';
                              isset($val['TIP']) ? $tip = $val['TIP'] : $tip = '';

                              $hrarry[$doy][$hr][$key] = ['ts' => $ts, 'ex' => 0, 'irr' => 0, 'tmp' => 0, 'gdir' => 0, 'tcell' => 0];

                              if ($anlage->getIsOstWestAnlage()) {

                                  if ($modulisbif) {
                                      isset($val['OSTWEST']['RGESBIF_UPPER']) ? $irrUpper = round($val['OSTWEST']['RGESBIF_UPPER'], 2) : $irrUpper = '0.0';
                                      isset($val['OSTWEST']['RGESBIF_LOWER']) ? $irrLower = round($val['OSTWEST']['RGESBIF_LOWER'], 2) : $irrLower = '0.0';
                                  } else {
                                      isset($val['OSTWEST']['RGES_UPPER']) ? $irrUpper = round($val['OSTWEST']['RGES_UPPER'], 2) : $irrUpper = '0.0';
                                      isset($val['OSTWEST']['RGES_LOWER']) ? $irrLower = round($val['OSTWEST']['RGES_LOWER'], 2) : $irrLower = '0.0';
                                  }

                                  if ($tip == "60min") {
                                      $irrUpper = $irrUpper / 4;
                                  }
                                  $irr = $irrUpper;
                                  $Tcell = round($this->irradiationService->tempCellNrel($anlage, $windSpeed, $airTemp, $irrUpper), 2);
                                  if ($irrUpper == 0.0 or $irrUpper == 0 or $irrUpper == NULL) {
                                      $ex4 = 0.0;
                                  } else {
                                      $ex4 = $regression->predict([$irrUpper, $Tcell]);
                                  }

                              } elseif ($anlage->getIsOstWestAnlage()) {

                                  if ($modulisbif) {
                                      isset($val['TRACKEROW']['RGESBIF']) ? $irr = round($val['TRACKEROW']['RGESBIF'], 2) : $irr = '0.0';
                                  } else {
                                      isset($val['TRACKEROW']['RGES']) ? $irr = round($val['TRACKEROW']['RGES'], 2) : $irr = '0.0';
                                  }

                                  if ($tip == "60min") {
                                      $irr = $irr / 4;
                                  }

                                  $Tcell = round($this->irradiationService->tempCellNrel($anlage, $windSpeed, $airTemp, $irr), 2);
                                  if ($irr == 0.0 or $irr == 0 or $irr == NULL) {
                                      $ex4 = 0.0;
                                  } else {
                                      $ex4 = $regression->predict([$irr, $Tcell]);
                                  }

                              } else {

                                  if ($modulisbif) {
                                      isset($val['SUED']['RGESBIF']) ? $irr = round($val['SUED']['RGESBIF'], 2) : $irr = '0.0';
                                  } else {
                                      isset($val['SUED']['RGES']) ? $irr = round($val['SUED']['RGES'], 2) : $irr = '0.0';
                                  }

                                  if ($tip == "60min") {
                                      $irr = $irr / 4;
                                  }

                                  $Tcell = round($this->irradiationService->tempCellNrel($anlage, $windSpeed, $airTemp, $irr), 2);
                                  if ($irr == 0.0 or $irr == 0 or $irr == NULL) {
                                      $ex4 = 0.0;
                                  } else {
                                      $ex4 = $regression->predict([$irr, $Tcell]);
                                  }
                              }

                              $hrarry[$doy][$hr][$key] = ['ts' => $ts, 'ex' => round($ex4, 2), 'irr' => $irr, 'tmp' => $airTemp, 'gdir' => $gdir, 'tcell' => $Tcell]; // array for houry return
                              $irr = 0;

                          }

                      }

                      $resultArray = $hrarry;

                  }

              }

          }
      }

      return $resultArray;

  }

}
