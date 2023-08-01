<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlageAcGroups;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\GridMeterDayRepository;
use App\Repository\PRRepository;
use App\Service\Functions\PowerService;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use Psr\Cache\InvalidArgumentException;

class ExportService
{
    use G4NTrait;

    public function __construct(
        private FunctionsService $functions,
        private PRRepository $PRRepository,
        private AnlageAvailabilityRepository $availabilityRepo,
        private GridMeterDayRepository $gridRepo,
        private WeatherFunctionsService $weatherFunctions,
        private PowerService $powerService,
        private AvailabilityByTicketService $availabilityByTicket
    )
    {
    }

    /**
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     */
    public function gewichtetBavelseValuesExport(Anlage $anlage, DateTime $from, DateTime $to): string
    {
        $tempArray = [];
        $availability = 0;
        $outputArray = [];
        $outputArray[0][1] = "";
        $outputArray[1][1] = "Datum";
        $colCounter = 2;
        foreach ($anlage->getAcGroups() as $groupAC) {
            $outputArray[0][$colCounter] = $groupAC->getAcGroupName();
            $outputArray[1][$colCounter] = "Irr [kWh/qm] (all or east)"; ++$colCounter;
            $outputArray[0][$colCounter] = '';
            $outputArray[1][$colCounter] = "Irr [kWh/qm] (west)"; ++$colCounter;
            $outputArray[0][$colCounter] = '';
            $outputArray[1][$colCounter] = "Irr PPC [kWh/qm] (all or east)"; ++$colCounter;
            $outputArray[0][$colCounter] = '';
            $outputArray[1][$colCounter] = "Irr PPC [kWh/qm] (west)"; ++$colCounter;
            $outputArray[0][$colCounter] = '';
            $outputArray[1][$colCounter] = "gewichtete TheoPower mit TempCorr [kWh]"; ++$colCounter;
            $outputArray[0][$colCounter] = '';
            $outputArray[1][$colCounter] = "gewichtete TheoPower mit TempCorr PPC [kWh]"; ++$colCounter;
        }

        $outputArray[0][$colCounter] = '';
        $outputArray[1][$colCounter] = "Mittelwert Luft Temp [°C]"; ++$colCounter;
        #$outputArray[0][$colCounter] = '';
        #$outputArray[1][$colCounter] = "Verfügbarkeit [%]"; ++$colCounter;
        $outputArray[0][$colCounter] = '';
        $outputArray[1][$colCounter] = "gewichtete Strahlung [kWh/qm]"; ++$colCounter;
        $outputArray[0][$colCounter] = '';
        $outputArray[1][$colCounter] = "gewichtete Strahlung PPC [kWh/qm]"; ++$colCounter;
        $outputArray[0][$colCounter] = '';
        $outputArray[1][$colCounter] = "gewichtete TheoPower mit TempCorr [kWh]"; ++$colCounter;
        $outputArray[0][$colCounter] = '';
        $outputArray[1][$colCounter] = "gewichtete TheoPower mit TempCorr PPC [kWh]"; ++$colCounter;
        $outputArray[0][$colCounter] = '';
        $outputArray[1][$colCounter] = "eGrid [kWh]"; ++$colCounter;
        $outputArray[0][$colCounter] = '';
        $outputArray[1][$colCounter] = "eGrid PPC [kWh]"; ++$colCounter;
        $outputArray[0][$colCounter] = '';
        $outputArray[1][$colCounter] = "Janitza [kWh]"; ++$colCounter;
        $outputArray[0][$colCounter] = '';
        $outputArray[1][$colCounter] = "Janitza PPC [kWh]"; ++$colCounter;

        /* @var AnlageAcGroups $groupAC */

        $rowCounter = 3;
        $intervall = 3600 * 24;
        for ($stamp = (int)$from->format('U'); $stamp <= (int)$to->format('U'); $stamp += $intervall) {
            $gewichteteStrahlung = $gewichteteStrahlungPpc = $gewichteteTheoPower = $gewichteteTheoPowerPpc = $sumEvuPower = $sumEvuPowerPpc = 0;
            $colCounter = 1;
            $outputArray[$rowCounter][$colCounter] = date('Y-m-d H:i', $stamp+900); ++$colCounter;

            // für jede AC Gruppe ermittele Wetterstation, lese Tageswert und gewichte diesen
            foreach ($anlage->getAcGroups() as $groupAC) {
                $weather = $this->functions->getWeatherNew($anlage, $groupAC->getWeatherStation(), date('Y-m-d H:i', $stamp), date('Y-m-d H:i', $stamp+$intervall));
                $acPower = $this->powerService->getSumAcPowerBySection($anlage, date('Y-m-d H:i', $stamp), date('Y-m-d H:i', $stamp+$intervall), $groupAC->getAcGroup());
                $tempArray[] = $weather['airTemp'];
                if ($groupAC->getIsEastWestGroup()) {
                    if ($weather['upperIrr'] > 0 && $weather['lowerIrr'] > 0) {
                        $factorEast = $groupAC->getPowerEast() / $groupAC->getDcPowerInverter();
                        $factorWest = $groupAC->getPowerWest() / $groupAC->getDcPowerInverter();
                        $irradiation = $weather['upperIrr'] * $factorEast + $weather['lowerIrr'] * $factorWest;
                        $irradiationPpc = $weather['upperIrrPpc'] * $factorEast + $weather['lowerIrrPpc'] * $factorWest;
                    } elseif ($weather['upperIrr'] > 0) {
                        $irradiation = $weather['upperIrr'];
                        $irradiationPpc = $weather['upperIrrPpc'];
                    } else {
                        $irradiation = $weather['lowerIrr'];
                        $irradiationPpc = $weather['lowerIrrPpc'];
                    }
                } else {
                    $irradiation = $weather['upperIrr'];
                    $irradiationPpc = $weather['upperIrrPpc'];
                }
                // TheoPower gewichtet berechnen
                $outputArray[$rowCounter][$colCounter] = round($weather['upperIrr'] / 1000 / 4, 6); ++$colCounter;
                $outputArray[$rowCounter][$colCounter] = round($weather['lowerIrrlowerIrr'] / 1000 / 4, 6); ++$colCounter;
                $outputArray[$rowCounter][$colCounter] = round($weather['upperIrrPpc'] / 1000 / 4, 6); ++$colCounter;
                $outputArray[$rowCounter][$colCounter] = round($weather['lowerIrrPpc'] / 1000 / 4, 6); ++$colCounter;
                $outputArray[$rowCounter][$colCounter] = round($acPower['powerTheoFt'], 6); ++$colCounter;
                $outputArray[$rowCounter][$colCounter] = round($acPower['powerTheoFtPpc'], 6); ++$colCounter;

                // Aufsummieren der gewichteten Werte zum Gesamtwert
                $gewichteteTheoPower += $acPower['powerTheoFt'];
                $gewichteteTheoPowerPpc += $acPower['powerTheoFtPpc'];
                $sumEvuPower = $acPower['powerEvu'];
                $sumEvuPowerPpc = $acPower['powerEvuPpc'];
                $gewichteteStrahlung += $groupAC->getGewichtungAnlagenPR() * $irradiation;
                $gewichteteStrahlungPpc += $groupAC->getGewichtungAnlagenPR() * $irradiationPpc;
            }
            $temp = self::mittelwert($tempArray);
            unset($tempArray);
            $availability = $this->availabilityByTicket->calcAvailability($anlage, date_create(date('Y-m-d H:i', $stamp)), date_create(date('Y-m-d H:i', $stamp+$intervall)), null, 2);
            $eGrid = $this->powerService->getGridSum($anlage, date_create(date('Y-m-d H:i', $stamp)), date_create(date('Y-m-d H:i', $stamp+$intervall)));
            $eGridPPC = $this->powerService->getGridSumPpc($anlage, date_create(date('Y-m-d H:i', $stamp)), date_create(date('Y-m-d H:i', $stamp+$intervall)));
            $outputArray[$rowCounter][$colCounter] = $temp; ++$colCounter;
            #$outputArray[$rowCounter][$colCounter] = $availability; ++$colCounter;
            $outputArray[$rowCounter][$colCounter] = $gewichteteStrahlung / 1000 / 4; ++$colCounter;
            $outputArray[$rowCounter][$colCounter] = $gewichteteStrahlungPpc / 1000 / 4; ++$colCounter;
            $outputArray[$rowCounter][$colCounter] = $gewichteteTheoPower; ++$colCounter;
            $outputArray[$rowCounter][$colCounter] = $gewichteteTheoPowerPpc; ++$colCounter;
            $outputArray[$rowCounter][$colCounter] = $eGrid; ++$colCounter;
            $outputArray[$rowCounter][$colCounter] = $eGridPPC; ++$colCounter;
            $outputArray[$rowCounter][$colCounter] = $sumEvuPower; ++$colCounter;
            $outputArray[$rowCounter][$colCounter] = $sumEvuPowerPpc; ++$colCounter;

            ++$rowCounter;
        }

        self::exportCsv($anlage, $from, $outputArray);

        return "fertig";
    }

