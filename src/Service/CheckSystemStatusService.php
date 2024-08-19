<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenStatus;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\AnlagenStatusRepository;
use App\Repository\ForcastDayRepository;
use Doctrine\ORM\EntityManagerInterface;
use PDO;


class CheckSystemStatusService
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly AnlagenRepository $anlagenRepository,
        private readonly AnlagenStatusRepository $statusRepository,
        private readonly EntityManagerInterface $em,
        private readonly MessageService $messageService,
        private readonly ForcastDayRepository $forecastDayRepo,
        private readonly FunctionsService $functions)
    {
    }

    public function checkSystemStatus(): string
    {
        // TODO: Umstellung auf Doctrine / Symfony
        $anlagenStatusDb = 'pvp_base.pvp_anlagen_status';
        $conn = $this->pdoService->getPdoPlant();
        $connAnlage = $this->pdoService->getPdoBase();

        $output = '';

        $currentTimeStamp = self::getCetTime();
        $timestampModulo = ($currentTimeStamp - ($currentTimeStamp % 1800)); // nur zur vollen und halben Stunde. Uhrzeit runden, um unnötige db einträge zu verhindern (besonders beim Testen)
        //$timestampModulo = $currentTimeStamp;
        $sqlTimeStamp = date('Y-m-d H:i:s', $timestampModulo);
        $from = date('Y-m-d 00:00:00', $currentTimeStamp);
        $to = date('Y-m-d H:i:00', $timestampModulo - 1800);
        $to = date('Y-m-d H:i:00', $timestampModulo);
        // $to                         = date("Y-m-d 23:59:00", $timestampModulo);
        $fromYesterday = date('Y-m-d 00:00:00', $currentTimeStamp - 24 * 3600);
        $toYestreday = date('Y-m-d 23:59:00', $timestampModulo - 24 * 3600);
        $timestampModuloYesterday = $timestampModulo - (24 * 3600);

        /* STATUS der Anlage ermitteln */
        // $anlagen = $this->anlagenRepository->findBy(['anlHidePlant' => 'No', 'calcPR' => true]);
        $anlagen = $this->anlagenRepository->findBy(['anlHidePlant' => 'No', 'calcPR' => '1']);
        #$anlagen = $this->anlagenRepository->findBy(['anlId' => '251']);
        if (isset($anlagen)) {
            foreach ($anlagen as $anlage) {
                $anlagenId = $anlage->getAnlId();
                $dbNameIst = $anlage->getDbNameIst();
                $dbNameWeather = $anlage->getDbNameWeather();
                $anlageDbWeather = $anlage->getNameWeather();
                $anlagenName = $anlage->getAnlName();

                $output .= "START - Anlage $anlagenName / $anlagenId <br>";

                $anlagenStatus = 0;
                $lastDataStatus = '';
                // letzten Eintrag in IST DB ermitteln für Status Anlagen IO
                // ////////////////
                $res = $conn->query("SELECT stamp FROM $dbNameIst ORDER BY stamp DESC LIMIT 1");
                if ($res) {
                    $rowTemp = $res->fetch(PDO::FETCH_OBJ);
                    $lastRecStampIst = strtotime((string) $rowTemp->stamp ?? 'now');
                    if ($anlage->getAnlInputDaily() !== 'Yes') { // Wenn daten kontinuierlich kommen
                        if ($currentTimeStamp - $lastRecStampIst <= $GLOBALS['abweichung']['io']['normal']) {
                            $lastDataStatus = 'normal';
                        }
                        if ($currentTimeStamp - $lastRecStampIst > $GLOBALS['abweichung']['io']['normal'] && $currentTimeStamp - $lastRecStampIst <= $GLOBALS['abweichung']['io']['warning']) {
                            $lastDataStatus = 'warning';
                        }
                        if ($currentTimeStamp - $lastRecStampIst > $GLOBALS['abweichung']['io']['warning']) {
                            $lastDataStatus = 'alert';
                        }
                    } else { // Wenn Daten nur einmal am Tag kommen
                        $currentTimeStampYesterday = $currentTimeStamp - 24 * 3600;
                        if ($currentTimeStampYesterday - $lastRecStampIst <= $GLOBALS['abweichung']['io']['normal']) {
                            $lastDataStatus = 'normal';
                        }
                        if ($currentTimeStampYesterday - $lastRecStampIst > $GLOBALS['abweichung']['io']['warning']) {
                            $lastDataStatus = 'alert';
                        }
                    }
                    $acActStamp = $rowTemp->stamp;
                    
                }

                // letzten Eintrag in  Weather DB ermitteln für Status Weather IO
                // ////////////////
                $res = $conn->query("SELECT stamp FROM $dbNameWeather ORDER BY stamp DESC LIMIT 1");
                if ($res) {
                    if ($res->rowCount() > 0) {
                        $rowTemp = $res->fetch(PDO::FETCH_OBJ);
                        $lastRecStampWeather = strtotime((string) $rowTemp->stamp ?? 'now');
                        if ($currentTimeStamp - $lastRecStampWeather <= $GLOBALS['abweichung']['io']['normal']) {
                            $lastWeatherStatus = 'normal';
                        }
                        if ($currentTimeStamp - $lastRecStampWeather > $GLOBALS['abweichung']['io']['normal'] && $currentTimeStamp - $lastRecStampWeather <= $GLOBALS['abweichung']['io']['warning']) {
                            $lastWeatherStatus = 'warning';
                        }
                        if ($currentTimeStamp - $lastRecStampWeather > $GLOBALS['abweichung']['io']['warning']) {
                            $lastWeatherStatus = 'alert';
                        }
                        $acExpStamp = $rowTemp->stamp;
                    }
                    
                } else {
                    $acExpStamp = 0;
                }

                // wenn Anlagen oder Wetter Daten fehlen dann Analagen Status um 1 erhöhen
                if ($lastWeatherStatus == 'alert' || $lastDataStatus == 'alert') {
                    ++$anlagenStatus;
                }

                // ac und dc IST ermitteln (alles was da ist)
                // ////////////////
                $resultIst = $this->calcPowerIstAcAndDc($anlage, $from, $to);
                $resultAcAct = $resultIst['ac'];
                $resultDcAct = $resultIst['dc'];

                // ac und dc SOLL ermitteln (alles was da ist)
                // ////////////////
                $resultSoll = $this->calcPowerSollAcAndDc($anlage, $from, $to);
                $resultAcExp = $resultSoll['ac'];
                $resultDcExp = $resultSoll['dc'];

                // Forecast Wert bis aktuelle Woche ermitteln
                $forecastDate = new \DateTime('last sunday');
                if ($anlage->getShowForecast() && $anlage->getCalcPR()) {
                    if ($anlage->getUsePac()) {
                        $pacDate = $anlage->getPacDate()->format('Y-m-d 00:00:00');
                        if (false === checkdate($anlage->getPacDate()->format('m'), $anlage->getPacDate()->format('d'), $anlage->getPacDate()->format('Y'))) {
                            $pacDate = $forecastDate->format('Y-m-d 00:00:00');
                        }
                        $powerActArray = $this->functions->getSumPowerAcAct($anlage, $forecastDate->format('Y-m-d 00:00:00'), $forecastDate->format('Y-m-d 23:00:00'), $pacDate, $forecastDate->format('Y-m-d 23:00:00'));


                        if ($anlage->getUseDayForecast()) {
                            if ($anlage->getShowEvuDiag()) {
                                $forecastYear = $powerActArray['powerEvuYear'] - $this->forecastDayRepo->calcForecastByDate($anlage, $forecastDate);
                            } else {
                                $forecastYear = $powerActArray['powerActYear'] - $this->forecastDayRepo->calcForecastByDate($anlage, $forecastDate);
                            }
                        } else {
                            if ($anlage->getShowEvuDiag()) {
                                $forecastYear = $powerActArray['powerEvuYear'] - $this->forecastDayRepo->calcForecastByDate($anlage, $forecastDate);
                            } else {
                                $forecastYear = $powerActArray['powerActYear'] - $this->forecastDayRepo->calcForecastByDate($anlage, $forecastDate);
                            }
                        }
                    }
                    if (!isset($forecastYear)) {
                        $forecastYear = 0;
                    }
                    $forecastDivMinusYear = 0;
                    $forecastDivPlusYear = 0;

                    $forecastPac = 0; // TODO: Forecast PAC
                    $forecastDivMinusPac = 0;
                    $forecastDivPlusPac = 0;
                } else {
                    $forecastYear = 0;
                    $forecastDivMinusYear = 0;
                    $forecastDivPlusYear = 0;
                    $forecastPac = 0;
                    $forecastDivMinusPac = 0;
                    $forecastDivPlusPac = 0;
                }

                // diff AC und DC bilden
                $acDiff = $resultAcExp - $resultAcAct;
                $dcDiff = $resultDcExp - $resultDcAct;

                // Inverter Status und Verfügbarkeit ermitteln
                if ($anlage->getAnlInputDaily() !== 'Yes') { // Wenn daten kontinuierlich kommen
                    $resultInverter = $this->checkInverter($anlage, $from, $to, $currentTimeStamp);
                } else { // Wenn Daten nur einmal am Tag kommen
                    $resultInverter = $this->checkInverter($anlage, $from, $to, $currentTimeStamp);
                }
                $inverterAnz = $resultInverter['anzInverter'];
                $inverterAnzWarning = $resultInverter['anzInverterWarning'];
                $inverterAnzAlert = $resultInverter['anzInverterAlert'];
                $inverterScore = $resultInverter['score'];
                $inverterErrorMessage = $resultInverter['errorMessage'];
                $inverterStatus = $resultInverter['invStatus'];

                // String Status ermitteln
                if ($anlage->getAnlInputDaily() !== 'Yes') { // Wenn daten kontinuierlich kommen
                    $resultString = $this->checkStrings($anlage, $timestampModulo);
                } else { // Wenn Daten nur einmal am Tag kommen
                    $resultString = $this->checkStrings($anlage, $timestampModuloYesterday);
                }
                $stringIStatus = $resultString['stringIStatus'];
                $stringIWarnings = $resultString['anzCurrentWarning'];
                $stringIAlerts = $resultString['anzCurrentAlert'];
                $stringIScore = $resultString['scoreCurrent'];
                $stringUStatus = $resultString['stringUStatus'];
                $stringUWarnings = $resultString['anzVoltageWarning'];
                $stringUAlerts = $resultString['anzVoltageAlert'];
                $stringUScore = $resultString['scoreVoltage'];
                $stringErrorMessages = $resultString['errorMessage'];
                $dcStatus = $resultString['dcStatus'];

                // AC und DC zum letzten gemeinsamen Zeitpunk der SOll und IST Daten ermitteln (wichtig für Fehlermeldungen)
                // $toLastBoth entspricht dem Datum an dem sowohl 'IST' als auch 'SOLL' (Weather) Daten vorlagen


                ($lastRecStampIst <= $lastRecStampWeather) ? $toLastBoth = self::formatTimeStampToSql($lastRecStampIst) : $toLastBoth = self::formatTimeStampToSql($lastRecStampWeather);

                // ac und dc 'IST' ermitteln
                $resultActBoth = $this->calcPowerIstAcAndDc($anlage, $from, $toLastBoth);
                $resultAcActBoth = round($resultActBoth['ac'] ?? 0, 0);
                $resultDcActBoth = round($resultActBoth['dc'] ?? 0, 0);

                // ac und dc 'SOLL' ermitteln
                $resultExpBoth = $this->calcPowerSollAcAndDc($anlage, $from, $toLastBoth);
                $resultAcExpBoth = round($resultExpBoth['ac'] ?? 0, 0);
                $resultDcExpBoth = round($resultExpBoth['dc'] ?? 0, 0);

                // diff AC und DC bilden
                $acDiffBoth = $resultAcExpBoth - $resultAcActBoth;
                $dcDiffBoth = $resultDcExpBoth - $resultDcActBoth;
                if ($resultAcExpBoth == 0) {
                    $resultAcExpBoth = 1;
                }
                $acDiffPercent = round($acDiffBoth / $resultAcExpBoth * 100, 1);
                if ($resultDcExpBoth == 0) {
                    $resultDcExpBoth = 1;
                }
                $dcDiffPercent = round($dcDiffBoth / $resultDcExpBoth * 100, 1);

                // Fehlercode AC Diff ermitteln
                if ($acDiffPercent < $GLOBALS['abweichung']['produktion']['warning'] && $resultAcActBoth > 0) {
                    // alles Okay
                    $acDiffError = 0;
                    $acDiffStatus = 'normal';
                } elseif ($acDiffPercent >= $GLOBALS['abweichung']['produktion']['warning'] && $acDiffPercent < $GLOBALS['abweichung']['produktion']['alert']) {
                    // warnung
                    $acDiffError = 1;
                    $acDiffStatus = 'warning';
                } else {
                    $acDiffError = 2;
                    $acDiffStatus = 'alert';
                }
                $anlagenStatus += $acDiffError;

                // Fehlercode DC Diff ermitteln
                if ($dcDiffPercent < $GLOBALS['abweichung']['produktion']['warning'] && $resultDcActBoth > 0) {
                    // alles Okay
                    $dcDiffError = 0;
                    $dcDiffStatus = 'normal';
                } elseif ($dcDiffPercent >= $GLOBALS['abweichung']['produktion']['warning'] && $dcDiffPercent < $GLOBALS['abweichung']['produktion']['alert']) {
                    // warnung
                    $dcDiffError = 1;
                    $dcDiffStatus = 'warning';
                } else {
                    $dcDiffError = 2;
                    $dcDiffStatus = 'alert';
                }
                $anlagenStatus += $dcDiffError;
                $anlagenStatus += $inverterScore;

                $uniqueKey = $anlage->getAnlId().'_'.strtotime($sqlTimeStamp);

                // Status Datenbank insert / update
                $status = $this->statusRepository->findOneBy(['uniqueKey' => $uniqueKey]);
                if (!$status) {
                    $status = new AnlagenStatus();
                    $status
                        ->setUniqueKey($uniqueKey)
                        ->setAnlage($anlage)
                        ->setAnlId($anlage->getAnlId())
                        ->setStamp(date_create($sqlTimeStamp))
                        ->setEignerId($anlage->getEignerId())
                    ;
                }
                $status
                    ->setLastDataIo(date_create($acActStamp ?? 'now'))
                    ->setLastDataStatus($lastDataStatus)
                    ->setLastWeatherIo(date_create($acExpStamp ?? 'now'))
                    ->setLastWeatherStatus($lastWeatherStatus)
                    ->setActStamp(date_create($acActStamp ?? 'now'))
                    ->setExpStamp(date_create($acExpStamp ?? 'now'))
                    ->setAcActAll($resultAcAct)
                    ->setAcExpAll($resultAcExp)
                    ->setAcDiffAll($acDiff)
                    ->setDcActAll($resultDcAct)
                    ->setDcExpAll($resultDcExp)
                    ->setDcDiffAll($dcDiff)
                    ->setStampLastBoth(date_create($toLastBoth))
                    ->setAcErrorCode($acDiffError)
                    ->setAcDiffStatus($acDiffStatus)
                    ->setAcActBoth($resultAcActBoth)
                    ->setAcExpBoth($resultAcExpBoth)
                    ->setAcLostPercent($acDiffPercent)
                    ->setDcErrorCode($dcDiffError)
                    ->setDcDiffStatus($dcDiffStatus)
                    ->setDcActBoth($resultDcActBoth)
                    ->setDcExpBoth($resultDcExpBoth)
                    ->setDcLostPercent($dcDiffPercent)
                    ->setInvScore($inverterScore)
                    ->setInvAnz($inverterAnz)
                    ->setInvAnzWarning($inverterAnzWarning)
                    ->setInvAnzAlert($inverterAnzAlert)
                    ->setInvStatus($inverterStatus)
                ;
                $status
                    ->setStringIStatus($stringIStatus)
                    ->setStringIWarnings($stringIWarnings)
                    ->setStringIAlerts($stringIAlerts)
                    ->setStringIScore($stringIScore)
                    ->setStringUStatus($stringUStatus)
                    ->setStringUWarnings($stringUWarnings)
                    ->setStringUAlerts($stringUAlerts)
                    ->setStringUScore($stringUScore)
                    ->setStringErrorMessages($stringErrorMessages)
                    ->setDcStatus($dcStatus)
                    ->setAnlagenStatus($anlagenStatus)
                    ->setForecastYear($forecastYear)
                    ->setForecastDivMinusYear($forecastDivMinusYear)
                    ->setForecastDivPlusYear($forecastDivPlusYear)
                    ->setForecastPac($forecastPac)
                    ->setForecastDivMinusPac($forecastDivMinusPac)
                    ->setForecastDivPlusPac($forecastDivPlusPac)
                    ->setForecastDate($forecastDate)
                ;
                $this->em->persist($status);
                $this->em->flush();

                // Erzeugen von Fehlermeldungen (deaktiviert !!! NICHT LÖSCHEN
                /*
                if ($anlagenStatus > 0 && self::isInTimeRange()) { // Status des aktuellen Laufes
                    $month = date('m', $currentTimeStamp);
                    if (date('H', $currentTimeStamp) == $GLOBALS['StartEndTimesAlert'][$month]['start'] && (int)date('i', $currentTimeStamp) + 0 <= 10) {
                        // Stunde ist gleich der anfangsStunde im config array und die Minute sind dicht an der 00 (nicht mehr als 5 minuten verstrichen, nach voller Stunde)
                        // erster Lauf an diesem Tag
                        $firstRun = true;
                    } else {
                        $firstRun = false;
                    }
                    $from = date('Y-m-d H:i:s', $currentTimeStamp - 4 * 3600);
                    $to = date('Y-m-d H:i:s', $currentTimeStamp);
                    $counter = 0;
                    $sqlLastStatus = "SELECT count(last_weather_status) as counter FROM $anlagenStatusDb WHERE anlage_id = $anlagenId AND stamp BETWEEN '$from' AND '$to' AND last_weather_status = 'alert'";
                    $resultLastStatus = $connAnlage->query($sqlLastStatus);
                    if ($resultLastStatus) {
                        if ($resultLastStatus->rowCount() == 1) {
                            $rowLastStatus = $resultLastStatus->fetch(PDO::FETCH_OBJ);
                            $counter = $rowLastStatus->counter;
                        }
                    }
                    // AlertType = 1 , Wetter IO Daten Fehler
                    if ((date('H', $currentTimeStamp) == 11 || date('H', $currentTimeStamp) == 15) && date('i', $currentTimeStamp) <= 10) { // Wetter Daten fehlen seit mehr als 4 Stunden
                        if ($lastWeatherStatus == 'alert' && ($counter >= 8 || $firstRun)) {
                            // Send Alert Email an Kast
                            $subject = "green4net - Keine Daten von Wetterstation $anlageDbWeather";
                            $message = "<h3 class='block'>Keine Daten von der Wetterstation $anlageDbWeather, seit $acExpStamp</h3>";
                            // $this->messageService->sendMessage($anlage, 'alert', 2, $subject, $message, false, false, true, true);
                        }
                    }

                    // lade Daten vom vorhergenden 'chechSystemStatus' Laufes
                    $sqlLastStatus = "SELECT * FROM $anlagenStatusDb WHERE anlage_id = $anlagenId ORDER BY STAMP DESC LIMIT 1 OFFSET 1";
                    $resultLastStatus = $connAnlage->query($sqlLastStatus);

                    if ($resultLastStatus->rowCount() == 1) {
                        $arrayAlertStatusLast = self::convertKeysToCamelCase($resultLastStatus->fetch(PDO::FETCH_OBJ));

                        // AlertType = 2 , Anlagen IO Daten Fehler
                        if ($lastDataStatus == 'alert' && ($arrayAlertStatusLast['lastDataStatus'] !== 'alert' || $firstRun)) {
                            // Anlagen Daten fehlen seit mehr als 2 Stunden
                            if (strtotime((string) $acActStamp) <= $currentTimeStamp - 2 * 3600) {
                                // Send Alert Email an G4N
                                $subject = "Keine Daten von der Anlage: $anlagenName";
                                $message = "<h3 class='block'>Keine Daten von der Anlage $anlagenName ($anlagenId), seit $acActStamp</h3>";
                                // $this->messageService->sendMessage($anlage, 'alert', 2, $subject, $message, false, false, true);
                            }
                        }

                        // AlertType = 3, AC Abweichung -findet im Moment nur bei Master Slave Anlagen Anwendung
                        if ($anlage->getAnlType() == 'Master Slave') {
                            if ($acDiffStatus == 'alert') {
                                // Send Alert Email an G4N und Kunden
                                $subject = "$anlagenName AC Abweichungen";
                                $message = "<p class='block'>'Abweichungen auf der AC Seite hoch. Bitte prüfen.'</p>";
                                // $this->messageService->sendMessage($anlage, 'alert', 3, $subject, $message);
                            }
                        }
                        // AlertType = 4

                        $output .= "<br>Anlagen Status: $anlagenStatus TimeRange: ".self::isInTimeRange()."<br>$inverterStatus ($inverterScore - ".$arrayAlertStatusLast['invScore'].')';
                        // AlertType = 5, Inverterfehler
                        if ($inverterStatus == 'alert' && ($inverterScore > $arrayAlertStatusLast['invScore'] || $firstRun)) { //
                            // Send Alert Email an G4N und Kunden
                            $subject = "$anlagenName Inverter Error";
                            $message = "<p class='block'>$inverterErrorMessage</p>";
                            // $this->messageService->sendMessage($anlage, 'alert', 5, $subject, $message);
                        }

                        // AlertType = 6, Stringfehler
                        if ($stringIStatus == 'alert' && ($stringIScore + $stringUScore > $arrayAlertStatusLast['stringIScore'] + $arrayAlertStatusLast['stringUScore'] || $firstRun)) {
                            // Send Alert Email an G4N und Kunden
                            $subject = "$anlagenName String Error";
                            $message = "<p class='block'>$stringErrorMessages</p>";
                            // $this->messageService->sendMessage($anlage, 'alert', 6, $subject, $message);
                        }
                    }
                }
                */
                $output .= 'ENDE<hr>';
            }
        }
        

        return $output;
    }

    /**
     * Ermitteln der Leitung einer Anlage für den angegebenen Zeitraum
     * Return Array mit AC Ist und DC Ist.
     */
    private function calcPowerIstAcAndDc(Anlage $anlage, $from, $to): array
    {
        $conn = $this->pdoService->getPdoPlant();

        $returnArray['ac'] = 0;
        $returnArray['dc'] = 0;
        if ($anlage->getUseNewDcSchema()) {
            $res = $conn->query('SELECT sum(wr_pac) as SumPowerAC FROM '.$anlage->getDbNameAcIst()." WHERE stamp BETWEEN '$from' AND '$to'");
            if ($res->rowCount() > 0) {
                $row = $res->fetch(PDO::FETCH_OBJ);
                $actAc = $row->SumPowerAC ?? 0;
                $returnArray['ac'] = $actAc;
            }
            $res = $conn->query('SELECT sum(wr_pdc) as SumPowerDC FROM '.$anlage->getDbNameDcIst()." WHERE stamp BETWEEN '$from' AND '$to'");
            if ($res) {
                if ($res->rowCount() > 0) {
                    $row = $res->fetch(PDO::FETCH_OBJ);
                    $actDc = $row->SumPowerDC ?? 0;
                    $returnArray['dc'] = $actDc;
                }
            }
        } else { // Altes Datenbank Schema ( AC und DC ISt in einer Tabelle )
            $res = $conn->query('SELECT sum(wr_pac) as SumPowerAC, sum(wr_pdc) as SumPowerDC FROM '.$anlage->getDbNameAcIst()." WHERE stamp BETWEEN '$from' AND '$to'");
            if ($res) {
                if ($res->rowCount() > 0) {
                    $row = $res->fetch(PDO::FETCH_OBJ);
                    $actAc = $row->SumPowerAC ?? 0;
                    $actDc = $row->SumPowerDC ?? 0;

                    $returnArray['ac'] = $actAc;
                    $returnArray['dc'] = $actDc;
                }
            }
        }

        return $returnArray;
    }

    /**
     * Ermitteln der Soll Leitung einer Anlage für den angegebenen Zeitraum
     * Return Array mit AC Soll und DC Soll.
     */
    private function calcPowerSollAcAndDc(Anlage $anlage, $from, $to): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $returnArray['ac'] = 0;
        $returnArray['dc'] = 0;
        // Soll AC
        $sql = 'SELECT sum(ac_exp_power) as SumPowerAC FROM '.$anlage->getDbNameDcSoll()." WHERE stamp BETWEEN '$from' AND '$to'";
        $res = $conn->query($sql);
        if ($res) {
            if ($res->rowCount() > 0) {
                $row = $res->fetch(PDO::FETCH_OBJ);
                $returnArray['ac'] = $row->SumPowerAC ?? 0;
            }
        }
        // Soll DC
        $sql = 'SELECT sum(dc_exp_power) as SumPowerDC FROM '.$anlage->getDbNameDcSoll()." WHERE stamp BETWEEN '$from' AND '$to'";
        $res = $conn->query($sql);
        if ($res) {
            if ($res->rowCount() === 1) {
                $row = $res->fetch(PDO::FETCH_OBJ);
                $returnArray['dc'] = $row->SumPowerDC ?? 0;
            }
        }

        return $returnArray;
    }

    /**
     * Ermitteln des Inverter Status.
     * Es wird unterschieden in ZWR (Zentralwechselrichter) und String Wechselrichter
     * Bei ZWR mus auch noch Master/Slave und 'normaler' Modus unterschieden werden.
     *
     * Zusätzlich Verfügbarkeit diverse Zähler je nach Einstrahlung
     */
    private function checkInverter(Anlage $anlage, $from, $to, $currentTimeStamp): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $inverterArray = [];
        $inverterArray['score'] = 0;
        $inverterArray['anzInverter'] = 0;
        $inverterArray['anzInverterWarning'] = 0;
        $inverterArray['anzInverterAlert'] = 0;

        $inverterArray['errorMessage'] = '';
        $inverterArray['invStatus'] = '';

        // String Wechselrichter oder ZentralWechselRichter
        if ($anlage->getAnlType() == 'string' || $anlage->getAnlType() == 'zwr') { // String Wechselrichter oder ZentralWechselRichter
            $inverterArray['type'] = $anlage->getAnlType();
            $acGroups = $anlage->getGroupsAc();
            if (count($acGroups) > 0) {
                foreach ($acGroups as $groupId => $group) {
                    ++$inverterArray['anzInverter'];
                    // referenzwert der Gruppe ermitteln
                    $sql_grp_avg = 'SELECT AVG(wr_pac) AS avg_power_ac FROM '.$anlage->getDbNameAcIst()." WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$groupId'";
                    $result = $conn->query($sql_grp_avg);
                    $rowGrpAvg = $result->fetch(PDO::FETCH_OBJ);
                    $grpAvgPowerAc = $rowGrpAvg->avg_power_ac ?? 0;
          
                    if ($grpAvgPowerAc > 0) {
                        $inverterArray['invStatus'] = 'normal';
                        for ($inverter = $group['GMIN']; $inverter <= $group['GMAX']; ++$inverter) {
                            $sql_inv_avg = 'SELECT AVG(wr_pac) AS avg_power_ac FROM '.$anlage->getDbNameAcIst()." WHERE stamp BETWEEN '$from' AND '$to' AND unit = '$inverter'";
                            $result = $conn->query($sql_inv_avg);
                            $rowInvAvg = $result->fetch(PDO::FETCH_OBJ);
                            $inverterAvgPowerAc = $rowInvAvg->avg_power_ac ?? 0;
                            

                            $lostInverter = 100 - round(100 / $grpAvgPowerAc * $inverterAvgPowerAc, 2); // Verlust in %
                            $inverterArray['lostPercent'][$inverter] = $lostInverter;
                            if ($lostInverter <= $GLOBALS['abweichung']['inverter']['string']['warning']) {
                                // alles Okay
                                $inverterArray['error'][$inverter] = 0;
                            } elseif ($lostInverter > $GLOBALS['abweichung']['inverter']['string']['warning'] && $lostInverter < $GLOBALS['abweichung']['inverter']['string']['alert']) {
                                // warnung
                                ++$inverterArray['anzInverterWarning'];
                                $inverterArray['error'][$inverter] = 3;
                                ++$inverterArray['score'];
                                $inverterArray['errorMessage'] .= 'Groupe '.$group['GroupName']." Inverter No $inverter : warning (lost: $lostInverter%) ($grpAvgPowerAc - $inverterAvgPowerAc) (".date('Y-m-d H:i', $currentTimeStamp).')<br>';
                            } else {
                                // alert
                                ++$inverterArray['anzInverterAlert'];
                                $inverterArray['error'][$inverter] = 9;
                                $inverterArray['score'] += 9;
                                $inverterArray['errorMessage'] .= 'Groupe '.$group['GroupName']." Inverter No $inverter : alert (lost: $lostInverter%) ($grpAvgPowerAc - $inverterAvgPowerAc) (".date('Y-m-d H:i', $currentTimeStamp).')<br>';
                            }
                        }
                    }
                }
            }

            if (($inverterArray['anzInverterWarning'] >= 1) && ($inverterArray['anzInverterWarning'] < 5)) {
                $inverterArray['invStatus'] = 'warning';
            }
            if ($inverterArray['anzInverterWarning'] > 5) {
                $inverterArray['invStatus'] = 'alert';
            }
            if ($inverterArray['anzInverterAlert'] > 1) {
                $inverterArray['invStatus'] = 'alert';
            }
        }
        // Master Slave Wechselrichter
        elseif ($anlage->getAnlType() == 'masterslave') {
            $sql_avgAcIst = 'SELECT AVG(wr_pac) AS avg_power_ac_ist FROM '.$anlage->getDbNameAcIst()." WHERE stamp BETWEEN '$from' AND '$to'";
            $sql_avgAcSoll = 'SELECT AVG(exp_kwh) as avg_power_ac_soll FROM '.$anlage->getDbNameAcSoll()." WHERE stamp BETWEEN '$from' AND '$to'";
            $resultAcIst = $conn->query($sql_avgAcIst);
            $resultAcSoll = $conn->query($sql_avgAcSoll);
            if ($resultAcIst && $resultAcSoll) {
                if ($resultAcIst->rowCount() == 1 && $resultAcSoll->rowCount() == 1) {
                    $rowIst = $resultAcIst->fetch(PDO::FETCH_OBJ);
                    $rowSoll = $resultAcSoll->fetch(PDO::FETCH_OBJ);
                    $istPower = $rowIst->avg_power_ac_ist ?? 0;
                    $sollPower = $rowSoll->avg_power_ac_soll ?? 0;
                    $lostInverter = $sollPower > 0 ? 100 - round($istPower / $sollPower * 100) : 0; // Verlust in %
                    if ($istPower > 0) {
                        $inverterArray['invStatus'] = 'normal';
                        if ($lostInverter <= $GLOBALS['abweichung']['inverter']['string']['warning']) {
                            $inverterArray['error'] = 0;
                        } elseif ($lostInverter > $GLOBALS['abweichung']['inverter']['string']['warning'] && $lostInverter < $GLOBALS['abweichung']['inverter']['string']['alert']) {
                            // warnung
                            ++$inverterArray['anzInverterWarning'];
                            $inverterArray['error'] = 3;
                            ++$inverterArray['score'];
                            $inverterArray['errorMessage'] .= "Inverter : warning (lost: $lostInverter%) (".date('Y-m-d H:i', $currentTimeStamp).')<br>';
                        } else {
                            // alert
                            ++$inverterArray['anzInverterAlert'];
                            $inverterArray['error'] = 9;
                            $inverterArray['score'] += 9;
                            $inverterArray['errorMessage'] .= "Inverter : alert (lost: $lostInverter%) (".date('Y-m-d H:i', $currentTimeStamp).')<br>';
                        }
                    }
                    if (($inverterArray['score'] >= 1) && ($inverterArray['score'] < 5)) {
                        $inverterArray['invStatus'] = 'warning';
                    }
                    if ($inverterArray['score'] > 5) {
                        $inverterArray['invStatus'] = 'alert';
                    }
                    if ($inverterArray['anzInverterAlert'] > 1) {
                        $inverterArray['invStatus'] = 'alert';
                    }
                }
            }
        }
        

        return $inverterArray;
    }

    /**
     * Ermittelt den Status der Strings.
     */
    private function checkStrings(Anlage $anlage, $timestampModulo): array
    {
        $conn = $this->pdoService->getPdoPlant();

        $from = date('Y-m-d H:i', $timestampModulo - 1800); // nur die letzte halbe Stunde auswerten
        $to = date('Y-m-d H:i', $timestampModulo);
        $stringArray = [];
        $stringArray['scoreCurrent'] = 0;
        $stringArray['scoreVoltage'] = 0;
        $stringArray['anzCurrentWarning'] = 0;
        $stringArray['anzCurrentAlert'] = 0;
        $stringArray['anzVoltageWarning'] = 0;
        $stringArray['anzVoltageAlert'] = 0;
        $stringArray['errorMessage'] = '';
        $stringArray['stringIStatus'] = '';
        $stringArray['stringUStatus'] = '';
        $stringArray['dcStatus'] = '';
        $stringArray['stringStatus'] = '';
        $anzCurrentAlert = 0;
        $anzVoltageAlert = 0;

        $dcGroups = $anlage->getGroupsDc();
        $sqlActAnlage = 'SELECT * FROM '.$anlage->getDbNameAcIst()." WHERE stamp BETWEEN '$from' AND '$to' ORDER BY stamp";

        $result = $conn->query($sqlActAnlage);
        while ($rowAnlage = $result->fetch(PDO::FETCH_OBJ)) {
            $stamp = $rowAnlage->stamp;
            if (true) { // (isStartEndTimeValid($stamp))
                $inverter = $rowAnlage->unit ?? 0;
                $group = $rowAnlage->group_ac ?? 0;
                $anzStringsCurrent = 0;
                $anzStringsVoltage = 0;
                if (isset($rowAnlage->wr_mpp_current)) {
                    $stringCurrent = json_decode((string) $rowAnlage->wr_mpp_current, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($stringCurrent)) {
                        $anzStringsCurrent = count($stringCurrent);
                        $sumStringCurrent = array_sum($stringCurrent);
                        ($anzStringsCurrent > 0) ? $avgStringCurrent = $sumStringCurrent / $anzStringsCurrent : $avgStringCurrent = 0;
                    }
                }
                if (isset($rowAnlage->wr_mpp_current)) {
                    $stringVoltage = json_decode((string) $rowAnlage->wr_mpp_voltage, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($stringVoltage)) {
                        $anzStringsVoltage = count($stringVoltage);
                        $sumStringVoltage = array_sum($stringVoltage);
                        ($anzStringsVoltage > 0) ? $avgStringVoltage = $sumStringVoltage / $anzStringsVoltage : $avgStringVoltage = 0;
                    }
                }

                // In diesem 2dimensionalen Arrays werden alle Strings gespeichert bei denn ein Fehler aufgetreten ist,
                // in der 2ten dimension wird die Zeit gespeichert wann der fehler auftrat
                // je ein Array für warnungen und error und jeweils eins für ausfall (lost) und Abweichung (diff)
                $arrayErrorTimePerStringLostCurrent = [];
                $arrayWarningTimePerStringLostCurrent = [];
                $arrayErrorTimePerStringDiffCurrent = [];
                $arrayWarningTimePerStringDiffCurrent = [];

                $arrayErrorTimePerStringLostVoltage = [];
                $arrayWarningTimePerStringVoltage = [];
                $arrayErrorTimePerStringDiffVoltage = [];
                $arrayWarningTimePerStringDiffVoltage = [];

                // Current
                if ($anzStringsCurrent > 0) {
                    foreach ($stringCurrent as $stringKey => $stringValue) {
                        if ($stringValue == 0) {
                            $stringArray['errorMessage'] .= 'Alert at group '.$dcGroups[$group]['GroupName'].", inverter $inverter, string $stringKey: no current. ($stamp)<br>";
                            $stringArray['scoreCurrent'] += 5;
                            ++$anzCurrentAlert; // Hilfszähler muss für Alert min 2 Sein
                            $arrayErrorTimePerStringLostCurrent["$inverter-$stringKey"] = $stamp;
                        }
                        /*
                         else  {
                            if ($stringValue < ($GLOBALS['abweichung']['string']['string']['error'] / 100 * $avgStringCurrent)) {
                                $stringArray['errorMessage'] .= "Alert at group " . $dcGroups[$group]['GroupName'] . ", inverter $inverter, string $stringKey: difference alert current. ($stamp)<br>";
                                $stringArray['scoreCurrent'] += 3;
                                $anzCurrentAlert++; // Hilfszähler muss für Alert min 2 Sein
                                $arrayErrorTimePerStringDiffCurrent["$inverter-$stringKey"] = $stamp;
                            } elseif ($stringValue < ($GLOBALS['abweichung']['string']['string']['warning'] / 100 * $avgStringCurrent)) {
                                $stringArray['errorMessage'] .= "Warning at group " . $dcGroups[$group]['GroupName'] . ", inverter $inverter, string $stringKey: difference warning current. ($stamp)<br>";
                                $stringArray['scoreCurrent'] += 1;
                                $stringArray['anzCurrentWarning']++;
                                $arrayWarningTimePerStringDiffCurrent["$inverter-$stringKey"] = $stamp;
                            }
                        }
                        */
                    }
                }

                // Voltage
                if ($anzStringsVoltage > 0) {
                    foreach ($stringVoltage as $stringKey => $stringValue) {
                        if ($stringValue == 0) {
                            $stringArray['errorMessage'] .= 'Alert at group '.$dcGroups[$group]['GroupName'].", inverter $inverter, string $stringKey: no voltage. ($stamp)<br>";
                            $stringArray['scoreVoltage'] += 5;
                            ++$stringArray['anzVoltageAlert'];
                            ++$anzVoltageAlert; // Hilfszähler muss für Alert min 2 Sein
                            $arrayErrorTimePerStringLostVoltage["$inverter-$stringKey"] = $stamp;
                        }
                        /*
                        else {
                            if ($stringValue < ($GLOBALS['abweichung']['string']['string']['error'] / 100 * $avgStringVoltage)) {
                                $stringArray['errorMessage'] .= "Alert at group " . $dcGroups[$group]['GroupName'] . ", inverter $inverter, string $stringKey: difference alert voltage. ($stamp)<br>";
                                $stringArray['scoreVoltage'] += 3;
                                $stringArray['anzVoltageAlert']++;
                                $anzVoltageAlert++; // Hilfszähler muss für Alert min 2 Sein
                                $arrayErrorTimePerStringDiffVoltage["$inverter-$stringKey"] = $stamp;
                            } elseif ($stringValue < ($GLOBALS['abweichung']['string']['string']['warning'] / 100 * $avgStringVoltage)) {
                                $stringArray['errorMessage'] .= "Warning at group " . $dcGroups[$group]['GroupName'] . ", inverter $inverter, string $stringKey: difference warning voltage. ($stamp)<br>";
                                $stringArray['scoreVoltage'] += 1;
                                $stringArray['anzVoltageWarning']++;
                                $arrayWarningTimePerStringDiffVoltage["$inverter-$stringKey"] = $stamp;
                            }
                        }
                        */
                    }
                }

                if ($stringArray['scoreVoltage'] == 0) {
                    $stringArray['stringUStatus'] = 'normal';
                }
                if (($stringArray['scoreVoltage'] >= 1) && ($stringArray['scoreVoltage'] < 5)) {
                    $stringArray['stringUStatus'] = 'warning';
                }
                if ($stringArray['scoreVoltage'] > 5) {
                    $stringArray['stringUStatus'] = 'alert';
                }

                if ($stringArray['scoreCurrent'] == 0) {
                    $stringArray['stringIStatus'] = 'normal';
                }
                if (($stringArray['scoreCurrent'] >= 1) && ($stringArray['scoreCurrent'] < 5)) {
                    $stringArray['stringIStatus'] = 'warning';
                }
                if ($stringArray['scoreCurrent'] > 5) {
                    $stringArray['stringIStatus'] = 'alert';
                }

                if ($stringArray['scoreCurrent'] + $stringArray['scoreVoltage'] == 0) {
                    $stringArray['dcStatus'] = 'normal';
                }
                if (($stringArray['scoreCurrent'] + $stringArray['scoreVoltage'] >= 1) && ($stringArray['scoreCurrent'] + $stringArray['scoreVoltage'] < 5)) {
                    $stringArray['dcStatus'] = 'warning';
                }
                if ($stringArray['scoreCurrent'] + $stringArray['scoreVoltage'] > 5) {
                    $stringArray['dcStatus'] = 'alert';
                }

                $stringArray['lostCurrent'] = $arrayErrorTimePerStringLostCurrent;
                $stringArray['lostVoltage'] = $arrayErrorTimePerStringLostVoltage;
                $stringArray['diffCurrent']['error'] = $arrayErrorTimePerStringDiffCurrent;
                $stringArray['diffVoltage']['error'] = $arrayErrorTimePerStringDiffVoltage;
                $stringArray['diffCurrent']['warning'] = $arrayWarningTimePerStringDiffCurrent;
                $stringArray['diffVoltage']['warning'] = $arrayWarningTimePerStringDiffVoltage;
            }
        }

        if ($anzCurrentAlert >= 2) {
            $stringArray['anzCurrentAlert'] = $anzCurrentAlert;
        }
        if ($anzVoltageAlert >= 2) {
            $stringArray['anzVoltageAlert'] = $anzVoltageAlert;
        }

        

        return $stringArray;
    }
}
