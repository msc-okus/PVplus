<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use App\Repository\Case5Repository;
use App\Repository\GridMeterDayRepository;
use App\Repository\MonthlyDataRepository;
use App\Repository\PRRepository;
use App\Repository\PVSystDatenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;

class PRCalulationService
{
    use G4NTrait;

    public function __construct(
        private PVSystDatenRepository $pvSystRepo,
        private AnlagenRepository $anlagenRepository,
        private PRRepository $PRRepository,
        private AnlageAvailabilityRepository $anlageAvailabilityRepo,
        private FunctionsService $functions,
        private EntityManagerInterface $em,
        private Case5Repository $case5Repo,
        private MonthlyDataRepository $monthlyDataRepo,
        private WeatherFunctionsService $weatherFunctions,
        private GridMeterDayRepository $gridMeterDayRepo,
        private AvailabilityService $availabilityService,
        private AvailabilityByTicketService $availabilityByTicket
    )
    {
    }

    public function calcPRAll(Anlage|int $anlage, string $day): string
    {
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlage]);
        }

        $timeStamp = strtotime($day);
        // Nur ausführen wenn das zu berechnende Datum vor dem aktuellen Datum liegt (min. Gestern :-))
        if (date('Y-m-d', $timeStamp) < date('Y-m-d', self::getCetTime())) {
            $from = date('Y-m-d 00:00', $timeStamp);
            $to = date('Y-m-d 23:59', $timeStamp);
            $day = date('Y-m-d', $timeStamp);
            $year = date('Y', $timeStamp);
            $month = date('m', $timeStamp);
            $anzTageUntilToday = (int) date('z', $timeStamp) + 1;

            // PAC Date berechnen
            if ($anlage->getUsePac()) {
                $pacDate = $anlage->getPacDate()->format('Y-m-d 00:00');
                $pacDateEnd = $anlage->getPacDateEnd()->format('Y-m-d 23:59');
                // FAC Date bzw letztes FAC Jahr berechnen
                ($anlage->getFacDate()) ? $facDate = $anlage->getFacDate()->format('Y-m-d') : $facDate = $pacDateEnd;
                $facDateForecast = $facDate;
                $facDateForecastMinusOneYear = date('Y-m-d 23:59', strtotime($facDateForecast.' -1 year'));
                if ($facDateForecastMinusOneYear > $day) {
                    $facDateForecast = $facDateForecastMinusOneYear;
                    $facDateForecastMinusOneYear = date('Y-m-d 00:00', strtotime($facDateForecast.' -1 year'));
                }
            } else {
                $pacDate = $pacDateEnd = $facDateForecastMinusOneYear = $facDateForecast = '';
            }

            $output = '';

            // Berechne Forecast für FAC Jahr
            $forecastArray = $this->functions->getFacForcast($anlage, $facDateForecastMinusOneYear, $facDateForecast, $day);

            // Berechne Actual Power für Tag, Jahr und PAC
            $powerActArray = $this->functions->getSumPowerAcAct($anlage, $from, $to, $pacDate, $pacDateEnd); // Summe Leistung AC IST

            $powerAct = $powerActArray['sumPower'];
            $powerActMonth = $powerActArray['powerActMonth'];
            $powerActPac = $powerActArray['powerActPac'];
            $powerActYear = $powerActArray['powerActYear'];
            $powerEvu = $powerActArray['powerEvu'];
            $powerEvuMonth = $powerActArray['powerEvuMonth'];
            $powerEvuPac = $powerActArray['powerEvuPac'];
            $powerEvuYear = $powerActArray['powerEvuYear'];

            // Wenn externe Tagesdaten genutzt werden sollen, lade diese aus der DB und ÜBERSCHREIBE die Daten aus den 15Minuten Werten
            $powerEGridExt = $this->functions->getSumeGridMeter($anlage, $from, $to, true);
            $powerEGridExtMonth = -999; //$this->functions->getSumeGridMeter($anlage, date('Y-m-01 00:00', strtotime($from)), $to);
            $powerEGridExtPac = -999; //$this->functions->getSumeGridMeter($anlage, $pacDate, $to);
            $powerEGridExtYear = -999; //$this->functions->getSumeGridMeter($anlage, date('Y-01-01 00:00', strtotime($from)), $to);

            if ($anlage->getUsePac()) {
                $weather = $this->functions->getWeather($anlage, $anlage->getWeatherStation(), $from, $to, $pacDate, $pacDateEnd); // Strahlung und andere Wetter Daten als Array
            } else {
                $weather = $this->functions->getWeather($anlage, $anlage->getWeatherStation(), $from, $to, false, false); // Strahlung und andere Wetter Daten als Array
            }

            // Berechne Summe und Mittelwert der JSON Arrays
            ($powerActArray['irrAnlage']) ? $irrAnlageArray = $this->functions->buildSumFromArray($powerActArray['irrAnlage'], 4) : $irrAnlageArray = []; // Strahlung (Irradiation) in Wh/qm
            ($powerActArray['tempAnlage']) ? $tempAnlageArray = $this->functions->buildAvgFromArray($powerActArray['tempAnlage']) : $tempAnlageArray = [];           // Temperatur
            ($powerActArray['windAnlage']) ? $windAnlageArray = $this->functions->buildAvgFromArray($powerActArray['windAnlage']) : $windAnlageArray = [];           // Wind

            // Berechne Expected G4N für Tag, Jahr und PAC
            $powerExpArray = $this->functions->getSumPowerAcExp($anlage, $from, $to, $pacDate, $pacDateEnd); // Summe Leistung AC SOLL
            $powerExp = ($powerExpArray['sumPowerEvuExp'] > 0) ? $powerExpArray['sumPowerEvuExp'] : $powerExpArray['sumPowerExp'];
            $powerExpMonth = ($powerExpArray['sumPowerEvuExpMonth'] > 0) ? $powerExpArray['sumPowerEvuExpMonth'] : $powerExpArray['sumPowerExpMonth'];
            $powerExpYear = ($powerExpArray['sumPowerEvuExpYear'] > 0) ? $powerExpArray['sumPowerEvuExpYear'] : $powerExpArray['sumPowerExpYear'];
            $powerExpPac = ($powerExpArray['sumPowerEvuExpPac'] > 0) ? $powerExpArray['sumPowerEvuExpPac'] : $powerExpArray['sumPowerExpPac'];

            // PlantAvailability berechnen FIRST
            // pro Tag
            // FIRST
            $availability = $this->availabilityService->calcAvailability($anlage, date_create($day.' 00:00'), date_create($day.' 23:59'));
            // SECOND
            $availabilitySecond = $this->anlageAvailabilityRepo->sumAvailabilitySecondPerDay($anlage->getAnlId(), $day);
            if (!$availabilitySecond) {
                $availabilitySecond = 0;
            }

            // pro Monat
            $startMonth = date('Y-m-01 00:00', strtotime($to));
            $anzPRRecordsPerMonth = -999; //$this->PRRepository->anzRecordsPRPerPac($anlage->getAnlId(), $startMonth, $to);
            if ($anzPRRecordsPerMonth == 0) {
                $anzPRRecordsPerMonth = 1;
            }
            // FIRST
            $availabilityPerMonth = -999; //$this->availabilityService->calcAvailability($anlage, date_create($startMonth), date_create($to));
            // SECOND
            $availabilitySecondPerMonth = -999; //$this->PRRepository->sumAvailabilitySecondPerPac($anlage->getAnlId(), $startMonth, $to);
            $availabilitySecondPerMonth = $availabilitySecondPerMonth / $anzPRRecordsPerMonth;

            // pro Jahr
            // FIRST
            $anzPRRecordsPerYear = -999; //$this->PRRepository->anzRecordsPRPerYear($anlage->getAnlId(), $year, $to);
            $availabilityPerYear = -999; //$this->availabilityService->calcAvailability($anlage, date_create("$year-01-01 00:00"), date_create($to));
            if ($anzPRRecordsPerYear == 0) {
                $anzPRRecordsPerYear = 1;
            }
            // SECOND
            $availabilityPerYearSecond = -999; //$this->PRRepository->sumAvailabilitySecondPerYear($anlage->getAnlId(), $year, $to);
            if ($availabilityPerYearSecond == null) {
                $availabilityPerYearSecond = '';
            } else {
                $availabilityPerYearSecond = $availabilityPerYearSecond / $anzPRRecordsPerYear;
            }

            // auf Basis des PAC (Productions Start Datum)
            // FIRST und SECOND
            if ($anlage->getUsePac()) { // Nur, wenn pacDate benutzt werden soll
                $anzPRRecordsPerPac = -999; //$this->PRRepository->anzRecordsPRPerPac($anlage->getAnlId(), $pacDate, $pacDateEnd);
                if ($anzPRRecordsPerPac == 0) {
                    $anzPRRecordsPerPac = 1;
                }
                // FIRST
                $availabilityPerPac = -999; //$this->availabilityService->calcAvailability($anlage, date_create($pacDate), date_create($pacDateEnd));
                // SECOND
                $availabilitySecondPerPac = -999; //$this->PRRepository->sumAvailabilitySecondPerPac($anlage->getAnlId(), $pacDate, $pacDateEnd);
                $availabilitySecondPerPac = $availabilitySecondPerPac / $anzPRRecordsPerPac;
            } else {
                $availabilityPerPac = 0;
                $availabilitySecondPerPac = 0;
            }

            // PvSyst Daten berechnen
            $pvSystArray = $this->functions->getPvSyst($anlage, $from, $to, $pacDate);

            // Kundenspezifischer PR berechnen
            $tempCorrection = 0;
            $prEvu = $prAct = $prExp = $prEGridExt = $monthPrEvu = $monthPrAct = $monthPrEGridExt = 0;
            $yearPrEGridExt = $pacPrEGridExt = $monthPrExp = $pacPrAct = $yearPrAct = $pacPrEvu = $yearPrEvu = $pacPrExp = $yearPrExp = 0;
            $prDefaultEvu = $prDefaultAct = $prDefaultExp = $prDefaultEGridExt = 0;
            $pacPrDefaultEvu = $pacPrDefaultAct = $pacPrDefaultExp = $pacPrDefaultEGridExt = 0;
            $monthPrDefaultEvu = $monthPrDefaultAct = $monthPrDefaultExp = $monthPrDefaultEGridExt = 0;
            $yearPrDefaultEvu = $yearPrDefaultAct = $yearPrDefaultExp = $yearPrDefaultEGridExt = 0;

            // Strahlung berechnen
            if ($anlage->getIsOstWestAnlage()) {
                // Strahlung (upper = Ost / lower = West)
                $irr = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
                $irrMonth = ($weather['upperIrrMonth'] * $anlage->getPowerEast() + $weather['lowerIrrMonth'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
                $irrPac = ($weather['upperIrrPac'] * $anlage->getPowerEast() + $weather['lowerIrrPac'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
                $irrYear = ($weather['upperIrrYear'] * $anlage->getPowerEast() + $weather['lowerIrrYear'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
            } else {
                $irr = $weather['upperIrr'] / 4 / 1000; // Umrechnug zu kWh
                $irrMonth = $weather['upperIrrMonth'] / 4 / 1000; // Umrechnug zu kWh
                $irrPac = $weather['upperIrrPac'] / 1000 / 4;
                $irrYear = $weather['upperIrrYear'] / 1000 / 4;
            }

            // PowerTheoretical für das Jahr und PAC berechnen (Standard Werte)
            $powerTheo = $powerTheoDefault = $anlage->getPnom() * $irr;
            $powerTheoMonth = $powerTheoMonthDefault = $anlage->getPnom() * $irrMonth;
            $powerTheoPac = $powerTheoPacDefault = $anlage->getPnom() * $irrPac;
            $powerTheoYear = $powerTheoYearDefault = $anlage->getPnom() * $irrYear;
            // Berechne Default PR
            if ($powerTheo > 0) { // Verhindere Divison by zero
                $prDefaultEvu = ($powerEvu / $powerTheo) * 100;
                $prDefaultAct = ($powerAct / $powerTheo) * 100;
                $prDefaultExp = ($powerExp / $powerTheo) * 100;
                $prDefaultEGridExt = ($powerEGridExt / $powerTheo) * 100;
            }
            if ($powerTheoMonth > 0) {
                $monthPrDefaultEvu = ($powerEvuMonth / $powerTheoMonth) * 100;
                $monthPrDefaultAct = ($powerActMonth / $powerTheoMonth) * 100;
                $monthPrDefaultExp = ($powerExpMonth / $powerTheoMonth) * 100;
                $monthPrDefaultEGridExt = ($powerEGridExtMonth / $powerTheoMonth) * 100;
            }
            if ($anlage->getUsePac() && $powerTheoPac > 0) {
                $pacPrDefaultEvu = ($powerEvuPac / $powerTheoPac) * 100;
                $pacPrDefaultAct = ($powerActPac / $powerTheoPac) * 100;
                $pacPrDefaultExp = ($powerExpPac / $powerTheoPac) * 100;
                $pacPrDefaultEGridExt = ($powerEGridExtPac / $powerTheoPac) * 100;
            }
            if ($powerTheoYear > 0) {
                $yearPrDefaultEvu = ($powerEvuYear / $powerTheoYear) * 100;
                $yearPrDefaultAct = ($powerActYear / $powerTheoYear) * 100;
                $yearPrDefaultExp = ($powerExpYear / $powerTheoYear) * 100;
                $yearPrDefaultEGridExt = ($powerEGridExtYear / $powerTheoYear) * 100;
            }
            // Berechne kundenspezifischen PR
            switch ($anlage->getUseCustPRAlgorithm()) {
                case 'Groningen':
                    // Umrechnung Globalstrahlung auf Modulstrahlung erfolgt schon beim Import
                    // (Bsp. Groningen: IrrUpper = umgerechnete Globalstrahlung, IrrLower = gemesene Modulstrahlung, IrrHori = gemessene Horizontalstrahlung)
                    if ($powerTheo > 0 && $availability > 0) { // Verhinder Divison by zero
                        $prEvu = ($powerEvu / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                        $prAct = ($powerAct / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                        $prExp = ($powerExp / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                        $prEGridExt = ($powerEGridExt / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                    }
                    if ($powerTheoMonth > 0 && $availabilityPerMonth > 0) {
                        $monthPrEvu = ($powerEvuMonth / ($powerTheoMonth / 1000 * $availabilityPerMonth)) * (10 / 0.9945);
                        $monthPrAct = ($powerActMonth / ($powerTheoMonth / 1000 * $availabilityPerMonth)) * (10 / 0.9945);
                        $monthPrExp = ($powerExpMonth / ($powerTheoMonth / 1000 * $availabilityPerMonth)) * (10 / 0.9945);
                        $monthPrEGridExt = ($powerEGridExtMonth / ($powerTheoMonth / 1000 * $availabilityPerMonth)) * (10 / 0.9945);
                    }

                    if ($anlage->getUsePac() && $powerTheoPac > 0 && $availabilityPerPac > 0) {
                        $pacPrEvu = ($powerEvuPac / ($powerTheoPac / 1000 * $availabilityPerPac)) * (10 / 0.9945);
                        $pacPrAct = ($powerActPac / ($powerTheoPac / 1000 * $availabilityPerPac)) * (10 / 0.9945);
                        $pacPrExp = ($powerExpPac / ($powerTheoPac / 1000 * $availabilityPerPac)) * (10 / 0.9945);
                        $pacPrEGridExt = ($powerEGridExtPac / ($powerTheoPac / 1000 * $availabilityPerPac)) * (10 / 0.9945);
                    }
                    if ($powerTheoYear > 0 && $availabilityPerYear > 0) {
                        $yearPrEvu = ($powerEvuYear / ($powerTheoYear / 1000 * $availabilityPerYear)) * (10 / 0.9945);
                        $yearPrAct = ($powerActYear / ($powerTheoYear / 1000 * $availabilityPerYear)) * (10 / 0.9945);
                        $yearPrExp = ($powerExpYear / ($powerTheoYear / 1000 * $availabilityPerYear)) * (10 / 0.9945);
                        $yearPrEGridExt = ($powerEGridExtYear / ($powerTheoYear / 1000 * $availabilityPerYear)) * (10 / 0.9945);
                    }
                    break;
                case 'Veendam':
                    if ($powerTheo > 0 && $availability > 0) {
                        $prEvu = ($powerEvu / ($powerTheo / 100 * $availability)) * 100;
                        $prAct = ($powerAct / ($powerTheo / 100 * $availability)) * 100;
                        $prExp = ($powerExp / ($powerTheo / 100 * $availability)) * 100;
                        $prEGridExt = ($powerEGridExt / ($powerTheo / 100 * $availability)) * 100;
                    }
                    if ($powerTheoMonth > 0 && $availabilityPerMonth > 0) {
                        $monthPrEvu = ($powerEvuMonth / ($powerTheoMonth / 100 * $availabilityPerMonth)) * 100;
                        $monthPrAct = ($powerActMonth / ($powerTheoMonth / 100 * $availabilityPerMonth)) * 100;
                        $monthPrExp = ($powerExpMonth / ($powerTheoMonth / 100 * $availabilityPerMonth)) * 100;
                        $monthPrEGridExt = ($powerEGridExtMonth / ($powerTheoMonth / 100 * $availabilityPerMonth)) * 100;
                    }
                    if ($anlage->getUsePac() && $powerTheoPac > 0 && $availabilityPerPac > 0) {
                        $pacPrEvu = ($powerEvuPac / ($powerTheoPac / 100 * $availabilityPerPac)) * 100;
                        $pacPrAct = ($powerActPac / ($powerTheoPac / 100 * $availabilityPerPac)) * 100;
                        $pacPrExp = ($powerExpPac / ($powerTheoPac / 100 * $availabilityPerPac)) * 100;
                        $pacPrEGridExt = ($powerEGridExtPac / ($powerTheoPac / 100 * $availabilityPerPac)) * 100;
                    }
                    if ($powerTheoYear > 0 && $availabilityPerYear > 0) {
                        $yearPrEvu = ($powerEvuYear / ($powerTheoYear / 100 * $availabilityPerYear)) * 100;
                        $yearPrAct = ($powerActYear / ($powerTheoYear / 100 * $availabilityPerYear)) * 100;
                        $yearPrExp = ($powerExpYear / ($powerTheoYear / 100 * $availabilityPerYear)) * 100;
                        $yearPrEGridExt = ($powerEGridExtYear / ($powerTheoYear / 100 * $availabilityPerYear)) * 100;
                    }
                    break;
                case 'Lelystad':
                    // Summe der theo Power aus den IST Werten (koriegiert mit TemperaturKorrektur)

                    $powerTheo = $powerActArray['theoPower'];
                    $powerTheoMonth = $powerActArray['theoPowerMonth'];
                    $powerTheoPac = $powerActArray['theoPowerPac'];
                    $powerTheoYear = $powerActArray['theoPowerYear'];

                    if ($powerTheo > 0) { // Verhinder Divison by zero
                        $prEvu = ($powerEvu / $powerTheo) * 100;
                        $prAct = ($powerAct / $powerTheo) * 100;
                        $prExp = ($powerExp / $powerTheo) * 100;
                        $prEGridExt = ($powerEGridExt / $powerTheo) * 100;
                    }
                    if ($powerTheoMonth > 0) {
                        $monthPrEvu = ($powerEvuMonth / $powerTheoMonth) * 100;
                        $monthPrAct = ($powerActMonth / $powerTheoMonth) * 100;
                        $monthPrExp = ($powerExpMonth / $powerTheoMonth) * 100;
                        $monthPrEGridExt = ($powerEGridExtMonth / $powerTheoMonth) * 100;
                    }
                    if ($anlage->getUsePac() && $powerTheoPac > 0) {
                        $pacPrEvu = ($powerEvuPac / $powerTheoPac) * 100;
                        $pacPrAct = ($powerActPac / $powerTheoPac) * 100;
                        $pacPrExp = ($powerExpPac / $powerTheoPac) * 100;
                        $pacPrEGridExt = ($powerEGridExtPac / $powerTheoPac) * 100;
                    }
                    if ($powerTheoYear > 0) {
                        $yearPrEvu = ($powerEvuYear / $powerTheoYear) * 100;
                        $yearPrAct = ($powerActYear / $powerTheoYear) * 100;
                        $yearPrExp = ($powerExpYear / $powerTheoYear) * 100;
                        $yearPrEGridExt = ($powerEGridExtYear / $powerTheoYear) * 100;
                    }

                    break;
                default:
                    // wenn es keinen spezielen Algoritmus gibt
                    if ($powerTheo > 0) { // Verhindere Divison by zero
                        $prEvu = ($powerEvu / $powerTheo) * 100;
                        $prAct = ($powerAct / $powerTheo) * 100;
                        $prExp = ($powerExp / $powerTheo) * 100;
                        $prEGridExt = ($powerEGridExt / $powerTheo) * 100;
                    }
                    if ($powerTheoMonth > 0) {
                        $monthPrEvu = ($powerEvuMonth / $powerTheoMonth) * 100;
                        $monthPrAct = ($powerActMonth / $powerTheoMonth) * 100;
                        $monthPrExp = ($powerExpMonth / $powerTheoMonth) * 100;
                        $monthPrEGridExt = ($powerEGridExtMonth / $powerTheoMonth) * 100;
                    }
                    if ($anlage->getUsePac() && $powerTheoPac > 0) {
                        $pacPrEvu = ($powerEvuPac / $powerTheoPac) * 100;
                        $pacPrAct = ($powerActPac / $powerTheoPac) * 100;
                        $pacPrExp = ($powerExpPac / $powerTheoPac) * 100;
                        $pacPrEGridExt = ($powerEGridExtPac / $powerTheoPac) * 100;
                    }
                    if ($powerTheoYear > 0) {
                        $yearPrEvu = ($powerEvuYear / $powerTheoYear) * 100;
                        $yearPrAct = ($powerActYear / $powerTheoYear) * 100;
                        $yearPrExp = ($powerExpYear / $powerTheoYear) * 100;
                        $yearPrEGridExt = ($powerEGridExtYear / $powerTheoYear) * 100;
                    }
            }

            // diverse andere Werte Berechnen
            $diff = $powerAct - $powerExp;
            ($powerExp > 0) ? $diffPr = $diff / $powerExp : $diffPr = 0;
            $diffPrProzent = $diffPr * 100;

            $pannelTempAvg = $weather['panelTemp'];

            // Case 5
            $anzCase5PerDay = $this->case5Repo->countCase5DayAnlage($anlage, $day);

            // SpecYield
            if ($anlage->getUseGridMeterDayData()) {
                $specYieldMonth = $powerEGridExtMonth / $anlage->getKwPeak();
            } else {
                if ($anlage->getShowEvuDiag()) {
                    $specYieldMonth = $powerEvuMonth / $anlage->getKwPeak();
                } else {
                    $specYieldMonth = $powerActMonth / $anlage->getKwPeak();
                }
            }

            // Datensatz Speichern in PR Entity
            /** @var AnlagenPR $pr */
            $pr = $this->PRRepository->findOneBy(['stamp' => new DateTime($from), 'anlage' => $anlage]);
            if (!$pr) { // Wenn Daten nicht gefunden lege neu an
                $pr = new AnlagenPR();
                $pr->setAnlId($anlage->getAnlId())
                    ->setAnlage($anlage)
                    ->setstamp(new DateTime($from))
                ;
            }
            // Daten gefunden, aktualisieren der Daten
            $pr->setPowerAct($powerAct)
                ->setPowerActMonth($powerActMonth)
                ->setPowerActPac($powerActPac)
                ->setPowerActYear($powerActYear)

                ->setPowerExp($powerExp)
                ->setPowerExpMonth($powerExpMonth)
                ->setPowerExpPac($powerExpPac)
                ->setPowerExpYear($powerExpYear)

                ->setPowerEvu($powerEvu)
                ->setPowerEvuMonth($powerEvuMonth)
                ->setPowerEvuPac($powerEvuPac)
                ->setPowerEvuYear($powerEvuYear)

                ->setPowerEGridExt($powerEGridExt)
                ->setPowerEGridExtMonth($powerEGridExtMonth)
                ->setPowerEGridExtPac($powerEGridExtPac)
                ->setPowerEGridExtYear($powerEGridExtYear)

                ->setPowerTheo($powerTheo)
                ->setPowerTheoMonth($powerTheoMonth)
                ->setTheoPowerPac($powerTheoPac)
                ->setTheoPowerYear($powerTheoYear)

                ->setPowerPvSyst($pvSystArray['powerPvSyst'])
                ->setPowerPvSystYear($pvSystArray['powerPvSystYear'])
                ->setPowerPvSystPac($pvSystArray['powerPvSystPac'])

                ->setPowerDiff($diff)
                ->setPrDiff($diffPrProzent)
            ;
            $pr->setIrradiation($irr)
                ->setCustIrr($irr)
                ->setG4nIrrAvg($weather['upperIrr'] / 1000 / 4)

                ->setPrAct($prAct)
                ->setPrActMonth($monthPrAct)
                ->setPrActPac($pacPrAct)
                ->setPrActYear($yearPrAct)

                ->setPrExp($prExp)
                ->setPrExpMonth($monthPrExp)
                ->setPrExpPac($pacPrExp)
                ->setPrExpYear($yearPrExp)

                ->setPrEvu($prEvu)
                ->setPrEvuMonth($monthPrEvu)
                ->setPrEvuPac($pacPrEvu)
                ->setPrEvuYear($yearPrEvu)

                ->setPrEGridExt($prEGridExt)
                ->setPrEGridExtMonth($monthPrEGridExt)
                ->setPrEGridExtPac($pacPrEGridExt)
                ->setPrEGridExtYear($yearPrEGridExt)

                ->setPanneltemp($pannelTempAvg)
                ->setTempCorrection($tempCorrection)
                ->setPacDate($pacDate)
            ;
            $pr->setPlantAvailability($availability)
                ->setPlantAvailabilityPerMonth($availabilityPerMonth)
                ->setPlantAvailabilityPerPac($availabilityPerPac)
                ->setPlantAvailabilityPerYear($availabilityPerYear)

                ->setPlantAvailabilitySecond($availabilitySecond)
                ->setPlantAvailabilityPerMonthSecond($availabilitySecondPerMonth)
                ->setPlantAvailabilityPerPacSecond($availabilitySecondPerPac)
                ->setPlantAvailabilityPerYearSecond($availabilityPerYearSecond)

                ->setIrradiationJson($irrAnlageArray)
                ->setTemperaturJson($tempAnlageArray)
                ->setWindJson($windAnlageArray)

                ->setForecastSum($forecastArray['sumForecast'])
                ->setForecastSumAct($forecastArray['sumActual'])
                ->setForecastDivMinus($forecastArray['divMinus'])
                ->setForecastDivPlus($forecastArray['divPlus'])
                ->setPrPac(0)
                ->setElectricityGrid(0)
            ;
            $pr->setIrrMonth($irrMonth)
                ->setIrrPac($irrPac)
                ->setIrrYear($irrYear)
                ->setSpezYield($specYieldMonth)
                ->setCase5perDay($anzCase5PerDay)
            ;
            $pr->setTheoPowerDefault($powerTheoDefault)
                ->setTheoPowerDefaultMonth($powerTheoMonthDefault)
                ->setTheoPowerDefaultPac($powerTheoPacDefault)
                ->setTheoPowerDefaultYear($powerTheoYearDefault)

                ->setPrDefaultEvu($prDefaultEvu)
                ->setPrDefaultAct($prDefaultAct)
                ->setPrDefaultExp($prDefaultExp)
                ->setPrDefaultEGridExt($prDefaultEGridExt)

                ->setPrDefaultMonthEvu($monthPrDefaultEvu)
                ->setPrDefaultMonthAct($monthPrDefaultAct)
                ->setPrDefaultMonthExp($monthPrDefaultExp)
                ->setPrDefaultMonthEGridExt($monthPrDefaultEGridExt)

                ->setPrDefaultPacEvu($pacPrDefaultEvu)
                ->setPrDefaultPacAct($pacPrDefaultAct)
                ->setPrDefaultPacExp($pacPrDefaultExp)
                ->setPrDefaultPacEGridExt($pacPrDefaultEGridExt)

                ->setPrDefaultYearEvu($yearPrDefaultEvu)
                ->setPrDefaultYearAct($yearPrDefaultAct)
                ->setPrDefaultYearExp($yearPrDefaultExp)
                ->setPrDefaultYearEGridExt($yearPrDefaultEGridExt)
            ;

            $this->em->persist($pr);
            $this->em->flush();

            $output .= "$from | PR-Act: $prAct - PR-Exp: $prExp - PR-EVU: $prEvu";
            $output .= " Irr: $irr - Power theor.: $powerTheo - PA1: $availability PA1 per Year: $availabilityPerYear<br>";
        } else {
            $output = 'Datum ist gleich oder größer aktuelles Datum ('.date('Y-m-d', $timeStamp).')<br>';
        }

        return $output;
    }

    /**
     * Returns Array with all Information for given Date (Daterange)<br>
     *  $result['powerEGridExt']<br>
     *  $result['powerEvu']<br>
     *  $result['powerAct']<br>
     *  $result['powerExp']<br>
     *  $result['powerTheo']<br>
     *  $result['powerTheoTempCorr']<br>
     *  $result['prDefaultEGridExt']<br>
     *  $result['prDefaultEvu']<br>
     *  $result['prDefaultAct']<br>
     *  $result['prDefaultExp']<br>
     *  $result['prEGridExt']<br>
     *  $result['prEvu']<br>
     *  $result['prAct']<br>
     *  $result['prExp']<br>
     *  $result['prDep0Evu'] (by default 'open book')<br>
     *  $result['prDep0Act'] (by default 'open book')<br>
     *  $result['prDep0Exp'] (by default 'open book')<br>
     *  $result['prDep0EGridExt'] (by default 'open book')<br>
     *  $result['prDep1Evu'] (by default 'O&M')<br>
     *  $result['prDep1Act'] (by default 'O&M')<br>
     *  $result['prDep1Exp'] (by default 'O&M')<br>
     *  $result['prDep1EGridExt'] (by default 'O&M')<br>
     *  $result['prDep2Evu'] (by default 'EPC')<br>
     *  $result['prDep2Act'] (by default 'EPC')<br>
     *  $result['prDep2Exp'] (by default 'EPC')<br>
     *  $result['prDep2EGridExt'] (by default 'EPC')<br>
     *  $result['prDep3Evu'] (by default 'AM')<br>
     *  $result['prDep3Act'] (by default 'AM')<br>
     *  $result['prDep3Exp'] (by default 'AM')<br>
     *  $result['prDep3EGridExt'] (by default 'AM')<br>
     *  $result['algorithmus'] deprecated (depending on settings in Plant)<br>
     *  $result['tempCorrection']<br>
     *  $result['irradiation']<br>
     *  $result['availability'] deprecated<br>
     *  $result['availability2'] deprecated<br>
     *  $result['pa0'] (by default 'open book')<br>
     *  $result['pa1'] (by default 'O&M')<br>
     *  $result['pa2'] (by default 'EPC')<br>
     *  $result['pa3'] (by default 'AM')<br>
     *  $result['anzCase5'] (proof)<br>
     *  $result['tCellAvgMeasured'] (proof)<br>
     *  $result['tCellAvgNrel'] (proof)<br>
     *  $result['tCellAvgMultiIrr'] (proof)<br>.
     *
     * @throws \Exception
     */
    public function calcPR(Anlage $anlage, DateTime $startDate, DateTime $endDate = null, string $type = 'day', int $years = 1): array
    {
        $type = strtolower($type); // sicherstellen das type immer in Kleinbuchstaben
        $result = [];

        // Start Zeite je nach gewähltem Typ ermitteln und als für SQL formatiertem String speichern
        switch ($type) {
            case 'month':
                // PR für Monat berechnen (ohne Rumpfmonate)
                $localStartDate = $startDate->format('Y-m-d 00:00');
                $localEndDate = $startDate->format('Y-m-d-23:59');
                break;
            case 'year':
                // PR für das Jahr brechnen (vom 1. Jan bis zum 31. Dez)
                $localStartDate = $startDate->format('Y-01-01 00:00');
                $localEndDate = $startDate->format('Y-12-31 23:59');
                break;
            case 'pac':
                // PR für PAC Datum bis $endDate berechnen
                $localStartDate = $anlage->getPacDate()->format('Y-m-d 00:00');
                if ($endDate === null) {
                    $localEndDate = $startDate->format('Y-m-d-23:59');
                } else {
                    $localEndDate = $endDate->format('Y-m-d-23:59');
                }
                break;
            default:
                // PR für einen Tag (wenn $endDate = null) oder für beliebigen Zeitraum (auch für Rumpfmonate in epc Berichten) berechnen
                $localStartDate = $startDate->format('Y-m-d 00:00');
                if ($endDate === null) {
                    $localEndDate = $startDate->format('Y-m-d 23:59');
                } else {
                    $localEndDate = $endDate->format('Y-m-d 23:59');
                }
        }

        // Wetter Daten ermitteln
        $weather = $this->weatherFunctions->getWeather($anlage->getWeatherStation(), $localStartDate, $localEndDate);

        // Leistungsdaten ermitteln
        $power = $this->functions->getSumAcPower($anlage, $localStartDate, $localEndDate);

        $result['powerEvu'] = $power['powerEvu'];
        $result['powerAct'] = $power['powerAct'];
        $result['powerExp'] = $power['powerExpEvu'] > 0 ? $power['powerExpEvu'] : $power['powerExp'];
        $result['powerEGridExt'] = $power['powerEGridExt'];

        // Verfügbarkeit ermitteln
        $pa0 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), null, 0);
        $pa1 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), null, 1);
        $pa2 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), null, 2);
        $pa3 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), null, 3);
        $availability = $pa2;

        // Strahlungen berechnen – (upper = Ost / lower = West)
        if ($anlage->getIsOstWestAnlage()) {
            $irr = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
        } else {
            $irr = $weather['upperIrr'] / 4 / 1000; // Umrechnug zu kWh
        }

        $irr = $this->functions->checkAndIncludeMonthlyCorrectionIrr($anlage, $irr, $localStartDate, $localEndDate);

        $tempCorrection = 0; // not used at the Moment

        // PR Calculation
        $result['powerTheoDep0'] = $anlage->getPrFormular0() == 'Lelystad' ? $power['powerTheo'] : $anlage->getPnom() * $irr; // if theoretic Power ist corrected by temperature (NREL) (PR Algoritm = Lelystad) then use 'powerTheo' from array $power, if not calc by Pnom and Irr.
        $result['powerTheo'] = $result['powerTheoDep0'];
        $result['prDep0Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr, $power['powerEvu'], $result['powerTheoDep0'], $pa0); //($power['powerEvu'] / $tempTheoPower) * 100;
        $result['prDep0Act'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr, $power['powerAct'], $result['powerTheoDep0'], $pa0); //(($power['powerAct'] / $tempTheoPower) * 100;
        $result['prDep0Exp'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr, $power['powerExp'], $result['powerTheoDep0'], $pa0); //(($result['powerExp'] / $tempTheoPower) * 100;
        $result['prDep0EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr, $power['powerEGridExt'], $result['powerTheoDep0'], $pa0); //(($power['powerEGridExt'] / $tempTheoPower) * 100;

        $result['powerTheoDep1'] = $anlage->getPrFormular1() == 'Lelystad' ? $power['powerTheo'] : $anlage->getPnom() * $irr; // if theoretic Power ist corrected by temperature (NREL) (PR Algoritm = Lelystad) then use 'powerTheo' from array $power, if not calc by Pnom and Irr.
        $result['prDep1Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr, $power['powerEvu'], $result['powerTheoDep1'], $pa0); //($power['powerEvu'] / $tempTheoPower) * 100;
        $result['prDep1Act'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr, $power['powerAct'], $result['powerTheoDep1'], $pa0); //(($power['powerAct'] / $tempTheoPower) * 100;
        $result['prDep1Exp'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr, $power['powerExp'], $result['powerTheoDep1'], $pa0); //(($result['powerExp'] / $tempTheoPower) * 100;
        $result['prDep1EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr, $power['powerEGridExt'], $result['powerTheoDep1'], $pa0); //(($power['powerEGridExt'] / $tempTheoPower) * 100;

        $result['powerTheoDep2'] = $anlage->getPrFormular2() == 'Lelystad' ? $power['powerTheo'] : $anlage->getPnom() * $irr; // if theoretic Power ist corrected by temperature (NREL) (PR Algoritm = Lelystad) then use 'powerTheo' from array $power, if not calc by Pnom and Irr.
        $result['prDep2Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr, $power['powerEvu'], $result['powerTheoDep2'], $pa0); //($power['powerEvu'] / $tempTheoPower) * 100;
        $result['prDep2Act'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr, $power['powerAct'],  $result['powerTheoDep2'], $pa0); //(($power['powerAct'] / $tempTheoPower) * 100;
        $result['prDep2Exp'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr, $power['powerExp'], $result['powerTheoDep2'], $pa0); //(($result['powerExp'] / $tempTheoPower) * 100;
        $result['prDep2EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr, $power['powerEGridExt'], $result['powerTheoDep2'], $pa0); //(($power['powerEGridExt'] / $tempTheoPower) * 100;

        $result['powerTheoDep3'] = $anlage->getPrFormular3() == 'Lelystad' ? $power['powerTheo'] : $anlage->getPnom() * $irr; // if theoretic Power ist corrected by temperature (NREL) (PR Algoritm = Lelystad) then use 'powerTheo' from array $power, if not calc by Pnom and Irr.
        $result['prDep3Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr, $power['powerEvu'], $result['powerTheoDep3'], $pa0); //($power['powerEvu'] / $tempTheoPower) * 100;
        $result['prDep3Act'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr, $power['powerAct'], $result['powerTheoDep3'], $pa0); //(($power['powerAct'] / $tempTheoPower) * 100;
        $result['prDep3Exp'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr, $power['powerExp'], $result['powerTheoDep3'], $pa0); //(($result['powerExp'] / $tempTheoPower) * 100;
        $result['prDep3EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr, $power['powerEGridExt'], $result['powerTheoDep3'], $pa0); //(($power['powerEGridExt'] / $tempTheoPower) * 100;

        /*
        // depending on used allgoritmus
        switch ($anlage->getUseCustPRAlgorithm()) {
            case 'Groningen':
                if ($powerTheo > 0 && $availability > 0) { // Verhinder Divison by zero
                    $result['prEvu'] = ($power['powerEvu'] / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                    $result['prAct'] = ($power['powerAct'] / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                    $result['prExp'] = ($result['powerExp'] / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                    $result['prEGridExt'] = ($power['powerEGridExt'] / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                }
                break;
            case 'Veendam':
                if ($availability > 0) { // Verhinder Divison by zero
                    if ($powerTheo > 0) {
                        $result['prEvu'] = ($power['powerEvu'] / ($powerTheo / 100 * $availability)) * 100;
                        $result['prAct'] = ($power['powerAct'] / ($powerTheo / 100 * $availability)) * 100;
                        $result['prExp'] = ($result['powerExp'] / ($powerTheo / 100 * $availability)) * 100;
                        $result['prEGridExt'] = ($power['powerEGridExt'] / ($powerTheo / 100 * $availability)) * 100;
                    }
                }
                break;
            case 'Lelystad':
                // mit Temperatur korriegierten theoretischen Enerie ($powerTheo)
                if ($powerTheo > 0) { // Verhinder Divison by zero
                    $result['prEvu'] = ($power['powerEvu'] / $powerTheo) * 100;
                    $result['prAct'] = ($power['powerAct'] / $powerTheo) * 100;
                    $result['prExp'] = ($result['powerExp'] / $powerTheo) * 100;
                    $result['prEGridExt'] = ($power['powerEGridExt'] / $powerTheo) * 100;
                }
                break;
            case 'Ladenburg' :
                // entspricht Standard PR plus degradation (Faktor = $years int)
                $powerTheo = ($anlage->getPnom() / 1000) * pow(1 - 0.25, $years) * $irr;
                if ($powerTheo > 0) { // Verhindere Divison by zero
                    $result['prEvu'] = ($power['powerEvu'] / $powerTheo) * 100;
                    $result['prAct'] = ($power['powerAct'] / $powerTheo) * 100;
                    $result['prExp'] = ($result['powerExp'] / $powerTheo) * 100;
                    $result['prEGridExt'] = ($power['powerEGridExt'] / $powerTheo) * 100;
                }
                break;
            default:
                // wenn es keinen spezielen Algorithmus gibt
                if ($powerTheo > 0) { // Verhindere Divison by zero
                    $result['prEvu'] = ($power['powerEvu'] / $powerTheo) * 100;
                    $result['prAct'] = ($power['powerAct'] / $powerTheo) * 100;
                    $result['prExp'] = ($result['powerExp'] / $powerTheo) * 100;
                    $result['prEGridExt'] = ($power['powerEGridExt'] / $powerTheo) * 100;
                }
        }
        */

        $anzCase5PerDay = $this->case5Repo->countCase5DayAnlage($anlage, $localStartDate, $localEndDate);

        $result['algorithmus'] = $anlage->getUseCustPRAlgorithm(); /** @deprecated */
        $result['powerTheoTempCorr'] = (float) $power['powerTheo']; /** @deprecated */
        $result['tempCorrection'] = (float) $tempCorrection;
        $result['irradiation'] = $irr;
        $result['availability'] = $pa2; // old EPC
        $result['availability2'] = $pa1; // old O&M
        $result['pa0'] = $pa0;
        $result['pa1'] = $pa1;
        $result['pa2'] = $pa2;
        $result['pa3'] = $pa3;
        $result['prDefaultEvu'] = $result['prDep0Evu']; // OpenBook / default PR
        $result['prDefaultAct'] = $result['prDep0Act']; // OpenBook / default PR
        $result['prDefaultExp'] = $result['prDep0Exp']; // OpenBook / default PR
        $result['prDefaultEGridExt'] = $result['prDep0EGridExt']; // OpenBook / default PR
        $result['prEvu'] = $result['prDep2Evu']; // EPC PR
        $result['prAct'] = $result['prDep2Act']; // EPC PR
        $result['prExp'] = $result['prDep2Exp']; // EPC PR
        $result['prEGridExt'] = $result['prDep2EGridExt']; // EPC PR
        $result['anzCase5'] = $anzCase5PerDay;
        $result['tCellAvgMeasured'] = (float) $weather['panelTempAvg'];
        $result['tCellAvgNrel'] = (float) $weather['temp_cell_corr'];
        $result['tCellAvgMultiIrr'] = (float) $weather['temp_cell_multi_irr'];

        return $result;
    }

    /**
     * @deprecated use 'calcPrBySelectedAlgorithm()' instead
     * @param Anlage $anlage
     * @param float $irr
     * @param float $spezYield
     * @param float $eGrid
     * @param float $theoPowerFT
     * @param $pa
     * @return float
     */
    #[Deprecated]
    public function calcPrByValues(Anlage $anlage, float $irr, float $spezYield, float $eGrid, float $theoPowerFT, $pa): float
    {
        return $this->calcPrBySelectedAlgorithm($anlage, 2, $irr, $eGrid, $theoPowerFT, $pa);
        /*
        switch ($anlage->getUseCustPRAlgorithm()) {
            case 'Lelystad':
                // Summe der theo Power aus den IST Werten (koriegiert mit TemperaturKorrektur)
                return $eGrid / $theoPowerFT * 100;
                break;
            default:
                // wenn es keinen spezielen Algoritmus gibt
                return ($irr > 0) ? $spezYield / $irr * 100 : 0;
        }
        */
    }

    /**
     * @param Anlage $anlage
     * @param int $dep
     * @param float $irr
     * @param float $eGrid
     * @param float $theoPower
     * @param float $pa
     * @param int|null $years
     * @return float|null
     */
    public function calcPrBySelectedAlgorithm(Anlage $anlage, int $dep, float $irr, float $eGrid, float $theoPower, float $pa, ?int $years = null): ?float
    {
        $result = null;
        $algorithm = match ($dep) {
            1 => $anlage->getPrFormular1(),
            2 => $anlage->getPrFormular2(),
            3 => $anlage->getPrFormular3(),
            default => $anlage->getPrFormular0(),
        };
        switch ($algorithm) {
            case 'Groningen': // special for Groningen
                $result = ($eGrid > 0 && $pa > 0) ? ($eGrid / ($theoPower / 1000 * $pa)) * (10 / 0.9945) : null;
                break;
            case 'Veendam': // with availability
                $result = ($eGrid > 0 && $pa > 0) ? ($eGrid / ($theoPower / 100 * $pa)) * 100 : null;
                break;
            case 'Lelystad': // with Temp Correction by NREL
                // Sum of theo. power from the actual values (corrected with temperature correction)
                $result = ($eGrid > 0 && $pa > 0) ? ($eGrid / $theoPower) * 100 : null;
                break;
            case 'Ladenburg': // not tested (2023-03-22 MR)
                if ($years && $years > 0){
                    // entspricht Standard PR plus degradation (Faktor = $years int)
                    $powerTheo = ($anlage->getPnom() / 1000) * pow(1 - 0.25, $years) * $irr;
                    $result = ($irr > 0) ? ($eGrid / $powerTheo) * 100 : null;
                }
                break;

            default:
                // wenn es keinen spezielen Algoritmus gibt
                $result = ($irr > 0) ? ($eGrid / ($anlage->getPnom() * $irr)) * 100 : null;
                #dd($eGrid, $anlage->getPnom() * $irr);
        }

        return $result;
    }
}
