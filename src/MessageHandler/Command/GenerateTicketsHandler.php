<?php
namespace App\MessageHandler\Command;

use App\Entity\Anlage;
use App\Message\Command\GenerateTickets;
use App\Repository\AnlagenRepository;
use App\Service\TicketsGeneration\AlertSystemService;
use App\Service\TicketsGeneration\AlertSystemV2Service;
use App\Service\LogMessagesService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateTicketsHandler
{
    public function __construct(
        private readonly AlertSystemService $alertService,
        private readonly AlertSystemv2Service $alertServiceV2,
        private readonly LogMessagesService $logMessages,
        private readonly AnlagenRepository $anlagenRepo
    )
    {
    }

    public function __invoke(GenerateTickets $generateTickets): void
    {
        /** @var $anlage Anlage */
        $anlage = $this->anlagenRepo->find($generateTickets->getAnlageId());
        $logId = $generateTickets->getlogId();

        $this->logMessages->updateEntry($logId, 'working');
        $timeCounter = 0;
        $timeRange = $generateTickets->getEndDate()->getTimestamp() - $generateTickets->getStartDate()->getTimestamp();
        for ($stamp = $generateTickets->getStartDate()->getTimestamp(); $stamp <= $generateTickets->getEndDate()->getTimestamp(); $stamp = $stamp + (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += 24 * 3600;
            if ($anlage->isNewAlgorythm()) {
                $this->alertServiceV2->generateTicketsInterval($anlage, date('Y-m-d H:i:00', $stamp), date('Y-m-d H:i:00', $stamp + (24 * 3600)));
            }
            else {
                $this->alertService->generateTicketsInterval($anlage, date('Y-m-d H:i:00', $stamp), date('Y-m-d H:i:00', $stamp + (24 * 3600)));
            }
        }
        $this->logMessages->updateEntry($logId, 'done', 100);
    }
}