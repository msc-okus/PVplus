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
    private $ticketArray;

    /**
     * this method should be called to generate the tickets
     * no other method from this class should be called manually
     * @param Anlage $anlage
     * @param string $from
     * @param string|null $to
     */
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
    public function checkSystem(Anlage $anlage, string $from, string $to  ): string
    {

        $fromStamp = strtotime($from);
        $toStamp = strtotime($to);
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
                $plant_status = self::RetrievePlant($anlage, date('Y-m-d H:i:00', $stamp));
                $ticketArray[date('Y-m-d H:i:00', $stamp)]['countIrr'] = $plant_status['countIrr'];
                $ticketArray[date('Y-m-d H:i:00', $stamp)]['countExp'] = $plant_status['countExp'];
                $ticketArray[date('Y-m-d H:i:00', $stamp)]['countPPC'] = $plant_status['countPPC'];
            }
            foreach ($ticketArray as $key => $value){
                $previousQuarter = date('Y-m-d H:i:00', strtotime($key) - 900);
                $nextQuarter = date('Y-m-d H:i:00', strtotime($key) + 900);
                if ($value['countIrr'] == true){
                    if (isset($ticketArray[$previousQuarter]['countIrr']) && $ticketArray[$previousQuarter]['countIrr'] === false){
                        $ticket = new Ticket();
                    }
                }
                dump($key);
            }
        dd($ticketArray);
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
        $conn = self::getPdoConnection();
        $offsetServer = new DateTimeZone("Europe/Luxembourg");
        $plantoffset = new DateTimeZone($this->getNearestTimezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(), strtoupper($anlage->getCountry())));
        $totalOffset = $plantoffset->getOffset(new DateTime("now")) - $offsetServer->getOffset(new DateTime("now"));
        $time = date('Y-m-d H:i:s', strtotime($time) - $totalOffset);
        $sql = "SELECT *
                FROM ". $anlage->getDbNameWeather()."
                WHERE stamp = '$time' ";

        $resp = $conn->query($sql);

        $plantStatus['countIrr']  = $resp->rowCount() === 0;

        $sql = "SELECT *
                FROM ". $anlage->getDbNameDcSoll()."
                WHERE stamp = '$time'";

        $resp = $conn->query($sql);

        $plantStatus['countExp']  = $resp->rowCount() !== count($anlage->getInverterFromAnlage());

        $sql = "SELECT *
                FROM ". $anlage->getDbNamePPC()."
                WHERE stamp = '$time' ";

        $resp = $conn->query($sql);

        $plantStatus['countPPC'] =  $resp->rowCount() === 0;

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