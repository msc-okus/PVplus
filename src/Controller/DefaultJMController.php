<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Service\AlertSystemService;
use App\Service\Charts\IrradiationChartService;
use App\Service\FunctionsService;
use App\Service\MessageService;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PDO;

class DefaultJMController extends AbstractController
{
    use G4NTrait;
    #[Route(path: '/default/j/m', name: 'default_j_m')]
    public function index() : Response
    {
        return $this->render('default_jm/index.html.twig', [
            'controller_name' => 'DefaultJMController',
        ]);
    }
    #[Route(path: '/default/test', name: 'default_j_m')]
    public function test(FunctionsService $functionsService, AnlagenRepository $repo)
    {
        $stringArray = $functionsService->readInverters(" 2, 14 , 25-28, 300", $repo->findIdLike(94)[0]);
        return $this->redirectToRoute("/default/test");
    }
    #[Route(path: '/default/test/check', name: 'default_check')]
    public function check(AlertSystemService $service)
    {
        $service->generateTicketsInterval("2020-12-20", "2020-12-21", "93");
        dd("fertig");
    }
}
