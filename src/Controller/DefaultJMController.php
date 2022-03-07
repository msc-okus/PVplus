<?php

namespace App\Controller;

use App\Entity\Status;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\Charts\IrradiationChartService;
use App\Service\FunctionsService;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PDO;

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
    public function checkSystem(WeatherServiceNew $weather, AnlagenRepository $AnlRepo, EntityManagerInterface $em){
        $Anlagen = $AnlRepo->findAll();
        $time = $this->getLastQuarter(date('Y-m-d H:i') );
        $time = $this->timeAjustment($time, -2);
        $sungap = $weather->getSunrise($Anlagen);
        foreach($Anlagen as $anlage){

            $conn = self::getPdoConnection();
            if (($anlage->getAnlMute() == "No") && (($time > $sungap[$anlage->getanlName()]['sunrise']) && ($time < $sungap[$anlage->getAnlName()]['sunset']))) {
                    $status = new Status();
                    $sqlw = "SELECT b.gi_avg as gi , b.gmod_avg as gmod, b.temp_ambient as temp, b.wind_speed as wspeed FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp = '$time' ";
                    //change to g_lower g_upper
                    $resw = $conn->query($sqlw);
                    $wdata = $resw->fetch(PDO::FETCH_ASSOC);
                    if($wdata['gi'] != null && $wdata['gmod'] != null) {
                        if ($wdata['gi'] == 0 && $wdata['gmod'] == 0) $status_report[$anlage->getAnlName()]['Irradiation'] = "Irradiation is 0";
                        else $status_report[$anlage->getAnlName()]['Irradiation'] = "All good";
                    }
                    else  $status_report[$anlage->getAnlName()]['Irradiation'] = "No data";

                if($wdata['temp'] != null ) {

                     $status_report[$anlage->getAnlName()]['temperature'] = "All good";
                }
                else  $status_report[$anlage->getAnlName()]['temperature'] = "No data";

                if($wdata['wspeed'] != null) {
                    if ($wdata['wspeed'] == 0 ) $status_report[$anlage->getAnlName()]['wspeed'] = "Wind Speed is 0";
                    else $status_report[$anlage->getAnlName()]['wspeed'] = "All good";
                }
                else  $status_report[$anlage->getAnlName()]['wspeed'] = "No data";

                    $sqlp = "SELECT wr_pac as ist
                          FROM (db_dummysoll a left JOIN " . $anlage->getDbNameIst() . " b ON a.stamp = b.stamp) 
                          WHERE a.stamp = '$time' ";
                    $counter = 1;
                    $resp = $conn->query($sqlp);
                    while($pdata = $resp->fetch(PDO::FETCH_ASSOC)){
                        if($pdata['ist'] != null){
                            if($pdata['ist'] == 0) $status_report[$anlage->getAnlName()]['Ist'][$counter] = "Power is 0";
                            else $status_report[$anlage->getAnlName()]['Ist'][$counter] = "All good";
                        }
                        else $status_report[$anlage->getAnlName()]['Ist'][$counter] = "No Data";
                        $counter++;
                    }


                    $status->setAnlage($anlage);
                    $status->setStamp($time);
                    $status->setStatus($status_report[$anlage->getAnlName()]);
                    $em->persist($status);
                    $em->flush();
            }
            else if(!$anlage->getAnlMute()){

                $status = new Status();
                $sqlw = "SELECT  b.temp_ambient as temp, b.wind_speed as wspeed FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp = '$time' ";

                $resw = $conn->query($sqlw);
                $wdata = $resw->fetch(PDO::FETCH_ASSOC);
                if($wdata['temp'] != null ) {

                    $status_report[$anlage->getAnlName()]['temperature'] = "All good";
                }
                else  $status_report[$anlage->getAnlName()]['temperature'] = "No data";

                if($wdata['wspeed'] != null) {
                    if ($wdata['wspeed'] == 0 ) $status_report[$anlage->getAnlName()]['wspeed'] = "Wind Speed is 0";
                    else $status_report[$anlage->getAnlName()]['wspeed'] = "All good";
                }
                else  $status_report[$anlage->getAnlName()]['wspeed'] = "No data";
                $status->setAnlage($anlage);
                $status->setStamp($time);
                $status->setStatus($status_report[$anlage->getAnlName()]);
                $em->persist($status);
                $em->flush();
            }
        }
        dd($status_report);
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
    public static function timeAjustment($timestamp, float $val = 0, $reverse = false)
    {
        $format     = 'Y-m-d H:i:s';

        if (gettype($timestamp) != 'integer') $timestamp = strtotime($timestamp);
        ($reverse) ? $timestamp -= ($val * 3600) : $timestamp += ($val * 3600);

        return date($format, $timestamp);
    }
}
