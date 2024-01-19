<?php

namespace App\Entity;

use App\Repository\EconomicVarNamesRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EconomicVarNamesRepository::class)]
class EconomicVarNames
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_1 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_2 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_3 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_4 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_5 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_6 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_7 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_8 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_9 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_10 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_11 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_12 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_13 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_14 = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $var_15 = '';

    #[ORM\OneToOne(targetEntity: Anlage::class, inversedBy: 'economicVarNames')]
    #[ORM\JoinColumn(nullable: false)]
    private \App\Entity\Anlage|array|null $anlage = null;

    public function __construct()
    {
    }

    public function setparams(Anlage $anlage, string $var1, string $var2, string $var3, string $var4, string $var5, string $var6, string $var7, string $var8, string $var9, string $var10, string $var11, string $var12, string $var13, string $var14, string $var15)
    {
        $this->anlage = $anlage;
        $this->var_1 = $var1;
        $this->var_2 = $var2;
        $this->var_3 = $var3;
        $this->var_4 = $var4;
        $this->var_5 = $var5;
        $this->var_6 = $var6;
        $this->var_7 = $var7;
        $this->var_8 = $var8;
        $this->var_9 = $var9;
        $this->var_10 = $var10;
        $this->var_11 = $var11;
        $this->var_12 = $var12;
        $this->var_13 = $var13;
        $this->var_14 = $var14;
        $this->var_15 = $var15;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection|Anlage[]
     */
    public function getAnlage(): Collection
    {
        return $this->anlage;
    }

    public function addAnlage(Anlage $anlage): self
    {
        if (!$this->anlage->contains($anlage)) {
            $this->anlage[] = $anlage;
            $anlage->setEconomicVarNames($this);
        }

        return $this;
    }

    public function removeAnlage(Anlage $anlage): self
    {
        if ($this->anlage->removeElement($anlage)) {
            // set the owning side to null (unless already changed)
            if ($anlage->getEconomicVarNames() === $this) {
                $anlage->setEconomicVarNames(null);
            }
        }

        return $this;
    }

    public function getNamesArray(): array
    {
        if ($this->var_1 != '') {
            $name['var_1'] = $this->var_1;
        }
        if ($this->var_2 != '') {
            $name['var_2'] = $this->var_2;
        }
        if ($this->var_3 != '') {
            $name['var_3'] = $this->var_3;
        }
        if ($this->var_4 != '') {
            $name['var_4'] = $this->var_4;
        }
        if ($this->var_5 != '') {
            $name['var_5'] = $this->var_5;
        }
        if ($this->var_6 != '') {
            $name['var_6'] = $this->var_6;
        }
        if ($this->var_7 != '') {
            $name['var_7'] = $this->var_7;
        }
        if ($this->var_8 != '') {
            $name['var_8'] = $this->var_8;
        }
        if ($this->var_9 != '') {
            $name['var_9'] = $this->var_9;
        }
        if ($this->var_10 != '') {
            $name['var_10'] = $this->var_10;
        }
        if ($this->var_11 != '') {
            $name['var_11'] = $this->var_11;
        }
        if ($this->var_12 != '') {
            $name['var_12'] = $this->var_12;
        }
        if ($this->var_13 != '') {
            $name['var_13'] = $this->var_13;
        }
        if ($this->var_14 != '') {
            $name['var_14'] = $this->var_14;
        }
        if ($this->var_15 != '') {
            $name['var_15'] = $this->var_15;
        }

        return $name;
    }
}
