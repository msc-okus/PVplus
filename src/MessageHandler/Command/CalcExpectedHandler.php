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
        $logId = $calcExpected->getlogId();

        $this->logMessages->updateEntry($logId, 'working');
        $timeCounter = 0;
        $timeRange = $calcExpected->getEndDate()->getTimestamp() - $calcExpected->getStartDate()->getTimestamp();
        for ($stamp = $calcExpected->getStartDate()->getTimestamp(); $stamp <= $calcExpected->getEndDate()->getTimestamp(); $stamp = $stamp + (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += 24 * 3600;
            $from = date('Y-m-d 00:00', $stamp);
            $to = date('Y-m-d 23:59', $stamp);

            $this->expectedService->storeExpectedToDatabase($anlageId, $from, $to);
        }
        $this->logMessages->updateEntry($logId, 'done');
    }
}
