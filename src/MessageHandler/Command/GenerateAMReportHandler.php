<?php
namespace App\MessageHandler\Command;

use App\Entity\Anlage;
use App\Message\Command\GenerateAMReport;
use App\Repository\AnlagenRepository;
use App\Service\AssetManagementService;
use App\Service\LogMessagesService;
use Doctrine\Instantiator\Exception\ExceptionInterface;
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

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(GenerateAMReport $generateAMReport)
    {
        /** @var $anlage Anlage */
        $anlage = $this->anlagenRepo->find($generateAMReport->getAnlageId());
        $logId = $generateAMReport->getlogId();

        $this->logMessages->updateEntry($logId, 'working', 0);
        $this->assetManagement->createAmReport($anlage, $generateAMReport->getMonth(), $generateAMReport->getYear(), $generateAMReport->getUserId(), $logId);
        $this->logMessages->updateEntry($logId, 'working', 100);
        $this->logMessages->updateEntry($logId, 'done');
    }
}