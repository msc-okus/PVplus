<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Entity\Ticket;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use PDO;
use App\Helper\G4NTrait;


class AlertSystemService
{
    use G4NTrait;
    private AnlagenRepository $anlagenRepository;
    private WeatherServiceNew $weather;
    private AnlagenRepository $AnlRepo;
    private EntityManagerInterface $em;
    private MessageService $mailservice;
    private functionsService $functions;
    private StatusRepository $statusRepo;
    private TicketRepository $ticketRepo;

    public function __construct(AnlagenRepository $anlagenRepository,
                                WeatherServiceNew $weather,
                                AnlagenRepository $AnlRepo,
                                EntityManagerInterface $em,
                                MessageService $mailservice,
                                FunctionsService $functions,
                                StatusRepository $statusRepo,
                                TicketRepository $ticketRepo){
        $this->anlagenRepository = $anlagenRepository;
        $this->weather = $weather;
        $this->AnlRepo = $AnlRepo;
        $this->em = $em;
        $this->mailservice = $mailservice;
        $this->functions = $functions;
        $this->statusRepo = $statusRepo;
        $this->ticketRepo = $ticketRepo;
    }

    public function checkSystem(){

        $Anlagen = $this->AnlRepo->findAll();
        $time = $this->getLastQuarter(date('Y-m-d H:i:s') );
        $time = G4NTrait::timeAjustment($time, -2);
        $status_report = false;
        $inverter_status = false;
        $sungap = $this->weather->getSunrise($Anlagen);

        foreach($Anlagen as $anlage){
            if (($anlage->getCalcPR() == true) && (($time > $sungap[$anlage->getanlName()]['sunrise']) && ($time < $sungap[$anlage->getAnlName()]['sunset']))) {
                $status = new Status();

                $nameArray = $this->functions->getInverterArray($anlage);
                $counter = 1;
                foreach($nameArray as $inverterName) {

                    $inverter_status[$inverterName]['istdata'] = $this->IstData($anlage, $time, $counter);
                    $counter++;
                }
                /*
                 * change to make it work per inverter
                $status->setAnlage($anlage);
                $status->setStamp($time);
                $status->setStatus($status_report[$anlage->getAnlName()]);
                $status->setIsWeather(false);

                $this->em->persist($status);
                $this->em->flush();
                */
            }
            $status_report[$anlage->getAnlName()] = $inverter_status;
        }
        dd($status_report);
        return $status_report;
    }

    public function checkWeatherStation(){
        $Anlagen = $this->AnlRepo->findAll();
        $time = $this->getLastQuarter(date('Y-m-d H:i:s') );
        $time = G4NTrait::timeAjustment($time, -2);
        $status_report = false;
        $sungap = $this->weather->getSunrise($Anlagen);

        foreach($Anlagen as $anlage) {
            if (($anlage->getCalcPR() == true) && (($time > $sungap[$anlage->getanlName()]['sunrise']) && ($time < $sungap[$anlage->getAnlName()]['sunset']))) {


                $status_report[$anlage->getAnlName()] = $this->WData($anlage, $time);


                $message = self::AnalyzeWeather($status_report[$anlage->getAnlName()], $time, $anlage);
                self::messagingFunction($message, $anlage);
            }
        }
        dd($status_report);
        return $status_report;
    }

