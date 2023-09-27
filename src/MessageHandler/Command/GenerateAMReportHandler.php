<?php
namespace App\MessageHandler\Command;

use App\Entity\Anlage;
use App\Message\Command\GenerateAMReport;
use App\Repository\AnlagenRepository;
use App\Service\AssetManagementService;
use App\Service\LogMessagesService;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateAMReportHandler
{
    public function __construct(
        private readonly AssetManagementService $assetManagement,
        private readonly LogMessagesService $logMessages,
        private readonly AnlagenRepository $anlagenRepo
    )
    {
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     */
    public function __invoke(GenerateAMReport $generateAMReport): void
    {
        /** @var $anlage Anlage */
        $anlage = $this->anlagenRepo->find($generateAMReport->getAnlageId());
        $logId = $generateAMReport->getlogId();

        $this->logMessages->updateEntry($logId, 'working', 0);
        $this->assetManagement->createAmReport($anlage, $generateAMReport->getMonth(), $generateAMReport->getYear(), $generateAMReport->getUserId(), $logId);

        $this->logMessages->updateEntry($logId, 'done', 100);
    }
}