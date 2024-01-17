<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
class TestService
{
    public function __construct(
        private Environment $twig
    )
    {
    }
    public function testMal()
    {
        $html =  $this->twig->render('logMessages/_prozessReady.html.twig', [
            'messagex' => 'Ready mein Schatz',
        ]);

        return($html);
    }

}
