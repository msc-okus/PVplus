<?php

namespace App\Service;

use App\Service\WeatherFunctionsService;
use App\Repository\Case5Repository;
use App\Repository\GridMeterDayRepository;
use App\Repository\MonthlyDataRepository;
use DateTime;
use PDO;
use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use App\Repository\PRRepository;
use App\Repository\PVSystDatenRepository;
use Doctrine\ORM\EntityManagerInterface;

class PRCalulationService
{
    use G4NTrait;

    private PVSystDatenRepository $pvSystRepo;
    private AnlagenRepository $anlagenRepository;
    private PRRepository $PRRepository;
    private AnlageAvailabilityRepository $anlageAvailabilityRepo;
    private FunctionsService $functions;
    private EntityManagerInterface $em;
    private Case5Repository $case5Repo;
    private MonthlyDataRepository $monthlyDataRepo;
    private WeatherFunctionsService $weatherFunctions;
    private GridMeterDayRepository $gridMeterDayRepo;

    public function __construct(AnlagenRepository $anlagenRepository,
                                PRRepository $PRRepository,
                                AnlageAvailabilityRepository $anlageAvailabilityRepo,
                                FunctionsService $functions, EntityManagerInterface $em,
                                PVSystDatenRepository $pvSystRepo,
                                Case5Repository $case5Repo,
                                MonthlyDataRepository $monthlyDataRepo,
                                WeatherFunctionsService $weatherFunctions,
                                GridMeterDayRepository $gridMeterDayRepo)
    {
        $this->pvSystRepo = $pvSystRepo;
        $this->anlagenRepository = $anlagenRepository;
        $this->PRRepository = $PRRepository;
        $this->anlageAvailabilityRepo = $anlageAvailabilityRepo;
        $this->functions = $functions;
        $this->em = $em;
        $this->case5Repo = $case5Repo;
        $this->monthlyDataRepo = $monthlyDataRepo;
        $this->weatherFunctions = $weatherFunctions;
        $this->gridMeterDayRepo = $gridMeterDayRepo;
    }

