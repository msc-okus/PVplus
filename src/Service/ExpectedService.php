<?php

namespace App\Service;

use App\Entity\AnlageModules;
use App\Repository\AnlageMonthRepository;
use JetBrains\PhpStorm\Pure;
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

    public function storeExpectedToDatabase(Anlage|Int $anlage, $from, $to): string
    {
        if (is_int($anlage)) $anlage = $this->anlagenRepo->findOneBy(['anlId' => $anlage]);

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
        $weatherStations = $this->groupsRepo->findAllWeatherstations($anlage, $anlage->getWeatherStation());
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

        foreach($anlage->getGroups() as $group) {
            // Monatswerte für diese Gruppe laden
            /** @var AnlageGroupMonths $groupMonth */
            $groupMonth = $this->groupMonthsRepo->findOneBy(['anlageGroup' => $group->getId(), 'month' => $month]);
            $anlageMonth = $this->anlageMonthRepo->findOneBy(['anlage' => $anlage, 'month' => $month]);

            // Wetterstation auswählen, von der die Daten kommen sollen
            /* @var WeatherStation $currentWeatherStation */
            ($group->getWeatherStation()) ? $currentWeatherStation = $group->getWeatherStation() : $currentWeatherStation = $anlage->getWeatherStation();
            for ($unit = $group->getUnitFirst(); $unit <= $group->getUnitLast(); $unit++) {
                foreach($weatherArray[$currentWeatherStation->getDatabaseIdent()] as $weather) {
                    $stamp          = $weather["stamp"];

                    // use plant based shadow loss (normaly - 0)
                    $shadow_loss    = $group->getShadowLoss();
                    if ($groupMonth) {
                        // use individule shadow loss per group (Entity: GroupMonth)
                        if ($groupMonth->getShadowLoss()) $shadow_loss = $groupMonth->getShadowLoss();
                    } elseif ($anlageMonth) {
                        // use general monthly shadow loss (Entity: AnlageMonth)
                        $shadow_loss = $anlageMonth->getShadowLoss();
                    }

                    // Anpassung der Verschattung an die jeweiligen Strahlungsbedingungen
                    // d.h. je weniger Strahlung desso geringer ist die Auswirkung der Verschattung
                    // Werte für die Eingruppierung sind mit OS und TL abgesprochen
                    $tempIrr = $this->functions->mittelwert([(float)$weather["irr_upper"], (float)$weather["irr_lower"]]);
                    if     ($tempIrr <= 100) {$shadow_loss = $shadow_loss * 0.0;} // 0.05
                    elseif ($tempIrr <= 200) {$shadow_loss = $shadow_loss * 0.0;} // 0.21
                    elseif ($tempIrr <= 400) {$shadow_loss = $shadow_loss * 0.35;}
                    elseif ($tempIrr <= 600) {$shadow_loss = $shadow_loss * 0.57;}
                    elseif ($tempIrr <= 800) {$shadow_loss = $shadow_loss * 0.71;}
                    elseif ($tempIrr <= 1000) {$shadow_loss = $shadow_loss * 0.8;}


                    $pannelTemp     = (float)$weather["panel_temp"];   // Pannel Temperatur
                    $irrUpperBase   = (float)$weather["irr_upper"]  - ((float)$weather["irr_upper"] / 100 * $shadow_loss);    // Strahlung an obern Sensor
                    $irrLowerBase   = (float)$weather["irr_lower"]  - ((float)$weather["irr_lower"] / 100 * $shadow_loss);    // Strahlung an unterem Sensor

                    // Strahlung berechnen, für Analgen die KEINE 'Ost/West' Ausrichtung haben
                    if ($anlage->getUseLowerIrrForExpected()) {
                        $irrBase = $irrLowerBase;
                    } else {
                        $irrBase = $this->functions->calcIrr($irrUpperBase, $irrLowerBase, $stamp, $anlage, $group, $currentWeatherStation, $groupMonth);
                    }

                    /** @var AnlageGroupModules[] $modules */
                    $modules = $group->getModules();
                    $expPowerDc = $expCurrentDc = 0;
                    foreach ($modules as $modul) {
                        // Irradiation discount depending on irradiation (Settings stored in entity: AnlageGroupModules)
                        $irr        = $this->calcIrradiationDiscountByModule($modul->getModuleType(), $irrBase);
                        $irrUpper   = $this->calcIrradiationDiscountByModule($modul->getModuleType(), $irrUpperBase);
                        $irrLower   = $this->calcIrradiationDiscountByModule($modul->getModuleType(), $irrLowerBase);

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
                        if ($anlage->getHasPannelTemp() ) { //&& $pannelTemp >= 25
                            $expPowerDcHlp      = $expPowerDcHlp   * $modul->getModuleType()->getTempCorrPower($pannelTemp);
                            $expCurrentDcHlp    = $expCurrentDcHlp * $modul->getModuleType()->getTempCorrCurrent($pannelTemp);
                        }

                        // degradation abziehen (degradation * Betriebsjahre).
                        $expPowerDcHlp = $expPowerDcHlp - ($expPowerDcHlp / 100 * $modul->getModuleType()->getDegradation() * $betriebsJahre);
                        $expCurrentDcHlp = $expCurrentDcHlp - ($expCurrentDcHlp / 100 * $modul->getModuleType()->getDegradation() * $betriebsJahre);

                        $expPowerDc     += $expPowerDcHlp;
                        $expCurrentDc   += $expCurrentDcHlp;
                    }

                    // Verluste auf der DC Seite brechnen
                    // Kabel Verluste + Sicherheitsverlust
                    $loss = $group->getCabelLoss() + $group->getSecureLoss();

                    // Verhindert 'diff by zero'
                    if ($loss <> 0) {
                        $expPowerDc = $expPowerDc - ($expPowerDc / 100 * $loss);
                        $expCurrentDc = $expCurrentDc - ($expCurrentDc / 100 * $loss);
                    }

                    // AC Expected Berechnung
                    // Umrechnung DC nach AC
                    $expNoLimit = $expPowerDc - ($expPowerDc / 100 * $group->getFactorAC());

                    // Prüfe ob Abriegelung gesetzt ist, wenn ja, begrenze den Wert auf das maximale.
                    if ($group->getLimitAc() > 0) {
                        ($expNoLimit > $group->getLimitAc()) ? $expPowerAc = $group->getLimitAc() : $expPowerAc = $expNoLimit;
                    } else {
                        $expPowerAc = $expNoLimit;
                    }
                    // Berechne die Expected für das GRID (evu)
                    $expEvu = $expPowerAc - ($expPowerAc / 100 * $group->getGridLoss());

                    //Speichern der Werte in Array
                    $resultArray[] = [
                        'stamp'             => $stamp,
                        'unit'              => $unit,
                        'dc_group'          => $group->getDcGroup(),
                        'ac_group'          => $group->getAcGroup(),
                        'exp_power_dc'      => round($expPowerDc, 6),
                        'exp_current_dc'    => round((is_nan($expCurrentDc)) ? 0: $expCurrentDc, 6),
                        'exp_power_ac'      => round($expPowerAc, 6),
                        'exp_evu'           => round($expEvu, 6),
                        'exp_nolimit'       => round($expNoLimit, 6),
                    ];
                }
            }
        }

        return $resultArray;
    }

    private function calcIrradiationDiscountByModule(AnlageModules $modul, float $irradiation): float
    {
        $factor = 1.0;
        if (false === $modul->isNewExpected())  {
            if (true !== $modul->getDisableIrrDiscount()) {
                if ($irradiation > 0 && $irradiation <= 50) {
                    $factor = 1 - ($modul->getIrrDiscount1() / 100);
                } elseif ($irradiation > 50 && $irradiation <= 100) {
                    $factor = 1 - ($modul->getIrrDiscount2() / 100);
                } elseif ($irradiation > 100 && $irradiation <= 150) {
                    $factor = 1 - ($modul->getIrrDiscount3() / 100);
                } elseif ($irradiation > 150 && $irradiation <= 200) {
                    $factor = 1 - ($modul->getIrrDiscount4() / 100);
                } elseif ($irradiation > 200 && $irradiation <= 250) {
                    $factor = 1 - ($modul->getIrrDiscount5() / 100);
                } elseif ($irradiation > 250 && $irradiation <= 300) {
                    $factor = 1 - ($modul->getIrrDiscount6() / 100);
                } elseif ($irradiation > 300 && $irradiation <= 350) {
                    $factor = 1 - ($modul->getIrrDiscount7() / 100);
                } elseif ($irradiation > 350 && $irradiation <= 400) {
                    $factor = 1 - ($modul->getIrrDiscount8() / 100);
                }
            }
        }
        return $irradiation * $factor;
    }
}