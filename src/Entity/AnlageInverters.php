<?php

namespace App\Entity;

use App\Repository\InvertersRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InvertersRepository::class)
 */
class AnlageInverters
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="Inverters")
     */
    private ?Anlage $anlage;

    /**
     * @ORM\Column(type="integer")
     */
    private int $invNr;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $InverterName;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerDc;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $anlage): self
    {
        $this->anlage = $anlage;

        return $this;
    }

    public function getInvNr(): ?int
    {
        return $this->invNr;
    }

    public function setInvNr(int $invNr): self
    {
        $this->invNr = $invNr;

        return $this;
    }

    public function getInverterName(): ?string
    {
        return $this->InverterName;
    }

    public function setInverterName(string $InverterName): self
    {
        $this->InverterName = $InverterName;

        return $this;
    }

    public function getPowerDc(): ?string
    {
        return $this->powerDc;
    }

    public function setPowerDc(string $powerDc): self
    {
        $this->powerDc = $powerDc;

        return $this;
    }
}
