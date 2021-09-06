<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\OpenWeather;
use App\Helper\G4NTrait;
use App\Repository\OpenWeatherRepository;
use Doctrine\ORM\EntityManagerInterface;

class OpenWeatherService
{
    use G4NTrait;

    private OpenWeatherRepository $openWeatherRepo;
    private EntityManagerInterface $em;

    public function __construct( OpenWeatherRepository $openWeatherRepo, EntityManagerInterface $em)
    {
        $this->openWeatherRepo = $openWeatherRepo;
        $this->em = $em;
    }

    public function loadOpenWeather(Anlage $anlage){

        $timestamp = self::getCetTime() - (self::getCetTime() % (3 * 3600));
        $date = date('Y-m-d H:00:00', $timestamp);

        $apiKey = "795982a4e205f23abb3ce3cf9a9a032a";
        $lat = $anlage->getAnlGeoLat();
        $lng = $anlage->getAnlGeoLon();

        if ($lat and $lng) {
            $urli     = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lng&lang=en&APPID=$apiKey";
            $contents = file_get_contents($urli);
            $clima    = json_decode($contents);
            if ($clima) {
                $openWeather = $this->openWeatherRepo->findOneBy(['stamp' => $date, 'anlage' => $anlage]);

                if (!$openWeather) { // Wenn Daten nicht gefunden lege neu an
                    $openWeather = $openWeather = new OpenWeather();
                    $openWeather
                        ->setAnlage($anlage)
                        ->setStamp($date);
                }

                $openWeather
                    ->setTempC(round(($clima->main->temp - 273.15), 0))
                    ->setIconWeather(strtolower($clima->weather[0]->icon))
                    ->setDescription($clima->weather[0]->description)
                    ->setData(json_decode($contents, true));
                $this->em->persist($openWeather);
            }
        }

        $this->em->flush();

        return $date;
    }
}