<?php

namespace App\Entity;

use App\Repository\AnlageSunShadingRepository;
use DateTime;
use App\Repository\AnlageModulesDBRepository;
use App\Repository\ModulesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Float_;

#[ORM\Entity(repositoryClass: AnlageModulesDBRepository::class)]
class AnlageModulesDB
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 30)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $power;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tempCoefCurrent;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tempCoefPower;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tempCoefVoltage;

    #[ORM\Column(type: 'string', length: 20)]
    private string $maxImpp;

    #[ORM\Column(type: 'string', length: 20)]
    private string $maxUmpp;

    #[ORM\Column(type: 'string', length: 20)]
    private string $maxPmpp;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorPowerA;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorPowerB;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorPowerC;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $operatorPowerD;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $operatorPowerE;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorPowerHighA;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorPowerHighB;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorCurrentA;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorCurrentB;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorCurrentC;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorCurrentD;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorCurrentE;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorCurrentHighA;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorVoltageA;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorVoltageB;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorVoltageHightA;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorVoltageHightB;

    #[ORM\Column(type: 'string', length: 20)]
    private string $operatorVoltageHightC;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $backSideFactor;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $modul_picture;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $data_sheet_1;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $data_sheet_2;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $annotation;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $producer;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $dimension_height;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $dimension_width;

    #[ORM\OneToOne(targetEntity: AnlageSunShading::class, mappedBy:"modulesDB", fetch:"EAGER")]
    private $modulesDBData;

    // MS
    //#[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'modules')]
    //private Anlage $anlage;

    #[ORM\OneToMany(mappedBy: 'moduleType', targetEntity: AnlageGroupModules::class)]
    private Collection $anlageGroupModules;

    #[ORM\Column(type: 'string', length: 20)]
    private string $degradation;

    public function __construct()
    {
        $this->anlageGroupModules = new ArrayCollection();
    }


    public function getModulesDBData()
    {
        return $this->modulesDBData;
    }

    public function setModulesDBData($modulesDBData)
    {
        $this->modulesDBData = $modulesDBData;

        return $this;
    }

