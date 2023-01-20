<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\Status;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use App\Service\AlertSystemService;
use App\Service\AlertSystemWeatherService;
use App\Service\Charts\IrradiationChartService;
use App\Service\FunctionsService;
use App\Service\MessageService;
use App\Service\WeatherServiceNew;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PDO;

class DefaultJMController extends AbstractController
{
    private functionsService $functions;
    use G4NTrait;
    public function __construct(FunctionsService $functions)
    {
        $this->functions = $functions;
    }
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
<<<<<<< HEAD
        $anlage = $anlagenRepository->findIdLike("181")[0];


        $service->generateWeatherTicketsInterval($anlage, "2022-12-06", "2022-12-31");
=======
        $anlage = $anlagenRepository->findIdLike("44")[0];
        $service->generateTicketsInterval($anlage, "2022-12-01", "2023-01-17");
>>>>>>> 37d2270cab67c192c0629265f6b7b2c7c2a163b8
        dd("hello");
    }

    #[Route(path: '/test/read', name: 'default_read')]
    public function testread(FunctionsService $fs, AnlagenRepository $ar, WeatherServiceNew $weather){
        $anlage = $ar->findIdLike("94")[0];
        return $this->render('base.html.twig');// this is suposed to never run so no problem
    }
}
