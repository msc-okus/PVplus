<?php

namespace App\MessageHandler\Command;

use App\Message\Command\CalcPR;
use App\Service\LogMessagesService;
use App\Service\PRCalulationService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CalcPRHandler implements MessageHandlerInterface
{
    public function __construct(
        private PRCalulationService $PRCalulation,
        private LogMessagesService $logMessages)
    {
    }

    public function __invoke(CalcPR $calc)
    {
        $anlageId = $calc->getAnlageId();
        $logId = $calc->getlogId();

        $this->logMessages->updateEntry($logId, 'working');
        $timeCounter = 0;
        $timeRange = $calc->getEndDate()->getTimestamp() - $calc->getStartDate()->getTimestamp();
        for ($stamp = $calc->getStartDate()->getTimestamp(); $stamp <= $calc->getEndDate()->getTimestamp(); $stamp = $stamp + (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += 24 * 3600;
            $this->PRCalulation->calcPRAll($anlageId, date('Y-m-d 00:00', $stamp));
        }
        $this->logMessages->updateEntry($logId, 'done');
    }
}
