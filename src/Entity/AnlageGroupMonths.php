<?php

namespace App\Entity;

use App\Repository\GroupMonthsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupMonthsRepository::class)]
class AnlageGroupMonths
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer')]
    private int $month;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private string $irrUpper;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private string $irrLower;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private string $shadowLoss;

    #[ORM\ManyToOne(targetEntity: AnlageGroups::class, inversedBy: 'months')]
    private ?\App\Entity\AnlageGroups $anlageGroup = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAnlageGroup(): ?AnlageGroups
    {
        return $this->anlageGroup;
    }

    public function setAnlageGroup(?AnlageGroups $anlageGroup): self
    {
        $this->anlageGroup = $anlageGroup;

        return $this;
    }
}
