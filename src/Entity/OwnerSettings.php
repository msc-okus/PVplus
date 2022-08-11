<?php

namespace App\Entity;

use App\Repository\OwnerSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OwnerSettingsRepository::class)]
class OwnerSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'settings', cascade: ['persist', 'remove'])]
    private ?Eigner $owner = null;

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
}
