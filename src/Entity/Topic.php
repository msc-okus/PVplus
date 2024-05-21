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
	 * @var object
	 * @ORM\Column(name="anlage", type="object", nullable=false)
	 * @Assert\Choice(callback="getValidCategories", groups={"flow_createTopic_step1"}, strict=true)
	 * @Assert\NotBlank(groups={"flow_createTopic_step1"})
	 */
	public $anlage;

	/**
	 * @var array
	 * @ORM\Column(name="startday", type="array", nullable=true)
	 */
	public $startday;

    /**
     * @var array
     * @ORM\Column(name="endday", type="array", nullable=true)
     */
    public $endday;

	/**
	 * @var string
	 * @ORM\Column(name="details", type="text", nullable=true)
	 * @Assert\NotBlank(groups={"flow_createTopic_step3"})
	 */
	public $details;

	public function isBugReport() {
		return $this->category === 'BUG_REPORT';
	}


}
