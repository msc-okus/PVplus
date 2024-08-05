<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlageAvailability;
use App\Entity\TicketDate;
use App\Entity\TimesConfig;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use App\Repository\Case5Repository;
use App\Repository\Case6Repository;
use App\Repository\ReplaceValuesTicketRepository;
use App\Repository\TicketDateRepository;
use App\Repository\TicketRepository;
use App\Repository\TimesConfigRepository;
use App\Service\Functions\IrradiationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use PDO;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

class AvailabilityByTicketService
{
    use G4NTrait;

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly EntityManagerInterface $em,
        private readonly AnlageAvailabilityRepository $availabilityRepository,
        private readonly Case5Repository $case5Repository,
        private readonly Case6Repository $case6Repository,
        private readonly TimesConfigRepository $timesConfigRepo,
        private readonly FunctionsService $functions,
        private readonly AnlagenRepository $anlagenRepository,
        private readonly TicketRepository $ticketRepo,
        private readonly TicketDateRepository $ticketDateRepo,
        private readonly WeatherFunctionsService $weatherFunctionsService,
        private readonly WeatherServiceNew $weatherService,
        private readonly ReplaceValuesTicketRepository $replaceValuesTicketRepo,
        private readonly IrradiationService $irradiationService,
        private readonly CacheInterface $cache
    )
    {}

    /**
     * Calculate the availability cases depending on tickets and settings in plant
     * Stores for every day a record with the case values, this are the base to generate the PA
     *
     * @param Anlage|int $anlage
     * @param string|DateTime $date
     * @param int $department (for wich department (0 = Technische PA, 1 = O&M, 2 = EPC, 3 = AM)
     * @return string
     * @throws \Exception|InvalidArgumentException
     */
    public function checkAvailability(Anlage|int $anlage, string|DateTime $date, int $department = 0): string
    {
        // If $anlage is integer, search for Plant
        if (is_int($anlage)) {$anlage = $this->anlagenRepository->findOneByIdAndJoin($anlage);}
        // If $date is a string, create a DateTime Object
        if (! $date instanceof DateTime) {$date = date_create($date);}

        // Suche pasende Zeitkonfiguration für diese Anlage und dieses Datum
        /* @var TimesConfig $timesConfig */
        $timesConfig = match ($department) {
            1 => $this->timesConfigRepo->findValidConfig($anlage, 'availability_1', $date),
            2 => $this->timesConfigRepo->findValidConfig($anlage, 'availability_2', $date),
            3 => $this->timesConfigRepo->findValidConfig($anlage, 'availability_3', $date),
            default => $this->timesConfigRepo->findValidConfig($anlage, 'availability_0', $date),
        };

        $dayStamp = new DateTime($date->format('Y-m-d 04:00'));

        $inverterPowerDc = [];
        $output = '';

        $doCalc = match ($department) {
            1 => isset($anlage) && $anlage->getEigner()->getFeatures()->isAktDep1() && $anlage->getSettings()->getEnablePADep1(),
            2 => isset($anlage) && $anlage->getEigner()->getFeatures()->isAktDep2() && $anlage->getSettings()->getEnablePADep2(),
            3 => isset($anlage) && $anlage->getEigner()->getFeatures()->isAktDep3() && $anlage->getSettings()->getEnablePADep3(),
            default => isset($anlage),
        };

        /* Verfügbarkeit der Anlage ermitteln */
        if ($doCalc) {
            $output .= 'Anlage: '.$anlage->getAnlId().' / '.$anlage->getAnlName().' ; '.$date->format('Y-m-d')." ; Department: $department ; ";

            // Pnom für Inverter laden
            $inverterPowerDc = $anlage->getPnomInverterArray();

            // Verfügbarkeit Berechnen und in Hilfsarray speichern
            $availabilitysReturnArray = $this->checkAvailabilityInverter($anlage, $date->getTimestamp(), $timesConfig, $inverterPowerDc, $department);

            $availabilitysHelper = $availabilitysReturnArray['availability'];
            $availabilityByStamp = $availabilitysReturnArray['availabilityByStamp'];

            // Speichern der ermittelten Werte
            foreach ($availabilitysHelper as $inverter => $availability) {
                // Berechnung der prozentualen Verfügbarkeit Part 1 und Part 2
                if ($availability['control'] - $availability['case4'] != 0) {
                    $invAPart1 = $this->calcInvAPart1($anlage, $availability, $department);
                    ($anlage->getPnom() > 0 && $inverterPowerDc[$inverter] > 0) ? $invAPart2 = $inverterPowerDc[$inverter] / $anlage->getPnom() : $invAPart2 = 1;
                } else {
                    $invAPart1 = 0;
                    $invAPart2 = 0;
                }

                // Datensatz Availability suchen
                $anlagenAvailability = $this->availabilityRepository->findOneBy(['stamp' => $dayStamp, 'inverter' => $inverter, 'anlage' => $anlage]);

                // Datensatz anlegen wenn nicht schon vorhanden
                if (!$anlagenAvailability) { // Wenn Daten nicht gefunden lege neu an
                    $anlagenAvailability = new AnlageAvailability();
                    $anlagenAvailability->setAnlage($anlage);
                    $anlagenAvailability->setInverter($inverter);
                    $anlagenAvailability->setStamp($dayStamp);
                }

                switch ($department) {
                    case 1:
                        // PA Department 1
                        $anlagenAvailability->setCase01($availability['case0']);
                        $anlagenAvailability->setCase11($availability['case1']);
                        $anlagenAvailability->setCase21($availability['case2']);
                        $anlagenAvailability->setCase31($availability['case3']);
                        $anlagenAvailability->setCase41($availability['case4']);
                        $anlagenAvailability->setCase51($availability['case5']);
                        $anlagenAvailability->setCase61($availability['case6']);
                        $anlagenAvailability->setControl1($availability['control']);
                        $anlagenAvailability->setInvAPart11($invAPart1);
                        $anlagenAvailability->setInvAPart21($invAPart2);
                        $anlagenAvailability->setInvA1($invAPart1 * $invAPart2);
                        $anlagenAvailability->setRemarks1('');
                        break;
                    case 2:
                        // PA Department 2
                        $anlagenAvailability->setCase02($availability['case0']);
                        $anlagenAvailability->setCase12($availability['case1']);
                        $anlagenAvailability->setCase22($availability['case2']);
                        $anlagenAvailability->setCase32($availability['case3']);
                        $anlagenAvailability->setCase42($availability['case4']);
                        $anlagenAvailability->setCase52($availability['case5']);
                        $anlagenAvailability->setCase62($availability['case6']);
                        $anlagenAvailability->setControl2($availability['control']);
                        $anlagenAvailability->setInvAPart12($invAPart1);
                        $anlagenAvailability->setInvAPart22($invAPart2);
                        $anlagenAvailability->setInvA2($invAPart1 * $invAPart2);
                        $anlagenAvailability->setRemarks2('');
                        break;
                    case 3:
                        // PA Department 3
                        $anlagenAvailability->setcase03($availability['case0']);
                        $anlagenAvailability->setCase13($availability['case1']);
                        $anlagenAvailability->setCase23($availability['case2']);
                        $anlagenAvailability->setCase33($availability['case3']);
                        $anlagenAvailability->setCase43($availability['case4']);
                        $anlagenAvailability->setCase53($availability['case5']);
                        $anlagenAvailability->setCase63($availability['case6']);
                        $anlagenAvailability->setControl3($availability['control']);
                        $anlagenAvailability->setInvAPart13($invAPart1);
                        $anlagenAvailability->setInvAPart23($invAPart2);
                        $anlagenAvailability->setInvA3($invAPart1 * $invAPart2);
                        $anlagenAvailability->setRemarks3('');
                        break;
                    default:
                        // PA (Standard Berechneung)
                        $anlagenAvailability->setcase00($availability['case0']);
                        $anlagenAvailability->setCase10($availability['case1']);
                        $anlagenAvailability->setCase20($availability['case2']);
                        $anlagenAvailability->setCase30($availability['case3']);
                        $anlagenAvailability->setCase40($availability['case4']);
                        $anlagenAvailability->setCase50($availability['case5']);
                        $anlagenAvailability->setCase60($availability['case6']);
                        $anlagenAvailability->setControl0($availability['control']);
                        $anlagenAvailability->setInvAPart10($invAPart1);
                        $anlagenAvailability->setInvAPart20($invAPart2);
                        $anlagenAvailability->setInvA0($invAPart1 * $invAPart2);
                        $anlagenAvailability->setRemarks0('');
                        break;
                }
                $this->em->persist($anlagenAvailability);
            }
            $this->em->flush();

            // Store results to Weather Database (VirtualValues !!)
            $conn = $this->pdoService->getPdoPlant();
            $sqlPa = match ($department) {
                1 => "pa1",
                2 => "pa2",
                3 => "pa3",
                default => "pa0"
            };

            if ($availabilityByStamp) {
                $sql = "";
                foreach ($availabilityByStamp as $stamp => $availability){
                    $sql .= "UPDATE ".$anlage->getDbNameWeather()." SET $sqlPa = '$availability' WHERE stamp = '$stamp';";
                }
                $conn->exec($sql);
            }
            $conn = null;

        }

        return $output;
    }

    /**
     * CASE 0 = Datenlücke <b>(die Definition ob der Inverter bei Datenlücke verfügbar ist kann im BE konfiguriert werden.)</b><br>
     * CASE 1 = wenn Gmod > threshold1 && Gmod < threshold2 (Wert aus Anlagenkonfiguration)<br>
     * CASE 2 = wenn Gmod >= threshold2 && PowerAc inverter > 0<br>
     * CASE 3 = wenn Gmod >= threshold2 && PowerAc inverter <= 0<br>
     * CASE 4 = wenn Gmod >= threshold2 && PowerAc inverter > 0 && cosPhi = 0<br>
     * CASE 5 = Manuel, durch Operator herausgenommen (z.B.: wegen Wartung)<br>
     * CASE 6 = Manuel, durch Operator korriegierte Datenlücke (Datenlücke ist Ausfall des Inverters) <br>
     * CONTROL = wenn Gmod > threshold1<br>.
     *
     * @param Anlage $anlage
     * @param $timestampDay
     * @param TimesConfig $timesConfig
     * @param array $inverterPowerDc
     * @param int $department
     * @return array
     * @throws InvalidArgumentException
     * @throws \JsonException
     * @throws \Exception
     */
    public function checkAvailabilityInverter(Anlage $anlage, $timestampDay, TimesConfig $timesConfig, array $inverterPowerDc, int $department = 0): array
    {
        $case3Helper = $availability = $availabilityByStamp = [];
        switch ($department){
            case 1:
                $threshold1PA = $anlage->getThreshold1PA1();
                $threshold2PA = $anlage->getThreshold2PA1();
                break;
            case 2:
                $threshold1PA = $anlage->getThreshold1PA2();
                $threshold2PA = $anlage->getThreshold2PA2();
                break;
            case 3 :
                $threshold1PA = $anlage->getThreshold1PA3();
                $threshold2PA = $anlage->getThreshold2PA3();
                break;
            default:
                $threshold1PA = $anlage->getThreshold1PA0();
                $threshold2PA = $anlage->getThreshold2PA0();
        }

        $from   = date('Y-m-d 00:15', $timestampDay);
        $to     = date('Y-m-d 00:00', $timestampDay + (3600 * 25)); // +25 (stunden) um sicher auf einen Time stamp des nächsten Tages zu kommen, auch wenn Umstellung auf Winterzeit

        $sunArray = $this->weatherService->getSunrise($anlage, $from);
        dump($sunArray);

        #$maxFailTime = $timesConfig->getMaxFailTime();
        $powerThersholdkWh = $anlage->getPowerThreshold() / 4; // Umrechnung von kW auf kWh bei 15 Minuten Werten

        // get plant data and irradiation data
        $istData = $this->getIstData($anlage, $from, $to);
        $einstrahlungen = $this->irradiationService->getIrrData($anlage, $from, $to);

        // Aus IST Produktionsdaten und IST Strahlungsdaten und Tickets die Tages-Verfügbarkeit je Inverter berechnen
        if (count($einstrahlungen) > 0) {
            $anzInverter = $anlage->getAnzInverter();
            $case5Array = $case6Array = $commIssuArray = $skipTiAndTitheoArray = $skipTiOnlyArray = [];

            // suche commIssu Tickets und schreibe diese in Array $commIssuArray[inverter][stamp] = true|false
            $commIssus = $this->ticketDateRepo->findCommIssu($anlage, $from, $to, $department);

            /** @var TicketDate $commIssu */
            foreach ($commIssus as $commIssu) {
                $commIssuFrom = $commIssu->getBegin()->getTimestamp();
                $commIssuTo = $commIssu->getEnd()->getTimestamp();
                $inverters = $this->functions->readInverters($commIssu->getInverter(), $anlage);

                for ($commIssuStamp = $commIssuFrom; $commIssuStamp < $commIssuTo; $commIssuStamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    foreach ($inverters as $inverter) {
                        $inverter = trim((string) $inverter, ' ');
                        $commIssuArray[$inverter][date('Y-m-d H:i:00', $commIssuStamp)] = true;
                    }
                }
            }
            unset($commIssus);

            // suche Performance Tickets die die PA beeinflussen (alertType = 72)
            $perfTicketsSkips  = $this->ticketDateRepo->findPerformanceTicketWithPA($anlage, $from, $to, $department, 0); // behaviour = Skip for PA and Replace outage with TiFM for PA
            /** @var TicketDate $perfTicketsSkip */
            foreach ($perfTicketsSkips as $perfTicketsSkip){
                $skipFrom = $perfTicketsSkip->getBegin()->getTimestamp();
                $skipTo = $perfTicketsSkip->getEnd()->getTimestamp();
                $inverters = $this->functions->readInverters($perfTicketsSkip->getInverter(), $anlage);
                for ($skipStamp = $skipFrom; $skipStamp < $skipTo; $skipStamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    foreach ($inverters as $inverter) {
                        $inverter = trim((string) $inverter, ' ');
                        $skipTiAndTitheoArray[$inverter][date('Y-m-d H:i:00', $skipStamp)] = false;
                        $skipTiOnlyArray[$inverter][date('Y-m-d H:i:00', $skipStamp)] = false;
                        if ($perfTicketsSkip->getPRExcludeMethod() == 10) { // Skip for PA
                            $skipTiAndTitheoArray[$inverter][date('Y-m-d H:i:00', $skipStamp)] = true;
                        }
                        if ($perfTicketsSkip->getPRExcludeMethod() == 20) { // Replace outage with TiFM for PA
                            $skipTiOnlyArray[$inverter][date('Y-m-d H:i:00', $skipStamp)] = true;
                        }
                    }
                }
            }
            unset($perfTicketsSkips);

            // suche Case 5 Fälle und schreibe diese in case5Array[inverter][stamp] = true|false
            // sollte so bald wie möglich entfallen, da 'Case5' durch Ticketsystem ersetzt wird (gibt auch keine erfassung für 'Case5' mehr)
            /*
            foreach ($this->case5Repository->findAllCase5($anlage, $from, $to) as $case) {
                $c5From = strtotime((string) $case['stampFrom']);
                $c5To = strtotime((string) $case['stampTo']);
                $inverters = $this->functions->readInverters($case['inverter'], $anlage);
                for ($c5Stamp = $c5From; $c5Stamp <= $c5To; $c5Stamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    foreach ($inverters as $inverter) {
                        $inverter = trim((string) $inverter, ' ');
                        $case5Array[$inverter][date('Y-m-d H:i:00', $c5Stamp)] = true;
                    }
                }
            }
            */


            // Handele case5 by ticket
            $case5Tickets = $this->ticketDateRepo->findTiFm($anlage, $from, $to, $department);
            /** @var TicketDate $case5Ticket */
            foreach ($case5Tickets as $case5Ticket){
                $case5From = $case5Ticket->getBegin()->getTimestamp();
                $case5To = $case5Ticket->getEnd()->getTimestamp();
                $inverters = $this->functions->readInverters($case5Ticket->getInverter(), $anlage);
                for ($case5Stamp = $case5From; $case5Stamp < $case5To; $case5Stamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    foreach ($inverters as $inverter) {
                        $inverter = trim((string) $inverter, ' ');
                        $case5Array[$inverter][date('Y-m-d H:i:00', $case5Stamp)] = true;
                    }
                }
            }
            unset($case5Tickets);
            unset($perfTicketsCase5);

            // suche Case 6 Fälle und schreibe diese in case6Array[inverter][stamp] = true|false
            // sollte so bald wie möglich entfallen, da 'Case6' durch Ticketsystem ersetzt wird ('Case6' war nur eine Hilfslösung)
            /*
            foreach ($this->case6Repository->findAllCase6($anlage, $from, $to) as $case) {
                $c6From = strtotime((string) $case['stampFrom']);
                $c6To = strtotime((string) $case['stampTo']);
                $inverters = $this->functions->readInverters($case['inverter'], $anlage);
                for ($c6Stamp = $c6From; $c6Stamp < $c6To; $c6Stamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    foreach ($inverters as $inverter) {
                        $inverter = trim((string) $inverter, ' ');
                        $case6Array[$inverter][date('Y-m-d H:i:00', $c6Stamp)] = true;
                    }
                }
            }
            */

            // Handel case6 by ticket
            /** @var TicketDate $case6Ticket */
            $case6Tickets = $this->ticketDateRepo->findDataGapOutage($anlage, $from, $to, $department);
            foreach ($case6Tickets as $case6Ticket){
                $case6From = $case6Ticket->getBegin()->getTimestamp();
                $case6To = $case6Ticket->getEnd()->getTimestamp();
                for ($case6Stamp = $case6From; $case6Stamp < $case6To; $case6Stamp += 900) { // 900 = 15 Minuten in Sekunden | $c6Stamp < $c6To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    foreach ($this->functions->readInverters($case6Ticket->getInverter(), $anlage) as $inverter) {
                        $inverter = trim((string) $inverter, ' ');
                        $case6Array[$inverter][date('Y-m-d H:i:00', $case6Stamp)] = true;
                    }
                }
            }
            unset($case6Tickets);

            $inverterPowerDc = $anlage->getPnomInverterArray();  // Pnom for every inverter
            $theoPowerByPA = 0;
            foreach ($einstrahlungen as $einstrahlung) {
                $stamp = $einstrahlung['stamp'];
                $strahlung = $einstrahlung['irr'];
                $irrFlag = $einstrahlung['irr_flag'];

                if ($sunArray['sunrise'] > $stamp && $sunArray['sunset'] < $stamp) {
                    // Wenn die Sonne aufgegangen ist muss Strahlungswert da sein
                    $conditionIrrCase1 = $strahlung <= $threshold2PA && $strahlung !== null;
                } else {
                    // in der Nacht soll eine darf die Strahlung = null (datagap) sein (Bsp: null <= 50' === true)
                    $conditionIrrCase1 = $strahlung <= $threshold2PA;
                }
                $conditionIrrCase2 = $strahlung > $threshold2PA;

                if (($department === 0 && $anlage->isUsePAFlag0()) || ($department === 1 && $anlage->isUsePAFlag1()) ||
                    ($department === 2 && $anlage->isUsePAFlag2()) || ($department === 3 && $anlage->isUsePAFlag3()))
                {
                    $conditionIrrCase1 = !$irrFlag;
                    $conditionIrrCase2 = $irrFlag;
                }

                $startInverter = 1;
                $availabilityByStamp[$stamp] = 0;
                for ($inverter = $startInverter; $inverter <= $anzInverter; ++$inverter) {
                    // Nur beim ersten durchlauf, Werte setzen, damit nicht 'undefined'
                    $availabilityPlantByStamp['case0'] = $availabilityPlantByStamp['case1'] = $availabilityPlantByStamp['case2'] = $availabilityPlantByStamp['case3'] = 0;
                    $availabilityPlantByStamp['case5'] = $availabilityPlantByStamp['case6'] = $availabilityPlantByStamp['control'] = 0;
                    if (!isset($availability[$inverter]['case0']))      $availability[$inverter]['case0'] = 0;
                    if (!isset($availability[$inverter]['case1']))      $availability[$inverter]['case1'] = 0;
                    if (!isset($availability[$inverter]['case2']))      $availability[$inverter]['case2'] = 0;
                    if (!isset($availability[$inverter]['case3']))      $availability[$inverter]['case3'] = 0;
                    if (!isset($availability[$inverter]['case4']))      $availability[$inverter]['case4'] = 0;
                    if (!isset($availability[$inverter]['case5']))      $availability[$inverter]['case5'] = 0;
                    if (!isset($availability[$inverter]['case6']))      $availability[$inverter]['case6'] = 0;
                    if (!isset($availability[$inverter]['control']))    $availability[$inverter]['control'] = 0;
                    if (!isset($case3Helper[$inverter]))      $case3Helper[$inverter] = 0;

                    $powerAc = isset($istData[$stamp][$inverter]['power_ac']) ? (float) $istData[$stamp][$inverter]['power_ac'] : null;
                    $cosPhi  = isset($istData[$stamp][$inverter]['cos_phi'])  ? (float) $istData[$stamp][$inverter]['cos_phi'] :  null;

                    // Wenn die Strahlung keine Datenlücke hat dann: ?? Brauchen wir das

                    $case0 = $case1 = $case2 = $case3 = $case4 = $case5 = $case6 = false;
                    $commIssu = $commIssuCase5 = $skipTi = $skipTiTheo = $outageAsTiFm = false;

                    if ($strahlung > $threshold1PA || ($strahlung === 0.0 && $threshold1PA < 0) || ($strahlung === null && $threshold1PA < 0)) {//
                        // Schaue in Arrays nach, ob ein Eintrag für diesen Inverter und diesen Timestamp vorhanden ist
                        $case5          = isset($case5Array[$inverter][$stamp]) && !isset($commIssuArray[$inverter][$stamp]);
                        $case6          = isset($case6Array[$inverter][$stamp]);
                        $commIssuCase5  = isset($commIssuArray[$inverter][$stamp])          && !$case5; // ignoriere Communication errors wenn case5 (tiFM) gesetzt ist
                        $commIssu       = isset($commIssuArray[$inverter][$stamp]);
                        $skipTi         = isset($skipTiAndTitheoArray[$inverter][$stamp])   && $skipTiAndTitheoArray[$inverter][$stamp] === true;
                        $skipTiTheo     = isset($skipTiAndTitheoArray[$inverter][$stamp])   && $skipTiAndTitheoArray[$inverter][$stamp] === true;
                        $outageAsTiFm   = isset($skipTiOnlyArray[$inverter][$stamp])        && $skipTiOnlyArray[$inverter][$stamp]      === true; // Replace outage with TiFM for PA

                        // Case 0 (Datenlücken Inverter Daten oder keine Datenlücken für Strahlung)
                        if ($commIssu || $powerAc === null) { // ($powerAc === null && $case5 === false)
                            $case0 = true;
                            ++$availability[$inverter]['case0'];
                            ++$availabilityPlantByStamp['case0'];
                        }
                        // Case 1 (first part of ti)
                        if ($conditionIrrCase1 && $skipTi === false) {
                            $case1 = true;
                            ++$availability[$inverter]['case1'];
                            ++$availabilityPlantByStamp['case1'];
                            /*
                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availabilityPlantByStamp['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                                $availabilityPlantByStamp['case2'] += $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                            */
                        }
                        // Case 2 (second part of ti - means case1 + case2 = ti)
                        #########

                        if ($anlage->getTreatingDataGapsAsOutage() && ($sunArray['sunrise'] > $stamp && $sunArray['sunset'] < $stamp)) {
                            // Data Gap soll als Ausfall gewertet werden, wenn der Zeitstempel innehalb der Aufgegangenen Sonne liegt
                            $hitCase2 = ($conditionIrrCase2 && $commIssu === true && $skipTi === false) ||
                                        ($conditionIrrCase2 && ($powerAc > $powerThersholdkWh) && $case5 === false && $case6 === false && $skipTi === false);
                            // Änderung am 27. Feb 24 '$powerAc > $powerThersholdkWh' ersetzt durch '($powerAc > $powerThersholdkWh || $powerAc === null)' | MRE // && $commIssuCase5 === true)
                        } else {
                            // Data Gap wir NICHT als Ausfall gewertet.
                            dump($stamp);
                            $hitCase2 = ($conditionIrrCase2 && $commIssu === true && $skipTi === false) ||
                                        ($conditionIrrCase2 && ($powerAc > $powerThersholdkWh || $powerAc === null) && $case5 === false && $case6 === false && $skipTi === false);

                        }
                        if ($hitCase2) {
                            $case2 = true;
                            ++$availability[$inverter]['case2'];
                            ++$availabilityPlantByStamp['case2'];
                            /*
                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availabilityPlantByStamp['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                                $availabilityPlantByStamp['case2'] -= $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                            */
                        }
                        // Case 3
                        if ($conditionIrrCase2 && ($powerAc <= $powerThersholdkWh && $powerAc !== null) && !$commIssu) { // ohne case5
                            $case3 = true;
                            ++$availability[$inverter]['case3'];
                            ++$availabilityPlantByStamp['case3'];
                            #$case3Helper[$inverter] += 15;
                        }
                        // Case 4
                        if ($powerAc !== null && $cosPhi === 0 && $case5 === false) {
                            $case4 = true;
                            ++$availability[$inverter]['case4'];
                            ++$availabilityPlantByStamp['case4'];
                            /*
                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availabilityPlantByStamp['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                                $availabilityPlantByStamp['case2'] -= $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                            */
                        }
                        // Case 5 ti,FM
                        if (($conditionIrrCase2 === true && $case5 === true)
                            || ($conditionIrrCase2 === true && $case5 === true && $case3 === true)
                            || ($conditionIrrCase2 === true && $case3 === true && $outageAsTiFm === true)
                            || ($conditionIrrCase2 === true && $case0 === true && $case5 === false && $commIssu === false && $outageAsTiFm === true)) {
                            ++$availability[$inverter]['case5'];
                            ++$availabilityPlantByStamp['case5'];
                        }
                        // Case 6
                        if ($case6 === true && $case3 === false && $case0 === true) { //
                            ++$availability[$inverter]['case6'];
                            ++$availabilityPlantByStamp['case6'];
                        }
                        // Control ti,theo
                        if ($skipTiTheo === false) {
                            ++$availability[$inverter]['control'];
                            ++$availabilityPlantByStamp['control'];
                        }
                    }

                    ## virtual Value for PA speichern (by stamp and plant)
                    $invWeight = ($anlage->getPnom() > 0 && $inverterPowerDc[$inverter] > 0) ? $inverterPowerDc[$inverter] / $anlage->getPnom() : 1;
                    $availabilityByStamp[$stamp] += ($this->calcInvAPart1($anlage, $availabilityPlantByStamp, $department) / 100) * $invWeight;
                }
            }
        }
        unset($resultEinstrahlung);

        $return['availability'] = $availability;
        $return['availabilityByStamp'] = $availabilityByStamp;

        return $return;
    }

    /**
     * Calculate the Availability (PA) for the given plant and the given time range. Base on the folowing formular:<br>
     * ti / ti,(theo - tFM)<br>
     * wobei:<br>
     * ti = case1 + case2<br>
     * ti,theo = control<br>
     * tFM = case5<br>.
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     */
    public function calcAvailability(Anlage|int $anlage, DateTime $from, DateTime $to, ?int $inverter = null, int $department = 0): float
    {
        // If $anlage is integer, search for Plant
        if (is_int($anlage)) $anlage = $this->anlagenRepository->findOneByIdAndJoin($anlage);

        $inverterPowerDc = $anlage->getPnomInverterArray();  // Pnom for every inverter

        $availabilitys = $this->availabilityRepository->getPaByDate($anlage, $from, $to, $inverter, $department);
        $ti = $titheo = $pa = $paSum = $paSingle = $paSingleSum = 0;
        $cases['case0'] = $cases['case1'] = $cases['case2'] = $cases['case3'] = $cases['case4'] = $cases['case5'] = $cases['case6'] = $cases['control'] = 0;
        $currentInverter = null;
        foreach ($availabilitys as $availability) {
            if ($currentInverter != (int)$availability['inverter'] && $currentInverter !== null) {
                // Berechne PA für den aktuellen Inverter
                $invWeight = ($anlage->getPnom() > 0 && $inverterPowerDc[$currentInverter] > 0) ? $inverterPowerDc[$currentInverter] / $anlage->getPnom() : 1;
                $paSingle = $this->calcInvAPart1($anlage, $cases, $department);
                $pa = $paSingle * $invWeight;
                $paSum += $pa;
                $paSingleSum += $paSingle;
                $cases['case0'] = $cases['case1'] = $cases['case2'] = $cases['case3'] = $cases['case4'] = $cases['case5'] = $cases['case6'] = $cases['control'] = 0;
            }
            $currentInverter = $availability['inverter'];
            $cases['case0'] += $availability['case_0'];
            $cases['case1'] += $availability['case_1'];
            $cases['case2'] += $availability['case_2'];
            $cases['case3'] += $availability['case_3'];
            $cases['case4'] += $availability['case_4'];
            $cases['case5'] += $availability['case_5'];
            $cases['case6'] += $availability['case_6'];
            $cases['control'] += $availability['control'];
        }
        // Berechne PA für den letzten Inverter
        $invWeight = ($anlage->getPnom() > 0 && $inverterPowerDc[$currentInverter] > 0) ? $inverterPowerDc[$currentInverter] / $anlage->getPnom() : 1;
        $paSingle = $this->calcInvAPart1($anlage, $cases, $department);
        $pa = $paSingle * $invWeight;
        $paSum += $pa;
        $paSingleSum += $paSingle;

        if ($inverter) {
            return $paSingleSum;
        } else {
            return $paSum;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getIstData(Anlage $anlage, $from, $to): array
    {
        return $this->cache->get('getIstData_'.md5($anlage->getAnlId().$from.$to), function(CacheItemInterface $cacheItem) use ($anlage, $from, $to) {

            $cacheItem->expiresAfter(60); // Lifetime of cache Item

            $conn = $this->pdoService->getPdoPlant();
            $istData = [];
            $dbNameIst = $anlage->getDbNameIst();
            $sql = "SELECT stamp, wr_cos_phi_korrektur as cos_phi, unit as inverter, wr_pac as power_ac FROM $dbNameIst WHERE stamp >= '$from' AND stamp <= '$to' ORDER BY stamp, unit";
            $result = $conn->query($sql);
            if ($result->rowCount() > 0) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = $row['stamp'];
                    $inverter = $row['inverter'];
                    $istData[$stamp][$inverter]['stamp'] = $row['stamp'];
                    $istData[$stamp][$inverter]['cos_phi'] = $row['cos_phi'];
                    $istData[$stamp][$inverter]['power_ac'] = $row['power_ac'];
                }
            }
            unset($result);
            $conn = null;

            return $istData;
        });
    }


    /**
     * Berechnet die PA TEIL 1 (OHNE GEWICHTUNG)
     *
     * <b>Wobei:</b><br>
     * ti = case1 + case 2 <br>
     * titheo = control<br>
     * tiFM = case5 (?? + case6)<br>
     *<br>
     * sollte ti und titheo = 0 sein so wird PA auf 100% definiert<br>
     *
     * @param Anlage $anlage
     * @param array $row
     * @param int $department
     * @return float
     */
    public function calcInvAPart1(Anlage $anlage, array $row, int $department = 0): float
    {
        $paInvPart1 = 0.0;

        // Get needed formular, depending on settings for this department
        $formel = match ($department) {
            1 => $anlage->getPaFormular1(),
            2 => $anlage->getPaFormular2(),
            3 => $anlage->getPaFormular3(),
            default => $anlage->getPaFormular0(),
        };

        // calculate pa depending on the chose formular
        switch ($formel) {
            case '1': // PA = ti / (ti,theo - tiFM)
                if ($row['case1'] + $row['case2'] === 0 && $row['control'] - $row['case5'] === 0) {
                    $paInvPart1 = 100;
                } else {
                    if ($row['control'] - $row['case5'] === 0) {
                        $paInvPart1 = 100;
                    } else {
                        $paInvPart1 = (($row['case1'] + $row['case2']) / ($row['control'] - $row['case5'])) * 100;
                    }
                }
                /*
                 * NOCH nicht löschen, wird event noch benötigt (MR)
                 if ($row['case1'] + $row['case2'] + $row['case5'] != 0 && $row['control'] - $row['case5'] != 0) {
                    if ((int) $row['case1'] + (int) $row['case2'] === 0 && (int) $row['control'] - (int) $row['case5'] === 0) {
                        // Sonderfall wenn Dividend und Divisor = 0 => dann ist PA per definition 100%
                        $paInvPart1 = 100;
                    } else {
                        $paInvPart1 = (($row['case1'] + $row['case2']) / ($row['control'] - $row['case5'])) * 100;
                    }
                } else {
                    $paInvPart1 = 100;
                }
                 */
                break;

            case '2': // PA = ti / ti,theo
                if ($row['case1'] + $row['case2'] != 0 && $row['control'] != 0) {
                    $paInvPart1 = (($row['case1'] + $row['case2']) / $row['control']) * 100;
                }
                break;

            case '3': // PA = (ti + tiFM) / ti,theo
                if ($row['case1'] + $row['case2'] + $row['case5'] != 0 && $row['control'] != 0) {
                    $paInvPart1 = (($row['case1'] + $row['case2'] + $row['case5']) / $row['control']) * 100;
                }
                break;
        }
        return $paInvPart1;
    }
}
