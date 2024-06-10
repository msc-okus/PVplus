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
    private ?string $apiUser = null;  // mc = Medio Control = VCOM

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiPassword = null; // mc = Medio Control = VCOM

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $apiToken = null;  // mc = Medio Control = VCOM

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

    public function getapiUser(): ?string
    {
        return $this->apiUser;
    }

    public function setapiUser(?string $apiUser): self
    {
        $this->apiUser = $apiUser;

        return $this;
    }

    public function getapiPassword(): ?string
    {
        if($this->apiPassword != NULL){
            return $this->unHashData($this->apiPassword);
        }else{
            return $this->apiPassword;
        }

    }

    public function setapiPassword(?string $apiPassword): self
    {
        $this->apiPassword = is_string($apiPassword) ? $this->hashData($apiPassword) : null;

        return $this;
    }

    public function getapiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setapiToken(?string $apiToken): self
    {
        $this->apiToken = $apiToken;

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
