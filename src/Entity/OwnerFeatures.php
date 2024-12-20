<?php

namespace App\Entity;

use App\Repository\OwnerFeaturesRepository;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;

#[ORM\Entity(repositoryClass: OwnerFeaturesRepository::class)]
class OwnerFeatures
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'features', cascade: ['persist', 'remove'])]
    private ?Eigner $owner = null;

    #[ORM\Column(nullable: true, options: ['default' => '0'])]
    private ?bool $aktDep1 = false;

    #[ORM\Column(nullable: true, options: ['default' => '0'])]
    private ?bool $aktDep2 = false;

    #[ORM\Column(nullable: true, options: ['default' => '1'])]
    private ?bool $aktDep3 = true;

    #[ORM\Column(nullable: true, options: ['default' => '0'])]
    private ?bool $splitInverter = false;

    #[ORM\Column(nullable: true, options: ['default' => '0'])]
    private ?bool $manAktive = false; // MRO ('man' is the wrong wording)

    #[ORM\Column(nullable: true, options: ['default' => '0'])]
    private ?bool $amStringAnalyseAktive = false;

    #[ORM\Column(nullable: true, options: ['default' => '0'])]
    private ?bool $splitGap = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $simulatorName = "Simulation";

    #[ORM\Column(nullable: true, options: ['default' => '0'])]
    private ?bool $allow2fa = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?Eigner
    {
        return $this->owner;
    }

    public function setOwner(?Eigner $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function isAktDep1(): ?bool
    {
        return $this->aktDep1;
    }

    public function setAktDep1(?bool $aktDep1): self
    {
        $this->aktDep1 = $aktDep1;

        return $this;
    }

    public function isAktDep2(): ?bool
    {
        return $this->aktDep2;
    }

    public function setAktDep2(?bool $aktDep2): self
    {
        $this->aktDep2 = $aktDep2;

        return $this;
    }

    public function isAktDep3(): ?bool
    {
        return $this->aktDep3;
    }

    public function setAktDep3(?bool $aktDep3): self
    {
        $this->aktDep3 = $aktDep3;

        return $this;
    }

    public function isSplitInverter(): ?bool
    {
        return $this->splitInverter;
    }

    public function setSplitInverter(bool $SplitInverter): self
    {
        $this->splitInverter = $SplitInverter;

        return $this;
    }

    public function isSplitGap(): ?bool
    {
        return $this->splitGap;
    }

    public function setSplitGap(bool $SplitGap): self
    {
        $this->splitGap = $SplitGap;

        return $this;
    }

    #[Deprecated]
    public function isManAktive(): ?bool
    {
        return $this->manAktive;
    }

    #[Deprecated]
    public function setManAktive(bool $manActive): self
    {
        $this->manAktive = $manActive;

        return $this;
    }

    public function isMroAktive(): ?bool
    {
        return $this->manAktive;
    }

    public function setMroAktive(bool $manActive): self
    {
        $this->manAktive = $manActive;

        return $this;
    }
    public function isAmStringAnalyseAktive(): ?bool
    {
        return $this->amStringAnalyseAktive;
    }
    public function getAmStringAnalyseAktive(): ?bool
    {
        return $this->amStringAnalyseAktive;
    }

    public function setAmStringAnalyseAktive(?bool $amStringAnalyseAktive): void
    {
        $this->amStringAnalyseAktive = $amStringAnalyseAktive;
    }

    public function getSimulatorName(): ?string
    {
        return $this->simulatorName;
    }

    public function setSimulatorName(?string $simulatorName): self
    {
        $this->simulatorName = $simulatorName;

        return $this;
    }

    public function getAllow2fa(): ?bool
    {
        return $this->allow2fa;
    }

    public function setAllow2fa(?bool $allow2fa): void
    {
        $this->allow2fa = $allow2fa;
    }


}
