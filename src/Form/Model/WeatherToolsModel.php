<?php

namespace App\Form\Model;

use App\Entity\WeatherStation;

class WeatherToolsModel
{
    public WeatherStation $anlage;

    public \DateTime $startDate;

    public \DateTime $endDate;

}
