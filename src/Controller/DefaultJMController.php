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

    #[Route(path: '/test/createticket', name: 'default_check')]
    public function check(AnlagenRepository $anlagenRepository, AlertSystemService $service)
    {
        $anlage = $anlagenRepository->findIdLike("111")[0];
        $service->generateTicketsInterval($anlage, "2022-08-07", "2022-08-09");
    }

    #[Route(path: '/test/read', name: 'default_read')]
    public function testread(FunctionsService $fs, AnlagenRepository $ar, WeatherServiceNew $weather){
        $anlage = $ar->findIdLike("94")[0];

        return $this->render('base.html.twig');// this is suposed to never run so no problem

    }
}
