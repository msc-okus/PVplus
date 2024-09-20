<?php

namespace App\Entity;

use App\Repository\OwnerSettingsRepository;
use App\Service\PiiCryptoService;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: OwnerSettingsRepository::class)]
class OwnerSettings extends PiiCryptoService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'settings', cascade: ['persist', 'remove'])]
    private ?Eigner $owner = null;

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'O&M'])]
    private ?string $nameDep1 = 'O&M';

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'EPC'])]
    private ?string $nameDep2 = 'EPC';

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'AM'])]
    private ?string $nameDep3 = 'AM';

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

    public function getNameDep1(): ?string
    {
        return $this->nameDep1;
    }

    public function setNameDep1(?string $nameDep1): self
    {
        $this->nameDep1 = $nameDep1;

        return $this;
    }

    public function getNameDep2(): ?string
    {
        return $this->nameDep2;
    }

    public function setNameDep2(?string $nameDep2): self
    {
        $this->nameDep2 = $nameDep2;

        return $this;
    }

    public function getNameDep3(): ?string
    {
        return $this->nameDep3;
    }

    public function setNameDep3(?string $nameDep3): self
    {
        $this->nameDep3 = $nameDep3;

        return $this;
    }

}
