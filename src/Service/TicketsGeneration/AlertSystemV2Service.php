<?php

namespace App\Service\TicketsGeneration;

use App\Entity\Anlage;
use App\Entity\SystemLog;
use App\Entity\Ticket;
use App\Entity\TicketDate;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\SystemLogRepository;
use App\Repository\TicketRepository;
use App\Service\FunctionsService;
use App\Service\G4NSendMailService;
use App\Service\MessageService;
use App\Service\PdoService;
use App\Service\WeatherFunctionsService;
use App\Service\WeatherServiceNew;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use PDO;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


class AlertSystemV2Service
{
    use G4NTrait;

    private bool $irr = false;

    public function __construct(
        private readonly PdoService              $pdoService,
        private readonly AnlagenRepository       $anlagenRepository,
        private readonly WeatherServiceNew       $weather,
        private readonly WeatherFunctionsService $weatherFunctions,
        private readonly AnlagenRepository       $AnlRepo,
        private readonly EntityManagerInterface  $em,
        private readonly MessageService          $mailservice,
        private readonly FunctionsService        $functions,
        private readonly StatusRepository        $statusRepo,
        private readonly TicketRepository        $ticketRepo,
        private readonly  G4NSendMailService     $g4NSendMailService,
        private readonly SystemLogRepository     $sysLogRepo,
    )
    {

    }

