<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tabelle für AC Gruppen
 * anlage_groups_ac
 *
 * @ORM\Table(name="anlage_groups_ac")
 * @ORM\Entity(repositoryClass="App\Repository\AcGroupsRepository")
 */
class AnlageAcGroups
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="acGroups")
     */
    private $anlage;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_group_id", type="string", length=20, nullable=false)
     */
    private string $acGroup;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_group_name", type="string", length=20, nullable=false)
     */
    private string $acGroupName;

    /**
     * @var string
     *
     * @ORM\Column(name="unit_first", type="string", length=20, nullable=false)
     */
    private string $unitFirst;

    /**
     * @var string
     *
     * @ORM\Column(name="unit_last", type="string", length=50, nullable=false)
     */
    private string $unitLast;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private string $limitation;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private string $dcPowerInverter;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isEastWestGroup;

    /**
     * @ORM\ManyToOne(targetEntity=WeatherStation::class, inversedBy="anlageAcGroups")
     */
    private $weatherStation;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private ?string $gewichtungAnlagenPR;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $tCellAvg;


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
        return $this->acGroupName;
    }

    public function setAcGroupName(string $acGroupName): self
    {
        $this->acGroupName = $acGroupName;

        return $this;
    }

    public function getUnitFirst(): ?int
    {
        return $this->unitFirst * 1;
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

    public function getDcPowerInverter(): string
    {
        return $this->dcPowerInverter;
    }

    public function setDcPowerInverter(string $dcPowerInverter): self
    {
        $this->dcPowerInverter =  str_replace(',', '.', $dcPowerInverter);

        return $this;
    }

    public function getLimitation(): string
    {
        return $this->limitation;
    }

    public function setLimitation(string $limitation): self
    {
        $this->limitation =  str_replace(',', '.', $limitation);

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
        $this->gewichtungAnlagenPR = $gewichtungAnlagenPR;

        return $this;
    }

    public function getTCellAvg(): ?string
    {
        return $this->tCellAvg;
    }

    public function setTCellAvg(string $tCellAvg): self
    {
        $this->tCellAvg = $tCellAvg;

        return $this;
    }
}
