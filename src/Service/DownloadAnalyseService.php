<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlageAvailability;
use App\Entity\AnlagenPR;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\PRRepository;

use App\Entity\AnlagenPvSystMonth;
use App\Entity\AnlagenReports;

use App\Repository\Case5Repository;
use App\Repository\PvSystMonthRepository;
use App\Repository\ReportsRepository;
use App\Repository\AnlagenRepository;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use App\Reports\Monthly\DownloadReportMonthly;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;


use Doctrine\ORM\Query\ResultSetMappingBuilder;


class DownloadAnalyseService
{
    use G4NTrait;

    private AnlagenRepository $anlagenRepository;
    private PRRepository $prRepository;
    private Environment $twig;
    private ReportsRepository $downloadsRepository;
    private EntityManagerInterface $em;
    private MessageService $messageService;
    private PvSystMonthRepository $pvSystMonthRepo;
    private Case5Repository $case5Repo;
    private FunctionsService $functions;
    private NormalizerInterface $serializer;


    public function __construct(
        AnlageAvailabilityRepository $availabilityRepo,
        PRRepository $prRepository,
        AnlagenRepository $anlagenRepository,
        ReportsRepository $downloadsRepository,
        EntityManagerInterface $em,
        Environment $twig,
        MessageService $messageService,
        PvSystMonthRepository $pvSystMonthRepo,
        Case5Repository $case5Repo,
        FunctionsService $functions,
        NormalizerInterface $serializer
    )
    {
        $this->availabilityRepo = $availabilityRepo;
        $this->prRepository = $prRepository;
        $this->twig = $twig;
        $this->reportsRepository = $downloadsRepository;
        $this->functions = $functions;
        $this->em = $em;
        $this->messageService = $messageService;
        $this->pvSystMonthRepo = $pvSystMonthRepo;
        $this->case5Repo = $case5Repo;
        $this->serializer = $serializer;
    }



    /**
     * @param Anlage $anlage
     * @param $a
     * @param $b
     * @param $intervall
     * @return array
     */
    public function getAllSingleSystemData(Anlage $anlage, $year = 0, $month = 0, $timerange = 0)
    {


        #timerange = monthly or dayly table
        if($timerange == 1){
            if ($year != 0 && $month != 0) {
                $yesterday = strtotime("$year-$month-01");
            } else {
                $currentTime = G4NTrait::getCetTime();
                $yesterday = $currentTime - 86400 * 4;
            }

            $downloadMonth = date('m', $yesterday);
            $downloadYear = date('Y', $yesterday);
            $lastDayMonth = date('t', $yesterday);
            $from = "$downloadYear-$downloadMonth-01 00:00";
            $to = "$downloadYear-$downloadMonth-$lastDayMonth 23:59";
            $download = [];
            $download = $this->prRepository->findOneBy(['anlage' => $anlage, 'stamp' => date_create("$year-$month-$lastDayMonth")]);;
        }
        if($timerange == 2){
            $download = [];
            $download = $this->prRepository->findPRInMonth($anlage, "$month", "$year");
        }
        return $download;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $intervall
     * @return array
     */
    public function getDcSingleSystemData($anlage, $from, $to, $intervall) {
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
        foreach ($arrayout1a as $wert) {
            $datum = $wert['DATE'];
            $actdc = $wert['ACTDC'];
            ($anlage->getAnlDbUnit() == "w") ? $actdc = round($actdc / 1000 / 4, 2) : $actdc = round($actdc, 2);

            $output[] =
                [
                    'datum' => $datum,
                    'actdc' => $actdc,
                ];
        }

        return $output;
    }

    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $intervall
     * @return array
     */
    public function getEcpectedDcSingleSystemData($anlage, $from, $to, $intervall) {
        $conn = self::connectToDatabase();
        $dbnamesoll = $anlage->getDbNameDcSoll();

        // Expected DC
        $sql = "SELECT DATE_FORMAT( a.stamp, '$intervall' ) AS form_date, sum(b.dc_exp_power) as exp_power_dc, sum(b.ac_exp_power) as exp_power_ac 
            FROM (db_dummysoll a left JOIN $dbnamesoll b ON a.stamp = b.stamp) 
            WHERE a.stamp BETWEEN '$from' and '$to' GROUP by form_date ORDER BY form_date";
        //dump($sql);
        $resExpDc = $conn->query($sql);

        if ($resExpDc->num_rows > 0) {
            while ($rowExp = $resExpDc->fetch_assoc()) {
                $date_time = $rowExp['form_date'];
                #$expPower[$date_time]['expPowerDc'] = round($rowExp["exp_power_dc"], 2);
                $output[] =
                    [
                        'datum' => $date_time,
                        'expdc' => round($rowExp["exp_power_dc"], 2),
                    ];
            }
        }

        return $output;
    }


    /**
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $intervall
     * @param $headlineDate
     * @return array
     */
    public function getAllSingleSystemDataForDay($anlage, $from, $to, $intervall, $headlineDate)
    {
        $conn = self::connectToDatabase();
        $dbnameist = $anlage->getDbNameIst();
        $dbnamesoll = $anlage->getDbNameAcSoll();
        $dbnamedcsoll = $anlage->getDbNameDcSoll();
        $dbnamews = $anlage->getDbNameWeather();


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
        $resExpDc = $conn->query($sql);
        $expPower = [];
        if ($resExpDc->num_rows > 0) {
            while ($rowExp = $resExpDc->fetch_assoc()) {
                $date_time = $rowExp['form_date'];
                $expPower[$date_time]['expPowerAc'] = round($rowExp["exp_power_ac"], 2);
                $expPower[$date_time]['expPowerDc'] = round($rowExp["exp_power_dc"], 2);
            }
        }
        //dump($sql);
        // Wetter Daten laden
        $sql2ss = "SELECT a.stamp as orderStamp, DATE_FORMAT(a.stamp, '$intervall') AS form_date, SUM(b.g_upper) as irr_upper_pannel, SUM(b.g_lower) as irr_lower_pannel, AVG(b.wind_speed) as avgwind, AVG(b.pt_avg) as avgpt, b.anl_id 
                    FROM (db_dummysoll a left JOIN $dbnamews b ON a.stamp = b.stamp) 
                    WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY form_date ORDER BY a.stamp";
        //dump($sql2ss);
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
                #array_push($output, array($date_time,$irr_upper,$ptavgi,$powerGrid,$actPowerAc,$expPowerAc,$actPowerDc,$expPowerDc,$actWrTemp,$prArray[$date_time]['first'],$prArray[$date_time],$prArray[$date_time]));

                $output[] =
                    [
                        "time" => $date_time,
                        "irradiation" => (float)$irr_upper,
                        "powerEGridExt" => (float)$powerGrid,
                        "powerAc" => (float)$actPowerAc,
                        "powerDc" => (float)$actPowerDc,
                        "powerExpAc" => (float)$expPowerAc,
                        "powerExpDc" => (float)$expPowerDc,
                    ];
            }
        }

        $conn->close();

        return $output;
    }


    /**
     * @param Anlage $anlage
     * @return integer
     */
    public function getAnlagenId($anlage){
        return $anlage->getAnlagenId();
    }
}