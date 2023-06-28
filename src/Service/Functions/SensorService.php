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
        #dump($startDate, $endDate);
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
            if ($startDate > $ticket->getBegin()){
                $tempoStartDate = $startDate;
            } else {
                $tempoStartDate = $ticket->getBegin();
            }
            if ($endDate < $ticket->getEnd()){
                $tempoEndDate = $endDate;
            } else {
                $tempoEndDate = $ticket->getEnd();
            }


            switch ($ticket->getAlertType()) {
                case '70': // Exclude Sensors
                    // Funktionier in der ersten Version nur für Leek und Kampen
                    // es fehlt die Möglichkeit die gemittelte Strahlung, automatisiert aus den Sensoren zu berechnen
                    // ToDo: Sensor Daten müssen zur Wetter DB umgezogen werden, dann Code anpassen

                    // Search for sensor (irr) values in ac_ist database
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempoStartDate->format('Y-m-d H:i'), $tempoEndDate->format('Y-m-d H:i'));
                    $sensorArrays = $this->weatherFunctionsService->getSensors($anlage, $tempoStartDate, $tempoEndDate);
                    $sensorSum = [];
                    foreach ($sensorArrays as $sensorArray){
                        foreach ($sensorArray as $key => $sensorVal) {
                            if(!key_exists($key,$sensorSum)) $sensorSum[$key] = 0;
                            $sensorSum[$key] += $sensorVal;
                        }
                    }

                    switch ($anlage->getAnlId()) {
                        case '110': // Leek
                        case '207': // Leek Test
                            break;
                        case '108': // Kampen
                        case '184': // Kampen Test
                            break;
                        default:
                            $replaceArray = [];
                            break;
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

                    $sensorData = $this->corrIrr($tempWeatherArray, $replaceArray, $sensorData);
                    break;
                case '71': // Replace Sensors
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempoStartDate->format('Y-m-d H:i'), $tempoEndDate->format('Y-m-d H:i'));
                    $replaceArray = $this->replaceValuesTicketRepo->getSum($anlage, $tempoStartDate, $tempoEndDate);

                    $sensorData = $this->corrIrr($tempWeatherArray, $replaceArray, $sensorData);
                    break;

                case '72': // Exclude (Irr) from PR
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempoStartDate->format('Y-m-d H:i'), $tempoEndDate->format('Y-m-d H:i'));
                    // korriegiere Horizontal Irradiation
                    $sensorData['horizontalIrr'] = $sensorData['horizontalIrr'] - $tempWeatherArray['horizontalIrr'];
                    $sensorData['upperIrr'] = $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'];
                    $sensorData['lowerIrr'] = $sensorData['lowerIrr'] - $tempWeatherArray['lowerIrr'];
                    break;
            }
        }

        return $sensorData;
    }
    private function corrIrr(?array $oldWeather, ?array $newWeather, ?array $sensorData): array
    {

        // korriegiere Horizontal Irradiation
        if ($newWeather['irrHorizotal'] && $newWeather['irrHorizotal'] > 0) {
            $sensorData['horizontalIrr'] = $sensorData['horizontalIrr'] - $oldWeather['horizontalIrr'] + $newWeather['irrHorizotal'];
        }

        // korriegiere Irradiation auf Modulebene
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

        return $sensorData;
    }

}