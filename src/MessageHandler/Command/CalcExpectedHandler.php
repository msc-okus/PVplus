<?php

namespace App\MessageHandler\Command;

use App\Message\Command\CalcExpected;
use App\Service\ExpectedService;
use App\Service\LogMessagesService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CalcExpectedHandler
{
    public function __construct(
        private readonly ExpectedService $expectedService,
        private readonly LogMessagesService $logMessages)
    {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(CalcExpected $calcExpected): void
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
        $this->logMessages->updateEntry($logId, 'done', 100);
    }
}
