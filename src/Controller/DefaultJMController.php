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
use App\Service\AssetManagementService;
use App\Service\Charts\IrradiationChartService;
use App\Service\FunctionsService;
use App\Service\MessageService;
use App\Service\WeatherServiceNew;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PDO;

/**
 * @IsGranted("ROLE_G4N")
 */
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

        $anlage = $anlagenRepository->findIdLike("184")[0];
        $fromStamp = strtotime("2022-03-13 ");
        $toStamp = strtotime("2022-03-17 ");
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
            $service->generateTicketsInterval($anlage, date('Y-m-d H:i:00', $stamp));
        }
        dd("hello");
    }

    #[Route(path: '/test/read', name: 'default_read')]
    public function testread(FunctionsService $fs, AnlagenRepository $ar, WeatherServiceNew $weather, AssetManagementService $am){
        $anlage = $ar->findIdLike("57")[0];
        $am->createAmReport($anlage, "11", "2022");

        return $this->render('base.html.twig');// this is suposed to never run so no problem
    }
    #[Route(path: '/test/pdf', name: 'default_pdf')]
    public function testpdf(FunctionsService $fs, AnlagenRepository $ar, WeatherServiceNew $weather, AssetManagementService $am){

    }
}
