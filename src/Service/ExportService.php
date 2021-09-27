<?php


namespace App\Service;


use App\Entity\Anlage;
use App\Entity\AnlageAcGroups;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\PRRepository;
use DateTime;
use PDO;

class ExportService
{
    use G4NTrait;

    private FunctionsService $functions;
    private PRRepository $PRRepository;
    private AnlageAvailabilityRepository $availabilityRepo;

    public function __construct(FunctionsService $functions, PRRepository $PRRepository, AnlageAvailabilityRepository $availabilityRepo)
    {
        $this->functions = $functions;
        $this->PRRepository = $PRRepository;
        $this->availabilityRepo = $availabilityRepo;
    }

    public function gewichtetTagesstrahlung(Anlage $anlage, DateTime $from, DateTime $to):string
    {
        $tempArray = [];
        $help = '<tr><th></th>';
        $output = "<b>" . $anlage->getAnlName() . "</b><br>";
        $output .= "<div class='table-scroll'><table><thead><tr><th>Datum</th>";
        foreach ($anlage->getAcGroups() as $groupAC) {
            $output .= "<th>" . $groupAC->getAcGroupName() . "</th><th></th><th></th>";
            $help   .= "<th><small>Irr [kWh/qm]</small></th><th></th><th><small>gewichtete TheoPower mit TempCorr [kWh]</small></th>";
        }
        $output .= "<td>Mittelwert Luft Temp.</td><td>Verfügbarkeit</td><td>gewichtete Strahlung</td><td>gewichtete TheoPower mit TempCorr</td></tr>";
        $help   .= "<td>°C</td><td>[%]</td><td>[kWh/qm]</td><td>[kWh]</td><td></td></tr>";
        $output .= $help . "</thead><tbody>";

        /** @var AnlageAcGroups $groupAC */
        /** @var DateTime $from */
        /** @var DateTime $to */
        for ($stamp = $from->format('U'); $stamp <= $to->format('U'); $stamp += 86400) {
            $gewichteteStrahlung = $gewichteteTheoPower = $gewichteteTheoPower2 = 0;
            $output .= "<tr>";
            $output .= "<td>".date('Y-m-d', $stamp)."</td>";

            // für jede AC Gruppe ermittele Wetterstation, lese Tageswert und gewichte diesen
            foreach ($anlage->getAcGroups() as $groupAC) {
                $weather = $this->functions->getWeather($groupAC->getWeatherStation(), date( 'Y-m-d 00:00', $stamp), date('Y-m-d 23:59', $stamp), null, null);
                $acPower = $this->functions->getSumAcPowerByGroup($anlage, date( 'Y-m-d 00:00', $stamp), date('Y-m-d 23:59', $stamp), $groupAC->getAcGroup());
                $tempArray[] = $weather['airTemp'];
                if ($groupAC->getIsEastWestGroup()) {
                    if ($weather['upperIrr'] > 0 && $weather['lowerIrr'] > 0) {
                        $irradiation = ($weather['upperIrr'] + $weather['lowerIrr']) / 2;
                    } elseif ($weather['upperIrr'] > 0) {
                        $irradiation = $weather['upperIrr'];
                    } else {
                        $irradiation = $weather['lowerIrr'];
                    }
                } else {
                    $irradiation = $weather['upperIrr'];
                }
                // TheoPower gewichtet berechnen
                $output .= "<td><small>" . round($weather['upperIrr'] / 1000 / 4,2) . "</small></td><td><small>" . round($weather['lowerIrr'] / 1000 / 4,2) . "</small></td><td><small>".round($acPower['powerTheo'],2)."</small></td>";

                // Aufsummieren der gewichteten Werte zum gesamt Wert
                $gewichteteTheoPower    += $acPower['powerTheo'];
                $gewichteteStrahlung    += $groupAC->getGewichtungAnlagenPR() * $irradiation;
                $availability            = $this->availabilityRepo->sumAvailabilityPerDay($anlage->getAnlId(), date('Y-m-d', $stamp));
            }
            $output .= "<td>" . self::mittelwert($tempArray) . "</td>";
            $output .= "<td>".round($availability,2)."</td>";
            $output .= "<td>".round($gewichteteStrahlung / 1000 / 4,2)."</td>";
            #$output .= "<td>".round($gewichteteTheoPower2,2)."</td>";
            $output .= "<td>".round($gewichteteTheoPower,2)."</td></tr>";
        }
        $output .= "</tbody></table></div>";
        return $output;
    }

