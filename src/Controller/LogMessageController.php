<?php

namespace App\Controller;

use App\Helper\G4NTrait;
use App\Repository\LogMessagesRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogMessageController extends BaseController
{
    use G4NTrait;

    #[Route(path: '/log/messages/list', name: 'app_log_messages_list')]
    public function listActualMessages(LogMessagesRepository $logMessagesRepo): Response
    {
        $logMessages = $logMessagesRepo->findUsefull();

        return $this->render('logMessages/_list.html.twig', [
            'logs' => $logMessages,
        ]);
    }

    #[Route(path: '/log/messages/list-small', name: 'app_log_background_messages')]
    public function listBackgroundProcesses(LogMessagesRepository $logMessagesRepo): Response
    {
        $user = $this->getUser();
        $uid = $user->getUserId();
        $logMessages = $logMessagesRepo->findSmallList($uid);
        //$count = $logMessages->count();

        return $this->render('logMessages/_listSmall.html.twig', [
            'logs' => $logMessages,
            'count' => 0,
        ]);
    }
}
