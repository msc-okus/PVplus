<?php

namespace App\Service\Functions;

use App\Entity\Anlage;
use App\Entity\TicketDate;
use App\Helper\G4NTrait;
use App\Repository\ReplaceValuesTicketRepository;
use App\Repository\TicketDateRepository;
use App\Service\WeatherFunctionsService;
use Doctrine\ORM\NonUniqueResultException;
use DateTime;

class SensorService
{
    use G4NTrait;

    public function __construct(
        private WeatherFunctionsService $weatherFunctionsService,
        private TicketDateRepository    $ticketDateRepo,
        private ReplaceValuesTicketRepository $replaceValuesTicketRepo)
    {
    }

    /**
     * @param Anlage $anlage
     * @param array $sensorData (Wetter / Starahlungs Daten)
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array|null
     * @throws NonUniqueResultException
     */
    public function correctSensorsByTicket(Anlage $anlage, array $sensorData, DateTime $startDate, DateTime $endDate): ?array
    {
        // Suche alle Tickets (Ticketdates) die in den Zeitraum fallen
        // Es werden Nur Tickets mit Sensor Bezug gesucht (Performance Tickets mit ID = 72, 73, 71
        $ticketArray = $this->ticketDateRepo->performanceTickets($anlage, $startDate, $endDate);

        // Dursuche alle Tickets in Schleife
        // berechne Wert aus Original Daten und Subtrahiere vom Wert
        // berechne ersatz Wert und Addiere zum entsprechenden Wert
        /** @var TicketDate $ticket */
        foreach ($ticketArray as $ticket){ #loop über query result
            // Start und End Zeitpunkt ermitteln, es sollen keine Daten gesucht werden die auserhalb des Übergebenen Zeitaums liegen.
            // Ticket kann ja schon vor dem Zeitraum gestartet oder danach erst beendet werden
            $tempoStartDate = $startDate > $ticket->getBegin() ? $startDate : $ticket->getBegin();
            $tempoEndDate = $endDate < $ticket->getEnd() ? $endDate :$ticket->getEnd();

            switch ($ticket->getAlertType()) {
                // Exclude Sensors
                case '70':
                    // Funktioniert in der ersten Version nur für Leek und Kampen
                    // es fehlt die Möglichkeit die gemittelte Strahlung, automatisiert aus den Sensoren zu berechnen
                    // ToDo: Sensor Daten müssen zur Wetter DB umgezogen werden, dann Code anpassen

                    // Search for sensor (irr) values in ac_ist database
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempoStartDate->format('Y-m-d H:i'), $tempoEndDate->format('Y-m-d H:i'), false, $anlage);
                    $sensorArrays = $this->weatherFunctionsService->getSensors($anlage, $tempoStartDate, $tempoEndDate);

                    $sensorSum = [];
                    foreach ($sensorArrays as $sensorArray){
                        foreach ($sensorArray as $key => $sensorVal) {
                            if(!key_exists($key,$sensorSum)) $sensorSum[$key] = 0;
                            $sensorSum[$key] += $sensorVal;
                        }
                    }

                    // ermitteln welche Sensoren excludiert werden SOllen
                    $mittelwertPyrHoriArray = $mittelwertPyroArray = $mittelwertPyroEastArray = $mittelwertPyroWestArray = [];
                    foreach ($anlage->getSensorsInUse() as $sensor) {
                        if (!str_contains($ticket->getSensors(), $sensor->getNameShort())){
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


                    $sensorData = $this->corrIrr($tempWeatherArray, $replaceArray, $sensorData);
                    break;
                // Replace Sensors
                case '71':
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempoStartDate->format('Y-m-d H:i'), $tempoEndDate->format('Y-m-d H:i'), false, $anlage);
                    $replaceArray = $this->replaceValuesTicketRepo->getSum($anlage, $tempoStartDate, $tempoEndDate);
                    dump($tempWeatherArray, $replaceArray, $sensorData);

                    $sensorData = $this->corrIrr($tempWeatherArray, $replaceArray, $sensorData);
                    dump($sensorData);
                    break;
                // Exclude from PR/Energy (exclude Irr and TheoPower)
                case '72':
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempoStartDate->format('Y-m-d H:i'), $tempoEndDate->format('Y-m-d H:i'), false, $anlage);
                    // korrigiere Horizontal Irradiation
                    $sensorData['horizontalIrr'] = $sensorData['horizontalIrr'] - $tempWeatherArray['horizontalIrr'];
                    $sensorData['upperIrr'] = $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'];
                    $sensorData['lowerIrr'] = $sensorData['lowerIrr'] - $tempWeatherArray['lowerIrr'];
                    $sensorData['theoPowerPA0'] = $sensorData['theoPowerPA0'] - $tempWeatherArray['theoPowerPA0'];
                    $sensorData['theoPowerPA1'] = $sensorData['theoPowerPA1'] - $tempWeatherArray['theoPowerPA1'];
                    $sensorData['theoPowerPA2'] = $sensorData['theoPowerPA2'] - $tempWeatherArray['theoPowerPA2'];
                    $sensorData['theoPowerPA3'] = $sensorData['theoPowerPA3'] - $tempWeatherArray['theoPowerPA3'];

                    break;
            }
        }

        return $sensorData;
    }
    private function corrIrr(?array $oldWeather, ?array $newWeather, ?array $sensorData): array
    {
        // korrigiere Horizontal Irradiation
        if ($newWeather['irrHorizotal'] && $newWeather['irrHorizotal'] > 0) {
            $sensorData['horizontalIrr'] = $sensorData['horizontalIrr'] - $oldWeather['horizontalIrr'] + $newWeather['irrHorizotal'];
        }

        // korrigiere Irradiation auf Modulebene
        if (!$newWeather['irrEast'] && !$newWeather['irrWest']) {
            // eine Ausrichtung
            if ($newWeather['irrModul'] && $newWeather['irrModul'] > 0) {
                $sensorData['upperIrr'] = $sensorData['upperIrr'] - $oldWeather['upperIrr'] + $newWeather['irrModul'];
            }
        } else {
            // zwei Ausrichtungen (Ost / West)
            if ($newWeather['irrEast'] && $newWeather['irrEast'] > 0) {
                $sensorData['upperIrr'] = $sensorData['upperIrr'] - $oldWeather['upperIrr'] + $newWeather['irrEast'];
            }
            if ($newWeather['irrWest'] && $newWeather['irrWest'] > 0) {
                $sensorData['lowerIrr'] = $sensorData['lowerIrr'] - $oldWeather['lowerIrr'] + $newWeather['irrWest'];
            }
        }
        #dump($oldWeather, $newWeather);
        $sensorData['theoPowerPA0'] = $sensorData['theoPowerPA0'] - $oldWeather['theoPowerPA0'] + $newWeather['theoPowerPA0'];
        $sensorData['theoPowerPA1'] = $sensorData['theoPowerPA1'] - $oldWeather['theoPowerPA1'] + $newWeather['theoPowerPA1'];
        $sensorData['theoPowerPA2'] = $sensorData['theoPowerPA2'] - $oldWeather['theoPowerPA2'] + $newWeather['theoPowerPA2'];
        $sensorData['theoPowerPA3'] = $sensorData['theoPowerPA3'] - $oldWeather['theoPowerPA3'] + $newWeather['theoPowerPA3'];

        return $sensorData;
    }

}