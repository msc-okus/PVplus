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
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use PDO;
use App\Service\PdoService;

class AlertSystemWeatherService
{
    use G4NTrait;

    public function __construct(
private PdoService $pdoService,
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
     * this is the function we use to generate tickets from the command
     * @param Anlage $anlage
     * @param string $from
     * @param string|null $to
     * @return void
     */
    public function generateWeatherTicketsInterval(Anlage $anlage, string $from, ?string $to = null): void
    {

        $fromStamp = strtotime($from);
        if ($to != null) {
            $toStamp = strtotime($to);
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
                $this->checkWeatherStation($anlage, date('Y-m-d H:i:00', $stamp));
            }
        }
        else $this->checkWeatherStation($anlage, date('Y-m-d H:i:00', $fromStamp));
    }

    /**
     * main function from the class to work with the tickets, but should never be called from outside the class
     * @param Anlage $anlage
     * @param string $time
     * @return void
     */
    public function checkWeatherStation(Anlage $anlage, string $time)
    {
        $sungap = $this->weather->getSunrise($anlage, date('Y-m-d', strtotime($time)));
        if ( $anlage->getWeatherStation()->getType() !== 'custom') {
            if ($time >= $sungap['sunrise'] && $time <=  $sungap['sunset']) {
                $status_report = $this->WData($anlage, $time);
                $ticketData = "";
                if ($status_report['Irradiation']) $ticketData = $ticketData . "Problem with the Irradiation ";
                if ($status_report['Temperature']) $ticketData = $ticketData . "Problem with the Temperature";
                if ($status_report['wspeed'] != "") $ticketData = $ticketData . "Problem with the Wind Speed";
                $this->generateTicket($ticketData, $time, $anlage);

                /* disabled by now.
                if ($ticketData != "") {
                    self::messagingFunction($ticketData, $anlage);
                }
                */
                unset($status_report);
            }
        }
    }


    /**
     * here we analyze the data from the weather station and generate the status.
     * @param Anlage $anlage
     * @param $time
     * @return mixed
     */
    private function WData(Anlage $anlage, $time): mixed
    {
        $offsetServer = new DateTimeZone("Europe/Luxembourg");
        $plantoffset = new DateTimeZone($this->getNearestTimezone($anlage->getAnlGeoLat(), $anlage->getAnlGeoLon(), strtoupper($anlage->getCountry())));
        $totalOffset = $plantoffset->getOffset(new DateTime("now")) - $offsetServer->getOffset(new DateTime("now"));
        $time = date('Y-m-d H:i:s', strtotime($time) - $totalOffset);
        $conn = $this->pdoService->getPdoPlant();
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
                $status_report['wspeed'] = "";
            }
        }
        $conn = null;

        return $status_report;
    }
    /**
     * We use this to generate/update Tickets.
     *
     * @param $status_report
     * @param $time
     * @param $anlage
     * @return void
     */
    private function generateTicket($status_report, $time, $anlage): void
    {
        $ticket = self::getLastTicketWeather($anlage, $time);

         if ($ticket != null) {
            $timetempend = date('Y-m-d H:i:s', strtotime($time));
            $end = date_create_from_format('Y-m-d H:i:s', $timetempend);
            $end->getTimestamp();
            $ticket->setEnd($end);
             $this->em->persist($ticket);
             $this->em->flush();
        }
         else if ($status_report != ""){
             $ticket = new Ticket();
             $date = date_create_from_format('Y-m-d H:i:s', $time);
             $ticket->setBegin($date);
             $ticket->setEnd($date);
             $ticket->setInverter('*');
             $ticket->setAnlage($anlage);
             $ticket->setStatus(10);
             $ticket->setAlertType(40);
             $ticket->setDescription($status_report);
             $ticket->setEditor("AlertSystem");
             $this->em->persist($ticket);
             $this->em->flush();
         }
    }


    /**
     * here we retrieve the tickets to link
     * @param $anlage
     * @param $time
     * @return mixed
     */
    public function getLastTicketWeather($anlage, $time): mixed
    {
        $today = date('Y-m-d', strtotime($time));
        $yesterday = date('Y-m-d', strtotime($time) - 86400); // this is the date of yesterday
        $sunrise = self::getLastQuarter($this->weather->getSunrise($anlage, $today)['sunrise']); // the first quarter of today
        $lastQuarterYesterday = self::getLastQuarter($this->weather->getSunrise($anlage, $yesterday)['sunset']); // the last quarter of yesterday
        $quarter = date('Y-m-d H:i', strtotime($time) - 900); // the quarter before the actual
        if ($quarter <= $sunrise) {
            $ticket = $this->ticketRepo->findLastByAnlageInverterTime($anlage, $today, $lastQuarterYesterday, 40, "*")[0]; // the same as above but for weather station
        } else {
            $ticket = $this->ticketRepo->findByAnlageInverterTime($anlage, $quarter, 40, "*")[0];
        }
        return $ticket;
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
            $subject = 'There was an error in ' . $anlage->getAnlName();
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