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
        return $status_report;
    }

    public function checkWeatherStation(){
        $Anlagen = $this->AnlRepo->findAll();
        $time = $this->getLastQuarter(date('Y-m-d H:i') );
        $time = $this->timeAjustment($time, -2);
        $status_report = false;
        $sungap = $this->weather->getSunrise($Anlagen);

        foreach($Anlagen as $anlage) {
            if (($anlage->getCalcPR() == true) && (($time > $sungap[$anlage->getanlName()]['sunrise']) && ($time < $sungap[$anlage->getAnlName()]['sunset']))) {
                $status_report[] = $this->WData($anlage, $time);
            }
        }
        dd($status_report);
        return $status_report;
    }

    // ------------Checking Functions-----------------
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
                if ($wdata['gi'] <= 0 && $wdata['gmod'] <= 0) {
                    $status_report['Irradiation']['actual'] = "Irradiation is 0";
                    $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));

                    $result = self::RetrieveQuarter($time_q1, $anlage);
                    $status_report['Irradiation']['15'] = $result;
                    if($result == "No data" ){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::RetrieveQuarter($time_q1, $anlage);
                        $status_report['Irradiation']['30'] = $result;
                        if($result == "No data" ){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarter($time_q1, $anlage);
                            $status_report['Irradiation']['45'] = $result;
                            if($result == "No data" ){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarter($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                            else if ($result == "Irradiation is 0"){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarter($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                        }
                        else if ($result == "Irradiation is 0"){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarter($time_q1, $anlage);
                            $status_report['Irradiation']['45'] = $result;
                        }
                    }
                    else if ($result == "Irradiation is 0"){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));

                        $result = self::RetrieveQuarter($time_q1, $anlage);
                        $status_report['Irradiation']['30'] = $result;
                        if($result == "No data" ){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarter($time_q1, $anlage);
                            $status_report['Irradiation']['45'] = $result;
                            if($result == "No data" ){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarter($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                            else if ($result == "Irradiation is 0"){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarter($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                        }
                        else if ($result == "Irradiation is 0"){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarter($time_q1, $anlage);
                            $status_report['Irradiation']['45'] = $result;
                            if($result == "No data" ){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarter($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                            else if ($result == "Irradiation is 0"){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarter($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                        }
                    }
                }
                else $status_report['Irradiation']['Actual'] = "All good";
            }
            else{
                $status_report['Irradiation']['Actual'] = "No data";
                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                $result = self::RetrieveQuarter($time_q1, $anlage);
                $status_report['Irradiation']['15'] = $result;
                if($result == "No data" ){
                    $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                    $result = self::RetrieveQuarter($time_q1, $anlage);
                    $status_report['Irradiation']['30'] = $result;
                    if($result == "No data" ){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::RetrieveQuarter($time_q1, $anlage);
                        $status_report['Irradiation']['45'] = $result;
                        if($result == "No data" ){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarter($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
                        }
                        else if ($result == "Irradiation is 0"){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarter($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
                        }
                    }
                    else if ($result == "Irradiation is 0"){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::RetrieveQuarter($time_q1, $anlage);
                        $status_report['Irradiation']['45'] = $result;
                    }
                }
                else if ($result == "Irradiation is 0"){
                    $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));

                    $result = self::RetrieveQuarter($time_q1, $anlage);
                    $status_report['Irradiation']['30'] = $result;
                    if($result == "No data" ){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::RetrieveQuarter($time_q1, $anlage);
                        $status_report['Irradiation']['45'] = $result;
                        if($result == "No data" ){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarter($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
                        }
                        else if ($result == "Irradiation is 0"){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarter($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
                        }
                    }
                    else if ($result == "Irradiation is 0"){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::RetrieveQuarter($time_q1, $anlage);
                        $status_report['Irradiation']['45'] = $result;
                        if($result == "No data" ){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarter($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
                        }
                        else if ($result == "Irradiation is 0"){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarter($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
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

    //----------------Extra Functions------------------------

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


    private static function messagingFunction(array $status_report, $anlage){
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


    private static function RetrieveQuarter(string $stamp, Anlage $anlage){
        $conn = self::getPdoConnection();

        $sql = "SELECT b.g_lower as gi , b.g_upper as gmod 
                            FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                            WHERE a.stamp = '$stamp' ";

        $resp = $conn->query($sql);

        if ($resp->rowCount() > 0){
            $wdata = $resp->fetch(PDO::FETCH_ASSOC);
            if ($wdata['gi'] + $wdata['gmod'] < 0) return "Irradiation is 0";
            else if ($wdata['gi'] == null && $wdata['gmod'] == null) return "No data";
            else return "All is okay";
        }
        else return "No data";

    }
}