<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlageGroupModules;
use App\Entity\AnlageGroupMonths;
use App\Entity\OpenWeather;
use App\Entity\WeatherStation;
use App\Helper\G4NTrait;
use App\Repository\AnlageMonthRepository;
use App\Repository\AnlagenRepository;
use App\Repository\GroupModulesRepository;
use App\Repository\GroupMonthsRepository;
use App\Repository\GroupsRepository;
use PDO;

class ExpectedService
{
    use G4NTrait;

    public function __construct(
        private AnlagenRepository $anlagenRepo,
        private GroupsRepository $groupsRepo,
        private GroupMonthsRepository $groupMonthsRepo,
        private GroupModulesRepository $groupModulesRepo,
        private AnlageMonthRepository $anlageMonthRepo,
        private FunctionsService $functions,
        private WeatherFunctionsService $weatherFunctions,
        private OpenWeatherService $openWeather)
    {
    }

    public function storeExpectedToDatabase(Anlage|int $anlage, $from, $to): string
    {
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepo->findOneBy(['anlId' => $anlage]);
        }

        $output = '';
        if ($anlage->getGroups() && ! $anlage->isExcludeFromExpCalc()) {
            $conn = self::getPdoConnection();
            $arrayExpected = $this->calcExpected($anlage, $from, $to);
            if ($arrayExpected) {
                $sql = 'INSERT INTO '.$anlage->getDbNameDcSoll().' (stamp, wr, wr_num, group_dc, group_ac, ac_exp_power, ac_exp_power_evu, ac_exp_power_no_limit, dc_exp_power, dc_exp_current, soll_imppwr, soll_pdcwr, dc_exp_voltage) VALUES ';
                foreach ($arrayExpected as $expected) {
                    $sql .= "('".$expected['stamp']."',".$expected['unit'].','.$expected['dc_group'].','.$expected['dc_group'].','.$expected['ac_group'].','.
                        $expected['exp_power_ac'].','.$expected['exp_evu'].','.$expected['exp_nolimit'].','.$expected['exp_power_dc'].','.
                        $expected['exp_current_dc'].','.$expected['exp_current_dc'].','.$expected['exp_power_dc'].','.$expected['exp_voltage'].'),';
                }
                $sql = substr($sql, 0, -1); // nimm das letzte Komma weg
                $conn->exec('DELETE FROM '.$anlage->getDbNameDcSoll()." WHERE stamp BETWEEN '$from' AND '$to';");
                #dd('DELETE FROM '.$anlage->getDbNameDcSoll()." WHERE stamp BETWEEN '$from' AND '$to';");
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

    private function calcExpected(Anlage $anlage, $from, $to): array
    {
        $resultArray = [];
        $aktuellesJahr = date('Y', strtotime($from));
        $betriebsJahre = $aktuellesJahr - $anlage->getAnlBetrieb()->format('Y'); // betriebsjahre
        $month = date('m', strtotime($from));

        $conn = self::getPdoConnection();
        // Lade Wetter (Wetterstation der Anlage) Daten für die angegebene Zeit und Speicher diese in ein Array
        $weatherStations = $this->groupsRepo->findAllWeatherstations($anlage, $anlage->getWeatherStation());
        $sqlWetterDaten = 'SELECT stamp AS stamp, g_lower AS irr_lower, g_upper AS irr_upper, temp_pannel AS panel_temp, temp_ambient AS ambient_temp FROM '.$anlage->getDbNameWeather()." WHERE (`stamp` BETWEEN '$from' AND '$to') AND (g_lower > 0 OR g_upper > 0)";
        $resWeather = $conn->prepare($sqlWetterDaten);
        $resWeather->execute();
        $weatherArray[$anlage->getWeatherStation()->getDatabaseIdent()] = $resWeather->fetchAll(PDO::FETCH_ASSOC);

        $resWeather = null;
        foreach ($weatherStations as $weatherStation) {
            $sqlWetterDaten = 'SELECT stamp AS stamp, g_lower AS irr_lower, g_upper AS irr_upper, pt_avg AS panel_temp FROM '.$weatherStation->getWeatherStation()->getDbNameWeather()." WHERE (`stamp` BETWEEN '$from' AND '$to') AND (g_lower > 0 OR g_upper > 0)";
            $resWeather = $conn->prepare($sqlWetterDaten);
            $resWeather->execute();
            $weatherArray[$weatherStation->getWeatherStation()->getDatabaseIdent()] = $resWeather->fetchAll(PDO::FETCH_ASSOC);
            $resWeather = null;
        }
        $conn = null;

        foreach ($anlage->getGroups() as $group) {
            // Monatswerte für diese Gruppe laden
            /** @var AnlageGroupMonths $groupMonth */
            $groupMonth = $this->groupMonthsRepo->findOneBy(['anlageGroup' => $group->getId(), 'month' => $month]);
            $anlageMonth = $this->anlageMonthRepo->findOneBy(['anlage' => $anlage, 'month' => $month]);

            // Wetterstation auswählen, von der die Daten kommen sollen
            /* @var WeatherStation $currentWeatherStation */
            $currentWeatherStation = $group->getWeatherStation() ? $group->getWeatherStation() : $anlage->getWeatherStation();
            for ($unit = $group->getUnitFirst(); $unit <= $group->getUnitLast(); ++$unit) {
                foreach ($weatherArray[$currentWeatherStation->getDatabaseIdent()] as $weather) {
                    $stamp = $weather['stamp'];

                    // use plant based shadow loss (normaly - 0)
                    $shadow_loss = $group->getShadowLoss();
                    if ($groupMonth) {
                        // use individule shadow loss per group (Entity: GroupMonth)
                        if ($groupMonth->getShadowLoss()) {
                            $shadow_loss = $groupMonth->getShadowLoss();
                        }
                    } elseif ($anlageMonth) {
                        // use general monthly shadow loss (Entity: AnlageMonth)
                        $shadow_loss = $anlageMonth->getShadowLoss();
                    }

                    // Anpassung der Verschattung an die jeweiligen Strahlungsbedingungen
                    // d.h. je weniger Strahlung desso geringer ist die Auswirkung der Verschattung
                    // Werte für die Eingruppierung sind mit OS und TL abgesprochen
                    if ($currentWeatherStation->getHasUpper() && !$currentWeatherStation->getHasLower()) {
                        // Station hat nur oberen Sensor => Die Strahlung OHNE Gewichtung zurückgeben, Verluste werden dann über die Verschattung berechnet
                        $tempIrr = (float) $weather['irr_upper'];
                    } elseif ($anlage->getUseLowerIrrForExpected()) {
                        $tempIrr = (float) $weather['irr_lower'];
                    } else {
                        $tempIrr = $this->functions->mittelwert([(float) $weather['irr_upper'], (float) $weather['irr_lower']]);
                    }
                    if ($tempIrr <= 100) {
                        $shadow_loss = $shadow_loss * 0.0; // 0.05
                    } elseif ($tempIrr <= 200) {
                        $shadow_loss = $shadow_loss * 0.0; // 0.21
                    } elseif ($tempIrr <= 400) {
                        $shadow_loss = $shadow_loss * 0.35;
                    } elseif ($tempIrr <= 600) {
                        $shadow_loss = $shadow_loss * 0.57;
                    } elseif ($tempIrr <= 800) {
                        $shadow_loss = $shadow_loss * 0.71;
                    } elseif ($tempIrr <= 1000) {
                        $shadow_loss = $shadow_loss * 0.8;
                    }

                    $pannelTemp = is_numeric($weather['panel_temp']) ? (float)$weather['panel_temp'] : null;   // Pannel Temperatur
                    $irrUpper = (float) $weather['irr_upper'] - ((float) $weather['irr_upper'] / 100 * $shadow_loss);    // Strahlung an obern Sensor
                    $irrLower = (float) $weather['irr_lower'] - ((float) $weather['irr_lower'] / 100 * $shadow_loss);    // Strahlung an unterem Sensor

                    // Strahlung berechnen, für Analgen die KEINE 'Ost/West' Ausrichtung haben
                    if ($anlage->getUseLowerIrrForExpected()) {
                        $irr = $irrLower;
                    } else {
                        $irr = $this->functions->calcIrr($irrUpper, $irrLower, $stamp, $anlage, $group, $currentWeatherStation, $groupMonth);
                    }

                    /** @var AnlageGroupModules[] $modules */
                    $modules = $group->getModules();
                    $expPowerDc = $expCurrentDc = $limitExpCurrent = $limitExpPower = $expVoltage = 0;
                    foreach ($modules as $modul) {
                        if ($anlage->getIsOstWestAnlage()) {
                            // Ist 'Ost/West' Anlage, dann nutze $irrUpper (Strahlung Osten) und $irrLower (Strahlung Westen) und multipliziere mit der Anzahl Strings Ost / West
                            // Power
                            $expPowerDcHlp = $modul->getModuleType()->getFactorPower($irrUpper) * $modul->getNumStringsPerUnitEast() * $modul->getNumModulesPerString() / 1000 / 4; // Ost
                            $expPowerDcHlp += $modul->getModuleType()->getFactorPower($irrLower) * $modul->getNumStringsPerUnitWest() * $modul->getNumModulesPerString() / 1000 / 4; // West
                            $limitExpPowerHlp = ($modul->getNumStringsPerUnitWest() + $modul->getNumStringsPerUnitEast()) * $modul->getNumModulesPerString() * $modul->getModuleType()->getMaxPmpp() / 1000 / 4;
                            // Current
                            $expCurrentDcHlp = $modul->getModuleType()->getFactorCurrent($irrUpper) * $modul->getNumStringsPerUnitEast(); // Ost // nicht durch 4 teilen, sind keine Ah, sondern A
                            $expCurrentDcHlp += $modul->getModuleType()->getFactorCurrent($irrLower) * $modul->getNumStringsPerUnitWest(); // West // nicht durch 4 teilen, sind keine Ah, sondern A
                            $limitExpCurrentHlp = ($modul->getNumStringsPerUnitWest() + $modul->getNumStringsPerUnitEast()) * ($modul->getModuleType()->getMaxImpp() * 1.015); // 1,5% Sicherheitsaufschlag
                            // Voltage
                            $expVoltageDcHlp = $modul->getModuleType()->getExpVoltage($irrUpper) * $modul->getNumModulesPerString();
                        } else {
                            // Ist keine 'Ost/West' Anlage
                            // Power
                            $expPowerDcHlp = $modul->getModuleType()->getFactorPower($irr) * $modul->getNumStringsPerUnit() * $modul->getNumModulesPerString() / 1000 / 4;
                            $limitExpPowerHlp = $modul->getNumStringsPerUnit() * $modul->getNumModulesPerString() * $modul->getModuleType()->getMaxPmpp() / 1000 / 4;
                            // Current
                            $expCurrentDcHlp = $modul->getModuleType()->getFactorCurrent($irr) * $modul->getNumStringsPerUnit(); // nicht durch 4 teilen, sind keine Ah, sondern A
                            $limitExpCurrentHlp = $modul->getNumStringsPerUnit() * ($modul->getModuleType()->getMaxImpp() * 1.015); // 1,5% Sicherheitsaufschlag
                            // Voltage
                            $expVoltageDcHlp = $modul->getModuleType()->getExpVoltage($irr) * $modul->getNumModulesPerString();
                        }

                        // Temperatur Korrektur
                        if ($anlage->getHasPannelTemp() && $pannelTemp) {
                            $expPowerDcHlp = $expPowerDcHlp * $modul->getModuleType()->getTempCorrPower($pannelTemp);
                            $expCurrentDcHlp = $expCurrentDcHlp * $modul->getModuleType()->getTempCorrCurrent($pannelTemp);
                            $expVoltageDcHlp = $expVoltageDcHlp * $modul->getModuleType()->getTempCorrVoltage($pannelTemp);
                        } else {
                            // ToDo: Funktion zur Berechnung der Temperatur Korrektur via OpenWeather (temp ambient, wind speed), NREL und Co implementieren
                            if (false) { // $anlage->hasAmbientTemp
                                // Wenn nur Umgebungstemepratur vorhanden
                            } else {
                                // Wenn weder Umgebungs noch Modul Temperatur vorhanden, dann nutze Daten aus Open Weather (sind nur Stunden weise vorhanden)
                                if ($anlage->getAnlId() == '183') {
                                    $openWeather = $this->openWeather->findOpenWeather($anlage, date_create($stamp));
                                    $windSpeed = 4; // ReGebeng – gemittelte Daten aus OpenWeather
                                    $airTemp = 26; // ReGebeng – gemittelte Daten aus OpenWeather
                                    $pannelTemp = round($this->weatherFunctions->tempCellNrel($anlage, $windSpeed, $airTemp, $irr), 2);
                                    #if ($irr > 0) dump("Pannel: $pannelTemp | AirTemp: $airTemp | WindSpeed: $windSpeed | Irr: $irr");
                                    $expPowerDcHlp = $expPowerDcHlp * $modul->getModuleType()->getTempCorrPower($pannelTemp);
                                    $expCurrentDcHlp = $expCurrentDcHlp * $modul->getModuleType()->getTempCorrCurrent($pannelTemp);
                                    $expVoltageDcHlp = $expVoltageDcHlp * $modul->getModuleType()->getTempCorrVoltage($pannelTemp);
                                }
                            }
                        }

                        // degradation abziehen (degradation * Betriebsjahre).
                        $expPowerDcHlp = $expPowerDcHlp - ($expPowerDcHlp / 100 * $modul->getModuleType()->getDegradation() * $betriebsJahre);
                        $expCurrentDcHlp = $expCurrentDcHlp - ($expCurrentDcHlp / 100 * $modul->getModuleType()->getDegradation() * $betriebsJahre);

                        $expPowerDc += $expPowerDcHlp;
                        $expCurrentDc += $expCurrentDcHlp;
                        $limitExpPower += $limitExpPowerHlp;
                        $limitExpCurrent += $limitExpCurrentHlp;
                        $expVoltage += $expVoltageDcHlp;
                    }

                    // Verluste auf der DC Seite brechnen
                    // Kabel Verluste + Sicherheitsverlust
                    $loss = $group->getCabelLoss() + $group->getSecureLoss();

                    // Verhindert 'diff by zero'
                    if ($loss != 0) {
                        $expPowerDc = $expPowerDc - ($expPowerDc / 100 * $loss);
                        $expCurrentDc = $expCurrentDc - ($expCurrentDc / 100 * $loss);
                    }

                    // Limitierung durch Modul prüfen und entsprechend abregeln
                    $expCurrentDc = $expCurrentDc > $limitExpCurrent ? $limitExpCurrent : $expCurrentDc;

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

                    // Speichern der Werte in Array
                    $resultArray[] = [
                        'stamp' => $stamp,
                        'unit' => $unit,
                        'dc_group' => $group->getDcGroup(),
                        'ac_group' => $group->getAcGroup(),
                        'exp_power_dc' => round($expPowerDc, 6),
                        'exp_current_dc' => round((is_nan($expCurrentDc)) ? 0 : $expCurrentDc, 6),
                        'exp_power_ac' => round($expPowerAc, 6),
                        'exp_evu' => round($expEvu, 6),
                        'exp_nolimit' => round($expNoLimit, 6),
                        'exp_voltage' => round($expVoltage, 4),
                    ];
                }
            }
        }

        return $resultArray;
    }
}