/* ms
    public function getModulesDBData(): Collection
    {
        return $this->modulesDBData;
    }

    public function setModulesDBData(AnlageModulesDB $modulesDBData): self
    {
        if (!$this->modulesDBData->contains($modulesDBData)){
            $this->modulesDBData = $modulesDBData;
            $modulesDBData->setModulesDBData($this);
        }
        return $this;
    }

    public function delModulesDBData(AnlageModulesDB $modulesDBData): self
    {
        if ($this->modulesDBData->removeElement($modulesDBData)) {
            // set the owning side to null (unless already changed)
            if ($modulesDBData->getModulesDBData() === $this) {
                $modulesDBData->setModulesDBData(null);
            }
        }

        return $this;
    }
*/
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

    public function getPower(): ?float
    {
        return (float) $this->power;
    }

    public function setPower(string $power): self
    {
        $this->power = str_replace(',', '.', $power);

        return $this;
    }

    public function getTempCoefCurrent(): ?float
    {
        return (float) $this->tempCoefCurrent;
    }

    public function setTempCoefCurrent(string $tempCoefCurrent): self
    {
        $this->tempCoefCurrent = str_replace(',', '.', $tempCoefCurrent);

        return $this;
    }

    public function getTempCoefPower(): ?float
    {
        return (float) $this->tempCoefPower;
    }

    public function setTempCoefPower(string $tempCoefPower): self
    {
        $this->tempCoefPower = str_replace(',', '.', $tempCoefPower);

        return $this;
    }

    public function getTempCoefVoltage(): float
    {
        return (float) $this->tempCoefVoltage;
    }

    public function setTempCoefVoltage(string $tempCoefVoltage): self
    {
        $this->tempCoefVoltage = str_replace(',', '.', $tempCoefVoltage);

        return $this;
    }

    public function getMaxImpp(): float
    {
        return (float) $this->maxImpp;
    }

    public function setMaxImpp(string $maxImpp): self
    {
        $this->maxImpp = str_replace(',', '.', $maxImpp);

        return $this;
    }

    public function getMaxUmpp(): float
    {
        return (float) $this->maxUmpp;
    }

    public function setMaxUmpp(string $maxUmpp): self
    {
        $this->maxUmpp = str_replace(',', '.', $maxUmpp);

        return $this;
    }

    public function getMaxPmpp(): float
    {
        return (float) $this->maxPmpp;
    }

    public function setMaxPmpp(string $maxPmpp): self
    {
        $this->maxPmpp = str_replace(',', '.', $maxPmpp);

        return $this;
    }

    public function getOperatorPowerA(): ?float
    {
        return (float) $this->operatorPowerA;
    }

    public function setOperatorPowerA(string $operatorPowerA): self
    {
        $this->operatorPowerA = str_replace(',', '.', $operatorPowerA);

        return $this;
    }

    public function getOperatorPowerB(): ?float
    {
        return (float) $this->operatorPowerB;
    }

    public function setOperatorPowerB(string $operatorPowerB): self
    {
        $this->operatorPowerB = str_replace(',', '.', $operatorPowerB);

        return $this;
    }

    public function getOperatorPowerC(): ?float
    {
        return (float) $this->operatorPowerC;
    }

    public function setOperatorPowerD(string $operatorPowerD): self
    {
        $this->operatorPowerD = str_replace(',', '.', $operatorPowerD);

        return $this;
    }

    public function getOperatorPowerD(): ?float
    {
        return (float) $this->operatorPowerD;
    }

    public function setOperatorPowerC(string $operatorPowerC): self
    {
        $this->operatorPowerC = str_replace(',', '.', $operatorPowerC);

        return $this;
    }

    public function getOperatorPowerE(): ?float
    {
        return (float) $this->operatorPowerE;
    }

    public function setOperatorPowerE(string $operatorPowerE): self
    {
        $this->operatorPowerE = str_replace(',', '.', $operatorPowerE);

        return $this;
    }

    public function getOperatorPowerHighA(): ?float
    {
        return (float) $this->operatorPowerHighA;
    }

    public function setOperatorPowerHighA(string $operatorPowerHighA): self
    {
        $this->operatorPowerHighA = str_replace(',', '.', $operatorPowerHighA);

        return $this;
    }

    public function getOperatorPowerHighB(): ?float
    {
        return (float) $this->operatorPowerHighB;
    }

    public function setOperatorPowerHighB(string $operatorPowerHighB): self
    {
        $this->operatorPowerHighB = str_replace(',', '.', $operatorPowerHighB);

        return $this;
    }

    //

    public function getModulPicture(): ?string
    {
        return $this->modul_picture;
    }

    public function setModulPicture(string $modul_picture): self
    {
        $this->modul_picture = $modul_picture;
        return $this;
    }

    public function getDataSheet1(): ?string
    {
        return $this->data_sheet_1;
    }

    public function setDataSheet1(string $data_sheet_1): self
    {
        $this->data_sheet_1 = $data_sheet_1;
        return $this;
    }

    public function getDataSheet2(): ?string
    {
        return $this->data_sheet_2;
    }

    public function setDataSheet2(string $data_sheet_2): self
    {
        $this->data_sheet_2 = $data_sheet_2;
        return $this;
    }


    public function getAnnotation(): ?string
    {
        return $this->annotation;
    }

    public function setAnnotation(string $annotation): self
    {
        $this->annotation = $annotation;
        return $this;
    }


    public function getProducer(): ?string
    {
        return $this->producer;
    }

    public function setProducer(string $producer): self
    {
        $this->producer = $producer;
        return $this;
    }


    public function getDimensionHeight(): ?string
    {
        return $this->dimension_height;
    }

    public function setDimensionHeight(string $dimension_height): self
    {
        $this->dimension_height = $dimension_height;
        return $this;
    }


    public function getDimensionWidth(): ?string
    {
        return $this->dimension_width;
    }

    public function setDimensionWidth(string $dimension_width): self
    {
        $this->dimension_width = $dimension_width;
        return $this;
    }

    // ####### Cuurent
    public function getOperatorCurrentA(): ?float
    {
        return (float) $this->operatorCurrentA;
    }

    public function setOperatorCurrentA(string $operatorCurrentA): self
    {
        $this->operatorCurrentA = str_replace(',', '.', $operatorCurrentA);

        return $this;
    }

    public function getOperatorCurrentB(): ?float
    {
        return (float) $this->operatorCurrentB;
    }

    public function setOperatorCurrentB(string $operatorCurrentB): self
    {
        $this->operatorCurrentB = str_replace(',', '.', $operatorCurrentB);

        return $this;
    }

    public function getOperatorCurrentC(): ?float
    {
        return (float) $this->operatorCurrentC;
    }

    public function setOperatorCurrentC(string $operatorCurrentC): self
    {
        $this->operatorCurrentC = str_replace(',', '.', $operatorCurrentC);

        return $this;
    }

    public function getOperatorCurrentD(): ?float
    {
        return (float) $this->operatorCurrentD;
    }

    public function setOperatorCurrentD(string $operatorCurrentD): self
    {
        $this->operatorCurrentD = str_replace(',', '.', $operatorCurrentD);

        return $this;
    }

    public function getOperatorCurrentE(): ?float
    {
        return (float) $this->operatorCurrentE;
    }

    public function setOperatorCurrentE(string $operatorCurrentE): self
    {
        $this->operatorCurrentE = str_replace(',', '.', $operatorCurrentE);

        return $this;
    }

    public function getOperatorCurrentHighA(): ?float
    {
        return (float) $this->operatorCurrentHighA;
    }

    public function setOperatorCurrentHighA(string $operatorCurrentHighA): self
    {
        $this->operatorCurrentHighA = str_replace(',', '.', $operatorCurrentHighA);

        return $this;
    }

    public function getOperatorVoltageA(): ?float
    {
        return (float)$this->operatorVoltageA;
    }

    public function setOperatorVoltageA(string $operatorVoltageA): void
    {
        $this->operatorVoltageA = str_replace(',', '.', $operatorVoltageA);
    }

    public function getOperatorVoltageB(): ?float
    {
        return (float)$this->operatorVoltageB;
    }

    public function setOperatorVoltageB(string $operatorVoltageB): void
    {
        $this->operatorVoltageB = str_replace(',', '.', $operatorVoltageB);
    }

    public function getOperatorVoltageHightA(): ?float
    {
        return (float)$this->operatorVoltageHightA;
    }

    public function setOperatorVoltageHightA(string $operatorVoltageHightA): void
    {
        $this->operatorVoltageHightA = str_replace(',', '.', $operatorVoltageHightA);
    }

    public function getOperatorVoltageHightB(): ?float
    {
        return (float)$this->operatorVoltageHightB;
    }

    public function setOperatorVoltageHightB(string $operatorVoltageHightB): void
    {
        $this->operatorVoltageHightB = str_replace(',', '.', $operatorVoltageHightB);
    }

    public function getOperatorVoltageHightC(): ?float
    {
        return (float)$this->operatorVoltageHightC;
    }

    public function setOperatorVoltageHightC(string $operatorVoltageHightC): void
    {
        $this->operatorVoltageHightC = str_replace(',', '.', $operatorVoltageHightC);
    }



    // ### Calulated Values
    /**
     * This Factor has to multiply by the numbers of modules, to calculate the expected current.<br>
     * The Parameter $irr (Irradiation) must be of type float.
     *
     * @param float $irr
     * @return float
     */
    public function getFactorCurrent(float $irr): float
    {
        if ($irr > 200) {
            $expected = $this->getOperatorCurrentHighA() * ($irr * $this->getBackSideFactorMultiplier());
        } else {
            $expected = $this->getOperatorCurrentA() * ($irr * $this->getBackSideFactorMultiplier()) ** 4 + $this->getOperatorCurrentB() * ($irr * $this->getBackSideFactorMultiplier()) ** 3 + $this->getOperatorCurrentC() * ($irr * $this->getBackSideFactorMultiplier()) ** 2 + $this->getOperatorCurrentD() * ($irr * $this->getBackSideFactorMultiplier()) + $this->getOperatorCurrentE();
        }

        return $irr > 0 ? $expected : 0;
    }

    /**
     * Calculate the expected voltage for the given irradiation.
     * generate only values if $irr is greater then 2 Watt
     *
     * @param float $irr
     * @return float
     */
    public function getExpVoltage(float $irr): float
    {
        if ($irr > 200) {
            $expected = ($this->getOperatorVoltageHightA() * ($irr * $this->getBackSideFactorMultiplier()) ** 2 + $this->getOperatorVoltageHightB() * ($irr * $this->getBackSideFactorMultiplier())) + $this->getOperatorVoltageHightC();
        } else {
            $expected = ($this->getOperatorVoltageA() * log(($irr * $this->getBackSideFactorMultiplier()))) + $this->getOperatorVoltageB();
        }
        if ($expected > $this->getMaxUmpp()) $expected = $this->getMaxUmpp();

        return $irr > 2 ? $expected : 0;
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
        if ($irr > 200) {
            $expected = $this->getOperatorPowerHighA() * ($irr * $this->getBackSideFactorMultiplier())  + $this->getOperatorPowerHighB();
        } else {
            $expected = $this->getOperatorPowerA() * ($irr * $this->getBackSideFactorMultiplier()) ** 4 + $this->getOperatorPowerB() * ($irr * $this->getBackSideFactorMultiplier()) ** 3 + $this->getOperatorPowerC() * ($irr * $this->getBackSideFactorMultiplier()) ** 2 + $this->getOperatorPowerD() * ($irr * $this->getBackSideFactorMultiplier()) + $this->getOperatorPowerE();
        }
        $expected = $expected > $this->maxPmpp ? $this->getMaxPmpp() : $expected;

        return $irr > 0 ? $expected : 0;
    }

    public function getTempCorrPower(float $pannelTemp): float
    {
        return (1 + ($this->getTempCoefPower() * ($pannelTemp - 25) / 100));
    }

    public function getTempCorrCurrent(float $pannelTemp): float
    {
        return (1 + ($this->getTempCoefCurrent() * ($pannelTemp - 25) / 100));
    }

    public function getTempCorrVoltage(float $pannelTemp): float
    {
        return (1 + ($this->getTempCoefVoltage() * ($pannelTemp - 25) / 100));
    }

    public function getBackSideFactor(): ?float
    {
        return (float)$this->backSideFactor;
    }

    public function setBackSideFactor(string $backSideFactor): void
    {
        $this->backSideFactor = str_replace(',', '.', $backSideFactor);
    }

    public function getBackSideFactorMultiplier(): float
    {
        return 1 + $this->backSideFactor / 100;
    }
/*
    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $anlage): self
    {
        $this->anlage = $anlage;

        return $this;
    }

    public function getAnlageGroupModules(): Collection
    {
        return $this->anlageGroupModules;
    }
*/
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
        return (float) $this->degradation;
    }

    public function setDegradation(string $degradation): self
    {
        $this->degradation = str_replace(',', '.', $degradation);

        return $this;
    }
}
