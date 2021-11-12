<?php

namespace App\Entity;

use App\Repository\ForcastDayRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ForcastDayRepository::class)
 */
class AnlageForcastDay
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="anlageForecasts")
     */
    private ?Anlage $anlage;

    /**
     * @ORM\Column(type="integer")
     */
    private int $week;

    /**
     * @ORM\Column(type="integer")
     */
    private int $day;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $expectedDay;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $factorDay;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $factorMin;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $factorMax;


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

    public function getWeek(): ?int
    {
        return $this->week;
    }

    public function setWeek(int $week): self
    {
        $this->week = $week;

        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getFactorDay(): ?float
    {
        return (float)$this->factorDay;
    }

    public function setFactorDay(int $factorDay): self
    {
        $this->factorDay = $factorDay;

        return $this;
    }

    public function getFactorMin(): ?float
    {
        return (float)$this->factorMin;
    }

    public function setFactorMin(int $factorMin): self
    {
        $this->factorMin = $factorMin;

        return $this;
    }

    public function getFactorMax(): ?float
    {
        return (float)$this->factorMax;
    }

    public function setFactorMax(int $factorMax): self
    {
        $this->factorMax = $factorMax;

        return $this;
    }

    public function getExpectedDay(): ?float
    {
        return (float)str_replace(',', '.', $this->expectedDay);
    }

    public function setExpectedDay(string $expectedDay): self
    {
        $this->expectedDay = $expectedDay;

        return $this;
    }

    public function getPowerDay(): float
    {
        return ($this->anlage->getContractualGuarantiedPower() > 0) ? $this->getFactorDay() * $this->anlage->getContractualGuarantiedPower() : $this->getExpectedDay();
    }

    public function getDivMinDay(): float
    {
        return ($this->anlage->getContractualGuarantiedPower() > 0) ? $this->getFactorDay() * $this->anlage->getContractualGuarantiedPower() * $this->getFactorMin() : $this->getExpectedDay() * $this->getFactorMin();
    }

    public function getDivMaxDay(): float
    {
        return ($this->anlage->getContractualGuarantiedPower() > 0) ? $this->getFactorDay() * $this->anlage->getContractualGuarantiedPower() * $this->getFactorMax() : $this->getExpectedDay() * $this->getFactorMax();
    }
}
