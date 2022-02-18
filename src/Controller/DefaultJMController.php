<?php

namespace App\Controller;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\Charts\IrradiationChartService;
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

        $time = $this->getLastQuarter(date('Y-m-d H:i') ); //we set the last quarter and we will use it for the queries and to check if the sun is up
        foreach($Anlagen as $anlage){
            $sungap = $weather->getSunrise($anlage);
            // to avoid doing this we should have an entity to store the values but that would be huge in the db I think
            //we will also have problems if we try to run this every 15 mins, sometimes the information is not present if little time has elapsed from the moment they were supposed to be created.

            $conn = self::getPdoConnection();
            if(( $time > $sungap['sunrise']) && ($time < $sungap['sunset'])){

                $sql2 = "SELECT a.stamp, b.gi_avg as gi , b.gmod_avg as gmod FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp = '$time' ";
                $res = $conn->query($sql2);
                dd($res->fetch(PDO::FETCH_ASSOC));
                /* replace this for the raw query for irr
                if ($anlage->getShowOnlyUpperIrr() || $anlage->getWeatherStation()->getHasLower() == false || $anlage->getUseCustPRAlgorithm() == "Groningen") {

                    $dataArrayIrradiation = $irradiationChart->getIrradiation($anlage, $time,  'upper');
                } else {
                    $dataArrayIrradiation = $irradiationChart->getIrradiation($anlage, $time, date('Y-m-d H:i', strtotime($time)) + 900);
                }
                dd($dataArrayIrradiation);
                */
            }
        }
    }
    public function getLastQuarter($stamp){
        $mins = date('i', strtotime($stamp));
        $rest = date('Y-m-d H', strtotime($stamp));
        if ($mins >= "00" && $mins < "15") $quarter = "00";
        else if ($mins >= "15" && $mins < "30") $quarter = "15";
        else if ($mins >= "30" && $mins < "45") $quarter = "30";
        else $quarter = "45";
        return ($rest.":".$quarter);

    }
}
