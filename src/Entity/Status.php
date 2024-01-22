<?php

namespace App\Entity;

use App\Repository\StatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatusRepository::class)]
class Status
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $stamp = null;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'statuses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Anlage $Anlage = null;

    #[ORM\Column(type: 'text')]
    private ?string $Status = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isWeather = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStamp(): ?string
    {
        return $this->stamp;
    }

    public function setStamp(string $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->Anlage;
    }

    public function setAnlage(?Anlage $Anlage): self
    {
        $this->Anlage = $Anlage;

        return $this;
    }

    public function getStatus(): ?array
    {
        return unserialize($this->Status);
    }

    public function setStatus(array $Status): self
    {
        $this->Status = serialize($Status);

        return $this;
    }

    public function getIsWeather(): ?bool
    {
        return $this->isWeather;
    }

    public function setIsWeather(bool $isWeather): self
    {
        $this->isWeather = $isWeather;

        return $this;
    }
}
