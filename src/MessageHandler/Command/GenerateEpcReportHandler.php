<?php
namespace App\MessageHandler\Command;

use App\Entity\Anlage;
use App\Message\Command\GenerateEpcReport;
use App\Service\Reports\ReportEpcService;
use App\Repository\AnlagenRepository;
use App\Service\LogMessagesService;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateEpcReportHandler
{
    public function __construct(
        private readonly ReportEpcService $reportEpcService,
        private readonly LogMessagesService $logMessages,
        private readonly AnlagenRepository $anlagenRepo,
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     */
    public function __invoke(GenerateEpcReport $generateEpcReport): void
    {
        /** @var $anlage Anlage */
        $anlage = $this->anlagenRepo->find($generateEpcReport->getAnlageId());

        $logId = $generateEpcReport->getlogId();
        $this->logMessages->updateEntry($logId, 'working', 0);

        $this->reportEpcService->createEpcReport(
            $anlage,
            $generateEpcReport->getReportDate(),
            $generateEpcReport->getUserId(),
            $logId
        );
        $this->logMessages->updateEntry($logId, 'done', 100);
    }
}