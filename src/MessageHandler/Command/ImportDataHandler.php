<?php

namespace App\MessageHandler\Command;

use App\Message\Command\ImportData;
use App\Service\LogMessagesService;
use App\Service\ExternFileService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ImportDataHandler implements MessageHandlerInterface
{
    public function __construct(
        private ExternFileService  $externFileService,
        private LogMessagesService $logMessages)
    {
    }

    /**
     * @throws \Exception
     */

    public function __invoke(ImportData $dta)
    {
        $path = $dta->getPath();
        $anlageId = $dta->getAnlageId();
        $logId = $dta->getlogId();
        $this->logMessages->updateEntry($logId, 'working');
        $timeCounter = 0;
        $timeRange = $dta->getEndDate()->getTimestamp() - $dta->getStartDate()->getTimestamp();

        for ($stamp = $dta->getStartDate()->getTimestamp(); $stamp <= $dta->getEndDate()->getTimestamp(); $stamp = $stamp + (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += 24 * 3600;
            $this->externFileService->CallImportDataFromApiManuel($path, $dta->getStartDate()->getTimestamp(), $dta->getEndDate()->getTimestamp());
        }

        $this->logMessages->updateEntry($logId, 'done');
    }
}
