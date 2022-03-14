<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use PDO;
use App\Helper\G4NTrait;
use App\Service\Charts\IrradiationChartService;
use App\Service\FunctionsService;
use App\Service\WeatherServiceNew;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class AlertSystemService
{
    use G4NTrait;
    private AnlagenRepository $anlagenRepository;

    public function __construct(AnlagenRepository $anlagenRepository){
        $this->anlagenRepository = $anlagenRepository;
    }

    /**
     * @Route("/default/test/check")
     */
    public function checkSystem(WeatherServiceNew $weather, AnlagenRepository $AnlRepo, EntityManagerInterface $em){
        $Anlagen = $AnlRepo->findAll();
        $time = $this->getLastQuarter(date('Y-m-d H:i') );
        $time = $this->timeAjustment($time, -2);
        $sungap = $weather->getSunrise($Anlagen);
        foreach($Anlagen as $anlage){
            if (($anlage->getAnlMute() == "No") && (($time > $sungap[$anlage->getanlName()]['sunrise']) && ($time < $sungap[$anlage->getAnlName()]['sunset']))) {
                $status = new Status();

                $status_report[$anlage->getAnlName()]['wdata'] = $this->WData($anlage, $time);
                $status_report[$anlage->getAnlName()]['istdata'] = $this->IstData($anlage, $time);

                $status->setAnlage($anlage);
                $status->setStamp($time);
                $status->setStatus($status_report[$anlage->getAnlName()]);
                $em->persist($status);
                $em->flush();
            }

        }
        dd($status_report);
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
    private static function IstData($anlage, $time){
        $conn = self::getPdoConnection();
        $sqlp = "SELECT wr_pac as ist, inv as inv
                          FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b ON a.stamp = b.stamp) 
                          WHERE a.stamp = '$time' ";
        $counter = 1;
        $resp = $conn->query($sqlp);
        while($pdata = $resp->fetch(PDO::FETCH_ASSOC)){
            if($pdata['ist'] != null){
                if($pdata['ist'] == 0) $status_report['Ist'][$counter]['actual'] = "Power is 0";
                else $status_report['Ist'][$counter]['actual'] = "All good";
            }
            else{
                $status_report['Ist'][$counter]['actual'] = "No Data";
                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
                $inv = $pdata['inv'];
                $sqlp = "SELECT wr_pac as ist
                          FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b ON a.stamp = b.stamp) 
                          WHERE a.stamp = '$time_q1' AND a.inv = $inv";
                $pdata = $resp->fetch(PDO::FETCH_ASSOC);
                if($pdata['ist'] != null){
                    if($pdata['ist'] == 0) $status_report['Ist'][$counter]['last_quarter'] = "Power is 0";
                    else $status_report['Ist'][$counter]['last_quarter'] = "All good";
                }
                else{
                    $status_report['Ist'][$counter]['last_quarter'] = "No Data";
                    $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
                    $inv = $pdata['inv'];
                    $sqlp = "SELECT wr_pac as ist
                          FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b ON a.stamp = b.stamp) 
                          WHERE a.stamp = '$time_q1' AND a.inv = $inv";
                    $pdata = $resp->fetch(PDO::FETCH_ASSOC);
                    if($pdata['ist'] != null){
                        if($pdata['ist'] == 0) $status_report['Ist'][$counter]['last_hour'] = "Power is 0";
                        else $status_report['Ist'][$counter]['last_hour'] = "All good";
                    }
                    else{
                        $status_report['Ist'][$counter]['last_hour'] = "No Data";
                    }
                }
            }
            $counter++;
        }
        return $status_report;
    }
    private static function WData(Anlage $anlage, $time)
    {
        $conn = self::getPdoConnection();
        $sqlw = "SELECT b.gi_avg as gi , b.gmod_avg as gmod, b.temp_ambient as temp, b.wind_speed as wspeed FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp = '$time' ";
        //change to g_lower g_upper
        $resw = $conn->query($sqlw);
        $wdata = $resw->fetch(PDO::FETCH_ASSOC);

        if ($wdata['gi'] != null && $wdata['gmod'] != null) {
            if ($wdata['gi'] == 0 && $wdata['gmod'] == 0) {
                $status_report['Irradiation'] = "Irradiation is 0";
                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
                $sqlw = "SELECT b.gi_avg as gi , b.gmod_avg as gmodFROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp = '$time_q1' ";
                $resw = $conn->query($sqlw);
                $wdata = $resw->fetch(PDO::FETCH_ASSOC);

                if ($wdata['gi'] != null && $wdata['gmod'] != null) {
                    if ($wdata['gi'] == 0 && $wdata['gmod'] == 0) {
                        $status_report['Irradiation_last30'] = "Irradiation is 0";
                    } else $status_report['Irradiation_last30'] = "All good";
                } else {
                    $status_report['Irradiation_last30'] = "No data";
                    $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
                    $sqlw = "SELECT b.gi_avg as gi , b.gmod_avg as gmod FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp = '$time_q1' ";
                    //change to g_lower g_upper
                    $resw = $conn->query($sqlw);
                    $wdata = $resw->fetch(PDO::FETCH_ASSOC);
                    if ($wdata['gi'] != null && $wdata['gmod'] != null) {
                        if ($wdata['gi'] == 0 && $wdata['gmod'] == 0) {
                            $status_report['Irradiation_lasthour'] = "Irradiation is 0";
                        } else $status_report['Irradiation_lasthour'] = "All good";
                    } else {
                        $status_report['Irradiation_lasthour'] = "No data";
                    }
                }
            } else $status_report['Irradiation'] = "All good";
        } else {
            $status_report['Irradiation'] = "No data";
            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
            $sqlw = "SELECT b.gi_avg as gi , b.gmod_avg as gmod FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp = '$time_q1' ";
            //change to g_lower g_upper
            $resw = $conn->query($sqlw);
            $wdata = $resw->fetch(PDO::FETCH_ASSOC);
            if ($wdata['gi'] != null && $wdata['gmod'] != null) {
                if ($wdata['gi'] == 0 && $wdata['gmod'] == 0) {
                    $status_report['Irradiation_last30'] = "Irradiation is 0";
                } else $status_report['Irradiation_last30'] = "All good";
            } else {
                $status_report['Irradiation_last30'] = "No data";
                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 1800));
                $sqlw = "SELECT b.gi_avg as gi , b.gmod_avg as gmod FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp = '$time_q1' ";
                //change to g_lower g_upper
                $resw = $conn->query($sqlw);
                $wdata = $resw->fetch(PDO::FETCH_ASSOC);
                if ($wdata['gi'] != null && $wdata['gmod'] != null) {
                    if ($wdata['gi'] == 0 && $wdata['gmod'] == 0) {
                        $status_report['Irradiation_lasthour'] = "Irradiation is 0";
                    } else $status_report['Irradiation_lasthour'] = "All good";
                } else {
                    $status_report['Irradiation_lasthour'] = "No data";
                }
            }
        }
        if($wdata['temp'] != null ) {

            $status_report['temperature'] = "All good";
        }
        else  $status_report['temperature'] = "No data";

        if($wdata['wspeed'] != null) {
            if ($wdata['wspeed'] == 0 ) $status_report['wspeed'] = "Wind Speed is 0";
            else $status_report['wspeed'] = "All good";
        }
        else  $status_report['wspeed'] = "No data";


        return $status_report;
    }
}