<?php

namespace App\Entity;

use App\Repository\OwnerSettingsRepository;
use App\Service\PiiCryptoService;
use Doctrine\ORM\Mapping as ORM;



#[ORM\Entity(repositoryClass: OwnerSettingsRepository::class)]
class ApiConfig extends PiiCryptoService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $apiType = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $configName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $mcUser = null;  // mc = Medio Control = VCOM

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mcPassword = null; // mc = Medio Control = VCOM

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mcToken = null;  // mc = Medio Control = VCOM

    #[ORM\ManyToOne(inversedBy: 'apiConfig')]
    private ?Eigner $owner = null;

    public function getApiType(): ?string
    {
        return $this->apiType;
    }

    public function setApiType(?string $apiType): static
    {
        $this->apiType = $apiType;

        return $this;
    }

    public function getConfigName(): ?string
    {
        return $this->configName;
    }

    public function setConfigName(?string $configName): static
    {
        $this->configName = $configName;

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
        $this->mcPassword = is_string($mcPassword) ? $this->hashData($mcPassword) : null;

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

    public function getOwner(): ?Eigner
    {
        return $this->owner;
    }

    public function setOwner(?Eigner $Owner): static
    {
        $this->owner = $Owner;

        return $this;
    }
}
