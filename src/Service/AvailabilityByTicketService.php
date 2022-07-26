<?php

namespace App\Service;

use App\Entity\TimesConfig;
use App\Repository\AnlagenRepository;
use App\Repository\Case6Repository;
use App\Repository\TimesConfigRepository;
use DateTime;
use PDO;
use Exception;
use App\Entity\Anlage;
use App\Entity\AnlageAvailability;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\Case5Repository;
use Doctrine\ORM\EntityManagerInterface;

class AvailabilityByTicketService
{
    use G4NTrait;

    private EntityManagerInterface $em;
    private AnlageAvailabilityRepository $availabilityRepository;
    private Case5Repository $case5Repository;
    private Case6Repository $case6Repository;
    private TimesConfig $timesConfig;
    private TimesConfigRepository $timesConfigRepo;
    private FunctionsService $functions;
    private AnlagenRepository $anlagenRepository;

    public function __construct(EntityManagerInterface $em, AnlageAvailabilityRepository $availabilityRepository,
                                Case5Repository $case5Repository, Case6Repository  $case6Repository,
                                TimesConfigRepository $timesConfigRepo, FunctionsService $functions, AnlagenRepository $anlagenRepository)
    {
        $this->em = $em;
        $this->availabilityRepository = $availabilityRepository;
        $this->case5Repository = $case5Repository;
        $this->case6Repository = $case6Repository;
        $this->timesConfigRepo = $timesConfigRepo;
        $this->functions = $functions;
        $this->anlagenRepository = $anlagenRepository;
    }

