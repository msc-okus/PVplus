<?php

namespace App\Service\TicketsGeneration;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Entity\Ticket;
use App\Entity\TicketDate;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use App\Service\FunctionsService;
use App\Service\MessageService;
use App\Service\PdoService;
use App\Service\WeatherFunctionsService;
use App\Service\WeatherServiceNew;
use DateTimeZone;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use PDO;
use phpDocumentor\Reflection\Types\Boolean;

class InternalAlertSystemService
{
    use G4NTrait;
    private $ticketArray;
    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private $userPlant,
        private $passwordPlant,
        private AnlagenRepository       $anlagenRepository,
        private WeatherServiceNew       $weather,
        private WeatherFunctionsService $weatherFunctions,
        private AnlagenRepository       $AnlRepo,
        private EntityManagerInterface  $em,
        private MessageService          $mailservice,
        private FunctionsService        $functions,
        private StatusRepository        $statusRepo,
        private TicketRepository        $ticketRepo,
        private PdoService $pdo)
    {
    }

    public function generateTicketsInterval(Anlage $anlage, string $from, string $to = null): void
    {
        $this->checkSystem($anlage,  $from,  $to);
    }

    /**
     * Generate tickets for the given time
     * @param Anlage $anlage
     * @param string $from
     * @param string $to
     * @return string
     */

    public function checkSystem(Anlage $anlage, string $from, ?string $to = null  ): string
    {

        $fromStamp = strtotime($from);
        if ($to != null) $toStamp = strtotime($to);
        else $toStamp = strtotime($from);

        $ticketArray['countIrr'] = false;
        $ticketArray['countExp'] = false;
        $ticketArray['countPPC'] = false;

            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
                $plant_status = self::RetrievePlant($anlage, date('Y-m-d H:i:00', $stamp));


                if ($plant_status['countIrr'] == true) $this->generateTickets(90, $anlage, date('Y-m-d H:i:00', $stamp), "");
                if ($plant_status['countExp'] == true) $this->generateTickets(91, $anlage, date('Y-m-d H:i:00', $stamp), "");
                if ($plant_status['countPPC'] == true) $this->generateTickets(92, $anlage, date('Y-m-d H:i:00', $stamp), "");
            }


        dump($plant_status);
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
        $conn = $this->pdo->getPdoPlant();
        $offsetServer = new DateTimeZone("Europe/Luxembourg");
        $plantoffset = new DateTimeZone($this->getNearestTimezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(), strtoupper($anlage->getCountry())));
        $totalOffset = $plantoffset->getOffset(new DateTime("now")) - $offsetServer->getOffset(new DateTime("now"));
        $time = date('Y-m-d H:i:s', strtotime($time) - $totalOffset);
        $tolerance = 240; // here we have the ammount of time we "look" in the past to generate the internal errors
        $begin = date('Y-m-d H:i:s', strtotime($time) - $totalOffset - $tolerance);

        $sql = "SELECT *
                FROM ". $anlage->getDbNameWeather()."
                WHERE stamp BETWEEN '$begin' AND'$time' ";
        $resp = $conn->query($sql);
        $plantStatus['countIrr']  = $resp->rowCount() === 0;
        $sql = "SELECT *
                FROM ". $anlage->getDbNameDcSoll()."
                WHERE stamp BETWEEN '$begin' AND'$time' ";
        $resp = $conn->query($sql);
        $plantStatus['countExp']  = $resp->rowCount() !== count($anlage->getInverterFromAnlage());
        $sql = "SELECT *
                FROM ". $anlage->getDbNamePPC()."

                WHERE stamp BETWEEN '$begin' AND'$time' ";
        $resp = $conn->query($sql);

        $plantStatus['countPPC'] =  $resp->rowCount() === 0;

        return $plantStatus;
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
    private function generateTickets($errorCategorie, $anlage, $time, $message)
    {
        dump($time);
        $ticketOld = $this->getLastTicket($anlage, $time, $errorCategorie);// we retrieve here the previous ticket (if any)
        //this could be the ticket from  the previous quarter or the last ticket from  the previous day
        if ($ticketOld !== null) { // is there is a previous ticket we just extend it
            $end = date_create(date('Y-m-d H:i:s', strtotime($time) + 900));
            $end->getTimestamp();
            $ticketOld->setEnd($end);
            $ticketOld->setOpenTicket(true);
            $this->em->persist($ticketOld);
        } else {// if there is no previous ticket we create a new one, the next lines are just setting the properties of the ticket
            $ticket = new Ticket();dump('nes');
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

        $ticket = $this->ticketRepo->findByAnlageInverterTime($anlage, $time, $errorCategory, "*"); // we try to retrieve the ticket in the previous quarter
        dump($errorCategory, $time, $ticket);
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