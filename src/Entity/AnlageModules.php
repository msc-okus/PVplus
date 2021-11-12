<?php

namespace App\Entity;

use App\Repository\ModulesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ModulesRepository::class)
 */
class AnlageModules
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $power;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $tempCoefCurrent;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $tempCoefPower;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $operatorPowerA;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $operatorPowerB;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $operatorPowerC;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $operatorCurrentA;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $operatorCurrentB;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $operatorCurrentC;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $operatorCurrentD;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $operatorCurrentE;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="modules")
     */
    private $anlage;

    /**
     * @ORM\OneToMany(targetEntity=AnlageGroupModules::class, mappedBy="moduleType")
     */
    private $anlageGroupModules;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $degradation;

    public function __construct()
    {
        $this->anlageGroupModules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPower(): ?string
    {
        return $this->power;
    }

    public function setPower(string $power): self
    {
        $this->power =  str_replace(',', '.', $power);

        return $this;
    }

    public function getTempCoefCurrent(): ?float
    {
        return (float)$this->tempCoefCurrent;
    }

    public function setTempCoefCurrent(string $tempCoefCurrent): self
    {
        $this->tempCoefCurrent =  str_replace(',', '.', $tempCoefCurrent);

        return $this;
    }

    public function getTempCoefPower(): ?float
    {
        return (float)$this->tempCoefPower;
    }

    public function setTempCoefPower(string $tempCoefPower): self
    {
        $this->tempCoefPower =  str_replace(',', '.', $tempCoefPower);

        return $this;
    }

    public function getOperatorPowerA(): ?string
    {
        return $this->operatorPowerA;
    }

    public function setOperatorPowerA(string $operatorPowerA): self
    {
        $this->operatorPowerA =  str_replace(',', '.', $operatorPowerA);

        return $this;
    }

    public function getOperatorPowerB(): ?string
    {
        return $this->operatorPowerB;
    }

    public function setOperatorPowerB(string $operatorPowerB): self
    {
        $this->operatorPowerB =  str_replace(',', '.', $operatorPowerB);

        return $this;
    }

    public function getOperatorPowerC(): ?string
    {
        return $this->operatorPowerC;
    }

    public function setOperatorPowerC(string $operatorPowerC): self
    {
        $this->operatorPowerC =  str_replace(',', '.', $operatorPowerC);

        return $this;
    }

    public function getOperatorCurrentA(): ?string
    {
        return $this->operatorCurrentA;
    }

    public function setOperatorCurrentA(string $operatorCurrentA): self
    {
        $this->operatorCurrentA =  str_replace(',', '.', $operatorCurrentA);

        return $this;
    }

    public function getOperatorCurrentB(): ?string
    {
        return $this->operatorCurrentB;
    }

    public function setOperatorCurrentB(string $operatorCurrentB): self
    {
        $this->operatorCurrentB =  str_replace(',', '.', $operatorCurrentB);

        return $this;
    }

    public function getOperatorCurrentC(): ?string
    {
        return $this->operatorCurrentC;
    }

    public function setOperatorCurrentC(string $operatorCurrentC): self
    {
        $this->operatorCurrentC =  str_replace(',', '.', $operatorCurrentC);

        return $this;
    }

    public function getOperatorCurrentD(): ?string
    {
        return $this->operatorCurrentD;
    }

    public function setOperatorCurrentD(string $operatorCurrentD): self
    {
        $this->operatorCurrentD =  str_replace(',', '.', $operatorCurrentD);

        return $this;
    }

    public function getOperatorCurrentE(): ?string
    {
        return $this->operatorCurrentE;
    }

    public function setOperatorCurrentE(string $operatorCurrentE): self
    {
        $this->operatorCurrentE =  str_replace(',', '.', $operatorCurrentE);

        return $this;
    }

    public function getFactorCurrent($irr)
    {
        @list($a1, $b1, $c1) = explode(":", $this->getOperatorCurrentA());
        ($b1 and $c1) ? $a = $a1 * $b1 ** $c1 : $a = $a1;

        @list($a2, $b2, $c2) = explode(":", $this->getOperatorCurrentB());
        ($b2 and $c2) ? $b = $a2 * $b2 ** $c2 : $b = $a2;

        @list($a3, $b3, $c3) = explode(":", $this->getOperatorCurrentC());
        ($b3 and $c3) ? $c = $a3 * $b3 ** $c3 : $c = $a3;

        @list($a4, $b4, $c4) = explode(":", $this->getOperatorCurrentD());
        ($b4 and $c4) ? $d = $a4 * $b4 ** $c4 : $d = $a4;

        @list($a5, $b5, $c5) = explode(":", $this->getOperatorCurrentE());
        ($b5 and $c5) ? $e = $a5 * $b5 ** $c5 : $e = $a5;

        return ($irr > 0) ? ($a * $irr ** 4) + ($b * $irr ** 3) + ($c * $irr ** 2) + ($d * $irr) + $e : 0;
    }

    public function getFactorPower($irr)
    {
        @list($a1, $b1, $c1) = explode(":", $this->getOperatorPowerA());
        ($b1 and $c1) ? $a = $a1 * $b1 ** $c1 : $a = $a1;

        @list($a2, $b2, $c2) = explode(":", $this->getOperatorPowerB());
        ($b2 and $c2) ? $b = $a2 * $b2 ** $c2 : $b = $a2;

        @list($a3, $b3, $c3) = explode(":", $this->getOperatorPowerC());
        ($b3 and $c3) ? $c = $a3 * $b3 ** $c3 : $c = $a3;

        if ($irr < (4 * $c)) $c = $c * 2 / 3;
        if ($irr < (3 * $c)) $c = $c * 3;
        if ($irr < (2 * $c)) $c = 0;

        return ($irr > 0) ? ($a * $irr ** 2) + ($b * $irr) + $c : 0; //
    }

    public function getTempCorrPower(float $pannelTemp): float
    {
        return (float)(1 + ($this->getTempCoefPower() * ($pannelTemp - 25) / 100));
    }

    public function getTempCorrCurrent(float $pannelTemp): float
    {
        return (float)(1 + ($this->getTempCoefCurrent() * ($pannelTemp - 25) / 100));
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

    /**
     * @return Collection|AnlageGroupModules[]
     */
    public function getAnlageGroupModules(): Collection
    {
        return $this->anlageGroupModules;
    }

    public function addAnlageGroupModule(AnlageGroupModules $anlageGroupModule): self
    {
        if (!$this->anlageGroupModules->contains($anlageGroupModule)) {
            $this->anlageGroupModules[] = $anlageGroupModule;
            $anlageGroupModule->setModuleType($this);
        }

        return $this;
    }

    public function removeAnlageGroupModule(AnlageGroupModules $anlageGroupModule): self
    {
        if ($this->anlageGroupModules->removeElement($anlageGroupModule)) {
            // set the owning side to null (unless already changed)
            if ($anlageGroupModule->getModuleType() === $this) {
                $anlageGroupModule->setModuleType(null);
            }
        }

        return $this;
    }

    public function getDegradation(): ?float
    {
        return (float)$this->degradation;
    }

    public function setDegradation(string $degradation): self
    {
        $this->degradation =  str_replace(',', '.', $degradation);

        return $this;
    }
}
