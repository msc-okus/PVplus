<?php

namespace App\Entity;

use App\Repository\AnlageSensorsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

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

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $orientation = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $virtualSensor = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?bool $useToCalc = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?bool $isFromBasics = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $vcomId = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $vcomAbbr = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $startDateSensor = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $endDateSensor = null;

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

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function setOrientation(?string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function getVirtualSensor(): ?string
    {
        return $this->virtualSensor;
    }

    public function setVirtualSensor(string $virtualSensor): static
    {
        $this->virtualSensor = $virtualSensor;

        return $this;
    }

    public function getUseToCalc(): ?bool
    {
        return $this->useToCalc;
    }

    public function setUseToCalc(?bool $useToCalc): static
    {
        $this->useToCalc = $useToCalc;

        return $this;
    }

    public function getIsFromBasics(): ?bool
    {
        return $this->isFromBasics;
    }

    public function setIsFromBasics(?bool $isFromBasics): static
    {
        $this->isFromBasics = $isFromBasics;
        return $this;
    }

    function getVcomId(): ?string
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

    public function getStartDateSensor(): ?\DateTimeInterface
    {
        return $this->startDateSensor;
    }

    public function setstartDateSensor(?\DateTimeInterface $startDateSensor = null): self
    {
        $this->startDateSensor = $startDateSensor;

        return $this;
    }

    public function getEndDateSensor(): ?\DateTimeInterface
    {
        return $this->endDateSensor;
    }

    public function setEndDateSensor(?\DateTimeInterface $endDateSensor = null): self
    {

        $this->endDateSensor = $endDateSensor;

        return $this;
    }
}
