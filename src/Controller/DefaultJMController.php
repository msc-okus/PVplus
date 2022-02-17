<?php

namespace App\Controller;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\FunctionsService;
use App\Service\WeatherServiceNew;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultJMController extends AbstractController
{
    use G4NTrait;
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
    public function test(FunctionsService $functionsService, AnlagenRepository $repo){
        $stringArray = $functionsService->readInverters(" 2, 14 , 25-28, 300", $repo->findIdLike(94)[0]);
        dd($stringArray);
        return $this->redirectToRoute("/default/test");
    }
    /**
     * @Route("/default/test/sunset")
     */
    public function getsunset(WeatherServiceNew $weather){

        dd(date('H:i'));
        if(date('H:i', strtotime(time())));
    }
    /**
     * @Route("/default/test/check")
     */
    public function checkSystem(WeatherServiceNew $weather, AnlagenRepository $AnlRepo){
        $Anlagen = $AnlRepo->findAll();

        foreach($Anlagen as $Anlage){
            $sungap = $weather->getSunrise($Anlage);
            if((date('H:i') > $sungap['sunrise'])&&(date('H:i') < $sungap['sunset'])){
                dd("true");
            }
        }
    }
    public function getLastQuarter($stamp){

    }
}
