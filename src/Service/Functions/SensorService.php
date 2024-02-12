<?php

namespace App\Service\Functions;

use App\Entity\Anlage;
use App\Entity\TicketDate;
use App\Helper\G4NTrait;
use App\Repository\PVSystDatenRepository;
use App\Repository\ReplaceValuesTicketRepository;
use App\Repository\TicketDateRepository;
use App\Service\WeatherFunctionsService;
use Doctrine\ORM\NonUniqueResultException;
use DateTime;
use Doctrine\ORM\NoResultException;
use JsonException;
use Psr\Cache\InvalidArgumentException;
use App\Service\PdoService;

class SensorService
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly WeatherFunctionsService $weatherFunctionsService,
        private readonly TicketDateRepository    $ticketDateRepo,
        private readonly ReplaceValuesTicketRepository $replaceValuesTicketRepo,
        private readonly PVSystDatenRepository $pvSystDatenRepo
    )
    {
    }

    /**
     * @param Anlage $anlage
     * @param array $sensorData (Wetter / Strahlungs Daten)
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array|null
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     * @throws JsonException
     */
    public function correctSensorsByTicket(Anlage $anlage, array $sensorData, DateTime $startDate, DateTime $endDate): ?array
    {
        // Suche alle Tickets (Ticketdates) die in den Zeitraum fallen
        // Es werden Nur Tickets mit Sensor Bezug gesucht (Performance Tickets mit ID = 72, 73, 71
        $ticketArray = $this->ticketDateRepo->performanceTickets($anlage, $startDate, $endDate);

        // Dursuche alle Tickets in Schleife
        // berechne Wert aus Original Daten und Subtrahiere vom Wert
        // berechne ersatz Wert und Addiere zum entsprechenden Wert
        /** @var TicketDate $ticketDate */
        foreach ($ticketArray as $ticketDate){ #loop über query result
            // Start und End Zeitpunkt ermitteln, es sollen keine Daten gesucht werden die auserhalb des Übergebenen Zeitaums liegen.
            // Ticket kann ja schon vor dem Zeitraum gestartet oder danach erst beendet werden
            $tempoStartDate = clone ($startDate > $ticketDate->getBegin() ? $startDate : $ticketDate->getBegin());
            $tempoEndDate = clone ($endDate < $ticketDate->getEnd() ? $endDate : $ticketDate->getEnd());
            // erzeuge Time mit 15 Minuten versatz nach hinten -> wenn wir nach wetter Daten suchen wird eine Datums suche > und <= genutzt,
            // bei Tickets muss aber <= und < genutzt werden oder das datum muss um ein 1 quater (15minuten) nach hinten verschoben werden
            $tempStartDateMinus15 = clone $tempoStartDate;
            $tempEndDateMinus15 = clone $tempoEndDate;
            $interval15 = new \DateInterval('PT15M');
            $tempStartDateMinus15->sub($interval15);
            $tempEndDateMinus15->sub($interval15);

            switch ($ticketDate->getTicket()->getAlertType()) {
                // Exclude Sensors
                case '70':
                    // Funktioniert in der ersten Version nur für Leek und Kampen
                    // es fehlt die Möglichkeit die gemittelte Strahlung, automatisiert aus den Sensoren zu berechnen
                    // ToDo: Sensor Daten müssen zur Wetter DB umgezogen werden, dann Code anpassen

                    // Search for sensor (irr) values in ac_ist database
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempStartDateMinus15->format('Y-m-d H:i'), $tempEndDateMinus15->format('Y-m-d H:i'), false, $anlage);
                    $sensorArrays = $this->weatherFunctionsService->getSensors($anlage, $tempoStartDate, $tempoEndDate);

                    $sensorSum = [];
                    foreach ($sensorArrays as $sensorArray){
                        foreach ($sensorArray as $key => $sensorVal) {
                            if(!key_exists($key,$sensorSum)) $sensorSum[$key] = 0;
                            $sensorSum[$key] += $sensorVal;
                        }
                    }

                    // ermitteln welche Sensoren excludiert werden sollen
                    $mittelwertPyrHoriArray = $mittelwertPyroArray = $mittelwertPyroEastArray = $mittelwertPyroWestArray = [];
                    foreach ($anlage->getSensorsInUse() as $sensor) {
                        if (!str_contains($ticketDate->getSensors(), $sensor->getNameShort())){
                            switch ($sensor->getVirtualSensor()){
                                case 'irr-hori':
                                    $mittelwertPyrHoriArray[] = $sensorSum[$sensor->getNameShort()];
                                    break;
                                case 'irr':
                                    $mittelwertPyroArray[] = $sensorSum[$sensor->getNameShort()];
                                    break;
                                case 'irr-east':
                                    $mittelwertPyroEastArray[] = $sensorSum[$sensor->getNameShort()];
                                    break;
                                case 'irr-west':
                                    $mittelwertPyroWestArray[] = $sensorSum[$sensor->getNameShort()];
                                    break;
                            }
                        }
                    }
                    // erechne neuen Mittelwert aus den Sensoren die genutzt werden sollen
                    $replaceArray['horizontalIrr']  = self::mittelwert($mittelwertPyrHoriArray);
                    $replaceArray['irrModul']       = self::mittelwert($mittelwertPyroArray);
                    $replaceArray['irrEast']        = self::mittelwert($mittelwertPyroEastArray);
                    $replaceArray['irrWest']        = self::mittelwert($mittelwertPyroWestArray);

                    ##########################
                    ### TODO: Bessere Lösung suchen, da die nicht funktioniert wenn lange Zeiträume ausgeschlossen werden die PA < 100 haben
                    ##########################
                    if ($anlage->getIsOstWestAnlage()){
                        $replaceArray['theoPowerPA0']   = ($replaceArray['irrEast'] * $anlage->getPowerEast() + $replaceArray['irrWest'] * $anlage->getPowerWest()) / 4000;
                        $replaceArray['theoPowerPA1']   = ($replaceArray['irrEast'] * $anlage->getPowerEast() + $replaceArray['irrWest'] * $anlage->getPowerWest()) / 4000;
                        $replaceArray['theoPowerPA2']   = ($replaceArray['irrEast'] * $anlage->getPowerEast() + $replaceArray['irrWest'] * $anlage->getPowerWest()) / 4000;
                        $replaceArray['theoPowerPA3']   = ($replaceArray['irrEast'] * $anlage->getPowerEast() + $replaceArray['irrWest'] * $anlage->getPowerWest()) / 4000;
                    } else {
                        $replaceArray['theoPowerPA0']   = ($replaceArray['irrModul'] * $anlage->getPnom()) / 4000 ;
                        $replaceArray['theoPowerPA1']   = ($replaceArray['irrModul'] * $anlage->getPnom()) / 4000 ;
                        $replaceArray['theoPowerPA2']   = ($replaceArray['irrModul'] * $anlage->getPnom()) / 4000 ;
                        $replaceArray['theoPowerPA3']   = ($replaceArray['irrModul'] * $anlage->getPnom()) / 4000 ;
                    }
                    $sensorData = $this->corrIrr($tempWeatherArray, $replaceArray, $sensorData, $ticketDate);
                    break;

                // Replace Sensors
                case '71':
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempStartDateMinus15->format('Y-m-d H:i'), $tempEndDateMinus15->format('Y-m-d H:i'), false, $anlage);
                    $replaceArray = $this->replaceValuesTicketRepo->getSum($anlage, $tempoStartDate, $tempoEndDate);
                    $sensorData = $this->corrIrr($tempWeatherArray, $replaceArray, $sensorData, $ticketDate);
                    break;

                // Replace Enery / Irradiation
                case '73':
                    // wenn replace Enery with PVSyst und replace Irradiation
                    if ($ticketDate->isReplaceEnergy() && $ticketDate->isReplaceIrr()) {
                        if ($tempoStartDate->format('i') == '00') {
                            $hour = (int)$tempoStartDate->format('H') - 1;
                            $tempoStartDate = date_create($tempoStartDate->format("Y-m-d $hour:15"));
                            $tempoEndDate = date_create($tempoEndDate->format("Y-m-d H:00"));
                        } else {
                            $tempoStartDate = date_create($tempoStartDate->format('Y-m-d H:15'));
                            $hour = (int)$tempoStartDate->format('H') + 1;
                        }
                        $pvSystStartDate = date_create($tempoStartDate->format("Y-m-d $hour:00"));
                        $pvSystEndDate = date_create($tempoEndDate->format("Y-m-d H:00"));

                        $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempStartDateMinus15->format('Y-m-d H:i'), $tempEndDateMinus15->format('Y-m-d H:i'), false, $anlage);
                        $replaceArray = $this->getPvSystIrr($anlage, $pvSystStartDate, $pvSystEndDate);

                        $sensorData = $this->corrIrr($tempWeatherArray, $replaceArray, $sensorData, $ticketDate);
                    } elseif ($ticketDate->isReplaceEnergyG4N()) {
                        // do nothing at the moment
                    } else {
                        $replaceValueIrr = (float)$ticketDate->getValueIrr();
                        // ToDo: Repolace IRR algorithmus
                    }
                    break;
                    
                // Exclude from PR/Energy (exclude Irr and TheoPower)
                case '72':
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempStartDateMinus15->format('Y-m-d H:i'), $tempEndDateMinus15->format('Y-m-d H:i'), false, $anlage);

                    // korrigiere Horizontal Irradiation
                    $sensorData['irrModul0'] = $sensorData['upperIrr'];
                    $sensorData['irrModul1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'] : $sensorData['upperIrr'];
                    $sensorData['irrModul2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'] : $sensorData['upperIrr'];
                    $sensorData['irrModul3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'] : $sensorData['upperIrr'];

                    $sensorData['irrEast0'] = $sensorData['upperIrr'];
                    $sensorData['irrEast1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrEast1'] - $tempWeatherArray['upperIrr'] : $sensorData['irrEast1'];
                    $sensorData['irrEast2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrEast2'] - $tempWeatherArray['upperIrr'] : $sensorData['irrEast2'];
                    $sensorData['irrEast3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrEast3'] - $tempWeatherArray['upperIrr'] : $sensorData['irrEast3'];

                    $sensorData['irrWest0'] = $sensorData['lowerIrr'];
                    $sensorData['irrWest1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrWest1'] - $tempWeatherArray['lowerIrr'] : $sensorData['irrWest1'];
                    $sensorData['irrWest2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrWest2'] - $tempWeatherArray['lowerIrr'] : $sensorData['irrWest2'];
                    $sensorData['irrWest3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrWest3'] - $tempWeatherArray['lowerIrr'] : $sensorData['irrWest3'];


                    $sensorData['horizontalIrr'] = $sensorData['horizontalIrr'] - $tempWeatherArray['horizontalIrr'];
                    $sensorData['upperIrr'] = $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'];
                    $sensorData['lowerIrr'] = $sensorData['lowerIrr'] - $tempWeatherArray['lowerIrr'];

                    $sensorData['theoPowerPA0'] = $sensorData['theoPowerPA0'] - $tempWeatherArray['theoPowerPA0'];
                    $sensorData['theoPowerPA1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['theoPowerPA1'] - $tempWeatherArray['theoPowerPA1'] : $sensorData['theoPowerPA1'];
                    $sensorData['theoPowerPA2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['theoPowerPA2'] - $tempWeatherArray['theoPowerPA2'] : $sensorData['theoPowerPA2'];
                    $sensorData['theoPowerPA3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['theoPowerPA3'] - $tempWeatherArray['theoPowerPA3'] : $sensorData['theoPowerPA3'];
                    $sensorData['theoPowerTempCorr_NREL']   = $sensorData['theoPowerTempCorr_NREL']     - $tempWeatherArray['theoPowerTempCorr_NREL'];
                    $sensorData['theoPowerTempCorDeg_IEC']  = $sensorData['theoPowerTempCorDeg_IEC']    - $tempWeatherArray['theoPowerTempCorDeg_IEC'];
                    break;
            }
        }

        if ($anlage->getIsOstWestAnlage()) {
            $sensorData['irr0'] = ($sensorData['irrEast0'] * $anlage->getPowerEast() + $sensorData['irrWest0'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
            $sensorData['irr1'] = ($sensorData['irrEast1'] * $anlage->getPowerEast() + $sensorData['irrWest1'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
            $sensorData['irr2'] = ($sensorData['irrEast2'] * $anlage->getPowerEast() + $sensorData['irrWest2'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
            $sensorData['irr3'] = ($sensorData['irrEast3'] * $anlage->getPowerEast() + $sensorData['irrWest3'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
        }

        return $sensorData;
    }

    /**
     * @param array|null $oldWeather
     * @param array|null $newWeather
     * @param array|null $sensorData
     * @param TicketDate $ticketDate
     * @param bool $debug
     * @return array
     */
    private function corrIrr(?array $oldWeather, ?array $newWeather, ?array $sensorData, TicketDate $ticketDate, $debug = false): array
    {
        switch ($ticketDate->getAlertType()) {
            case '73':
                if ($newWeather['irrModul'] && $newWeather['irrModul'] > 0) {
                    $sensorData['irr1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irr1'] - $oldWeather['irr1'] + $newWeather['irrModul'] : $sensorData['irr1'];
                    $sensorData['irr2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irr2'] - $oldWeather['irr2'] + $newWeather['irrModul'] : $sensorData['irr2'];
                    $sensorData['irr3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irr3'] - $oldWeather['irr3'] + $newWeather['irrModul'] : $sensorData['irr3'];
                }
                if ($newWeather['power'] && $newWeather['power'] > 0) {
                    $sensorData['theoPowerPA1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['theoPowerPA1'] - $oldWeather['theoPowerPA1'] + $newWeather['power'] : $sensorData['theoPowerPA1'];
                    $sensorData['theoPowerPA2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['theoPowerPA2'] - $oldWeather['theoPowerPA2'] + $newWeather['power'] : $sensorData['theoPowerPA2'];
                    $sensorData['theoPowerPA3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['theoPowerPA3'] - $oldWeather['theoPowerPA3'] + $newWeather['power'] : $sensorData['theoPowerPA3'];
                }
                break;

            default:
                // korrigiere Horizontal Irradiation
                if ($newWeather['irrHorizotal'] && $newWeather['irrHorizotal'] > 0) {
                    $sensorData['horizontalIrr']    = $sensorData['horizontalIrr'] - $oldWeather['horizontalIrr'] + $newWeather['irrHorizotal'];
                    $sensorData['irrHor0']          = $oldWeather['horizontalIrr'];
                    $sensorData['irrHor1']          = $ticketDate->getTicket()->isScope(10) ? $sensorData['horizontalIrr'] : $sensorData['irrHor1'];
                    $sensorData['irrHor2']          = $ticketDate->getTicket()->isScope(20) ? $sensorData['horizontalIrr'] : $sensorData['irrHor2'];
                    $sensorData['irrHor3']          = $ticketDate->getTicket()->isScope(30) ? $sensorData['horizontalIrr'] : $sensorData['irrHor3'];
                }

                // korrigiere Irradiation auf Modulebene
                if (!$newWeather['irrEast'] && !$newWeather['irrWest']) {
                    // eine Ausrichtung
                    if ($newWeather['irrModul'] && $newWeather['irrModul'] > 0) {
                        $sensorData['upperIrr']     = $sensorData['upperIrr'] - $oldWeather['upperIrr'] + $newWeather['irrModul'];
                        $sensorData['irr0']    = $sensorData['upperIrr'];
                        $sensorData['irr1']    = $ticketDate->getTicket()->isScope(10) ? $sensorData['irr1'] - $oldWeather['upperIrr'] + $newWeather['irrModul'] : $sensorData['irr1'];
                        $sensorData['irr2']    = $ticketDate->getTicket()->isScope(20) ? $sensorData['irr2'] - $oldWeather['upperIrr'] + $newWeather['irrModul'] : $sensorData['irr2'];
                        $sensorData['irr3']    = $ticketDate->getTicket()->isScope(30) ? $sensorData['irr3'] - $oldWeather['upperIrr'] + $newWeather['irrModul'] : $sensorData['irr3'];
                    }
                } else {
                    // zwei Ausrichtungen (Ost / West)
                    if ($newWeather['irrEast'] && $newWeather['irrEast'] > 0) {
                        $sensorData['upperIrr'] = $sensorData['upperIrr'] - $oldWeather['upperIrr'] + $newWeather['irrEast'];
                        $sensorData['irrEast0'] = $sensorData['upperIrr'];
                        $sensorData['irrEast1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrEast1'] - $oldWeather['upperIrr'] + $newWeather['irrEast'] : $sensorData['irrEast1'];
                        $sensorData['irrEast2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrEast2'] - $oldWeather['upperIrr'] + $newWeather['irrEast'] : $sensorData['irrEast2'];
                        $sensorData['irrEast3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrEast3'] - $oldWeather['upperIrr'] + $newWeather['irrEast'] : $sensorData['irrEast3'];
                    }
                    if ($newWeather['irrWest'] && $newWeather['irrWest'] > 0) {
                        $sensorData['lowerIrr'] = $sensorData['lowerIrr'] - $oldWeather['lowerIrr'] + $newWeather['irrWest'];
                        $sensorData['irrWest0'] = $sensorData['lowerIrr'];
                        $sensorData['irrWest1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrWest1'] - $oldWeather['lowerIrr'] + $newWeather['irrWest'] : $sensorData['irrWest1'];
                        $sensorData['irrWest2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrWest2'] - $oldWeather['lowerIrr'] + $newWeather['irrWest'] : $sensorData['irrWest2'];
                        $sensorData['irrWest3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrWest3'] - $oldWeather['lowerIrr'] + $newWeather['irrWest'] : $sensorData['irrWest3'];
                    }
                }

                $sensorData['theoPowerPA0'] = $sensorData['theoPowerPA0'] - $oldWeather['theoPowerPA0'] + $newWeather['theoPowerPA0'];
                $sensorData['theoPowerPA1'] = $sensorData['theoPowerPA1'] - $oldWeather['theoPowerPA1'] + $newWeather['theoPowerPA1'];
                $sensorData['theoPowerPA2'] = $sensorData['theoPowerPA2'] - $oldWeather['theoPowerPA2'] + $newWeather['theoPowerPA2'];
                $sensorData['theoPowerPA3'] = $sensorData['theoPowerPA3'] - $oldWeather['theoPowerPA3'] + $newWeather['theoPowerPA3'];

        }

        return $sensorData;
    }

    private function getPvSystIrr(Anlage $anlage, DateTime $from, DateTime $to): ?array
    {
        $irr['irrHorizontal'] = null;
        $irr['irrEast'] = null;
        $irr['irrWest'] = null;
        try {
            $irr['irrModul'] = $this->pvSystDatenRepo->sumIrrByDateRange($anlage, $from->format('Y-m-d H:i'), $to->format('Y-m-d H:i')) * 4; // Umrechnen in Wh
        } catch (NoResultException|NonUniqueResultException $e) {
            $irr['irrModul'] = null;
        }
        $irr['power'] = $irr['irrModul'] * $anlage->getPnom() / 4000; // Umrechen in kWh

        return $irr;
    }
}