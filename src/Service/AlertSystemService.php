<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Entity\Ticket;
use App\Entity\TicketDate;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use PDO;

class AlertSystemService
{
    use G4NTrait;

    public function __construct(
        private AnlagenRepository $anlagenRepository,
        private WeatherServiceNew $weather,
        private WeatherFunctionsService $weatherFunctions,
        private AnlagenRepository $AnlRepo,
        private EntityManagerInterface $em,
        private MessageService $mailservice,
        private FunctionsService $functions,
        private StatusRepository $statusRepo,
        private TicketRepository $ticketRepo)
    {
        define('SOR', '10');
        define('EFOR', '20');
        define('OMC', '30');

        define('DATA_GAP', 10);
        define('INVERTER_ERROR', 20);
        define('GRID_ERROR', 30);
        define('WEATHER_STATION_ERROR', 40);
        define('EXTERNAL_CONTROL', 50); // Regelung vom Direktvermarketr oder Netztbetreiber
    }

    public function generateTicketsInterval(Anlage $anlage, string $from, string $to)
    {
        $fromStamp = strtotime($from);
        $toStamp = strtotime($to);
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
            $this->checkSystem($anlage, date('Y-m-d H:i:00', $stamp));
        }
    }
    public function generateTicketMulti(Anlage $anlage, string $from, string $to){
        $fromStamp = strtotime($from);
        $toStamp = strtotime($to);
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
            $this->checkSystemTest($anlage, date('Y-m-d H:i:00', $stamp));
        }
    }

    public function generateTicketsIntervalWeather(Anlage $anlage, string $from, string $to)
    {
        $fromStamp = strtotime($from);
        $toStamp = strtotime($to);
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
            $this->checkSystem($anlage, $from = date('Y-m-d H:i:00', $stamp));
        }
    }

    /**
     * Generate tickets for the given time, check if there is an older ticket for same inverter with same error.
     * Write new ticket to database or extend existing ticket with new end time.
     */
    public function checkSystem(Anlage $anlage, ?string $time = null): string
    {
        if ($time === null) {
            $time = $this->getLastQuarter(date('Y-m-d H:i:s'));
        }
        $ppc = false;
        // we look 2 hours in the past to make sure the data we are using is stable (all is okay with the data)
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        $time = G4NTrait::timeAjustment($time, -2);
        if (($time >= $sungap['sunrise']) && ($time <= $sungap['sunset'])) {
            $nameArray = $this->functions->getInverterArray($anlage);

            foreach ($nameArray as $inverterNo => $inverterName) {
                // We do this to avoid checking further inverters if we have a PPC control shut
                if ($ppc === false) {
                    $inverter_status = $this->RetrieveQuarterIst($time, $inverterNo, $anlage); // IstData($anlage, $time, $counter);

                    if ($inverter_status['istdata'] == 'Plant Control by PPC') {
                        $ppc = true;
                        $message = $this->analyzeIst($inverter_status, $time, $anlage, '*');
                        // self::messagingFunction($message, $anlage);
                    } else {
                        $message = $this->analyzeIst($inverter_status, $time, $anlage, $inverterNo);
                        // self::messagingFunction($message, $anlage);
                        unset($inverter_status);
                    }

                }
            }

        }

        return 'success';
    }

    /**
     * TEST METHOD TO IMPLEMENT MULTI INVERTER TICKET GENERATION
     */
    public function checkSystemTest(Anlage $anlage, ?string $time = null): string
    {
        if ($time === null) {
            $time = $this->getLastQuarter(date('Y-m-d H:i:s'));
        }
        // we look 2 hours in the past to make sure the data we are using is stable
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        $time = G4NTrait::timeAjustment($time, -2);
        if (($time >= $sungap['sunrise']) && ($time <= $sungap['sunset'])) {
            $plant_status = self::RetrievePlantTest($anlage, $time);

            if ($plant_status['ppc'] === false) {
                if ($plant_status['Gap'] !== ""){
                    $errorType = '';
                    $message = "Data gap in Inverter(s): ".$plant_status['Gap'];
                    $errorCategorie = DATA_GAP;
                    $this->generateTickets($errorType, $errorCategorie, $anlage, $plant_status['Gap'] , $time, $message);
                }
                if ($plant_status['Power0'] !== ""){
                    $errorType = EFOR;
                    $message = "Power Error in Inverter(s): ".$plant_status['Power0'];
                    $errorCategorie = INVERTER_ERROR;
                    $this->generateTickets($errorType, $errorCategorie, $anlage, $plant_status['Power0'] , $time, $message);
                }
                if ($plant_status['Vol']){
                    $errorType = '';
                    $message = "Grid Error in Inverter(s): ".$plant_status['Vol'];
                    $errorCategorie = GRID_ERROR;
                    $this->generateTickets($errorType, $errorCategorie, $anlage, $plant_status['Vol'] , $time, $message);
                }
            }
            else {
                $errorType = OMC;
                $errorCategorie = EXTERNAL_CONTROL;
                $this->generateTickets($errorType, $errorCategorie, $anlage, '*' , $time, "");
                $this->generateTickets($errorType, DATA_GAP, $anlage, '' , $time, "");
                $this->generateTickets($errorType, INVERTER_ERROR, $anlage, '' , $time, "");
                $this->generateTickets($errorType, GRID_ERROR, $anlage, '' , $time, "");
            }
        }

        return 'success';
    }

    public function RetrievePlantTest(Anlage $anlage, $time): array
    {
        $irrLimit = 20; //in the future this will come from a field in anlage
        $freqLimitTop = $anlage->getFreqBase() + $anlage->getFreqTolerance();
        $freqLimitBot = $anlage->getFreqBase() - $anlage->getFreqTolerance();
        $voltLimit = 0;
        $freqLimit = $anlage->getFreqBase();
        $conn = self::getPdoConnection();
        $isPPC = false;
        $return['ppc'] = $isPPC;
        $return['Power0'] = "";
        $return['Gap'] = "";
        $return['Vol'] = "";
        $invCount = count($anlage->getInverterFromAnlage());
        $irradiation = $this->weatherFunctions->getIrrByStampForTicket($anlage, date_create($time));

        if ($irradiation > $irrLimit) {
            $sqlAct = 'SELECT b.unit 
                    FROM (db_dummysoll a left JOIN ' . $anlage->getDbNameIst() . " b on a.stamp = b.stamp)
                    WHERE a.stamp = '$time' AND  b.wr_pac <= 0 ";
            $resp = $conn->query($sqlAct);
            $result0 = $resp->fetchAll(PDO::FETCH_ASSOC);

            $sqlNull = 'SELECT b.unit 
                    FROM (db_dummysoll a left JOIN ' . $anlage->getDbNameIst() . " b on a.stamp = b.stamp)
                    WHERE a.stamp = '$time' AND  b.wr_pac is null ";
            $resp = $conn->query($sqlNull);
            $resultNull = $resp->fetchAll(PDO::FETCH_ASSOC);

            $sqlVol = "SELECT b.unit 
                    FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b on a.stamp = b.stamp)
                    WHERE a.stamp = '$time' AND  (b.u_ac < " . $voltLimit . " OR b.frequency < " . $freqLimitBot . " OR b.frequency > " . $freqLimitTop . ")";
            $resp = $conn->query($sqlVol);

            if ($anlage->getHasPPC()) {
                $sqlPpc = 'SELECT * 
                        FROM ' . $anlage->getDbNamePPC() . " 
                        WHERE stamp = '$time' ";
                $respPpc = $conn->query($sqlPpc);

                if ($respPpc->rowCount() === 1) {
                    $ppdData = $respPpc->fetch(PDO::FETCH_ASSOC);
                    $isPPC = (($ppdData['p_set_rel'] < 100 || $ppdData['p_set_gridop_rel'] < 100) && $anlage->getHasPPC());
                }
            }
            $resultVol = $resp->fetchAll(PDO::FETCH_ASSOC);
            if (count($resultNull) == $invCount) $return['Gap'] = '*';
            else {
                foreach ($resultNull as $value) {
                    if ($return['Gap'] !== "") $return['Gap'] = $return['Gap'] . ", " . $value['unit'];
                    else $return['Gap'] = $value['unit'];
                }
            }
            if (count($result0) == $invCount) $return['Power0'] = '*';
            else {
                foreach ($result0 as $value) {
                    if ($return['Power0'] !== "") $return['Power0'] = $return['Power0'] . ", " . $value['unit'];
                    else $return['Power0'] = $value['unit'];
                }
            }
            if (count($resultVol) == $invCount) $return['Vol'] = '*';
            else {
                foreach ($resultVol as $value) {
                    if ($return['Vol'] !== "") $return['Vol'] = $return['Vol'] . ", " . $value['unit'];
                    else $return['Vol'] = $value['unit'];
                }
            }

        }

        return $return;

    }

    private function generateTickets($errorType, $errorCategorie, $anlage, $inverter, $time, $message)
    {
        if ($errorType != "") {
            $ticketOld = self::getLastTicket($anlage, $time, false, $errorCategorie);
            if ($ticketOld !== null) {
                if ($ticketOld->getInverter() == $inverter) {
                    $ticketDate = $ticketOld->getDates()->last();
                    $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
                    $end->getTimestamp();
                    $ticketDate->setEnd($end);
                    $this->em->persist($ticketDate);
                    $this->em->persist($ticketOld);
                } else {
                    $ticketOld->setOpenTicket(false);
                    $this->em->persist($ticketOld);
                    $ticketOld = null;
                }


            }

            if ($ticketOld == null) {
                $ticket = new Ticket();
                $ticketDate = new TicketDate();
                $ticketDate->setAnlage($anlage);
                $ticketDate->setStatus('10');
                $ticketDate->setSystemStatus(10);
                $ticketDate->setPriority(10);
                $ticketDate->setDescription($message);
                $ticketDate->setCreatedBy("AlertSystem");
                $ticketDate->setUpdatedBy("AlertSystem");
                $ticket->setAnlage($anlage);
                $ticket->setStatus('10'); // Status 10 = open
                $ticket->setEditor('Alert system');
                $ticket->setSystemStatus(10);
                $ticket->setPriority(10);
                $ticket->setOpenTicket(true);
                $ticket->setDescription($message);
                $ticket->setCreatedBy("AlertSystem");
                $ticket->setUpdatedBy("AlertSystem");
                if ($errorCategorie == EXTERNAL_CONTROL) {
                    $ticket->setInverter('*');
                    $ticketDate->setInverter('*');
                } else {
                    $ticket->setInverter($inverter);
                    $ticketDate->setInverter($inverter);
                }
                $ticket->setAlertType($errorCategorie); //  category = alertType (bsp: datagap, inverter power, etc.)
                $ticketDate->setAlertType($errorCategorie);
                $ticket->setErrorType($errorType); // type = errorType (Bsp:  SOR, EFOR, OMC)
                $ticketDate->setErrorType($errorType);
                $begin = date_create(date('Y-m-d H:i:s', strtotime($time)));
                $begin->getTimestamp();
                $ticket->setBegin($begin);
                $ticketDate->setBegin($begin);
                $ticket->addDate($ticketDate);
                $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
                $end->getTimestamp();
                $ticketDate->setEnd($end);
                $ticket->setEnd($end);
                if ($errorType == EFOR) {
                    $ticketDate->setKpiPaDep1(10);
                    $ticketDate->setKpiPaDep2(10);
                    $ticketDate->setKpiPaDep3(20);
                }

                $this->em->persist($ticket);
                $this->em->persist($ticketDate);
            }
                $this->em->flush();
        }

    }

    /**
     * We use this to make an error message of the status array from the inverter and to generate/update Tickets.
     *
     * @param $inverter
     * @param $time
     * @param Anlage $anlage
     * @param $inverterNo
     * @return string
     */
    private function analyzeIst($inverter, $time, Anlage $anlage, $inverterNo): string
    {
        $resultArray = self::analyzeError($inverter);
        $message = $resultArray['message'];
        $this->generateTickets($resultArray['errorType'], $resultArray['errorCategorie'], $anlage, $inverterNo, $time, $message);
        unset($ticket);
        unset($ticketDate);
        return $message;
    }



    /**
     * We use this to query for a concrete quarter in an inverter.
     */
    private function RetrieveQuarterIst(string $stamp, ?string $inverter, Anlage $anlage): array
    {
        $conn = self::getPdoConnection();
        $irrLimit = 20;

        $irradiation = $this->weatherFunctions->getIrrByStampForTicket($anlage, date_create($stamp));

        $sqlExp = 'SELECT b.ac_exp_power, a.stamp
                    FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameDcSoll()."  b ON a.stamp = b.stamp)  
                    WHERE a.stamp = '$stamp' 
                    AND b.wr = '$inverter';";
        $resultExp = $conn->query($sqlExp);

        $sqlAct = 'SELECT b.wr_pac as ac_power, b.wr_pdc as dc_power, b.frequency as freq, b.u_ac as voltage 
                    FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameIst()." b ON a.stamp = b.stamp)
                    WHERE a.stamp = '$stamp' 
                    AND b.unit = '$inverter' ";

        $resp = $conn->query($sqlAct);

        if ($anlage->getHasPPC()) {
            $sqlPpc = 'SELECT * 
                        FROM '.$anlage->getDbNamePPC()." 
                        WHERE stamp = '$stamp'";
            $respPpc = $conn->query($sqlPpc);
            if ($respPpc->rowCount() == 1) {
                $ppdData = $respPpc->fetch(PDO::FETCH_ASSOC);
            }
        }

        if ($anlage->getHasPPC() && $respPpc->rowCount() == 1 && $ppdData['p_set_rel'] < 100) {
            $return['istdata'] = 'Plant Control by PPC';
        } else {
            if ($irradiation > $irrLimit) {
                if ($resp->rowCount() > 0) {
                    $pdata = $resp->fetch(PDO::FETCH_ASSOC);
                    if ($resultExp->rowCount() == 1) {
                        $expectedData = $resultExp->fetch(PDO::FETCH_ASSOC)['ac_exp_power'];
                    } else {
                        $expectedData = false;
                    }

                    // check power
                    if ($pdata['ac_power'] !== null) {
                        if ($pdata['ac_power'] <= 0) {
                            $return['istdata'] = 'Power is 0';
                        } elseif ($pdata['dc_power'] > 0 && $pdata['dc_power'] <= 1 && $irradiation > $irrLimit && !$anlage->getHasPPC()) {
                            $return['istdata'] = 'Power too low';
                        } else {
                            $return['istdata'] = 'All is ok';
                        }
                    } else {
                        $return['istdata'] = 'No Data';
                    }

                    // check frequency
                    if ($pdata['freq'] !== null) {
                        if (($pdata['freq'] <= $anlage->getFreqBase() + $anlage->getFreqTolerance()) && ($pdata['freq'] >= $anlage->getFreqBase() - $anlage->getFreqTolerance())) {
                            $return['freq'] = 'All is ok';
                        } else {
                            $return['freq'] = 'Error with the frequency';
                        }
                    } else {
                        $return['freq'] = 'No Data';
                    }

                    // check voltage
                    if (date('Y-m-d', strtotime($stamp)) > '2022-06-13') { // new definition of database field 'uac'
                        if ($pdata['voltage'] !== null) {
                            if ($pdata['voltage'] <= 0) {
                                $return['voltage'] = 'Voltage is 0';
                            } else {
                                $return['voltage'] = 'All is ok';
                            }
                        } else {
                            $return['voltage'] = 'No Data';
                        }
                    } else {
                        $return['voltage'] = 'All is ok';
                    }
                } else {
                    $return['istdata'] = 'No data';
                    $return['freq'] = 'No Data';
                    $return['voltage'] = 'No Data';
                }
            }
            else {
                $return['istdata'] = 'All is ok';
                $return['freq'] = 'All is ok';
                $return['voltage'] = 'All is ok';
            }
        }
        $conn = null;

        return $return;
    }


    /**
    Aux functions
    */

    /**
     * We use this to unify the analysis of the errors and get the data to generate the tickets.
     *
     * @param $data
     * @return array
     */
    #[ArrayShape(['errorType' => "string", 'errorCategorie' => "int|string", 'message' => "string"])]
    private function analyzeError($data): array
    {

        $message = '';
        $errorType = '';
        $errorCategorie = '';
        if ($data['istdata'] === 'No Data') {
            // data gap
            $message .= 'Data gap at inverter (Power) <br>';
            $errorType = '';
            $errorCategorie = DATA_GAP;
        } elseif ($data['istdata'] === 'Power is 0') {
            // inverter error
            $message .= 'No power at inverter <br>';
            $errorType = EFOR;
            $errorCategorie = INVERTER_ERROR;
        } elseif ($data['istdata'] === 'Power to low') {
            // check if inverter power make sense, to detect ppc
            $message .= 'Power too low at inverter (could be external plant control)<br>';
            $errorType = '';
            $errorCategorie = EXTERNAL_CONTROL;
        } elseif ($data['istdata'] === 'Plant Control by PPC') {
            // PPC Control
            $message .= 'Plant is controlled by PPC <br>';
            $errorType = OMC;
            $errorCategorie = EXTERNAL_CONTROL;
        }
        if ($errorCategorie != DATA_GAP && $errorCategorie != EXTERNAL_CONTROL) {

                if ($data['freq'] !== 'All is ok') {
                    if ($errorCategorie == '') {
                        $errorCategorie = GRID_ERROR;
                    }
                    $errorType = OMC;
                    $message .= 'Error with the frequency in inverter <br>';
                }

            if ($data['voltage'] != 'All is ok') {// grid error
                if ($errorCategorie == '') {
                    $errorCategorie = GRID_ERROR;
                }
                $errorType = OMC;
                $message .= 'Error with the voltage in inverter <br>';
            }
        }
        return [
            'errorType'         => $errorType,
            'errorCategorie'    => $errorCategorie,
            'message'           => $message
        ];
    }

    /**
     * In this function we retrieve the previous ticket if it exists
     *
     * @param $anlage
     * @param $inverter
     * @param $time
     * @param $isWeather
     * @param $errorCategory
     * @return mixed
     */
    public function getLastTicket($anlage, $time, $isWeather, $errorCategory): mixed
    {
        $today = date('Y-m-d', strtotime($time));
        $yesterday = date('Y-m-d', strtotime($time) - 86400); // this is the date of yesterday
        $sunrise = self::getLastQuarter($this->weather->getSunrise($anlage, $today)['sunrise']); // the first quarter of today
        $lastQuarterYesterday = self::getLastQuarter($this->weather->getSunrise($anlage, $yesterday)['sunset']); // the last quarter of yesterday
        $quarter = date('Y-m-d H:i', strtotime($time)); // the quarter before the actual

        if (!$isWeather) {
            // Inverter Tickets
                if ($quarter <= $sunrise) {
                    $ticket = $this->ticketRepo->findLastByATNoWeather($anlage,  $today, $lastQuarterYesterday, $errorCategory); // we try to retrieve the last quarter of yesterday
                }
                else {
                    $ticket = $this->ticketRepo->findByATNoWeather($anlage,  $time, $errorCategory); // we try to retrieve the ticket in the previous quarter
                }

        } else {

            if ($quarter <= $sunrise) {
                $ticket = $this->ticketRepo->findLastByAITWeather($anlage, $today, $lastQuarterYesterday); // the same as above but for weather station
            } else {
                $ticket = $this->ticketRepo->findByAITWeather($anlage, $time);
            }
        }
        return $ticket ? $ticket[0] : null;
    }


    /**
     * This is the function we use to send the messages we previously generated.
     *
     * @param $message
     * @param $anlage
     */
    private function messagingFunction($message, $anlage)
    {
        if ($message != '') {
            sleep(2);
            $subject = 'There was an error in '.$anlage->getAnlName();
            $this->mailservice->sendMessage($anlage, 'alert', 3, $subject, $message, false, true, true, true);
        }
    }

    /**
     * We use this to retrieve the last quarter of a time given pe: 3:42 will return 3:30.
     *
     * @param $stamp
     * @return string
     */
    private function getLastQuarter($stamp): string
    {
        // we split the minutes from the rest of the stamp
        $mins = date('i', strtotime($stamp));
        $rest = date('Y-m-d H', strtotime($stamp));
        // we work on the minutes to "round" to the lower quarter
        if ($mins >= '00' && $mins < '15') {
            $quarter = '00';
        } elseif ($mins >= '15' && $mins < '30') {
            $quarter = '15';
        } elseif ($mins >= '30' && $mins < '45') {
            $quarter = '30';
        } else {
            $quarter = '45';
        }

        return $rest.':'.$quarter;
    }


    /**
     * Weather functions
     */

    /**
     * here we analyze the data from the weather station and generate the status.
     *
     * @param Anlage $anlage
     * @param $time
     * @return array
     */
    private static function WData(Anlage $anlage, $time): array
    {
        $status_report = [];
        $conn = self::getPdoConnection();
        $sqlw = 'SELECT b.g_lower as gi , b.g_upper as gmod, b.temp_ambient as temp, b.wind_speed as wspeed 
                    FROM (db_dummysoll a LEFT JOIN '.$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) 
                    WHERE a.stamp = '$time' ";

        $resw = $conn->query($sqlw);

        if ($resw->rowCount() > 0) {
            $wdata = $resw->fetch(PDO::FETCH_ASSOC);
            if ($wdata['gi'] != null && $wdata['gmod'] != null) {
                if ($wdata['gi'] <= 0 && $wdata['gmod'] <= 0) {
                    $status_report['Irradiation'] = 'Irradiation is 0';
                } else {
                    $status_report['Irradiation'] = 'All good';
                }
            } else {
                $status_report['Irradiation'] = 'No data';
            }

            if ($wdata['temp'] != null) {
                $status_report['temperature'] = 'All good';
            } else {
                $status_report['temperature'] = 'No data';
            }

            if ($anlage->getHasWindSpeed()) {
                if ($wdata['wspeed'] != null) {
                    if ($wdata['wspeed'] == 0) {
                        $status_report['wspeed'] = 'Wind Speed is 0';
                    } else {
                        $status_report['wspeed'] = 'All good';
                    }
                } else {
                    $status_report['wspeed'] = 'No data';
                }
            } else {
                $status_report['wspeed'] = 'there is no wind measurer in the plant';
            }
        }
        $conn = null;

        return $status_report;
    }
    /**
     * We use this to make an error message of the status array from the weather station and to generate/update Tickets.
     *
     * @param $status_report
     * @param $time
     * @param $anlage
     * @param $sunrise
     * @return string
     */
    private function AnalyzeWeather($status_report, $time, $anlage, $sunrise): string
    {
        $message = '';

        $ticket = self::getLastTicket($anlage, null, $time, true);

        if ($ticket != null && $status_report['Irradiation'] == 'No data' || $status_report['Irradiation'] == 'Irradiation is 0') {
            $ticket = new Ticket();
            $ticket->setAnlage($anlage);
            $ticket->setStatus(10);
            $ticket->setErrorType('');
            $ticket->setEditor('Alert system');
            $ticket->setDescription('Error with the Data of the Weather station');
            $ticket->setSystemStatus(10);
            $ticket->setPriority(10);
            $ticket->setAlertType('40'); // 40 = Weather Station Error
            $timetempbeg = date('Y-m-d H:i:s', strtotime($time));
            $begin = date_create_from_format('Y-m-d H:i:s', $timetempbeg);
            $begin->getTimestamp();
            $ticket->setBegin($begin);
        }
        if ($status_report['Irradiation'] == 'No data') {
            $timetempend = date('Y-m-d H:i:s', strtotime($time));
            $end = date_create_from_format('Y-m-d H:i:s', $timetempend);
            $end->getTimestamp();
            $ticket->setEnd($end);
            $messaging = (date_diff($end, $ticket->getBegin(), true)->i == 30);
            if ($messaging) {
                $timeq2 = date('Y-m-d H:i:s', strtotime($time) - 1800);
                $status_q2 = $this->statusRepo->findOneByanlageDate($anlage, $timeq2, true);
                $temp = $status_q2->getStatus()['temperature'];
                $wind = $status_q2->getStatus()['wspeed'];
                $dateString = $ticket->getBegin()->format('Y-m-d H:i:s');
                $message .= 'There is no Irradiation Data since '.$dateString.'<br>';
                if ($temp == 'No data') {
                    $message .= 'There was no temperature data at '.$dateString.'<br>';
                }
                if ($wind == 'No data') {
                    $message .= 'There was no wind data at '.$dateString.'<br>';
                }
            }
        } elseif ($status_report['Irradiation'] == 'Irradiation is 0') {
            $timetempend = date('Y-m-d H:i:s', strtotime($time));
            $end = date_create_from_format('Y-m-d H:i:s', $timetempend);
            $end->getTimestamp();
            $ticket->setEnd($end);
            $messaging = (date_diff($end, $ticket->getBegin(), true)->i == 30);
            if ($messaging) {
                $timeq2 = date('Y-m-d H:i:s', strtotime($time) - 1800);
                $status_q2 = $this->statusRepo->findOneByanlageDate($anlage, $timeq2, true)[0];
                $temp = $status_q2->getStatus()['temperature'];
                $wind = $status_q2->getStatus()['wspeed'];
                $dateString = $ticket->getBegin()->format('Y-m-d H:i:s');
                $message .= 'Irradiation is 0 since '.$dateString.'<br>';
                if ($temp == 'No data') {
                    $message .= 'There was no temperature data at '.$dateString.'<br>';
                }
                if ($wind == 'No data') {
                    $message .= 'There was no wind data at '.$dateString.'<br>';
                }
            }
        } elseif ($ticket != null) {
            $timetempend = date('Y-m-d H:i:s', strtotime($time) - 900);
            $end = date_create_from_format('Y-m-d H:i:s', $timetempend);
            $end->getTimestamp();
            $ticket->setEnd($end);
        }
        if ($ticket != null) {
            $this->em->persist($ticket);
        }
        $this->em->flush();

        return $message;
    }


    public function checkWeatherStation(Anlage $anlage, ?string $time = null)
    {
        if ($time === null) {
            $time = $this->getLastQuarter(date('Y-m-d H:i:s'));
            $time = G4NTrait::timeAjustment($time, -2);
        }
        $sungap = $this->weather->getSunrise($anlage, $time);

        $weatherStation = $anlage->getWeatherStation();
        if ($weatherStation->getType() !== 'custom') {
            if (($anlage->getAnlType() != 'masterslave') && ($anlage->getCalcPR() == true) && (($time > $sungap['sunrise']) && ($time < $sungap['sunset']))) {
                $status_report = $this->WDataFix($anlage, $time);
                if ($status_report === 0) {
                    self::messagingFunction('No Data received from the weather station in the last four hours.', $anlage);
                }
                unset($status_report);
            }
        }
    }

    /**
     * here we analyze the data from the weather station and generate the status.
     *
     * @param Anlage $anlage
     * @param $time
     *
     * @return int
     */
    private static function WDataFix(Anlage $anlage, $time): int
    {
        $conn = self::getPdoConnection();
        $begin = G4NTrait::timeAjustment($time, -4);

        $sqlw = 'SELECT count(db_id)
                    FROM '.$anlage->getDbNameWeather()." 
                    WHERE stamp >= '$begin' AND stamp <= '$time' ";

        $resw = $conn->query($sqlw);

        return $resw->rowCount();
    }

}
