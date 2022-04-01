<?php

namespace App\Entity;

use App\Repository\AnlageSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AnlageSettingsRepository::class)
 */
class AnlageSettings
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity=Anlage::class, inversedBy="settings", cascade={"persist", "remove"})
     */
    private Anlage $anlage;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $name0;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $name1;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $name2;

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

    public function getName0(): string
    {
        if ($this->name0 === null){
            return 'AM';
        } else {
            return $this->name0;
        }
    }

    public function setName0(string $name0): self
    {
        $this->name0 = $name0;

        return $this;
    }

    public function getName1(): string
    {
        if ($this->name1 === null){
            return 'EPC';
        } else {
            return $this->name1;
        }
    }

    public function setName1(string $name1): self
    {
        $this->name1 = $name1;

        return $this;
    }

    public function getName2(): string
    {
        if ($this->name2 === null){
            return 'O&M';
        } else {
            return $this->name2;
        }
    }

    public function setName2(string $name2): self
    {
        $this->name2 = $name2;

        return $this;
    }
}
