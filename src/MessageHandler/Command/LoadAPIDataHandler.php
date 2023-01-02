<?php

namespace App\MessageHandler\Command;

use App\Message\Command\LoadAPIData;
use App\Service\LogMessagesService;
use App\Service\ExternFileService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class LoadAPIDataHandler implements MessageHandlerInterface
{
    public function __construct(
        private ExternFileService  $externFileService,
        private LogMessagesService $logMessages)
    {
    }

    public function __invoke(LoadAPIData $calc)
    {
        $anlageId = $calc->getAnlageId();
        $logId = $calc->getlogId();
#dd( $logId);
        $this->logMessages->updateEntry($logId, 'working');
        $timeCounter = 0;
        $timeRange = $calc->getEndDate()->getTimestamp() - $calc->getStartDate()->getTimestamp();
        for ($stamp = $calc->getStartDate()->getTimestamp(); $stamp <= $calc->getEndDate()->getTimestamp(); $stamp = $stamp + (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += 24 * 3600;
          #  $this->externFileService->($anlageId, date('Y-m-d 00:00', $stamp));
            $this->externFileService->CallFileService($anlageId, date('Y-m-d 00:00', $stamp));
        }
        $this->logMessages->updateEntry($logId, 'done');
    }
}
