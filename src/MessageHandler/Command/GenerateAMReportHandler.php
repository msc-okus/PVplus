<?php
namespace App\MessageHandler\Command;

use App\Entity\Anlage;
use App\Message\Command\GenerateAMReport;
use App\Repository\AnlagenRepository;
use App\Service\AssetManagementService;
use App\Service\LogMessagesService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GenerateAMReportHandler implements MessageHandlerInterface
{


    public function __construct(
        private AssetManagementService $assetManagement,
        private LogMessagesService $logMessages,
        private AnlagenRepository $anlagenRepo
    )
    {
    }

    public function __invoke(GenerateAMReport $generateAMReport)
    {
        /** @var $anlage Anlage */
        $anlage = $this->anlagenRepo->find($generateAMReport->getAnlageId());
        $logId = $generateAMReport->getlogId();

        $this->logMessages->updateEntry($logId, 'working', 50);
        $this->assetManagement->createAmReport($anlage, $generateAMReport->getMonth(), $generateAMReport->getYear());
        $this->logMessages->updateEntry($logId, 'working', 100);
        $this->logMessages->updateEntry($logId, 'done');
    }
}