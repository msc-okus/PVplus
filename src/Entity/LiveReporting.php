<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


class LiveReporting {


    public $year;

    public string $month;

    public string $anlagename;

    public string $daysinmonth;

    public ?Anlage $anlage;

    public string $startday;

    public string $endday;


    public function getYear(): ?string
    {
        return $this->year;
    }

    public function getMonth(): ?string
    {
        return $this->month;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function getStartDay(): ?string
    {
        return $this->startday;
    }

    public function getEndDay(): ?string
    {
        return $this->endday;
    }

    public function getDaysInMonth(): ?string
    {
        return $this->daysinmonth;
    }

    public function isBugReport(): bool
    {
        return $this->category === 'BUG_REPORT';
    }

}
