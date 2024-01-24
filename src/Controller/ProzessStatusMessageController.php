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
        $logMessages = $logMessagesRepo->getStatusMessages($uid);

        if(!is_object($logMessages)){
            return new Response('');
        }else{
            $id = $logMessages->getId();
            $plant = $logMessages->getPlant();
            $function = $logMessages->getFunction();
            $prozessId = $logMessages->getProzessId();
            #$logMessagesRepo->setStatusMessagesIsSeen($id);

            switch ($function){
                case 'Expected';
                    $message = "Your $function calculation for $plant is ready.";
                    break;
                case 'AM Report';
                    $message = "Your $function calculation for $plant is ready.";
                    break;
                default:
                    $message = "Your $function for $plant is ready.";
                    break;
            }

            return $this->render('logMessages/_prozessReady.html.twig', [
                'messagetext'   => "$message",
                'function'      => $function,
                'prozessid'     => $prozessId
            ]);
        }
    }
}
