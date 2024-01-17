<?php

namespace App\Form\Model;

use App\Entity\Anlage;

class ImportPvSystModel
{
    public Anlage $anlage;

    public string $separator;

    public string $dateFormat;

    public string $filename;

}
