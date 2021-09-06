<?php

namespace App\Entity;

use App\Repository\TimesConfigRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TimesConfigRepository::class)
 */
class TimesConfig
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="timesConfigs")
     */
    private $anlage;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     */
    private int $startDateMonth;

    /**
     * @ORM\Column(type="integer")
     */
    private int $startDateDay;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $startDate;

    /**
     * @ORM\Column(type="integer")
     */
    private int $endDateMonth;

    /**
     * @ORM\Column(type="integer")
     */
    private int $endDateDay;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $endDate;

    /**
     * @ORM\Column(type="time")
     */
    private $startTime;

    /**
     * @ORM\Column(type="time")
     */
    private $endTime;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $maxFailTime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $anlage): self
    {
        $this->anlage = $anlage;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStartDateMonth(): ?int
    {
        return $this->startDateMonth;
    }

    public function setStartDateMonth(int $startDateMonth): self
    {
        $this->startDateMonth = $startDateMonth;
        if (isset($this->startDateDay)) $this->startDate = "2000-" . $this->startDateMonth . "-" . $this->startDateDay;

        return $this;
    }

    public function getStartDateDay(): ?int
    {
        return $this->startDateDay;
    }

    public function setStartDateDay(int $startDateDay): self
    {
        $this->startDateDay = $startDateDay;
        if (isset($this->startDateMonth)) $this->startDate = "2000-" . $this->startDateMonth . "-" . $this->startDateDay;

        return $this;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function getEndDateMonth(): ?int
    {
        return $this->endDateMonth;
    }

    public function setEndDateMonth(int $endDateMonth): self
    {
        $this->endDateMonth = $endDateMonth;
        if (isset($this->endDateDay)) $this->endDate = "2000-" . $this->endDateMonth . "-" . $this->endDateDay;

        return $this;
    }

    public function getEndDateDay(): ?int
    {
        return $this->endDateDay;
    }

    public function setEndDateDay(int $endDateDay): self
    {
        $this->endDateDay = $endDateDay;
        if (isset($this->endDateMonth)) $this->endDate = "2000-" . $this->endDateMonth . "-" . $this->endDateDay;

        return $this;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getMaxFailTime(): ?string
    {
        return $this->maxFailTime;
    }

    public function setMaxFailTime(string $maxFailTime): self
    {
        $this->maxFailTime = $maxFailTime;

        return $this;
    }

}
