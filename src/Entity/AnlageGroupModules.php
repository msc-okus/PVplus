<?php

namespace App\Entity;

use App\Repository\GroupModulesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupModulesRepository::class)]
class AnlageGroupModules
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $numStringsPerUnit = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $numStringsPerUnitEast = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $numStringsPerUnitWest = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $numModulesPerString = null;

    #[ORM\ManyToOne(targetEntity: AnlageGroups::class, inversedBy: 'modules')]
    private ?\App\Entity\AnlageGroups $anlageGroup = null;

    #[ORM\ManyToOne(targetEntity: AnlageModules::class, inversedBy: 'anlageGroupModules')]
    private ?\App\Entity\AnlageModules $moduleType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumStringsPerUnit(): ?int
    {
        return (int) $this->numStringsPerUnit;
    }

    public function setNumStringsPerUnit(string $numStringsPerUnit): self
    {
        $this->numStringsPerUnit = $numStringsPerUnit;

        return $this;
    }

    public function getNumStringsPerUnitEast(): ?int
    {
        return (int) $this->numStringsPerUnitEast;
    }

    public function setNumStringsPerUnitEast(?string $numStringsPerUnitEast): self
    {
        $this->numStringsPerUnitEast = $numStringsPerUnitEast;

        return $this;
    }

    public function getNumStringsPerUnitWest(): ?int
    {
        return (int) $this->numStringsPerUnitWest;
    }

    public function setNumStringsPerUnitWest(?string $numStringsPerUnitWest): self
    {
        $this->numStringsPerUnitWest = $numStringsPerUnitWest;

        return $this;
    }

    public function getNumModulesPerString(): ?int
    {
        return (int) $this->numModulesPerString;
    }

    public function setNumModulesPerString(string $numModulesPerString): self
    {
        $this->numModulesPerString = $numModulesPerString;

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

    public function getModuleType(): ?AnlageModules
    {
        return $this->moduleType;
    }

    public function setModuleType(?AnlageModules $moduleType): self
    {
        $this->moduleType = $moduleType;

        return $this;
    }

    public function getPower(): float
    {
        return $this->getNumStringsPerUnit()*$this->getNumModulesPerString()*$this->getModuleType()->getPower()/1000;
    }
}
