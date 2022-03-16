<?php

namespace App\MessageHandler\Command;

use App\Message\Command\CalcExpected;
use App\Message\Command\CalcPlantAvailability;
use App\Service\AvailabilityService;
use App\Service\LogMessagesService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CalcPlantAvailabilityHandler implements MessageHandlerInterface
{
    private LogMessagesService $logMessages;
    private AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService, LogMessagesService $logMessages)
    {
        $this->logMessages = $logMessages;
        $this->availabilityService = $availabilityService;
    }
    public function __invoke(CalcPlantAvailability $calc)
    {
        $anlageId = $calc->getAnlageId();
        $logId = $calc->getlogId();

        $this->logMessages->updateEntry($logId, 'working');
        $this->availabilityService->calcAvailability($anlageId, $calc->getStartDate(), $calc->getEndDate());
        $this->logMessages->updateEntry($logId, 'done');
    }

}