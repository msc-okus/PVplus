<?php

namespace App\Controller;

use App\Entity\WeatherStation;
use App\Form\WeatherStation\WeatherStationFormType;
use App\Helper\G4NTrait;
use App\Repository\WeatherStationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WeatherStationController extends BaseController
{
    use G4NTrait;

    /**
     * @Route("/admin/weather/new", name="app_admin_weather_new")
     */
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(WeatherStationFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var WeatherStation $station */
            $station = $form->getData();
            $this->createWeatherDatabase($station->getDatabaseIdent());
            $em->persist($station);
            $em->flush();
            $this->addFlash('success', 'New Weather Station created');

            return $this->redirectToRoute('app_admin_weather_list');
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_weather_list');
        }
        
        return $this->render('weather_station/new.html.twig', [
            'stationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/weather/edit/{id}", name="app_admin_weather_edit")
     */
    public function edit($id, EntityManagerInterface $em, Request $request, WeatherStationRepository $weatherStationRepo): Response
    {
        $station = $weatherStationRepo->find($id);
        $form = $this->createForm(WeatherStationFormType::class, $station);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {

            $em->persist($station);
            $em->flush();
            $this->addFlash('success', 'Owner saved!');
            if ($form->get('saveclose')->isClicked()) {
                return $this->redirectToRoute('app_admin_weather_list');
            }
        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_weather_list');
        }

        return $this->render('weather_station/edit.html.twig', [
            'stationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/weather/list", name="app_admin_weather_list")
     */
    public function list(Request $request, PaginatorInterface $paginator, WeatherStationRepository $weatherStationRepo): Response
    {
        $q = $request->query->get('qw');
        if ($request->query->get('search') == 'yes' && $q == '') $request->getSession()->set('qw', '');
        if ($q) $request->getSession()->set('qw', $q);

        if ($q == "" && $request->getSession()->get('qw') != "") {
            $q = $request->getSession()->get('qw');
            $request->query->set('qw', $q);
        }

        $queryBuilder = $weatherStationRepo->getWithSearchQueryBuilder($q);

        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            25                                         /*limit per page*/
        );

        return $this->render('weather_station/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @param $databaseIdent
     * @return bool
     */
    private function createWeatherDatabase($databaseIdent)
    {
        $pdo = self::getPdoConnection();
        $sqlCreateWeatherDatabase = "
        CREATE TABLE IF NOT EXISTS `db__pv_ws_$databaseIdent` (
            `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
            `anl_id` bigint(11) NOT NULL,
            `anl_intnr` varchar(50) NOT NULL,
            `stamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
            `at_avg` varchar(20) NOT NULL,
            `pt_avg` varchar(20) NOT NULL,
            `gi_avg` varchar(20) NOT NULL,
            `gmod_avg` varchar(20) NOT NULL,
            `g_upper` varchar(20) NOT NULL,
            `g_lower` varchar(20) NOT NULL,
            `g_horizontal` varchar(20) NOT NULL,            
            `temp_pannel` varchar(20) NOT NULL,
            `temp_ambient` varchar(20) NOT NULL,
            `rso` varchar(20) NOT NULL,
            `gi` varchar(20) NOT NULL,
            `wind_speed` varchar(20) NOT NULL,
            PRIMARY KEY (`db_id`),
            UNIQUE KEY `stamp` (`stamp`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
        $pdo->exec($sqlCreateWeatherDatabase);
        $pdo = null;

        return true;
    }
}
