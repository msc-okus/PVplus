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
use App\Service\Functions\PowerService;
use App\Service\Functions\SensorService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Psr\Cache\InvalidArgumentException;

class PRCalulationService
{
    use G4NTrait;

    public function __construct(
        private readonly PVSystDatenRepository $pvSystRepo,
        private readonly AnlagenRepository $anlagenRepository,
        private readonly PRRepository $PRRepository,
        private readonly AnlageAvailabilityRepository $anlageAvailabilityRepo,
        private readonly FunctionsService $functions,
        private readonly PowerService $powerServicer,
        private readonly EntityManagerInterface $em,
        private readonly Case5Repository $case5Repo,
        private readonly MonthlyDataRepository $monthlyDataRepo,
        private readonly WeatherFunctionsService $weatherFunctions,
        private readonly GridMeterDayRepository $gridMeterDayRepo,
        private readonly AvailabilityService $availabilityService,
        private readonly AvailabilityByTicketService $availabilityByTicket,
        private readonly SensorService $sensorService
    )
    {
    }

    /**
     * @param Anlage|int $anlage
     * @param string $day
     * @return string
     * @throws InvalidArgumentException
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @deprecated
     */
    #[Deprecated]
    public function calcPRAll(Anlage|int $anlage, string $day): string
    {
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneByIdAndJoin($anlage);
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
            $availability = $this->availabilityByTicket->calcAvailability($anlage, date_create($day.' 00:00'), date_create($day.' 23:59'), null, 0);

            // SECOND
            $availabilitySecond = 0; // $this->anlageAvailabilityRepo->sumAvailabilitySecondPerDay($anlage->getAnlId(), $day);

            // pro Monat
            $startMonth = date('Y-m-01 00:00', strtotime($to));
            // FIRST
            $availabilityPerMonth = $this->availabilityByTicket->calcAvailability($anlage, date_create($startMonth), date_create($to));
            // SECOND
            $availabilitySecondPerMonth = 0; //$this->PRRepository->sumAvailabilitySecondPerPac($anlage->getAnlId(), $startMonth, $to);

            // pro Jahr
            // FIRST
            $availabilityPerYear = $this->availabilityByTicket->calcAvailability($anlage, date_create("$year-01-01 00:00"), date_create($to));

            // SECOND
            $availabilityPerYearSecond = -999; //$this->PRRepository->sumAvailabilitySecondPerYear($anlage->getAnlId(), $year, $to);

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
                $specYieldMonth = $powerEGridExtMonth / $anlage->getPnom();
            } else {
                if ($anlage->getShowEvuDiag()) {
                    $specYieldMonth = $powerEvuMonth / $anlage->getPnom();
                } else {
                    $specYieldMonth = $powerActMonth / $anlage->getPnom();
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
     *  $result['powerEvuDep0']<br>
     *  $result['powerEvuDep1']<br>
     *  $result['powerEvuDep2']<br>
     *  $result['powerEvuDep3']<br>
     *  $result['powerAct']<br>
     *  $result['powerActDep0']<br>
     *  $result['powerActDep1']<br>
     *  $result['powerActDep2']<br>
     *  $result['powerActDep3']<br>
     *  $result['powerExp']<br>
     *  $result['powerTheo']<br>
     *  $result['powerTheoDep0']<br>
     *  $result['powerTheoDep1']<br>
     *  $result['powerTheoDep2']<br>
     *  $result['powerTheoDep3']<br>
     *  $result['powerTheoDep0NoPpc']<br>
     *  $result['powerTheoDep1NoPpc']<br>
     *  $result['powerTheoDep2NoPpc']<br>
     *  $result['powerTheoDep3NoPpc']<br>
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
     *  $result['irr0']<br>
     *  $result['irr1']<br>
     *  $result['irr2']<br>
     *  $result['irr3']<br>
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
     * @throws InvalidArgumentException
     */
    public function calcPR(Anlage $anlage, DateTime $startDate, DateTime $endDate = null): array
    {
        $result = [];
        // PR für einen Tag (wenn $endDate = null) oder für beliebigen Zeitraum (auch für Rumpfmonate in epc Berichten) berechnen
        $localStartDate = $startDate->format('Y-m-d 00:15');
        if ($endDate === null) {
            $localEndDateObj = clone ($startDate);
        } else {
            $localEndDateObj = clone ($endDate);
        }
        $localEndDate =  $localEndDateObj->add(new \DateInterval('P1D'))->format('Y-m-d 00:00'); // sicherstellen das das endatum der folgetag 0 Uhr ist

        // Verfügbarkeit ermitteln
        $pa1 = $pa2 = $pa3 = 0;
        if ($endDate === null) $this->availabilityByTicket->checkAvailability($anlage, date_create($localStartDate), 0);
        $pa0 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), null, 0);
        if (!$anlage->getSettings()->isDisableDep1() && $anlage->getSettings()->getEnablePADep1()) {
            if ($endDate === null) $this->availabilityByTicket->checkAvailability($anlage, date_create($localStartDate), 1);
            $pa1 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), null, 1);
        }
        if (!$anlage->getSettings()->isDisableDep2() && $anlage->getSettings()->getEnablePADep2()) {
            if ($endDate === null) $this->availabilityByTicket->checkAvailability($anlage, date_create($localStartDate), 2);
            $pa2 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), null, 2);
        }
        if (!$anlage->getSettings()->isDisableDep3() && $anlage->getSettings()->getEnablePADep3()) {
            if ($endDate === null) $this->availabilityByTicket->checkAvailability($anlage, date_create($localStartDate), 3);
            $pa3 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), null, 3);
        }

        // Wetter Daten ermitteln MIT Berücksichtigung des PPC Signals
        $weatherWithPpc = $this->weatherFunctions->getWeather($anlage->getWeatherStation(), $localStartDate, $localEndDate, true, $anlage);

        if (is_array($weatherWithPpc)) {
            $weatherWithPpc = $this->sensorService->correctSensorsByTicket($anlage, $weatherWithPpc, date_create($localStartDate), date_create($localEndDate), $pa0, $pa1, $pa2, $pa3);
        }
        // Wetter Daten ermitteln OHNE Berücksichtigung des PPC Signals
        $weatherNoPpc = $this->weatherFunctions->getWeather($anlage->getWeatherStation(), $localStartDate, $localEndDate, false, $anlage);
        if (is_array($weatherNoPpc)) {
            $weatherNoPpc = $this->sensorService->correctSensorsByTicket($anlage, $weatherNoPpc, date_create($localStartDate), date_create($localEndDate), $pa0, $pa1, $pa2, $pa3);
        }
        if ($anlage->getUsePPC()){
            $weather = $weatherWithPpc;
        } else {
            $weather = $weatherNoPpc;
        }

        // Leistungsdaten ermitteln
        $power = $this->powerServicer->getSumAcPowerV2Ppc($anlage, date_create($localStartDate), date_create($localEndDate));

        $result['powerEvu']     = $power['powerEvu'];
        $result['powerEvuDep0'] = $power['powerEvuDep0'];
        $result['powerEvuDep1'] = $power['powerEvuDep1'];
        $result['powerEvuDep2'] = $power['powerEvuDep2'];
        $result['powerEvuDep3'] = $power['powerEvuDep3'];
        $result['powerActDep0'] = $power['powerActDep0'];
        $result['powerActDep1'] = $power['powerActDep1'];
        $result['powerActDep2'] = $power['powerActDep2'];
        $result['powerActDep3'] = $power['powerActDep3'];
        $result['powerAct'] = $power['powerAct'];
        $result['powerExp'] = $power['powerExpEvu'] > 0 ? $power['powerExpEvu'] : $power['powerExp'];
        $result['powerEGridExt'] = $power['powerEGridExt'];

        // Strahlungen vereinfachen und Umrechnen
        $irr = $weather['irr0'] / 4 / 1000; // Umrechnug zu kWh
        $irr0 = $weather['irr0'] / 4 / 1000;
        $irr1 = $weather['irr1'] / 4 / 1000;
        $irr2 = $weather['irr2'] / 4 / 1000;
        $irr3 = $weather['irr3'] / 4 / 1000;
        $irrNoPpc = $weatherNoPpc['upperIrr'] / 4 / 1000; // Umrechnug zu kWh
        $irrNoPpc0 = $weatherNoPpc['irr0'] / 4 / 1000;
        $irrNoPpc1 = $weatherNoPpc['irr1'] / 4 / 1000;
        $irrNoPpc2 = $weatherNoPpc['irr2'] / 4 / 1000;
        $irrNoPpc3 = $weatherNoPpc['irr3'] / 4 / 1000;

        $tempCorrection = 0; // not used at the Moment

        // PR Calculation
        // Departement 0 (OpenBook)
        $result['powerTheoDep0'] = match($anlage->getPRFormular0()) {
            'Lelystad'          => $power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'IEC_with_deg'      => $weather['theoPowerDeg'],
            'TempCorrNREL'      => $weather['theoPowerTempCorr_NREL'], // same formular NREL and IEC
            'IEC61724-1:2021'   => $weather['theoPowerTempCorDeg_IEC'],
            'Veendam'           => $weather['theoPowerPA0'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default             => $anlage->getPnom() * $irr0    // all others calc by Pnom and Irr.
        };
        $result['powerTheoDep0NoPpc'] = match($anlage->getPRFormular0()) {
            'Lelystad'          => $power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'IEC_with_deg'      => $weather['theoPowerDeg'],
            'TempCorrNREL'      => $weatherNoPpc['theoPowerTempCorr_NREL'],
            'IEC61724-1:2021'   => $weatherNoPpc['theoPowerTempCorDeg_IEC'],
            'Veendam'           => $weatherNoPpc['theoPowerPA0'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default             => $anlage->getPnom() * $irrNoPpc0    // all others calc by Pnom and Irr.
        };
        $result['powerTheo'] = $result['powerTheoDep0'];
        if ($result['powerTheoDep0'] !== null) {
            $result['prDep0Act'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr0, $result['powerActDep0'], $result['powerTheoDep0'], $pa0); //(($power['powerAct'] / $tempTheoPower) * 100;
            $result['prDep0Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr0, $result['powerEvuDep0'], $result['powerTheoDep0'], $pa0); //($power['powerEvu'] / $tempTheoPower) * 100;
            $result['prDep0Exp'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irrNoPpc0, $result['powerExp'], $result['powerTheoDep0NoPpc'], $pa0); //(($result['powerExp'] / $tempTheoPower) * 100;
            $result['prDep0EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr0, $result['powerEGridExt'], $result['powerTheoDep0'], $pa0); //(($power['powerEGridExt'] / $tempTheoPower) * 100;
        } else {
            $result['prDep0Act'] = $result['prDep0Evu'] = $result['prDep0Exp'] = $result['prDep0EGridExt'] = 0;
        }

        // Departemet 1 (O&M)
        $result['powerTheoDep1'] = match($anlage->getPrFormular1()) {
            'Lelystad'          => $power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'IEC_with_deg'      => $weather['theoPowerDeg'],
            'TempCorrNREL'      => $weather['theoPowerTempCorr_NREL'],
            'IEC61724-1:2021'   => $weather['theoPowerTempCorDeg_IEC'],
            'Veendam'           => $weather['theoPowerPA1'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default             => $anlage->getPnom() * $irr1    // all others calc by Pnom and Irr.
        };
        $result['powerTheoDep1NoPpc'] = match($anlage->getPrFormular1()) {
            'Lelystad'          => $power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'IEC_with_deg'      => $weather['theoPowerDeg'],
            'TempCorrNREL'      => $weatherNoPpc['theoPowerTempCorr_NREL'],
            'IEC61724-1:2021'   => $weatherNoPpc['theoPowerTempCorDeg_IEC'],
            'Veendam'           => $weatherNoPpc['theoPowerPA1'],    // if theoretic Power is weighter by PA (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default             => $anlage->getPnom() * $irrNoPpc0    // all others calc by Pnom and Irr.
        };
        if ($result['powerTheoDep1'] !== null) {
            $result['prDep1Act'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr1, $result['powerActDep1'], $result['powerTheoDep1'], $pa1); //(($power['powerAct'] / $tempTheoPower) * 100;
            $result['prDep1Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr1, $result['powerEvuDep1'], $result['powerTheoDep1'], $pa1); //($power['powerEvu'] / $tempTheoPower) * 100;
            $result['prDep1Exp'] = $result['prDep0Exp']; //$this->calcPrBySelectedAlgorithm($anlage, 1, $irrNoPpc0, $result['powerExp'], $result['powerTheoDep1NoPpc'], $pa1); //(($result['powerExp'] / $tempTheoPower) * 100;
            $result['prDep1EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr1, $result['powerEGridExt'], $result['powerTheoDep1'], $pa1); //(($power['powerEGridExt'] / $tempTheoPower) * 100;
        } else {
            $result['prDep1Act'] = $result['prDep1Evu'] = $result['prDep1Exp'] = $result['prDep1EGridExt'] = 0;
        }
        // Departemet 2 (EPC)
        $result['powerTheoDep2'] = match($anlage->getPrFormular2()) {
            'Lelystad'          => $power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'IEC_with_deg'      => $weather['theoPowerDeg'],
            'TempCorrNREL'      => $weather['theoPowerTempCorr_NREL'],
            'IEC61724-1:2021'   => $weather['theoPowerTempCorDeg_IEC'],
            'Veendam'           => $weather['theoPowerPA2'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default             => $anlage->getPnom() * $irr2    // all others calc by Pnom and Irr.
        };
        $result['powerTheoDep2NoPpc'] = match($anlage->getPrFormular2()) {
            'Lelystad'          => $power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'IEC_with_deg'      => $weather['theoPowerDeg'],
            'TempCorrNREL'      => $weatherNoPpc['theoPowerTempCorr_NREL'],
            'IEC61724-1:2021'   => $weatherNoPpc['theoPowerTempCorDeg_IEC'],
            'Veendam'           => $weatherNoPpc['theoPowerPA2'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default             => $anlage->getPnom() * $irrNoPpc0    // all others calc by Pnom and Irr.
        };
        if ($result['powerTheoDep2'] !== null) {
            $result['prDep2Act'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr2, $result['powerActDep2'], $result['powerTheoDep2'], $pa2);
            $result['prDep2Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr2, $result['powerEvuDep2'], $result['powerTheoDep2'], $pa2);
            $result['prDep2Exp'] = $result['prDep0Exp']; //$this->calcPrBySelectedAlgorithm($anlage, 2, $irrNoPpc0, $result['powerExp'], $result['powerTheoDep2NoPpc'], $pa2); //(($result['powerExp'] / $tempTheoPower) * 100;
            $result['prDep2EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr2, $result['powerEGridExt'], $result['powerTheoDep2'], $pa2); //(($power['powerEGridExt'] / $tempTheoPower) * 100;
        } else {
            $result['prDep2Act'] = $result['prDep2Evu'] = $result['prDep2Exp'] = $result['prDep2EGridExt'] = 0;
        }

        // Departement 3 (AM)
        $result['powerTheoDep3'] = match($anlage->getPrFormular3()) {
            'Lelystad'          => $power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'IEC_with_deg'      => $weather['theoPowerDeg'],
            'TempCorrNREL'      => $weather['theoPowerTempCorr_NREL'],
            'IEC61724-1:2021'   => $weather['theoPowerTempCorDeg_IEC'],
            'Veendam'           => $weather['theoPowerPA3'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default             => $anlage->getPnom() * $irr3    // all others calc by Pnom and Irr.
        };
        $result['powerTheoDep3NoPpc'] = match($anlage->getPrFormular3()) {
            'Lelystad'          => $power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'IEC_with_deg'      => $weather['theoPowerDeg'],
            'TempCorrNREL'      => $weatherNoPpc['theoPowerTempCorr_NREL'],
            'IEC61724-1:2021'   => $weatherNoPpc['theoPowerTempCorDeg_IEC'],
            'Veendam'           => $weatherNoPpc['theoPowerPA3'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default             => $anlage->getPnom() * $irrNoPpc0    // all others calc by Pnom and Irr.
        };
        if ($result['powerTheoDep3'] !== null) {
            $result['prDep3Act'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr3, $result['powerActDep3'], $result['powerTheoDep3'], $pa3); //(($power['powerAct'] / $tempTheoPower) * 100;
            $result['prDep3Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr3, $result['powerEvuDep3'], $result['powerTheoDep3'], $pa3); //($power['powerEvu'] / $tempTheoPower) * 100;
            $result['prDep3Exp'] = $result['prDep0Exp']; //$this->calcPrBySelectedAlgorithm($anlage, 3, $irrNoPpc0, $result['powerExp'], $result['powerTheoDep3NoPpc'], $pa3); //(($result['powerExp'] / $tempTheoPower) * 100;
            $result['prDep3EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr3, $result['powerEGridExt'], $result['powerTheoDep3'], $pa3); //(($power['powerEGridExt'] / $tempTheoPower) * 100;
        } else {
            $result['prDep3Act'] = $result['prDep3Evu'] = $result['prDep3Exp'] = $result['prDep3EGridExt'] = 0;
        }

        $anzCase5PerDay = $this->case5Repo->countCase5DayAnlage($anlage, $localStartDate, $localEndDate);

        $result['algorithmus'] = $anlage->getUseCustPRAlgorithm(); /** @deprecated */
        $result['powerTheoTempCorr'] = (float) $power['powerTheo']; /** @deprecated */
        $result['tempCorrection'] = (float) $tempCorrection;
        $result['irradiation'] = $irr;
        $result['irr0'] = $irr0;
        $result['irr1'] = $irr1;
        $result['irr2'] = $irr2;
        $result['irr3'] = $irr3;
        $result['irradiationNoPpc'] = $irrNoPpc;
        $result['irrNoPpc0'] = $irrNoPpc0;
        $result['irrNoPpc1'] = $irrNoPpc1;
        $result['irrNoPpc2'] = $irrNoPpc2;
        $result['irrNoPpc3'] = $irrNoPpc3;
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
        $result['prExp'] = $result['prDep0Exp']; // EPC PR
        $result['prEGridExt'] = $result['prDep2EGridExt']; // EPC PR
        $result['anzCase5'] = $anzCase5PerDay;
        $result['tCellAvgMeasured'] = (float) $weather['panelTempAvg'];
        $result['tCellAvgNrel'] = (float) $weather['temp_cell_corr'];
        $result['tCellAvgMultiIrr'] = (float) $weather['temp_cell_multi_irr'];

        return $result;
    }

    /**
     * @param Anlage $anlage
     * @param int $inverterID
     * @param DateTime $startDate
     * @param DateTime|null $endDate
     * @return array
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function calcPRByInverter(Anlage $anlage, int $inverterID, DateTime $startDate, DateTime $endDate = null): array
    {
        $result = [];

        // PR für einen Tag (wenn $endDate = null) oder für beliebigen Zeitraum (auch für Rumpfmonate in epc Berichten) berechnen
        $localStartDate = $startDate->format('Y-m-d 00:00');
        if ($endDate === null) {
            $localEndDate = $startDate->format('Y-m-d 23:59');
        } else {
            $localEndDate = $endDate->format('Y-m-d 23:59');
        }

        // Verfügbarkeit ermitteln
        $pa1 = $pa2 = $pa3 = 0;
        #$this->availabilityByTicket->checkAvailability($anlage, date_create($localStartDate), 0);
        $pa0 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), $inverterID, 0);
        if (!$anlage->getSettings()->isDisableDep1()) {
            #$this->availabilityByTicket->checkAvailability($anlage, date_create($localStartDate), 1);
            $pa1 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), $inverterID, 1);
        }
        if (!$anlage->getSettings()->isDisableDep2()) {
            #$this->availabilityByTicket->checkAvailability($anlage, date_create($localStartDate), 2);
            $pa2 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), $inverterID, 2);
        }
        if (!$anlage->getSettings()->isDisableDep3()) {
            #$this->availabilityByTicket->checkAvailability($anlage, date_create($localStartDate), 3);
            $pa3 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), $inverterID, 3);
        }

        // Wetter Daten ermitteln
        $weather = $this->weatherFunctions->getWeather($anlage->getWeatherStation(), $localStartDate, $localEndDate, true, $anlage);
        if (is_array($weather)) {
            $weather = $this->sensorService->correctSensorsByTicket($anlage, $weather, date_create($localStartDate), date_create($localEndDate));
        }
        // Leistungsdaten ermitteln
        $power = $this->powerServicer->getSumAcPowerV2Ppc($anlage, date_create($localStartDate), date_create($localEndDate), $inverterID);

        $result['powerEvu'] = $power['powerEvu'];
        $result['powerAct'] = $power['powerAct'];
        $result['powerExp'] = $power['powerExpEvu'] > 0 ? $power['powerExpEvu'] : $power['powerExp'];
        $result['powerEGridExt'] = $power['powerEGridExt'];

        // Strahlungen berechnen – (upper = Ost / lower = West)
        if ($anlage->getIsOstWestAnlage()) {
            $irr = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
        } else {
            $irr = $weather['upperIrr'] / 4 / 1000; // Umrechnug zu kWh
        }

        $irr = $this->functions->checkAndIncludeMonthlyCorrectionIrr($anlage, $irr, $localStartDate, $localEndDate);

        $tempCorrection = 0; // not used at the Moment

        $inverterPowerDc = $anlage->getPnomInverterArray();
        // PR Calculation
        $result['powerTheoDep0'] = match($anlage->getPrFormular0()) {
            'Lelystad'  => $weather['theoPowerTempCorr_NREL'], //$power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'Veendam'   => $weather['theoPowerPA0'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default     => $inverterPowerDc[$inverterID] * $irr    // all others calc by Pnom and Irr.
        };
        $result['powerTheo'] = $result['powerTheoDep0'];
        $result['prDep0Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr, $result['powerEvu'], $result['powerTheoDep0'], $pa0); //($power['powerEvu'] / $tempTheoPower) * 100;
        $result['prDep0Act'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr, $result['powerAct'], $result['powerTheoDep0'], $pa0); //(($power['powerAct'] / $tempTheoPower) * 100;
        $result['prDep0Exp'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr, $result['powerExp'], $result['powerTheoDep0'], $pa0); //(($result['powerExp'] / $tempTheoPower) * 100;
        $result['prDep0EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr, $result['powerEGridExt'], $result['powerTheoDep0'], $pa0); //(($power['powerEGridExt'] / $tempTheoPower) * 100;

        $result['powerTheoDep1'] = match($anlage->getPrFormular1()) {
            'Lelystad'  => $weather['theoPowerTempCorr_NREL'], //$power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'Veendam'   => $weather['theoPowerPA1'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default     => $inverterPowerDc[$inverterID] * $irr    // all others calc by Pnom and Irr.
        };
        $result['prDep1Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr, $result['powerEvu'], $result['powerTheoDep1'], $pa1); //($power['powerEvu'] / $tempTheoPower) * 100;
        $result['prDep1Act'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr, $result['powerAct'], $result['powerTheoDep1'], $pa1); //(($power['powerAct'] / $tempTheoPower) * 100;
        $result['prDep1Exp'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr, $result['powerExp'], $result['powerTheoDep1'], $pa1); //(($result['powerExp'] / $tempTheoPower) * 100;
        $result['prDep1EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 1, $irr, $result['powerEGridExt'], $result['powerTheoDep1'], $pa1); //(($power['powerEGridExt'] / $tempTheoPower) * 100;

        $result['powerTheoDep2'] = match($anlage->getPrFormular2()) {
            'Lelystad'  => $weather['theoPowerTempCorr_NREL'], //$power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'Veendam'   => $weather['theoPowerPA2'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default     => $inverterPowerDc[$inverterID] * $irr    // all others calc by Pnom and Irr.
        };
        $result['prDep2Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr, $result['powerEvu'], $result['powerTheoDep2'], $pa2); //($power['powerEvu'] / $tempTheoPower) * 100;
        $result['prDep2Act'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr, $result['powerAct'],  $result['powerTheoDep2'], $pa2); //(($power['powerAct'] / $tempTheoPower) * 100;
        $result['prDep2Exp'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr, $result['powerExp'], $result['powerTheoDep2'], $pa2); //(($result['powerExp'] / $tempTheoPower) * 100;
        $result['prDep2EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 2, $irr, $result['powerEGridExt'], $result['powerTheoDep2'], $pa2); //(($power['powerEGridExt'] / $tempTheoPower) * 100;

        $result['powerTheoDep3'] = match($anlage->getPrFormular3()) {
            'Lelystad'  => $weather['theoPowerTempCorr_NREL'], //$power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'Veendam'   => $weather['theoPowerPA3'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default     => $inverterPowerDc[$inverterID] * $irr    // all others calc by Pnom and Irr.
        };
        $result['prDep3Evu'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr, $result['powerEvu'], $result['powerTheoDep3'], $pa3); //($power['powerEvu'] / $tempTheoPower) * 100;
        $result['prDep3Act'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr, $result['powerAct'], $result['powerTheoDep3'], $pa3); //(($power['powerAct'] / $tempTheoPower) * 100;
        $result['prDep3Exp'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr, $result['powerExp'], $result['powerTheoDep3'], $pa3); //(($result['powerExp'] / $tempTheoPower) * 100;
        $result['prDep3EGridExt'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr, $result['powerEGridExt'], $result['powerTheoDep3'], $pa3); //(($power['powerEGridExt'] / $tempTheoPower) * 100;


        $anzCase5PerDay = $this->case5Repo->countCase5DayAnlage($anlage, $localStartDate, $localEndDate);

        $result['algorithmus'] = $anlage->getUseCustPRAlgorithm(); /** @deprecated */
        $result['powerTheoTempCorr'] = (float) $power['powerTheo']; /** @deprecated */
        $result['tempCorrection'] = (float) $tempCorrection;
        $result['irradiation'] = $irr;
        $result['availability'] = $pa2;  /** @deprecated old EPC */
        $result['availability2'] = $pa1; /** @deprecated old O&M */
        $result['pa0'] = $pa0;
        $result['pa1'] = $pa1;
        $result['pa2'] = $pa2;
        $result['pa3'] = $pa3;
        $result['prDefaultEvu'] = $result['prDep0Evu']; // OpenBook / default PR
        $result['prDefaultAct'] = $result['prDep0Act']; // OpenBook / default PR
        $result['prDefaultExp'] = $result['prDep0Exp']; // OpenBook / default PR
        $result['prDefaultEGridExt'] = $result['prDirradiationep0EGridExt']; // OpenBook / default PR
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
     * we will use this function to calculate the PR inverter-based
     * @param Anlage $anlage
     * @param int $inverterID
     * @param DateTime $startDate
     * @param DateTime|null $endDate
     * @return array
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    public function calcPRByInverterAM(Anlage $anlage, int $inverterID, DateTime $startDate, DateTime $endDate = null): array{
        $result = [];;
        $inverterPowerDc = $anlage->getPnomInverterArray();
        // PR für einen Tag (wenn $endDate = null) oder für beliebigen Zeitraum (auch für Rumpfmonate in epc Berichten) berechnen
        $localStartDate = $startDate->format('Y-m-d 00:00');
        if ($endDate === null) {
            $localEndDate = $startDate->format('Y-m-d 23:59');
        } else {
            $localEndDate = $endDate->format('Y-m-d 23:59');
        }

        $pa3 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), $inverterID, 3);
        $weather = $this->weatherFunctions->getWeather($anlage->getWeatherStation(), $localStartDate, $localEndDate, true, $anlage, $inverterID);
        if (is_array($weather)) {
            $weather = $this->sensorService->correctSensorsByTicket($anlage, $weather, date_create($localStartDate), date_create($localEndDate));
        }
        if ($anlage->getIsOstWestAnlage()) {
            $irr = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
        } else {
            $irr = $weather['upperIrr'] / 4 / 1000; // Umrechnug zu kWh
        }

        $power = $this->powerServicer->getSumAcPowerV2($anlage, date_create($localStartDate), date_create($localEndDate), false, $inverterID);
        $result['powerTheo'] = match($anlage->getPrFormular3()) {
            'Lelystad'  => $power['powerTheo'],         // if theoretic Power ist corrected by temperature (NREL) (PR Algorithm = Lelystad) then use 'powerTheo' from array $power array,
            'Veendam'   => $weather['theoPowerPA3'],    // if theoretic Power is weighter by pa (PR Algorithm = Veendam) the use 'theoPowerPA' from $weather array
            default     => $inverterPowerDc[$inverterID] * $irr    // all others calc by Pnom and Irr.
        };
        $irr = $this->functions->checkAndIncludeMonthlyCorrectionIrr($anlage, $irr, $localStartDate, $localEndDate);

        if (!$anlage->getSettings()->isDisableDep3()) $result['prDep3Act'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr, $power['powerAct'], $result['powerTheo'], $pa3, $inverterID);
        else $result['prDep3Act'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr, $power['powerAct'], $result['powerTheo'], $pa3, $inverterID);

        $result['powerAct'] = $power['powerAct'];
        $result['irradiation'] = $irr;
        return $result;
    }

    /**
     * We use this function to
     * @param Anlage $anlage
     * @param int $inverterID
     * @param DateTime $startDate
     * @return array
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException|\JsonException
     */
    public function calcPRByInverterAMDay(Anlage $anlage, int $inverterID, DateTime $startDate): array{
        $result = [];
        $inverterPowerDc = $anlage->getPnomInverterArray();
        // PR für einen Tag (wenn $endDate = null) oder für beliebigen Zeitraum (auch für Rumpfmonate in epc Berichten) berechnen
        $localStartDate = $startDate->format('Y-m-d 00:00');
        $localEndDate = $startDate->format('Y-m-d 23:59');
        $pa3 = $this->availabilityByTicket->calcAvailability($anlage, date_create($localStartDate), date_create($localEndDate), $inverterID, 3);

        $weather = $this->weatherFunctions->getWeather($anlage->getWeatherStation(), $localStartDate, $localEndDate, true, $anlage);
        if (is_array($weather)) {
            $weather = $this->sensorService->correctSensorsByTicket($anlage, $weather, date_create($localStartDate), date_create($localEndDate));
        }
        if ($anlage->getIsOstWestAnlage()) {
            $irr = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
        } else {
            $irr = $weather['upperIrr'] / 4 / 1000; // Umrechnug zu kWh
        }

        $power = $this->powerServicer->getSumAcPowerV2Ppc($anlage, date_create($localStartDate), date_create($localEndDate), $inverterID);

        if (!$anlage->getSettings()->isDisableDep3()) $result['prDep3Act'] = $this->calcPrBySelectedAlgorithm($anlage, 3, $irr, $power['powerAct'], $result['powerTheo'], $pa3, $inverterID);
        else $result['prDep3Act'] = $this->calcPrBySelectedAlgorithm($anlage, 0, $irr, $power['powerAct'], $result['powerTheo'], $pa3, $inverterID);

        return $result;
    }

    /**
     * @param Anlage $anlage
     * @param float $irr
     * @param float $spezYield
     * @param float $eGrid
     * @param float $theoPowerFT
     * @param $pa
     * @return ?float
     * @throws InvalidArgumentException
     * @deprecated use 'calcPrBySelectedAlgorithm()' instead
     */
    #[Deprecated]
    public function calcPrByValues(Anlage $anlage, float $irr, float $spezYield, float $eGrid, float $theoPowerFT, $pa): ?float
    {
        return $this->calcPrBySelectedAlgorithm($anlage, 2, $irr, $eGrid, $theoPowerFT, $pa);
    }

    /**
     * Return value is in percentage
     * @param Anlage $anlage
     * @param int $dep
     * @param float|null $irr
     * @param float $eGrid
     * @param float $theoPower
     * @param float|null $pa
     * @param int|null $inverterID
     * @return float|null
     * @throws InvalidArgumentException
     */
    public function calcPrBySelectedAlgorithm(Anlage $anlage, int $dep, ?float $irr, float $eGrid, float $theoPower, ?float $pa, ?int $inverterID = null): ?float
    {

        $result = null;
        $irrLimit = 0.001;
        if (!is_null($irr)) {
            $algorithm = match ($dep) {
                1 => $anlage->getPrFormular1(),
                2 => $anlage->getPrFormular2(),
                3 => $anlage->getPrFormular3(),
                default => $anlage->getPrFormular0(),
            };

            $years = $anlage->getBetriebsJahre();
            if ($inverterID === null) {
                $pnom = $anlage->getPnom();
            } else {
                $pnom = $anlage->getPnomInverterArray()[$inverterID];
            }
            switch ($algorithm) {
                case 'Groningen': // special for Groningen
                    if ($theoPower > $irrLimit && $pa !== null) $result = ($eGrid > 0 && $pa > 0) ? ($eGrid / ($theoPower / 1000 * $pa)) * (10 / 0.9945) : null;
                    break;
                case 'Veendam': // with availability
                    if ($theoPower > $irrLimit) $result = $eGrid > 0 ? ($eGrid / $theoPower) * 100 : null;
                    break;
                case 'IEC61724-1:2021':// with Temp Correction by IEC 61724-1:2021
                case 'TempCorrNREL':
                case 'Lelystad': // with Temp Correction by NREL
                    // Sum of theo. power from the actual values (corrected with temperature correction)
                    if ($theoPower > $irrLimit) $result = $eGrid > 0 ? ($eGrid / $theoPower) * 100 : null;
                    break;
                case 'Ladenburg': // not tested (2023-03-22 MR)
                    if ($years >= 0) {
                        // entspricht Standard PR plus degradation (Faktor = $years int)
                        $powerTheo = $pnom * (1 - ($anlage->getDegradationPR() / 100)) ** $years * $irr;
                        $result = ($irr > $irrLimit) ? ($eGrid / $powerTheo) * 100 : null;
                    }
                    break;
                case 'Doellen': // not finaly tested (2023-09-12 MR)
                    if ($years >= 0) {
                        // entspricht Standard PR plus degradation in Zwei Faktoren (Faktor = $years int)
                        $powerTheo = $pnom * (1 - ($anlage->getDegradationPR() / 100)) ** ($years - 1) * (1 - ($anlage->getDegradationPR() / 100) / 2) * $irr;
                        $result = ($irr > $irrLimit) ? ($eGrid / $powerTheo) * 100 : null;
                    }

                    break;


                default:
                    // wenn es keinen spezielen Algoritmus gibt
                    $result = ($irr > $irrLimit) ? ($eGrid / ($pnom * $irr)) * 100 : null;
            }
        }
        return $result;
    }
}