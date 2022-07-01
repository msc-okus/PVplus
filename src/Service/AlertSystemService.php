<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Entity\Ticket;
use App\Entity\TicketDate;
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

        define('SOR','10');
        define('EFOR','20');
        define('OMC','30');

        define('DATA_GAP', 10);
        define('INVERTER_ERROR',20);
        define('GRID_ERROR',30);
        define('WEATHER_STATION_ERROR',40);
        define('EXTERNAL_CONTROL', 50); // Regelung vom Direktvermarketr oder Netztbetreiber

    }


    public function generateTicketsInterval(Anlage $anlage, string $from, string $to)
    {
        $fromStamp = strtotime($from);
        $toStamp = strtotime($to);
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
            $this->checkSystem($anlage, $from = date("Y-m-d H:i:00", $stamp));
        }
    }

    /**
     * Generate tickets for the given time, check if there is an older ticket for same inverter with same error.
     * Write new ticket to database or extend existing ticket with new end time.
     *
     * @param Anlage $anlage
     * @param string|null $time
     * @return string
     */
    public function checkSystem(Anlage $anlage, ?string $time = null): string
    {
        if ($time === null) $time = $this->getLastQuarter(date('Y-m-d H:i:s') );
        $ppc = false;
        //we look 2 hours in the past to make sure the data we are using is stable (all is okay with the data)
        $time = G4NTrait::timeAjustment($time, -2);

        $sungap = $this->weather->getSunrise($anlage, $time);
        if ((($time >= $sungap['sunrise']) && ($time <= $sungap['sunset']))) {

            $nameArray = $this->functions->getInverterArray($anlage);
            $counter = 1;
            foreach ($nameArray as $inverterName) {
                if($ppc == false) {
                    $inverter_status = $this->RetrieveQuarterIst($time, $counter, $anlage); //IstData($anlage, $time, $counter);
                    if ($inverter_status == "Plant Control by PPC"){
                        $ppc = true;
                        $message = $this->AnalyzeIst($inverter_status, $time, $anlage, $inverterName, $sungap['sunrise']);
                        self::messagingFunction($message, $anlage);
                    }
                    else {
                        $message = $this->AnalyzeIst($inverter_status, $time, $anlage, $inverterName, $sungap['sunrise']);
                        self::messagingFunction($message, $anlage);
                        $counter++;
                        $system_status[$inverterName] = $inverter_status;
                        unset($inverter_status);
                    }
                }
            }
            unset($system_status);
        }
        $this->em->flush();

        return "success";
    }

    public function checkWeatherStation(): bool
    {
        $anlagen = $this->AnlRepo->findAll();
        $time = $this->getLastQuarter(date('Y-m-d H:i:s') );
        $time = G4NTrait::timeAjustment($time, -2);
        $status_report = false;
        $sungap = $this->weather->getSunrise($anlagen);

        foreach ($anlagen as $anlage) {
            if (($anlage->getAnlType() != "masterslave") && ($anlage->getCalcPR() == true) && (($time > $sungap['sunrise']) && ($time < $sungap['sunset']))) {
                $status_report[$anlage->getAnlName()] = $this->WData($anlage, $time);
                $message = self::AnalyzeWeather($status_report[$anlage->getAnlName()], $time, $anlage, $sungap['sunrise']);
                self::messagingFunction($message, $anlage);
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
            $ticket->setErrorType("");
            $ticket->setEditor("Alert system");
            $ticket->setDescription("Error with the Data of the Weather station");
            $ticket->setSystemStatus(10);
            $ticket->setPriority(10);
            $ticket->setAlertType("40"); // 40 = Weather Station Error
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
                $message .= "There is no Irradiation Data since " . $dateString . "<br>";
                if ($temp == "No data") $message .= "There was no temperature data at " . $dateString . "<br>";
                if ($wind == "No data") $message .= "There was no wind data at " . $dateString . "<br>";
            }
        }
        elseif ($status_report['Irradiation'] == "Irradiation is 0") {
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
                $message .= "Irradiation is 0 since " . $dateString . "<br>";
                if ($temp == "No data") $message .= "There was no temperature data at " . $dateString . "<br>";
                if ($wind == "No data") $message .= "There was no wind data at " . $dateString . "<br>";
            }
        }
        else if ($ticket != null){
            $timetempend = date('Y-m-d H:i:s', strtotime($time)-900);
            $end = date_create_from_format('Y-m-d H:i:s', $timetempend);
            $end->getTimestamp();
            $ticket->setEnd(($end));
        }
        if($ticket != null) $this->em->persist($ticket);
        $this->em->flush();
        return $message;
    }

    /**
     * We use this to make an error message of the status array from the inverter and to generate/update Tickets
     * @param $inverter
     * @param $time
     * @param Anlage $anlage
     * @param $nameArray
     * @param $sunrise
     * @return string
     */
    private function AnalyzeIst($inverter, $time, Anlage $anlage, $nameArray, $sunrise): string
    {
        $message = "";
        $errorType = "";
        $errorCategorie = "";
        if ($inverter['istdata'] === "No Data") {
            //data gap
            $message .=  "Data gap at inverter (Power) " . $nameArray . "<br>";
            $errorType = "";
            $errorCategorie = DATA_GAP;
        } elseif ($inverter['istdata'] === "Power is 0") {
            //inverter error
            $message .=  "No power at inverter " . $nameArray . "<br>";
            $errorType = "";
            $errorCategorie = INVERTER_ERROR;
        } elseif ($inverter['istdata'] === 'Power to low') {
            // check if inverter power make sense, to detect ppc
            $message .=  "Power too low at inverter " . $nameArray . " (could be external plant control)<br>";
            $errorType = "";
            $errorCategorie = EXTERNAL_CONTROL;
        } elseif ($inverter['istdata'] === "Plant Control by PPC") {
            // PPC Control
            $message .=  "Inverter is controlled by PPC " . $nameArray . "<br>";
            $errorType = "";
            $errorCategorie = EXTERNAL_CONTROL;
        }
        if ($errorCategorie != DATA_GAP && $errorCategorie != EXTERNAL_CONTROL) {
            if ($anlage->getHasFrequency()) {
                if ($inverter['freq'] !== "All is ok") {
                    if ($errorCategorie == "") {
                        $errorCategorie = GRID_ERROR;
                    }
                    $errorType = OMC;
                    $message .= "Error with the frequency in inverter " . $nameArray . "<br>";
                }
            }
            if ($inverter['voltage'] != "All is ok") {//grid error
                $message .= "Error with the voltage in inverter " . $nameArray . "<br>";
                if ($errorCategorie == "") {
                    $errorCategorie = GRID_ERROR;
                }
                $errorType = OMC;
            }
        }

        $ticket = self::getLastTicket($anlage, $nameArray, $time, $sunrise, false);
        if ($message != "") {
            if ($ticket === null) {
                $ticket = new Ticket();
                $ticketDate = new TicketDate();
                $ticketDate->setAnlage($anlage);
                $ticketDate->setStatus("10");
                $ticketDate->setDescription($message);
                $ticketDate->setSystemStatus(10);
                $ticketDate->setPriority(10);
                $ticket->setAnlage($anlage);
                $ticket->setStatus("10"); // Status 10 = open
                $ticket->setEditor("Alert system");
                $ticket->setDescription($message);
                $ticket->setSystemStatus(10);
                $ticket->setPriority(10);
                if ($inverter['istdata'] === "Plant Control by PPC"){
                    $ticket->setInverter("*");
                    $ticketDate->setInverter("*");
                }
                else {
                    $ticket->setInverter($nameArray);
                    $ticketDate->setInverter($nameArray);
                }
                $ticket->setAlertType($errorCategorie); //  category = alertType (bsp: datagap, inverter power, etc.)
                $ticketDate->setAlertType($errorCategorie);
                $ticket->setErrorType($errorType); // type = errorType (Bsp:  SOR, EFOR, OMC)
                $ticketDate->setErrorType($errorType);
                $timetemp = date('Y-m-d H:i:s', strtotime($time));
                $begin = date_create_from_format('Y-m-d H:i:s', $timetemp);
                $begin->getTimestamp();
                $ticket->setBegin(($begin));
                $ticketDate->setBegin($begin);
            }
            $timetemp = date('Y-m-d H:i:s', strtotime($time));
            $end = date_create_from_format('Y-m-d H:i:s', $timetemp);
            $end->getTimestamp();
            $ticket->setEnd(($end));
            $this->em->persist($ticket);
            $this->em->flush();

            //this is to send a message after and only after 30 mins
            if (date_diff($end, $ticket->getBegin(), true)->i != 30) {
                $message = "";
            }
        } else {
            if ($ticket !== null) {
                //$ticket->setStatus(30); // Close Ticket
                $this->em->persist($ticket);
                $this->em->flush();
            }
        }
        if ($message != ""){
            $message = $message. " at ".$ticket->getBegin()->format('Y-m-d H:i') ."<br>";
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
    private static function IstData($anlage, $time, $inverter): array
    {
        return self::RetrieveQuarterIst($time, $inverter, $anlage);
    }

    /**
     * New version with datagap algorithm
     * @param $anlage
     * @param $time
     * @param $inverter
     * @return array|null
     */
    private static function IstData2($anlage, $time, $inverter): ?array
    {
        $status_report = null;
        $difference = 50;// this will be the variable tolerance in the difference between expected and actual
        $report = self::RetrieveQuarterIst($time, $inverter, $anlage);
        if ($report['istdata'] == "No Data"){
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
            if (($respaq->rowCount() > 0) && ($respeq->rowCount() > 0) && ($respah->rowCount() > 0) && ($respeh->rowCount() > 0)) {
                $exph = $respeh->fetch(PDO::FETCH_ASSOC);
                $expq = $respeq->fetch(PDO::FETCH_ASSOC);
                $acth = $respah->fetch(PDO::FETCH_ASSOC);
                $actq = $respaq->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $status_report = $report;
        }
        $conn = null;

        return $status_report;
    }

    /**
     * here we analyze the data from the weather station and generate the status
     * @param Anlage $anlage
     * @param $time
     * @return array
     */
    private static function WData(Anlage $anlage, $time): array
    {
        $status_report = [];
        $conn = self::getPdoConnection();
        $sqlw = "SELECT b.g_lower as gi , b.g_upper as gmod, b.temp_ambient as temp, b.wind_speed as wspeed 
                    FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) 
                    WHERE a.stamp = '$time' ";

        $resw = $conn->query($sqlw);

        if ($resw->rowCount() > 0) {
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
        $conn = null;

        return $status_report;
    }

    //----------------Extra Functions--------------------

    /**
     * We use this to retrieve the last quarter of a time given pe: 3:42 will return 3:30
     * @param $stamp
     * @return string
     */
    private function getLastQuarter($stamp): string
    {
        //we split the minutes from the rest of the stamp
        $mins = date('i', strtotime($stamp));
        $rest = date('Y-m-d H', strtotime($stamp));
        //we work on the minutes to "round" to the lower quarter
        if ($mins >= "00" && $mins < "15") $quarter = "00";
        elseif ($mins >= "15" && $mins < "30") $quarter = "15";
        elseif ($mins >= "30" && $mins < "45") $quarter = "30";
        else $quarter = "45";

        return ($rest.":".$quarter);
    }


    /**
     * We use this to query for a concrete quarter in an inverter
     * @param string $stamp
     * @param string|null $inverter
     * @param Anlage $anlage
     * @return array
     */
    private static function RetrieveQuarterIst(string $stamp, ?string $inverter, Anlage $anlage): array
    {
        $conn = self::getPdoConnection();
        $irrLimit = 30;

        $sqlw = "SELECT g_lower, g_upper FROM " . $anlage->getDbNameWeather() . " WHERE stamp = '$stamp' ";
        $respirr = $conn->query($sqlw);

        if ($respirr->rowCount() > 0) {
            $pdataw = $respirr->fetch(PDO::FETCH_ASSOC);
            /* TODO: Irradiation depends on config of plant (could east/west with wight of sensors by Pnom or only one sensore) */
            //WE CAN USE THE GETIRRADIATION FUNCTION FROM THE CHART SERVICE
            $irradiation = (float)$pdataw['g_lower'] + (float)$pdataw['g_upper'];
        } else {
            $irradiation = 0;
        }
        $sqlExp = "SELECT ac_exp_power  FROM ". $anlage->getDbNameDcSoll() . " WHERE  stamp = '$stamp' AND wr = '$inverter';";
        $resultExp = $conn->query($sqlExp);

        $sqlAct = "SELECT wr_pac as ac_power, wr_pdc as dc_power, frequency as freq, u_ac as voltage FROM " . $anlage->getDbNameIst() . " WHERE stamp = '$stamp' AND unit = '$inverter' ";
        $resp = $conn->query($sqlAct);

        if ($anlage->getHasPPC()) {
            $sqlPpc = "SELECT * FROM " . $anlage->getDbNamePPC() . " WHERE stamp = '$stamp'";
            $respPpc = $conn->query($sqlPpc);
            if ($respPpc->rowCount() == 1){
                $ppdData = $respPpc->fetch(PDO::FETCH_ASSOC);
            }
        }

        if ($anlage->getHasPPC() && $respPpc->rowCount() == 1 && $ppdData['p_set_rel'] < 100){
            // Power Plant Controller aktiv !!?? Regelung durch Direktvermarkter ??!!
            $return['istdata'] = "Plant Control by PPC";

        } else {
            if ($resp->rowCount() > 0) {
                $pdata = $resp->fetch(PDO::FETCH_ASSOC);
                if ($resultExp->rowCount() == 1) {
                    $expectedData = $resultExp->fetch(PDO::FETCH_ASSOC)['ac_exp_power'];
                } else {
                    $expectedData = false;
                }

                //check power
                if ($pdata['ac_power'] !== null) {
                    if ($pdata['ac_power'] <= 0 && $irradiation > $irrLimit) {
                        $return['istdata'] = "Power is 0";
                    } elseif ($pdata['dc_power'] > 0 && $pdata['dc_power'] <= 1 && $irradiation > $irrLimit && !$anlage->getHasPPC()) {
                        $return['istdata'] = "Power too low";
                    } else {
                        $return['istdata'] = "All is ok";
                    }
                } else {
                    $return['istdata'] = "No Data";
                }

                // check frequency
                if ($pdata['freq'] !== null) {
                    if (($pdata['freq'] <= $anlage->getFreqBase() + $anlage->getFreqTolerance()) && ($pdata['freq'] >= $anlage->getFreqBase() - $anlage->getFreqTolerance())) $return['freq'] = "All is ok";
                    else $return['freq'] = "Error with the frequency";
                } else {
                    $return['freq'] = "No Data";
                }

                // check voltage
                if (date("Y-m-d") > '2022-06-13') { // new definition of database field 'uac'
                    if ($pdata['voltage'] !== null) {
                        if ($pdata['voltage'] <= 0) {
                            $return['voltage'] = "Voltage is 0";
                        } else {
                            $return['voltage'] = "All is ok";
                        }
                    } else {
                        $return['voltage'] = "No Data";
                    }
                } else {
                    $return['voltage'] = 'All is ok';
                }
            } else {
                // no records could be found
                $return['istdata'] = "No data";
                $return['freq'] = "No Data";
                $return['voltage'] = "No Data";
            }
        }
        $conn = null;

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
     * @return mixed
     */
    private function getLastStatus($anlage, $date, $sunrise, $isWeather): mixed
    {
        $time = date('Y-m-d H:i:s', strtotime($date) - 900);
        $yesterday = date('Y-m-d', strtotime($date) - 86400); // this is the date of yesterday
        $today = date('Y-m-d', strtotime($date));
        if ($time <= $sunrise){
            $status = $this->statusRepo->findLastOfDay($anlage, $yesterday,$today, $isWeather);
        }
        else {
            $status = $this->statusRepo->findOneByanlageDate($anlage, $time, $isWeather);
        }

        return $status;
    }

    public function getLastTicket($anlage, $inverter, $time, $sunrise, $isWeather)
    {
        $yesterday = date('Y-m-d', strtotime($time) - 86400); // this is the date of yesterday
        $today = date('Y-m-d', strtotime($time));
        $quarter = date('Y-m-d H:i:s', strtotime($time) - 900);
        if (!$isWeather) {
            // Inverter Tickets
            if ($quarter <= $sunrise) {
                $ticket = $this->ticketRepo->findLastByAITNoWeather($anlage, $inverter, $today, $yesterday);
            } else {
                $ticket = $this->ticketRepo->findByAITNoWeather($anlage, $inverter, $quarter);
            }
        }
        else {
            // Weather Tickets
            if ($quarter <= $sunrise) {
                $ticket = $this->ticketRepo->findLastByAITWeather($anlage, $today, $yesterday);
            } else {
                $ticket = $this->ticketRepo->findByAITWeather($anlage, $quarter);
            }
        }

        return $ticket ? $ticket[0] : null;
    }
}