<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use PDO;
use App\Helper\G4NTrait;
use App\Service\Charts\IrradiationChartService;
use App\Service\FunctionsService;
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

    public function __construct(AnlagenRepository $anlagenRepository, WeatherServiceNew $weather, AnlagenRepository $AnlRepo, EntityManagerInterface $em, MessageService $mailservice){
        $this->anlagenRepository = $anlagenRepository;
        $this->weather = $weather;
        $this->AnlRepo = $AnlRepo;
        $this->em = $em;
        $this->mailservice = $mailservice;
    }

    public function checkSystem(){
        $Anlagen = $this->AnlRepo->findAll();
        $time = $this->getLastQuarter(date('Y-m-d H:i') );
        $time = $this->timeAjustment($time, -2);

        $sungap = $this->weather->getSunrise($Anlagen);//when using the cronjob we need to store this info

        foreach($Anlagen as $anlage){
            if (($anlage->getAnlMute() == "No") && (($time > $sungap[$anlage->getanlName()]['sunrise']) && ($time < $sungap[$anlage->getAnlName()]['sunset']))) {
                $status = new Status();

                $status_report[$anlage->getAnlName()]['wdata'] = $this->WData($anlage, $time, $this->mailservice);
                $status_report[$anlage->getAnlName()]['istdata'] = $this->IstData($anlage, $time, $this->mailservice);

                $status->setAnlage($anlage);
                $status->setStamp($time);
                $status->setStatus($status_report[$anlage->getAnlName()]);
                $this->em->persist($status);
                $this->em->flush();

            }

        }

    }
    //we use this to retrieve the last quarter of a time given pe: 3:42 will return 3:30
    public function getLastQuarter($stamp){
        $mins = date('i', strtotime($stamp));
        $rest = date('Y-m-d H', strtotime($stamp));
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

    private static function IstData($anlage, $time, MessageService $mailservice){
        $format     = 'Y-m-d H:i:s';
        $conn = self::getPdoConnection();
        $sqlp = "SELECT wr_pac as ist, inv as inv
                          FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b ON a.stamp = b.stamp) 
                          WHERE a.stamp = '$time' ";
        $counter = 1;
        $resp = $conn->query($sqlp);
        $message = "";
        while($pdata = $resp->fetch(PDO::FETCH_ASSOC)){
            if($pdata['ist'] != null){
                if($pdata['ist'] == 0) {
                    $status_report['Ist'][$counter]['actual'] = "Power is 0 ";
                    $message = $message . "Power 0 in Inverter " . $counter. " at ".$time."<br>";//replace for an id from the inverter
                }

                else $status_report['Ist'][$counter]['actual'] = "All good";
            }
            else{
                $message = $message." No data in Inverter " . $counter . " at ".$time."<br>";
                $status_report['Ist'][$counter]['actual'] = "No Data";
                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
                $inv = $pdata['inv'];
                $sqlp2 = "SELECT wr_pac as ist
                          FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b ON a.stamp = b.stamp) 
                          WHERE a.stamp = '$time_q1' AND b.inv = '$inv' ";
                $resp2 = $conn->query($sqlp2);
                $pdata2 = $resp2->fetch(PDO::FETCH_ASSOC);
                if($pdata2['ist'] != null){
                    if($pdata2['ist'] == 0){
                        $status_report['Ist'][$counter]['last_half'] = "Power is 0";
                        $message = $message . " -Power is 0 since " .date($format,$time_q1). "<br>";
                    }
                    else $status_report['Ist'][$counter]['last_half'] = "All good";
                }
                else{
                    $status_report['Ist'][$counter]['last_half'] = "No Data";
                    $message = $message .  " -No data since " .date($format,$time_q1). "<br>";
                    $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 3600));
                    $inv = $pdata['inv'];
                    $sqlp3 = "SELECT wr_pac as ist
                          FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b ON a.stamp = b.stamp) 
                          WHERE a.stamp = '$time_q1' AND b.inv = '$inv' ";
                    $resp3 = $conn->query($sqlp3);
                    $pdata3 = $resp3->fetch(PDO::FETCH_ASSOC);
                    if($pdata3['ist'] != null){
                        if($pdata3['ist'] == 0) {
                            $status_report['Ist'][$counter]['last_hour'] = "Power is 0";
                            $message = $message . " -Power is 0 since ".date($format,$time_q1). "<br>";
                        }
                        else $status_report['Ist'][$counter]['last_hour'] = "All good";
                    }
                    else{
                        $status_report['Ist'][$counter]['last_hour'] = "No Data";
                        $message = $message . " -No data since ".date($format,$time_q1). "<br>";
                    }
                }
            }
            $counter++;
        }
        if ($message != ""){
            sleep(2);
            $subject = "There was an error in " . $anlage->getAnlName();
            $mailservice->sendMessage($anlage, 'alert', 3, $subject, $message, false, true, true, true);
        }
        return $status_report;
    }
    private static function WData(Anlage $anlage, $time, MessageService $mailservice)
    {
        $format     = 'Y-m-d H:i:s';
        $conn = self::getPdoConnection();
        $sqlw = "SELECT b.gi_avg as gi , b.gmod_avg as gmod, b.temp_ambient as temp, b.wind_speed as wspeed 
                    FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                    WHERE a.stamp = '$time' ";
        //change to g_lower g_upper
        $resw = $conn->query($sqlw);
        $wdata = $resw->fetch(PDO::FETCH_ASSOC);
        $message = "";

        if ($wdata['gi'] != null && $wdata['gmod'] != null) {
            if ($wdata['gi'] == 0 && $wdata['gmod'] == 0) {
                $status_report['Irradiation'] = "Irradiation is 0";
                $message = $message." Irradiation is 0 <br>";
                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
                $sqlw1 = "SELECT b.gi_avg as gi , b.gmod_avg as gmod 
                            FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                            WHERE a.stamp = '$time_q1' ";
                $resw1 = $conn->query($sqlw1);
                $wdata1 = $resw1->fetch(PDO::FETCH_ASSOC);

                if ($wdata1['gi'] != null && $wdata1['gmod'] != null) {
                    if ($wdata1['gi'] == 0 && $wdata1['gmod'] == 0) {
                        $message = $message . " -Irradiation is 0 since ".date($format,$time_q1). " <br>";
                        $status_report['Irradiation_last30'] = "Irradiation is 0";
                    } else $status_report['Irradiation_last30'] = "All good";
                } else {
                    $message = $message . " -There was no data since ".date($format,$time_q1)."<br>";
                    $status_report['Irradiation_last30'] = "No data";
                    $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 3600));
                    $sqlw2 = "SELECT b.gi_avg as gi , b.gmod_avg as gmod 
                                FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                                WHERE a.stamp = '$time_q1' ";
                    //change to g_lower g_upper
                    $resw2 = $conn->query($sqlw2);
                    $wdata2 = $resw2->fetch(PDO::FETCH_ASSOC);
                    if ($wdata2['gi'] != null && $wdata2['gmod'] != null) {
                        if ($wdata2['gi'] == 0 && $wdata2['gmod'] == 0) {
                            $status_report['Irradiation_lasthour'] = "Irradiation is 0";
                            $message = $message . " -Irradiation is 0 since ".date($format,$time_q1)."<br>";
                        } else $status_report['Irradiation_lasthour'] = "All good";
                    } else {
                        $message = $message." -There is no data since ".date($format,$time_q1)."<br>";
                        $status_report['Irradiation_lasthour'] = "No data";
                    }
                }
            } else $status_report['Irradiation'] = "All good";
        } else {
            $status_report['Irradiation'] = "No data";
            $message = $message."No Irradiation data <br>";
            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
            $sqlw3 = "SELECT b.gi_avg as gi , b.gmod_avg as gmod 
                        FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                        WHERE a.stamp = '$time_q1' ";
            //change to g_lower g_upper
            $resw3 = $conn->query($sqlw3);
            $wdata3 = $resw3->fetch(PDO::FETCH_ASSOC);
            if ($wdata3['gi'] != null && $wdata3['gmod'] != null) {
                if ($wdata3['gi'] == 0 && $wdata3['gmod'] == 0) {
                    $status_report['Irradiation_last30'] = "Irradiation is 0";
                    $message = $message . " -Irradiation is 0 since".date($format,$time_q1)."<br>";
                } else $status_report['Irradiation_last30'] = "All good";
            } else {
                $message = $message . " -There is no data since ".date($format,$time_q1)." <br>";
                $status_report['Irradiation_last30'] = "No data";
                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 3600));
                $sqlw4 = "SELECT b.gi_avg as gi , b.gmod_avg as gmod 
                            FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                            WHERE a.stamp = '$time_q1' ";
                //change to g_lower g_upper
                $resw4 = $conn->query($sqlw4);
                $wdata4 = $resw4->fetch(PDO::FETCH_ASSOC);
                if ($wdata4['gi'] != null && $wdata4['gmod'] != null) {
                    if ($wdata4['gi'] == 0 && $wdata4['gmod'] == 0) {
                        $message = $message . " -Irradiation is 0 since ".date($format,$time_q1)." <br>";
                        $status_report['Irradiation_lasthour'] = "Irradiation is 0 since ".$time_q1."<br>";
                    } else $status_report['Irradiation_lasthour'] = "All good <br>";
                } else {
                    $message = $message . " -There is no data since".date($format,$time_q1)." <br>";
                    $status_report['Irradiation_lasthour'] = "No data";
                }
            }
        }
        if($wdata['temp'] != null ) {

            $status_report['temperature'] = "All good";
        }
        else  $status_report['temperature'] = "No data";
        if ($anlage->getHasWindSpeed()) {
            if ($wdata['wspeed'] != null) {
                if ($wdata['wspeed'] == 0) {
                    $status_report['wspeed'] = "Wind Speed is 0";
                    $message = $message . " Wind Speed is 0 at " . $time . " <br>";
                } else $status_report['wspeed'] = "All good";
            } else {
                $status_report['wspeed'] = "No data";
                $message = $message . "There is no wind speed data at " . $time . " <br>";
            }
        }
        if ($message != ""){
            $subject = "There was an error in the weather station from " . $anlage->getAnlName();
            sleep(2);
            $mailservice->sendMessage($anlage, 'alert', 3, $subject, $message, false, true, true, true);
        }

        return $status_report;
    }
}