<?php

namespace App\Entity;

use App\Repository\DayLightDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DayLightDataRepository::class)]
class DayLightData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 40)]
    private $Sunrise;

    #[ORM\Column(type: 'string', length: 40)]
    private $Sunset;

    #[ORM\Column(type: 'string', length: 40)]
    private $date;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'dayLightData')]
    private $anlage;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSunrise(): ?string
    {
        return $this->Sunrise;
    }

    public function setSunrise(string $Sunrise): self
    {
        $this->Sunrise = $Sunrise;

        return $this;
    }

    public function getSunset(): ?string
    {
        return $this->Sunset;
    }

    public function setSunset(string $Sunset): self
    {
        $this->Sunset = $Sunset;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
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
}