    public function calcPRAll(Anlage $anlage, $day):string
    {
        $timeStamp = strtotime($day);
        // Nur ausführen wenn das zu berechnende Datum vor dem aktuellen Datum liegt (min. Gestern :-))
        if (date('Y-m-d', $timeStamp) < date('Y-m-d',self::getCetTime())) {
            $from = date("Y-m-d 00:00", $timeStamp);
            $to = date("Y-m-d 23:59", $timeStamp);
            $day = date("Y-m-d", $timeStamp);
            $year = date("Y", $timeStamp);
            $month = date("m", $timeStamp);
            $anzTageUntilToday = date('z', $timeStamp) + 1;

            // PAC Date berechnen
            if ($anlage->getUsePac()) {

                $pacDate    = $anlage->getPacDate()->format('Y-m-d 00:00');
                $pacDateEnd = $anlage->getPacDateEnd()->format('Y-m-d 23:59');
                /*
                $pacDateEnd = date('Y-m-d 23:59',strtotime("$pacDate +".$anlage->getPacDuration()." month" ));
                if ($pacDateEnd > $day) $pacDateEnd = date("Y-m-d 23:59", $timeStamp);
                */
                //FAC Date bzw letztes FAC Jahr berechnen
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
            $forecastArray = $this->functions->getFacForecast($anlage, $facDateForecastMinusOneYear, $facDateForecast, $day);

            // Berechne Actual Power für Tag, Jahr und PAC
            $powerActArray = $this->functions->getSumPowerAcAct($anlage, $from, $to, $pacDate, $pacDateEnd);// Summe Leistung AC IST

            $powerAct       = $powerActArray['sumPower'];
            $powerActMonth  = $powerActArray['powerActMonth'];
            $powerActPac    = $powerActArray['powerActPac'];
            $powerActYear   = $powerActArray['powerActYear'];
            $powerEvu       = $powerActArray['powerEvu'];
            $powerEvuMonth  = $powerActArray['powerEvuMonth'];
            $powerEvuPac    = $powerActArray['powerEvuPac'];
            $powerEvuYear   = $powerActArray['powerEvuYear'];

            // Wenn externe Tagesdaten genutzt werden sollen, lade diese aus der DB und ÜBERSCHREIBE die Daten aus den 15Minuten Werten
            $powerEGridExt      = $this->functions->getSumeGridMeter($anlage, $from, $to, true);
            $powerEGridExtMonth = $this->functions->getSumeGridMeter($anlage, date('Y-m-01 00:00', strtotime($from)), $to);
            $powerEGridExtPac   = $this->functions->getSumeGridMeter($anlage, $pacDate, $to);
            $powerEGridExtYear  = $this->functions->getSumeGridMeter($anlage, date('Y-01-01 00:00', strtotime($from)), $to);

            if($anlage->getUsePac()) {
                $weather = $this->functions->getWeather($anlage->getWeatherStation(), $from, $to, $pacDate, $pacDateEnd); // Strahlung und andere Wetter Daten als Array
            } else {
                $weather = $this->functions->getWeather($anlage->getWeatherStation(), $from, $to, false, false); // Strahlung und andere Wetter Daten als Array
            }

            // Berechne Summe und mittelwert der JSON Array
            ($powerActArray['irrAnlage']) ?  $irrAnlageArray  = $this->functions->buildSumFromArray($powerActArray['irrAnlage'], 4) : $irrAnlageArray = []; // Strahlung (Irradiation) in Wh/qm
            ($powerActArray['tempAnlage']) ? $tempAnlageArray = $this->functions->buildAvgFromArray($powerActArray['tempAnlage']) : $tempAnlageArray = [];           // Temperatur
            ($powerActArray['windAnlage']) ? $windAnlageArray = $this->functions->buildAvgFromArray($powerActArray['windAnlage']) : $windAnlageArray = [];           // Wind

            // Berechne Expected G4N für Tag, Jahr und PAC
            $powerExpArray  = $this->functions->getSumPowerAcExp($anlage, $from, $to, $pacDate, $pacDateEnd); // Summe Leistung AC SOLL
            $powerExp       = $powerExpArray['sumPowerExp'];
            $powerExpMonth  = $powerExpArray['sumPowerExpMonth'];
            $powerExpYear   = $powerExpArray['sumPowerExpYear'];
            $powerExpPac    = $powerExpArray['sumPowerExpPac'];

            // PlantAvailability berechnen FIRST
            // pro Tag
            // FIRST
            $availability = $this->anlageAvailabilityRepo->sumAvailabilityPerDay($anlage->getAnlId(), $day);
            if (!$availability) $availability = 0;
            // SECOND
            $availabilitySecond = $this->anlageAvailabilityRepo->sumAvailabilitySecondPerDay($anlage->getAnlId(), $day);
            if (!$availabilitySecond) $availabilitySecond = 0;

            //pro Monat
            $startMonth = date('Y-m-01 00:00', strtotime($to));
            $anzPRRecordsPerMonth = $this->PRRepository->anzRecordsPRPerPac($anlage->getAnlId(), $startMonth, $to);
            if ($anzPRRecordsPerMonth == 0) $anzPRRecordsPerMonth = 1;
            // FIRST
            $availabilityPerMonth = $this->PRRepository->sumAvailabilityPerPac($anlage->getAnlId(), $startMonth, $to);
            $availabilityPerMonth = $availabilityPerMonth / $anzPRRecordsPerMonth;
            // SECOND
            $availabilitySecondPerMonth = $this->PRRepository->sumAvailabilitySecondPerPac($anlage->getAnlId(), $startMonth, $to);
            $availabilitySecondPerMonth = $availabilitySecondPerMonth / $anzPRRecordsPerMonth;

            // pro Jahr
            // FIRST
            $availabilityPerYear = $this->PRRepository->sumAvailabilityPerYear($anlage->getAnlId(), $year, $to);
            $anzPRRecordsPerYear = $this->PRRepository->anzRecordsPRPerYear($anlage->getAnlId(), $year, $to);
            if ($anzPRRecordsPerYear == 0) $anzPRRecordsPerYear = 1;
            $availabilityPerYear = $availabilityPerYear / $anzPRRecordsPerYear;
            // SECOND
            $availabilityPerYearSecond = $this->PRRepository->sumAvailabilitySecondPerYear($anlage->getAnlId(), $year, $to);
            if ($availabilityPerYearSecond == null) {
                $availabilityPerYearSecond = '';
            } else {
                $availabilityPerYearSecond = $availabilityPerYearSecond / $anzPRRecordsPerYear;
            }

            // auf Basis des PAC (Productions Start Datum)
            // FIRST und SECOND
            if ($anlage->getPacDate()) { // Nur wenn pacDate gesetzt ist
                $anzPRRecordsPerPac = $this->PRRepository->anzRecordsPRPerPac($anlage->getAnlId(), $pacDate, $pacDateEnd);
                if ($anzPRRecordsPerPac == 0) $anzPRRecordsPerPac = 1;
                // FIRST
                $availabilityPerPac = $this->PRRepository->sumAvailabilityPerPac($anlage->getAnlId(), $pacDate, $pacDateEnd);
                $availabilityPerPac = $availabilityPerPac / $anzPRRecordsPerPac;
                // SECOND
                $availabilitySecondPerPac = $this->PRRepository->sumAvailabilitySecondPerPac($anlage->getAnlId(), $pacDate, $pacDateEnd);
                $availabilitySecondPerPac = $availabilitySecondPerPac / $anzPRRecordsPerPac;
            } else {
                $availabilityPerPac = 0;
                $availabilitySecondPerPac = 0;
            }

            // PvSyst Daten berechnen
            $pvSystArray = $this->functions->getPvSyst($anlage, $from, $to, $pacDate);

            //Kundenspezifischer PR berechnen
            $tempCorrection = 0;
            $prEvu = $prAct = $prExp = $prEGridExt = $monthPrEvu = $monthPrAct = $monthPrEGridExt = 0;
            $yearPrEGridExt = $pacPrEGridExt = $monthPrExp = $pacPrAct = $yearPrAct = $pacPrEvu = $yearPrEvu = $pacPrExp = $yearPrExp = 0;
            $prDefaultEvu = $prDefaultAct = $prDefaultExp = $prDefaultEGridExt = 0;
            $pacPrDefaultEvu = $pacPrDefaultAct = $pacPrDefaultExp = $pacPrDefaultEGridExt = 0;
            $monthPrDefaultEvu = $monthPrDefaultAct = $monthPrDefaultExp = $monthPrDefaultEGridExt = 0;
            $yearPrDefaultEvu = $yearPrDefaultAct = $yearPrDefaultExp = $yearPrDefaultEGridExt = 0;

            //Strahlung berechnen
            if ($anlage->getIsOstWestAnlage()) {
                //Strahlung (upper = Ost / lower = West)
                $irr        = ($weather['upperIrr'] * $anlage->getPowerEast()       + $weather['lowerIrr'] * $anlage->getPowerWest())       / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
                $irrMonth   = ($weather['upperIrrMonth'] * $anlage->getPowerEast()  + $weather['lowerIrrMonth'] * $anlage->getPowerWest())  / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
                $irrPac     = ($weather['upperIrrPac'] * $anlage->getPowerEast()    + $weather['lowerIrrPac'] * $anlage->getPowerWest())    / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
                $irrYear    = ($weather['upperIrrYear'] * $anlage->getPowerEast()   + $weather['lowerIrrYear'] * $anlage->getPowerWest())   / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
            } else {
                $irr        = $weather['upperIrr'] / 4 / 1000; // Umrechnug zu kWh
                $irrMonth   = $weather['upperIrrMonth'] / 4 / 1000; // Umrechnug zu kWh
                $irrPac     = $weather['upperIrrPac'] / 1000 / 4;
                $irrYear    = $weather['upperIrrYear'] / 1000 / 4;
            }

            // PowerTheoretical für das Jahr und PAC berechnen (Standard Werte)
            $powerTheo      = $powerTheoDefault        = $anlage->getPower() * $irr;
            $powerTheoMonth = $powerTheoMonthDefault   = $anlage->getPower() * $irrMonth;
            $powerTheoPac   = $powerTheoPacDefault     = $anlage->getPower() * $irrPac;
            $powerTheoYear  = $powerTheoYearDefault    = $anlage->getPower() * $irrYear;
            // Berechne Default PR
            if ($powerTheo > 0) { // Verhindere Divison by zero
                $prDefaultEvu      = ($powerEvu        / $powerTheo) * 100;
                $prDefaultAct      = ($powerAct        / $powerTheo) * 100;
                $prDefaultExp      = ($powerExp        / $powerTheo) * 100;
                $prDefaultEGridExt = ($powerEGridExt   / $powerTheo) * 100;
            }
            if ($powerTheoMonth > 0) {
                $monthPrDefaultEvu      = ($powerEvuMonth      / $powerTheoMonth) * 100;
                $monthPrDefaultAct      = ($powerActMonth      / $powerTheoMonth) * 100;
                $monthPrDefaultExp      = ($powerExpMonth      / $powerTheoMonth) * 100;
                $monthPrDefaultEGridExt = ($powerEGridExtMonth / $powerTheoMonth) * 100;
            }
            if ($anlage->getUsePac() && $powerTheoPac > 0) {
                $pacPrDefaultEvu       = ($powerEvuPac         / $powerTheoPac) * 100;
                $pacPrDefaultAct       = ($powerActPac         / $powerTheoPac) * 100;
                $pacPrDefaultExp       = ($powerExpPac         / $powerTheoPac) * 100;
                $pacPrDefaultEGridExt  = ($powerEGridExtPac    / $powerTheoPac) * 100;
            }
            if ($powerTheoYear > 0) {
                $yearPrDefaultEvu          = ($powerEvuYear        / $powerTheoYear) * 100;
                $yearPrDefaultAct          = ($powerActYear        / $powerTheoYear) * 100;
                $yearPrDefaultExp          = ($powerExpYear        / $powerTheoYear) * 100;
                $yearPrDefaultEGridExt     = ($powerEGridExtYear   / $powerTheoYear) * 100;
            }
            // Berechne kundenspezifischen PR
            switch ($anlage->getUseCustPRAlgorithm()) {
                case 'Groningen':
                    // Umrechnung Globalstrahlung auf Modulstrahlung erfolgt schon beim Import
                    // (Bsp. Groningen: IrrUpper = umgerechnete Globalstrahlung, IrrLower = gemesene Modulstrahlung, IrrHori = gemessene Horizontalstrahlung)
                    if ($powerTheo > 0 && $availability > 0) { // Verhinder Divison by zero
                        $prEvu              = ($powerEvu            / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                        $prAct              = ($powerAct            / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                        $prExp              = ($powerExp            / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                        $prEGridExt         = ($powerEGridExt       / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                    }
                    if ($powerTheoMonth > 0 && $availabilityPerMonth > 0) {
                        $monthPrEvu = ($powerEvuMonth / ($powerTheoMonth / 1000 * $availabilityPerMonth)) * (10 / 0.9945);
                        $monthPrAct = ($powerActMonth / ($powerTheoMonth / 1000 * $availabilityPerMonth)) * (10 / 0.9945);
                        $monthPrExp = ($powerExpMonth / ($powerTheoMonth / 1000 * $availabilityPerMonth)) * (10 / 0.9945);
                        $monthPrEGridExt = ($powerEGridExtMonth / ($powerTheoMonth / 1000 * $availabilityPerMonth)) * (10 / 0.9945);
                    }

                    if ($anlage->getUsePac() && $powerTheoPac > 0 && $availabilityPerPac > 0) {
                        $pacPrEvu       = ($powerEvuPac / ($powerTheoPac / 1000 * $availabilityPerPac)) * (10 / 0.9945);
                        $pacPrAct       = ($powerActPac / ($powerTheoPac / 1000 * $availabilityPerPac)) * (10 / 0.9945);
                        $pacPrExp       = ($powerExpPac / ($powerTheoPac / 1000 * $availabilityPerPac)) * (10 / 0.9945);
                        $pacPrEGridExt  = ($powerEGridExtPac / ($powerTheoPac / 1000 * $availabilityPerPac)) * (10 / 0.9945);
                    }
                    if ($powerTheoYear > 0 && $availabilityPerYear > 0) {
                        $yearPrEvu          = ($powerEvuYear        / ($powerTheoYear / 1000 * $availabilityPerYear)) * (10 / 0.9945);
                        $yearPrAct          = ($powerActYear        / ($powerTheoYear / 1000 * $availabilityPerYear)) * (10 / 0.9945);
                        $yearPrExp          = ($powerExpYear        / ($powerTheoYear / 1000 * $availabilityPerYear)) * (10 / 0.9945);
                        $yearPrEGridExt     = ($powerEGridExtYear   / ($powerTheoYear / 1000 * $availabilityPerYear)) * (10 / 0.9945);
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
                        $monthPrEvu      = ($powerEvuMonth      / ($powerTheoMonth / 100 * $availabilityPerMonth)) * 100;
                        $monthPrAct      = ($powerActMonth      / ($powerTheoMonth / 100 * $availabilityPerMonth)) * 100;
                        $monthPrExp      = ($powerExpMonth      / ($powerTheoMonth / 100 * $availabilityPerMonth)) * 100;
                        $monthPrEGridExt = ($powerEGridExtMonth / ($powerTheoMonth / 100 * $availabilityPerMonth)) * 100;
                    }
                    if ($anlage->getUsePac() && $powerTheoPac > 0 && $availabilityPerPac > 0) {
                        $pacPrEvu       = ($powerEvuPac         / ($powerTheoPac / 100 * $availabilityPerPac)) * 100;
                        $pacPrAct       = ($powerActPac         / ($powerTheoPac / 100 * $availabilityPerPac)) * 100;
                        $pacPrExp       = ($powerExpPac         / ($powerTheoPac / 100 * $availabilityPerPac)) * 100;
                        $pacPrEGridExt  = ($powerEGridExtPac    / ($powerTheoPac / 100 * $availabilityPerPac)) * 100;
                    }
                    if ($powerTheoYear > 0 && $availabilityPerYear > 0) {
                        $yearPrEvu          = ($powerEvuYear        / ($powerTheoYear / 100 * $availabilityPerYear)) * 100;
                        $yearPrAct          = ($powerActYear        / ($powerTheoYear / 100 * $availabilityPerYear)) * 100;
                        $yearPrExp          = ($powerExpYear        / ($powerTheoYear / 100 * $availabilityPerYear)) * 100;
                        $yearPrEGridExt     = ($powerEGridExtYear   / ($powerTheoYear / 100 * $availabilityPerYear)) * 100;
                    }
                    break;
                case 'Lelystad':
                    // Summe der theo Power aus den IST Werten (koriegiert mit TemperaturKorrektur)
                    if ($anlage->getTempCorrCellTypeAvg() != 0) {
                        $powerTheo = $powerActArray['theoPower'];
                        $powerTheoMonth = $powerActArray['theoPowerMonth'];
                        $powerTheoPac = $powerActArray['theoPowerPac'];
                        $powerTheoYear = $powerActArray['theoPowerYear'];
                    }
                    //dump("PowerTheo: $powerTheo | PowerEGrid: $powerEGridExt");
                    if ($powerTheo > 0) { // Verhinder Divison by zero
                        $prEvu              = ($powerEvu            / $powerTheo) * 100;
                        $prAct              = ($powerAct            / $powerTheo) * 100;
                        $prExp              = ($powerExp            / $powerTheo) * 100;
                        $prEGridExt         = ($powerEGridExt       / $powerTheo) * 100;
                    }
                    if ($powerTheoMonth > 0) {
                        $monthPrEvu      = ($powerEvuMonth      / $powerTheoMonth) * 100;
                        $monthPrAct      = ($powerActMonth      / $powerTheoMonth) * 100;
                        $monthPrExp      = ($powerExpMonth      / $powerTheoMonth) * 100;
                        $monthPrEGridExt = ($powerEGridExtMonth / $powerTheoMonth) * 100;
                    }
                    if ($anlage->getUsePac() && $powerTheoPac > 0) {
                        $pacPrEvu       = ($powerEvuPac         / $powerTheoPac) * 100;
                        $pacPrAct       = ($powerActPac         / $powerTheoPac) * 100;
                        $pacPrExp       = ($powerExpPac         / $powerTheoPac) * 100;
                        $pacPrEGridExt  = ($powerEGridExtPac    / $powerTheoPac) * 100;
                    }
                    if ($powerTheoYear > 0) {
                        $yearPrEvu          = ($powerEvuYear        / $powerTheoYear) * 100;
                        $yearPrAct          = ($powerActYear        / $powerTheoYear) * 100;
                        $yearPrExp          = ($powerExpYear        / $powerTheoYear) * 100;
                        $yearPrEGridExt     = ($powerEGridExtYear   / $powerTheoYear) * 100;
                    }
                    break;
                default:
                    // wenn es keinen spezielen Algoritmus gibt
                    if ($powerTheo > 0) { // Verhindere Divison by zero
                        $prEvu      = ($powerEvu        / $powerTheo) * 100;
                        $prAct      = ($powerAct        / $powerTheo) * 100;
                        $prExp      = ($powerExp        / $powerTheo) * 100;
                        $prEGridExt = ($powerEGridExt   / $powerTheo) * 100;
                    }
                    if ($powerTheoMonth > 0) {
                        $monthPrEvu      = ($powerEvuMonth      / $powerTheoMonth) * 100;
                        $monthPrAct      = ($powerActMonth      / $powerTheoMonth) * 100;
                        $monthPrExp      = ($powerExpMonth      / $powerTheoMonth) * 100;
                        $monthPrEGridExt = ($powerEGridExtMonth / $powerTheoMonth) * 100;
                    }
                    if ($anlage->getUsePac() && $powerTheoPac > 0) {
                        $pacPrEvu       = ($powerEvuPac         / $powerTheoPac) * 100;
                        $pacPrAct       = ($powerActPac         / $powerTheoPac) * 100;
                        $pacPrExp       = ($powerExpPac         / $powerTheoPac) * 100;
                        $pacPrEGridExt  = ($powerEGridExtPac    / $powerTheoPac) * 100;
                    }
                    if ($powerTheoYear >0 ) {
                        $yearPrEvu          = ($powerEvuYear        / $powerTheoYear) * 100;
                        $yearPrAct          = ($powerActYear        / $powerTheoYear) * 100;
                        $yearPrExp          = ($powerExpYear        / $powerTheoYear) * 100;
                        $yearPrEGridExt     = ($powerEGridExtYear   / $powerTheoYear) * 100;
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
            if($anlage->getUseGridMeterDayData()) {
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
                    ->setstampIst(new DateTime($from));
            }
            // Daten gefunden, aktualisieren der Daten
            $pr ->setPowerAct($powerAct)
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
            $pr ->setIrradiation($irr)
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
            $pr ->setPlantAvailability($availability)
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
                ->setForecastSumAct($forecastArray['sumActual']  )
                ->setForecastDivMinus($forecastArray['divMinus'] )
                ->setForecastDivPlus($forecastArray['divPlus'])
                ->setPrPac(0)
                ->setElectricityGrid(0)
            ;
            $pr ->setIrrMonth($irrMonth)
                ->setIrrPac($irrPac)
                ->setIrrYear($irrYear)
                ->setSpezYield($specYieldMonth)
                ->setCase5perDay($anzCase5PerDay)
            ;
            $pr ->setTheoPowerDefault($powerTheoDefault)
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
            $output = 'Datum ist gleich oder größer aktuelles Datum ('. date('Y-m-d', $timeStamp).')<br>';
        }

        return $output;
    }

    public function calcPR(Anlage $anlage, DateTime $startDate, DateTime $endDate = null, $type = 'day'):array
    {
        $type = strtolower($type); // sicherstellen das type immer in Kleinbuchstaben
        $result = [];

        //Start Zeite je nach gewähltem Typ ermitteln und als für SQL formatiertem String speichern
        switch ($type){
            case 'month':
                // PR für Monat berechnen (ohne Rumpfmonate)
                $localStartDate = $startDate->format('Y-m-d 00:00');
                $localEndDate   = $startDate->format('Y-m-d-23:59');
                break;
            case 'year':
                // PR für das Jahr brechnen (vom 1. Jan bis zum 31.Dez)
                $localStartDate = $startDate->format('Y-01-01 00:00');
                $localEndDate   = $startDate->format('Y-12-31 23:59');
                break;
            case 'pac':
                // PR Berechnen für PAC Datum bis $endDate
                $localStartDate = $anlage->getPacDate()->format('Y-m-d 00:00');
                if ($endDate === null) {
                    $localEndDate   = $startDate->format('Y-m-d-23:59');
                } else {
                    $localEndDate   = $endDate->format('Y-m-d-23:59');
                }
                break;
            default:
                // PR berechnen für einen Tag (wenn $endDate = null) oder für beliebigen Zeitraum (auch für Rumpfmonate in epc Berichten)
                $localStartDate = $startDate->format('Y-m-d 00:00');
                if ($endDate === null) {
                    $localEndDate   = $startDate->format('Y-m-d 23:59');
                } else {
                    $localEndDate   = $endDate->format('Y-m-d 23:59');
                }
        }

        // Wetter Daten ermitteln
        $weather    = $this->weatherFunctions->getWeather($anlage->getWeatherStation(), $localStartDate, $localEndDate);
        // Leistungsdaten ermitteln
        $power      = $this->functions->getSumAcPower($anlage, $localStartDate, $localEndDate);
        $result['powerEvu']         = $power['powerEvu'];
        $result['powerAct']         = $power['powerAct'];
        $result['powerExp']         = $power['powerExp'];
        $result['powerEGridExt']    = $power['powerEGridExt'];
        // Verfügbarkeit ermitteln
        $anzTage = date_diff(date_create($localStartDate), date_create($localEndDate))->days + 1;
        if($anzTage === 0) $anzTage = 1; //verhindert diffision by zero
        $availability = $this->PRRepository->sumAvailabilityByRange($anlage->getAnlId(), $localStartDate, $localEndDate) / $anzTage;

        //Strahlung berechnen – Strahlung (upper = Ost / lower = West)
        if ($anlage->getIsOstWestAnlage()) {
            $irr = ($weather['upperIrr'] * $anlage->getPowerEast() + $weather['lowerIrr'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()) / 1000 / 4;
        } else {
            $irr = $weather['upperIrr'] / 4 / 1000; // Umrechnug zu kWh
        }
        $powerTheo = $anlage->getPower() * $irr;
        $result['powerTheo'] = $powerTheo;
        $tempCorrection = 0;

        // PR berechnung je nach eingestelltem Allgoritmus
        switch ($anlage->getUseCustPRAlgorithm()) {
            case 'Groningen':
                // PowerTheoretical für das Jahr und PAC berechnen unter Berücksichtigung umgerechneten Globalstrahlung
                // Umrechnung Global auf Modulstrahlung erfolgt schon beim Import
                // (Bsp. Groningen: IrrUpper = umgerechnete Globalstrahlung, IrrLower = gemesene Modulstrahlung, IrrHori = gemessene Horizontal Strahlung)

                if ($powerTheo > 0 && $availability > 0) { // Verhinder Divison by zero
                    $result['prEvu']      = ($power['powerEvu']      / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                    $result['prAct']      = ($power['powerAct']      / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                    $result['prExp']      = ($power['powerExp']      / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                    $result['prEGridExt'] = ($power['powerEGridExt'] / ($powerTheo / 1000 * $availability)) * (10 / 0.9945);
                }
                break;
            case 'Veendam':
                if ($availability > 0) { // Verhinder Divison by zero
                    if ($powerTheo > 0 && $availability > 0) {
                        $result['prEvu']      = ($power['powerEvu']      / ($powerTheo / 100 * $availability)) * 100;
                        $result['prAct']      = ($power['powerAct']      / ($powerTheo / 100 * $availability)) * 100;
                        $result['prExp']      = ($power['powerExp']      / ($powerTheo / 100 * $availability)) * 100;
                        $result['prEGridExt'] = ($power['powerEGridExt'] / ($powerTheo / 100 * $availability)) * 100;
                    }
                }
                break;
            case 'Lelystad':
                if ($anlage->getTempCorrCellTypeAvg() != 0) {
                    $powerTheo = $power['powerTheo'];
                    $result['powerTheo'] = $powerTheo;
                }
                if ($powerTheo > 0) { // Verhinder Divison by zero
                    $result['prEvu']      = ($power['powerEvu']      / $powerTheo) * 100;
                    $result['prAct']      = ($power['powerAct']      / $powerTheo) * 100;
                    $result['prExp']      = ($power['powerExp']      / $powerTheo) * 100;
                    $result['prEGridExt'] = ($power['powerEGridExt'] / $powerTheo) * 100;
                }
                break;
            default:
                // wenn es keinen spezielen Algorithmus gibt
                if ($powerTheo > 0) { // Verhindere Divison by zero
                    $result['prEvu']      = ($power['powerEvu']      / $powerTheo) * 100;
                    $result['prAct']      = ($power['powerAct']      / $powerTheo) * 100;
                    $result['prExp']      = ($power['powerExp']      / $powerTheo) * 100;
                    $result['prEGridExt'] = ($power['powerEGridExt'] / $powerTheo) * 100;
                }
        }
        if ($powerTheo > 0) { // Verhindere Divison by zero
            $result['prDefaultEvu']      = ($power['powerEvu']      / $powerTheo) * 100;
            $result['prDefaultAct']      = ($power['powerAct']      / $powerTheo) * 100;
            $result['prDefaultExp']      = ($power['powerExp']      / $powerTheo) * 100;
            $result['prDefaultEGridExt'] = ($power['powerEGridExt'] / $powerTheo) * 100;
        }

        $result['algorithmus']       = $anlage->getUseCustPRAlgorithm();
        $result['powerTheoTempCorr'] = $power['powerTheo'];
        $result['tempCorrection']    = $tempCorrection;
        $result['irradiation']       = $irr;
        $result['availability']      = $availability;

        return $result;
    }


}
