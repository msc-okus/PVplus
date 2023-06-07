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
use DateTimeZone;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use PDO;
use phpDocumentor\Reflection\Types\Boolean;

class AlertSystemV2Service
{
    use G4NTrait;

    private bool $irr = false;

    public function __construct(
        private AnlagenRepository       $anlagenRepository,
        private WeatherServiceNew       $weather,
        private WeatherFunctionsService $weatherFunctions,
        private AnlagenRepository       $AnlRepo,
        private EntityManagerInterface  $em,
        private MessageService          $mailservice,
        private FunctionsService        $functions,
        private StatusRepository        $statusRepo,
        private TicketRepository        $ticketRepo)
    {
        define('EFOR', '10');
        define('SOR', '20');
        define('OMC', '30');

        define('DATA_GAP', 10);
        define('INVERTER_ERROR', 20);
        define('GRID_ERROR', 30);
        define('WEATHER_STATION_ERROR', 40);
        define('EXTERNAL_CONTROL', 50); // Regelung vom Direktvermarketr oder Netztbetreiber
        define('POWER_DIFF', 60);
    }

    /**
     * this method should be called to generate the tickets
     * no other method from this class should be called manually
     * @param Anlage $anlage
     * @param string $from
     * @param string|null $to
     */
    public function generateTicketsInterval(Anlage $anlage, string $from, ?string $to = null): void
    {

        $fromStamp = strtotime($from);
        if ($to != null) {

            $toStamp = strtotime($to);
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
                $this->checkSystem($anlage, date('Y-m-d H:i:00', $stamp));
            }
        }
        else $this->checkSystem($anlage, date('Y-m-d H:i:00', $fromStamp));
    }
    /**
     * this method should be called to generate the tickets
     * no other method from this class should be called manually
     * @param Anlage $anlage
     * @param string $from
     * @param string|null $to
     */
    public function generateTicketsExpectedInterval(Anlage $anlage, string $from, ?string $to = null): void
    {
        $fromStamp = strtotime($from);
        if ($to != null) {
            $toStamp = strtotime($to);
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 86400) {
                $this->checkExpected($anlage, date('Y-m-d', $stamp));
            }
        }
        else $this->checkExpected($anlage, date('Y-m-d', $fromStamp));
    }

    /**
     * Generate tickets for the given time, check if there is an older ticket for same inverter with same error.
     * Write new ticket to database or extend existing ticket with new end time.
     * @param Anlage $anlage
     * @param string|null $time
     * @return string
     */
    public function checkSystem(Anlage $anlage, ?string $time = null): string
    {
        if ($time === null) {
            $time = $this->getLastQuarter(date('Y-m-d H:i:s'));
        }
        // we look 2 hours in the past to make sure the data we are using is stable (all is okay with the data)
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        $time = G4NTrait::timeAjustment($time, -2);
        if (($time > $sungap['sunrise']) && ($time <= $sungap['sunset'])) {
            //here we retrieve the values from the plant and set soma flags to generate tickets
            $plant_status = self::RetrievePlant($anlage, $time);

            $ticketOld = $this->getAllTickets($anlage, $time);
            //revise; maybe we can skip this
            if ((isset($ticketOld))) {
                foreach ($ticketOld as $ticket) {
                    $ticket->setOpenTicket(false);
                    $this->em->persist($ticket);
                }
            }

            if ( $plant_status['ppc'] != null && $plant_status['ppc'] === true)  $this->generateTickets(OMC, EXTERNAL_CONTROL, $anlage, ["*"], $time, "test");
            if ( $plant_status['Gap'] != null && count($plant_status['Gap']) > 0) $this->generateTickets('', DATA_GAP, $anlage, $plant_status['Gap'], $time, "test");
            if ( $plant_status['Power0'] != null && count($plant_status['Power0']) > 0)  $this->generateTickets(EFOR, INVERTER_ERROR, $anlage, $plant_status['Power0'], $time, "test");
            if ( $plant_status['Vol'] != null && (count($plant_status['Vol']) === count($anlage->getInverterFromAnlage())) or ($plant_status['Vol'] == "*")) $this->generateTickets('', GRID_ERROR, $anlage, $plant_status['Vol'], $time, "test");
        }

        $this->em->flush();

        return 'success';
    }

    /**
     * main function to retrieve plant status for a given time
     * @param Anlage $anlage
     * @param $time
     * @return array
     */
    private function RetrievePlant(Anlage $anlage, $time): array
    {

        $offsetServer = new DateTimeZone("Europe/Luxembourg");
        $plantoffset = new DateTimeZone($this->getNearestTimezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(), strtoupper($anlage->getCountry())));
        $totalOffset = $plantoffset->getOffset(new DateTime("now")) - $offsetServer->getOffset(new DateTime("now"));
        $time = date('Y-m-d H:i:s', strtotime($time) - $totalOffset);
        $irrLimit = $anlage->getThreshold1PA0() != "0" ? (float)$anlage->getThreshold1PA0() : 20; // we get the irradiation limit from the plant config
        $freqLimitTop = $anlage->getFreqBase() + $anlage->getFreqTolerance();
        $freqLimitBot = $anlage->getFreqBase() - $anlage->getFreqTolerance();
        //we get the frequency values
        $voltLimit = 0;
        $conn = self::getPdoConnection();

        $return['ppc'] = false;

        $invCount = count($anlage->getInverterFromAnlage());
        $irradiation = $this->weatherFunctions->getIrrByStampForTicket($anlage, date_create($time));
        if ($irradiation < $irrLimit) $this->irr = true;
        else $this->irr = false;
        if ($anlage->getHasPPC()) {
            $sqlPpc = 'SELECT * 
                        FROM ' . $anlage->getDbNamePPC() . " 
                        WHERE stamp = '$time' ";
            $respPpc = $conn->query($sqlPpc);
            if ($respPpc->rowCount() === 1) {
                $ppdData = $respPpc->fetch(PDO::FETCH_ASSOC);
                $return['ppc'] = ((($ppdData['p_set_rpc_rel'] !== null && $ppdData['p_set_rpc_rel'] < 100) || ($ppdData['p_set_gridop_rel'] !== null && $ppdData['p_set_gridop_rel'] < 100)));
            }
            else $return['ppc'] = false;
        }
        else $return['ppc'] = false;


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
        //here we check the status of the plant and use it to create the arrays
        if ($anlage->isGridTicket()) {
            $sqlVol = "SELECT b.unit 
                    FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b on a.stamp = b.stamp)
                    WHERE a.stamp = '$time' AND  (b.u_ac < " . $voltLimit . " OR b.frequency < " . $freqLimitBot . " OR b.frequency > " . $freqLimitTop . ")";
            $resp = $conn->query($sqlVol);

            $resultVol = $resp->fetchAll(PDO::FETCH_ASSOC);
            if (count($resultVol) == $invCount &&  $this->irr == false) $return['Vol'] = ['*'];
                else if (count($resultVol) == 0) $return['Vol'] = [];
                else {
                    foreach ($resultVol as $value) {
                        $return['Vol'][] =  $value['unit'];

                    }

                }
            }
            else $return['Vol'] = [];
            if (count($resultNull) == $invCount &&  $this->irr == false) $return['Gap'] = ['*'];
            else if (count($resultNull) == 0) $return['Gap'] = [];
            else {
                foreach ($resultNull as $value) {
                    $return['Gap'][] =  $value['unit'];
                }
            }
            if (count($result0) == $invCount &&  $this->irr == false) $return['Power0'] = ['*'];
            else if (count($result0) == 0) $return['Power0'] = [];
            else {
                foreach ($result0 as $value) {
                     $return['Power0'][] =  $value['unit'];
                }
            }

        return $return;
    }

    /**
     * Given all the information needed to generate a ticket, the tickets are created and commit them to the db
     * WARNING: The logic behind this function is rather complex, so please make sure that it is completely understood before making a substantial change
     * @param $errorType
     * @param $errorCategorie
     * @param $anlage
     * @param $inverter
     * @param $time
     * @param $message
     * @return void
     */
    private function generateTickets($errorType, $errorCategorie, $anlage, $inverter, $time, $message)
    {
            $ticketArray = $this->getAllTicketsByCat($anlage, $time, $errorCategorie);// we retrieve here the previous ticket (if any)
            if($ticketArray != []) {
                foreach ($ticketArray as $ticketOld) { // we iterate over the array of old tickets
                    $result = G4NTrait::subArrayFromArray($inverter, $ticketOld->getInverterArray());// here we substract the actual inverter array from the inverter array of the old ticket
                    $inverter = $result['array1'];//here will be the array of inverter failing now, but okay in the previous quarter(at least according to the ticket we are analyzing)
                    $intersection = implode(', ', $result['intersection']);//here the inverters that are failing both in the previous and in the current moment
                    $Ticket2Inverters = implode(', ', $result['array2']);//here the inverters that are not failing now but are failing in the previous quarter, thus they must be in a closed ticket
                    if($intersection !== ""){//intersection empty means none of the actual inverters were failing the previous quarter, so we have to do nothing

                        $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
                        $end->getTimestamp();
                        $ticketDate = $ticketOld->getDates()->last();

                        if ($Ticket2Inverters !== "") {
                            $ticketClose = new Ticket();
                            $ticketClose->setInverter($intersection);
                            $ticketClose->copyTicket($ticketOld);
                            $ticketClose->setEnd($end);
                            $ticketClose->setOpenTicket(true);
                            $ticketDate->setEnd($end);
                            $ticketClose->setCreatedBy("AlertSystem");
                            $ticketClose->setUpdatedBy("AlertSystem");
                            $this->em->persist($ticketClose);
                        }
                        else{
                            $ticketOld->setEnd($end);
                            $ticketOld->setOpenTicket(true);
                            $ticketOld->setInverter($intersection);
                            $ticketDate->setEnd($end);
                            $this->em->persist($ticketOld);
                        }
                    }
                    if ($Ticket2Inverters !== ""){//
                        $ticketOld->setOpenTicket(false);
                        $ticketOld->setInverter($Ticket2Inverters);
                        $this->em->persist($ticketOld);
                    }
                }
            }
            //here we create a ticket with the inverters that are loose.
            if ($inverter != "*" ) {
                $restInverter = implode(', ', $inverter);
            }
            else $restInverter = $inverter;
            if ($restInverter != "" && $this->irr === false) {
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
                $ticket->setProofAM(false);
                if ($errorCategorie == EXTERNAL_CONTROL) {
                    $ticket->setInverter('*');
                    $ticketDate->setInverter('*');
                } else {
                    $ticket->setInverter($restInverter);
                    $ticketDate->setInverter($restInverter);
                }
                $ticket->setAlertType($errorCategorie); //  category = alertType (bsp: datagap, inverter power, etc.)
                $ticketDate->setAlertType($errorCategorie);
                $ticket->setErrorType($errorType); // type = errorType (Bsp:  SOR, EFOR, OMC)
                $ticketDate->setErrorType($errorType);
                if ($ticket->getAlertType() == "20") $ticketDate->setDataGapEvaluation(10);
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
                    $ticketDate->setKpiPaDep3(10);
                }
                $this->em->persist($ticket);
                $this->em->persist($ticketDate);
            }
    }


    //AUXILIAR FUNCTIONS
    /**
     * We use this to retrieve the last quarter of a time given pe: 3:42 will return 3:30.
     *
     * @param $stamp
     * @return string
     */
    private function getLastQuarter($stamp): string
    {
        $mins = date('i', strtotime($stamp));
        $rest = date('Y-m-d H', strtotime($stamp));
        if ($mins >= '00' && $mins < '15') {
            $quarter = '00';
        } elseif ($mins >= '15' && $mins < '30') {
            $quarter = '15';
        } elseif ($mins >= '30' && $mins < '45') {
            $quarter = '30';
        } else {
            $quarter = '45';
        }
        return $rest . ':' . $quarter;
    }
    private function getAllTicketsByCat($anlage, $time, $errorCategory):mixed
    {
        $yesterday = date('Y-m-d', strtotime($time) - 86400); // this is the date of yesterday
        $lastQuarterYesterday = self::getLastQuarter($this->weather->getSunrise($anlage, $yesterday)['sunset']);
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        if (strtotime($time) - 900 < strtotime($sungap['sunrise'])) return $this->ticketRepo->findByAnlageTimeYesterday($anlage, $lastQuarterYesterday, $time, $errorCategory);
        else return  $this->ticketRepo->findByAnlageTime($anlage, $time, $errorCategory);
    }

    /**
     * We retrieve all the tickets
     * @param $anlage
     * @param $time
     * @return mixed
     */
    private function getAllTickets($anlage, $time): mixed
    {
        {
            $today = date('Y-m-d', strtotime($time));
            $yesterday = date('Y-m-d', strtotime($time) - 86400); // this is the date of yesterday
            $sunrise = self::getLastQuarter($this->weather->getSunrise($anlage, $today)['sunrise']); // the first quarter of today
            $lastQuarterYesterday = self::getLastQuarter($this->weather->getSunrise($anlage, $yesterday)['sunset']); // the last quarter of yesterday

            $quarter = date('Y-m-d H:i', strtotime($time) - 900); // the quarter before the actual

            if ($quarter <= $sunrise) {
                $ticket = $this->ticketRepo->findAllYesterday($anlage, $today, $lastQuarterYesterday); // we try to retrieve the last quarter of yesterday
            } else {
                $ticket = $this->ticketRepo->findAllByTime($anlage, $time); // we try to retrieve the ticket in the previous quarter
            }
            return $ticket;
        }
    }



}