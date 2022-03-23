<?php

namespace App\MessageHandler\Command;

use App\Message\Command\CalcExpected;
use App\Message\Command\CalcPR;
use App\Repository\AnlagenRepository;
use App\Service\ExpectedService;
use App\Service\LogMessagesService;
use App\Service\PRCalulationService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CalcPRHandler implements MessageHandlerInterface
{
    private LogMessagesService $logMessages;
    private PRCalulationService $PRCalulation;

    public function __construct(PRCalulationService $PRCalulation, LogMessagesService $logMessages,)
    {
        $this->logMessages = $logMessages;
        $this->PRCalulation = $PRCalulation;
    }
    public function __invoke(CalcPR $calc)
    {
        $anlageId = $calc->getAnlageId();
        $fromShort = $calc->getStartDate()->format('Y-m-d 00:00');
        $logId = $calc->getlogId();

        $this->logMessages->updateEntry($logId, 'working');
        $this->PRCalulation->calcPRAll($anlageId, $fromShort);
        $this->logMessages->updateEntry($logId, 'done');
    }

}