    /**
     *
     * @param Anlage|int $anlage
     * @param $date
     * @param int $department (for witch department (1 = EPC, 2 = O&M, 3 = AM)
     * @return string
     */
    public function checkAvailability(Anlage|int $anlage, $date, int $department = 1): string
    {
        if (is_int($anlage)) $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlage]);

        // Suche pasende Zeitkonfiguration für diese Anlage und dieses Datum
        /** @var TimesConfig $timesConfig */
        switch ($department) {
            case 2:
                $timesConfig = $this->timesConfigRepo->findValidConfig($anlage, 'availability_second', date_create(date('Y-m-d H:m', $date)));
                break;
            case 3:
                #$timesConfig = $this->timesConfigRepo->findValidConfig($anlage, 'availability_second', date_create(date('Y-m-d H:m', $date)));
                break;
            default :
                $timesConfig = $this->timesConfigRepo->findValidConfig($anlage, 'availability_first', date_create(date('Y-m-d H:m', $date)));
                break;
        }

        $timestampModulo = $date;

        $from = date("Y-m-d 04:00", $timestampModulo);
        $dayStamp = new DateTime($from);

        $inverterPowerDc = [];
        $output = '';

        /**************************************/
        /* Verfügbarkeit der Anlage ermitteln */
        /**************************************/
        if (isset($anlage)) {
            $output .= "Anlage: " . $anlage->getAnlId() . " / " . $anlage->getAnlName() . " - " . date("Y-m-d", $timestampModulo) . "Department: $department<br>";

            // Verfügbarkeit Berechnen und in Hilfsarray speichern
            $availabilitysHelper = $this->checkAvailabilityInverter($anlage, $timestampModulo, $timesConfig, $department);

            // DC Leistung der Inverter laden (aus AC Gruppen)

            /* Todo: OLD code could be removed after testing
            Anpassung der Berechnung der Inverter Pnom je nach Analgen Typ (Berechnen aus DC Gruppen config)
            switch ($anlage->getConfigType()) {
                case 1:
                case 2:
                    foreach ($anlage->getGroups() as $group) {
                        $inverterPowerDc[$group->getDcGroup()] = $group->getPnomPerGroup();
                    }
                    break;
                case 3:
                case 4:
                    foreach ($anlage->getAcGroups() as $acGroup) {
                        $inverterPowerDc[$acGroup->getAcGroup()] = $acGroup->getDcPowerInverter();
                    }
                    break;

            }
            */
            // Pnom für Inverter laden
            $inverterPowerDc = $anlage->getPnomInverterArray();

            // Speichern der ermittelten Werte
            foreach ($availabilitysHelper as $inverter => $availability) {

                // Berechnung der protzentualen Verfügbarkeit Part 1 und Part 2
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
                    #case 1:
                        // PA Department 1
                        #break;
                    case 2:
                        // PA Department 2
                        $anlagenAvailability->setcase0Second($availability['case0']);
                        $anlagenAvailability->setCase1Second($availability['case1']);
                        $anlagenAvailability->setCase2Second($availability['case2']);
                        $anlagenAvailability->setCase3Second($availability['case3']);
                        $anlagenAvailability->setCase4Second($availability['case4']);
                        $anlagenAvailability->setCase5Second($availability['case5']);
                        $anlagenAvailability->setCase6Second($availability['case6']);
                        $anlagenAvailability->setControlSecond($availability['control']);
                        $anlagenAvailability->setInvAPart1Second($invAPart1);
                        $anlagenAvailability->setInvAPart2Second($invAPart2);
                        $anlagenAvailability->setInvASecond($invAPart1 * $invAPart2);
                        $anlagenAvailability->setRemarksSecond("");
                        break;
                    case 3:
                        // PA Department 3
                        break;
                    default:
                        // PA (Standard Berechneung)
                        $anlagenAvailability->setcase0($availability['case0']);
                        $anlagenAvailability->setCase1($availability['case1']);
                        $anlagenAvailability->setCase2($availability['case2']);
                        $anlagenAvailability->setCase3($availability['case3']);
                        $anlagenAvailability->setCase4($availability['case4']);
                        $anlagenAvailability->setCase5($availability['case5']);
                        $anlagenAvailability->setCase6($availability['case6']);
                        $anlagenAvailability->setControl($availability['control']);
                        $anlagenAvailability->setInvAPart1($invAPart1);
                        $anlagenAvailability->setInvAPart2($invAPart2);
                        $anlagenAvailability->setInvA($invAPart1 * $invAPart2);
                        $anlagenAvailability->setRemarks("");
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
     * CASE 6 = Manuel, durch Operator koriegierte Datenlücke (Datenlücke ist Ausfall des Inverters) <br>
     * CONTROL = wenn Gmod > 0<br>
     * @param Anlage $anlage
     * @param $timestampModulo
     * @param TimesConfig $timesConfig
     * @param int $department
     * @return array
     */
    public function checkAvailabilityInverter(Anlage $anlage, $timestampModulo, TimesConfig $timesConfig, int $department = 1):array
    {
        $case3Helper = [];
        $availability = [];
        $threshold1PA = $anlage->getThreshold1PA();
        $threshold2PA = $anlage->getThreshold2PA();

        $from   = date("Y-m-d ".$timesConfig->getStartTime()->format('H:i'), $timestampModulo);
        $to     = date("Y-m-d ".$timesConfig->getEndTime()->format('H:i'), $timestampModulo);
        $maxFailTime = $timesConfig->getMaxFailTime();

        // get plant data and irradiation data
        $istData = $this->getIstData($anlage, $from, $to);
        $einstrahlungen = $this->getIrrData($anlage, $from, $to);

        // Aus IstDaten und IstStrahlungsdaten die Tages-Verfügbarkeit je Inverter berechnen
        if (count($einstrahlungen) > 0) {
            $anzInverter = $anlage->getAnzInverter();
            $case5Array = $case6Array = [];

            // ToDo: Die 'case5' Fälle sollen aus dem Ticket erzeugt werden (FM Fälle – abhänig vom departement)
            // suche Case 5 Fälle und schreibe diese in case5Array[inverter][stamp] = true|false
            foreach ($this->case5Repository->findAllCase5($anlage, $from, $to) as $case) {
                $c5From = strtotime($case['stampFrom']);
                $c5To   = strtotime($case['stampTo']);
                for ($c5Stamp = $c5From; $c5Stamp <= $c5To; $c5Stamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    //foreach (explode(',', $case['inverter'], 999) as $inverter) {
                    foreach ($this->functions->readInverters($case['inverter'], $anlage) as $inverter) {
                        $inverter = trim($inverter, ' ');
                        $case5Array[$inverter][date('Y-m-d H:i:00', $c5Stamp)] = true;
                    }
                }
            }

            // ToDo: Die 'case6' Fälle sollen aus dem Ticket erzeugt werden (is DataGap = outage or comm issue)
            // suche Case 6 Fälle und schreibe diese in case6Array[inverter][stamp] = true|false
            foreach ($this->case6Repository->findAllCase6($anlage, $from, $to) as $case) {
                $c6From = strtotime($case['stampFrom']);
                $c6To   = strtotime($case['stampTo']);
                for ($c6Stamp = $c6From; $c6Stamp < $c6To; $c6Stamp += 900) { // 900 = 15 Minuten in Sekunden | $c5Stamp < $c5To um den letzten Wert nicht abzufragen (Bsp: 10:00 bis 10:15, 10:15 darf NICHT mit eingerechnet werden)
                    foreach (explode(',', $case['inverter'], 999) as $inverter) {
                        $inverter = trim($inverter, ' ');
                        $case6Array[$inverter][date('Y-m-d H:i:00', $c6Stamp)] = true;
                    }
                }
            }

            foreach ($einstrahlungen as $einstrahlung) {
                $stamp      = $einstrahlung['stamp'];
                $strahlung  = $einstrahlung['irr'];
                $startInverter = 1;

                for ($inverter = $startInverter; $inverter <= $anzInverter; $inverter++) {
                    // Nur beim ersten durchlauf, Werte setzen, damit nicht 'undifined'
                    if (!isset($availability[$inverter]['case0'])) $availability[$inverter]['case0'] = 0;
                    if (!isset($availability[$inverter]['case1'])) $availability[$inverter]['case1'] = 0;
                    if (!isset($availability[$inverter]['case2'])) $availability[$inverter]['case2'] = 0;
                    if (!isset($availability[$inverter]['case3'])) {
                        $availability[$inverter]['case3'] = 0;
                        $case3Helper[$inverter] = 0;
                    }
                    if (!isset($availability[$inverter]['case4'])) $availability[$inverter]['case4'] = 0;
                    if (!isset($availability[$inverter]['case5'])) $availability[$inverter]['case5'] = 0;
                    if (!isset($availability[$inverter]['case6'])) $availability[$inverter]['case6'] = 0;
                    if (!isset($availability[$inverter]['control'])) $availability[$inverter]['control'] = 0;


                    isset($istData[$stamp][$inverter]['power_ac']) ? $powerAc = (float)$istData[$stamp][$inverter]['power_ac'] : $powerAc = null;
                    isset($istData[$stamp][$inverter]['cos_phi'])  ? $cosPhi  = $istData[$stamp][$inverter]['cos_phi'] : $cosPhi  = null;

                    // Wenn Strahlung keine Datenlücke hat dann:
                    if ($strahlung !== null) {
                        $case0 = $case1 = $case2 = $case3 = $case4 = false;
                        // Schaue in case5Array nach, ob ein Eintrag für diesen Inverter und diesen Timestamp vorhanden ist
                        (($strahlung > $threshold1PA) && isset($case5Array[$inverter][$stamp])) ? $case5 = true : $case5 = false;
                        (($strahlung > $threshold1PA) && isset($case6Array[$inverter][$stamp])) ? $case6 = true : $case6 = false;

                        // Case 0 (Datenlücken Inverter Daten | keine Datenlücken für Strahlung)
                        if ($powerAc === null && $case5 === false && $strahlung > $threshold1PA) { // Nur Hochzählen, wenn Datenlücke nicht durch Case 5 abgefangen
                            $case0 = true;
                            $availability[$inverter]['case0']++;
                        }
                        // Case 1 (first part of ti)
                        if ($strahlung > $threshold1PA && $strahlung <= $threshold2PA && $case5 === false) {
                            $case1 = true;
                            $availability[$inverter]['case1']++;
                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                        }
                        // Case 2 (second part of ti - means case1 + case2 = ti)
                        if ($strahlung > $threshold2PA && ($powerAc > 0 || $powerAc === null) && $case5 === false && $case6 === false) {
                            $case2 = true;
                            $availability[$inverter]['case2']++;

                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                        }
                        // Case 3
                        if ($strahlung > $threshold2PA && ($powerAc <= 0 && $powerAc !== null) ) {
                            $case3 = true;
                            $availability[$inverter]['case3']++;
                            $case3Helper[$inverter] += 15;
                        }
                        // Case 4
                        if ($strahlung > $threshold2PA && $powerAc !== null && $cosPhi === 0 && $case5 === false) {
                            $case4 = true;
                            $availability[$inverter]['case4']++;
                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                        }
                        // Case 5
                        if ($case5 === true) {
                            $availability[$inverter]['case5']++;
                        }
                        // Case 6
                        if ($case6 === true && $case3 === false && $case0 === true) {
                            $availability[$inverter]['case6']++;
                        }
                        // Control ti,theo
                        if ($strahlung > $threshold1PA) {
                            $availability[$inverter]['control']++;
                        }
                    }
                }
            }
        }
        unset($resultEinstrahlung);
        $conn = null;

        return $availability;
    }

    /**
     * Calculate the Availability (PA) for the given plant and the given time range. Base on the folowing formular:<br>
     * ti / ti,(theo - tFM)<br>
     * wobei:<br>
     * ti = case1 + case2<br>
     * ti,theo = control<br>
     * tFM = case5<br>
     *
     * @param Anlage|int $anlage
     * @param DateTime $from
     * @param DateTime $to
     * @param int|null $inverter
     * @return float
     */
    public function calcAvailability(Anlage|int $anlage, DateTime $from, DateTime $to, ?int $inverter = null): float
    {
        if (is_int($anlage)) $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlage]);

        $sumPart1 = $sumPart2 = $pa = 0;
        $inverterPowerDc = [];

        // START: calulate pNom for each inverter
        if ($inverter === null) {
            // ToDo: muss auf Anlagen Typ angepasst werden
            if ($anlage->getUseNewDcSchema()) {
                foreach ($anlage->getAcGroups() as $acGroup) {
                    $inverterPowerDc[$acGroup->getAcGroup()] = $acGroup->getDcPowerInverter();
                }
            } else {
                foreach ($anlage->getAcGroups() as $acGroup) {
                    ($acGroup->getDcPowerInverter() > 0) ? $powerPerInverter = $acGroup->getDcPowerInverter() / ($acGroup->getUnitLast() - $acGroup->getUnitFirst() + 1) : $powerPerInverter = 0;
                    for ($inv = $acGroup->getUnitFirst(); $inv <= $acGroup->getUnitLast(); $inv++) {
                        $inverterPowerDc[$inv] = $powerPerInverter;
                    }
                }
            }
        } else {
            // ToDo: nachdenken ob wir die Berechnung des Pnom je Inverter überhaupt brauchen,
            // ToDo: wenn ich nur für einen Inverter Daten berechne, wird eh nicht ausgegeben ??????
            /*
            $inverter--;
            if ($anlage->getConfigType() == 1) {

            } else {
                $acGroups = $anlage->getAcGroups();
                if ($anlage->getUseNewDcSchema()) {
                    $inverterPowerDc[$acGroups[$inverter]->getAcGroup()] = $acGroups[$inverter]->getDcPowerInverter();
                } else {
                    ($acGroups[$inverter]->getDcPowerInverter() > 0) ? $powerPerInverter = $acGroups[$inverter]->getDcPowerInverter() / ($acGroups[$inverter]->getUnitLast() - $acGroups[$inverter]->getUnitFirst() + 1) : $powerPerInverter = 0;
                    $inverterPowerDc[$inverter] = $powerPerInverter;
                }
            }*/
        }
        // END: calulate pNom for each inverter

        $availabilitys = $this->availabilityRepository->sumAllCasesByDate($anlage, $from, $to, $inverter);
        foreach ($availabilitys as $row) {
            $inverterNr = $row['inverter'];
            // Berechnung der prozentualen Verfügbarkeit Part 1 und Part 2
            if ($row['control'] - $row['case4'] !== 0) {
                $invAPart1 = $this->calcInvAPart1($row);
                ($anlage->getPnom() > 0 && $inverterPowerDc[$inverterNr] > 0) ? $invAPart2 = $inverterPowerDc[$inverterNr] / $anlage->getPnom() : $invAPart2 = 1;
                $invAPart3 = $invAPart1 * $invAPart2;
            } else {
                $invAPart1 = 0;
                $invAPart2 = 0;
                $invAPart3 = 0;
            }
            $sumPart1   += $invAPart1;
            $sumPart2   += $invAPart2;
            $pa         += $invAPart3;
        }

        return $inverter === null ? $pa : $sumPart1;
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
                $istData[$stamp][$inverter]['stamp']    = $row['stamp'];
                $istData[$stamp][$inverter]['cos_phi']  = $row['cos_phi'];
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
        $sql_einstrahlung = "SELECT a.stamp, b.g_lower, b.g_upper, b.wind_speed FROM (db_dummysoll a left JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' AND '$to'";
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
                $irrData[$stamp]['stamp']   = $stamp;
                $irrData[$stamp]['irr']     = $strahlung;
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