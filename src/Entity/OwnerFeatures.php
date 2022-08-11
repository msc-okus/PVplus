<?php

namespace App\Entity;

use App\Repository\OwnerFeaturesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OwnerFeaturesRepository::class)]
class OwnerFeatures
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'ownerFeatures', cascade: ['persist', 'remove'])]
    private ?Eigner $owner = null;

    #[ORM\Column(nullable: true)]
    private ?bool $aktDep1 = null;

    #[ORM\Column(nullable: true)]
    private ?bool $aktDep2 = null;

    #[ORM\Column(nullable: true)]
    private ?bool $aktDep3 = null;

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
}
