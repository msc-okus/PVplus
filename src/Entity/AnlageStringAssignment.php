<?php

namespace App\Entity;

use App\Repository\AnlageStringAssignmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnlageStringAssignmentRepository::class)]
class AnlageStringAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $stationNr = null;

    #[ORM\Column(length: 255)]
    private ?string $inverterNr = null;

    #[ORM\Column(length: 255)]
    private ?string $stringNr = null;

    #[ORM\Column(length: 255)]
    private ?string $channelNr = null;

    #[ORM\Column(length: 255)]
    private ?string $stringActive = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $channelCat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tilt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $azimut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $panelType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $inverterType = null;

    #[ORM\ManyToOne(inversedBy: 'anlageStringAssignments')]
    private ?Anlage $anlage = null;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStationNr(): ?string
    {
        return $this->stationNr;
    }

    public function setStationNr(?string $stationNr): static
    {
        $this->stationNr = $stationNr;

        return $this;
    }

    public function getInverterNr(): ?string
    {
        return $this->inverterNr;
    }

    public function setInverterNr(string $inverterNr): static
    {
        $this->inverterNr = $inverterNr;

        return $this;
    }

    public function getStringNr(): ?string
    {
        return $this->stringNr;
    }

    public function setStringNr(string $stringNr): static
    {
        $this->stringNr = $stringNr;

        return $this;
    }

    public function getChannelNr(): ?string
    {
        return $this->channelNr;
    }

    public function setChannelNr(string $channelNr): static
    {
        $this->channelNr = $channelNr;

        return $this;
    }

    public function getStringActive(): ?string
    {
        return $this->stringActive;
    }

    public function setStringActive(string $stringActive): static
    {
        $this->stringActive = $stringActive;

        return $this;
    }

    public function getChannelCat(): ?string
    {
        return $this->channelCat;
    }

    public function setChannelCat(?string $channelCat): static
    {
        $this->channelCat = $channelCat;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getTilt(): ?string
    {
        return $this->tilt;
    }

    public function setTilt(string $tilt): static
    {
        $this->tilt = $tilt;

        return $this;
    }

    public function getAzimut(): ?string
    {
        return $this->azimut;
    }

    public function setAzimut(string $azimut): static
    {
        $this->azimut = $azimut;

        return $this;
    }

    public function getPanelType(): ?string
    {
        return $this->panelType;
    }

    public function setPanelType(string $panelType): static
    {
        $this->panelType = $panelType;

        return $this;
    }

    public function getInverterType(): ?string
    {
        return $this->inverterType;
    }

    public function setInverterType(?string $inverterType): static
    {
        $this->inverterType = $inverterType;

        return $this;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $anlage): static
    {
        $this->anlage = $anlage;

        return $this;
    }


}
