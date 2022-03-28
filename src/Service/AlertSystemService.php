<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Service\FunctionsService;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use PDO;
use App\Helper\G4NTrait;
use App\Service\Charts\IrradiationChartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class AlertSystemService
{
    use G4NTrait;
    private AnlagenRepository $anlagenRepository;
    private \App\Service\WeatherServiceNew $weather;
    private AnlagenRepository $AnlRepo;
    private EntityManagerInterface $em;
    private MessageService $mailservice;
    public functionsService $functions;

    public function __construct(AnlagenRepository $anlagenRepository,
                                WeatherServiceNew $weather,
                                AnlagenRepository $AnlRepo,
                                EntityManagerInterface $em,
                                MessageService $mailservice,
                                FunctionsService $functions){
        $this->anlagenRepository = $anlagenRepository;
        $this->weather = $weather;
        $this->AnlRepo = $AnlRepo;
        $this->em = $em;
        $this->mailservice = $mailservice;
        $this->functions = $functions;
    }

    public function checkSystem(){
        $Anlagen = $this->AnlRepo->findAll();
        $time = $this->getLastQuarter(date('Y-m-d H:i') );
        $time = $this->timeAjustment($time, -2);
        $status_report = false;
        $sungap = $this->weather->getSunrise($Anlagen);

        foreach($Anlagen as $anlage){
            if (($anlage->getCalcPR() == true) && (($time > $sungap[$anlage->getanlName()]['sunrise']) && ($time < $sungap[$anlage->getAnlName()]['sunset']))) {
                $status = new Status();

                $nameArray = $this->functions->getInverterArray($anlage);
                $counter = 1;
                foreach($nameArray as $inverterName) {

                    $status_report[$anlage->getAnlName()]['istdata'][$inverterName] = $this->IstData($anlage, $time, $counter);
                    $counter++;
                }
                $status->setAnlage($anlage);
                $status->setStamp($time);
                $status->setStatus($status_report[$anlage->getAnlName()]);
                $status->setIsWeather(false);

                $this->em->persist($status);
                $this->em->flush();
            }
        }
        dd($status_report);
    }
    private function checkWeather(){
        $Anlagen = $this->AnlRepo->findAll();
        $time = $this->getLastQuarter(date('Y-m-d H:i') );
        $time = $this->timeAjustment($time, -2);
        $status_report = false;
        $sungap = $this->weather->getSunrise($Anlagen);
    }


    private function IstData($anlage, $time, $inverter){
        $result = self::checkQuarter($time, $inverter, $anlage);
        $status_report['actual'] = $result;
        if ($result == "No data"){
            $time = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
            $result = self::checkQuarter($time, $inverter, $anlage);
            $status_report['15'] = $result;
            if ($result == "No data"){
                $time = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                $result = self::checkQuarter($time, $inverter, $anlage);
                $status_report['30'] = $result;
                if($result == "No data"){
                    $time = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                    $result = self::checkQuarter($time, $inverter, $anlage);
                    $status_report['45'] = $result;
                    if($result == "No data"){
                        $time = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::checkQuarter($time, $inverter, $anlage);
                        $status_report['60'] = $result;
                    }
                }
            }
        }

        return $status_report;
    }
    private static function WData(Anlage $anlage, $time)
    {

        $conn = self::getPdoConnection();
        $sqlw = "SELECT b.g_lower as gi , b.g_upper as gmod, b.temp_ambient as temp, b.wind_speed as wspeed 
                    FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                    WHERE a.stamp = '$time' ";

        $resw = $conn->query($sqlw);

        if($resw->rowCount() > 0) {
            $wdata = $resw->fetch(PDO::FETCH_ASSOC);
            if ($wdata['gi'] != null && $wdata['gmod'] != null) {
                if ($wdata['gi'] == 0 && $wdata['gmod'] == 0) {
                    $status_report['Irradiation']['actual'] = "Irradiation is 0";
                    $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
                    $sqlw1 = "SELECT b.gi_avg as gi , b.gmod_avg as gmod 
                            FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                            WHERE a.stamp = '$time_q1' ";
                    $resw1 = $conn->query($sqlw1);
                    if ($resw1->rowCount() > 0) {
                        $wdata1 = $resw1->fetch(PDO::FETCH_ASSOC);

                        if ($wdata1['gi'] != null && $wdata1['gmod'] != null) {
                            if ($wdata1['gi'] == 0 && $wdata1['gmod'] == 0) {
                                $status_report['Irradiation']['last_half'] = "Irradiation is 0";
                            } else $status_report['Irradiation']['last_half'] = "All good";
                        } else {
                            $status_report['Irradiation']['last_half'] = "No data";
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 3600));
                            $sqlw2 = "SELECT b.g_lower as gi , b.g_upper as gmod 
                                FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                                WHERE a.stamp = '$time_q1' ";

                            $resw2 = $conn->query($sqlw2);
                            if($resw2->rowCount() > 0) {
                                $wdata2 = $resw2->fetch(PDO::FETCH_ASSOC);
                                if ($wdata2['gi'] != null && $wdata2['gmod'] != null) {
                                    if ($wdata2['gi'] == 0 && $wdata2['gmod'] == 0) {
                                        $status_report['Irradiation']['last_hour'] = "Irradiation is 0";
                                    } else $status_report['Irradiation']['last_hour'] = "All good";
                                } else {
                                    $status_report['Irradiation']['last_hour'] = "No data";
                                }
                            }
                        }
                    } else $status_report['Irradiation'] = "All good";
                }
            } else {
                $status_report['Irradiation'] = "No data";
                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
                $sqlw3 = "SELECT b.g_lower as gi , b.g_upper as gmod 
                        FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                        WHERE a.stamp = '$time_q1' ";

                $resw3 = $conn->query($sqlw3);
                if ($resw3->rowCount() > 0) {
                    $wdata3 = $resw3->fetch(PDO::FETCH_ASSOC);
                    if ($wdata3['gi'] != null && $wdata3['gmod'] != null) {
                        if ($wdata3['gi'] == 0 && $wdata3['gmod'] == 0) {
                            $status_report['Irradiation']['last_half'] = "Irradiation is 0";
                        } else $status_report['Irradiation']['last_half'] = "All good";
                    } else {;
                        $status_report['Irradiation']['last_half'] = "No data";
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 3600));

                        $sqlw4 = "SELECT b.g_lower as gi , b.g_upper as gmod 
                            FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                            WHERE a.stamp = '$time_q1' ";

                        $resw4 = $conn->query($sqlw4);
                        if ($resw4->rowCount() > 0) {
                            $wdata4 = $resw4->fetch(PDO::FETCH_ASSOC);
                            if ($wdata4['gi'] != null && $wdata4['gmod'] != null) {
                                if ($wdata4['gi'] == 0 && $wdata4['gmod'] == 0) {
                                    $status_report['Irradiation']['last_hour'] = "Irradiation is 0 " ;
                                } else $status_report['Irradiation']['last_hour'] = "All good";
                            } else {
                                $status_report['Irradiation']['last_hour'] = "No data";
                            }
                        }
                    }
                }
            }
            if ($wdata['temp'] != null) $status_report['temperature'] = "All good";

            else  $status_report['temperature'] = "No data";

            if ($anlage->getHasWindSpeed()) {
                if ($wdata['wspeed'] != null) {
                    if ($wdata['wspeed'] == 0) $status_report['wspeed'] = "Wind Speed is 0";

                    else $status_report['wspeed'] = "All good";
                }
                else $status_report['wspeed'] = "No data";
            }
        }
        return $status_report;
    }

    //we use this to retrieve the last quarter of a time given pe: 3:42 will return 3:30
    public function getLastQuarter($stamp){
        //we splikt the minutes from the rest of the stamp
        $mins = date('i', strtotime($stamp));
        $rest = date('Y-m-d H', strtotime($stamp));
        //we work on the minutes to "round" to the lower quarter
        if ($mins >= "00" && $mins < "15") $quarter = "00";
        else if ($mins >= "15" && $mins < "30") $quarter = "15";
        else if ($mins >= "30" && $mins < "45") $quarter = "30";
        else $quarter = "45";
        return ($rest.":".$quarter);

    }
    // We use this to substract 2 hours to the current time so we dont get false data
    public static function timeAjustment($timestamp, float $val = 0, $reverse = false)
    {
        $format     = 'Y-m-d H:i:s';

        if (gettype($timestamp) != 'integer') $timestamp = strtotime($timestamp);
        ($reverse) ? $timestamp -= ($val * 3600) : $timestamp += ($val * 3600);

        return date($format, $timestamp);
    }
    private static function messagingFunction(array $status_report){

    }
    private function checkQuarter(string $stamp, ?string $inverter, Anlage $anlage){
        $conn = self::getPdoConnection();

        $sql = "SELECT wr_pac as ist
                FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b ON a.stamp = b.stamp) 
                WHERE a.stamp = '$stamp' AND b.unit = '$inverter' ";

        $resp = $conn->query($sql);

        if ($resp->rowCount() > 0) {
            $pdata = $resp->fetch(PDO::FETCH_ASSOC);
            if ($pdata['ist'] == 0) return  "Power is 0";
            else if ($pdata['ist'] == null) return "No Data";
            else return "All is ok";
        }
        else return "No data";
    }
}