<?php

namespace App\Service;


use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlertSystemMailService
{

    public function __construct(
        private MessageService $mailservice,
    )
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
     * In this function we will analyze the tickets that are open for the current given time and decide if we have to notify by mail
     * @param Anlage $anlage
     * @param $time
     */
    public function checkTickets(Anlage $anlage, $time){



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
}
