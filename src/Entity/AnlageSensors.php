<?php

namespace App\Entity;

use App\Repository\AnlageSensorsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnlageSensorsRepository::class)]
class AnlageSensors
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sensors')]
    private ?Anlage $anlage = null;

    #[ORM\Column(length: 20)]
    private ?string $nameShort = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\Column(length: 20)]
    private ?string $orientation = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $vcomId = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $vcomAbbr = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNameShort(): ?string
    {
        return $this->nameShort;
    }

    public function setNameShort(string $nameShort): static
    {
        $this->nameShort = $nameShort;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function setOrientation(string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function getVcomId(): ?string
    {
        return $this->vcomId;
    }

    public function setVcomId(?string $vcomId): static
    {
        $this->vcomId = $vcomId;

        return $this;
    }

    public function getVcomAbbr(): ?string
    {
        return $this->vcomAbbr;
    }

    public function setVcomAbbr(?string $vcomAbbr): static
    {
        $this->vcomAbbr = $vcomAbbr;

        return $this;
    }
}