    /**
     * this method should be called to generate the tickets
     * no other method from this class should be called manually
     * @param Anlage $anlage
     * @param string $from
     * @param string|null $to
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function generateTicketsInterval(Anlage $anlage, string $from, ?string $to = null): void
    {
        $offsetServer = new DateTimeZone("Europe/Luxembourg");
        $plantoffset = new DateTimeZone($anlage->getNearestTimezone());
        $totalOffset = $plantoffset->getOffset(new DateTime("now")) - $offsetServer->getOffset(new DateTime("now"));
        $fromStamp = strtotime($from) + $totalOffset;

        if ($to !== null) {
            $toStamp = strtotime($to);
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
                $this->checkSystem($anlage, date('Y-m-d H:i:00', $stamp));
            }
        } else {
            $this->checkSystem($anlage, date('Y-m-d H:i:00', $fromStamp));
        }
    }

    /**
     * this method should be called to generate the tickets
     * no other method from this class should be called manually
     * @param Anlage $anlage
     * @param string $from
     * @param string|null $to
     * @throws InvalidArgumentException
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
        else {
            $this->checkExpected($anlage, date('Y-m-d', $fromStamp));
        }
    }

    /**
     * this method should be called from the command to join the tickets
     * not in use now
     * no other method from this class should be called manually
     * @deprecated
     * @param Anlage $anlage
     * @param string $from
     * @param string $to
     * @throws InvalidArgumentException
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
     * Generate tickets for the given time, check if there is an older ticket for same inverter with same error.
     * Write new ticket to database or extend existing ticket with new end time.
     * @param Anlage $anlage
     * @param string|null $time
     * @throws InvalidArgumentException
     */
    public function checkExpected(Anlage $anlage, ?string $time = null): void
    {
        $percentajeDiff = $anlage->getPercentageDiff();
        $invCount = count($anlage->getInverterFromAnlage());
        $conn = $this->pdoService->getPdoPlant();
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        $powerArray = "";

        if ($anlage->isExpectedTicket() && $anlage->getAnlType() != "masterslave"){
            $timeEnd =  $this->getLastQuarter(date("Y-m-d H:i",strtotime($sungap['sunset'])));
            $timeBegin = $this->getLastQuarter(date("Y-m-d H:i",strtotime($sungap['sunrise']))); // we start looking one hour in the past to check the power and expected
            $counter = 0;
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
                                if ($powerArray == "")
                                    $powerArray = $value['inverter'];
                                else
                                    $powerArray = $powerArray . ", " . $value['inverter'];
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
                                if ($powerArray == "")
                                    $powerArray = $expected['inverter'];
                                else
                                    $powerArray = $powerArray . ", " . $expected['inverter'];
                            }
                        }
                    }
                    break;
            }
            if ($counter == $invCount)  $powerArray = "*";

            if ($powerArray != ""){
                $message = "Power below ".$percentajeDiff." % of Expected: " . $powerArray;
                $this->generateTicketsExpected(10, $anlage, $powerArray, $timeBegin, $timeEnd, $message);
            }
        }

    }

    /**
     * Generate tickets for the given time, check if there is an older ticket for same inverter with same error.
     * Write new ticket to database or extend existing ticket with new end time.
     * @param Anlage $anlage
     * @param string|null $time
     * @return string
     * @throws InvalidArgumentException
     */
    public function checkSystem(Anlage $anlage, ?string $time = null): string
    {

        if ($time === null) {

            $time = $this->getLastQuarter(date('Y-m-d H:i:s'));
        }
        // we look 2 hours in the past to make sure the data we are using is stable (all is okay with the data)
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        $time = self::timeAjustment($time, -2);
        if (($time >= $sungap['sunrise']) && ($time <= $sungap['sunset'])) {

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
            $anlType = $anlage->getAnlType();
            if ( $plant_status['Irradiation'] == false ) {

                if ($plant_status['ppc'] != null && $plant_status['ppc']){
                    $this->generateTickets(ticket::OMC, ticket::EXTERNAL_CONTROL, $anlage, ["*"], $time, $plant_status['ppc'], false);
                    $this->generatePPCTickets(ticket::OMC, $anlage,  $time);
                }
                if ($plant_status['Gap'] != null && count($plant_status['Gap']) > 0){
                    $this->generateTickets('', ticket::DATA_GAP, $anlage, $plant_status['Gap'], $time, ($plant_status['ppc']), false);}
                if ($anlType != "masterslave"){


                    if ($plant_status['Power0'] != null && count($plant_status['Power0']) > 0 ){
                        if (!$anlage->isPpcBlockTicket() or !$plant_status['ppc']){
                            $this->generateTickets(ticket::EFOR, ticket::INVERTER_ERROR, $anlage, $plant_status['Power0'], $time, ($plant_status['ppc']), false);}
                    }
                }
                if ($plant_status['Vol'] != null && (count($plant_status['Vol']) === count($anlage->getInverterFromAnlage())) or ($plant_status['Vol'] == "*"))
                {

                    $this->generateTickets('', ticket::GRID_ERROR, $anlage, $plant_status['Vol'], $time, ($plant_status['ppc']), false);}
            }else {
                $this->generateTickets('', 100, $anlage, ['*'], $time, $plant_status['ppc'], true);
            }
        }

        $sysLog = $this->sysLogRepo->findOneBy(['anlage' => $anlage]);
        if ($sysLog != null){
            if ($sysLog->getLastTicketExecutionDate()->getTimestamp() < strtotime($time)){
                $execDate = date_create(date('Y-m-d H:i:s', strtotime($time)));
                $sysLog->setLastTicketExecutionDate($execDate);
                $sysLog->setLastTicketExecution("Successful execution");
            }
        }
        else{
            $sysLog = new SystemLog();
            $sysLog->setAnlage($anlage);
            $execDate = date_create(date('Y-m-d H:i:s', strtotime($time)));
            $sysLog->setLastTicketExecutionDate($execDate);
            $sysLog->setLastTicketExecution("Successful execution and log created");
        }
        $this->em->persist($sysLog);

        $this->em->flush();

        return 'success';
    }

    /**
     * main function to retrieve plant status for a given time
     * @param Anlage $anlage
     * @param $time
     * @return array
     * @throws InvalidArgumentException
     */
    private function RetrievePlant(Anlage $anlage, $time): array
    {
        $totalOffset = 0;
        $time = date('Y-m-d H:i:s', strtotime($time) - $totalOffset);
        $irrLimit = $anlage->getMinIrrThreshold() != "0" ? (float)$anlage->getMinIrrThreshold() : 20; // we get the irradiation limit from the plant config
        $freqLimitTop = $anlage->getFreqBase() + $anlage->getFreqTolerance();
        $freqLimitBot = $anlage->getFreqBase() - $anlage->getFreqTolerance();
        //we get the frequency values
        $voltLimit = 0;
        $conn = $this->pdoService->getPdoPlant();
        $powerThreshold = (float) $anlage->getPowerThreshold() / 4;
        $invCount = count($anlage->getInverterFromAnlage());
        $irradiation = $this->weatherFunctions->getIrrByStampForTicket($anlage, date_create($time));


        if ($irradiation !== null && $irradiation < $irrLimit) $this->irr = true; // about irradiation === null, it is better to miss a ticket than to have a false one
        else $this->irr = false;

        if($irradiation === null or $irradiation == 0){
            $return['Irradiation'] = true;
        }
        else{
            $return['Irradiation'] = false;
        }

        if ($anlage->getHasPPC()) {
            $sqlPpc = 'SELECT * 
                        FROM ' . $anlage->getDbNamePPC() . " 
                        WHERE stamp = '$time' ";
            $respPpc = $conn->query($sqlPpc);
            if ($respPpc->rowCount() === 1) {
                $ppdData = $respPpc->fetch(PDO::FETCH_ASSOC);

                $return['ppc'] = ((($ppdData['p_set_rpc_rel'] !== null && $ppdData['p_set_rpc_rel'] < 100) || ($ppdData['p_set_gridop_rel'] !== null && $ppdData['p_set_gridop_rel'] < 100)));
            } else {
                $return['ppc'] = false;
            }
        } else {
            $return['ppc'] = false;
        }
            $sqlAct = 'SELECT b.unit as unit 
                    FROM (db_dummysoll a left JOIN ' . $anlage->getDbNameIst() . " b on a.stamp = b.stamp)
                    WHERE a.stamp = '$time' AND  b.wr_pac <= $powerThreshold ";

            $resp = $conn->query($sqlAct);
            $result0 = $resp->fetchAll(PDO::FETCH_ASSOC);
            $sqlNull = 'SELECT b.unit as unit 
                    FROM (db_dummysoll a left JOIN ' . $anlage->getDbNameIst() . " b on a.stamp = b.stamp)
                    WHERE a.stamp = '$time' AND  b.wr_pac is null ";
            $resp = $conn->query($sqlNull);
            $resultNull = $resp->fetchAll(PDO::FETCH_ASSOC);
            if ($anlage->isGridTicket()) {
                $sqlVol = "SELECT b.unit 
                    FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b on a.stamp = b.stamp)
                    WHERE a.stamp = '$time' AND  (b.u_ac < " . $voltLimit . " OR b.frequency < " . $freqLimitBot . " OR b.frequency > " . $freqLimitTop . ")";
                $resp = $conn->query($sqlVol);
                //here if there is no plant control we check the values and get the information to create the tickets
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

    private function generatePPCTickets($errorType,Anlage $anlage, $time): void{
        if ($anlage->getSettings()->getPpcAutoTicketBehavior() != "nothing"){
            if ($anlage->getSettings()->getPpcAutoTicketBehavior() == "replace") {
                $cat = 73;
            } else {
                $cat = 72;
            }
            $previousTicket = $this->getAllTicketsByCat($anlage, $time, $cat)[0];
            if ($previousTicket !== null) {
                $ticketDate = $previousTicket->getDates()->last();
                $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
                $end->getTimestamp();
                $previousTicket->setEnd($end);
                $previousTicket->setOpenTicket(true);
                $ticketDate->setEnd($end);
                $this->em->persist($ticketDate);
                $this->em->persist($previousTicket);
            } else {
                $ticket = new Ticket();
                $ticket->setErrorType($cat);
                $ticket = new Ticket();
                $ticketDate = new TicketDate();
                $ticketDate->setAnlage($anlage);
                $ticketDate->setStatus('10');
                $ticketDate->setSystemStatus(10);
                $ticketDate->setPriority(10);
                $ticketDate->setCreatedBy("AlertSystem");
                $ticketDate->setUpdatedBy("AlertSystem");
                $ticket->setStatus('10'); // Status 10 = open
                $ticket->setEditor('Alert system');
                $ticket->setSystemStatus(10);
                $ticket->setPriority(10);
                $ticket->setOpenTicket(true);
                $ticket->setCreatedBy("AlertSystem");
                $ticket->setUpdatedBy("AlertSystem");
                $ticket->setProofAM(false);
                $ticket->setCreationLog("Irradiation Limit: " . $anlage->getMinIrrThreshold() . "; Power Limit: " . $anlage->getPowerThreshold());
                $ticket->setAlertType($cat); //  category = alertType (bsp: datagap, inverter power, etc.)
                $ticketDate->setAlertType($cat);
                $ticket->setInverter('*');
                $ticketDate->setInverter('*');
                $ticket->setAnlage($anlage);
                $begin = date_create(date('Y-m-d H:i:s', strtotime($time)));
                $begin->getTimestamp();
                $ticket->setBegin($begin);
                $ticketDate->setBegin($begin);
                $ticket->addDate($ticketDate);
                $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
                $end->getTimestamp();
                $ticketDate->setEnd($end);
                $ticket->setEnd($end);
                if ($cat == 72) {
                    if ($anlage->getSettings()->getPpcAutoTicketPaBehavior() == "skip") {
                        $ticketDate->setPRExcludeMethod(10);
                    } else if ($anlage->getSettings()->getPpcAutoTicketPaBehavior() == "replace") {
                        $ticketDate->setPRExcludeMethod(20);
                    }
                } else if ($cat == 73) {
                    if ($anlage->getSettings()->getPpcAutoTicketReplaceBy() == "g4n_exp") {
                        $ticketDate->setReplaceEnergyG4N(true);
                        $ticketDate->setReplaceIrr($anlage->getSettings()->isPpcAutoTicketReplaceIrr());
                    } else {
                        $ticketDate->setReplaceEnergy(true);
                        $ticketDate->setUseHour($anlage->getSettings()->isPpcAutoTicketUseHour());
                        $ticketDate->setReplaceIrr($anlage->getSettings()->isPpcAutoTicketReplaceIrr());
                    }
                }
                if ($anlage->getSettings()->isPpcAutoTicketUseHour()) {
                    $ticketDate->setUseHour(true);
                } else {
                    $ticketDate->setUseHour(false);
                }
                $this->em->persist($ticket);
                $this->em->persist($ticketDate);
            }
        }
    }
    /**
     * Given all the information needed to generate a ticket, the tickets are created and committed to the db (single ticket variant)
     * @param $errorType
     * @param $errorCategorie
     * @param Anlage $anlage
     * @param $inverter
     * @param $time
     * @param $message
     * @param $PPC
     * @param bool|null $fullGap
     * @return void
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function generateTickets($errorType, $errorCategorie,Anlage $anlage, $inverter, $time, $PPC, ?bool $fullGap = false): void
    {

            $ticketArray = $this->getAllTicketsByCat($anlage, $time, $errorCategorie);// we retrieve here the previous ticket (if any)
            if ($ticketArray != []) {

                foreach ($ticketArray as $ticketOld) {
                    $endclose = date_create(date('Y-m-d H:i:s', strtotime($time)));
                    $result = self::subArrayFromArray($inverter, $ticketOld->getInverterArray()); // this is the most important part of this function
                    // it is a function that given 2 arrays (actual faulty inverter and previous faulty inverters) will return 3 arrays:
                    // array of inverters present in the first array but not in the second (those are inverters failing in the previous quarter that now work fine)
                    // array of inverters present in the second array but not in the first (those are inverters that were working fine in the previous interval but now are faulty)
                    // the intersection: array of inverters present in both arrays
                    // for the first array we have to put the in a separated ticket and close it, for the second we have to create a new ticket with them and for the last we have to extend the previous ticket but only with those inverters
                    // IMPORTANT: if you are not sure about the changes you are going to do in this function please DO NOT DO THEM. This took a lot of thinking and even tho is complex its logic have been working fail-proof for months
                    $inverter = $result['array1'];
                    $intersection = implode(', ', $result['intersection']);
                    $Ticket2Inverters = implode(', ', $result['array2']);
                    if ($intersection !== ""){ // here we link (in time) and split (per inverter) base on the logic previously mentioned
                        $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
                        $end->getTimestamp();
                        $ticketDate = $ticketOld->getDates()->last();
                        if ($Ticket2Inverters !== "") {
                            $ticketNew = new Ticket();
                            $ticketNew->copyTicket($ticketOld);
                            $ticketNew->setInverter($intersection);
                            $ticketNew->setEnd($end);
                            $ticketNew->setOpenTicket(true);
                            $ticketNew->getDates()->last()->setEnd($end);
                            $ticketDate->setEnd($end);
                            $ticketNew->setCreatedBy("AlertSystem");
                            $ticketNew->setUpdatedBy("AlertSystem");
                            $this->em->persist($ticketNew);


                        } else {
                            $ticketOld->setEnd($end);
                            $ticketOld->setOpenTicket(true);
                            $ticketOld->setInverter($intersection);
                            $ticketDate->setEnd($end);
                            $this->em->persist($ticketOld);

                        }
                    }
                    if ($Ticket2Inverters !== ""){
                        $ticketOld->setOpenTicket(false);
                        $ticketOld->setInverter($Ticket2Inverters);
                        $ticketOld->getDates()->last()->setEnd($endclose);
                        $this->em->persist($ticketOld);


                    }
                }
            }
            if ($inverter != "*" ) {

                $restInverter = implode(', ', $inverter);

            } else {

                $restInverter = $inverter;
            }
            if ($restInverter != "" && ($errorCategorie == 100 or $this->irr === false)) { // this is the easy part, here we create a new ticket if there is nothing else to link with, so this is the actual part where new tickets are created

                //we set the internal values of the ticket based on the type of error and the current state of the system (mostly PPC signals)
                $ticket = new Ticket();
                $ticketDate = new TicketDate();
                $ticketDate->setAnlage($anlage);
                $ticketDate->setStatus('10');
                $ticketDate->setSystemStatus(10);
                $ticketDate->setPriority(10);
                $ticketDate->setCreatedBy("AlertSystem");
                $ticketDate->setUpdatedBy("AlertSystem");
                $ticket->setAnlage($anlage);
                $ticket->setStatus('10'); // Status 10 = open
                $ticket->setEditor('Alert system');
                $ticket->setSystemStatus(10);
                $ticket->setPriority(10);
                $ticket->setOpenTicket(true);
                $ticket->setCreatedBy("AlertSystem");
                $ticket->setUpdatedBy("AlertSystem");
                $ticket->setProofAM(false);
                $ticket->setCreationLog("Irradiation Limit: ". $anlage->getMinIrrThreshold()."; Power Limit: ".$anlage->getPowerThreshold());
                $ticket->setAlertType($errorCategorie); //  category = alertType (bsp: datagap, inverter power, etc.)
                $ticketDate->setAlertType($errorCategorie);
                $ticket->setErrorType($errorType); // type = errorType (Bsp:  SOR, EFOR, OMC)
                $ticketDate->setErrorType($errorType);
                if ($errorCategorie == ticket::EXTERNAL_CONTROL) {
                    $ticket->setInverter('*');
                    $ticketDate->setInverter('*');
                } else {
                    $ticket->setInverter($restInverter);
                    $ticketDate->setInverter($restInverter);
                }
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
                if ( $errorCategorie == 20) {
                    if (!$PPC) {
                            $ticketDate->setDataGapEvaluation(10);
                            $ticketDate->setKpiPaDep1(10);
                            $ticketDate->setKpiPaDep2(10);
                            $ticketDate->setKpiPaDep3(10);
                    } else{
                            $ticketDate->setDataGapEvaluation(10);
                            $ticketDate->setKpiPaDep1(20);
                            $ticketDate->setKpiPaDep2(10);
                            $ticketDate->setKpiPaDep3(10);
                    }
                }
                if ($errorCategorie == 10 && $fullGap) $ticketDate->setDataGapEvaluation(20);



                $this->em->persist($ticket);
                $this->em->persist($ticketDate);
                $this->em->flush();


                //send alertMessage
                $this->g4NSendMailService->sendAlertMessage($anlage,$ticket);

            }
    }

    /**
     * Given all the information needed to generate a ticket, the tickets are created and commited to the db (single ticket variant)
     * @param $errorType
     * @param $anlage
     * @param $inverter
     * @param $begin
     * @param $end
     * @param $message
     * @return void
     */
    private function generateTicketsExpected($errorType, $anlage, $inverter, $begin, $end, $message): void
    {
        $ticketOld = $this->getTicketYesterday($anlage, $begin, 60,  $inverter);// we retrieve here the previous ticket (if any)
        //this could be the ticket from  the previous quarter or the last ticket from  the previous day
        if ($ticketOld !== null) { // is there is a previous ticket we just extend it
            $ticketDate = $ticketOld->getDates()->last();
            $end = date_create(date('Y-m-d H:i:s', strtotime($end) ));
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
            $ticketDate->setCreatedBy("AlertSystem");
            $ticketDate->setUpdatedBy("AlertSystem");
            $ticket->setAnlage($anlage);
            $ticket->setStatus('10'); // Status 10 = open
            $ticket->setEditor('Alert system');
            $ticket->setSystemStatus(10);
            $ticket->setPriority(10);
            $ticket->setOpenTicket(true);
            $ticket->setCreatedBy("AlertSystem");
            $ticket->setUpdatedBy("AlertSystem");
            $ticket->setInverter($inverter);
            $ticketDate->setInverter($inverter);
            $ticket->setAlertType(60); //  category = alertType (bsp: datagap, inverter power, etc.)
            $ticketDate->setAlertType(60);
            $ticket->setErrorType($errorType); // type = errorType (Bsp:  SOR, EFOR, OMC)
            $ticketDate->setErrorType($errorType);
            $begin = date_create(date('Y-m-d H:i:s', strtotime($begin) ));
            $begin->getTimestamp();
            $ticket->setBegin($begin);
            $ticketDate->setBegin($begin);
            $ticket->addDate($ticketDate);
            $end = date_create(date('Y-m-d H:i:s', strtotime($end) ));
            $end->getTimestamp();
            $ticketDate->setEnd($end);
            $ticket->setEnd($end);
            //default values por the kpi evaluation
            if ($errorType == ticket::EFOR) {
                $ticketDate->setKpiPaDep1(10);
                $ticketDate->setKpiPaDep2(10);
                $ticketDate->setKpiPaDep3(10);
            }
            $this->em->persist($ticket);
            $this->em->persist($ticketDate);
        }
    }

    /**
     * @param $anlage
     * @param $time
     * @param $errorCategory
     * @return mixed
     */
    private function getAllTicketsByCat($anlage, $time, $errorCategory): mixed
    {
        $yesterday = date('Y-m-d', strtotime($time) - 86400); // this is the date of yesterday
        $lastQuarterYesterday = self::getLastQuarter($this->weather->getSunrise($anlage, $yesterday)['sunset']);
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        if (strtotime($time) - 900 < strtotime($sungap['sunrise'])) {
            return $this->ticketRepo->findByAnlageTimeYesterday($anlage, $lastQuarterYesterday, $time, $errorCategory);
        } else {
            return $this->ticketRepo->findByAnlageTime($anlage, $time, $errorCategory);
        }
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
