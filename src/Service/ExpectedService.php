<?php


namespace App\Service;

use App\Repository\AnlageMonthRepository;
use PDO;
use App\Entity\Anlage;
use App\Entity\AnlageGroupModules;
use App\Entity\AnlageGroupMonths;
use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\GroupModulesRepository;
use App\Repository\GroupMonthsRepository;
use App\Repository\GroupsRepository;

class ExpectedService
{
    use G4NTrait;

    private AnlagenRepository $anlagenRepo;
    private GroupsRepository $groupsRepo;
    private GroupMonthsRepository $groupMonthsRepo;
    private GroupModulesRepository $groupModulesRepo;
    private FunctionsService $functions;
    private AnlageMonthRepository $anlageMonthRepo;

    public function __construct(AnlagenRepository $anlagenRepo,
                                GroupsRepository $groupsRepo,
                                GroupMonthsRepository $groupMonthsRepo,
                                GroupModulesRepository $groupModulesRepo,
                                AnlageMonthRepository $anlageMonthRepo,
                                FunctionsService $functions)
    {
        $this->anlagenRepo = $anlagenRepo;
        $this->groupsRepo = $groupsRepo;
        $this->groupMonthsRepo = $groupMonthsRepo;
        $this->groupModulesRepo = $groupModulesRepo;
        $this->functions = $functions;
        $this->anlageMonthRepo = $anlageMonthRepo;
    }

    public function storeExpectedToDatabase(Anlage $anlage, $from, $to):string
    {
        $output = '';
        if ($anlage->getGroups()) {
            $conn = self::getPdoConnection();
            $arrayExpected = $this->calcExpected($anlage, $from, $to);
            if ($arrayExpected) {
                $sql = "INSERT INTO " . $anlage->getDbNameDcSoll() . " (stamp, wr, wr_num, group_dc, group_ac, ac_exp_power, ac_exp_power_evu, ac_exp_power_no_limit, dc_exp_power, dc_exp_current, soll_imppwr, soll_pdcwr) VALUES ";
                foreach ($arrayExpected as $expected) {
                    $sql .= "('" . $expected['stamp'] . "'," . $expected['unit'] . "," . $expected['dc_group'] . "," . $expected['dc_group'] . "," . $expected['ac_group'] . "," .
                        $expected['exp_power_ac'] . "," . $expected['exp_evu'] . "," . $expected['exp_nolimit'] . "," . $expected['exp_power_dc'] . "," .
                        $expected['exp_current_dc'] . "," . $expected['exp_current_dc'] . "," . $expected['exp_power_dc'] . "),";
                }
                $sql = substr($sql, 0, -1); // nimm das letzte Komma weg
                $conn->exec("DELETE FROM " . $anlage->getDbNameDcSoll() . " WHERE stamp BETWEEN '$from' AND '$to';");
                $conn->exec($sql);
                $recUpdated = count($arrayExpected);
                $output .= "From $from until $to – $recUpdated records updated.<br>";
            } else {
                $output .= "Fehler bei 'calcExpected', leeres Array zurückgegben.<br>";
            }
            $conn = null;
        }

        return $output;
    }


