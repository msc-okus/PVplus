<?php

namespace App\MessageHandler\Command;

use App\Message\Command\CalcPlantAvailabilityNew;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\LogMessagesService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CalcPlantAvailabilityNewHandler implements MessageHandlerInterface
{
    public function __construct(
        private AvailabilityByTicketService $availabilityByTicket,
        private LogMessagesService $logMessages,
        private AnlagenRepository $anlagenRepository)
    {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(CalcPlantAvailabilityNew $calc)
    {
        $anlageId = $calc->getAnlageId();
        $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlageId]);
        $logId = $calc->getlogId();

        $this->logMessages->updateEntry($logId, 'working');
        $timeCounter = 0;
        $timeRange = $calc->getEndDate()->getTimestamp() - $calc->getStartDate()->getTimestamp();
        for ($stamp = $calc->getStartDate()->getTimestamp(); $stamp <= $calc->getEndDate()->getTimestamp(); $stamp += (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += (24 * 3600);
            $day = date_create(date("Y-m-d 12:00", $stamp));
            $this->availabilityByTicket->checkAvailability($anlage, $day, 0);
            $this->availabilityByTicket->checkAvailability($anlage, $day, 1);
            $this->availabilityByTicket->checkAvailability($anlage, $day, 2);
            $this->availabilityByTicket->checkAvailability($anlage, $day, 3);
        }
        $this->logMessages->updateEntry($logId, 'done');
    }
}
