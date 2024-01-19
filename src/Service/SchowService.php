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
    public function showMessege(string $messege)
    {
        $html =  $this->twig->render('logMessages/_prozessReady.html.twig', [
            'messagetext' => $messege,
        ]);

        return($html);
    }

}