    /**
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     */
    public function gewichtetTagesstrahlungAsTable(Anlage $anlage, DateTime $from, DateTime $to): string
    {
        $tempArray = [];
        $availability = 0;
        $help = '<tr><th>&nbsp;</th>';
        $output = '<b>'.$anlage->getAnlName().'</b><br>';
        $output .= "<div class='table-scroll'><table><thead><tr><th>Datum</th>";
        foreach ($anlage->getAcGroups() as $groupAC) {
            $output .= '<th>'.$groupAC->getAcGroupName().'</th><th></th><th></th><th></th><th></th><th></th>';
            $help .= '<th><small>Irr [kWh/qm]</small></th><th></th><th><small>Irr PPC [kWh/qm]</small></th><th></th><th><small>gewichtete TheoPower mit TempCorr [kWh]</small></th><th><small>gewichtete TheoPower mit TempCorr PPC [kWh]</small></th>'; // part of second row Headline
        }
        $output .= '<td>Mittelwert Luft Temp.</td><td>Verfügbarkeit</td><td>gewichtete Strahlung</td><td>gewichtete Strahlung PPC</td><td>gewichtete TheoPower mit TempCorr</td><td>gewichtete TheoPower mit TempCorr PPC</td><td></td><td></td><td></td><td></td></tr>';
        $help .= '<td>°C</td><td>[%]</td><td>[kWh/qm]</td><td>[kWh/qm]</td><td>[kWh]</td><td>[kWh]</td><td>eGrid</td><td>eGrid PPC</td><td>Janitza</td><td>Janitza PPC</td></tr>'; // part of second row Headline
        $output .= $help.'</thead><tbody>';

        /* @var AnlageAcGroups $groupAC */
        for ($stamp = (int)$from->format('U') + (5*3600); $stamp <= (int)$to->format('U'); $stamp += 86400) {
            $gewichteteStrahlung = $gewichteteStrahlungPpc = $gewichteteTheoPower = $gewichteteTheoPowerPpc = $sumEvuPower = $sumEvuPowerPpc = 0;
            $output .= '<tr>';
            $output .= '<td><small>'.date('Y-m-d', $stamp).'</small></td>';

            // für jede AC Gruppe ermittele Wetterstation, lese Tageswert und gewichte diesen
            foreach ($anlage->getAcGroups() as $groupAC) {
                $weather = $this->functions->getWeatherNew($anlage, $groupAC->getWeatherStation(), date('Y-m-d 00:00', $stamp), date('Y-m-d 23:59', $stamp));
                $acPower = $this->powerService->getSumAcPowerBySection($anlage, date('Y-m-d 00:00', $stamp), date('Y-m-d 23:59', $stamp), $groupAC->getAcGroup());
                $tempArray[] = $weather['airTemp'];
                if ($groupAC->getIsEastWestGroup()) {
                    if ($weather['upperIrr'] > 0 && $weather['lowerIrr'] > 0) {
                        $factorEast = $groupAC->getPowerEast() / $groupAC->getDcPowerInverter();
                        $factorWest = $groupAC->getPowerWest() / $groupAC->getDcPowerInverter();
                        $irradiation = $weather['upperIrr'] * $factorEast + $weather['lowerIrr'] * $factorWest;
                        $irradiationPpc = $weather['upperIrrPpc'] * $factorEast + $weather['lowerIrrPpc'] * $factorWest;
                    } elseif ($weather['upperIrr'] > 0) {
                        $irradiation = $weather['upperIrr'];
                        $irradiationPpc = $weather['upperIrrPpc'];
                    } else {
                        $irradiation = $weather['lowerIrr'];
                        $irradiationPpc = $weather['lowerIrrPpc'];
                    }
                } else {
                    $irradiation = $weather['upperIrr'];
                    $irradiationPpc = $weather['upperIrrPpc'];
                }
                // TheoPower gewichtet berechnen
                $output .= '<td><small>'.round($weather['upperIrr'] / 1000 / 4, 2).'</small></td>
                            <td><small>'.round($weather['lowerIrr'] / 1000 / 4, 2).'</small></td>
                            <td><small>'.round($weather['upperIrrPpc'] / 1000 / 4, 2).'</small></td>
                            <td><small>'.round($weather['lowerIrrPpc'] / 1000 / 4, 2).'</small></td>
                            <td><small>'.round($acPower['powerTheoFt'], 2).'</small></td>
                            <td><small>'.round($acPower['powerTheoFtPpc'], 2).'</small></td>';

                // Aufsummieren der gewichteten Werte zum Gesamtwert
                $gewichteteTheoPower += $acPower['powerTheoFt'];
                $gewichteteTheoPowerPpc += $acPower['powerTheoFtPpc'];
                $sumEvuPower = $acPower['powerEvu'];
                $sumEvuPowerPpc = $acPower['powerEvuPpc'];
                $gewichteteStrahlung += $groupAC->getGewichtungAnlagenPR() * $irradiation;
                $gewichteteStrahlungPpc += $groupAC->getGewichtungAnlagenPR() * $irradiationPpc;
            }
            $availability = $this->availabilityByTicket->calcAvailability($anlage, date_create(date('Y-m-d 00:00', $stamp)), date_create(date('Y-m-d 23:59', $stamp)), null, 2);
            $output .= '<td>'.round(self::mittelwert($tempArray), 3).'</td>';
            $output .= '<td>'.round($availability, 2).'</td>';
            $output .= '<td>'.round($gewichteteStrahlung / 1000 / 4, 4).'</td>';
            $output .= '<td>'.round($gewichteteStrahlungPpc / 1000 / 4, 4).'</td>';
            $output .= '<td>'.round($gewichteteTheoPower, 2).'</td>';
            $output .= '<td>'.round($gewichteteTheoPowerPpc, 2).'</td>';
            $output .= '<td>'.round($this->powerService->getGridSum($anlage, date_create(date('Y-m-d 00:00', $stamp)), date_create(date('Y-m-d 23:59', $stamp))),2).'</td>';
            $output .= '<td>'.round($this->powerService->getGridSumPpc($anlage, date_create(date('Y-m-d 00:00', $stamp)), date_create(date('Y-m-d 23:59', $stamp))),2).'</td>';
            $output .= '<td>'.round($sumEvuPower,2).'</td>';
            $output .= '<td>'.round($sumEvuPowerPpc,2).'</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table></div>';

        return $output;
    }

    public function gewichtetTagesstrahlungOneLine(Anlage $anlage, DateTime $from, DateTime $to): string
    {
        $tempArray = [];
        $availability = 0;
        $help = '<tr><th>&nbsp;</th>';
        $output = '<b>'.$anlage->getAnlName().'</b><br>';
        $output .= "<div class='table-scroll'><table><thead><tr><th>Datum</th>";
        foreach ($anlage->getAcGroups() as $groupAC) {
            $output .= '<th>'.$groupAC->getAcGroupName().'</th><th></th><th></th><th></th><th></th><th></th>';
            $help .= '<th><small>Irr [kWh/qm]</small></th><th></th><th><small>Irr PPC [kWh/qm]</small></th><th></th><th><small>gewichtete TheoPower mit TempCorr [kWh]</small></th><th><small>gewichtete TheoPower mit TempCorr PPC [kWh]</small></th>'; // part of second row Headline
        }
        $output .= '<td>Mittelwert Luft Temp.</td><td>Verfügbarkeit</td><td>gewichtete Strahlung</td><td>gewichtete Strahlung PPC</td><td>gewichtete TheoPower mit TempCorr</td><td>gewichtete TheoPower mit TempCorr PPC</td><td></td><td></td><td></td><td></td></tr>';
        $help .= '<td>°C</td><td>[%]</td><td>[kWh/qm]</td><td>[kWh/qm]</td><td>[kWh]</td><td>[kWh]</td><td>eGrid</td><td>eGrid PPC</td><td>Janitza</td><td>Janitza PPC</td></tr>'; // part of second row Headline
        $output .= $help.'</thead><tbody>';

        /* @var AnlageAcGroups $groupAC */
        $gewichteteStrahlung = $gewichteteStrahlungPpc = $gewichteteTheoPower = $gewichteteTheoPowerPpc = $sumEvuPower = $sumEvuPowerPpc = 0;
        $output .= '<tr>';
        $output .= '<td><small>'.$from->format('Y-m-d').' - '.$to->format('Y-m-d').'</small></td>';

        // für jede AC Gruppe ermittele Wetterstation, lese Tageswert und gewichte diesen
        foreach ($anlage->getAcGroups() as $groupAC) {
            $weather = $this->functions->getWeatherNew($anlage, $groupAC->getWeatherStation(), $from->format('Y-m-d 00:00'), $to->format('Y-m-d 23:59'));
            $acPower = $this->powerService->getSumAcPowerBySection($anlage, $from->format('Y-m-d 00:00'), $to->format('Y-m-d 23:59'), $groupAC->getAcGroup());
            $tempArray[] = $weather['airTemp'];
            if ($groupAC->getIsEastWestGroup()) {
                if ($weather['upperIrr'] > 0 && $weather['lowerIrr'] > 0) {
                    $factorEast = $groupAC->getPowerEast() / $groupAC->getDcPowerInverter();
                    $factorWest = $groupAC->getPowerWest() / $groupAC->getDcPowerInverter();
                    $irradiation = $weather['upperIrr'] * $factorEast + $weather['lowerIrr'] * $factorWest;
                    $irradiationPpc = $weather['upperIrrPpc'] * $factorEast + $weather['lowerIrrPpc'] * $factorWest;
                } elseif ($weather['upperIrr'] > 0) {
                    $irradiation = $weather['upperIrr'];
                    $irradiationPpc = $weather['upperIrrPpc'];
                } else {
                    $irradiation = $weather['lowerIrr'];
                    $irradiationPpc = $weather['lowerIrrPpc'];
                }
            } else {
                $irradiation = $weather['upperIrr'];
                $irradiationPpc = $weather['upperIrrPpc'];
            }
            // TheoPower gewichtet berechnen
            $output .= '<td><small>'.round($weather['upperIrr'] / 1000 / 4, 2).'</small></td>
                        <td><small>'.round($weather['lowerIrr'] / 1000 / 4, 2).'</small></td>
                        <td><small>'.round($weather['upperIrrPpc'] / 1000 / 4, 2).'</small></td>
                        <td><small>'.round($weather['lowerIrrPpc'] / 1000 / 4, 2).'</small></td>
                        <td><small>'.round($acPower['powerTheoFt'], 2).'</small></td>
                        <td><small>'.round($acPower['powerTheoFtPpc'], 2).'</small></td>';

            // Aufsummieren der gewichteten Werte zum Gesamtwert
            $gewichteteTheoPower += $acPower['powerTheoFt'];
            $gewichteteTheoPowerPpc += $acPower['powerTheoFtPpc'];
            $sumEvuPower = $acPower['powerEvu'];
            $sumEvuPowerPpc = $acPower['powerEvuPpc'];
            $gewichteteStrahlung += $groupAC->getGewichtungAnlagenPR() * $irradiation;
            $gewichteteStrahlungPpc += $groupAC->getGewichtungAnlagenPR() * $irradiationPpc;
        }
        #$availability = $this->availabilityRepo->sumAvailabilityPerDay($anlage->getAnlId(), date('Y-m-d', $stamp));
        $availability = $this->availabilityByTicket->calcAvailability($anlage, $from, $to, null, 2);
        $output .= '<td>'.round(self::mittelwert($tempArray), 3).'</td>';
        $output .= '<td>'.round($availability, 2).'</td>';
        $output .= '<td>'.round($gewichteteStrahlung / 1000 / 4, 4).'</td>';
        $output .= '<td>'.round($gewichteteStrahlungPpc / 1000 / 4, 4).'</td>';
        $output .= '<td>'.round($gewichteteTheoPower, 2).'</td>';
        $output .= '<td>'.round($gewichteteTheoPowerPpc, 2).'</td>';
        $output .= '<td>'.round($this->powerService->getGridSum($anlage, $from, $to),2).'</td>';
        $output .= '<td>'.round($this->powerService->getGridSumPpc($anlage, $from, $to),2).'</td>';
        $output .= '<td>'.round($sumEvuPower,2).'</td>';
        $output .= '<td>'.round($sumEvuPowerPpc,2).'</td>';
        $output .= '</tr>';

        $output .= '</tbody></table></div>';

        return $output;
    }

    /**
     * Exportiert die FAC relevanten Daten, Summiert auf Tage
     *
     * @param Anlage $anlage
     * @param DateTime $from
     * @param DateTime|null $to
     * @param string $target (array = php array zur Weiterverarbeitung, csv = export als csv Datei)
     * @return array
     */
    public function getFacPRData(Anlage $anlage, DateTime $from, ?DateTime $to = null, string $target = 'array'): array
    {
        $conn = self::getPdoConnection();

        $export = [];
        $fromSql = $from->format('Y-m-d 00:00');
        $toSql = $to->format('Y-m-d 23:59');
        $nameArray = $this->functions->getNameArray($anlage);
        $startDay = strtotime($from->format('Y-m-d 05:00')); // start at 5 o'clock to prevent problems with DLS
        $endDay = strtotime($to->format('Y-m-d 23:59'));

        // Export der PR Tageswerte (stamp, egrid, Pnorm_ft, Ft ??, )
        for ($dayStamp = $startDay; $dayStamp <= $endDay; $dayStamp += 86400) { // 24*60*60 = 86400
            $localFrom = date('Y-m-d 00:00', $dayStamp);
            $localTo = date('Y-m-d 23:59', $dayStamp);
            $stamp = date('Y-m-d', $dayStamp);
            $sumAcPower = $this->functions->getSumAcPower($anlage, $localFrom, $localTo);
            if ($anlage->getUseGridMeterDayData()){
                $export[$stamp]['eGrid'] = $sumAcPower['powerEGridExt'];
            } else {
                $export[$stamp]['eGrid'] = $sumAcPower['powerEvu'];
            }
            $export[$stamp]['theoPower'] = 0;
            $export[$stamp]['theoPowerFT'] = $sumAcPower['powerTheo'];

            $sql = 'SELECT sum(g_upper) as upper, sum(g_lower) as lower, sum(g_horizontal) as horizontal FROM '.$anlage->getDbNameWeather()." WHERE stamp BETWEEN '$localFrom' AND '$localTo'";
            $res = $conn->prepare($sql);
            $res->execute();
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_OBJ);
                // Strahlung berechnen
                if ($anlage->getIsOstWestAnlage()) {
                    // Strahlung (upper = Ost / lower = West)
                    $export[$stamp]['irr_mod'] = ($row->upper * $anlage->getPowerEast() + $row->lower * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 4 / 1000; // Umrechnug zu kWh
                } else {
                    $export[$stamp]['irr_mod'] = $row->upper / 4 / 1000; // Umrechnug zu kWh
                }
            } else {
                $export[$stamp]['irr_mod'] = null;
            }

            $export[$stamp]['theoPower'] = $export[$stamp]['irr_mod'] * $anlage->getPnom();
        }

        return $export;
    }

