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
    private int $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $type;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $power;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $tempCoefCurrent;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $tempCoefPower;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorPowerA;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorPowerB;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorPowerC;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $operatorPowerD;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorCurrentA;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorCurrentB;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorCurrentC;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorCurrentD;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorCurrentE;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="modules")
     */
    private Anlage $anlage;

    /**
     * @ORM\OneToMany(targetEntity=AnlageGroupModules::class, mappedBy="moduleType")
     */
    private $anlageGroupModules;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $degradation;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $disableIrrDiscount = false;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrDiscount1 = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrDiscount2 = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrDiscount3 = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrDiscount4 = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrDiscount5 = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrDiscount6 = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrDiscount7 = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrDiscount8 = '0';

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

    public function setOperatorPowerD(string $operatorPowerD): self
    {
        $this->operatorPowerD =  str_replace(',', '.', $operatorPowerD);

        return $this;
    }

    public function getOperatorPowerD(): ?string
    {
        return $this->operatorPowerD;
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

    public function getFactorCurrent($irr): float
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

    public function getFactorPower($irr): float
    {
        @list($a1, $b1, $c1) = explode(":", $this->getOperatorPowerA());
        ($b1 and $c1) ? $a = $a1 * $b1 ** $c1 : $a = $a1;
        @list($a2, $b2, $c2) = explode(":", $this->getOperatorPowerB());
        ($b2 and $c2) ? $b = $a2 * $b2 ** $c2 : $b = $a2;
        @list($a3, $b3, $c3) = explode(":", $this->getOperatorPowerC());
        ($b3 and $c3) ? $c = $a3 * $b3 ** $c3 : $c = $a3;

        if ($this->getOperatorPowerD() === null) { // Operator D ist nicht eingeben => wir nutzen den alten Algorithmus
            if ($irr < (4 * $c)) $c = $c * 2 / 3;
            if ($irr < (3 * $c)) $c = $c * 3;
            if ($irr < (2 * $c)) $c = 0;
            $power = ($irr > 0) ? ($a * $irr ** 2) + ($b * $irr) + $c : 0;
        } else {
            @list($a3, $b3, $c3) = explode(":", $this->getOperatorPowerD());
            ($b3 and $c3) ? $d = $a3 * $b3 ** $c3 : $d = $a3;
            $power = ($irr > 0) ? ($a * $irr ** 3) + ($b * $irr ** 2) + ($c * $irr) + $d : 0;
        }

        return $power;
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
     * @return Collection
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

    public function getDisableIrrDiscount(): bool
    {
        return $this->disableIrrDiscount;
    }

    public function setDisableIrrDiscount(bool $disableIrrDiscount): self
    {
        $this->disableIrrDiscount = $disableIrrDiscount;

        return $this;
    }

    public function getIrrDiscount1(): ?float
    {
        return (float)$this->irrDiscount1;
    }

    public function setIrrDiscount1(string $irrDiscount1): self
    {
        $this->irrDiscount1 = str_replace(',', '.', $irrDiscount1);

        return $this;
    }

    public function getIrrDiscount2(): ?float
    {
        return (float)$this->irrDiscount2;
    }

    public function setIrrDiscount2(string $irrDiscount2): self
    {
        $this->irrDiscount2 = str_replace(',', '.', $irrDiscount2);

        return $this;
    }

    public function getIrrDiscount3(): ?float
    {
        return (float)$this->irrDiscount3;
    }

    public function setIrrDiscount3(string $irrDiscount3): self
    {
        $this->irrDiscount3 = str_replace(',', '.', $irrDiscount3);

        return $this;
    }

    public function getIrrDiscount4(): ?float
    {
        return (float)$this->irrDiscount4;
    }

    public function setIrrDiscount4(string $irrDiscount4): self
    {
        $this->irrDiscount4 = str_replace(',', '.', $irrDiscount4);

        return $this;
    }

    public function getIrrDiscount5(): float
    {
        return (float)$this->irrDiscount5;
    }

    public function setIrrDiscount5(string $irrDiscount5): void
    {
        $this->irrDiscount5 = $irrDiscount5;
    }

    public function getIrrDiscount6(): float
    {
        return (float)$this->irrDiscount6;
    }

    public function setIrrDiscount6(string $irrDiscount6): void
    {
        $this->irrDiscount6 = $irrDiscount6;
    }

    public function getIrrDiscount7(): float
    {
        return (float)$this->irrDiscount7;
    }

    public function setIrrDiscount7(string $irrDiscount7): void
    {
        $this->irrDiscount7 = $irrDiscount7;
    }

    public function getIrrDiscount8(): float
    {
        return (float)$this->irrDiscount8;
    }

    public function setIrrDiscount8(string $irrDiscount8): void
    {
        $this->irrDiscount8 = $irrDiscount8;
    }

}
