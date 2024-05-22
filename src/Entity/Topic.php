<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


class Topic {

	use EntityHasIdTrait;

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

    /**
     * @var integer
     * @ORM\Column(name="issm", type="integer", nullable=true)
     */
    public $issm;

    public function getMonth(): ?string
    {
        return $this->month;
    }

    public function getStartDay(): ?string
    {
        return $this->startday;
    }

    public function getDaysInMonth(): ?string
    {
        return $this->daysinmonth;
    }

	public function isBugReport() {
		return $this->category === 'BUG_REPORT';
	}


}
