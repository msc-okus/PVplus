<?php

namespace App\Service\Functions;

use App\Entity\Anlage;
use App\Entity\TicketDate;
use App\Repository\ReplaceValuesTicketRepository;
use App\Repository\TicketDateRepository;
use App\Service\WeatherFunctionsService;
use Doctrine\ORM\NonUniqueResultException;
use DateTime;

class SensorService
{

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

            $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempoStartDate->format('Y-m-d H:i'), $tempoEndDate->format('Y-m-d H:i'));

            switch ($ticket->getAlertType()) {
                case '71':
                    $replaceArray = $this->replaceValuesTicketRepo->getSum($anlage, $tempoStartDate, $tempoEndDate);

                    // korriegiere Horizontal Irradiation
                    if ($replaceArray['irrHorizotal'] && $replaceArray['irrHorizotal'] > 0) {
                        $sensorData['horizontalIrr'] = $sensorData['horizontalIrr'] - $tempWeatherArray['horizontalIrr'] + $replaceArray['irrHorizotal'];
                    }

                    // korriegiere Irradiation auf Modulebene
                    if (!$replaceArray['irrEast'] && !$replaceArray['irrWest']) {
                        // eine Ausrichtung
                        if ($replaceArray['irrModul'] && $replaceArray['irrModul'] > 0) {
                            $sensorData['upperIrr'] = $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'] + $replaceArray['irrModul'];
                        }
                    } else {
                        // zwei Ausrichtungen (Ost / West)
                        if ($replaceArray['irrEast'] && $replaceArray['irrEast'] > 0) {
                            $sensorData['upperIrr'] = $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'] + $replaceArray['irrEast'];
                        }
                        if ($replaceArray['irrWest'] && $replaceArray['irrWest'] > 0) {
                            $sensorData['lowerIrr'] = $sensorData['lowerIrr'] - $tempWeatherArray['lowerIrr'] + $replaceArray['irrWest'];
                        }
                    }
                    break;

                case '72':
                    // korriegiere Horizontal Irradiation
                    $sensorData['horizontalIrr'] = $sensorData['horizontalIrr'] - $tempWeatherArray['horizontalIrr'];
                    $sensorData['upperIrr'] = $sensorData['upperIrr'] - $tempWeatherArray['upperIrr'];
                    $sensorData['lowerIrr'] = $sensorData['lowerIrr'] - $tempWeatherArray['lowerIrr'];

                    break;
            }
        }

        return $sensorData;
    }

}