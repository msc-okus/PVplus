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

        $this->externFileService->CallImportDataFromApiManuel($path, $dta->getStartDate()->getTimestamp(), $dta->getEndDate()->getTimestamp());
        $this->logMessages->updateEntry($logId, 'done');
    }
}
