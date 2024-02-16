<?php
namespace App\MessageHandler\Command;

use App\Entity\Anlage;
use App\Message\Command\GenerateEpcReportPRNew;
use App\Service\ReportEpcPRNewService;
use App\Repository\AnlagenRepository;
use App\Service\LogMessagesService;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateEpcReportPRNewHandler
{
    public function __construct(
        private readonly ReportEpcPRNewService $reportEpcPRNewService,
        private readonly LogMessagesService $logMessages,
        private readonly AnlagenRepository $anlagenRepo,
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     */
    public function __invoke(GenerateEpcReportPRNew $generateEpcReportPRNew): void
    {
        /** @var $anlage Anlage */
        $anlage = $this->anlagenRepo->find($generateEpcReportPRNew->getAnlageId());

        $logId = $generateEpcReportPRNew->getlogId();
        $this->logMessages->updateEntry($logId, 'working', 0);

        $this->reportEpcPRNewService->createEpcReportNew(
            $anlage,
            $generateEpcReportPRNew->getReportDate(),
            $generateEpcReportPRNew->getUserId(),
            $logId
        );
        $this->logMessages->updateEntry($logId, 'done', 100);
    }
}