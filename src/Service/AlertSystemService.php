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

    public function generateTicketsIntervalWeather(Anlage $anlage, string $from, string $to)
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
            foreach ($nameArray as $inverterNo => $inverterName) {
                // We do this to avoid checking further inverters if we have a PPC control shut
                if ($ppc === false) {
                    $inverter_status = $this->RetrieveQuarterIst($time, $inverterNo, $anlage); //IstData($anlage, $time, $counter);
                    if ($inverter_status['istdata'] == "Plant Control by PPC"){
                        $ppc = true;
                        $message = $this->analyzeIst($inverter_status, $time, $anlage, $inverterName, $inverterNo);
                        self::messagingFunction($message, $anlage);
                    } else {
                        $message = $this->analyzeIst($inverter_status, $time, $anlage, $inverterName, $inverterNo);
                        self::messagingFunction($message, $anlage);
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

    public function checkWeatherStation(Anlage $anlage, ?string $time = null)
    {
        if ($time === null) {
            $time = $this->getLastQuarter(date('Y-m-d H:i:s'));
            $time = G4NTrait::timeAjustment($time, -2);
        }
        $sungap = $this->weather->getSunrise($anlage, $time);

        $weatherStation  = $anlage->getWeatherStation();
        if ($weatherStation->getType() !== "custom") {
            if (($anlage->getAnlType() != "masterslave") && ($anlage->getCalcPR() == true) && (($time > $sungap['sunrise']) && ($time < $sungap['sunset']))) {
                //$status_report = $this->WData($anlage, $time);
                $status_report = $this->WDataFix($anlage, $time);

                //$message = self::AnalyzeWeather($status_report, $time, $anlage, $sungap['sunrise']);
                //$message = self::AnalyzeWeatherFix($status_report, $time, $anlage, $sungap['sunrise']);
                if ($status_report === 0) {
                    self::messagingFunction("No Data received from the weather station in the last four hours.", $anlage);
                }
                unset($status_report);
            }
        }
    }

    //quick fix to send messages
    /**
     * here we analyze the data from the weather station and generate the status
     * @param Anlage $anlage
     * @param $time
     * @return array
     */
    private static function WDataFix(Anlage $anlage, $time): int
    {
        $conn = self::getPdoConnection();
        $begin = G4NTrait::timeAjustment($time, -4);

        $sqlw = "SELECT count(db_id)
                    FROM " . $anlage->getDbNameWeather() . " 
                    WHERE stamp >= '$begin' AND stamp <= '$time' ";

        $resw = $conn->query($sqlw);

        return $resw->rowCount();
    }

    //TEST FOR OPTIMIZED VERSION
//Notes: Maybe we could make 2 separate functions, the one to create old tickets will do only one super big query to the db (depending on which is the max amount of records we can take from the db)
    public function checkSystem2(Anlage $anlage, ?string $time = null): string
    {
        if ($time === null) $time = $this->getLastQuarter(date('Y-m-d H:i:s') );
        $ppc = false;
        //we look 2 hours in the past to make sure the data we are using is stable (all is okay with the data)

        $sungap = $this->weather->getSunrise($anlage, $time);;
                $plant_status = self::RetrieveQuarterPlant($anlage, $sungap);
                // We do this to avoid checking further inverters if we have a PPC control shut
                if ($ppc === false) {
                    if ($plant_status['istdata'] == "Plant Control by PPC"){
                        $ppc = true;
                        $message = $this->analyzePlant($time, $anlage, $sungap['sunrise']);
                        self::messagingFunction($message, $anlage);
                    } else {
                        $message = $this->analyzeIst($time, $anlage, $sungap['sunrise']);
                        self::messagingFunction($message, $anlage);
                        unset($inverter_status);
                    }

                }
            unset($system_status);

        $this->em->flush();

        return "success";
    }

    private static function RetrieveQuarterPlant(Anlage $anlage, $sungap): array
    {
        $conn = self::getPdoConnection();
        $irrLimit = 30;
        $sunrise = $sungap['sunrise'];
        $sunset = $sungap['sunset'];
        $sqlw = "SELECT b.g_lower as lower, b.g_upper as upper, a.stamp
                    FROM (db_dummysoll a left JOIN " . $anlage->getDbNameWeather() . " b on a.stamp = b.stamp)
                    WHERE a.stamp >= '$sunrise' AND  a.stamp < '$sunset' 
                    GROUP BY a.stamp";
        $respirr = $conn->query($sqlw);

        $sqlExp = "SELECT b.dc_exp_power as exp, b.wr as wr, a.stamp as stamp  
                    FROM (db_dummysoll a left JOIN ". $anlage->getDbNameDcSoll() . " b on a.stamp = b.stamp) 
                    WHERE a.stamp >= '$sunrise' AND  a.stamp < '$sunset' 
                    GROUP BY a.stamp, b.wr";
        $resultExp = $conn->query($sqlExp);

        $sqlAct = "SELECT b.wr_pac as ac_power, b.wr_pdc as dc_power, b.frequency as freq, b.u_ac as voltage, a.stamp, b.unit as unit
                    FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b on a.stamp = b.stamp)
                    WHERE a.stamp >= '$sunrise' AND  a.stamp < '$sunset' 
                    GROUP BY a.stamp, b.unit";
        $resp = $conn->query($sqlAct);



            $result = $resp->fetchAll(PDO::FETCH_ASSOC);
            $resulte = $resultExp->fetchAll(PDO::FETCH_ASSOC);
            $resulti = $respirr->fetchAll(PDO::FETCH_ASSOC);

            $stamp = $result[0]['stamp'];
            $irraditerator = 0;
            for ($iterator = 0 ; $iterator < count($result); $iterator++){
                if ($stamp == $result[$iterator]['stamp']) {
                    if ($anlage->getHasPPC()) {
                        $sqlPpc = "SELECT * 
                        FROM " . $anlage->getDbNamePPC() . " 
                        WHERE stamp = '$stamp' ";
                        $respPpc = $conn->query($sqlPpc);

                    }
                    if ($respPpc->rowCount() === 1){
                        $ppdData = $respPpc->fetch(PDO::FETCH_ASSOC);
                    }

                    if (!($anlage->getHasPPC() && $respPpc->rowCount() == 1 && $ppdData['p_set_rel'] < 100)) {
                        $irradiation = (float)$resulti[$irraditerator]['lower'] + (float)$resulti[$irraditerator]['upper'];
                        $irraditerator++;
                        $stamp = date('Y-m-d H:i:s', strtotime($stamp) + 900);

                    }
                }
                if ($anlage->getHasPPC() && $respPpc->rowCount() == 1 && $ppdData['p_set_rel'] < 100) {
                    $return[$result[$iterator]['stamp']]['istdata'] = "Plant Control by PPC";
                } else {
                    $return[$result[$iterator]['stamp']][$result[$iterator]['unit']]['acp'] = $result[$iterator]['ac_power'];
                    $return[$result[$iterator]['stamp']][$result[$iterator]['unit']]['dcp'] = $result[$iterator]['dc_power'];
                    $return[$result[$iterator]['stamp']][$result[$iterator]['unit']]['freq'] = $result[$iterator]['freq'];
                    $return[$result[$iterator]['stamp']][$result[$iterator]['unit']]['voltage'] = $result[$iterator]['vol'];
                    $return[$result[$iterator]['stamp']][$result[$iterator]['unit']]['exp'] = $resulte[$iterator]['exp'];
                }

            }
        dd($return);
    }

    private function analyzePlant($time, Anlage $anlage, $sunrise): string
    {
        dd("todo");
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

        $ticket = self::getLastTicket($anlage, null, $time, true);

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
     * @return string
     */
    private function analyzeIst($inverter, $time, Anlage $anlage, $nameArray, $inverterNo): string
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
            $message .=  "Plant is controlled by PPC <br>";
            $errorType = OMC;
            $errorCategorie = EXTERNAL_CONTROL;
            $inverterNo = "*";
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
                if ($errorCategorie == "") {
                    $errorCategorie = GRID_ERROR;
                }
                $errorType = OMC;
                $message .= "Error with the voltage in inverter " . $nameArray . "<br>";
            }
        }

        $ticket = self::getLastTicket($anlage, $inverterNo, $time, false);
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
                    $ticket->setInverter($inverterNo);
                    $ticketDate->setInverter($inverterNo);
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
                $ticket->addDate($ticketDate);
            }
            else{
                $ticketDate = $ticket->getDates()->last();
            }
            $timetemp = date('Y-m-d H:i:s', strtotime($time));
            $end = date_create_from_format('Y-m-d H:i:s', $timetemp);
            $end->getTimestamp();
            $ticketDate->setEnd($end);
            $ticket->setEnd($end);
            $this->em->persist($ticket);
            $this->em->persist($ticketDate);
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
            $irradiation = (float)$pdataw['g_lower'] + (float)$pdataw['g_upper'];
        } else {
            $irradiation = 0;
        }
        $sqlExp = "SELECT b.ac_exp_power, a.stamp
                    FROM (db_dummysoll a LEFT JOIN ". $anlage->getDbNameDcSoll() . "  b ON a.stamp = b.stamp)  
                    WHERE a.stamp = '$stamp' 
                    AND b.wr = '$inverter';";
        $resultExp = $conn->query($sqlExp);

        $sqlAct = "SELECT b.wr_pac as ac_power, b.wr_pdc as dc_power, b.frequency as freq, b.u_ac as voltage 
                    FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameIst() . " b ON a.stamp = b.stamp)
                    WHERE a.stamp = '$stamp' 
                    AND b.unit = '$inverter' ";

        $resp = $conn->query($sqlAct);

        if ($anlage->getHasPPC()) {
            $sqlPpc = "SELECT * 
                        FROM " . $anlage->getDbNamePPC() . " 
                        WHERE stamp = '$stamp'";
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
                if (date("Y-m-d", strtotime($stamp)) > '2022-06-13') { // new definition of database field 'uac'
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

    public function getLastTicket($anlage, $inverter, $time, $isWeather)
    {
        $today = date('Y-m-d', strtotime($time));
        $yesterday = date('Y-m-d', strtotime($time) - 86400); // this is the date of yesterday
        $sunrise = self::getLastQuarter($this->weather->getSunrise($anlage, $today)['sunrise']); // the first quarter of today
        $lastQuarterYesterday = self::getLastQuarter($this->weather->getSunrise($anlage, $yesterday)['sunset']); // the last quarter of yesterday

        $quarter = date('Y-m-d H:i', strtotime($time) - 900); // the quarter before the actual

        if (!$isWeather) {
            // Inverter Tickets
            if ($quarter <= $sunrise) {
                $ticket = $this->ticketRepo->findLastByAITNoWeather($anlage, $inverter, $today, $lastQuarterYesterday); // we try to retrieve the last quarter of yesterday
            } else {
                $ticket = $this->ticketRepo->findByAITNoWeather($anlage, $inverter, $quarter);// we try to retrieve the ticket in the previous quarter
            }
        }
        else {
            // Weather Tickets
            if ($quarter <= $sunrise) {
                $ticket = $this->ticketRepo->findLastByAITWeather($anlage, $today, $lastQuarterYesterday); //the same as above but for weather station
            } else {
                $ticket = $this->ticketRepo->findByAITWeather($anlage, $quarter);
            }
        }
        return $ticket ? $ticket[0] : null;
    }
}