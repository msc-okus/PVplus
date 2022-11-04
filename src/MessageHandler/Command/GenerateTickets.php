<?php
namespace App\MessageHandler\Command;

use App\Message\Command\CalcPR;
use App\Service\LogMessagesService;
use App\Service\PRCalulationService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GenerateTickets implements MessageHandlerInterface
{


    public function __construct()
    {

    }

    public function __invoke(CalcPR $calc)
    {

    }
}