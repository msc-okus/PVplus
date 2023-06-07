<?php

namespace App\Entity;

use App\Repository\ReplaceValuesTicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReplaceValuesTicketRepository::class)]
class ReplaceValuesTicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $stamp = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Anlage $anlage = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $irrHorizontal = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $irrModule = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $irrEast = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $irrWest = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $power = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStamp(): ?\DateTimeInterface
    {
        return $this->stamp;
    }

    public function setStamp(\DateTimeInterface $stamp): self
    {
        $this->stamp = $stamp;

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

    public function getIrrHorizontal(): ?string
    {
        return $this->irrHorizontal;
    }

    public function setIrrHorizontal(?string $irrHorizontal): self
    {
        $this->irrHorizontal = $irrHorizontal;

        return $this;
    }

    public function getIrrModule(): ?string
    {
        return $this->irrModule;
    }

    public function setIrrModule(?string $irrModule): self
    {
        $this->irrModule = $irrModule;

        return $this;
    }

    public function getIrrEast(): ?string
    {
        return $this->irrEast;
    }

    public function setIrrEast(?string $irrEast): self
    {
        $this->irrEast = $irrEast;

        return $this;
    }

    public function getIrrWest(): ?string
    {
        return $this->irrWest;
    }

    public function setIrrWest(?string $irrWest): self
    {
        $this->irrWest = $irrWest;

        return $this;
    }

    public function getPower(): ?string
    {
        return $this->power;
    }

    public function setPower(?string $power): self
    {
        $this->power = $power;

        return $this;
    }
}
