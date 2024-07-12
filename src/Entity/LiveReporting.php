<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


class LiveReporting {

    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var array
     * @ORM\Column(name="year", type="array", nullable=false)
     * @Assert\NotBlank(groups={"flow_createTopic_step1"})
     */
    public $year;

    /**
     * @var array
     * @ORM\Column(name="month", type="array", nullable=true)
     */
    public $month;

    /**
     * @var string
     * @ORM\Column(name="anlagename", type="string", nullable=true)
     */
    public $anlagename;

    /**
     * @var integer
     * @ORM\Column(name="daysinmonth", type="integer", nullable=true)
     */
    public $daysinmonth;


    /**
     * @var object
     * @ORM\Column(name="anlage", type="object", nullable=false)
     * @Assert\Choice(callback="getValidCategories", groups={"flow_createTopic_step1"}, strict=true)
     * @Assert\NotBlank(groups={"flow_createTopic_step1"})
     */
    public $anlage;

    /**
     * @var string
     * @ORM\Column(name="startday", type="string", nullable=true)
     */
    public $startday;

    /**
     * @var string
     * @ORM\Column(name="endday", type="string", nullable=true)
     */
    public $endday;

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function getMonth(): ?string
    {
        return $this->month;
    }

    public function getAnlage(): ?string
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

    public function isBugReport() {
        return $this->category === 'BUG_REPORT';
    }


}
