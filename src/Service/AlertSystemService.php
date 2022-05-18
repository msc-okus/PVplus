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

    public function generateTicketsInterval(string $from, string $to, ?string $anlId = null){
        while($from <= $to){
            $from = G4NTrait::timeAjustment($from, 0.25);

            $this->checkSystem($from, $anlId);

        }
    }
    public function checkSystem(?string $time = null, ?string $anlId = null){

        if ($time == null)$time = $this->getLastQuarter(date('Y-m-d H:i:s') );
        $time = G4NTrait::timeAjustment($time, -2);
        if ($anlId == null) {
            $Anlagen = $this->AnlRepo->findAll();
            foreach ($Anlagen as $anlage) {
                if ($anlage->getAnlId() == "95"/*Buinerveen*/ || $anlage->getAnlId() == "93"/*Staadskanal ||$anlage->getAnlId()=="84"*/) {
                    $sungap = $this->weather->getSunrise($anlage, $time);

                    if ((($time >= $sungap[$anlage->getanlName()]['sunrise']) && ($time <= $sungap[$anlage->getAnlName()]['sunset']))) {
                        $nameArray = $this->functions->getInverterArray($anlage);
                        $counter = 1;
                        foreach ($nameArray as $inverterName) {
                            $inverter_status = $this->IstData($anlage, $time, $counter);
                            $message = self::AnalyzeIst($inverter_status, $time, $anlage, $inverterName, $sungap[$anlage->getanlName()]['sunrise']);
                            self::messagingFunction($message, $anlage);
                            $counter++;
                            $system_status[$inverterName] = $inverter_status;
                            unset($inverter_status);
                        }
                        $this->em->flush();
                        unset($system_status);
                    }
                }
            }
        }
        else{
            $anlage = $this->AnlRepo->findIdLike($anlId)[0];
                $sungap = $this->weather->getSunrise($anlage, $time);

                if ((($time >= $sungap[$anlage->getanlName()]['sunrise']) && ($time <= $sungap[$anlage->getAnlName()]['sunset']))) {
                    $nameArray = $this->functions->getInverterArray($anlage);
                    $counter = 1;
                    foreach ($nameArray as $inverterName) {
                        $inverter_status = $this->IstData($anlage, $time, $counter);
                        $message = self::AnalyzeIst($inverter_status, $time, $anlage, $inverterName, $sungap[$anlage->getanlName()]['sunrise']);
                        self::messagingFunction($message, $anlage);
                        $counter++;
                        $system_status[$inverterName] = $inverter_status;
                        unset($inverter_status);
                    }
                    $this->em->flush();
                    unset($system_status);
                }
        }
        return "success";
    }

    public function checkWeatherStation(){
        $Anlagen = $this->AnlRepo->findAll();
        $time = $this->getLastQuarter(date('Y-m-d H:i:s') );
        $time = G4NTrait::timeAjustment($time, -2);
        $status_report = false;
        $sungap = $this->weather->getSunrise($Anlagen);

        foreach($Anlagen as $anlage) {
            if($anlage->getAnlId()=="106"||$anlage->getAnlId()=="102" || $anlage->getAnlId()=="47" || $anlage->getAnlId()=="107" || $anlage->getAnlId()=="84") {
                if (($anlage->getAnlType() != "masterslave")&&($anlage->getCalcPR() == true) && (($time > $sungap[$anlage->getanlName()]['sunrise']) && ($time < $sungap[$anlage->getAnlName()]['sunset']))) {
                    $status_report[$anlage->getAnlName()] = $this->WData($anlage, $time);
                    $message = self::AnalyzeWeather($status_report[$anlage->getAnlName()], $time, $anlage, $sungap[$anlage->getanlName()]['sunrise']);
                    self::messagingFunction($message, $anlage);
                }
            }
        }
        return $status_report;
    }
    //----------------Analyzing functions----------------

    /**
     * We use this to make an error message of the status array from the weather station and to generate/update Tickets
     * @param $status_report
     * @param $time
     * @param $anlage
     * @param $sunrise
     * @return string
     */
    private function AnalyzeWeather($status_report, $time, $anlage, $sunrise): string
    {
        $message = "";

        $ticket = self::getLastTicket($anlage, null, $time, $sunrise, true);

        if($ticket != null && $status_report['Irradiation'] == "No data" || $status_report['Irradiation'] == "Irradiation is 0"){
            $ticket = new Ticket();
            $ticket->setAnlage($anlage);
            $ticket->setStatus(10);
            $ticket->setErrorType("SFOR");
            $ticket->setEditor("Alert system");
            $ticket->setDescription("Error with the Data of the Weather station");
            $ticket->setSystemStatus(10);
            $ticket->setPriority(10);
            $ticket->setAlertType("Weather Station Error");
            $timetempbeg = date('Y-m-d H:i:s', strtotime($time));
            $begin = date_create_from_format('Y-m-d H:i:s', $timetempbeg);
            $begin->getTimestamp();
            $ticket->setBegin(($begin));
        }


        if ($status_report['Irradiation'] == "No data") {
            $timetempend = date('Y-m-d H:i:s', strtotime($time));
            $end = date_create_from_format('Y-m-d H:i:s', $timetempend);
            $end->getTimestamp();
            $ticket->setEnd(($end));
            $messaging =(date_diff($end, $ticket->getBegin(), true)->i == 30);
            if ($messaging) {
                $timeq2 = date('Y-m-d H:i:s', strtotime($time) - 1800);
                $status_q2 = $this->statusRepo->findOneByanlageDate($anlage, $timeq2, true);
                $temp = $status_q2->getStatus()['temperature'];
                $wind = $status_q2->getStatus()['wspeed'];
                $dateString = $ticket->getBegin()->format('Y-m-d H:i:s');
                $message = $message . "There is no Irradiation Data since " . $dateString . "<br>";
                if ($temp == "No data") $message = $message . "There was no temperature data at " . $dateString . "<br>";
                if ($wind == "No data") $message = $message . "There was no wind data at " . $dateString . "<br>";
            }
        }
        else if ($status_report['Irradiation'] == "Irradiation is 0") {
            $timetempend = date('Y-m-d H:i:s', strtotime($time));
            $end = date_create_from_format('Y-m-d H:i:s', $timetempend);
            $end->getTimestamp();
            $ticket->setEnd(($end));
            $messaging = (date_diff($end, $ticket->getBegin(), true)->i == 30);
            if ($messaging) {
                $timeq2 = date('Y-m-d H:i:s', strtotime($time) - 1800);
                $status_q2 = $this->statusRepo->findOneByanlageDate($anlage, $timeq2, true)[0];
                $temp = $status_q2->getStatus()['temperature'];
                $wind = $status_q2->getStatus()['wspeed'];
                $dateString = $ticket->getBegin()->format('Y-m-d H:i:s');
                $message = $message . "Irradiation is 0 since " . $dateString . "<br>";
                if ($temp == "No data") $message = $message . "There was no temperature data at " . $dateString . "<br>";
                if ($wind == "No data") $message = $message . "There was no wind data at " . $dateString . "<br>";
            }
        }
        else if ($ticket != null){
            $timetempend = date('Y-m-d H:i:s', strtotime($time)-900);
            $end = date_create_from_format('Y-m-d H:i:s', $timetempend);
            $end->getTimestamp();
            $ticket->setEnd(($end));
            $ticket->setStatus(30);
        }
        if($ticket != null) $this->em->persist($ticket);
        $this->em->flush();
        return $message;
    }

    /**
     * We use this to make an error message of the status array from the inverter and to generate/update Tickets
     * @param $status_report
     * @param $time
     * @param $anlage
     * @param $nameArray
     * @return string
     */
    private function AnalyzeIst($inverter, $time, $anlage, $nameArray, $sunrise){
        $message = "";
        $alert ="";
            if ($inverter['istdata'] == "No Data"){
                $message .=  "Data gap at inverter(Power)  ".$nameArray."<br>";
                $alert = "DATA GAP";
            }
            elseif ($inverter['istdata'] == "Power is 0"){
                $message .=  "No power at inverter " .$nameArray."<br>";
                $alert = "Inverter error (Power)";
            }

                if ($anlage->getHasFrequency()) {
                    if ($inverter['freq'] != "All is ok") {
                        if($alert == "") {
                            $alert = "Inverter error (Frequency)";
                        }
                        $message = $message . "Error with the frequency in inverter " . $nameArray . "<br>";
                    }
                }
                if ($inverter['voltage'] != "All is ok") {
                    $message = $message . "Error with the voltage in inverter " . $nameArray . "<br>";
                    if($alert == "") {
                        $alert = "Inverter error (Voltage)";
                    }
                }

        if($message != "") {
            $ticket = self::getLastTicket($anlage, $nameArray, $time, $sunrise, false);
            if ($ticket == null) {
                $ticket = new Ticket();
                $ticket->setAnlage($anlage);
                $ticket->setStatus(10);
                $ticket->setErrorType("SFOR");
                $ticket->setEditor("Alert system");
                $ticket->setDescription($message);
                $ticket->setSystemStatus(10);
                $ticket->setPriority(10);
                $ticket->setInverter($nameArray);
                $ticket->setAlertType($alert);
                $timetemp = date('Y-m-d H:i:s', strtotime($time));
                $begin = date_create_from_format('Y-m-d H:i:s', $timetemp);
                $begin->getTimestamp();
                $ticket->setBegin(($begin));
            }
            $timetemp = date('Y-m-d H:i:s', strtotime($time));
            $end = date_create_from_format('Y-m-d H:i:s', $timetemp);
            $end->getTimestamp();
            $ticket->setEnd(($end));
            $this->em->persist($ticket);
            if (date_diff($end, $ticket->getBegin(), true)->i != 30) {
                $message = "";
            }
        }
        else {
            $ticket = self::getLastTicket($anlage, $nameArray, $time, $sunrise, false);
            if($ticket!=null){
                $ticket->setStatus(30);
                $this->em->persist($ticket);
                $this->em->flush();
            }
        }
        return $message;
    }
    // ---------------Checking Functions-----------------

    /**
     * here we analyze the data of the inverter and generate the status
     * @param $anlage
     * @param $time
     * @param $inverter
     * @return array
     */
    private static function IstData($anlage, $time, $inverter){
        $status_report = self::RetrieveQuarterIst($time, $inverter, $anlage);
        return $status_report;
    }
    /**
     * New version with datagap algorithm
     * @param $anlage
     * @param $time
     * @param $inverter
     * @return array
     */
    private static function IstData2($anlage, $time, $inverter){
        $status_report = null;
        $difference = 50;// this will be the variable tolerance in the difference between expected and actual
        $report = self::RetrieveQuarterIst($time, $inverter, $anlage);
        if($report['istdata'] == "No Data"){
            $conn = self::getPdoConnection();
            $quarter = date('Y-m-d H:i:s', strtotime($time) - 900);
            $half = date('Y-m-d H:i:s', strtotime($time) - 1800);
            $sqlaq = "SELECT wr_pac as ist
                FROM " . $anlage->getDbNameIst() . " 
                WHERE stamp = '$quarter' AND unit = '$inverter' ";
            $sqleq = "SELECT ac_exp_power as exp
                FROM " . $anlage->getDbNameAcSoll() . " 
                WHERE stamp = '$quarter' AND unit = '$inverter' ";
            $sqlah = "SELECT wr_pac as ist
                FROM " . $anlage->getDbNameIst() . " 
                WHERE stamp = '$half' AND unit = '$inverter' ";
            $sqleh = "SELECT ac_exp_power as exp
                FROM " . $anlage->getDbNameAcSoll() . " 
                WHERE stamp = '$half' AND unit = '$inverter' ";
            $respaq = $conn->query($sqlaq);
            $respeq = $conn->query($sqleq);
            $respah = $conn->query($sqlah);
            $respeh = $conn->query($sqleh);
            if (($respaq->rowCount() > 0) && ($respeq->rowCount() > 0)&& ($respah->rowCount() > 0) && ($respeh->rowCount() > 0)) {
                $exph = $respeh->fetch(PDO::FETCH_ASSOC);
                $expq = $respeq->fetch(PDO::FETCH_ASSOC);
                $acth = $respah->fetch(PDO::FETCH_ASSOC);
                $actq = $respaq->fetch(PDO::FETCH_ASSOC);
                dd($exph, $expq, $actq, $acth);
            }
        }
        else $status_report = $report;
        return $status_report;
    }

    /**
     * here we analyze the data from the weather station and generate the status
     * @param Anlage $anlage
     * @param $time
     * @return array
     */
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
                    $status_report['Irradiation'] = "Irradiation is 0";
                }
                else $status_report['Irradiation'] = "All good";
            }
            else{
                $status_report['Irradiation'] = "No data";
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
            else $status_report['wspeed'] = "there is no wind measurer in the plant";
        }
        return $status_report;
    }

    //----------------Extra Functions--------------------

    /**
     * We use this to retrieve the last quarter of a time given pe: 3:42 will return 3:30
     * @param $stamp
     * @return string
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


    /**
     * We use this to query for a concrete quarter in an inverter
     * @param string $stamp
     * @param string|null $inverter
     * @param Anlage $anlage
     * @return string
     */
    private static function RetrieveQuarterIst(string $stamp, ?string $inverter, Anlage $anlage){
        $conn = self::getPdoConnection();

        $sql = "SELECT wr_pac as ist, frequency as freq, wr_udc as voltage
                FROM " . $anlage->getDbNameIst() . " 
                WHERE stamp = '$stamp' AND unit = '$inverter' ";
        $resp = $conn->query($sql);

        if ($resp->rowCount() > 0) {
            $pdata = $resp->fetch(PDO::FETCH_ASSOC);
            if ($pdata['ist'] == 0) $return['istdata'] =  "Power is 0";
            else if ($pdata['ist'] == null) $return['istdata'] = "No Data";
            else $return['istdata'] = "All is ok";
            if ($pdata['freq'] != null){
                if (($pdata['freq'] <= $anlage->getFreqBase()+$anlage->getFreqTolerance()) && ($pdata['freq'] >= $anlage->getFreqBase()-$anlage->getFreqTolerance())) $return['freq'] = "All is ok";
                else $return['freq'] = "Error with the frequency";
            }
            else $return['freq'] = "No Data";
            if ($pdata['voltage'] != null){
                if ($pdata['voltage'] == 0) $return['voltage'] = "Voltage is 0";
                else $return['voltage'] = "All is ok";
            }
            else $return['voltage'] = "No Data";
        }
        else{
            $return['istdata'] = "No data";
            $return['freq'] = "No Data";
            $return['voltage'] = "No Data";
        }
        return $return;
    }

    /**
     * This is the function we use to send the messages we previously generated
     * @param $message
     * @param $anlage
     */
    private function messagingFunction($message, $anlage){
        if ($message != "") {
            sleep(2);
            $subject = "There was an error in " . $anlage->getAnlName();
            $this->mailservice->sendMessage($anlage, 'alert', 3, $subject, $message, false, true, true, true);
        }
    }

    /**
     * this function retrieves the previous status (if any), taking into account that the previous status can be the last from the previous day
     * @param $anlage
     * @param $date
     * @param $sunrise
     * @param $isWeather
     * @return Status|int|mixed|string|null
     */
    private function getLastStatus($anlage, $date, $sunrise, $isWeather){
        $time = date('Y-m-d H:i:s', strtotime($date) - 900);
        $yesterday = date('Y-m-d', strtotime($date) - 86400); // this is the date of yesterday
        $today = date('Y-m-d', strtotime($date));
        if($time <= $sunrise){
            $status = $this->statusRepo->findLastOfDay($anlage, $yesterday,$today, $isWeather);
        }
        else {
            $status = $this->statusRepo->findOneByanlageDate($anlage, $time, $isWeather);
        }
        return $status;
    }
    public function getLastTicket($anlage, $inverter, $time, $sunrise, $isWeather){
        $yesterday = date('Y-m-d', strtotime($time) - 86400); // this is the date of yesterday
        $today = date('Y-m-d', strtotime($time));
        $quarter = date('Y-m-d H:i:s', strtotime($time) - 900);
        if(!$isWeather) {
            if ($quarter <= $sunrise) {
                $ticket = $this->ticketRepo->findLastByAITNoWeather($anlage, $inverter, $today, $yesterday);
            } else $ticket = $this->ticketRepo->findByAITNoWeather($anlage, $inverter, $quarter);
        }
        else {
            if ($quarter <= $sunrise) {
                $ticket = $this->ticketRepo->findLastByAITWeather($anlage, $today, $yesterday);
            } else $ticket = $this->ticketRepo->findByAITWeather($anlage, $quarter);
        }
        if ($ticket != null)  return $ticket[0];
        else return null;
    }
}