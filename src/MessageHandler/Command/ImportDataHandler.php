<?php

namespace App\MessageHandler\Command;

use App\Message\Command\ImportData;
use App\Service\LogMessagesService;
use App\Service\ExternFileService;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Helper\ImportFunctionsTrait;

class ImportDataHandler implements MessageHandlerInterface
{
    use ImportFunctionsTrait;

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
        $anlage = $importData->getAnlage();
        $path = $importData->getPath();
        $importType = $importData->getImportType();
        $logId = $importData->getlogId();
        $plantId = $importData->getAnlageId();
        $readyToImport = $importData->getReadyToImport();

        $key = array_search($plantId, array_column($readyToImport, 'anlage_id'));

        $timeCounter = 0;
        $timeRange = $importData->getEndDate()->getTimestamp() - $importData->getStartDate()->getTimestamp();
        for ($stamp = $importData->getStartDate()->getTimestamp(); $stamp <= $importData->getEndDate()->getTimestamp(); $stamp = $stamp + (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += 24 * 3600;

            if($readyToImport[$key]['anlage_id'] == $plantId){
                $this->loadDataFromApi($anlage, $plantId, $importData->getStartDate()->getTimestamp(), $importData->getEndDate()->getTimestamp());
            }else{
                $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
                $txt = "Monte Zuma\n";
                fwrite($myfile, $txt);
                fclose($myfile);
                #$this->externFileService->callImportDataFromApiManuel($path, $importType, $importData->getStartDate()->getTimestamp(), $importData->getEndDate()->getTimestamp());
            }



        }
        $this->logMessages->updateEntry($logId, 'done');
    }

    function loadDataFromApi($anlage, $plantId, $from, $to){
        $systemKey = $anlage->getCustomPlantId();
        $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
        $txt = "Idi Amin $systemKey \n";
        fwrite($myfile, $txt);
        fclose($myfile);
    }
}
