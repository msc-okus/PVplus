<?php

namespace App\Entity;

use App\Repository\ForecastRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ForecastRepository::class)
 */
class AnlageForecast
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
    private string $expectedWeek;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $factorWeek;

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

    public function getFactorWeek(): ?float
    {
        return (float)$this->factorWeek;
    }

    public function setFactorWeek(int $factorWeek): self
    {
        $this->factorWeek = $factorWeek;

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

    public function getExpectedWeek(): ?float
    {
        return (float)str_replace(',', '.', $this->expectedWeek);
    }

    public function setExpectedWeek(string $expectedWeek): self
    {
        $this->expectedWeek = $expectedWeek;

        return $this;
    }

    public function getPowerWeek(): float
    {
<<<<<<< HEAD
        return ($this->anlage->getContractualGuarantiedPower() > 0) ? $this->getFactorWeek() * $this->anlage->getContractualGuarantiedPower() : $this->getExpectedWeek();
=======
        return ($this->anlage->getContractualGuarantiedPower() > 0) ? $this->factorWeek * $this->anlage->getContractualGuarantiedPower() : $this->expectedWeek;
>>>>>>> 429ad2e3e2e8f735b5c9ef5ca5ef63d2c838ad8c
    }

    public function getDivMinWeek(): float
    {
<<<<<<< HEAD
        return ($this->anlage->getContractualGuarantiedPower() > 0) ? $this->getFactorWeek() * $this->anlage->getContractualGuarantiedPower() * $this->getFactorMin() : $this->getExpectedWeek() * $this->getFactorMin();
=======
        return ($this->anlage->getContractualGuarantiedPower() > 0) ? $this->factorWeek * $this->anlage->getContractualGuarantiedPower() * $this->factorMin : $this->expectedWeek * $this->factorMin;
>>>>>>> 429ad2e3e2e8f735b5c9ef5ca5ef63d2c838ad8c
    }

    public function getDivMaxWeek(): float
    {
<<<<<<< HEAD
        return ($this->anlage->getContractualGuarantiedPower() > 0) ? $this->getFactorWeek() * $this->anlage->getContractualGuarantiedPower() * $this->getFactorMax() : $this->getExpectedWeek() * $this->getFactorMax();
=======
        return ($this->anlage->getContractualGuarantiedPower() > 0) ? $this->factorWeek * $this->anlage->getContractualGuarantiedPower() * $this->factorMax : $this->expectedWeek * $this->factorMax;
>>>>>>> 429ad2e3e2e8f735b5c9ef5ca5ef63d2c838ad8c
    }

}