    public function getFacPRData(Anlage $anlage, DateTime $from, DateTime $to = null):array
    {
        $conn = self::getPdoConnection();

        $export = [];
        $fromSql    = $from->format('Y-m-d 00:00');
        $toSql      = $to->format('Y-m-d 23:59');
        $nameArray  = $this->functions->getNameArray($anlage);
        $startDay   = strtotime($from->format('Y-m-d 00:00'));
        $endDay     = strtotime($to->format('Y-m-d 23:59'));

        //Export der PR Tageswerte (stamp, egrid, Pnorm_ft, Ft ??, )
        for ($dayStamp = $startDay; $dayStamp <= $endDay; $dayStamp += 86400) { //24*60*60 = 86400
            $localFrom  = date('Y-m-d 00:00', $dayStamp);
            $localTo    = date('Y-m-d 23:59', $dayStamp);
            $stamp      = date('Y-m-d', $dayStamp);
            $export[$stamp]['stamp']        = $stamp;
            $export[$stamp]['eGrid']        = $this->functions->getSumeGridMeter($anlage, $localFrom, $localTo);

            $sql = "SELECT sum(theo_power) as theoPower, avg(temp_corr) as Ft FROM " . $anlage->getDbNameIst() . " WHERE stamp BETWEEN '$localFrom' AND '$localTo'";
            $res = $conn->prepare($sql);
            $res->execute();
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_OBJ);
                //dump($row);
                $export[$stamp]['theoPower']    = (float)$row->theoPower;
                //$export[$stamp]['Ft']           = (float)$row->Ft;
            } else {
                $export[$stamp]['theoPower']    = null;
                //$export[$stamp]['Ft']           = null;
            }

            //$export[$stamp]['pa']               =  (float)$this->availabilityRepo->sumAvailabilityPerDay($anlage->getAnlId(), $stamp);

