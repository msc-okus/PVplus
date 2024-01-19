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

        $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
        $txt = "sdfsdfsdf\n";
        fwrite($myfile, $txt);
        $txt = "Maximus\n";
        fwrite($myfile, $txt);
        fclose($myfile);
        return $this->render('logMessages/_prozessReady.html.twig', [
            'messagetext' => 'Manomann',
        ]);
    }
}
