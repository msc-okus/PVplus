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
    private ?Anlage $anlage = null;

    /**
     * @deprecated
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $stamp = '';

    #[ORM\Column(type: 'integer')]
    private int $month;

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

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(string $month): self
    {
        $this->month = $month;

        return $this;
    }

    public function getPrDesign(): ?float
    {
        return (float) $this->prDesign;
    }

    public function setPrDesign(string $prDesign): self
    {
        $this->prDesign = str_replace(',', '.',$prDesign);

        return $this;
    }

    public function getErtragDesign(): ?float
    {
        return (float) $this->ertragDesign;
    }

    public function setErtragDesign(string $ertragDesign): self
    {
        $this->ertragDesign = str_replace(',', '.',$ertragDesign);

        return $this;
    }

    public function getIrrDesign(): ?float
    {
        return (float) $this->irrDesign;
    }

    public function setIrrDesign(?string $irrDesign): self
    {
        $this->irrDesign = str_replace(',', '.',$irrDesign);

        return $this;
    }

    public function getTempAmbientDesign(): float
    {
        return (float) $this->tempAmbientDesign;
    }

    public function setTempAmbientDesign(string $tempAmbientDesign): self
    {
        $this->tempAmbientDesign = str_replace(',', '.',$tempAmbientDesign);

        return $this;
    }

    public function getTempArrayAvgDesign(): float
    {
        return (float) $this->tempArrayAvgDesign;
    }

    public function setTempArrayAvgDesign(string $tempArrayAvgDesign): self
    {
        $this->tempArrayAvgDesign = str_replace(',', '.',$tempArrayAvgDesign);

        return $this;
    }
}
