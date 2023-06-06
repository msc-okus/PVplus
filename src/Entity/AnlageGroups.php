<?php

namespace App\Entity;

use App\Helper\G4NTrait;
use App\Repository\GroupsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupsRepository::class)]
class AnlageGroups
{
    use G4NTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer')]
    private int $dcGroup;

    #[ORM\Column(type: 'string', length: 30)]
    private string $dcGroupName;

    #[ORM\Column(type: 'integer')]
    private int $acGroup;

    #[ORM\Column(type: 'integer')]
    private int $unitFirst;

    #[ORM\Column(type: 'integer')]
    private int $unitLast;

    #[ORM\Column(type: 'string', length: 20)]
    private string $irrUpper;

    #[ORM\Column(type: 'string', length: 20)]
    private string $irrLower;

    #[ORM\Column(type: 'string', length: 20)]
    private string $shadowLoss = '0';

    #[ORM\Column(type: 'string', length: 20)]
    private string $cabelLoss = '0';

    #[ORM\Column(type: 'string', length: 20)]
    private string $secureLoss = '0';

    #[ORM\Column(type: 'string', length: 20)]
    private string $factorAC;

    #[ORM\Column(type: 'string', length: 20)]
    private string $gridLoss;

    #[ORM\Column(type: 'string', length: 20)]
    private string $limitAc;

    #[ORM\Column(type: 'string', length: 20)]
    private string $gridLimitAc;

    #[ORM\Column(type: 'string', length: 40)]
    private string $importId;

    #[ORM\OneToMany(mappedBy: 'anlageGroup', targetEntity: AnlageGroupMonths::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['month' => 'ASC'])]
    private Collection $months;

    #[ORM\OneToMany(mappedBy: 'anlageGroup', targetEntity: AnlageGroupModules::class, cascade: ['persist', 'remove'])]
    private Collection $modules;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'groups')]
    private ?Anlage $anlage;

    #[ORM\ManyToOne(targetEntity: WeatherStation::class)]
    private ?WeatherStation $weatherStation;

    public function __construct()
    {
        $this->months = new ArrayCollection();
        $this->modules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDcGroup(): ?int
    {
        return $this->dcGroup;
    }

    public function setDcGroup(int $dcGroup): self
    {
        $this->dcGroup = $dcGroup;

        return $this;
    }

    public function getDcGroupName(): ?string
    {
        return $this->removeControlChar($this->dcGroupName);
    }

    public function setDcGroupName(string $dcGroupName): self
    {
        $this->dcGroupName = $dcGroupName;

        return $this;
    }

    public function getAcGroup(): ?int
    {
        return $this->acGroup;
    }

    public function setAcGroup(int $acGroup): self
    {
        $this->acGroup = $acGroup;

        return $this;
    }

    public function getUnitFirst(): ?int
    {
        return $this->unitFirst;
    }

    public function setUnitFirst(int $unitFirst): self
    {
        $this->unitFirst = $unitFirst;

        return $this;
    }

    public function getUnitLast(): ?int
    {
        return $this->unitLast;
    }

    public function setUnitLast(int $unitLast): self
    {
        $this->unitLast = $unitLast;

        return $this;
    }

    public function getIrrUpper(): ?float
    {
        return (float) str_replace(',', '.', $this->irrUpper);
    }

    public function setIrrUpper(string $irrUpper): self
    {
        $this->irrUpper = str_replace(',', '.', $irrUpper);

        return $this;
    }

    public function getIrrLower(): ?float
    {
        return (float) str_replace(',', '.', $this->irrLower);
    }

    public function setIrrLower(string $irrLower): self
    {
        $this->irrLower = str_replace(',', '.', $irrLower);

        return $this;
    }

    public function getShadowLoss(): ?float
    {
        return (float) str_replace(',', '.', $this->shadowLoss);
    }

    public function setShadowLoss(string $shadowLoss): self
    {
        $this->shadowLoss = str_replace(',', '.', $shadowLoss);

        return $this;
    }

    public function getCabelLoss(): ?float
    {
        return (float) str_replace(',', '.', $this->cabelLoss);
    }

    public function setCabelLoss(string $cabelLoss): self
    {
        $this->cabelLoss = str_replace(',', '.', $cabelLoss);

        return $this;
    }

    public function getSecureLoss(): ?float
    {
        return (float) str_replace(',', '.', $this->secureLoss);
    }

    public function setSecureLoss(string $secureLoss): self
    {
        $this->secureLoss = str_replace(',', '.', $secureLoss);

        return $this;
    }

    public function getImportId(): string
    {
        return $this->importId;
    }

    public function setImportId(string $importId): void
    {
        $this->importId = $importId;
    }

    /**
     * @return Collection|AnlageGroupMonths[]
     */
    public function getMonths(): Collection
    {
        return $this->months;
    }

    public function addMonth(AnlageGroupMonths $month): self
    {
        if (!$this->months->contains($month)) {
            $this->months[] = $month;
            $month->setAnlageGroup($this);
        }

        return $this;
    }

    public function removeMonth(AnlageGroupMonths $month): self
    {
        if ($this->months->removeElement($month)) {
            // set the owning side to null (unless already changed)
            if ($month->getAnlageGroup() === $this) {
                $month->setAnlageGroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(AnlageGroupModules $module): self
    {
        if (!$this->modules->contains($module)) {
            $this->modules[] = $module;
            $module->setAnlageGroup($this);
        }

        return $this;
    }

    public function removeModule(AnlageGroupModules $module): self
    {
        if ($this->modules->removeElement($module)) {
            // set the owning side to null (unless already changed)
            if ($module->getAnlageGroup() === $this) {
                $module->setAnlageGroup(null);
            }
        }

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

    public function getFactorAC(): ?float
    {
        return (float) $this->factorAC;
    }

    public function setFactorAC(string $factorAC): self
    {
        $this->factorAC = str_replace(',', '.', $factorAC);

        return $this;
    }

    public function getWeatherStation(): ?WeatherStation
    {
        return $this->weatherStation;
    }

    public function setWeatherStation(?WeatherStation $weatherStation): self
    {
        $this->weatherStation = $weatherStation;

        return $this;
    }

    public function getLimitAc(): ?float
    {
        return (float) $this->limitAc;
    }

    public function setLimitAc(string $limitAc): self
    {
        $this->limitAc = $limitAc;

        return $this;
    }

    public function getGridLoss(): ?float
    {
        return (float) $this->gridLoss;
    }

    public function setGridLoss(string $gridLoss): self
    {
        $this->gridLoss = $gridLoss;

        return $this;
    }

    public function getGridLimitAc(): ?string
    {
        return $this->gridLimitAc;
    }

    public function setGridLimitAc(string $gridLimitAc): self
    {
        $this->gridLimitAc = $gridLimitAc;

        return $this;
    }

    /**
     * Calculate Pnom for every single Inverter (Group)<br>
     * makes only sense if anlage->configType == 1 or 2<br>.
     */
    public function getPnomPerGroup(): float
    {
        $pNom = 0.0;
        foreach ($this->getModules() as $module) {
            $pNom += ($module->getNumStringsPerUnit() * $module->getNumModulesPerString() * $module->getModuleType()->getPower()) / 1000;
        }

        return $pNom;
    }




}
