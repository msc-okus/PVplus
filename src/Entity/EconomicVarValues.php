<?php

namespace App\Entity;

use App\Repository\EconomicVarValuesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EconomicVarValuesRepository::class)]
class EconomicVarValues
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $Month;

    #[ORM\Column(type: 'integer')]
    private $year;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'economicVarValues')]
    #[ORM\JoinColumn(nullable: false)]
    private $anlage;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_1;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_2;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_3;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_4;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_5;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_6;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_7;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_8;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_9;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_10;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_11;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_12;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_13;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_14;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $var_15;

    #[ORM\Column(type: 'string', length: 255)]
    private $KwHPrice;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMonth(): ?int
    {
        return $this->Month;
    }

    public function setMonth(int $Month): self
    {
        $this->Month = $Month;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
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

    public function getVar1(): ?string
    {
        return $this->var_1;
    }

    public function setVar1(string $var_1): self
    {
        $this->var_1 = $var_1;

        return $this;
    }

    public function getVar2(): ?string
    {
        return $this->var_2;
    }

    public function setVar2(string $var_2): self
    {
        $this->var_2 = $var_2;

        return $this;
    }

    public function getVar3(): ?string
    {
        return $this->var_3;
    }

    public function setVar3(string $var_3): self
    {
        $this->var_3 = $var_3;

        return $this;
    }

    public function getVar4(): ?string
    {
        return $this->var_4;
    }

    public function setVar4(string $var_4): self
    {
        $this->var_4 = $var_4;

        return $this;
    }

    public function getVar5(): ?string
    {
        return $this->var_5;
    }

    public function setVar5(string $var_5): self
    {
        $this->var_5 = $var_5;

        return $this;
    }

    public function getVar6(): ?string
    {
        return $this->var_6;
    }

    public function setVar6(string $var_6): self
    {
        $this->var_6 = $var_6;

        return $this;
    }

    public function getVar7(): ?string
    {
        return $this->var_7;
    }

    public function setVar7(string $var_7): self
    {
        $this->var_7 = $var_7;

        return $this;
    }

    public function getVar8(): ?string
    {
        return $this->var_8;
    }

    public function setVar8(string $var_8): self
    {
        $this->var_8 = $var_8;

        return $this;
    }

    public function getVar9(): ?string
    {
        return $this->var_9;
    }

    public function setVar9(string $var_9): self
    {
        $this->var_9 = $var_9;

        return $this;
    }

    public function getVar10(): ?string
    {
        return $this->var_10;
    }

    public function setVar10(string $var_10): self
    {
        $this->var_10 = $var_10;

        return $this;
    }

    public function getVar11(): ?string
    {
        return $this->var_11;
    }

    public function setVar11(string $var_11): self
    {
        $this->var_11 = $var_11;

        return $this;
    }

    public function getVar12(): ?string
    {
        return $this->var_12;
    }

    public function setVar12(string $var_12): self
    {
        $this->var_12 = $var_12;

        return $this;
    }

    public function getVar13(): ?string
    {
        return $this->var_13;
    }

    public function setVar13(string $var_13): self
    {
        $this->var_13 = $var_13;

        return $this;
    }

    public function getVar14(): ?string
    {
        return $this->var_14;
    }

    public function setVar14(string $var_14): self
    {
        $this->var_14 = $var_14;

        return $this;
    }

    public function getVar15(): ?string
    {
        return $this->var_15;
    }

    public function setVar15(string $var_15): self
    {
        $this->var_15 = $var_15;

        return $this;
    }

    public function getKwHPrice(): ?string
    {
        return $this->KwHPrice;
    }

    public function setKwHPrice(string $KwHPrice): self
    {
        $this->KwHPrice = $KwHPrice;

        return $this;
    }
}
