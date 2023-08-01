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
use App\Repository\OpenWeatherRepository;
use App\Service\Functions\IrradiationService;
use Doctrine\ORM\NonUniqueResultException;
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
        private OpenWeatherService $openWeather,
        private OpenWeatherRepository $openWeatherRepo,
        private IrradiationService $irradiationService)
    {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function storeExpectedToDatabase(Anlage|int $anlage, $from, $to): string
    {
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepo->findOneBy(['anlId' => $anlage]);
        }

        $output = '';
        if ($anlage->getGroups() && !$anlage->isExcludeFromExpCalc() && $anlage->getAnlBetrieb() !== null) {
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

    /**
     * @throws NonUniqueResultException
     */
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
            $sqlWetterDaten = 'SELECT stamp AS stamp, g_lower AS irr_lower, g_upper AS irr_upper, pt_avg AS panel_temp, at_avg as ambient_temp FROM '.$weatherStation->getWeatherStation()->getDbNameWeather()." WHERE (`stamp` BETWEEN '$from' AND '$to') AND (g_lower > 0 OR g_upper > 0)";
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

            foreach ($weatherArray[$currentWeatherStation->getDatabaseIdent()] as $weather) {
                $stamp = $weather['stamp'];
                $openWeather = false; ### temporäre deaktivierung OpenWeather
                ###$openWeather = $this->openWeatherRepo->findTimeMatchingOpenWeather($anlage, date_create($stamp));


                for ($unit = $group->getUnitFirst(); $unit <= $group->getUnitLast(); ++$unit) {
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
                    $irrUpper = (float) $weather['irr_upper'] - ((float) $weather['irr_upper'] / 100 * $shadow_loss);    // Strahlung an obern (Ost) Sensor
                    $irrLower = (float) $weather['irr_lower'] - ((float) $weather['irr_lower'] / 100 * $shadow_loss);    // Strahlung an unterem (West) Sensor

                    // Strahlung berechnen, für Analgen die KEINE 'Ost/West' Ausrichtung haben
                    if ($anlage->getUseLowerIrrForExpected()) {
                        $irr = $irrLower;
                    } else {
                        $irr = $this->functions->calcIrr($irrUpper, $irrLower, $stamp, $anlage, $group, $currentWeatherStation, $groupMonth);
                    }
                    $irr = $irr - ($irr / 100 * $shadow_loss);

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

                                // Berechne anhand der gemessenen Umgebungstemperatur, mit hilfe der NREL Methode, die Modul Temperatur
                            } else {
                                // Wenn weder Umgebungs noch Modul Temperatur vorhanden, dann nutze Daten aus Open Weather (sind nur Stunden weise vorhanden)
                                if ($anlage->getAnlId() == '183' ) {  // im Moment nur für REGebeng
                                    switch ($anlage->getAnlId() == '183') {
                                        case '183':
                                            $windSpeed = 4; // ReGebeng – gemittelte Daten aus OpenWeather
                                            $airTemp = 24; // ReGebeng – gemittelte Daten aus OpenWeather
                                        break;
                                        case 'xx':
                                            $windSpeed = 1; //
                                            $airTemp = 24; //
                                        break;
                                    }

                                    #$windSpeed = $openWeather->getWindSpeed();
                                    #$airTemp = $openWeather->getTempC();

                                    // Calculate pannel temperatur by NREL
                                    $pannelTemp = round($this->irradiationService->tempCellNrel($anlage, $windSpeed, $airTemp, $irr), 2);

                                    // Correct Values by modul temperature
                                    $expPowerDcHlp = $expPowerDcHlp * $modul->getModuleType()->getTempCorrPower($pannelTemp);
                                    $expCurrentDcHlp = $expCurrentDcHlp * $modul->getModuleType()->getTempCorrCurrent($pannelTemp);
                                    $expVoltageDcHlp = $expVoltageDcHlp * $modul->getModuleType()->getTempCorrVoltage($pannelTemp);
                                }
                            }
                        }


                        if ($anlage->getSettings()->getEpxCalculationByCurrent()) {
                            // Calculate DC power by current and voltage
                            $expPowerDcHlp = $expCurrentDcHlp * $expVoltageDcHlp / 4000;
                        }
                        // degradation abziehen (degradation * Betriebsjahre).
                        $expCurrentDcHlp = $expCurrentDcHlp - $expCurrentDcHlp  * ($modul->getModuleType()->getDegradation() * $betriebsJahre / 100);
                        $expPowerDcHlp = $expPowerDcHlp - $expPowerDcHlp * ($modul->getModuleType()->getDegradation() * $betriebsJahre / 100);

                        $expPowerDc += $expPowerDcHlp;
                        $expCurrentDc += $expCurrentDcHlp;
                        $limitExpPower += $limitExpPowerHlp;
                        $limitExpCurrent += $limitExpCurrentHlp;
                        $expVoltage += $expVoltageDcHlp;
                    }
                    $expVoltage = count($modules) !== 0 ? $expVoltage / count($modules) : 0;

                    // Verluste auf der DC Seite brechnen
                    // Kabel Verluste + Sicherheitsverlust
                    $loss = $group->getCabelLoss() + $group->getSecureLoss();

                    // Verhindert 'diff by zero'
                    if ($loss != 0) {
                        $expPowerDc = $expPowerDc - $expPowerDc * ($loss / 100);
                        $expCurrentDc = $expCurrentDc - $expCurrentDc * ($loss / 100);
                    }

                    // Limitierung durch Modul prüfen und entsprechend abregeln
                    $expCurrentDc = min($expCurrentDc, $limitExpCurrent);

                    // AC Expected Berechnung
                    // Umrechnung DC nach AC
                    $expNoLimit = $expPowerDc - $expPowerDc * ($group->getFactorAC() / 100);

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
                        'exp_voltage' => round($expVoltage, 6),
                    ];
                }
            }
        }
        return $resultArray;
    }
    // MS 07/2023
    public function calcExpectedforForecast(Anlage $anlage, $decarray ): array {

        // $aktuellesJahr = date("Y",time());
        // $betriebsJahre = $aktuellesJahr - $anlage->getAnlBetrieb()->format('Y'); // betriebsjahre werden nicht berücksichtigt
        $resultArray = [];
        $theoYear = $expEvuSumYear = $expEvuSumDay =  $Fttheohrday =  $FttheoYear = $expEvuSum = 0;
        $pnomsgl = $anlage->getPnom() / 2;
        $pnomall = $anlage->getPnom() ;
        $kwp = $anlage->getKwPeak();
        $modulisbif = false; // Sollte aus der Modul DB kommen
        $TcellTypDay = $theoday = $irrYear = $irrDay = 0;

        if (count($decarray) > 0) {
        // Eerstelle Jahres Werte
            foreach ($decarray as $key_out => $val_out) {

                foreach ($val_out as $key_y => $valy) {
                            isset($valy['TMP']) ? $airTemp = $valy['TMP'] : $airTemp = '0.0';
                            isset($valy['FF']) ? $windSpeed = $valy['FF'] : $windSpeed = '0.0';
                            isset($valy['DOY']) ? $doy = $valy['DOY'] : $doy = '0';
                        if ($anlage->getIsOstWestAnlage()) {

                        if ($modulisbif) {
                            isset($valy['OSTWEST']['RGESBIF_UPPER']) ? $irrUpper = round($valy['OSTWEST']['RGESBIF_UPPER'], 2) : $irrUpper = '0.0';
                            isset($valy['OSTWEST']['RGESBIF_LOWER']) ? $irrLower = round($valy['OSTWEST']['RGESBIF_LOWER'], 2) : $irrLower = '0.0';
                        } else {
                            isset($valy['OSTWEST']['RGES_UPPER']) ? $irrUpper = round($valy['OSTWEST']['RGES_UPPER'], 2) : $irrUpper = '0.0';
                            isset($valy['OSTWEST']['RGES_LOWER']) ? $irrLower = round($valy['OSTWEST']['RGES_LOWER'], 2) : $irrLower = '0.0';
                        }

                        $Tcell = round($this->irradiationService->tempCellNrel($anlage, $windSpeed, $airTemp, $irrUpper), 2);
                        $TcellTyp = round($irrUpper / 1000 * $Tcell,2);
                        $TcellTypDay += $TcellTyp;
                        $irrYear += $irrUpper ; // W/m
                    } else {

                        if ($modulisbif) {
                            isset($valy['SUED']['RGESBIF']) ? $irr = round($valy['SUED']['RGESBIF'], 2) : $irr = '0.0';
                        } else {
                            isset($valy['SUED']['RGES']) ? $irr = round($valy['SUED']['RGES'], 2) : $irr = '0.0';
                        }
                        $Tcell = round($this->irradiationService->tempCellNrel($anlage, $windSpeed, $airTemp, $irr), 2);
                        $TcellTyp = round($irr / 1000 * $Tcell,2);
                        $TcellTypDay += $TcellTyp;
                        $irrYear += $irr; // W/m
                    }

                }

            }
            $irrYearkWh = $irrYear / 1000;
            $tcell_avg = round($TcellTypDay / $irrYearkWh,2);
            $irrUpper = $irrLower = $irr = 0;
            // Erstelle die Tages Erträge
            foreach ($decarray as $keyout => $valout) {

                foreach ($valout as $key => $val) {
                    isset($val['TMP']) ? $airTemp = $val['TMP'] : $airTemp = '0.0';
                    isset($val['FF']) ? $windSpeed = $val['FF'] : $windSpeed = '0.0';
                    isset($val['DOY']) ? $doy = $val['DOY'] : $doy = '0';

                    if ($anlage->getIsOstWestAnlage()) {

                        if ($modulisbif) {
                            isset($val['OSTWEST']['RGESBIF_UPPER']) ? $irrUpper = round($val['OSTWEST']['RGESBIF_UPPER'], 2) : $irrUpper = '0.0';
                            isset($val['OSTWEST']['RGESBIF_LOWER']) ? $irrLower = round($val['OSTWEST']['RGESBIF_LOWER'], 2) : $irrLower = '0.0';
                        } else {
                            isset($val['OSTWEST']['RGES_UPPER']) ? $irrUpper = round($val['OSTWEST']['RGES_UPPER'], 2) : $irrUpper = '0.0';
                            isset($val['OSTWEST']['RGES_LOWER']) ? $irrLower = round($val['OSTWEST']['RGES_LOWER'], 2) : $irrLower = '0.0';
                        }
                        $Tcell = round($this->irradiationService->tempCellNrel($anlage, $windSpeed, $airTemp, $irrUpper), 2);
                        $Ft = round(1 - ($tcell_avg - $Tcell) * -0.34/100,2);// FT Faktor

                    } else {

                        if ($modulisbif) {
                            isset($val['SUED']['RGESBIF']) ? $irr = round($val['SUED']['RGESBIF'], 2) : $irr = '0.0';
                        } else {
                            isset($val['SUED']['RGES']) ? $irr = round($val['SUED']['RGES'], 2) : $irr = '0.0';
                        }
                        $Tcell = round($this->irradiationService->tempCellNrel($anlage, $windSpeed, $airTemp, $irr), 2);
                        $Ft = round(1 - ($tcell_avg - $Tcell) * -0.34/100,2); // FT Faktor
                    }

                    // Weiter für Irr day per hour
                    if ($irr > 0  or $irrUpper > 0 or $irrLower > 0) {

                        foreach ($anlage->getGroups() as $group) {
                            // Monatswerte für diese Gruppe laden
                            /** @var AnlageGroupMonths $groupMonth */
                            $modules = $group->getModules();
                            $expPowerDc = 0;

                            // Verschattungsverlusste? werden hier noch nicht berücksichtigt.
                            /*
                            $shadow_loss = $group->getShadowLoss();
                            if ($irr <= 100) {
                                $shadow_loss = $shadow_loss * 0.0; // 0.05
                            } elseif ($irr <= 200) {
                                $shadow_loss = $shadow_loss * 0.0; // 0.21
                            } elseif ($irr <= 400) {
                                $shadow_loss = $shadow_loss * 0.35;
                            } elseif ($irr <= 600) {
                                $shadow_loss = $shadow_loss * 0.57;
                            } elseif ($irr <= 800) {
                                $shadow_loss = $shadow_loss * 0.71;
                            } elseif ($irr <= 1000) {
                                $shadow_loss = $shadow_loss * 0.8;
                            }
                            $irr = $irr ; // - ($irr / 100 * $shadow_loss) ;
                            */

                            foreach ($modules as $modul) {

                                if ($anlage->getIsOstWestAnlage()) {
                                    // Ist 'Ost/West' Anlage, dann nutze $irrUpper (Strahlung Osten) und $irrLower (Strahlung Westen) und multipliziere mit der Anzahl Strings Ost / West
                                    // Power
                                    $expPowerDcHlp = $modul->getModuleType()->getFactorPower($irrUpper) * $modul->getNumStringsPerUnitEast() * $modul->getNumModulesPerString() / 1000 ; // Ost
                                    $expPowerDcHlp += $modul->getModuleType()->getFactorPower($irrLower) * $modul->getNumStringsPerUnitWest() * $modul->getNumModulesPerString() / 1000 ; // West
                                    // Current
                                    $expCurrentDcHlp = $modul->getModuleType()->getFactorCurrent($irrUpper) * $modul->getNumStringsPerUnitEast(); // Ost // nicht durch 4 teilen, sind keine Ah, sondern A
                                    $expCurrentDcHlp += $modul->getModuleType()->getFactorCurrent($irrLower) * $modul->getNumStringsPerUnitWest(); // West // nicht durch 4 teilen, sind keine Ah, sondern A
                                    // Voltage
                                    $expVoltageDcHlp = $modul->getModuleType()->getExpVoltage($irrUpper) * $modul->getNumModulesPerString();
                                } else {
                                    // Ist keine 'Ost/West' Anlage
                                    // Power
                                    $expPowerDcHlp = $modul->getModuleType()->getFactorPower($irr) * $modul->getNumStringsPerUnit() * $modul->getNumModulesPerString() / 1000 ;
                                    // Current
                                    $expCurrentDcHlp = $modul->getModuleType()->getFactorCurrent($irr) * $modul->getNumStringsPerUnit(); // nicht durch 4 teilen, sind keine Ah, sondern A
                                    // Voltage
                                    $expVoltageDcHlp = $modul->getModuleType()->getExpVoltage($irr) * $modul->getNumModulesPerString();
                                }

                                if ($anlage->getSettings()->getEpxCalculationByCurrent()) {
                                    $expPowerDcHlp = $expCurrentDcHlp * $expVoltageDcHlp / 4000;
                                }
                                $expPowerDc += $expPowerDcHlp;
                            }
                            // Verluste auf der DC Seite brechnen
                            // Kabel Verluste + Sicherheitsverlust
                            $loss = $group->getCabelLoss() + $group->getSecureLoss();

                            // Verhindert 'diff by zero'
                            if ($loss != 0) {
                                $expPowerDc = $expPowerDc - ($expPowerDc / 100 * $loss);
                            }

                            // Umrechnung DC nach AC
                            $expNoLimit = $expPowerDc  - ($expPowerDc / 100 * $group->getFactorAC()) ;

                            // Prüfe ob Abriegelung gesetzt ist, wenn ja, begrenze den Wert auf das maximale.
                            if ($group->getLimitAc() > 0) {
                                ($expNoLimit > $group->getLimitAc()) ? $expPowerAc = $group->getLimitAc() : $expPowerAc = $expNoLimit;
                            } else {
                                $expPowerAc = $expNoLimit;
                            }
                            // Berechne den Expected für das GRID (evu)
                            $expEvu = $expPowerAc  - ($expPowerAc / 100 * $group->getGridLoss());
                            $expEvuSum += $expEvu;
                        }

                        if ($anlage->getIsOstWestAnlage()) {
                            $hj = round(($irrUpper / 1000 * $pnomsgl + $irrLower  / 1000 * $pnomsgl) / $pnomall, 2);
                            $theohr = ($pnomall * $hj) ;
                            $Fttheohr = $theohr * $Ft; // Theoretical * FT Faktor
                            $Fttheohrday += $Fttheohr;
                            $theoday +=  $theohr;
                            $ex4 = $expEvuSum * 4; //* 4;
                            $expEvuSumDay += $ex4; // Wh
                            $irrDay += ($irrUpper + $irrLower) / 2; // W/m
                            $expEvuSum = 0;
                            $theohr = 0;
                            $hj = 0;
                            $irrUpper = 0;
                        } else {
                            $hj = round(($irr / 1000 * $pnomsgl + $irr  / 1000 * $pnomsgl) / $pnomall, 2);
                            $theohr = ($pnomall * $hj) ;
                            $theoday +=  $theohr;
                            $Fttheohr = $theohr * $Ft; // Theoretical * FT Faktor
                            $Fttheohrday += $Fttheohr;
                            $ex4 = $expEvuSum * 4; //* 4;
                            $expEvuSumDay += $ex4; // Wh
                            $irrDay += $irr; // W/m
                            $expEvuSum = 0;
                            $theohr = 0;
                            $hj = 0;
                            $irr = 0;
                        }

                    }

                }

                $ContractualGuarantiedPower = $anlage->getContractualGuarantiedPower();
                $expEvuSumYear += $expEvuSumDay;
                $theoYear += $theoday;
                $FttheoYear += $Fttheohrday;

                $hjday = round(($irrDay / 1000 * $pnomsgl + $irrDay / 1000 * $pnomsgl) / ($pnomall), 2);

                $pr_clas_skaliert = round(($expEvuSumDay / $kwp) / ($irrDay / 1000) * 100,2);
                $pr_clas_komuliert = round(($expEvuSumYear / $kwp) / ($irrYear / 1000) * 100,2);
                $pr_theo_skaliert = round(($expEvuSumDay / $theoday)  * 100,2);
                $pr_theo_komuliert = round(($expEvuSumYear / $theoYear)  * 100,2);
                $pr_theo_ft_skaliert = round(($expEvuSumDay / $Fttheohrday ) * 100,2);
                $pr_theo_ft_komuliert = round(($expEvuSumYear  / $FttheoYear) * 100,2);

                // Ein Fallback, falls der skalierte PR über 100 % liegt
                if (round($pr_clas_skaliert,0) > 100 ) {
                    $prfz = round($pr_clas_skaliert,0) / 100;
                    $expEvuSumDay =  $expEvuSumDay / $prfz ;
                }

                    if ($doy < 365) {
                        // Speichern der Tageswerte Werte in ein Array
                        $resultArray[$doy] = [
                            'doy' => $doy,
                            'irrday' => $irrDay,
                            'tcell' => $Tcell,
                            'hj' => $hjday,
                            'pr_clas_skal' =>  $pr_clas_skaliert,
                            'pr_clas_komu' => $pr_clas_komuliert,
                            'pr_theo_skal' =>  $pr_theo_skaliert,
                            'pr_theo_komu' => $pr_theo_komuliert,
                            'pr_theo_ft_skal' => $pr_theo_ft_skaliert,
                            'pr_theo_ft_komu' => $pr_theo_ft_komuliert,
                            'pnom' => $pnomall,
                            'exp_theo_day' => round($theoday,0),
                            'exp_evu_day' => round($expEvuSumDay, 0),
                            'fkt_day' => number_format($expEvuSumDay /  $expEvuSumYear,8,".","")
                        ];
                    } else {
                        // Speichern der Tageswerte Werte in Array
                        $resultArray[$doy] = [
                            'doy' => $doy,
                            'irrday' => $irrDay,
                            'tcell' => $Tcell,
                            'hj' => $hjday,
                            'pr_clas_skal' =>  $pr_clas_skaliert,
                            'pr_clas_komu' => $pr_clas_komuliert,
                            'pr_theo_skal' =>  $pr_theo_skaliert,
                            'pr_theo_komu' => $pr_theo_komuliert,
                            'pr_theo_ft_skal' => $pr_theo_ft_skaliert,
                            'pr_theo_ft_komu' => $pr_theo_ft_komuliert,
                            'pnom' => $pnomall,
                            'exp_theo_day' => round($theoday,0),
                            'exp_evu_day' => round($expEvuSumDay, 0),
                            'fkt_day' => number_format($expEvuSumDay /  $expEvuSumYear,8,".","")
                        ];

                        $resultArray['yearsum'] = [
                            'irryear' => round($irrYear,3) ,
                            'exp_theo_year' => round($theoYear,0),
                            'exp_evu_year' => round($expEvuSumYear, 0),
                            'tcell_avg' => $tcell_avg,
                            'CGP' => $ContractualGuarantiedPower
                        ];
                    }

                $irrDay = 0;
                $expEvuSum = 0;
                $expEvuSumDay = 0;
                $theoday = 0;
                $Fttheohrday = 0;
            }

            return $resultArray;

        }

        return false;

    }
}