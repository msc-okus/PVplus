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

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'O-Skadow'])]
    private ?string $mcUser = 'O-Skadow';  // mc = Medio Control = VCOM

    #[ORM\Column(length: 255, nullable: true, options: ['default' => 'Tr3z%2!x$5'])]
    private ?string $mcPassword = 'Tr3z%2!x$5'; // mc = Medio Control = VCOM

    #[ORM\Column(length: 100, nullable: true, options: ['default' => '264b63333e951a6c327d627003f6a828'])]
    private ?string $mcToken = '264b63333e951a6c327d627003f6a828';  // mc = Medio Control = VCOM

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

    public function getMcUser(): ?string
    {
        return $this->mcUser;
    }

    public function setMcUser(?string $mcUser): self
    {
        $this->mcUser = $mcUser;

        return $this;
    }

    public function getMcPassword(): ?string
    {
        if($this->mcPassword != NULL){
            return $this->unHashData($this->mcPassword);
        }else{
            return $this->mcPassword;
        }

    }

    public function setMcPassword(?string $mcPassword): self
    {
        $this->mcPassword = $this->hashData($mcPassword);

        return $this;
    }

    public function getMcToken(): ?string
    {
        return $this->mcToken;
    }

    public function setMcToken(?string $mcToken): self
    {
        $this->mcToken = $mcToken;

        return $this;
    }
}
