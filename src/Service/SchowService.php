<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
class SchowService
{
    public function __construct(
        private Environment $twig
    )
    {
    }
    public function showMessege($messege)
    {
        $html =  $this->twig->render('logMessages/_prozessReady.html.twig', [
            'message' => $messege,
        ]);

        return($html);
    }

}
