<?php

namespace App\Form\Model;

use App\Entity\Anlage;

class ImportToolsModel
{
    public Anlage $anlage;

    public \DateTime $startDate;

    public \DateTime $endDate;

    public string $path;

    public string $importType;

    public int $hasPpc;
}
