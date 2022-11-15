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
use App\Repository\TicketDateRepository;
use App\Repository\TicketRepository;
use App\Repository\TimesConfigRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PDO;

class AvailabilityByTicketService
{
    use G4NTrait;

    public function __construct(
        private EntityManagerInterface $em,
        private AnlageAvailabilityRepository $availabilityRepository,
        private Case5Repository $case5Repository,
        private Case6Repository $case6Repository,
        private TimesConfigRepository $timesConfigRepo,
        private FunctionsService $functions,
        private AnlagenRepository $anlagenRepository,
        private TicketRepository $ticketRepo,
        private TicketDateRepository $ticketDateRepo,
        private AvailabilityService $availabilityService
    )
    {}

    /**
     * @param Anlage|int $anlage
     * @param $date
     * @param int $department (for wich department (0 = Technische PA, 1 = O&M, 2 = EPC, 3 = AM)
     * @return string
     * @throws \Exception
     */
    public function checkAvailability(Anlage|int $anlage, string|DateTime $date, int $department = 0): string
    {
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlage]);
        }
        if (is_string($date)) {
            $date = date_create($date);
        }

        // Suche pasende Zeitkonfiguration für diese Anlage und dieses Datum
        /* @var TimesConfig $timesConfig */
        $timesConfig = match ($department) {
            1 => $this->timesConfigRepo->findValidConfig($anlage, 'availability_1', $date),
            2 => $this->timesConfigRepo->findValidConfig($anlage, 'availability_2', $date),
            3 => $this->timesConfigRepo->findValidConfig($anlage, 'availability_3', $date),
            default => $this->timesConfigRepo->findValidConfig($anlage, 'fallback', $date),
        };

        $timestampModulo = $date->format('Y-m-d 04:00');

        $from = $timestampModulo;
        $dayStamp = new DateTime($from);

        $inverterPowerDc = [];
        $output = '';

        /* Verfügbarkeit der Anlage ermitteln */
        if (isset($anlage)) {
            $output .= 'Anlage: '.$anlage->getAnlId().' / '.$anlage->getAnlName().' - '.$date->format('Y-m-d')."Department: $department<br>";

            // Verfügbarkeit Berechnen und in Hilfsarray speichern
            $availabilitysHelper = $this->checkAvailabilityInverter($anlage, $date->getTimestamp(), $timesConfig, $department);

            // Pnom für Inverter laden
            $inverterPowerDc = $anlage->getPnomInverterArray();

            // Speichern der ermittelten Werte
            foreach ($availabilitysHelper as $inverter => $availability) {
                // Berechnung der prozentualen Verfügbarkeit Part 1 und Part 2
                if ($availability['control'] - $availability['case4'] != 0) {
                    $invAPart1 = $this->calcInvAPart1($availability);
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
        }

        return $output;
    }

    /**
     * CASE 0 = Datenlücke wenn nicht von CASE 5 abgefangen <b>(per devinition ist der Inverter bei Datenlücke verfügbar, kann durch CASE 6 korrigiert werden.)</b><br>
     * CASE 1 = wenn Gmod > 0 && Gmod < 50<br>
     * CASE 2 = wenn Gmod >= 50 && PowerAc inverter > 0<br>
     * CASE 3 = wenn Gmod >= 50 && PowerAc inverter <= 0<br>
     * CASE 4 = wenn Gmod >= 50 && PowerAc inverter > 0 && cosPhi = 0<br>
     * CASE 5 = Manuel, durch Operator herausgenommen (z.B.: wegen Wartung)<br>
     * CASE 6 = Manuel, durch Operator korriegierte Datenlücke (Datenlücke ist Ausfall des Inverters) <br>
     * CONTROL = wenn Gmod > 0<br>.
     *
     * @param Anlage $anlage
     * @param $timestampModulo
     * @param TimesConfig $timesConfig
     * @param int $department
     * @return array
     */
    public function checkAvailabilityInverter(Anlage $anlage, $timestampModulo, TimesConfig $timesConfig, int $department = 0): array
    {
        $case3Helper = [];
        $availability = [];
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


        $from = date('Y-m-d '.$timesConfig->getStartTime()->format('H:i'), $timestampModulo);
        $to = date('Y-m-d '.$timesConfig->getEndTime()->format('H:i'), $timestampModulo);
        $maxFailTime = $timesConfig->getMaxFailTime();

        // get plant data and irradiation data
        $istData = $this->getIstData($anlage, $from, $to);
        $einstrahlungen = $this->getIrrData($anlage, $from, $to);

        // Aus IstDaten und IstStrahlungsdaten die Tages-Verfügbarkeit je Inverter berechnen
        if (count($einstrahlungen) > 0) {
            $anzInverter = $anlage->getAnzInverter();
            $case5Array = $case6Array = [];

            // suche Case 5 Fälle und schreibe diese in case5Array[inverter][stamp] = true|false
            foreach ($this->case5Repository->findAllCase5($anlage, $from, $to) as $case) {
                $c5From = strtotime($case['stampFrom']);
                $c5To = strtotime($case['stampTo']);
                for ($c5Stamp = $c5From; $c5Stamp <= $c5To; $c5Stamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    // foreach (explode(',', $case['inverter'], 999) as $inverter) {
                    foreach ($this->functions->readInverters($case['inverter'], $anlage) as $inverter) {
                        $inverter = trim($inverter, ' ');
                        $case5Array[$inverter][date('Y-m-d H:i:00', $c5Stamp)] = true;
                    }
                }
            }

            // Handele case5 by ticket
            /** @var TicketDate $case5Ticket */
            foreach ($this->ticketDateRepo->findDataGapOutage($anlage, $from, $to) as $case5Ticket){

                $c5From = $case5Ticket->getBegin()->getTimestamp();
                $c5To = $case5Ticket->getEnd()->getTimestamp();
                for ($c5Stamp = $c5From; $c5Stamp < $c5To; $c5Stamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    foreach ($this->functions->readInverters($case5Ticket->getInverter(), $anlage) as $inverter) {
                        $inverter = trim($inverter, ' ');
                        $case5Array[$inverter][date('Y-m-d H:i:00', $c5Stamp)] = true;
                    }
                }
            }

            // suche Case 6 Fälle und schreibe diese in case6Array[inverter][stamp] = true|false
            foreach ($this->case6Repository->findAllCase6($anlage, $from, $to) as $case) {
                $c6From = strtotime($case['stampFrom']);
                $c6To = strtotime($case['stampTo']);
                for ($c6Stamp = $c6From; $c6Stamp < $c6To; $c6Stamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    foreach ($this->functions->readInverters($case['inverter'], $anlage) as $inverter) {
                        $inverter = trim($inverter, ' ');
                        $case6Array[$inverter][date('Y-m-d H:i:00', $c6Stamp)] = true;
                    }
                }
            }

            // Handel case6 by ticket
            /** @var TicketDate $case6Ticket */
            foreach ($this->ticketDateRepo->findDataGapOutage($anlage, $from, $to) as $case6Ticket){

                $c6From = $case6Ticket->getBegin()->getTimestamp();
                $c6To = $case6Ticket->getEnd()->getTimestamp();
                for ($c6Stamp = $c6From; $c6Stamp < $c6To; $c6Stamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    foreach ($this->functions->readInverters($case6Ticket->getInverter(), $anlage) as $inverter) {
                        $inverter = trim($inverter, ' ');
                        $case6Array[$inverter][date('Y-m-d H:i:00', $c6Stamp)] = true;
                    }
                }

            }


            foreach ($einstrahlungen as $einstrahlung) {
                $stamp = $einstrahlung['stamp'];
                $strahlung = $einstrahlung['irr'];
                $startInverter = 1;

                for ($inverter = $startInverter; $inverter <= $anzInverter; ++$inverter) {
                    // Nur beim ersten durchlauf, Werte setzen, damit nicht 'undifined'
                    if (!isset($availability[$inverter]['case0'])) {
                        $availability[$inverter]['case0'] = 0;
                    }
                    if (!isset($availability[$inverter]['case1'])) {
                        $availability[$inverter]['case1'] = 0;
                    }
                    if (!isset($availability[$inverter]['case2'])) {
                        $availability[$inverter]['case2'] = 0;
                    }
                    if (!isset($availability[$inverter]['case3'])) {
                        $availability[$inverter]['case3'] = 0;
                        $case3Helper[$inverter] = 0;
                    }
                    if (!isset($availability[$inverter]['case4'])) {
                        $availability[$inverter]['case4'] = 0;
                    }
                    if (!isset($availability[$inverter]['case5'])) {
                        $availability[$inverter]['case5'] = 0;
                    }
                    if (!isset($availability[$inverter]['case6'])) {
                        $availability[$inverter]['case6'] = 0;
                    }
                    if (!isset($availability[$inverter]['control'])) {
                        $availability[$inverter]['control'] = 0;
                    }

                    isset($istData[$stamp][$inverter]['power_ac']) ? $powerAc = (float) $istData[$stamp][$inverter]['power_ac'] : $powerAc = null;
                    isset($istData[$stamp][$inverter]['cos_phi']) ? $cosPhi = $istData[$stamp][$inverter]['cos_phi'] : $cosPhi = null;

                    // Wenn Strahlung keine Datenlücke hat dann:
                    if ($strahlung !== null) {
                        $case0 = $case1 = $case2 = $case3 = $case4 = false;
                        // Schaue in case5Array nach, ob ein Eintrag für diesen Inverter und diesen Timestamp vorhanden ist
                        (($strahlung > $threshold1PA) && isset($case5Array[$inverter][$stamp])) ? $case5 = true : $case5 = false;
                        (($strahlung > $threshold1PA) && isset($case6Array[$inverter][$stamp])) ? $case6 = true : $case6 = false;

                        // Case 0 (Datenlücken Inverter Daten | keine Datenlücken für Strahlung)
                        if ($powerAc === null && $case5 === false && $strahlung > $threshold1PA) { // Nur Hochzählen, wenn Datenlücke nicht durch Case 5 abgefangen
                            $case0 = true;
                            ++$availability[$inverter]['case0'];
                        }
                        // Case 1 (first part of ti)
                        if ($strahlung > $threshold1PA && $strahlung <= $threshold2PA && $case5 === false) {
                            $case1 = true;
                            ++$availability[$inverter]['case1'];
                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                        }
                        // Case 2 (second part of ti - means case1 + case2 = ti)
                        if ($strahlung > $threshold2PA && ($powerAc > 0 || $powerAc === null) && $case5 === false && $case6 === false) {
                            $case2 = true;
                            ++$availability[$inverter]['case2'];

                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                        }
                        // Case 3
                        if ($strahlung > $threshold2PA && ($powerAc <= 0 && $powerAc !== null)) {
                            $case3 = true;
                            ++$availability[$inverter]['case3'];
                            $case3Helper[$inverter] += 15;
                        }
                        // Case 4
                        if ($strahlung > $threshold2PA && $powerAc !== null && $cosPhi === 0 && $case5 === false) {
                            $case4 = true;
                            ++$availability[$inverter]['case4'];
                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                        }
                        // Case 5
                        if ($case5 === true) {
                            ++$availability[$inverter]['case5'];
                        }
                        // Case 6
                        if ($case6 === true && $case3 === false && $case0 === true) {
                            ++$availability[$inverter]['case6'];
                        }
                        // Control ti,theo
                        if ($strahlung > $threshold1PA) {
                            ++$availability[$inverter]['control'];
                        }
                    }
                }
            }
        }
        unset($resultEinstrahlung);
        $conn = null;

        return $availability;
    }


    private function getIstData(Anlage $anlage, $from, $to): array
    {
        $conn = self::getPdoConnection();
        $istData = [];
        $dbNameIst = $anlage->getDbNameIst();
        // $sql = "SELECT a.stamp as stamp, wr_cos_phi_korrektur as cos_phi, b.unit as inverter, b.wr_pac as power_ac FROM (db_dummysoll a left JOIN $dbNameIst b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' AND '$to' ORDER BY a.stamp, b.unit";
        $sql = "SELECT stamp, wr_cos_phi_korrektur as cos_phi, unit as inverter, wr_pac as power_ac FROM $dbNameIst WHERE stamp BETWEEN '$from' AND '$to' ORDER BY stamp, unit";

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
    }

    private function getIrrData(Anlage $anlage, $from, $to): array
    {
        $conn = self::getPdoConnection();
        $irrData = [];
        $sql_einstrahlung = 'SELECT a.stamp, b.g_lower, b.g_upper, b.wind_speed FROM (db_dummysoll a left JOIN '.$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' AND '$to'";
        $resultEinstrahlung = $conn->query($sql_einstrahlung);

        if ($resultEinstrahlung->rowCount() > 0) {
            while ($row = $resultEinstrahlung->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $row['stamp'];
                if ($anlage->getIsOstWestAnlage()) {
                    $strahlung = self::mittelwert([$row['g_upper'], $row['g_lower']]);
                } else {
                    if ($anlage->getUseLowerIrrForExpected()) {
                        $strahlung = $row['g_lower'];
                    } else {
                        $strahlung = $row['g_upper'];
                    }
                }
                $irrData[$stamp]['stamp'] = $stamp;
                $irrData[$stamp]['irr'] = $strahlung;
            }
        }
        unset($result);
        $conn = null;

        return $irrData;
    }

    private function calcInvAPart1(array $row): float
    {
        return (($row['case1'] + $row['case2'] + $row['case5']) / $row['control']) * 100;
    }
}