    public function getFacPAData(Anlage $anlage, DateTime $from, DateTime $to = null): array
    {
        $conn = self::getPdoConnection();

        $export = [];
        $fromSql = $from->format('Y-m-d 00:00');
        $toSql = $to->format('Y-m-d 23:59');
        $nameArray = $this->functions->getNameArray($anlage);
        $startDay = strtotime($from->format('Y-m-d 00:00'));
        $endDay = strtotime($to->format('Y-m-d 23:59'));

        // Export der PR Tageswerte (stamp, egrid, Pnorm_ft, Ft ??, )
        for ($dayStamp = $startDay; $dayStamp <= $endDay; $dayStamp += 86400) { // 24*60*60 = 86400
            $localFrom = date('Y-m-d 00:00', $dayStamp);
            $localTo = date('Y-m-d 23:59', $dayStamp);
            $stamp = date('Y-m-d', $dayStamp);

            $availabilitys = $this->availabilityRepo->findBy(['anlage' => $anlage, 'stamp' => date_create($stamp)]);
            foreach ($availabilitys as $availability) {
                $export[$stamp]['stamp'] = $stamp;
                // $export[$stamp]['Inverter']     = $nameArray[$availability->getInverter()];
                $export[$stamp][$nameArray[$availability->getInverter()].'_t'] = $availability->getCase2() - $availability->getCase3() - $availability->getCase4();
                $export[$stamp][$nameArray[$availability->getInverter()].'_ttheo'] = $availability->getControl() - $availability->getCase1();
            }
        }

        return $export;
    }

