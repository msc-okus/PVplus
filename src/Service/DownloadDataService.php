<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlageAvailability;
use App\Entity\AnlagenPR;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\PRRepository;

class DownloadDataService
{
    use G4NTrait;

    private AnlageAvailabilityRepository $availabilityRepo;
    private PRRepository $prRepository;

    public function __construct(AnlageAvailabilityRepository $availabilityRepo, PRRepository $prRepository)
    {
        $this->availabilityRepo = $availabilityRepo;
        $this->prRepository = $prRepository;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $intervall
     * @param $headlineDate
     * @return string
     */
    public function getAllSingleSystemData($anlage, $from, $to, $intervall, $headlineDate)
    {
        $conn = self::connectToDatabase();
        $dbnameist = $anlage->getDbNameIst();
        $dbnamesoll = $anlage->getDbNameAcSoll();
        $dbnamedcsoll = $anlage->getDbNameDcSoll();
        $dbnamews = $anlage->getDbNameWeather();
        $ht2 = "<table id='statistics' class='table'><thead><tr><th>$headlineDate</th><th>Irradiation<br>[W/qm]</th><th>PT &Oslash;<br>[°C]</th><th>Grid AC<br>[kWh]</th><th>Inv. AC<br>[kWh]</th><th>Inv. exp AC<br>[kWh]</th><th>Inv. DC<br>[kWh]</th><th>Inv. exp DC<br>[kWh]</th>";
        if ($intervall == '%d.%m.%Y'){
            $ht2 .= "<th>Availability 1<br>[%]</th><th>Availability 2<br>[%]</th><th>PR<br>[%]</th>";
        }
        $ht2 .= "</tr></thead><tbody>";
        // Actual AC & DC
        $sql = "SELECT DATE_FORMAT( a.stamp, '$intervall') AS form_date, sum(b.wr_pac) as act_power_ac, sum(b.wr_pdc) as act_power_dc, SUM(b.e_z_evu) as power_grid
            FROM (db_dummysoll a left JOIN $dbnameist b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '$from' and '$to'AND b.wr_pac > 0 GROUP by form_date ORDER BY form_date";
        //dump($sql);
        $actAcDcPower = [];
        $resAct = $conn->query("$sql");
        if ($resAct->num_rows > 0) {
            while ($rowAct = $resAct->fetch_assoc()) {
                $date_time = $rowAct['form_date'];
                // Power GRID muss durch Anzahl der Gruppen geteilt werden, weil der Wert für die gesamte Anlage in jeder Gruppe gespeichert ist. Er darf aber nur einmal gezählt werden.
                $actAcDcPower[$date_time]['actPowerGrid'] = round($rowAct["power_grid"]  / $anlage->getAcGroups()->count(), 2);
                $actAcDcPower[$date_time]['actPowerAc'] = round($rowAct["act_power_ac"], 2);
                $actAcDcPower[$date_time]['actPowerDc'] = round($rowAct["act_power_dc"], 2);
            }
        }

        // wenn Tagesdaten, dann Verfügbarkeit laden
        $prArray = [];
        if ($intervall == '%d.%m.%Y'){
            /** @var AnlagenPR [] $prs */
            $prs = $this->prRepository->findPrAnlageDate($anlage, $from, $to);
            foreach ($prs as $pr) {
                $date_time = $pr->getstamp()->format('d.m.Y');
                $prArray[$date_time]['first'] = round($pr->getPlantAvailability(),2);
                $prArray[$date_time]['second'] = round($pr->getPlantAvailabilitySecond(),2);
                /** TODO: prüfen ob richtiger PR Wert */
                $prArray[$date_time]['pr'] = round($pr->getPrEvuProz(),2); ######################## ????????????????????????
            }
        }


        // Expected AC & DC
        $sql = "SELECT DATE_FORMAT( a.stamp, '$intervall' ) AS form_date, sum(b.dc_exp_power) as exp_power_dc, sum(b.ac_exp_power) as exp_power_ac
            FROM (db_dummysoll a left JOIN $dbnamedcsoll b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '$from' and '$to' GROUP by form_date ORDER BY form_date";
        #dump($sql);
        $resExpDc = $conn->query($sql);
        $expPower = [];
        if ($resExpDc->num_rows > 0) {
            while ($rowExp = $resExpDc->fetch_assoc()) {
                $date_time = $rowExp['form_date'];
                $expPower[$date_time]['expPowerAc'] = round($rowExp["exp_power_ac"], 2);
                $expPower[$date_time]['expPowerDc'] = round($rowExp["exp_power_dc"], 2);
            }
        }

        // Wetter Daten laden
        $sql2ss = "SELECT a.stamp as orderStamp, DATE_FORMAT(a.stamp, '$intervall') AS form_date, SUM(b.g_upper) as irr_upper_pannel, SUM(b.g_lower) as irr_lower_pannel, AVG(b.wind_speed) as avgwind, AVG(b.pt_avg) as avgpt, b.anl_id 
                    FROM (db_dummysoll a left JOIN $dbnamews b ON a.stamp = b.stamp) 
                    WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY form_date ORDER BY a.stamp";
        #dump($sql2ss);
        $res01 = $conn->query($sql2ss);
        if ($res01->num_rows > 0) {
            while ($ro01 = $res01->fetch_assoc()) {
                $ptavgi     = round($ro01["avgpt"]);       // Pannel Temperature
                $irr_upper  = round($ro01["irr_upper_pannel"]);     // Einstrahlung upper Pannel
                $irr_lower  = round($ro01["irr_lower_pannel"]);
                $irr_helper = ($irr_upper + $irr_lower) / 2;
                $date_time  = $ro01["form_date"];   // Datum
                // Actual AC & DC
                $powerGrid  = $actAcDcPower[$date_time]['actPowerGrid'];
                $actPowerAc = $actAcDcPower[$date_time]['actPowerAc'];
                $actPowerDc = $actAcDcPower[$date_time]['actPowerDc'];
                // Expected AC
                $expPowerAc = $expPower[$date_time]['expPowerAc'];
                // Expected DC
                ($irr_helper <= 2) ? $expPowerDc = 0 : $expPowerDc = $expPower[$date_time]['expPowerDc'];
                // Availability

                ($anlage->getAnlDbUnit() == "w") ? $actPowerAc = round($actPowerAc / 1000 / 4, 2) : $actPowerAc = round($actPowerAc, 2);
                $ht2 .= "<tr><td>$date_time</td><td>$irr_upper</td><td>$ptavgi</td><td>$powerGrid</td><td>$actPowerAc</td><td>$expPowerAc</td><td>$actPowerDc</td><td>$expPowerDc</td>";
                if ($intervall == '%d.%m.%Y'){
                    //dump($availability);
                    $ht2 .= "<td>".$prArray[$date_time]['first']."</td><td>".$prArray[$date_time]['second']."</td><td>".$prArray[$date_time]['pr']."</td>";
                }
                $ht2 .= "</tr>";
            }
        }

        $ht2 .= "</tbody></table>";
        $conn->close();

        return $ht2;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $intervall
     * @param $headlineDate
     * @return string
     */
    public function getIrrSingleSystemData($anlage, $from, $to, $intervall, $headlineDate) {
        $conn = self::connectToDatabase();
        $dbnamews = $anlage->getDbNameWeather();
        $sql2 = "SELECT DATE_FORMAT( a.stamp, '$intervall' ) AS form_date , AVG(b.pt_avg) AS sum_pt_avg, SUM(b.gi_avg) as sum_avg , SUM(b.gmod_avg) as sum_gmod , AVG(b.wind_speed) AS sum_wind_speed, b.anl_id
                    FROM (db_dummysoll a left JOIN $dbnamews b ON a.stamp = b.stamp) 
                    WHERE a.stamp BETWEEN '$from' and '$to' GROUP by form_date ORDER BY form_date";
        $res = $conn->query($sql2);
        $ht2 = "<table id='statistics' class='table'><thead><tr><th>$headlineDate</th><th>GI</br>[W/qm]</th><th>GMOD</br>[W/qm]</th><th>PT</br>[°C]</th></tr></thead><tbody>";
        while ($ro = $res->fetch_assoc()) {
            $sumAvg = round($ro["sum_avg"],2);
            if (!$sumAvg) { $sumAvg = 0; }
            $sumGmod = round($ro["sum_gmod"],2);
            if (!$sumGmod) { $sumGmod = 0; }
            $sumPtAvg = round($ro["sum_pt_avg"],2);
            if (!$sumPtAvg) { $sumPtAvg = 0; }

            $sumAvg = str_replace(',', '.', $sumAvg);
            $sumGmod = str_replace(',', '.', $sumGmod);
            $sumPtAvg = str_replace(',', '.', $sumPtAvg);

            $sumAvg = str_replace('#', "0.00", $sumAvg);
            $sumGmod = str_replace('#', "0.00", $sumGmod);
            $sumPtAvg = str_replace('#', "0.00", $sumPtAvg);
            $stamp = $ro["form_date"];
            $ht2 .= "<tr><td>$stamp</th><td>$sumAvg</td><td>$sumGmod</td><td>$sumPtAvg</td></tr>";
        }
        $ht2 .= "</tbody></table>";
        return $ht2;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $intervall
     * @param $headlineDate
     * @return string
     */
    public function getAcSingleSystemData($anlage, $from, $to, $intervall, $headlineDate) {
        $conn = self::connectToDatabase();
        $dbnameist = $anlage->getDbNameIst();
        $dbnamesoll = $anlage->getDbNameDcSoll();
        $arrayout1a = [];
        // Soll Daten laden (nur AC im Moment)
        $sql2sb = "SELECT DATE_FORMAT( a.stamp, '$intervall' ) AS form_date, sum(b.ac_exp_power) as exp_power_ac 
                    FROM (db_dummysoll a left JOIN $dbnamesoll b ON a.stamp = b.stamp)
                    WHERE a.stamp BETWEEN '$from' and '$to' GROUP by form_date ORDER BY form_date";

        $res02 = $conn->query($sql2sb);
        $dds = 0;
        if ($res02->num_rows > 0) {
            while ($ro02 = $res02->fetch_assoc()) {
                $arrayout1a[$dds]['DATE'] = $ro02["form_date"];
                $arrayout1a[$dds]['EXP'] = round($ro02["exp_power_ac"], 2);
                $dds++;
            }
        }
        // Ist Daten laden
        $sql2sc = "SELECT DATE_FORMAT( a.stamp, '$intervall' ) AS form_date, sum(b.wr_pac) as act_power_ac FROM (db_dummysoll a left JOIN $dbnameist b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' GROUP by form_date ORDER BY form_date";
        $res03 = $conn->query($sql2sc);
        $dds = 0;
        if ($res03->num_rows > 0) {
            while ($ro03 = $res03->fetch_assoc()) {
                $arrayout1a[$dds]['ACTAC'] = round($ro03["act_power_ac"], 2);
                $dds++;
            }
        }
        $ht2 = "<table id='statistics' class='table'><thead><tr><th>$headlineDate</th><th>Act AC</br>[kWh]</th><th>Exp AC[kWh]</th></tr></thead><tbody>";
        foreach ($arrayout1a as $wert) {
            $datum = $wert['DATE'];
            $actac = $wert['ACTAC'];
            $exp = $wert['EXP'];
            ($anlage->getAnlDbUnit() == "w") ? $actac = round($actac / 1000 / 4, 2) : $actac = round($actac, 2);

            $ht2 .= "<tr><td>$datum</th><td>$actac</td><td>$exp</td></tr>";
        }

        $ht2 .= "</tbody></table>";
        return $ht2;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $intervall
     * @param $headlineDate
     * @return string
     */
    public function getDcSingleSystemData($anlage, $from, $to, $intervall, $headlineDate) {
        $conn = self::connectToDatabase();
        $dbnameist = $anlage->getDbNameIst();
        $arrayout1a = [];
        // Ist Daten laden
        $sql2sc = "SELECT DATE_FORMAT( a.stamp, '$intervall' ) AS form_date, sum(b.wr_pdc) as act_power_dc 
                    FROM (db_dummysoll a left JOIN $dbnameist b ON a.stamp = b.stamp) 
                    WHERE a.stamp BETWEEN '$from' and '$to' GROUP by form_date ORDER BY form_date";
        $res03 = $conn->query($sql2sc);
        $dds = 0;
        if ($res03->num_rows > 0) {
            while ($row = $res03->fetch_assoc()) {
                $arrayout1a[$dds]['DATE'] = $row["form_date"];
                $arrayout1a[$dds]['ACTDC'] = round($row["act_power_dc"], 2);
                $dds++;
            }
        }
        $ht2 = "<table id='statistics' class='table'><thead><tr><th>$headlineDate</th><th>Act DC</br>[kWh]</th></tr></thead><tbody>";
        foreach ($arrayout1a as $wert) {
            $datum = $wert['DATE'];
            $actac = $wert['ACTDC'];
            ($anlage->getAnlDbUnit() == "w") ? $actac = round($actac / 1000 / 4, 2) : $actac = round($actac, 2);
            $ht2 .= "<tr><td>$datum</th><td>$actac</td></tr>";
        }
        $ht2 .= "</tbody></table>";

        return $ht2;
    }

    public function getAvailabilitySingleSystemData($anlage, $from, $to, $intervall, $headlineDate)
    {
        if ($intervall == "%d.%m.%Y") {
            /** @var AnlageAvailability [] $availabilitys */
            $availabilitys = $this->availabilityRepo->findAvailabilityAnlageDate($anlage, $from, $to);
            /** @var AnlagenPR [] $prs */
            $prs = $this->prRepository->findPrAnlageDate($anlage, $from, $to);
            $ht2 = "
<table id='statistics' class='table'>
    <thead>
        <tr>
            <th>$headlineDate</th>
            <th>Plant Availability 1 [%]</th>
            <th>Plant Availability 2 [%]</th>
        </tr>
    </thead>
    <tbody>";

            foreach ($prs as $pr) {
                $ht2 .= "
        <tr>
            <td>".$pr->getstamp()->format('Y-m-d')."</td>
            <td>".round($pr->getPlantAvailability(),2)."</td>
            <td>".round($pr->getPlantAvailabilitySecond(),2)."</td>
        </tr>";
            }
            $ht2 .= "
    </tbody>
</table>";
        } else {
            $ht2 = "<h1>Only available for interval 'Day'.</h1>";
        }


        return $ht2;
    }
}