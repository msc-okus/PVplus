<?php

namespace App\MessageHandler\Command;

use App\Message\Command\CalcExpected;
use App\Service\ExpectedService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CalcExpectedHandler implements MessageHandlerInterface
{
    private ExpectedService $expectedService;

    public function __construct(ExpectedService $expectedService)
    {

        $this->expectedService = $expectedService;
    }
    public function __invoke(CalcExpected $calcExpected)
    {
        $anlage = $calcExpected->getAnlageId();
        $fromShort = $calcExpected->getStartDate()->format('Y-m-d 00:00');
        $toShort = $calcExpected->getEndDate()->format('Y-m-d 23:59');

        $this->expectedService->storeExpectedToDatabase($anlage, $fromShort, $toShort);
    }

}