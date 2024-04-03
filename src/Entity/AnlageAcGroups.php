<?php

namespace App\Entity;

use App\Helper\G4NTrait;
use App\Repository\AcGroupsRepository;
use App\Repository\GroupsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tabelle für AC Gruppen
 * anlage_groups_ac.
 */
#[ORM\Table(name: 'anlage_groups_ac')]
#[ORM\Entity(repositoryClass: AcGroupsRepository::class)]
class AnlageAcGroups
{
    use G4NTrait;

    #[ORM\Column(name: 'id', type: 'bigint', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private string $id; // DBAL return Type of bigint = string

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'acGroups')]
    private ?\App\Entity\Anlage $anlage = null;

    #[ORM\Column(name: 'trafo_nr', type: 'string', length: 20, nullable: true)]
    private ?string $trafoNr = null;

    #[ORM\Column(name: 'ac_group_id', type: 'string', length: 20, nullable: false)]
    private string $acGroup;

    #[ORM\Column(name: 'ac_group_name', type: 'string', length: 20, nullable: false)]
    private string $acGroupName;

    #[ORM\Column(name: 'unit_first', type: 'string', length: 20, nullable: false)]
    private string $unitFirst;

    #[ORM\Column(name: 'unit_last', type: 'string', length: 50, nullable: false)]
    private string $unitLast;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private string $limitation;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private string $dcPowerInverter;

    #[ORM\Column(type: 'boolean')]
    private bool $isEastWestGroup;

    #[ORM\ManyToOne(targetEntity: WeatherStation::class, inversedBy: 'anlageAcGroups')]
    private ?WeatherStation $weatherStation = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $gewichtungAnlagenPR = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string|array|null $tCellAvg = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $powerEast = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $powerWest = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $pyro1 = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $pyro2 = null;

    #[ORM\Column(type: 'string', length: 40, nullable: true)]
    private ?string $importId = null;

    public function __construct(
    )
    {
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAnlId(): ?string
    {
        return $this->anlage;
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

    public function getTrafoNr(): ?string
    {
        return $this->trafoNr;

    }

    public function setTrafoNr(string $trafoNr): self
    {
        $this->trafoNr = $trafoNr;

        return $this;
    }

    public function getAcGroup(): ?string
    {
        return $this->acGroup;
    }

    public function setAcGroup(string $acGroup): self
    {
        $this->acGroup = $acGroup;

        return $this;
    }

    public function getAcGroupName(): ?string
    {
        return self::removeControlChar($this->acGroupName);
    }

    public function setAcGroupName(string $acGroupName): self
    {
        $this->acGroupName = $acGroupName;

        return $this;
    }

    public function getUnitFirst(): ?int
    {
        return (int)$this->unitFirst;
    }

    public function setUnitFirst(string $unitFirst): self
    {
        $this->unitFirst = $unitFirst;

        return $this;
    }

    public function getUnitLast(): ?int
    {
        return $this->unitLast * 1;
    }

    public function setUnitLast(string $unitLast): self
    {
        $this->unitLast = $unitLast;

        return $this;
    }

    public function getDcPowerInverter(): float
    {
        return (float) $this->dcPowerInverter;
    }

    public function setDcPowerInverter(string $dcPowerInverter): self
    {
        $this->dcPowerInverter = str_replace(',', '.', $dcPowerInverter);

        return $this;
    }

    public function getLimitation(): string
    {
        return $this->limitation;
    }

    public function setLimitation(string $limitation): self
    {
        $this->limitation = str_replace(',', '.', $limitation);

        return $this;
    }

    public function getIsEastWestGroup(): ?bool
    {
        return $this->isEastWestGroup;
    }

    public function setIsEastWestGroup(bool $isEastWestGroup): self
    {
        $this->isEastWestGroup = $isEastWestGroup;

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

    public function getGewichtungAnlagenPR(): ?string
    {
        return $this->gewichtungAnlagenPR;
    }

    public function setGewichtungAnlagenPR(string $gewichtungAnlagenPR): self
    {
        $this->gewichtungAnlagenPR = str_replace(',', '.', $gewichtungAnlagenPR);

        return $this;
    }

    public function getTCellAvg(): ?string
    {
        return $this->tCellAvg;
    }

    public function setTCellAvg(string $tCellAvg): self
    {
        $this->tCellAvg = str_replace(',', '.', $tCellAvg);

        return $this;
    }

    public function getPowerEast(): ?string
    {
        return $this->powerEast;
    }

    public function setPowerEast(?string $powerEast): self
    {
        $this->powerEast = str_replace(',', '.', $powerEast);

        return $this;
    }

    public function getPowerWest(): ?string
    {
        return $this->powerWest;
    }

    public function setPowerWest(?string $powerWest): self
    {
        $this->powerWest = str_replace(',', '.', $powerWest);

        return $this;
    }

    public function getPyro1(): ?string
    {
        return $this->pyro1;
    }

    public function setPyro1(?string $pyro1): self
    {
        $this->pyro1 = $pyro1;

        return $this;
    }

    public function getPyro2(): ?string
    {
        return $this->pyro2;
    }

    public function setPyro2(?string $pyro2): self
    {
        $this->pyro2 = $pyro2;

        return $this;
    }

    public function getImportId(): ?string
    {
        return $this->importId;
    }

    public function setImportId(string $importId): void
    {
        $this->importId = $importId;
    }
}