    private function calcExpected(Anlage $anlage, $from, $to):array
    {
        $resultArray    = [];
        $aktuellesJahr  = date('Y', strtotime($from));
        $betriebsJahre  = $aktuellesJahr - $anlage->getAnlBetrieb()->format('Y'); #betriebsjahre
        $month          = date("m", strtotime($from));

        $conn = self::getPdoConnection();
        // Lade Wetter (Wetterstation der Anlage) Daten für die angegebene Zeit und Speicher diese in ein Array
        $weatherStations = $weatherStations = $this->groupsRepo->findAllWeatherstations($anlage, $anlage->getWeatherStation());
        $sqlWetterDaten = "SELECT stamp AS stamp, g_lower AS irr_lower, g_upper AS irr_upper, pt_avg AS panel_temp FROM " . $anlage->getDbNameWeather() . " WHERE (`stamp` BETWEEN '$from' AND '$to') AND (g_lower > 0 OR g_upper > 0)";
        $resWeather = $conn->prepare($sqlWetterDaten);
        $resWeather->execute();
        $weatherArray[$anlage->getWeatherStation()->getDatabaseIdent()] = $resWeather->fetchAll(PDO::FETCH_ASSOC);
        $resWeather = null;
        foreach ($weatherStations as $weatherStation) {
            $sqlWetterDaten = "SELECT stamp AS stamp, g_lower AS irr_lower, g_upper AS irr_upper, pt_avg AS panel_temp FROM " . $weatherStation->getWeatherStation()->getDbNameWeather() . " WHERE (`stamp` BETWEEN '$from' AND '$to') AND (g_lower > 0 OR g_upper > 0)";
            $resWeather = $conn->prepare($sqlWetterDaten);
            $resWeather->execute();
            $weatherArray[$weatherStation->getWeatherStation()->getDatabaseIdent()] = $resWeather->fetchAll(PDO::FETCH_ASSOC);
            $resWeather = null;
        }
        $conn = null;

        foreach($anlage->getGroups() as $groupKey => $group) {
            // Monatswerte für diese Gruppe laden
            /** @var AnlageGroupMonths $groupMonth */
            $groupMonth = $this->groupMonthsRepo->findOneBy(['anlageGroup' => $group->getId(), 'month' => $month]);
            $anlageMonth = $this->anlageMonthRepo->findOneBy(['anlage' => $anlage, 'month' => $month]);

            // Wetterstation auswählen, von der die Daten kommen sollen
            /* @var WeatherStation $currentWeatherStation */
            ($group->getWeatherStation()) ? $currentWeatherStation = $group->getWeatherStation() : $currentWeatherStation = $anlage->getWeatherStation();
            for ($unit = $group->getUnitFirst(); $unit <= $group->getUnitLast(); $unit++) {
                foreach($weatherArray[$currentWeatherStation->getDatabaseIdent()] as $weather) {

                    $stamp      = $weather["stamp"];
                    $pannelTemp = $weather["panel_temp"];   // Pannel Temperatur
                    $irrUpper   = $weather["irr_upper"];    // Strahlung an obern Sensor
                    $irrLower   = $weather["irr_lower"];    // Strahlung an unterem Sensor

                    // Strahlung berechnen, für Analgen die KEINE 'Ost/West' Ausrichtung haben
                    if ($anlage->getUseLowerIrrForExpected()) {
                        $irr = $irrLower;
                    } else {
                        $irr = $this->functions->calcIrr($irrUpper, $irrLower, $stamp, $anlage, $group, $currentWeatherStation, $groupMonth);
                    }

                    /** @var AnlageGroupModules[] $modules */
                    $modules = $group->getModules();
                    $expPowerDc = $expCurrentDc = 0;
                    foreach ($modules as $modul) {
                        //
                        if ($anlage->getIsOstWestAnlage()) {
                            // Ist 'Ost/West' Anlage, dann nutze $irrUpper (Strahlung Osten) und $irrLower (Strahlung Westen) und multipliziere mit der Anzahl Strings Ost / West
                            $expPowerDcHlp      = $modul->getModuleType()->getFactorPower($irrUpper) * $modul->getNumStringsPerUnitEast() * $modul->getNumModulesPerString() / 1000 / 4; // Ost
                            $expPowerDcHlp     += $modul->getModuleType()->getFactorPower($irrLower) * $modul->getNumStringsPerUnitWest() * $modul->getNumModulesPerString() / 1000 / 4; // West
                            $expCurrentDcHlp    = $modul->getModuleType()->getFactorCurrent($irrUpper) * $modul->getNumStringsPerUnitEast(); // Ost // nicht durch 4 teilen, sind keine Ah, sondern A
                            $expCurrentDcHlp   += $modul->getModuleType()->getFactorCurrent($irrLower) * $modul->getNumStringsPerUnitWest(); // West // nicht durch 4 teilen, sind keine Ah, sondern A
                        } else {
                            // Ist keine 'Ost/West' Anlage
                            $expPowerDcHlp      = $modul->getModuleType()->getFactorPower($irr) * $modul->getNumStringsPerUnit() * $modul->getNumModulesPerString() / 1000 / 4;
                            $expCurrentDcHlp    = $modul->getModuleType()->getFactorCurrent($irr) * $modul->getNumStringsPerUnit(); // nicht durch 4 teilen, sind keine Ah, sondern A
                        }

                        // Temperatur Korrektur
                        if ($anlage->getHasPannelTemp()) {
                            //$expPowerDcHlp      = $expPowerDcHlp * $modul->getModuleType()->getTempCorrPower($pannelTemp);
                            //$expCurrentDcHlp    = $expCurrentDcHlp * $modul->getModuleType()->getTempCorrCurrent($pannelTemp);
                        }

                        // degradation abziehen (degradation * Betriebsjahre).
                        $expPowerDcHlp      = $expPowerDcHlp - ($expPowerDcHlp / 100 * $modul->getModuleType()->getDegradation() * $betriebsJahre);
                        $expCurrentDcHlp    = $expCurrentDcHlp - ($expCurrentDcHlp / 100 * $modul->getModuleType()->getDegradation() * $betriebsJahre);

                        $expPowerDc     += $expPowerDcHlp;
                        $expCurrentDc   += $expCurrentDcHlp;
                    }
                    //
                    $shadow_loss    = $group->getShadowLoss();
                    if ($groupMonth) {
                        if ($groupMonth->getShadowLoss()) $shadow_loss = $groupMonth->getShadowLoss();
                    } elseif ($anlageMonth) {
                        // nutze Anlagenweite Monatsverschattung (Entity: AnlageMonth)
                        $shadow_loss = $anlageMonth->getShadowLoss();
                    }

                    // Anpassung der Verschattung an die jeweiligen Strahlungsbedingungen
                    // d.h. je weniger Strahlung desso geringer ist die Auswirkung der Verschattung
                    // Werte für $val bis $val 6 sind mit OS und TL abgesprochen
                    $val1 = 100;
                    $val2 = 200;
                    $val3 = 400;
                    $val4 = 600;
                    $val5 = 800;
                    $val6 = 1000;
                    if ($irr <= $val1) {$shadow_loss = $shadow_loss * 0.05;}
                    elseif ($irr > $val1 && $irr <= $val2) {$shadow_loss = $shadow_loss * 0.21;}
                    elseif ($irr > $val2 && $irr <= $val3) {$shadow_loss = $shadow_loss * 0.35;}
                    elseif ($irr > $val3 && $irr <= $val4) {$shadow_loss = $shadow_loss * 0.57;}
                    elseif ($irr > $val4 && $irr <= $val5) {$shadow_loss = $shadow_loss * 0.71;}
                    elseif ($irr > $val5 && $irr <= $val6) {$shadow_loss = $shadow_loss * 0.8;}

                    $originalExpPower = $expPowerDc;
                    // Verluste auf der DC Seite brechnen
                    // Schattenverluste + Kabel Verluste + Sicherheitsverlust
                    $loss           = $shadow_loss + $group->getCabelLoss() + $group->getSecureLoss();
                    // Verhindert 'diff by zero'
                    if ($loss <> 0) {
                        $expPowerDc = $expPowerDc - ($expPowerDc / 100 * $loss);
                        $expCurrentDc = $expCurrentDc - ($expCurrentDc / 100 * $loss);
                    }

                    // AC Expected Berechnung
                    // Umrechnung DC nach AC
                    $expNoLimit = $expPowerDc - ($expPowerDc / 100 * $group->getFactorAC());
                    $expEvu = $expNoLimit - ($expNoLimit / 100 * $group->getGridLoss());
                    // Abriegelung
                    if ($group->getLimitAc() > 0) {
                        ($expNoLimit > $group->getLimitAc()) ? $expPowerAc = $group->getLimitAc() : $expPowerAc = $expNoLimit;
                        if ($expEvu > $group->getLimitAc()) $expEvu = $group->getLimitAc();
                    } else {
                        $expPowerAc = $expNoLimit;
                    }

                    //Speichern der Werte in Array
                    $resultArray[] = [
                        'stamp'             => $stamp,
                        'unit'              => $unit,
                        'dc_group'          => $group->getDcGroup(),
                        'ac_group'          => $group->getAcGroup(),
                        'exp_power_dc'      => round($expPowerDc, 4),
                        'exp_current_dc'    => round((is_nan($expCurrentDc)) ? 0: $expCurrentDc, 4),
                        'exp_power_ac'      => round($expPowerAc, 4),
                        'exp_evu'           => round($expEvu, 4),
                        'exp_nolimit'       => round($expNoLimit, 4),
                    ];
                }
            }
        }

        return $resultArray;
    }
}