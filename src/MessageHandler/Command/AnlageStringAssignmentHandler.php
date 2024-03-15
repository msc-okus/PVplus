<?php

namespace App\MessageHandler\Command;

use App\Message\Command\AnlageStringAssignment;
use App\Service\AnlageStringAssigmentService;
use App\Service\ExpectedService;
use App\Service\LogMessagesService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AnlageStringAssignmentHandler
{
    public function __construct(
        private readonly AnlageStringAssigmentService $anlageStringAssigmentService,
        private readonly LogMessagesService $logMessages)
    {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(AnlageStringAssignment $anlageStringAssigment): void
    {
        $anlageId = $anlageStringAssigment->getAnlId();
        $logId = $anlageStringAssigment->getlogId();
        $year = $anlageStringAssigment->getYear();
        $month = $anlageStringAssigment->getMonth();
        $publicDirectory = $anlageStringAssigment->getPulicDirectory();
        $currentUserName = $anlageStringAssigment->getCurrentUserName();


        $this->logMessages->updateEntry((int)$logId, 'working');
        $timeCounter = 0;

        $this->anlageStringAssigmentService->exportMontly($anlageId,$year,$month,$currentUserName,$publicDirectory,$logId);
        $this->logMessages->updateEntry($logId, 'done', 100);
    }
}
