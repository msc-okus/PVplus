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

class AlertSystemService
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
     * this method should be called from the command to join the tickets
     * not in use now
     * no other method from this class should be called manually
     * @param Anlage $anlage
     * @param string $from
     * @param string $to
     */
    public function joinTicketsInterval(Anlage $anlage, string $from, string $to): void
    {
        $fromStamp = strtotime(date("Y-m-d", strtotime($from)));
        $toStamp = strtotime(date("Y-m-d", strtotime($to)));
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 86400) {
            $this->joinTicketsForTheDay($anlage, date('Y-m-d H:i:00', $stamp));
        }
    }

    /**
     * this method should be called to generate the multi inverter tickets
     * not in use now
     *  no other method from this class should be called manually
     * @param Anlage $anlage
     * @param string $from
     * @param string $to
     */
    public function generateTicketMulti(Anlage $anlage, string $from, string $to)
    {
        $fromStamp = strtotime($from);
        $toStamp = strtotime($to);
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
            $this->checkSystemMulti($anlage, date('Y-m-d H:i:00', $stamp));
        }
    }


    /**
     * function to join tickets based on inverters-timegaps
     * @param Anlage $anlage
     * @param string|null $time
     * @return void
     */
    private function joinTicketsForTheDay(Anlage $anlage, ?string $time = null): void
    {
        if ($time === null) {
            $time = $this->getLastQuarter(date('Y-m-d'));//if no date is provided we use the current day
        }
        //we use the sunrise information to define where the day begins and where it ends
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        $fromStamp = strtotime($this->getLastQuarter(date('Y-m-d H:i', strtotime($sungap['sunrise']))));
        $toStamp = strtotime($this->getLastQuarter(date('Y-m-d H:i', strtotime($sungap['sunset']))));

        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) { // we iterate over all the quarters of the day
            //we retrieve all the tickets that begin in this quarter
            $ticketGap = $this->ticketRepo->findMultipleByBeginErrorAnlage($anlage, date('Y-m-d H:i', ($stamp)), DATA_GAP);
            $ticketZero = $this->ticketRepo->findMultipleByBeginErrorAnlage($anlage, date('Y-m-d H:i', ($stamp)), INVERTER_ERROR);
            $ticketGrid = $this->ticketRepo->findMultipleByBeginErrorAnlage($anlage, date('Y-m-d H:i', ($stamp)), GRID_ERROR);
            $ticketExpected = $this->ticketRepo->findMultipleByBeginErrorAnlage($anlage, date('Y-m-d H:i', ($stamp)), POWER_DIFF);
            //this for loop will iterate over the DataGaps Tickets to join them if they share begin-end date and the editor is AlertSystem
            for ($mainTicketGapIndex = 0; $mainTicketGapIndex < count($ticketGap); $mainTicketGapIndex++) {
                $mainTicketGap = $ticketGap[$mainTicketGapIndex];
                if ($mainTicketGap->getEditor() == "Alert system") {
                    for ($secondTicketGapIndex = $mainTicketGapIndex + 1; $secondTicketGapIndex < count($ticketGap); $secondTicketGapIndex++) {
                        $secondTicketGap = $ticketGap[$secondTicketGapIndex];

                        if (($secondTicketGap->getEnd() == $mainTicketGap->getEnd()) && ($secondTicketGap->getEditor() == "Alert system")) {//if we find a ticket we want to link
                            $this->em->remove($secondTicketGap);
                            array_splice($ticketGap, $secondTicketGapIndex, 1);//we remove the ticket we are linking with the main
                            $secondTicketGapIndex--; //we do this because when we remove an element the index is moved to the left
                            $mainTicketGap->setInverter($mainTicketGap->getInverter() . ", " . $secondTicketGap->getInverter());
                            $mainTicketGap->setDescription($mainTicketGap->getDescription() . ", " . $anlage->getInverterFromAnlage()[(int)$secondTicketGap->getInverter()]);
                        }
                    }
                    $this->em->persist($mainTicketGap);
                }
            }
            for ($mainTicket0Index = 0; $mainTicket0Index < count($ticketZero); $mainTicket0Index++) {
                $mainTicket0 = $ticketZero[$mainTicket0Index];
                if ($mainTicket0->getEditor() == "Alert system") {
                    for ($secondTicket0Index = $mainTicket0Index + 1; $secondTicket0Index < count($ticketZero); $secondTicket0Index++) {
                        $secondTicket0 = $ticketZero[$secondTicket0Index];

                        if (($secondTicket0->getEnd() == $mainTicket0->getEnd()) && ($secondTicket0->getEditor() == "Alert system")) {//if we find a ticket we want to link
                            $this->em->remove($secondTicket0);
                            array_splice($ticketZero, $secondTicket0Index, 1);//we remove the ticket we are linking with the main
                            $secondTicket0Index--; //we do this because when we remove an element the index is moved to the left
                            $mainTicket0->setInverter($mainTicket0->getInverter() . ", " . $secondTicket0->getInverter());
                            $mainTicket0->setDescription($mainTicket0->getDescription() . ", " . $anlage->getInverterFromAnlage()[(int)$secondTicket0->getInverter()]);
                        }
                    }
                    $this->em->persist($mainTicket0);
                }
            }
            for ($mainTicketGridIndex = 0; $mainTicketGridIndex < count($ticketGrid); $mainTicketGridIndex++) {
                $mainTicketGrid = $ticketGrid[$mainTicketGridIndex];
                if ($mainTicketGrid->getEditor() == "Alert system") {
                    for ($secondTicketGridIndex = $mainTicketGridIndex + 1; $secondTicketGridIndex < count($ticketGrid); $secondTicketGridIndex++) {
                        $secondTicketGrid = $ticketGrid[$secondTicketGridIndex];

                        if (($secondTicketGrid->getEnd() == $mainTicketGrid->getEnd()) && ($secondTicketGrid->getEditor() == "Alert system")) {//if we find a ticket we want to link
                            $this->em->remove($secondTicketGrid);
                            array_splice($ticketGrid, $secondTicketGridIndex, 1);//we remove the ticket we are linking with the main
                            $secondTicketGridIndex--; //we do this because when we remove an element the index is moved to the left
                            $mainTicketGrid->setInverter($mainTicketGrid->getInverter() . ", " . $secondTicketGrid->getInverter());
                            $mainTicketGrid->setDescription($mainTicketGrid->getDescription() . ", " . $anlage->getInverterFromAnlage()[(int)$secondTicketGrid->getInverter()]);
                        }
                    }
                    $this->em->persist($mainTicketGrid);
                }
            }
            for ($mainTicketPowerIndex = 0; $mainTicketPowerIndex < count($ticketExpected); $mainTicketPowerIndex++) {
                $mainTicketPower = $ticketExpected[$mainTicketPowerIndex];
                if ($mainTicketPower->getEditor() == "Alert system") {
                    for ($secondTicketPowerIndex = $mainTicketPowerIndex + 1; $secondTicketPowerIndex < count($ticketExpected); $secondTicketPowerIndex++) {
                        $secondTicketPower = $ticketExpected[$secondTicketPowerIndex];

                        if (($secondTicketPower->getEnd() == $mainTicketPower->getEnd()) && ($secondTicketPower->getEditor() == "Alert system")) {//if we find a ticket we want to link
                            $this->em->remove($secondTicketPower);
                            array_splice($ticketExpected, $secondTicketPowerIndex, 1);//we remove the ticket we are linking with the main
                            $secondTicketPowerIndex--; //we do this because when we remove an element the index is moved to the left
                            $mainTicketPower->setInverter($mainTicketPower->getInverter() . ", " . $secondTicketPower->getInverter());
                            $mainTicketPower->setDescription($mainTicketPower->getDescription() . ", " . $anlage->getInverterFromAnlage()[(int)$secondTicketPower->getInverter()]);
                        }
                    }
                    $this->em->persist($mainTicketPower);
                }
            }

            //$this->getTicketYesterday($anlage, $time);

            $this->em->flush();

        }
    }
    /**
     * Generate tickets for the given time, check if there is an older ticket for same inverter with same error.
     * Write new ticket to database or extend existing ticket with new end time.
     * @param Anlage $anlage
     * @param string|null $time
     */
    public function checkExpected(Anlage $anlage, ?string $time = null)
    {
        if ($time === null) {
            $time = $this->getLastQuarter(date('Y-m-d'));
        }

        // we look 2 hours in the past to make sure the data we are using is stable (all is okay with the data)


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
        dump($sungap);
        $time = G4NTrait::timeAjustment($time, -2);
        if (($time >= $sungap['sunrise']) && ($time <= $sungap['sunset'])) {


            //here we retrieve the values from the plant and set soma flags to generate tickets
            $plant_status = self::RetrievePlant($anlage, $time);
            // We do this to avoid checking further inverters if we have a PPC control shut
            $array_gap = explode(", ", $plant_status['Gap']);
            $array_zero = explode(", ", $plant_status['Power0']);
            $array_vol = explode(", ", $plant_status['Vol']);
            $array_PowerDiff = explode(", ", $plant_status['PowerDiff']);
            //we close all the previous tickets and we will re-open them if needed.

            $ticketOld = $this->getAllTickets($anlage, $time);
            if ((isset($ticketOld))) {
                foreach ($ticketOld as $ticket) {
                    $ticket->setOpenTicket(false);
                    $this->em->persist($ticket);
                }
            }

            if ($plant_status['ppc'] === false) {


                if (count($array_gap) > 0) {
                    foreach ($array_gap as $inverter) {
                        if ($inverter != "") {
                            $message = "Data gap in Inverter(s): " . $anlage->getInverterFromAnlage()[(int)$inverter];
                            $this->generateTickets('', DATA_GAP, $anlage, $inverter, $time, $message);
                        }
                    }
                }
                if (count($array_zero) > 0) {
                    foreach ($array_zero as $inverter) {
                        if ($inverter != "") {
                            $message = "Power Error in Inverter(s): " . $anlage->getInverterFromAnlage()[(int)$inverter];
                            $this->generateTickets(EFOR, INVERTER_ERROR, $anlage, $inverter, $time, $message);
                        }
                    }
                }
                if((count($array_vol) === count($anlage->getInverterFromAnlage()))){
                    foreach ($array_vol as $inverter) {
                        if (($inverter != "")) {
                            $message = "Grid Error in Inverter(s): " . $anlage->getInverterFromAnlage()[(int)$inverter];
                            $this->generateTickets('', GRID_ERROR, $anlage, $inverter, $time, $message);
                        }
                    }
                }
            } else {
                $errorCategorie = EXTERNAL_CONTROL;
                $this->generateTickets(OMC, $errorCategorie, $anlage, '*', $time, "");
            }
        }
        if ((date('Y-m-d H:i', strtotime($time) + 900) >= $sungap['sunset']) && (date('Y-m-d H:i', strtotime($time) + 900) <= date('Y-m-d H:i', strtotime($sungap['sunset']) +1800))){
            $this->joinTicketsForTheDay($anlage, date('Y-m-d', strtotime($time)));
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
        $irrLimit = $anlage->getThreshold1PA0() == 0 ? $anlage->getThreshold1PA0() : 20; // we get the irradiation limit from the plant config
        $freqLimitTop = $anlage->getFreqBase() + $anlage->getFreqTolerance();
        $freqLimitBot = $anlage->getFreqBase() - $anlage->getFreqTolerance();
        $percentajeDiff = $anlage->getPercentageDiff();
        //we get the frequency values
        $voltLimit = 0;
        $conn = self::getPdoConnection();

        $return['ppc'] = false;
        $return['PowerDiff'] = "";
        $return['Power0'] = "";
        $return['Gap'] = "";
        $return['Vol'] = "";
        $invCount = count($anlage->getInverterFromAnlage());
        $irradiation = $this->weatherFunctions->getIrrByStampForTicket($anlage, date_create($time));

        if ($irradiation < $irrLimit) $this->irr = true;
        else $this->irr = false;
        $counter = 0;


        if ($anlage->getHasPPC()) {
            $sqlPpc = 'SELECT * 
                        FROM ' . $anlage->getDbNamePPC() . " 
                        WHERE stamp = '$time' ";
            $respPpc = $conn->query($sqlPpc);
            if ($respPpc->rowCount() === 1) {
                $ppdData = $respPpc->fetch(PDO::FETCH_ASSOC);
                $return['ppc'] = (($ppdData['p_set_rel'] < 100 || $ppdData['p_set_gridop_rel'] < 100) && $anlage->getHasPPC());
            }
        }

        if ($return['ppc'] != true) {

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
            //here if there is no plant control we check the values and get the information to create the tickets
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

            if ($anlage->isExpectedTicket() && $anlage->getAnlType() != "masterslave"){

                $timeEnd = date("Y-m-d H:i",strtotime($time));
                $timeBegin = date("Y-m-d H:i",strtotime($time)- 3600); // we start looking one hour in the past to check the power and expected

                switch ($anlage->getConfigType()) {
                    case 1:
                    case 2:
                        $actQuery = "SELECT unit as inverter, avg(wr_pac) as power 
                            FROM " . $anlage->getDbNameIst() . " 
                            WHERE stamp BETWEEN '$timeBegin' AND '$timeEnd' AND  wr_pac > 0 
                            GROUP BY unit";
                        $resp = $conn->query($actQuery);
                        $power = $resp->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($power as $value) {
                            if ($value['inverter'] != null) {
                                $expQuery = "SELECT avg(ac_exp_power) as exp
                                FROM  " . $anlage->getDbNameDcSoll() . " 
                                WHERE stamp BETWEEN '$timeBegin' AND '$timeEnd' AND  wr_num = " . $value['inverter'] . " ";
                                $respExp = $conn->query($expQuery);
                                $expected = $respExp->fetch(PDO::FETCH_ASSOC);
                                if ((abs($expected['exp'] - $value['power']) * 100 / (($value['power'] + $expected['exp']) / 2) > $percentajeDiff) && ($value['power'] > 0)) {
                                    $counter++;
                                    if ($return['PowerDiff'] == "")
                                        $return['PowerDiff'] = $value['inverter'];
                                    else
                                        $return['PowerDiff'] = $return['PowerDiff'] . ", " . $value['inverter'];
                                }
                            }
                        }
                        break;
                    case 3:

                        $actQuery = "SELECT group_ac as groupe, sum(wr_pac) as power 
                            FROM " . $anlage->getDbNameIst() . "
                            WHERE stamp BETWEEN '$timeBegin' AND '$timeEnd' AND  wr_pac > 0 
                            GROUP by group_ac";
                        $resp = $conn->query($actQuery);
                        $power = $resp->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($power as $value) {
                            if ($value['groupe'] != null) {
                                $expQuery = "SELECT sum(ac_exp_power) as exp, group_ac as inverter
                                FROM " . $anlage->getDbNameDcSoll() . " 
                                WHERE stamp BETWEEN '$timeBegin' AND '$timeEnd' AND  group_ac = " . $value['groupe'] . " 
                                GROUP BY group_ac";
                                $respExp = $conn->query($expQuery);
                                $expected = $respExp->fetch(PDO::FETCH_ASSOC);
                                if ((abs($expected['exp'] - $value['power']) * 100 / (($value['power'] + $expected['exp']) / 2) > $percentajeDiff) && ($value['power'] > 0)) {
                                    $counter++;
                                    if ($return['PowerDiff'] == "")
                                        $return['PowerDiff'] = $expected['inverter'];
                                    else
                                        $return['PowerDiff'] = $return['PowerDiff'] . ", " . $expected['inverter'];
                                }
                            }
                        }
                        break;
                }
                if ($counter == $invCount)  $return['PowerDiff'] = "*";
            }
        }
        return $return;
    }

    /**
     * Given all the information needed to generate a ticket, the tickets are created and commited to the db (single ticket variant)
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
        $ticketOld = $this->getLastTicket($anlage, $time, $errorCategorie, $inverter);// we retrieve here the previous ticket (if any)
        if ($inverter == "19")dump($time, $ticketOld);
        //this could be the ticket from  the previous quarter or the last ticket from  the previous day
        if ($ticketOld !== null) { // is there is a previous ticket we just extend it
            $ticketDate = $ticketOld->getDates()->last();
            $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
            $end->getTimestamp();
            $ticketOld->setEnd($end);
            $ticketOld->setOpenTicket(true);
            $ticketDate->setEnd($end);
            $this->em->persist($ticketDate);
            $this->em->persist($ticketOld);
        } else if ($this->irr === false) {// if there is no previous ticket we create a new one, the next lines are just setting the properties of the ticket
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
            //default values por the kpi evaluation
            if ($errorType == EFOR) {
                $ticketDate->setKpiPaDep1(10);
                $ticketDate->setKpiPaDep2(10);
                $ticketDate->setKpiPaDep3(10);
            }
            $this->em->persist($ticket);
            $this->em->persist($ticketDate);
        }
        if ($inverter == "19")dump($ticket);
    }

    private function getLastTicket($anlage, $time, $errorCategory, $inverter): mixed
    {
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        if (strtotime($time) - 900 < strtotime($sungap['sunrise'])) return $this->getTicketYesterday($anlage, $time, $errorCategory,  $inverter);
        else return  $this->getLastTicketInverter($anlage, $time, $errorCategory, $inverter);
    }

    /**
     * this is normal function for retrieval of previous tickets
     * @param $anlage
     * @param $time
     * @param $errorCategory
     * @param $inverter
     * @return mixed
     */
    private function getLastTicketInverter($anlage, $time, $errorCategory, $inverter): mixed
    {
        $ticket = $this->ticketRepo->findByAnlageInverterTime($anlage, $time, $errorCategory, $inverter); // we try to retrieve the ticket in the previous quarter
        return $ticket != null ? $ticket[0] : null;
    }

    /**
     * We will use this function to retrieve all the tickets from yesterday (work in progress to link tickets)
     * @param $anlage
     * @param $time
     * @param $errorCategory
     * @param $inverter
     * @return mixed
     */
    private function getTicketYesterday($anlage, $time, $errorCategory, $inverter): mixed
    {
        $today = date('Y-m-d', strtotime($time));
        $yesterday = date('Y-m-d', strtotime($time) - 86400); // this is the date of yesterday
        $lastQuarterYesterday = self::getLastQuarter($this->weather->getSunrise($anlage, $yesterday)['sunset']); // the last quarter of yesterday
        $ticket = $this->ticketRepo->findLastByAnlageInverterTime($anlage, $today, $lastQuarterYesterday, $errorCategory, $inverter); // we try to retrieve the last quarter of yesterday
        return $ticket != null ? $ticket[0] : null;
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
// *************************************** MULTI TICKET GENERATION (NOT IN USE AT THE TIME) ***********************************************************

    /**
     * main function to generate tickets with multi inverter
     * @param Anlage $anlage
     * @param string|null $time
     * @return string
     */
    private function checkSystemMulti(Anlage $anlage, ?string $time = null): string
    {
        if ($time === null) {
            $time = $this->getLastQuarter(date('Y-m-d H:i:s'));
        }
        // we look 2 hours in the past to make sure the data we are using is stable
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        $time = G4NTrait::timeAjustment($time, -2);
        if (($time >= $sungap['sunrise']) && ($time <= $sungap['sunset'])) {
            $plant_status = self::RetrievePlant($anlage, $time);

            if ($plant_status['ppc'] === false) {


                $message = "Data gap in Inverter(s): " . $plant_status['Gap'];
                $this->generateTicketsMulti('', DATA_GAP, $anlage, $plant_status['Gap'], $time, $message);

                $message = "Power Error in Inverter(s): " . $plant_status['Power0'];
                $this->generateTicketsMulti(EFOR, INVERTER_ERROR, $anlage, $plant_status['Power0'], $time, $message);

                $message = "Grid Error in Inverter(s): " . $plant_status['Vol'];
                $this->generateTicketsMulti('', GRID_ERROR, $anlage, $plant_status['Vol'], $time, $message);

            } else {
                $errorType = OMC;
                $errorCategorie = EXTERNAL_CONTROL;
                $this->generateTicketsMulti(OMC, $errorCategorie, $anlage, '*', $time, "");
                $this->generateTicketsMulti('', DATA_GAP, $anlage, '', $time, "");
                $this->generateTicketsMulti(EFOR, INVERTER_ERROR, $anlage, '', $time, "");
                $this->generateTicketsMulti('', GRID_ERROR, $anlage, '', $time, "");
            }
        }

        return 'success';
    }

    /**
     * Given all the information needed to generate a ticket, the tickets are created and commited to the db
     * @param $errorType
     * @param $errorCategorie
     * @param $anlage
     * @param $inverter
     * @param $time
     * @param $message
     * @return void
     */
    private function generateTicketsMulti($errorType, $errorCategorie, $anlage, $inverter, $time, $message)
    {

        $ticketOld = $this->getLastTicket($anlage, $time, $errorCategorie);

        if ($ticketOld !== null) {
            if ($ticketOld->getInverter() == $inverter) {
                $ticketDate = $ticketOld->getDates()->last();
                $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
                $end->getTimestamp();
                $ticketOld->setEnd($end);
                $ticketDate->setEnd($end);
                $this->em->persist($ticketDate);
                $this->em->persist($ticketOld);
            } else {
                $ticketOld->setOpenTicket(false);
                $this->em->persist($ticketOld);
                $ticketOld = null;
            }
        }
        if ($inverter != "") {
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
                $ticket->setAlertType($errorCategorie); //  category = alertType (bsp: datagap, inverter power, etc.)
                $ticketDate->setAlertType($errorCategorie);
                $ticket->setErrorType($errorType); // type = errorType (Bsp:  SOR, EFOR, OMC)
                $ticketDate->setErrorType($errorType);
                if ($errorCategorie == EXTERNAL_CONTROL) {
                    $ticket->setInverter('*');
                    $ticketDate->setInverter('*');
                } else {
                    $ticket->setInverter($inverter);
                    $ticketDate->setInverter($inverter);
                }

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
            $this->em->flush();
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


}