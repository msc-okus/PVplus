<?php

namespace App\Form\Model;

use App\Entity\Anlage;

class ToolsModel
{
    public Anlage $anlage;

    public \DateTime $startDate;

    public \DateTime $endDate;

    public string $function;
}