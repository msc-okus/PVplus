<?php

namespace App\MessageHandler\Command;

use App\Message\Command\CalcPlantAvailability;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityService;
use App\Service\LogMessagesService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CalcPlantAvailabilityHandler implements MessageHandlerInterface
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private LogMessagesService $logMessages,
        private AnlagenRepository $anlagenRepository
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(CalcPlantAvailability $calc)
    {
        $anlageId = $calc->getAnlageId();
        $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlageId]);
        $logId = $calc->getlogId();

        $this->logMessages->updateEntry($logId, 'working');
        $timeCounter = 0;
        $timeRange = $calc->getEndDate()->getTimestamp() - $calc->getStartDate()->getTimestamp();
        for ($stamp = $calc->getStartDate()->getTimestamp(); $stamp <= $calc->getEndDate()->getTimestamp(); $stamp += (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += (24 * 3600);
            $this->availabilityService->checkAvailability($anlageId, $stamp);
            if ($anlage->getShowAvailabilitySecond()) {
                $this->availabilityService->checkAvailability($anlageId, $stamp, true);
            }
        }
        $this->logMessages->updateEntry($logId, 'done', 100);
    }
}
