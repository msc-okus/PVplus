<?php

namespace App\Service;

use App\Entity\TimesConfig;
use App\Repository\TimesConfigRepository;
use DateTime;
use PDO;
use App\Entity\Anlage;
use App\Entity\AnlageAvailability;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\Case5Repository;
use Doctrine\ORM\EntityManagerInterface;

class AvailabilityService
{
    use G4NTrait;

    private EntityManagerInterface $em;
    private AnlageAvailabilityRepository $availabilityRepository;
    private Case5Repository $case5Repository;
    private TimesConfig $timesConfig;
    private TimesConfigRepository $timesConfigRepo;

    public function __construct(EntityManagerInterface $em, AnlageAvailabilityRepository $availabilityRepository, Case5Repository $case5Repository, TimesConfigRepository $timesConfigRepo)
    {
        $this->em = $em;
        $this->availabilityRepository = $availabilityRepository;
        $this->case5Repository = $case5Repository;
        $this->timesConfigRepo = $timesConfigRepo;
    }

    public function checkAvailability(Anlage $anlage, $date, $second = false)
    {
        // Suche pasende Zeitkonfiguration für diese Anlage und diese Datum
        /** @var TimesConfig $timesConfig */
        if ($second){
            $timesConfig = $this->timesConfigRepo->findValidConfig($anlage, 'availability_second', date_create(date('Y-m-d H:m', $date)));
        } else {
            $timesConfig = $this->timesConfigRepo->findValidConfig($anlage, 'availability_first', date_create(date('Y-m-d H:m', $date)));
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
            if (!$second) {
                $output .= "Anlage: " . $anlage->getAnlId() . " / " . $anlage->getAnlName() . " - " . date("Y-m-d", $timestampModulo) . "<br>";
            } else {
                $output .= "Anlage: " . $anlage->getAnlId() . " / " . $anlage->getAnlName() . " - " . date("Y-m-d", $timestampModulo) . " - SECOND<br>";
            }

            // prüfe ob minimum Strahlung für Verfügbarkeit eingetragen, wenn Ja nutze diese – ansonsten standard Wert 50 Watt nutzen
            ($anlage->getMinIrradiationAvailability() != null && $anlage->getMinIrradiationAvailability() > 0) ? $minStrahlung = $anlage->getMinIrradiationAvailability() : $minStrahlung = 50; // Watt / qm

            // Verfügbarkeit Berechnen und in Hilfsarray speichern
            $availabilitysHelper = $this->checkAvailabilityInverter($anlage, $timestampModulo, $timesConfig, $minStrahlung);

            // DC Leistung der Inverter laden (aus AC Gruppen)
            if ($anlage->getUseNewDcSchema()) {
                foreach ($anlage->getAcGroups() as $acGroup) {
                    $inverterPowerDc[$acGroup->getAcGroup()] = $acGroup->getDcPowerInverter();
                }
            } else {
                foreach ($anlage->getAcGroups() as $acGroup) {
                    ($acGroup->getDcPowerInverter() > 0) ? $powerPerInverter = $acGroup->getDcPowerInverter() / ($acGroup->getUnitLast() - $acGroup->getUnitFirst() + 1) : $powerPerInverter = 0;
                    for ($inverter = $acGroup->getUnitFirst(); $inverter <= $acGroup->getUnitLast(); $inverter++) {
                        $inverterPowerDc[$inverter] = $powerPerInverter;
                    }
                }
            }
            // Speichern der ermittelten Werte

            foreach ($availabilitysHelper as $inverter => $availability) {

                // Berechnung der prozentualen Verfügbarkeit Part 1 und Part 2
                if ($availability['control'] - $availability['case4'] != 0) {
                    $invAPart1 = (($availability['case1'] + $availability['case2'] + $availability['case5']) / ($availability['control'])) * 100;
                    ($anlage->getPower() > 0 && $inverterPowerDc[$inverter] > 0) ? $invAPart2 = $inverterPowerDc[$inverter] / $anlage->getPower() : $invAPart2 = 1;
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

                if (!$second) {
                    // First Availability (Standard Berechneung)
                    $anlagenAvailability->setCase1($availability['case1']);
                    $anlagenAvailability->setCase2($availability['case2']);
                    $anlagenAvailability->setCase3($availability['case3']);
                    $anlagenAvailability->setCase4($availability['case4']);
                    $anlagenAvailability->setCase5($availability['case5']);
                    $anlagenAvailability->setControl($availability['control']);
                    $anlagenAvailability->setInvAPart1($invAPart1);
                    $anlagenAvailability->setInvAPart2($invAPart2);
                    $anlagenAvailability->setInvA($invAPart1 * $invAPart2);
                    $anlagenAvailability->setRemarks("");
                } else {
                    // Second Availability (optionale Berechneung)
                    $anlagenAvailability->setCase1Second($availability['case1']);
                    $anlagenAvailability->setCase2Second($availability['case2']);
                    $anlagenAvailability->setCase3Second($availability['case3']);
                    $anlagenAvailability->setCase4Second($availability['case4']);
                    $anlagenAvailability->setCase5Second($availability['case5']);
                    $anlagenAvailability->setControlSecond($availability['control']);
                    $anlagenAvailability->setInvAPart1Second($invAPart1);
                    $anlagenAvailability->setInvAPart2Second($invAPart2);
                    $anlagenAvailability->setInvASecond($invAPart1 * $invAPart2);
                    $anlagenAvailability->setRemarksSecond("");
                }
                $this->em->persist($anlagenAvailability);
            }
            $this->em->flush();
        }
        //$conn->close();

        return $output;
    }

    /**
     * @param Anlage $anlage
     * @param $timestampModulo
     * @param TimesConfig $timesConfig
     * @param int $minStrahlungModul minimale einstrahlung ab der Leistung zur Verfügung stehen muss
     * @return array
     * CASE 1 = wenn Gmod > 0 && Gmod < 50
     * CASE 2 = wenn Gmod >= 50 && PowerAc inverter > 0
     * CASE 3 = wenn Gmod >= 50 && PowerAc inverter <= 0
     * CASE 4 = wenn Gmod >= 50 && PowerAc inverter > 0 && cosPhi = 0
     * CASE 5 = Manuel, durch Operator herausgenommen (z.B.: wegen Wartung)
     * CONTROL = wenn Gmod > 0
     */
    public function checkAvailabilityInverter(Anlage $anlage, $timestampModulo, TimesConfig $timesConfig, int $minStrahlungModul = 50):array
    {
        $conn = self::getPdoConnection();
        $case3Helper = [];
        $availability = [];
        $case5 = false;

        $from   = date("Y-m-d ".$timesConfig->getStartTime()->format('H:i'), $timestampModulo);
        $to     = date("Y-m-d ".$timesConfig->getEndTime()->format('H:i'), $timestampModulo);
        $maxFailTime = $timesConfig->getMaxFailTime();

        // hole IST Werte
        $istData = $this->getIstData($anlage, $from, $to);
        // hole Strahlung (für Verfügbarkeit)
        //TODO: Erweitern auf die entsprechenden Gruppen Wetter Stationen; also nicht nur aus der Anlagen Wettersation sondern auch auf die Gruppen Stationen verweisen wenn angegeben
        $sql_einstrahlung = "SELECT stamp, g_lower, g_upper, wind_speed FROM " . $anlage->getDbNameWeather() . " WHERE stamp BETWEEN '$from' AND '$to'";
        $resultEinstrahlung = $conn->query($sql_einstrahlung);

        // Aus IstDaten und Strahlungsdaten die Tages Verfügbarkeit je Inverter berechnen
        if ($resultEinstrahlung->rowCount() > 0) {
            if($anlage->getUseNewDcSchema()) {
                $anzInverter = $anlage->getAcGroups()->count();
            } else {
                $anzInverter = $anlage->getAnzInverterFromGroupsAC();
            }
            $case5Array = [];

            // suche Case 5 Fälle und schreibe diese in Array[inverter][stamp] = true|false
            foreach ($this->case5Repository->findAllCase5($anlage, $from, $to) as $case) {
                $c5From = strtotime($case['stampFrom']);
                $c5To   = strtotime($case['stampTo']);
                for ($c5Stamp = $c5From; $c5Stamp < $c5To; $c5Stamp += 900) { // 900 = 15 Minuten in Sekunden
                    foreach (explode(',', $case['inverter'], 999) as $inverter) {
                        $inverter = trim($inverter, ' ');
                        $case5Array[$inverter][date('Y-m-d H:i:00', $c5Stamp)] = true;
                    }
                }
            }
            if(true){
                while ($einstrahlung = $resultEinstrahlung->fetch(PDO::FETCH_ASSOC)) {

                    $stamp = $einstrahlung['stamp'];
                    //TODO: Erweiterung auf Gruppen ebene (Bavelse Berg) und gewichtung nach Anlagengröße - Nutzung der Funktion 'calcIrr()'
                    if ($anlage->getIsOstWestAnlage()) {
                        $strahlung = ($einstrahlung['g_upper'] + $einstrahlung['g_lower']) / 2;
                    } else {
                        $strahlung = $einstrahlung['g_upper'];
                    }
                    $startInverter = 1;
                    for ($inverter = $startInverter; $inverter <= $anzInverter; $inverter++) {

                        // Nur beim ersten durchlauf, Werte setzen, damit nicht 'undifiend'
                        if (!isset($availability[$inverter]['case1'])) $availability[$inverter]['case1'] = 0;
                        if (!isset($availability[$inverter]['case2'])) $availability[$inverter]['case2'] = 0;
                        if (!isset($availability[$inverter]['case3'])) {
                            $availability[$inverter]['case3'] = 0;
                            $case3Helper[$inverter] = 0;
                        }
                        if (!isset($availability[$inverter]['case4'])) $availability[$inverter]['case4'] = 0;
                        if (!isset($availability[$inverter]['case5'])) $availability[$inverter]['case5'] = 0;
                        if (!isset($availability[$inverter]['control'])) $availability[$inverter]['control'] = 0;

                        (isset($istData[$stamp][$inverter]['power_ac'])) ? $powerAc = $istData[$stamp][$inverter]['power_ac'] : $powerAc = 0;
                        (isset($istData[$stamp][$inverter]['cos_phi'])) ? $cosPhi = $istData[$stamp][$inverter]['cos_phi'] : $cosPhi = 0;

                        // Schaue in case5Array nach ob eintrag für diesen Inverter und diesen Timestamp vorhanden ist
                        ($strahlung > 0 && isset($case5Array[$inverter][$stamp])) ? $case5 = true : $case5 = false;

                        // Case 1
                        if ($strahlung > 0 && $strahlung < $minStrahlungModul && $case5 === false) {
                            $availability[$inverter]['case1']++;
                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                        }
                        // Case 2
                        if ($strahlung >= $minStrahlungModul && $powerAc > 0 && $case5 === false) {
                            $availability[$inverter]['case2']++;

                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                        }
                        // Case 3
                        if ($strahlung >= $minStrahlungModul && $powerAc <= 0 && $case5 === false) {
                            $availability[$inverter]['case3']++;
                            $case3Helper[$inverter] += 15;
                        }
                        // Case 4
                        if ($strahlung >= $minStrahlungModul && $powerAc > 0 && $cosPhi == 0 && $case5 === false) {
                            $availability[$inverter]['case4']++;
                            if ($case3Helper[$inverter] < $maxFailTime) {
                                $availability[$inverter]['case3'] -= $case3Helper[$inverter] / 15;
                                $availability[$inverter]['case2'] += $case3Helper[$inverter] / 15;
                            }
                            $case3Helper[$inverter] = 0;
                        }
                        // Case 5
                        if ($strahlung > 0 && $case5 === true) {
                            $availability[$inverter]['case5']++;
                        }
                        // Control
                        if ($strahlung > 0) {
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

    private function getIstData(Anlage $anlage, $from, $to):array
    {
        $conn = self::getPdoConnection();
        $istData = [];
        $dbNameIst = $anlage->getDbNameIst();
        $sql = "SELECT a.stamp as stamp, wr_cos_phi_korrektur as cos_phi, b.unit as inverter, b.wr_pac as power_ac FROM (db_dummysoll a left JOIN $dbNameIst b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' AND '$to' ORDER BY a.stamp, b.unit";
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


}