    // ---------------Checking Functions-----------------
    private static function IstData($anlage, $time, $inverter){
        $result = self::RetrieveQuarterIst($time, $inverter, $anlage);
        $status_report['actual'] = $result;
        if ($result == "No data"){
            $time = strtotime(date('Y-m-d H:i:s', strtotime($time) - 900));
            $result = self::RetrieveQuarterIst($time, $inverter, $anlage);
            $status_report['15'] = $result;
            if ($result == "No data"){
                $time = strtotime(date('Y-m-d H:i:s', strtotime($time) - 900));
                $result = self::RetrieveQuarterIst($time, $inverter, $anlage);
                $status_report['30'] = $result;
                if($result == "No data"){
                    $time = strtotime(date('Y-m-d H:i:s', strtotime($time) - 900));
                    $result = self::RetrieveQuarterIst($time, $inverter, $anlage);
                    $status_report['45'] = $result;
                    if($result == "No data"){
                        $time = strtotime(date('Y-m-d H:i:s', strtotime($time) - 900));
                        $result = self::RetrieveQuarterIst($time, $inverter, $anlage);
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

                    $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                    $status_report['Irradiation']['15'] = $result;
                    if($result == "No data" ){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                        $status_report['Irradiation']['30'] = $result;
                        if($result == "No data" ){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                            $status_report['Irradiation']['45'] = $result;
                            if($result == "No data" ){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                            else if ($result == "Irradiation is 0"){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                        }
                        else if ($result == "Irradiation is 0"){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                            $status_report['Irradiation']['45'] = $result;
                        }
                    }
                    else if ($result == "Irradiation is 0"){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));

                        $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                        $status_report['Irradiation']['30'] = $result;
                        if($result == "No data" ){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                            $status_report['Irradiation']['45'] = $result;
                            if($result == "No data" ){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                            else if ($result == "Irradiation is 0"){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                        }
                        else if ($result == "Irradiation is 0"){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                            $status_report['Irradiation']['45'] = $result;
                            if($result == "No data" ){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                                $status_report['Irradiation']['60'] = $result;
                            }
                            else if ($result == "Irradiation is 0"){
                                $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                                $result = self::RetrieveQuarterWeather($time_q1, $anlage);
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
                $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                $status_report['Irradiation']['15'] = $result;
                if($result == "No data" ){
                    $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                    $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                    $status_report['Irradiation']['30'] = $result;
                    if($result == "No data" ){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                        $status_report['Irradiation']['45'] = $result;
                        if($result == "No data" ){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
                        }
                        else if ($result == "Irradiation is 0"){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
                        }
                    }
                    else if ($result == "Irradiation is 0"){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                        $status_report['Irradiation']['45'] = $result;
                    }
                }
                else if ($result == "Irradiation is 0"){
                    $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));

                    $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                    $status_report['Irradiation']['30'] = $result;
                    if($result == "No data" ){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                        $status_report['Irradiation']['45'] = $result;
                        if($result == "No data" ){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
                        }
                        else if ($result == "Irradiation is 0"){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
                        }
                    }
                    else if ($result == "Irradiation is 0"){
                        $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                        $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                        $status_report['Irradiation']['45'] = $result;
                        if($result == "No data" ){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarterWeather($time_q1, $anlage);
                            $status_report['Irradiation']['60'] = $result;
                        }
                        else if ($result == "Irradiation is 0"){
                            $time_q1 = strtotime(date('Y-m-d H:i', strtotime($time) - 900));
                            $result = self::RetrieveQuarterWeather($time_q1, $anlage);
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

    //----------------Extra Functions--------------------
    /**
    *We use this to retrieve the last quarter of a time given pe: 3:42 will return 3:30
    */
    private function getLastQuarter($stamp){
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
    //We use this to query for a concrete quarter in an inverter
    private static function RetrieveQuarterIst(string $stamp, ?string $inverter, Anlage $anlage){
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
    /**
    *We use this to query for a concrete quarter in the weatherstation
    */
    private static function RetrieveQuarterWeather(string $stamp, Anlage $anlage){
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

    /**
     * We use this to make an error message of the status array from the weather station
     */
    private function AnalyzeWeather($status_report, $time, $anlage): string
    {

        $status = new Status();
        $timeq1 = date('Y-m-d H:i:s', strtotime($time) - 900);
        $status_q1 = $this->statusRepo->findOneByanlageDate($anlage, $timeq1)[0];
        $ticket = null;
        if($status_q1 != null) {
            dump($status_q1);
            $ticket = $status_q1->getTicket();

            dump($ticket);
        }
        if ($ticket != null){
            $status->setTicket($ticket);
            dump("old ticket found");
        }
        else if(count($status_report['Irradiation']) > 3){
            $ticket = new Ticket();
            $timetempbeg = date('Y-m-d H:i', strtotime($time) - 1800);
            $ticket->setAnlage($anlage);
            $ticket->setStatus(10);
            $ticket->setErrorType("SFOR");
            $ticket->setEditor("Alert system");
            $ticket->setDescription("Error with the Data of the Weatherstation, check on your alert mail for more information");
            $ticket->setSystemStatus(10);
            $ticket->setPriority(10);
            $begin = date_create_from_format('Y-m-d H:i', $timetempbeg);
            $begin->getTimestamp();
            $timetempend = date('Y-m-d H:i', strtotime($time));
            $end = date_create_from_format('Y-m-d H:i', $timetempend);
            $end->getTimestamp();
            $ticket->setEnd(($end));
            $ticket->setBegin(($begin));
            $status->setTicket($ticket);

           // dd($ticket->getId());
        }
        $message = "";

            if ($status_report['Irradiation']['Actual'] == "No data") {
                if ($ticket != null){
                    $timetempend = date('Y-m-d H:i', strtotime($time));
                    $end = date_create_from_format('Y-m-d H:i', $timetempend);
                    $end->getTimestamp();
                    $ticket->setEnd(($end));
                }
                else {
                    $message = $message . "There was no Irradiation Data at " . $time . "<br>";
                    if ($status_report['Irradiation']['15'] == "No data") {
                            $timeq1 = date('Y-m-d H:i', strtotime($time) - 900);
                            $message = $message . "There was no Irradiation Data at " . $timeq1 . "<br>";
                            if ($status_report['Irradiation']['30'] == "No data") {
                                $timeq2 = date('Y-m-d H:i', strtotime($timeq1) - 900);
                                $message = $message . "There was no Irradiation Data at " . $timeq2 . "<br>";
                                if ($status_report['Irradiation']['45'] == "No data") {
                                    $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq3 . "<br>";
                                    if ($status_report['Irradiation']['60'] == "No data") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                    } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                    }
                                } else if ($status_report['Irradiation']['45'] == "Irradiation is 0") {
                                    $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq3 . "<br>";
                                    if ($status_report['Irradiation']['60'] == "No data") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                    } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                    }
                                }
                            } else if ($status_report['Irradiation']['30'] == "Irradiation is 0") {
                                $timeq2 = date('Y-m-d H:i', strtotime($timeq1) - 900);
                                $message = $message . "Irradiation was 0 at " . $timeq2 . "<br>";
                                if ($status_report['Irradiation']['45'] == "No data") {
                                    $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq3 . "<br>";
                                    if ($status_report['Irradiation']['60'] == "No data") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                    } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                    }
                                } else if ($status_report['Irradiation']['45'] == "Irradiation is 0") {
                                    $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq3 . "<br>";
                                    if ($status_report['Irradiation']['60'] == "No data") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                    } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                    }
                                }
                            }
                        }
                    else if ($status_report['Irradiation']['15'] == "Irradiation is 0") {
                            $timeq1 = date('Y-m-d H:i', strtotime($time) - 900);
                            $message = $message . "Irradiation was 0 at " . $timeq1 . "<br>";
                            if ($status_report['Irradiation']['30'] == "No data") {
                                $timeq2 = date('Y-m-d H:i', strtotime($timeq1) - 900);
                                $message = $message . "There was no Irradiation Data at " . $timeq2 . "<br>";
                                if ($status_report['Irradiation']['45'] == "No data") {
                                    $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq3 . "<br>";
                                    if ($status_report['Irradiation']['60'] == "No data") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                    } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                    }
                                } else if ($status_report['Irradiation']['45'] == "Irradiation is 0") {
                                    $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq3 . "<br>";
                                    if ($status_report['Irradiation']['60'] == "No data") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                    } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                    }
                                }
                            } else if ($status_report['Irradiation']['30'] == "Irradiation is 0") {
                                $timeq2 = date('Y-m-d H:i', strtotime($timeq1) - 900);
                                $message = $message . "Irradiation was 0 at " . $timeq2 . "<br>";
                                if ($status_report['Irradiation']['45'] == "No data") {
                                    $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq3 . "<br>";
                                    if ($status_report['Irradiation']['60'] == "No data") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                    } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                    }
                                } else if ($status_report['Irradiation']['45'] == "Irradiation is 0") {
                                    $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq3 . "<br>";
                                    if ($status_report['Irradiation']['60'] == "No data") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                    } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                        $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                        $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                    }
                                }
                            }
                        }
                }
            }
            else if ($status_report['Irradiation']['Actual'] == "Irradiation is 0") {
                if ($ticket != null) {
                    $timetempend = date('Y-m-d H:i', strtotime($time));
                    $end = date_create_from_format('Y-m-d H:i', $timetempend);
                    $end->getTimestamp();
                    $ticket->setEnd(($end));
                }
                else {
                    $message = $message . "Irradiation was 0 at " . $time . "<br>";
                    if ($status_report['Irradiation']['15'] == "No data") {
                        $timeq1 = date('Y-m-d H:i', strtotime($time) - 900);
                        $message = $message . "There was no Irradiation Data at " . $timeq1 . "<br>";
                        if ($status_report['Irradiation']['30'] == "No data") {
                            $timeq2 = date('Y-m-d H:i', strtotime($timeq1) - 900);
                            $message = $message . "There was no Irradiation Data at " . $timeq2 . "<br>";
                            if ($status_report['Irradiation']['45'] == "No data") {
                                $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                $message = $message . "There was no Irradiation Data at " . $timeq3 . "<br>";
                                if ($status_report['Irradiation']['60'] == "No data") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                }
                            } else if ($status_report['Irradiation']['45'] == "Irradiation is 0") {
                                $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                $message = $message . "Irradiation was 0 at " . $timeq3 . "<br>";
                                if ($status_report['Irradiation']['60'] == "No data") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                }
                            }
                        } else if ($status_report['Irradiation']['30'] == "Irradiation is 0") {
                            $timeq2 = date('Y-m-d H:i', strtotime($timeq1) - 900);
                            $message = $message . "Irradiation was 0 at " . $timeq2 . "<br>";
                            if ($status_report['Irradiation']['45'] == "No data") {
                                $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                $message = $message . "There was no Irradiation Data at " . $timeq3 . "<br>";
                                if ($status_report['Irradiation']['60'] == "No data") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                }
                            } else if ($status_report['Irradiation']['45'] == "Irradiation is 0") {
                                $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                $message = $message . "Irradiation was 0 at " . $timeq3 . "<br>";
                                if ($status_report['Irradiation']['60'] == "No data") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                }
                            }
                        }
                    }
                    else if ($status_report['Irradiation']['15'] == "Irradiation is 0") {
                        $timeq1 = date('Y-m-d H:i', strtotime($time) - 900);
                        $message = $message . "Irradiation was 0 at " . $timeq1 . "<br>";
                        if ($status_report['Irradiation']['30'] == "No data") {
                            $timeq2 = date('Y-m-d H:i', strtotime($timeq1) - 900);
                            $message = $message . "There was no Irradiation Data at " . $timeq2 . "<br>";
                            if ($status_report['Irradiation']['45'] == "No data") {
                                $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                $message = $message . "There was no Irradiation Data at " . $timeq3 . "<br>";
                                if ($status_report['Irradiation']['60'] == "No data") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                }
                            } else if ($status_report['Irradiation']['45'] == "Irradiation is 0") {
                                $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                $message = $message . "Irradiation was 0 at " . $timeq3 . "<br>";
                                if ($status_report['Irradiation']['60'] == "No data") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                }
                            }
                        } else if ($status_report['Irradiation']['30'] == "Irradiation is 0") {
                            $timeq2 = date('Y-m-d H:i', strtotime($timeq1) - 900);
                            $message = $message . "Irradiation was 0 at " . $timeq2 . "<br>";
                            if ($status_report['Irradiation']['45'] == "No data") {
                                $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                $message = $message . "There was no Irradiation Data at " . $timeq3 . "<br>";
                                if ($status_report['Irradiation']['60'] == "No data") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                }
                            } else if ($status_report['Irradiation']['45'] == "Irradiation is 0") {
                                $timeq3 = date('Y-m-d H:i', strtotime($timeq2) - 900);
                                $message = $message . "Irradiation was 0 at " . $timeq3 . "<br>";
                                if ($status_report['Irradiation']['60'] == "No data") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "There was no Irradiation Data at " . $timeq4 . "<br>";
                                } else if ($status_report['Irradiation']['60'] == "Irradiation is 0") {
                                    $timeq4 = date('Y-m-d H:i', strtotime($timeq3) - 900);
                                    $message = $message . "Irradiation was 0 at " . $timeq4 . "<br>";
                                }
                            }
                        }
                    }
                }
            }
            if ($status_report['temperature'] == "No data") $message = $message . "There was no temperature data at " . $time . "<br>";
            if ($status_report['wspeed'] == "No data") $message = $message . "There was no temperature data at" . $time . "<br>";
        dump($ticket);

        $status->setAnlage($anlage);
        $status->setStamp($time);
        $status->setStatus($status_report);
        $status->setIsWeather(true);

        if($ticket != null) $this->em->persist($ticket);
        $this->em->persist($status);
        dump($status);
        $this->em->flush();
        return $message;
    }
    /**
    *We use this to make an error message of the status array from the inverter
    */
    private function AnalyzeIst($status_report, $time){
        $message = "";
        if (count($status_report['Irradiation']) > 3) {

        }
        return $message;
    }
    /**
     *We use this to send the messages
    */
    private function messagingFunction($message, $anlage){
        if ($message != "") {
            sleep(2);
            $subject = "There was an error in " . $anlage->getAnlName();
            $this->mailservice->sendMessage($anlage, 'alert', 3, $subject, $message, false, true, true, true);
        }
    }
}