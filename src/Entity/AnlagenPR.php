<?php

namespace App\Entity;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * DbAnlPrw
 *
 * @ORM\Table(name="anlagen_pr", indexes={@ORM\Index(name="stamp", columns={"stamp"})}, uniqueConstraints={@ORM\UniqueConstraint(name="uniquePR", columns={"anlage_id", "stamp"})})
 * @ORM\Entity(repositoryClass="App\Repository\PRRepository")
 */
class AnlagenPR
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="anl_id", type="string", length=50, nullable=false)
     */
    private string $anlId;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="stamp", type="date", nullable=false)
     */
    private DateTime $stamp;

    /**
     * @var DateTime
     * @deprecated
     * @ORM\Column(name="stamp_ist", type="datetime", nullable=false)
     */
    private DateTime $stampIst;

    /**
     * @var string
     *
     * @ORM\Column(name="power_act", type="string", length=20, nullable=false)
     */
    private string $powerAct;

    /**
     * @var string
     *
     * @ORM\Column(name="power_exp", type="string", length=20, nullable=false)
     */
    private string $powerExp;

    /**
     * @var string
     *
     * @ORM\Column(name="power_diff", type="string", length=20, nullable=false)
     */
    private string $powerDiff;

    /**
     * @var string
     *
     * @ORM\Column(name="pr_diff", type="string", length=20, nullable=false)
     */
    private string $prDiff;

    /**
     * @var string
     *
     * @ORM\Column(name="irradiation", type="string", length=20, nullable=false)
     */
    private string $irradiation;

    /**
     * @var string
     *
     * @ORM\Column(name="pr_act", type="string", length=20, nullable=false)
     */
    private string $prActPoz;

    /**
     * @var string
     *
     * @ORM\Column(name="pr_exp", type="string", length=20, nullable=false)
     */
    private string $prExpPoz;

    /**
     * @var string
     *
     * @ORM\Column(name="panneltemp", type="string", length=20, nullable=false)
     */
    private string $panneltemp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $power_evu;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $power_evu_year;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $power_act_year;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerExpYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $cust_irr;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prEvuProz;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="pr")
     */
    private ?Anlage $anlage;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $plantAvailability;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $plantAvailabilityPerYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $plantAvailabilityPerPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $plantAvailabilitySecond;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $plantAvailabilityPerYearSecond;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $plantAvailabilityPerPacSecond;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerTheo;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $g4nIrrAvg;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerEvuPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerActPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerExpPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $electricityGrid;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerPvSyst;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerPvSystYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerPvSystPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $tempCorrection;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private ?string $theoPowerPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $theoPowerYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $pacDate;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private array $irradiationJson = [];

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private array $temperaturJson = [];

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private array $windJson = [];

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $forecastSumAct;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $forecastSum;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $forecastDivMinus;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $forecastDivPlus;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prEvuMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prActMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $powerEGridExt;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $powerEGridExtPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $powerEGridExtYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prEGridExt;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prEGridExtPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prEGridExtYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $powerEGridExtMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prEGridExtMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prEvuPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prActPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prEvuYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prActYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prExpMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prExpPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prExpYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerActMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerExpMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $PowerEvuMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerTheoMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $plantAvailabilityPerMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $plantAvailabilityPerMonthSecond;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $IrrMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $IrrPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $IrrYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $spezYield;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $case5perDay;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $theoPowerDefault;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $theoPowerDefaultMonth;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $theoPowerDefaultPac;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $theoPowerDefaultYear;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultEvu;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultAct;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultExp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultEGridExt;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultMonthEvu;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultMonthAct;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultMonthExp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultMonthEGridExt;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultPacEvu;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultPacAct;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultPacExp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultPacEGridExt;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultYearEvu;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultYearAct;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultYearExp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDefaultYearEGridExt;


    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAnlId(): ?string
    {
        return $this->anlId;
    }

    public function setAnlId(string $anlId): self
    {
        $this->anlId = $anlId;

        return $this;
    }

    public function getstamp(): ?DateTime
    {
        return $this->stamp;
    }

    public function setstamp(DateTime $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    /**
     * @deprecated
     */
    public function getstampIst(): ?DateTime
    {
        return $this->stampIst;
    }

    /**
     * @deprecated
     */
    public function setstampIst(DateTime $stampIst): self
    {
        $this->stampIst = $stampIst;

        return $this;
    }

    public function getPowerAct(): ?string
    {
        return $this->powerAct;
    }

    public function setPowerAct(string $powerAct): self
    {
        $this->powerAct = $powerAct;

        return $this;
    }

    public function getPowerExp(): ?string
    {
        return $this->powerExp;
    }

    public function setPowerExp(string $powerExp): self
    {
        $this->powerExp = $powerExp;

        return $this;
    }

    public function getPowerDiff(): ?string
    {
        return $this->powerDiff;
    }

    public function setPowerDiff(string $powerDiff): self
    {
        $this->powerDiff = $powerDiff;

        return $this;
    }

    public function getPrDiff(): ?string
    {
        return $this->prDiff;
    }

    public function setPrDiff(string $prDiff): self
    {
        $this->prDiff = $prDiff;

        return $this;
    }

    public function getIrradiation(): ?string
    {
        return $this->irradiation;
    }

    public function setIrradiation(string $irradiation): self
    {
        $this->irradiation = $irradiation;

        return $this;
    }

    public function getPrAct(): ?string
    {
        return $this->prActPoz;
    }

    public function setPrAct(string $prActPoz): self
    {
        $this->prActPoz = $prActPoz;

        return $this;
    }

    public function getPrExp(): ?string
    {
        return $this->prExpPoz;
    }

    public function setPrExp(string $prExpPoz): self
    {
        $this->prExpPoz = $prExpPoz;

        return $this;
    }

    public function getPanneltemp(): ?string
    {
        return $this->panneltemp;
    }

    public function setPanneltemp(string $panneltemp): self
    {
        $this->panneltemp = $panneltemp;

        return $this;
    }

    public function getPowerEvu(): ?string
    {
        return $this->power_evu;
    }

    public function setPowerEvu(string $power_evu): self
    {
        $this->power_evu = $power_evu;

        return $this;
    }

    public function getPowerEvuYear(): ?string
    {
        return $this->power_evu_year;
    }

    public function setPowerEvuYear(string $power_evu_year): self
    {
        $this->power_evu_year = $power_evu_year;

        return $this;
    }

    public function getPowerActYear(): ?string
    {
        return $this->power_act_year;
    }

    public function setPowerActYear(string $power_act_year): self
    {
        $this->power_act_year = $power_act_year;

        return $this;
    }

    public function getPowerExpYear(): ?string
    {
        return $this->powerExpYear;
    }

    public function setPowerExpYear(string $powerExpYear): self
    {
        $this->powerExpYear = $powerExpYear;

        return $this;
    }

    public function getCustIrr(): ?string
    {
        return $this->cust_irr;
    }

    public function setCustIrr(string $cust_irr): self
    {
        $this->cust_irr = $cust_irr;

        return $this;
    }

    public function getPrEvu(): ?string
    {
        return $this->prEvuProz;
    }

    public function setPrEvu(string $prEvuProz): self
    {
        $this->prEvuProz = $prEvuProz;

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

    public function getPlantAvailability(): ?string
    {
        return $this->plantAvailability;
    }

    public function setPlantAvailability(string $plantAvailability): self
    {
        $this->plantAvailability = $plantAvailability;

        return $this;
    }

    public function getPlantAvailabilityPerYear(): ?string
    {
        return $this->plantAvailabilityPerYear;
    }

    public function setPlantAvailabilityPerYear(string $plantAvailabilityPerYear): self
    {
        $this->plantAvailabilityPerYear = $plantAvailabilityPerYear;

        return $this;
    }

    public function getPlantAvailabilityPerPac(): ?string
    {
        return $this->plantAvailabilityPerPac;
    }

    public function setPlantAvailabilityPerPac(string $plantAvailabilityPerPac): self
    {
        $this->plantAvailabilityPerPac = $plantAvailabilityPerPac;

        return $this;
    }

    public function getPlantAvailabilitySecond(): ?string
    {
        return $this->plantAvailabilitySecond;
    }

    public function setPlantAvailabilitySecond(string $plantAvailability): self
    {
        $this->plantAvailabilitySecond = $plantAvailability;

        return $this;
    }

    public function getPlantAvailabilityPerYearSecond(): ?string
    {
        return $this->plantAvailabilityPerYearSecond;
    }

    public function setPlantAvailabilityPerYearSecond(string $plantAvailabilityPerYear): self
    {
        $this->plantAvailabilityPerYearSecond = $plantAvailabilityPerYear;

        return $this;
    }

    public function getPlantAvailabilityPerPacSecond(): ?string
    {
        return $this->plantAvailabilityPerPacSecond;
    }

    public function setPlantAvailabilityPerPacSecond(string $plantAvailabilityPerPac): self
    {
        $this->plantAvailabilityPerPacSecond = $plantAvailabilityPerPac;

        return $this;
    }

    public function getPowerTheo(): ?string
    {
        return $this->powerTheo;
    }

    public function setPowerTheo(string $powerTheo): self
    {
        $this->powerTheo = $powerTheo;

        return $this;
    }

    public function getG4nIrrAvg(): ?string
    {
        return $this->g4nIrrAvg;
    }

    public function setG4nIrrAvg(string $g4nIrrAvg): self
    {
        $this->g4nIrrAvg = $g4nIrrAvg;

        return $this;
    }

    public function getPowerEvuPac(): ?string
    {
        return $this->powerEvuPac;
    }

    public function setPowerEvuPac(string $powerEvuPac): self
    {
        $this->powerEvuPac = $powerEvuPac;

        return $this;
    }

    public function getPowerActPac(): ?string
    {
        return $this->powerActPac;
    }

    public function setPowerActPac(string $powerActPac): self
    {
        $this->powerActPac = $powerActPac;

        return $this;
    }

    public function getPowerExpPac(): ?string
    {
        return $this->powerExpPac;
    }

    public function setPowerExpPac(string $powerExpPac): self
    {
        $this->powerExpPac = $powerExpPac;

        return $this;
    }

    public function getPrPac(): ?string
    {
        return $this->prPac;
    }

    public function setPrPac(string $prPac): self
    {
        $this->prPac = $prPac;

        return $this;
    }

    public function getElectricityGrid(): ?string
    {
        return $this->electricityGrid;
    }

    public function setElectricityGrid(string $electricityGrid): self
    {
        $this->electricityGrid = $electricityGrid;

        return $this;
    }

    public function getPowerPvSyst(): ?string
    {
        return $this->powerPvSyst;
    }

    public function setPowerPvSyst(string $powerPvSyst): self
    {
        $this->powerPvSyst = $powerPvSyst;

        return $this;
    }

    public function getPowerPvSystYear(): ?string
    {
        return $this->powerPvSystYear;
    }

    public function setPowerPvSystYear(string $powerPvSystYear): self
    {
        $this->powerPvSystYear = $powerPvSystYear;

        return $this;
    }

    public function getPowerPvSystPac(): ?string
    {
        return $this->powerPvSystPac;
    }

    public function setPowerPvSystPac(string $powerPvSystPac): self
    {
        $this->powerPvSystPac = $powerPvSystPac;

        return $this;
    }

    public function getTempCorrection(): ?string
    {
        return $this->tempCorrection;
    }

    public function setTempCorrection(string $tempCorrection): self
    {
        $this->tempCorrection = $tempCorrection;

        return $this;
    }

    public function getTheoPowerPac(): ?string
    {
        return $this->theoPowerPac;
    }

    public function setTheoPowerPac(string $theoPowerPac): self
    {
        $this->theoPowerPac = $theoPowerPac;

        return $this;
    }

    public function getTheoPowerYear(): ?string
    {
        return $this->theoPowerYear;
    }

    public function setTheoPowerYear(string $theoPowerYear): self
    {
        $this->theoPowerYear = $theoPowerYear;

        return $this;
    }

    public function getPacDate(): ?string
    {
        return $this->pacDate;
    }

    public function setPacDate(string $pacDate): self
    {
        $this->pacDate = $pacDate;

        return $this;
    }

    public function getIrradiationJson(): ?array
    {
        return $this->irradiationJson;
    }

    public function setIrradiationJson(array $irradiationJson): self
    {
        $this->irradiationJson = $irradiationJson;

        return $this;
    }

    public function getTemperaturJson(): ?array
    {
        return $this->temperaturJson;
    }

    public function setTemperaturJson(array $temperaturJson): self
    {
        $this->temperaturJson = $temperaturJson;

        return $this;
    }

    public function getWindJson(): ?array
    {
        return $this->windJson;
    }

    public function setWindJson(array $windJson): self
    {
        $this->windJson = $windJson;

        return $this;
    }

    public function getForecastSumAct(): ?string
    {
        return $this->forecastSumAct;
    }

    public function setForecastSumAct(string $forecastSumAct): self
    {
        $this->forecastSumAct = $forecastSumAct;

        return $this;
    }

    public function getForecastSum(): ?string
    {
        return $this->forecastSum;
    }

    public function setForecastSum(string $forecastSum): self
    {
        $this->forecastSum = $forecastSum;

        return $this;
    }

    public function getForecastDivMinus(): ?string
    {
        return $this->forecastDivMinus;
    }

    public function setForecastDivMinus(string $forecastDivMinus): self
    {
        $this->forecastDivMinus = $forecastDivMinus;

        return $this;
    }

    public function getForecastDivPlus(): ?string
    {
        return $this->forecastDivPlus;
    }

    public function setForecastDivPlus(string $forecastDivPlus): self
    {
        $this->forecastDivPlus = $forecastDivPlus;

        return $this;
    }

    public function getPrEvuMonth(): ?string
    {
        return $this->prEvuMonth;
    }

    public function setPrEvuMonth(string $prEvuMonth): self
    {
        $this->prEvuMonth = $prEvuMonth;

        return $this;
    }

    public function getPrActMonth(): ?string
    {
        return $this->prActMonth;
    }

    public function setPrActMonth(string $prActMonth): self
    {
        $this->prActMonth = $prActMonth;

        return $this;
    }

    public function getPowerEGridExt(): ?string
    {
        return $this->powerEGridExt;
    }

    public function setPowerEGridExt(string $powerEGridExt): self
    {
        $this->powerEGridExt = $powerEGridExt;

        return $this;
    }

    public function getPowerEGridExtPac(): ?string
    {
        return $this->powerEGridExtPac;
    }

    public function setPowerEGridExtPac(string $powerEGridExtPac): self
    {
        $this->powerEGridExtPac = $powerEGridExtPac;

        return $this;
    }

    public function getPowerEGridExtYear(): ?string
    {
        return $this->powerEGridExtYear;
    }

    public function setPowerEGridExtYear(string $powerEGridExtYear): self
    {
        $this->powerEGridExtYear = $powerEGridExtYear;

        return $this;
    }

    public function getPrEGridExt(): ?string
    {
        return $this->prEGridExt;
    }

    public function setPrEGridExt(string $prEGridExt): self
    {
        $this->prEGridExt = $prEGridExt;

        return $this;
    }

    public function getPrEGridExtPac(): ?string
    {
        return $this->prEGridExtPac;
    }

    public function setPrEGridExtPac(string $prEGridExtPac): self
    {
        $this->prEGridExtPac = $prEGridExtPac;

        return $this;
    }

    public function getPrEGridExtYear(): ?string
    {
        return $this->prEGridExtYear;
    }

    public function setPrEGridExtYear(string $prEGridExtYear): self
    {
        $this->prEGridExtYear = $prEGridExtYear;

        return $this;
    }

    public function getPowerEGridExtMonth(): ?string
    {
        return $this->powerEGridExtMonth;
    }

    public function setPowerEGridExtMonth(string $powerEGridExtMonth): self
    {
        $this->powerEGridExtMonth = $powerEGridExtMonth;

        return $this;
    }

    public function getPrEGridExtMonth(): ?string
    {
        return $this->prEGridExtMonth;
    }

    public function setPrEGridExtMonth(string $prEGridExtMonth): self
    {
        $this->prEGridExtMonth = $prEGridExtMonth;

        return $this;
    }

    public function getPrEvuPac(): ?string
    {
        return $this->prEvuPac;
    }

    public function setPrEvuPac(string $prEvuPac): self
    {
        $this->prEvuPac = $prEvuPac;

        return $this;
    }

    public function getPrActPac(): ?string
    {
        return $this->prActPac;
    }

    public function setPrActPac(string $prActPac): self
    {
        $this->prActPac = $prActPac;

        return $this;
    }

    public function getPrEvuYear(): ?string
    {
        return $this->prEvuYear;
    }

    public function setPrEvuYear(string $prEvuYear): self
    {
        $this->prEvuYear = $prEvuYear;

        return $this;
    }

    public function getPrActYear(): ?string
    {
        return $this->prActYear;
    }

    public function setPrActYear(string $prActYear): self
    {
        $this->prActYear = $prActYear;

        return $this;
    }

    public function getPrExpMonth(): ?string
    {
        return $this->prExpMonth;
    }

    public function setPrExpMonth(string $prExpMonth): self
    {
        $this->prExpMonth = $prExpMonth;

        return $this;
    }

    public function getPrExpPac(): ?string
    {
        return $this->prExpPac;
    }

    public function setPrExpPac(string $prExpPac): self
    {
        $this->prExpPac = $prExpPac;

        return $this;
    }

    public function getPrExpYear(): ?string
    {
        return $this->prExpYear;
    }

    public function setPrExpYear(string $prExpYear): self
    {
        $this->prExpYear = $prExpYear;

        return $this;
    }

    public function getPowerActMonth(): ?string
    {
        return $this->powerActMonth;
    }

    public function setPowerActMonth(string $powerActMonth): self
    {
        $this->powerActMonth = $powerActMonth;

        return $this;
    }

    public function getPowerExpMonth(): ?string
    {
        return $this->powerExpMonth;
    }

    public function setPowerExpMonth(string $powerExpMonth): self
    {
        $this->powerExpMonth = $powerExpMonth;

        return $this;
    }

    public function getPowerEvuMonth(): ?string
    {
        return $this->PowerEvuMonth;
    }

    public function setPowerEvuMonth(string $PowerEvuMonth): self
    {
        $this->PowerEvuMonth = $PowerEvuMonth;

        return $this;
    }

    public function getPowerTheoMonth(): ?string
    {
        return $this->powerTheoMonth;
    }

    public function setPowerTheoMonth(string $powerTheoMonth): self
    {
        $this->powerTheoMonth = $powerTheoMonth;

        return $this;
    }

    public function getPlantAvailabilityPerMonth(): ?string
    {
        return $this->plantAvailabilityPerMonth;
    }

    public function setPlantAvailabilityPerMonth(string $plantAvailbilityPerMonth): self
    {
        $this->plantAvailabilityPerMonth = $plantAvailbilityPerMonth;

        return $this;
    }

    public function getPlantAvailabilityPerMonthSecond(): ?string
    {
        return $this->plantAvailabilityPerMonthSecond;
    }

    public function setPlantAvailabilityPerMonthSecond(string $plantAvailbilityPerMonthSecond): self
    {
        $this->plantAvailabilityPerMonthSecond = $plantAvailbilityPerMonthSecond;

        return $this;
    }

    public function getIrrMonth(): ?string
    {
        return $this->IrrMonth;
    }

    public function setIrrMonth(string $IrrMonth): self
    {
        $this->IrrMonth = $IrrMonth;

        return $this;
    }

    public function getIrrPac(): ?string
    {
        return $this->IrrPac;
    }

    public function setIrrPac(string $IrrPac): self
    {
        $this->IrrPac = $IrrPac;

        return $this;
    }

    public function getIrrYear(): ?string
    {
        return $this->IrrYear;
    }

    public function setIrrYear(string $IrrYear): self
    {
        $this->IrrYear = $IrrYear;

        return $this;
    }

    public function getSpezYield(): ?string
    {
        return $this->spezYield;
    }

    public function setSpezYield(string $spezYield): self
    {
        $this->spezYield = $spezYield;

        return $this;
    }

    public function getCase5perDay(): ?string
    {
        return $this->case5perDay;
    }

    public function setCase5perDay(string $case5perDay): self
    {
        $this->case5perDay = $case5perDay;

        return $this;
    }

    public function getTheoPowerDefault(): ?string
    {
        return $this->theoPowerDefault;
    }

    public function setTheoPowerDefault(string $theoPowerDefault): self
    {
        $this->theoPowerDefault = $theoPowerDefault;

        return $this;
    }

    public function getTheoPowerDefaultMonth(): ?string
    {
        return $this->theoPowerDefaultMonth;
    }

    public function setTheoPowerDefaultMonth(string $theoPowerDefaultMonth): self
    {
        $this->theoPowerDefaultMonth = $theoPowerDefaultMonth;

        return $this;
    }

    public function getTheoPowerDefaultPac(): ?string
    {
        return $this->theoPowerDefaultPac;
    }

    public function setTheoPowerDefaultPac(string $theoPowerDefaultPac): self
    {
        $this->theoPowerDefaultPac = $theoPowerDefaultPac;

        return $this;
    }

    public function getTheoPowerDefaultYear(): ?string
    {
        return $this->theoPowerDefaultYear;
    }

    public function setTheoPowerDefaultYear(string $theoPowerDefaultYear): self
    {
        $this->theoPowerDefaultYear = $theoPowerDefaultYear;

        return $this;
    }

    public function getPrDefaultEvu(): ?string
    {
        return $this->prDefaultEvu;
    }

    public function setPrDefaultEvu(string $prDefaultEvu): self
    {
        $this->prDefaultEvu = $prDefaultEvu;

        return $this;
    }

    public function getPrDefaultAct(): ?string
    {
        return $this->prDefaultAct;
    }

    public function setPrDefaultAct(string $prDefaultAct): self
    {
        $this->prDefaultAct = $prDefaultAct;

        return $this;
    }

    public function getPrDefaultExp(): ?string
    {
        return $this->prDefaultExp;
    }

    public function setPrDefaultExp(string $prDefaultExp): self
    {
        $this->prDefaultExp = $prDefaultExp;

        return $this;
    }

    public function getPrDefaultEGridExt(): ?string
    {
        return $this->prDefaultEGridExt;
    }

    public function setPrDefaultEGridExt(string $prDefaultEGridExt): self
    {
        $this->prDefaultEGridExt = $prDefaultEGridExt;

        return $this;
    }

    public function getPrDefaultMonthEvu(): ?string
    {
        return $this->prDefaultMonthEvu;
    }

    public function setPrDefaultMonthEvu(string $prDefaultMonthEvu): self
    {
        $this->prDefaultMonthEvu = $prDefaultMonthEvu;

        return $this;
    }

    public function getPrDefaultMonthAct(): ?string
    {
        return $this->prDefaultMonthAct;
    }

    public function setPrDefaultMonthAct(string $prDefaultMonthAct): self
    {
        $this->prDefaultMonthAct = $prDefaultMonthAct;

        return $this;
    }

    public function getPrDefaultMonthExp(): ?string
    {
        return $this->prDefaultMonthExp;
    }

    public function setPrDefaultMonthExp(string $prDefaultMonthExp): self
    {
        $this->prDefaultMonthExp = $prDefaultMonthExp;

        return $this;
    }

    public function getPrDefaultMonthEGridExt(): ?string
    {
        return $this->prDefaultMonthEGridExt;
    }

    public function setPrDefaultMonthEGridExt(string $prDefaultMonthEGridExt): self
    {
        $this->prDefaultMonthEGridExt = $prDefaultMonthEGridExt;

        return $this;
    }

    public function getPrDefaultPacEvu(): ?string
    {
        return $this->prDefaultPacEvu;
    }

    public function setPrDefaultPacEvu(string $prDefaultPacEvu): self
    {
        $this->prDefaultPacEvu = $prDefaultPacEvu;

        return $this;
    }

    public function getPrDefaultPacAct(): ?string
    {
        return $this->prDefaultPacAct;
    }

    public function setPrDefaultPacAct(string $prDefaultPacAct): self
    {
        $this->prDefaultPacAct = $prDefaultPacAct;

        return $this;
    }

    public function getPrDefaultPacExp(): ?string
    {
        return $this->prDefaultPacExp;
    }

    public function setPrDefaultPacExp(string $prDefaultPacExp): self
    {
        $this->prDefaultPacExp = $prDefaultPacExp;

        return $this;
    }

    public function getPrDefaultPacEGridExt(): ?string
    {
        return $this->prDefaultPacEGridExt;
    }

    public function setPrDefaultPacEGridExt(string $prDefaultPacEGridExt): self
    {
        $this->prDefaultPacEGridExt = $prDefaultPacEGridExt;

        return $this;
    }

    public function getPrDefaultYearEvu(): ?string
    {
        return $this->prDefaultYearEvu;
    }

    public function setPrDefaultYearEvu(string $prDefaultYearEvu): self
    {
        $this->prDefaultYearEvu = $prDefaultYearEvu;

        return $this;
    }

    public function getPrDefaultYearAct(): ?string
    {
        return $this->prDefaultYearAct;
    }

    public function setPrDefaultYearAct(string $prDefaultYearAct): self
    {
        $this->prDefaultYearAct = $prDefaultYearAct;

        return $this;
    }

    public function getPrDefaultYearExp(): ?string
    {
        return $this->prDefaultYearExp;
    }

    public function setPrDefaultYearExp(string $prDefaultYearExp): self
    {
        $this->prDefaultYearExp = $prDefaultYearExp;

        return $this;
    }

    public function getPrDefaultYearEGridExt(): ?string
    {
        return $this->prDefaultYearEGridExt;
    }

    public function setPrDefaultYearEGridExt(string $prDefaultYearEGridExt): self
    {
        $this->prDefaultYearEGridExt = $prDefaultYearEGridExt;

        return $this;
    }
}
