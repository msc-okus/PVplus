<?php

namespace App\MessageHandler\Command;

use App\Message\Command\CalcExpected;
use App\Repository\AnlagenRepository;
use App\Service\ExpectedService;
use App\Service\LogMessagesService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CalcExpectedHandler implements MessageHandlerInterface
{
    private ExpectedService $expectedService;
    private LogMessagesService $logMessages;
    private AnlagenRepository $anlagenRepo;

    public function __construct(ExpectedService $expectedService, LogMessagesService $logMessages, AnlagenRepository $anlagenRepo)
    {
        $this->expectedService = $expectedService;
        $this->logMessages = $logMessages;
        $this->anlagenRepo = $anlagenRepo;
    }
    public function __invoke(CalcExpected $calcExpected)
    {

        $anlageId = $calcExpected->getAnlageId();
        $anlage = $this->anlagenRepo->findOneBy(['anlId' => $anlageId]);
        $fromShort = $calcExpected->getStartDate()->format('Y-m-d 00:00');
        $toShort = $calcExpected->getEndDate()->format('Y-m-d 23:59');

        $job = "Calculate Expected from $fromShort until $toShort";
        $logId = $this->logMessages->writeNewEntry($anlage, 'Expected', $job);

        $this->expectedService->storeExpectedToDatabase($anlageId, $fromShort, $toShort);

        $this->logMessages->updateEntry($logId, 'done');
    }

}