<?php

namespace App\Controller;

use App\Helper\G4NTrait;
use App\Repository\LogMessagesRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProzessStatusMessageController extends BaseController
{
    use G4NTrait;

    #[Route(path: '/log/messages/prozess-status', name: 'app_log_processmessenges')]
    public function showProzessStatusMessages(LogMessagesRepository $logMessagesRepo): Response
    {
        $user = $this->getUser();
        $uid = $user->getUserId();


        return $this->render('logMessages/_prozessReady.html.twig', [
            'messagetext' => 'Testxxxxxxxxxx',
        ]);
    }
}
