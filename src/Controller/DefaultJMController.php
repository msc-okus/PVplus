<?php

namespace App\Controller;

use App\Service\FunctionsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultJMController extends AbstractController
{
    /**
     * @Route("/default/j/m", name="default_j_m")
     */
    public function index(): Response
    {
        return $this->render('default_jm/index.html.twig', [
            'controller_name' => 'DefaultJMController',
        ]);
    }
    /**
     * @Route("/default/test", name="default_j_m")
     */
    public function test(FunctionsService $functionsService){
        $functionsService->readInverters("7-14, 21, 32-33");
        return $this->redirectToRoute("/default/test");
    }
}
