<?php

namespace App\Entity;

use App\Repository\AnlageMonthRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnlageMonthRepository::class)]
class AnlageMonth
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'anlageMonth')]
    #[ORM\JoinColumn(nullable: false)]
    private ?\App\Entity\Anlage $anlage = null;

    #[ORM\Column(type: 'integer')]
    private int $month;

    #[ORM\Column(type: 'string', length: 20)]
    private string $irrUpper;

    #[ORM\Column(type: 'string', length: 20)]
    private string $irrLower;

    #[ORM\Column(type: 'string', length: 20)]
    private string $shadowLoss;

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

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): self
    {
        $this->month = $month;

        return $this;
    }

    public function getIrrUpper(): ?float
    {
        return (float) str_replace(',', '.', $this->irrUpper);
    }

    public function setIrrUpper(string $irrUpper): self
    {
        $this->irrUpper = $irrUpper;

        return $this;
    }

    public function getIrrLower(): ?float
    {
        return (float) str_replace(',', '.', $this->irrLower);
    }

    public function setIrrLower(string $irrLower): self
    {
        $this->irrLower = $irrLower;

        return $this;
    }

    public function getShadowLoss(): ?float
    {
        return (float) str_replace(',', '.', $this->shadowLoss);
    }

    public function setShadowLoss(string $shadowLoss): self
    {
        $this->shadowLoss = $shadowLoss;

        return $this;
    }
}
