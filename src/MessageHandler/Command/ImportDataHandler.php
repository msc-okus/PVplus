<?php

namespace App\MessageHandler\Command;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Message\Command\ImportData;
use App\Repository\AnlagenRepository;
use App\Service\LogMessagesService;
use App\Service\ExternFileService;
use App\Service\ImportService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ImportDataHandler
{
    public function __construct(
        private readonly ExternFileService  $externFileService,
        private readonly ImportService      $importService,
        private readonly LogMessagesService $logMessages,
        private readonly AnlagenRepository $anlagenRepo,
    )
    {
    }

    use G4NTrait;
    /**
     * @throws \Exception
     */

    public function __invoke(ImportData $importData): void
    {
        date_default_timezone_set('Europe/Berlin');
        $path = $importData->getPath();
        $importType = $importData->getImportType();
        $logId = $importData->getlogId();
        $plantId = $importData->getAnlageId();
        /** @var $anlage Anlage */
        $anlage = $this->anlagenRepo->find($importData->getAnlageId());

        $timeCounter = 0;
        $timeRange = $importData->getEndDate()->getTimestamp() - $importData->getStartDate()->getTimestamp();
        if ($anlage->getSettings()->isSymfonyImport()) {
            for ($dayStamp = $importData->getStartDate()->getTimestamp(); $dayStamp <= $importData->getEndDate()->getTimestamp(); $dayStamp += 24*3600) {
                $from = strtotime(date('Y-m-d 00:15', $dayStamp));
                $to = strtotime(date('Y-m-d 23:59', $dayStamp));
                $currentDay = date('d', $dayStamp);

                // Proof if date = today, if yes set $to to current DateTime
                if ($importData->getEndDate()->format('Y') == date('Y') && $importData->getEndDate()->format('m') == date('m') && $currentDay == date('d')) {
                    $hour = date('H');
                    $to = strtotime($importData->getEndDate()->format("Y-m-d $hour:00"));
                }

                $minute = (int)date('i');
                while (($minute >= 28 && $minute < 33) || $minute >= 58 || $minute < 3) {
                    sleep(20);
                    $minute = (int)date('i');
                }

                $this->importService->prepareForImport($plantId, $from, $to, $importType);

                $this->logMessages->updateEntry($logId, 'working (s)', ($timeCounter / $timeRange) * 100);
                $timeCounter += 24 * 3600;
                sleep(1);
            }
            $this->logMessages->updateEntry($logId, 'done',100);
        } else {
            $this->logMessages->updateEntry($logId, "preparing", 0);
            $this->externFileService->callImportDataFromApiManuel($anlage->getPathToImportScript(), $importType, $importData->getStartDate()->getTimestamp(), $importData->getEndDate()->getTimestamp(), $logId);
        }
    }
}
