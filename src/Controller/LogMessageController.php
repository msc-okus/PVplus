<?php

namespace App\Controller;

use App\Helper\G4NTrait;
use App\Repository\LogMessagesRepository;
use Symfony\Component\Routing\Annotation\Route;

class LogMessageController extends BaseController
{

    use G4NTrait;
    /**
     * @Route("/log/messages/list", name="app_log_messages_list")
     */
    public function listActualMessages( LogMessagesRepository $logMessagesRepo)
    {
        $logMessages = $logMessagesRepo->findUseful();

        return $this->render('logMessages/_list.html.twig', [
            'logs'       => $logMessages,
        ]);
    }
}