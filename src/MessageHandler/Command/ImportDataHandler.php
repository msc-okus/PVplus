<?php

namespace App\MessageHandler\Command;

use App\Message\Command\ImportData;
use App\Service\LogMessagesService;
use App\Service\ExternFileService;
use Doctrine\Instantiator\Exception\ExceptionInterface;
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

    public function __invoke(ImportData $importData): void
    {
        $path = $importData->getPath();
        $importType = $importData->getImportType();
        $logId = $importData->getlogId();
        $timeCounter = 0;
        $timeRange = $importData->getEndDate()->getTimestamp() - $importData->getStartDate()->getTimestamp();
        for ($stamp = $importData->getStartDate()->getTimestamp(); $stamp <= $importData->getEndDate()->getTimestamp(); $stamp = $stamp + (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += 24 * 3600;
            #  $this->externFileService->($anlageId, date('Y-m-d 00:00', $stamp));
            $this->externFileService->callImportDataFromApiManuel($path, $importType, $importData->getStartDate()->getTimestamp(), $importData->getEndDate()->getTimestamp());
        }
        $this->logMessages->updateEntry($logId, 'done');
    }
}
