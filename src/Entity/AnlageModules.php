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
     * @ORM\Column(type="boolean")
     */
    private bool $newExpected = false;

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
    private string $tempCoefVoltage;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $maxImpp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $maxUmpp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $maxPmpp;

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
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $operatorPowerE;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorPowerHighA;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorPowerHighB;

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
     * @ORM\Column(type="string", length=20)
     */
    private string $operatorCurrentHighA;

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

    public function getNewExpected(): bool
    {
        return $this->newExpected;
    }

    public function isNewExpected(): bool
    {
        return $this->newExpected;
    }

    public function setNewExpected(bool $newExpected): self
    {
        $this->newExpected = $newExpected;

        return $this;
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

    public function getTempCoefVoltage(): float
    {
        return (float)$this->tempCoefVoltage;
    }

    public function setTempCoefVoltage(string $tempCoefVoltage): self
    {
        $this->tempCoefVoltage = str_replace(',', '.', $tempCoefVoltage);

        return $this;
    }

    public function getMaxImpp(): float
    {
        return (float)$this->maxImpp;
    }

    public function setMaxImpp(string $maxImpp): self
    {
        $this->maxImpp = str_replace(',', '.', $maxImpp);

        return $this;
    }

    public function getMaxUmpp(): float
    {
        return (float)$this->maxUmpp;
    }

    public function setMaxUmpp(string $maxUmpp): self
    {
        $this->maxUmpp = str_replace(',', '.', $maxUmpp);

        return $this;
    }

    public function getMaxPmpp(): float
    {
        return (float)$this->maxPmpp;
    }

    public function setMaxPmpp(string $maxPmpp): self
    {
        $this->maxPmpp = str_replace(',', '.', $maxPmpp);

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

    public function getOperatorPowerE(): ?string
    {
        return $this->operatorPowerE;
    }

    public function setOperatorPowerE(string $operatorPowerE): self
    {
        $this->operatorPowerE =  str_replace(',', '.', $operatorPowerE);

        return $this;
    }

    public function getOperatorPowerHighA(): ?string
    {
        return $this->operatorPowerHighA;
    }

    public function setOperatorPowerHighA(string $operatorPowerHighA): self
    {
        $this->operatorPowerHighA =  str_replace(',', '.', $operatorPowerHighA);

        return $this;
    }

    public function getOperatorPowerHighB(): ?string
    {
        return $this->operatorPowerHighB;
    }

    public function setOperatorPowerHighB(string $operatorPowerHighB): self
    {
        $this->operatorPowerHighB =  str_replace(',', '.', $operatorPowerHighB);

        return $this;
    }

    ######## Cuurent

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

    public function getOperatorCurrentHighA(): ?string
    {
        return $this->operatorCurrentHighA;
    }

    public function setOperatorCurrentHighA(string $operatorCurrentHighA): self
    {
        $this->operatorCurrentHighA =  str_replace(',', '.', $operatorCurrentHighA);

        return $this;
    }

    #### Calulated Values

    /**
     * This Factor has to multiply by the numbers of modules, to calculate the expected current.<br>
     * The Parameter $irr (Irradiation) must be of type float.
     *
     * @param float $irr
     * @return float
     */
    public function getFactorCurrent(float $irr): float
    {
        if ($this->newExpected === true) {
            if ($irr > 200) {
                $expected = $this->getOperatorCurrentHighA() * $irr;
            } else {
                $expected = $this->getOperatorCurrentA() * $irr ** 4 + $this->getOperatorCurrentB() * $irr ** 3 + $this->getOperatorCurrentC() * $irr ** 2 + $this->getOperatorCurrentD() * $irr + $this->getOperatorCurrentE() ;
            }
            $expected = $expected > $this->maxImpp ? $this->maxImpp : $expected;
        } else {
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

            $expected = $irr > 0 ? ($a * $irr ** 4) + ($b * $irr ** 3) + ($c * $irr ** 2) + ($d * $irr) + $e : 0;
        }

        return $irr > 0 ? $expected : 0;
    }

    /**
     * This Factor has to multiply by the numbers of modules, to calculate the expected power.<br>
     * The Parameter $irr (Irradiation) must be of type float.
     *
     * @param float $irr
     * @return float
     */
    public function getFactorPower(float $irr): float
    {
        if ($this->newExpected === true) {
            // New Algoritmen
            if ($irr > 200) {
                $expected = $this->getOperatorPowerHighA() * $irr + $this->getOperatorPowerHighB();
            } else {
                $expected = $this->getOperatorPowerA() * $irr ** 4 + $this->getOperatorPowerB() * $irr ** 3 + $this->getOperatorPowerC() * $irr ** 2 + $this->getOperatorPowerD() * $irr + $this->getOperatorPowerE() ;
            }

            $dumpString = "Max PowerMPP: $this->maxPmpp – Old: $expected - ";

            $expected = $expected > $this->maxPmpp ? $this->maxPmpp : $expected;
            if ($irr > 950) {
                dump($dumpString . "New: $expected");
            }
        } else {
            // old Methode
            @list($a1, $b1, $c1) = explode(":", $this->getOperatorPowerA());
            ($b1 and $c1) ? $a = $a1 * $b1 ** $c1 : $a = $a1;
            @list($a2, $b2, $c2) = explode(":", $this->getOperatorPowerB());
            ($b2 and $c2) ? $b = $a2 * $b2 ** $c2 : $b = $a2;
            @list($a3, $b3, $c3) = explode(":", $this->getOperatorPowerC());
            ($b3 and $c3) ? $c = $a3 * $b3 ** $c3 : $c = $a3;

            if ($this->getOperatorPowerD() === null) { // Operator D ist nicht eingeben => wir nutzen den alten Algorithmus
                $expected = ($irr > 0) ? ($a * $irr ** 2) + ($b * $irr) + $c : 0;
            } else {
                @list($a4, $b4, $c4) = explode(":", $this->getOperatorPowerD());
                ($b4 and $c4) ? $d = $a4 * $b4 ** $c4 : $d = $a4;
                $expected = ($irr > 0) ? ($a * $irr ** 3) + ($b * $irr ** 2) + ($c * $irr) + $d : 0;
            }
        }

        return $irr > 0 ? $expected : 0;
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
        $this->irrDiscount5 = str_replace(',', '.', $irrDiscount5);
    }

    public function getIrrDiscount6(): float
    {
        return (float)$this->irrDiscount6;
    }

    public function setIrrDiscount6(string $irrDiscount6): void
    {
        $this->irrDiscount6 = str_replace(',', '.', $irrDiscount6);
    }

    public function getIrrDiscount7(): float
    {
        return (float)$this->irrDiscount7;
    }

    public function setIrrDiscount7(string $irrDiscount7): void
    {
        $this->irrDiscount7 = str_replace(',', '.', $irrDiscount7);
    }

    public function getIrrDiscount8(): float
    {
        return (float)$this->irrDiscount8;
    }

    public function setIrrDiscount8(string $irrDiscount8): void
    {
        $this->irrDiscount8 = str_replace(',', '.', $irrDiscount8);
    }

}