            $sql = "SELECT sum(g_upper) as upper, sum(g_lower) as lower, sum(g_horizontal) as horizontal FROM " . $anlage->getDbNameWeather() . " WHERE stamp BETWEEN '$localFrom' AND '$localTo'";
            $res = $conn->prepare($sql);
            $res->execute();
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_OBJ);
                //Strahlung berechnen
                if ($anlage->getIsOstWestAnlage()) {
                    //Strahlung (upper = Ost / lower = West)
                    $export[$stamp]['irr_mod'] = ($row->upper * $anlage->getPowerEast() + $row->lower * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest())  / 4; // Umrechnug zu Wh
                } else {
                    $export[$stamp]['irr_mod'] = $row->upper() / 4 ; // Umrechnug zu Wh
                }
            } else {
                $export[$stamp]['irr_mod'] = null;
            }
        }

        return $export;
    }

    public function getFacPAData(Anlage $anlage, DateTime $from, DateTime $to = null):array
    {
        $conn = self::getPdoConnection();

        $export = [];
        $fromSql    = $from->format('Y-m-d 00:00');
        $toSql      = $to->format('Y-m-d 23:59');
        $nameArray  = $this->functions->getNameArray($anlage);
        $startDay   = strtotime($from->format('Y-m-d 00:00'));
        $endDay     = strtotime($to->format('Y-m-d 23:59'));

        //Export der PR Tageswerte (stamp, egrid, Pnorm_ft, Ft ??, )
        for ($dayStamp = $startDay; $dayStamp <= $endDay; $dayStamp += 86400) { //24*60*60 = 86400
            $localFrom  = date('Y-m-d 00:00', $dayStamp);
            $localTo    = date('Y-m-d 23:59', $dayStamp);
            $stamp      = date('Y-m-d', $dayStamp);

            $availabilitys = $this->availabilityRepo->findBy(['anlage' => $anlage, 'stamp' => date_create($stamp)]);
            foreach($availabilitys as $availability) {
                $export[$stamp]['stamp']        = $stamp;
                //$export[$stamp]['Inverter']     = $nameArray[$availability->getInverter()];
                $export[$stamp][$nameArray[$availability->getInverter()].'_t']            = $availability->getCase2() - $availability->getCase3() - $availability->getCase4();
                $export[$stamp][$nameArray[$availability->getInverter()].'_ttheo']        = $availability->getControl() - $availability->getCase1();

            }


        }

        return $export;
    }

    public function getRawData(Anlage $anlage, DateTime $from = null, DateTime $to = null):string
    {
        $conn = self::getPdoConnection();
        $output = '';
        $fromSql = $from->format('Y-m-d 00:00');
        $toSql = $to->format('Y-m-d 23:59');
        $nameArray = $this->functions->getNameArray($anlage);

        $groups = $anlage->getGroups();
        $i = 1;
        $dcPNormPerInverter = [];
        foreach($groups as $group) {
            $dcPNormPerInverter[$i] = 0;
            foreach($group->getModules() as $module) {
                $dcPNormPerInverter[$i] += $module->getNumStringsPerUnit() * $module->getNumModulesPerString() * $module->getModuleType()->getPower();
            }
            $i++;
        }

        $sql = "SELECT * FROM " . $anlage->getDbNameIst() . " WHERE stamp >= '$fromSql' AND stamp <= '$toSql';";

        $res = $conn->prepare($sql);
        $res->execute();
        if ($res->rowCount() > 0) {
            $fp = fopen("daten ".$anlage->getAnlName()."-".$from->format('Y-m').".csv", 'a');
            $first = true;
            $i = 0;
            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                $export['timestamp']        = $row['stamp'];
                $export['inverter']         = $nameArray[$row['unit']];
                $export['p_ac']             = (float)$row['wr_pac'];
                $export['temp_corr']        = (float)$row['temp_corr'];
                $export['theo_power']       = (float)$row['theo_power'];
                $export['pNormInv']         = $dcPNormPerInverter[$row['unit']] / 1000; // Umrechnung von Wp auf kWp
                $irrAnlage                  = json_decode($row['irr_anlage'], true);
                $tempAnlage                 = json_decode($row['temp_anlage'], true);
                $windAnlage                 = json_decode($row['wind_anlage'], true);

                foreach ($irrAnlage as $key => $value){
                    $export[$key] = $value;
                }
                foreach ($tempAnlage as $key => $value){
                    $export[$key] = $value;
                }
                foreach ($windAnlage as $key => $value){
                    if (is_array($value)) {
                        foreach ($value as $subKey => $subValue) {
                            $export[$subKey] = $subValue;
                        }
                    } else {
                        $export[$key] = $value;
                    }
                }
                // erzeuge Headline bei erstem Durchlauf
                if ($first) {
                    $exportHeadline['timestamp']  = 'Timestamp';
                    //$exportHeadline['section']  = 'Section';
                    $exportHeadline['inverter']  = 'Inverter';
                    $exportHeadline['p_ac']  = 'Power_AC';
                    $exportHeadline['temp_corr']  = 'Temp_Corr';
                    $exportHeadline['theo_power']  = 'Theo_Power';
                    $exportHeadline['pNormInv'] = 'pNormInv';
                    foreach ($irrAnlage as $key => $value){
                        $exportHeadline[$key] = $key;
                    }
                    foreach ($tempAnlage as $key => $value){
                        $exportHeadline[$key] = $key;
                    }
                    foreach ($windAnlage as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $subKey => $subValue) {
                                $exportHeadline[$subKey] = $subKey;
                            }
                        } else {
                            $exportHeadline[$key] = $key;
                        }
                    }
                    fputcsv($fp, $exportHeadline);
                    unset($exportHeadline);
                }
                $first = false;
                fputcsv($fp, $export);
                unset($export);
                $i++;
                if ($i % 1000 == 0) {
                    fclose($fp);
                    $fp = fopen("daten ".$anlage->getAnlName()."-".$from->format('Y-m').".csv", 'a');
                }
            }
            fclose($fp);
        }
        $res = null;
        $conn = null;
        unset($res);

        return $output;
    }

}


