<?php
namespace App\MessageHandler\Command;

use App\Entity\Anlage;
use App\Message\Command\GenerateMonthlyReport;
use App\Repository\AnlagenRepository;
use App\Service\LogMessagesService;
use App\Service\Reports\ReportsMonthlyV2Service;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateMonthlyReportHandler
{
    public function __construct(
        private readonly ReportsMonthlyV2Service $reportsMonthlyV2,
        private readonly LogMessagesService $logMessages,
        private readonly AnlagenRepository $anlagenRepo,
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     */
    public function __invoke(GenerateMonthlyReport $generateMonthlyReport): void
    {
        /** @var $anlage Anlage */
        $anlage = $this->anlagenRepo->find($generateMonthlyReport->getAnlageId());

        $logId = $generateMonthlyReport->getlogId();
        $this->logMessages->updateEntry($logId, 'working', 0);

        $this->reportsMonthlyV2->createReportV2(
            $anlage,
            $generateMonthlyReport->getMonth(),
            $generateMonthlyReport->getYear(),
            $generateMonthlyReport->getUserId(),
            $logId
        );
        $this->logMessages->updateEntry($logId, 'done', 100);
    }
}