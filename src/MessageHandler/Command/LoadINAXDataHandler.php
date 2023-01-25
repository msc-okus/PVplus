<?php

namespace App\MessageHandler\Command;

use App\Message\Command\LoadINAXData;
use App\Service\LogMessagesService;
use App\Service\ExternFileService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class LoadINAXDataHandler implements MessageHandlerInterface
{
    public function __construct(
        private ExternFileService  $externFileService,
        private LogMessagesService $logMessages)
    {
    }

    /**
     * @throws \Exception
     */

    public function __invoke(LoadINAXData $dta)
    {
        $anlageId = $dta->getAnlageId();
        $logId = $dta->getlogId();
        $this->logMessages->updateEntry($logId, 'working');
        $timeCounter = 0;
        $timeRange = $dta->getEndDate()->getTimestamp() - $dta->getStartDate()->getTimestamp();
        for ($stamp = $dta->getStartDate()->getTimestamp(); $stamp <= $dta->getEndDate()->getTimestamp(); $stamp = $stamp + (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += 24 * 3600;
         #  $this->externFileService->($anlageId, date('Y-m-d 00:00', $stamp));
            $this->externFileService->CallFileServiceINAX($anlageId, date('Y-m-d 00:00', $stamp));
        }
        $this->logMessages->updateEntry($logId, 'done');
    }
}