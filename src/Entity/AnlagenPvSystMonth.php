<?php

namespace App\Entity;

use App\Repository\PvSystMonthRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: PvSystMonthRepository::class)]
class AnlagenPvSystMonth
{
    use TimestampableEntity;

    use BlameableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'anlagenPvSystMonths')]
    private ?Anlage $anlage;

    /**
     * @deprecated
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $stamp = '';

    #[ORM\Column(type: 'string', length: 20)]
    private string $prDesign;

    #[ORM\Column(type: 'string', length: 20)]
    private string $ertragDesign;

    #[ORM\Column(type: 'string', length: 20)]
    private string $irrDesign;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tempAmbientDesign;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tempArrayAvgDesign;

    #[ORM\Column(type: 'integer')]
    private int $month;

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

    public function getStamp(): ?string
    {
        return $this->stamp;
    }

    public function setStamp(string $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    public function getPrDesign(): ?float
    {
        return (float) $this->prDesign;
    }

    public function setPrDesign(string $prDesign): self
    {
        $this->prDesign = $prDesign;

        return $this;
    }

    public function getErtragDesign(): ?float
    {
        return (float) $this->ertragDesign;
    }

    public function setErtragDesign(string $ertragDesign): self
    {
        $this->ertragDesign = $ertragDesign;

        return $this;
    }

    public function getIrrDesign(): ?float
    {
        return (float) $this->irrDesign;
    }

    public function setIrrDesign(?string $irrDesign): self
    {
        $this->irrDesign = $irrDesign;

        return $this;
    }

    public function getTempAmbientDesign(): float
    {
        return (float) $this->tempAmbientDesign;
    }

    public function setTempAmbientDesign(string $tempAmbientDesign): self
    {
        $this->tempAmbientDesign = $tempAmbientDesign;

        return $this;
    }

    public function getTempArrayAvgDesign(): float
    {
        return (float) $this->tempArrayAvgDesign;
    }

    public function setTempArrayAvgDesign(string $tempArrayAvgDesign): self
    {
        $this->tempArrayAvgDesign = $tempArrayAvgDesign;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(string $month): self
    {
        $this->month = $month;

        return $this;
    }
}
