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

    /**
     * this method should be called to generate the tickets
     * no other method from this class should be called manually
     * @param Anlage $anlage
     * @param string $from
     * @param string|null $to
     */
    public function generateTicketsInterval(Anlage $anlage, string $from): void
    {
        $fromStamp = strtotime($from);
        $this->checkSystem($anlage, date('Y-m-d H:i:00', $fromStamp));
    }

    /**
     * Generate tickets for the given time
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
        $timeEnd = $time;
        $timeBegin = G4NTrait::timeAjustment($time, -11.75);

        //here we retrieve the values from the plant and set soma flags to generate tickets

        $plant_status = self::RetrievePlant($anlage, $timeBegin, $timeEnd);
        dd($plant_status);

        $this->em->flush();

        return 'success';
    }
    /**
     * main function to retrieve plant status for a given time
     * @param Anlage $anlage
     * @param $time
     * @return array
     */
    private function RetrievePlant(Anlage $anlage, $beginTime, $endTime): array
    {
        $conn = self::getPdoConnection();
        $offsetServer = new DateTimeZone("Europe/Luxembourg");
        $plantoffset = new DateTimeZone($this->getNearestTimezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(), strtoupper($anlage->getCountry())));
        $totalOffset = $plantoffset->getOffset(new DateTime("now")) - $offsetServer->getOffset(new DateTime("now"));
        $beginTime = date('Y-m-d H:i:s', strtotime($beginTime) - $totalOffset);
        $endTime = date('Y-m-d H:i:s', strtotime($endTime) - $totalOffset);
        $sql = "SELECT *
                FROM ". $anlage->getDbNameWeather()."
                WHERE stamp BETWEEN '$beginTime' AND '$endTime'";

        $resp = $conn->query($sql);

        $plantStatus['countIrr']  = $resp->rowCount();

        $sql = "SELECT *
                FROM ". $anlage->getDbNameDcSoll()."
                WHERE stamp BETWEEN '$beginTime' AND '$endTime' group by stamp";

        $resp = $conn->query($sql);

        $plantStatus['countExp']  = $resp->rowCount();

        $sql = "SELECT *
                FROM ". $anlage->getDbNamePPC()."
                WHERE stamp BETWEEN '$beginTime' AND '$endTime' ";

        $resp = $conn->query($sql);

        $plantStatus['countPPC'] =  $resp->rowCount();

        return $plantStatus;
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