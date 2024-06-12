<?php

namespace App\Service\TicketsGeneration;

use App\Entity\Anlage;
use App\Entity\Ticket;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use App\Service\FunctionsService;
use App\Service\MessageService;
use App\Service\PdoService;
use App\Service\WeatherFunctionsService;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;

class InternalAlertSystemService
{
    use G4NTrait;

    public function __construct(
        private readonly AnlagenRepository       $anlagenRepository,
        private readonly WeatherServiceNew       $weather,
        private readonly WeatherFunctionsService $weatherFunctions,
        private readonly AnlagenRepository       $AnlRepo,
        private readonly EntityManagerInterface  $em,
        private readonly MessageService          $mailservice,
        private readonly FunctionsService        $functions,
        private readonly StatusRepository        $statusRepo,
        private readonly TicketRepository        $ticketRepo,
        private readonly PdoService              $pdo
    ){
    }

    /**
     * @throws InvalidArgumentException
     */
    public function generateTicketsInterval(Anlage $anlage, string $from, string $to = null): void
    {
        $this->checkSystem($anlage,  $from,  $to);
    }

    /**
     * Generate tickets for the given time
     * @param Anlage $anlage
     * @param string $time
     * @return string
     * @throws InvalidArgumentException
     */
    public function checkSystem(Anlage $anlage, string $time): string
    {
        $timeStamp = strtotime($time);

        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', $timeStamp));
        $time = self::timeAjustment($timeStamp, - 2);

        // nur ausführen wenn $time zwische sonnen auf und sonnen untergan liegt
        if (($time > $sungap['sunrise']) && ($time <= $sungap['sunset'])) {
                $plant_status = self::RetrievePlant($anlage, date('Y-m-d H:i:00', strtotime($time)));
                if ($plant_status['countIrr'] === true) $this->generateTickets(91, $anlage, date('Y-m-d H:i:00', strtotime($time)), "");
                if ($plant_status['countExp'] === true) $this->generateTickets(92, $anlage, date('Y-m-d H:i:00', strtotime($time)), "");
                if ($plant_status['countPPC'] === true) $this->generateTickets(93, $anlage, date('Y-m-d H:i:00', strtotime($time)), "");
        }

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
        $conn = $this->pdo->getPdoPlant();
        $time = date('Y-m-d H:i:s', strtotime($time) );

        // prüfe ob wetter daten vorhanden sind
        $sql = "SELECT *
                FROM ". $anlage->getDbNameWeather()."
                WHERE stamp = '$time' ";
        $resp = $conn->query($sql);

        // prüfe ob die Wetter daten werte enthalten (oder 'null' sind)
        $sql = "SELECT *
                FROM ". $anlage->getDbNameWeather()."
                WHERE stamp ='$time' and g_upper is null and g_lower is null";
        $respNull = $conn->query($sql);

        $plantStatus['countIrr']  = $resp->rowCount() === 0  ||  $respNull->rowCount() === 1;

        // prüfe ob expected datensätze da sind
        $sql = "SELECT *
                FROM ". $anlage->getDbNameDcSoll()."
                WHERE stamp ='$time' ";
        $resp = $conn->query($sql);
        $plantStatus['countExp']  = $resp->rowCount() !== count($anlage->getInverterFromAnlage());

        $plantStatus['countPPC'] = false;

        return $plantStatus;
    }


    /**
     * Given all the information needed to generate a ticket, the tickets are created and commited to the db (single ticket variant)
     * @param $errorCategorie
     * @param $anlage
     * @param $time
     * @param $message
     * @return void
     */
    private function generateTickets($errorCategorie, $anlage, $time, $message): void
    {
        $ticketOld = $this->getLastTicket($anlage, $time, $errorCategorie);// we retrieve here the previous ticket (if any)
        //this could be the ticket from  the previous quarter or the last ticket from  the previous day
        if ($ticketOld !== null) { // is there is a previous ticket we just extend it
            $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
            $end->getTimestamp();
            $ticketOld->setEnd($end);
            $ticketOld->setOpenTicket(true);
            $this->em->persist($ticketOld);
        } else {// if there is no previous ticket we create a new one, the next lines are just setting the properties of the ticket
            $ticket = new Ticket();
            $ticket->setAnlage($anlage);
            $ticket->setStatus('10'); // Status 10 = open
            $ticket->setEditor('Alert system');
            $ticket->setSystemStatus(10);
            $ticket->setPriority(20);
            $ticket->setOpenTicket(true);
            $ticket->setDescription($message);
            $ticket->setCreatedBy("AlertSystem");
            $ticket->setUpdatedBy("AlertSystem");
            $ticket->setProofAM(false);
            $ticket->setInverter('*');
            $ticket->setAlertType($errorCategorie); //  category = alertType (bsp: datagap, inverter power, etc.)
            $ticket->setInternal(true);
            $ticket->setErrorType($errorCategorie); // type = errorType (Bsp:  SOR, EFOR, OMC)
            $begin = date_create(date('Y-m-d H:i:s', strtotime($time)));
            $begin->getTimestamp();
            $ticket->setBegin($begin);
            $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
            $end->getTimestamp();
            $ticket->setEnd($end);
            //default values por the kpi evaluation
            $this->em->persist($ticket);
        }
        $this->em->flush();
    }

    private function getLastTicket($anlage, $time, $errorCategory): mixed
    {

        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        if (strtotime($time) - 900 < strtotime($sungap['sunrise'])) return $this->getTicketYesterday($anlage, $time, $errorCategory,  '*');
        else return  $this->getLastTicketInverter($anlage, $time, $errorCategory, '*');
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