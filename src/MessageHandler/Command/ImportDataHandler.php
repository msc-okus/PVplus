<?php

namespace App\MessageHandler\Command;

use App\Message\Command\ImportData;
use App\Service\LogMessagesService;
use App\Service\ExternFileService;
use App\Service\ImportService;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ImportDataHandler implements MessageHandlerInterface
{
    public function __construct(
        private ExternFileService  $externFileService,
        private ImportService      $importService,
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
        $plantId = $importData->getAnlageId();
        $readyToImport = $importData->getReadyToImport();

        $key = array_search($plantId, array_column($readyToImport, 'anlage_id'));

        $timeCounter = 0;
        $timeRange = $importData->getEndDate()->getTimestamp() - $importData->getStartDate()->getTimestamp();
        if ($readyToImport[$key]['anlage_id'] == $plantId) {
            for ($stamp = $importData->getStartDate()->getTimestamp(); $stamp <= $importData->getEndDate()->getTimestamp(); $stamp += 24 * 3600) {
                $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
                $timeCounter += 24 * 3600;


                $from = date('Y-m-d 00:00', $importData->getStartDate()->getTimestamp());
                $to = date('Y-m-d 23:59', $importData->getEndDate()->getTimestamp());

                $ff = explode(" ", $from);
                $tt = explode(" ", $to);
                $f = explode("-", $ff[0]);
                $t = explode("-", $tt[0]);
                $year = $f[0];
                $startMonth = $f[1];
                $startday = $f[2];
                $endMonth = $t[1];
                $endday = $t[2];

                for ($month = $startMonth; $month <= $endMonth; $month++) {
                    (is_null($endday)) ? $endday2 = (int)date('t', strtotime("$year-$month-1")) : $endday2 = $endday;
                    for ($d = $startday; $d <= $endday2; $d++) {

                        $from = strtotime($year . '-' . $month . '-' . $d . ' 00:15');
                        $to = strtotime($year . '-' . $month . '-' . $d . ' 23:59:59');
                        if ($year == date('Y') && $month == date('m') && $d == date('d')) {
                            $hour = date('H');
                            $minute = date('i');
                            $to = strtotime($year . '-' . $month . '-' . $d . " $hour:$minute:59");
                        }

                        $minute = (int)date('i');
                        while (($minute >= 28 && $minute < 33) || $minute >= 58 || $minute < 3) {

                            sleep(20);
                            $minute = (int)date('i');
                        }
                        $this->importService->prepareForImport($plantId, $from, $to, $importType);
                        sleep(1);
                    }
                }


            }
        } else {
            $this->externFileService->callImportDataFromApiManuel($path, $importType, $importData->getStartDate()->getTimestamp(), $importData->getEndDate()->getTimestamp());
        }
        $this->logMessages->updateEntry($logId, 'done');
    }
}
