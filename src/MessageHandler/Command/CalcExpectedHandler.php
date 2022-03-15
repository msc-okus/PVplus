<?php

namespace App\MessageHandler\Command;

use App\Message\Command\CalcExpected;
use App\Service\ExpectedService;
use App\Service\LogMessagesService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CalcExpectedHandler implements MessageHandlerInterface
{
    private ExpectedService $expectedService;
    private LogMessagesService $logMessages;

    public function __construct(ExpectedService $expectedService, LogMessagesService $logMessages)
    {
        $this->expectedService = $expectedService;
        $this->logMessages = $logMessages;
    }
    public function __invoke(CalcExpected $calcExpected)
    {
        $anlageId = $calcExpected->getAnlageId();
        $fromShort = $calcExpected->getStartDate()->format('Y-m-d 00:00');
        $toShort = $calcExpected->getEndDate()->format('Y-m-d 23:59');
        $logId = $calcExpected->getlogId();

        $this->logMessages->updateEntry($logId, 'working');
        $this->expectedService->storeExpectedToDatabase($anlageId, $fromShort, $toShort);
        $this->logMessages->updateEntry($logId, 'done');
    }

}