    public function getRawData(Anlage $anlage, DateTime $from = null, DateTime $to = null): string
    {
        $conn = self::getPdoConnection();
        $output = '';
        $fromSql = $from->format('Y-m-d 00:00');
        $toSql = $to->format('Y-m-d 23:59');
        $nameArray = $this->functions->getNameArray($anlage);
        $dcPNormPerInverter = $anlage->getPnomInverterArray();

        $sql = 'SELECT * FROM '.$anlage->getDbNameIst()." WHERE stamp BETWEEN '$fromSql' AND '$toSql';";
        $res = $conn->prepare($sql);
        $res->execute();
        if ($res->rowCount() > 0) {
            $fp = fopen('daten-'.$anlage->getAnlName().'-'.$from->format('Y-m').'.csv', 'a');
            $first = true;
            $i = 0;
            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                $export['timestamp'] = $row['stamp'];
                $export['inverter'] = $nameArray[$row['unit']];
                $export['p_ac'] = (float) $row['wr_pac'];
                $export['temp_corr'] = (float) $row['temp_corr'];
                $export['theo_power'] = (float) $row['theo_power'];
                $export['pNormInv'] = $dcPNormPerInverter[$row['unit']] / 1000; // Umrechnung von Wp auf kWp
                $irrAnlage = json_decode($row['irr_anlage'], true);
                $tempAnlage = json_decode($row['temp_anlage'], true);
                $windAnlage = json_decode($row['wind_anlage'], true);

                foreach ($irrAnlage as $key => $value) {
                    $export[$key] = $value;
                }
                foreach ($tempAnlage as $key => $value) {
                    $export[$key] = $value;
                }
                foreach ($windAnlage as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $subKey => $subValue) {
                            $export[$subKey] = $subValue;
                        }
                    } else {
                        $export[$key] = $value;
                    }
                }
                // erzeuge Headline bei erstem Durchlauf
                if ($first) {
                    $exportHeadline['timestamp'] = 'Timestamp';
                    // $exportHeadline['section']  = 'Section';
                    $exportHeadline['inverter'] = 'Inverter';
                    $exportHeadline['p_ac'] = 'Power_AC';
                    $exportHeadline['temp_corr'] = 'Temp_Corr';
                    $exportHeadline['theo_power'] = 'Theo_Power';
                    $exportHeadline['pNormInv'] = 'pNormInv';
                    foreach ($irrAnlage as $key => $value) {
                        $exportHeadline[$key] = $key;
                    }
                    foreach ($tempAnlage as $key => $value) {
                        $exportHeadline[$key] = $key;
                    }
                    foreach ($windAnlage as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $subKey => $subValue) {
                                $exportHeadline[$subKey] = $subKey;
                            }
                        } else {
                            $exportHeadline[$key] = $key;
                        }
                    }
                    fputcsv($fp, $exportHeadline);
                    unset($exportHeadline);
                }
                $first = false;
                fputcsv($fp, $export);
                unset($export);
                ++$i;
                if ($i % 1000 == 0) {
                    fclose($fp);
                    $fp = fopen('daten '.$anlage->getAnlName().'-'.$from->format('Y-m').'.csv', 'a');
                }
            }
            fclose($fp);
        }
        $res = null;
        $conn = null;
        unset($res);

        return $output;
    }

    private function exportCsv(Anlage $anlage, DateTime $from, array $data): string
    {
        // Start the output buffer.
        ob_start();

        // Set PHP headers for CSV output.
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=csv_export.csv');

        // Clean up output buffer before writing anything to CSV file.
        ob_end_clean();

        // Create a file pointer with PHP.
        #$output = fopen('daten-'.$anlage->getAnlName().'-'.$from->format('Y-m').'.csv', 'a'); //
        $output = fopen('php://output', 'w');

        $i=1;
        // Loop through the prepared data to output it to CSV file.
        foreach ($data as $data_item) {
            fputcsv(
                $output,
                $data_item,
                ";", "\"", "\\","\n");

            ++$i;
            if ($i % 1000 == 0) {
                #fclose($output);
                #$output = fopen('daten '.$anlage->getAnlName().'-'.$from->format('Y-m').'.csv', 'a');
            }
        }

        fclose($output);
        unset ($output);

        return "";
    }
}
