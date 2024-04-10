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
     * @param float $pa0
     * @param float $pa1
     * @param float $pa2
     * @param float $pa3
     * @return array|null
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NonUniqueResultException
     */
    public function correctSensorsByTicket(Anlage $anlage, array $sensorData, DateTime $startDate, DateTime $endDate, float $pa0 = 1, float $pa1 = 1, float $pa2 = 1, float $pa3 = 1): ?array
    {
        // Suche alle Tickets (Ticketdates) die in den Zeitraum fallen
        // Es werden Nur Tickets mit Sensor Bezug gesucht (Performance Tickets mit ID = 72, 73, 71
        $ticketArray = $this->ticketDateRepo->performanceTickets($anlage, $startDate, $endDate);

        // Dursuche alle Tickets in Schleife
        // berechne Wert aus Original Daten und Subtrahiere vom Wert
        // berechne ersatz Wert und Addiere zum entsprechenden Wert
        /** @var TicketDate $ticketDate */
        foreach ($ticketArray as $ticketDate){ #loop über query result
            $interval15 = new \DateInterval('PT15M');
            // Start und End Zeitpunkt ermitteln, es sollen keine Daten gesucht werden die auserhalb des Übergebenen Zeitaums liegen.
            // Ticket kann ja schon vor dem Zeitraum gestartet oder danach erst beendet werden
            $tempStartDate = clone ($startDate > $ticketDate->getBegin() ? $startDate : $ticketDate->getBegin());
            $tempEndDate = clone ($endDate < $ticketDate->getEnd() ? $endDate : $ticketDate->getEnd());
            // Wenn ticket länger als $endDate geht dann 15 minuten aufschlagen um den letzten Wert (end ticket ist Wert an der ANlage wieder geht) mit in die Kalkulation einzubinden
            if ($ticketDate->getEnd() > $tempEndDate) {
                $tempEndDate->add($interval15);
            }
            // erzeuge Time mit 15 Minuten versatz nach hinten -> wenn wir nach wetter Daten suchen wird eine Datums suche > und <= genutzt,
            // bei Tickets muss aber >= und < genutzt werden oder das datum muss um ein 1 quater (15minuten) nach hinten verschoben werden
            $tempStartDateMinus15 = clone $tempStartDate;
            $tempEndDateMinus15 = clone $tempEndDate;
            $tempStartDateMinus15->sub($interval15);
            $tempEndDateMinus15->sub($interval15);

            switch ($ticketDate->getTicket()->getAlertType()) {
                // Exclude Sensors
                case '70':
                    // ToDo: Sensor Daten müssen zur Wetter DB umgezogen werden, dann Code anpassen

                    // Search for sensor (irr) values in ac_ist database
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempStartDateMinus15->format('Y-m-d H:i'), $tempEndDateMinus15->format('Y-m-d H:i'), false, $anlage);
                    $sensorArrays = $this->weatherFunctionsService->getSensors($anlage, $tempStartDate, $tempEndDate);
                    $intervallPAs = $this->weatherFunctionsService->getIntervallPA($anlage, $tempStartDateMinus15, $tempEndDateMinus15);

                    /* deprecated
                    $sensorSum = [];
                    foreach ($sensorArrays as $sensorArray){
                        foreach ($sensorArray as $key => $sensorVal) {
                            if(!key_exists($key,$sensorSum)) $sensorSum[$key] = 0;
                            $sensorSum[$key] += $sensorVal;
                        }
                    }
                    */

                    // ermitteln welche Sensoren excludiert werden sollen
                    $mittelwertPyrHoriArray = $mittelwertPyroArray = $mittelwertPyroEastArray = $mittelwertPyroWestArray = [];
                    $sensorsInUse = $anlage->getSensorsInUse();

                    foreach($sensorArrays as $stamp => $sensorArray) {
                        $countIrrHori = $countIrr = $countIrrEast = $countIrrWest = 0;
                        foreach ($sensorsInUse as $sensor) {
                            if (!str_contains($ticketDate->getSensors(), $sensor->getNameShort())) {
                                switch ($sensor->getVirtualSensor()) {
                                    case 'irr-hori':
                                        $mittelwertPyrHoriArray[$stamp] += $sensorArray[$sensor->getNameShort()];
                                        $countIrrHori++;
                                        break;
                                    case 'irr':
                                        $mittelwertPyroArray[$stamp] += $sensorArray[$sensor->getNameShort()];
                                        $countIrr++;
                                        break;
                                    case 'irr-east':
                                        $mittelwertPyroEastArray[$stamp] += $sensorArray[$sensor->getNameShort()];
                                        $countIrrEast++;
                                        break;
                                    case 'irr-west':
                                        $mittelwertPyroWestArray[$stamp] += $sensorArray[$sensor->getNameShort()];
                                        $countIrrWest++;
                                        break;
                                }
                            }
                        }
                        $mittelwertPyrHoriArray[$stamp] = $countIrrHori > 0 ? $mittelwertPyrHoriArray[$stamp] / $countIrrHori : 0;
                        $mittelwertPyroArray[$stamp] = $countIrr > 0 ? $mittelwertPyroArray[$stamp] / $countIrr : 0;
                        $mittelwertPyroEastArray[$stamp] = $countIrrEast > 0 ? $mittelwertPyroEastArray[$stamp] / $countIrrEast : 0;
                        $mittelwertPyroWestArray[$stamp] = $countIrrWest > 0 ? $mittelwertPyroWestArray[$stamp] / $countIrrWest : 0;
                    }

                    // erechne neuen Mittelwert aus den Sensoren die genutzt werden sollen
                    $replaceArray['horizontalIrr']  = array_sum($mittelwertPyrHoriArray);//self::mittelwert($mittelwertPyrHoriArray);
                    $replaceArray['irrModul']       = array_sum($mittelwertPyroArray);//self::mittelwert($mittelwertPyroArray);
                    $replaceArray['irrEast']        = array_sum($mittelwertPyroEastArray);//self::mittelwert($mittelwertPyroEastArray);
                    $replaceArray['irrWest']        = array_sum($mittelwertPyroWestArray);//self::mittelwert($mittelwertPyroWestArray);



                    // Theoretische Leistung berechnen unter berücksichtigung des PA
                    foreach ($intervallPAs as $stamp => $intervallPA) {
                        // fallback Werte falls PA nicht berechnet
                        if ($anlage->getPrFormular0() != 'Veendam') $intervallPA['pa0'] = 1;
                        if ($anlage->getPrFormular1() != 'Veendam') $intervallPA['pa1'] = 1;
                        if ($anlage->getPrFormular2() != 'Veendam') $intervallPA['pa2'] = 1;
                        if ($anlage->getPrFormular3() != 'Veendam') $intervallPA['pa3'] = 1;
                        if ($anlage->getIsOstWestAnlage()) {
                            $replaceArray['theoPowerPA0'] += (($mittelwertPyroEastArray[$stamp] * $anlage->getPowerEast()) + ($mittelwertPyroWestArray[$stamp] * $anlage->getPowerWest()))  * $intervallPA['pa0'];
                            $replaceArray['theoPowerPA1'] += (($mittelwertPyroEastArray[$stamp] * $anlage->getPowerEast()) + ($mittelwertPyroWestArray[$stamp] * $anlage->getPowerWest()))  * $intervallPA['pa1'];
                            $replaceArray['theoPowerPA2'] += (($mittelwertPyroEastArray[$stamp] * $anlage->getPowerEast()) + ($mittelwertPyroWestArray[$stamp] * $anlage->getPowerWest()))  * $intervallPA['pa2'];
                            $replaceArray['theoPowerPA3'] += (($mittelwertPyroEastArray[$stamp] * $anlage->getPowerEast()) + ($mittelwertPyroWestArray[$stamp] * $anlage->getPowerWest()))  * $intervallPA['pa3'];
                        } else {
                            $replaceArray['theoPowerPA0'] += $mittelwertPyroArray[$stamp] * $anlage->getPnom() * $intervallPA['pa0'];
                            $replaceArray['theoPowerPA1'] += $mittelwertPyroArray[$stamp] * $anlage->getPnom() * $intervallPA['pa1'];
                            $replaceArray['theoPowerPA2'] += $mittelwertPyroArray[$stamp] * $anlage->getPnom() * $intervallPA['pa2'];
                            $replaceArray['theoPowerPA3'] += $mittelwertPyroArray[$stamp] * $anlage->getPnom() * $intervallPA['pa3'];
                        }
                    }
                    $replaceArray['theoPowerPA0'] = $replaceArray['theoPowerPA0'] / 4000;
                    $replaceArray['theoPowerPA1'] = $replaceArray['theoPowerPA1'] / 4000;
                    $replaceArray['theoPowerPA2'] = $replaceArray['theoPowerPA2'] / 4000;
                    $replaceArray['theoPowerPA3'] = $replaceArray['theoPowerPA3'] / 4000;

                    $sensorData = $this->corrIrr($tempWeatherArray, $replaceArray, $sensorData, $ticketDate);
                    break;

                // Replace Sensors
                case '71':
                    $oldWeather = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempStartDateMinus15->format('Y-m-d H:i'), $tempEndDateMinus15->format('Y-m-d H:i'), false, $anlage);
                    $replaceArray = $this->replaceValuesTicketRepo->getSum($anlage, $tempStartDate, $tempEndDateMinus15); // End date muss 15 minuten früher, da end date Ticket erstes Intervall das wieder geht
                    $sensorData = $this->corrIrr($oldWeather, $replaceArray, $sensorData, $ticketDate);
                    break;

                // Exclude from PR/Energy (exclude Irr and TheoPower)
                case '72':
                    $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempStartDateMinus15->format('Y-m-d H:i'), $tempEndDateMinus15->format('Y-m-d H:i'), false, $anlage);

                    // korrigiere Modul Irradiation
                    $sensorData['irr0'] = $sensorData['upperIrr'];
                    $sensorData['irr1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irr1'] - $tempWeatherArray['irr1'] : $sensorData['irr1'];
                    $sensorData['irr2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irr2'] - $tempWeatherArray['irr2'] : $sensorData['irr2'];
                    $sensorData['irr3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irr3'] - $tempWeatherArray['irr3'] : $sensorData['irr3'];

                    $sensorData['irrEast0'] = $sensorData['upperIrr'];
                    $sensorData['irrEast1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrEast1'] - $tempWeatherArray['irrEast1'] : $sensorData['irrEast1'];
                    $sensorData['irrEast2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrEast2'] - $tempWeatherArray['irrEast2'] : $sensorData['irrEast2'];
                    $sensorData['irrEast3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrEast3'] - $tempWeatherArray['irrEast3'] : $sensorData['irrEast3'];

                    $sensorData['irrWest0'] = $sensorData['lowerIrr'];
                    $sensorData['irrWest1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrWest1'] - $tempWeatherArray['irrWest1'] : $sensorData['irrWest1'];
                    $sensorData['irrWest2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrWest2'] - $tempWeatherArray['irrWest2'] : $sensorData['irrWest2'];
                    $sensorData['irrWest3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrWest3'] - $tempWeatherArray['irrWest3'] : $sensorData['irrWest3'];

                    // korrigiere Horizontale Irradiation
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

                // Replace Enery / Irradiation
                case '73':
                    // wenn replace Enery with PVSyst und replace Irradiation
                    if ($ticketDate->isReplaceEnergy() && $ticketDate->isReplaceIrr()) {
                        if ($tempStartDate->format('i') == '00') {
                            $hour = (int)$tempStartDate->format('H') - 1;
                            $tempStartDate = date_create($tempStartDate->format("Y-m-d $hour:15"));
                            $tempEndDate = date_create($tempEndDate->format("Y-m-d H:00"));
                        } else {
                            $tempStartDate = date_create($tempStartDate->format('Y-m-d H:15'));
                            $hour = (int)$tempStartDate->format('H') + 1;
                        }
                        $pvSystStartDate = date_create($tempStartDate->format("Y-m-d $hour:00"));
                        $pvSystEndDate = date_create($tempEndDate->format("Y-m-d H:00"));

                        $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempStartDateMinus15->format('Y-m-d H:i'), $tempEndDateMinus15->format('Y-m-d H:i'), false, $anlage);
                        $replaceArray = $this->getPvSystIrr($anlage, $pvSystStartDate, $pvSystEndDate);
                        $sensorData = $this->corrIrr($tempWeatherArray, $replaceArray, $sensorData, $ticketDate);
                    } elseif ($ticketDate->isReplaceEnergyG4N()) {
                        //ToDo: implement replacement by G4N Expected
                    } else {
                        $replaceValueIrr = $ticketDate->getValueIrr() * 1000;
                        $tempWeatherArray = $this->weatherFunctionsService->getWeather($anlage->getWeatherStation(), $tempStartDateMinus15->format('Y-m-d H:i'), $tempEndDateMinus15->format('Y-m-d H:i'), false, $anlage);
                        if ($replaceValueIrr > 0) {
                            $sensorData['irr1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irr1'] - $tempWeatherArray['irr1'] + $replaceValueIrr : $sensorData['irr1'];
                            $sensorData['irr2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irr2'] - $tempWeatherArray['irr2'] + $replaceValueIrr : $sensorData['irr2'];
                            $sensorData['irr3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irr3'] - $tempWeatherArray['irr3'] + $replaceValueIrr : $sensorData['irr3'];

                            $sensorData['irrEast1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrEast1'] - $tempWeatherArray['irr1'] + $replaceValueIrr : $sensorData['irrEast1'];
                            $sensorData['irrEast2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrEast2'] - $tempWeatherArray['irr2'] + $replaceValueIrr : $sensorData['irrEast2'];
                            $sensorData['irrEast3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrEast3'] - $tempWeatherArray['irr3'] + $replaceValueIrr : $sensorData['irrEast3'];

                            $sensorData['irrWest1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrWest1'] - $tempWeatherArray['irr1'] + $replaceValueIrr : $sensorData['irrWest1'];
                            $sensorData['irrWest2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrWest2'] - $tempWeatherArray['irr2'] + $replaceValueIrr : $sensorData['irrWest2'];
                            $sensorData['irrWest3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrWest3'] - $tempWeatherArray['irr3'] + $replaceValueIrr : $sensorData['irrWest3'];

                            $theoEnergie = $replaceValueIrr * $anlage->getPnom() / 1000;
                            $sensorData['theoPowerPA1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['theoPowerPA1'] - $tempWeatherArray['theoPowerPA1'] + $theoEnergie : $sensorData['theoPowerPA1'];
                            $sensorData['theoPowerPA2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['theoPowerPA2'] - $tempWeatherArray['theoPowerPA2'] + $theoEnergie : $sensorData['theoPowerPA2'];
                            $sensorData['theoPowerPA3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['theoPowerPA3'] - $tempWeatherArray['theoPowerPA3'] + $theoEnergie : $sensorData['theoPowerPA3'];
                        }
                    }
                    break;
            }
        }

        if ($anlage->getIsOstWestAnlage()) {
            $sensorData['irr0'] = ($sensorData['irrEast0'] + $sensorData['irrWest0']) / 2;#($return['irrEast0'] * $anlage->getPowerEast() + $return['irrWest0'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
            $sensorData['irr1'] = ($sensorData['irrEast1'] + $sensorData['irrWest1']) / 2;#($return['irrEast1'] * $anlage->getPowerEast() + $return['irrWest1'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
            $sensorData['irr2'] = ($sensorData['irrEast2'] + $sensorData['irrWest2']) / 2;#($return['irrEast2'] * $anlage->getPowerEast() + $return['irrWest2'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
            $sensorData['irr3'] = ($sensorData['irrEast3'] + $sensorData['irrWest3']) / 2;#($return['irrEast3'] * $anlage->getPowerEast() + $return['irrWest3'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest());
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
    private function corrIrr(?array $oldWeather, ?array $newWeather, ?array $sensorData, TicketDate $ticketDate, bool $debug = false): array
    {
        $return = $sensorData;
        switch ($ticketDate->getAlertType()) {
            case '73':
                if (is_numeric($newWeather['irrModul'])) {
                    $return['irr1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irr1'] - $oldWeather['irr1'] + $newWeather['irrModul'] : $sensorData['irr1'];
                    $return['irr2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irr2'] - $oldWeather['irr2'] + $newWeather['irrModul'] : $sensorData['irr2'];
                    $return['irr3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irr3'] - $oldWeather['irr3'] + $newWeather['irrModul'] : $sensorData['irr3'];

                    $return['irrEast1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrEast1'] - $oldWeather['irr1'] + $newWeather['irrModul'] : $sensorData['irrEast1'];
                    $return['irrEast2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrEast2'] - $oldWeather['irr2'] + $newWeather['irrModul'] : $sensorData['irrEast2'];
                    $return['irrEast3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrEast3'] - $oldWeather['irr3'] + $newWeather['irrModul'] : $sensorData['irrEast3'];

                    $return['irrWest1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrWest1'] - $oldWeather['irr1'] + $newWeather['irrModul'] : $sensorData['irrWest1'];
                    $return['irrWest2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrWest2'] - $oldWeather['irr2'] + $newWeather['irrModul'] : $sensorData['irrWest2'];
                    $return['irrWest3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrWest3'] - $oldWeather['irr3'] + $newWeather['irrModul'] : $sensorData['irrWest3'];
                }

                if (is_numeric($newWeather['power'])) {
                    $return['theoPowerPA1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['theoPowerPA1'] - $oldWeather['theoPowerPA1'] + $newWeather['power'] : $sensorData['theoPowerPA1'];
                    $return['theoPowerPA2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['theoPowerPA2'] - $oldWeather['theoPowerPA2'] + $newWeather['power'] : $sensorData['theoPowerPA2'];
                    $return['theoPowerPA3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['theoPowerPA3'] - $oldWeather['theoPowerPA3'] + $newWeather['power'] : $sensorData['theoPowerPA3'];
                }
                break;

            default:
                // korrigiere Horizontal Irradiation
                if (is_numeric($newWeather['irrHorizotal'])) {
                    $return['irrHor0'] =  $return['horizontalIrr']    = $sensorData['horizontalIrr'] - $oldWeather['horizontalIrr'] + $newWeather['irrHorizotal'];
                    #$return['irrHor0']          = $oldWeather['horizontalIrr'];
                    $return['irrHor1']          = $ticketDate->getTicket()->isScope(10) ? $sensorData['horizontalIrr'] : $sensorData['irrHor1'];
                    $return['irrHor2']          = $ticketDate->getTicket()->isScope(20) ? $sensorData['horizontalIrr'] : $sensorData['irrHor2'];
                    $return['irrHor3']          = $ticketDate->getTicket()->isScope(30) ? $sensorData['horizontalIrr'] : $sensorData['irrHor3'];
                }

                // korrigiere Irradiation auf Modulebene

                if ($newWeather['irrModul'] && $newWeather['irrModul'] > 0) {
                    $return['irr0']    = $return['upperIrr']     = $sensorData['upperIrr'] - $oldWeather['upperIrr'] + $newWeather['irrModul'];
                    $return['irr1']    = $ticketDate->getTicket()->isScope(10) ? $sensorData['irr1'] - $oldWeather['upperIrr'] + $newWeather['irrModul'] : $sensorData['irr1'];
                    $return['irr2']    = $ticketDate->getTicket()->isScope(20) ? $sensorData['irr2'] - $oldWeather['upperIrr'] + $newWeather['irrModul'] : $sensorData['irr2'];
                    $return['irr3']    = $ticketDate->getTicket()->isScope(30) ? $sensorData['irr3'] - $oldWeather['upperIrr'] + $newWeather['irrModul'] : $sensorData['irr3'];
                }
                // zwei Ausrichtungen (Ost / West)
                if ($newWeather['irrEast'] && $newWeather['irrEast'] > 0) {
                    $return['irrEast0'] = $return['upperIrr'] = $sensorData['upperIrr'] - $oldWeather['upperIrr'] + $newWeather['irrEast'];

                    $return['irrEast1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrEast1'] - $oldWeather['upperIrr'] + $newWeather['irrEast'] : $sensorData['irrEast1'];
                    $return['irrEast2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrEast2'] - $oldWeather['upperIrr'] + $newWeather['irrEast'] : $sensorData['irrEast2'];
                    $return['irrEast3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrEast3'] - $oldWeather['upperIrr'] + $newWeather['irrEast'] : $sensorData['irrEast3'];
                }
                if ($newWeather['irrWest'] && $newWeather['irrWest'] > 0) {
                    $return['irrWest0'] = $return['lowerIrr'] = $sensorData['lowerIrr'] - $oldWeather['lowerIrr'] + $newWeather['irrWest'];

                    $return['irrWest1'] = $ticketDate->getTicket()->isScope(10) ? $sensorData['irrWest1'] - $oldWeather['lowerIrr'] + $newWeather['irrWest'] : $sensorData['irrWest1'];
                    $return['irrWest2'] = $ticketDate->getTicket()->isScope(20) ? $sensorData['irrWest2'] - $oldWeather['lowerIrr'] + $newWeather['irrWest'] : $sensorData['irrWest2'];
                    $return['irrWest3'] = $ticketDate->getTicket()->isScope(30) ? $sensorData['irrWest3'] - $oldWeather['lowerIrr'] + $newWeather['irrWest'] : $sensorData['irrWest3'];
                }

                $return['theoPowerPA0'] = $sensorData['theoPowerPA0'] - $oldWeather['theoPowerPA0'] + $newWeather['theoPowerPA0'];
                $return['theoPowerPA1'] = $sensorData['theoPowerPA1'] - $oldWeather['theoPowerPA1'] + $newWeather['theoPowerPA1'];
                $return['theoPowerPA2'] = $sensorData['theoPowerPA2'] - $oldWeather['theoPowerPA2'] + $newWeather['theoPowerPA2'];
                $return['theoPowerPA3'] = $sensorData['theoPowerPA3'] - $oldWeather['theoPowerPA3'] + $newWeather['theoPowerPA3'];

        }

        return $return;
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