<?php

namespace App\Entity;

use App\Repository\AnlageSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;

#[ORM\Entity(repositoryClass: AnlageSettingsRepository::class)]
class AnlageSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\OneToOne(inversedBy: 'settings', targetEntity: Anlage::class, cascade: ['persist', 'remove'])]
    private ?Anlage $anlage = null;

    // Settings for Department handling
    #[ORM\Column(type: 'string', length: 20)]
    #[Deprecated]
    private string $paDep1Name = 'EPC';

    #[ORM\Column(type: 'string', length: 20)]
    #[Deprecated]
    private string $paDep2Name = 'O&M';

    #[ORM\Column(type: 'string', length: 20)]
    #[Deprecated]
    private string $paDep3Name = 'AM';

    #[ORM\Column(nullable: true)]
    private ?bool $disableDep1 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $disableDep2 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $disableDep3 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $enablePADep1 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $enablePADep2 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $enablePADep3 = true;

    // Settings for Expected
    #[ORM\Column(nullable: true)]
    private ?bool $epxCalculationByCurrent = true; // if true = caclulate Expected by current*voltage / if false = caclulate by power settings

    // Handling PA calculation
    #[ORM\Column(type: 'string', length: 20)]
    private string $paDefaultDataGapHandling = 'available';

    // Settings for Chart Select-menu
    #[ORM\Column(nullable: true)]
    private ?bool $chartAC1 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC2 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC3 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC4 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC5 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC6 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC7 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC8 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC9 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC1 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC2 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC3 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC4 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC5 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC6 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse1 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse2 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse3 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse4 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse5 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse6 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse7 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse8 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse9 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse10 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse11 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAnalyse12 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartCurr1 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartCurr2 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartCurr3 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartVolt1 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartVolt2 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartVolt3 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartSensor1 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartSensor2 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartSensor3 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartSensor4 = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $apiConfig = null;

    #[ORM\Column(nullable: true)]
    private ?bool $symfonyImport = false;

    #[ORM\Column(nullable: true)]
    private ?bool $huaweiImport = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $importType = null;

    #[ORM\Column(nullable: true, options: ['default' => null])]
    private ?int $stringboxesUnits = null;

    #[ORM\Column(nullable: true, options: ['default' => null])]
    private ?int $invertersUnits = null;

    #[ORM\Column(nullable: false, options: ['default' => 0])]
    private ?int $dataDelay = 0;

    #[ORM\Column(nullable: true)]
    private ?bool $useSensorsData = false;

    #[ORM\Column(nullable: true)]
    private ?bool $sensorsInBasics = false;

    #[ORM\Column(nullable: true)]
    private ?bool $sensorsFromSatelite = false;

    #[ORM\Column(nullable: true)]
    private ?bool $usePpcTicketToReplacePvSyst = false;

    #[ORM\Column(nullable: true)]
    private ?bool $activateAnalysis = false;

    #[ORM\Column(nullable: true)]
    private ?bool $stringAnalysis = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $ppcAutoTicketBehavior = "nothing";


    #[ORM\Column(length: 20, nullable: true)]
    private ?string $ppcAutoTicketScope = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $ppcAutoTicketReplaceBy = null;

    #[ORM\Column(nullable: true)]
    private ?bool $ppcAutoTicketUseHour = null;

    #[ORM\Column(nullable: true)]
    private ?bool $ppcAutoTicketReplaceIrr = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $ppcAutoTicketPaBehavior = null;

    public function getActivateAnalysis(): ?bool
    {
        return $this->activateAnalysis;
    }

    public function setActivateAnalysis(?bool $activateAnalysis): void
    {
        $this->activateAnalysis = $activateAnalysis;
    }

    public function getStringAnalysis(): ?bool
    {
        return $this->stringAnalysis;
    }

    public function setStringAnalysis(?bool $stringAnalysis): void
    {
        $this->stringAnalysis = $stringAnalysis;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isDisableDep1(): ?bool
    {
        return $this->disableDep1;
    }

    public function setDisableDep1(bool $disableDep1): void
    {
        $this->disableDep1 = $disableDep1;
    }

    public function isDisableDep2(): ?bool
    {
        return $this->disableDep2;
    }

    public function setDisableDep2(bool $disableDep2): void
    {
        $this->disableDep2 = $disableDep2;
    }

    public function isDisableDep3(): ?bool
    {
        return $this->disableDep3;
    }

    public function setDisableDep3(bool $disableDep3): void
    {
        $this->disableDep3 = $disableDep3;
    }

    public function getEnablePADep0(): ?bool
    {
        return true;
    }


    public function getEnablePADep1(): ?bool
    {
        if ($this->enablePADep1 === null) return true;
        return $this->enablePADep1;
    }

    public function setEnablePADep1(?bool $enablePADep1): void
    {
        $this->enablePADep1 = $enablePADep1;
    }

    public function getEnablePADep2(): ?bool
    {
        if ($this->enablePADep2 === null) return true;
        return $this->enablePADep2;
    }

    public function setEnablePADep2(?bool $enablePADep2): void
    {
        $this->enablePADep2 = $enablePADep2;
    }

    public function getEnablePADep3(): ?bool
    {
        if ($this->enablePADep3 === null) return true;
        return $this->enablePADep3;
    }

    public function setEnablePADep3(?bool $enablePADep3): void
    {
        $this->enablePADep3 = $enablePADep3;
    }


    /**
     * indicateing the default behavior, how data gaps should be handled
     * default: 'available', the other option should be: 'not available'.
     */
    public function getPaDefaultDataGapHandling(): string
    {
        return $this->paDefaultDataGapHandling;
    }

    public function setPaDefaultDataGapHandling(string $paDefaultDataGapHandling): self
    {
        $this->paDefaultDataGapHandling = $paDefaultDataGapHandling;

        return $this;
    }

    public function getEpxCalculationByCurrent(): ?bool
    {
        return $this->epxCalculationByCurrent;
    }

    public function setEpxCalculationByCurrent(?bool $epxCalculationByCurrent): void
    {
        $this->epxCalculationByCurrent = $epxCalculationByCurrent;
    }

    public function isChartAC1(): ?bool
    {

        return $this->chartAC1;
    }

    public function setChartAC1(?bool $chartAC1): self
    {
        $this->chartAC1 = $chartAC1;

        return $this;
    }

    public function isChartAC2(): ?bool
    {
        return $this->chartAC2;
    }

    public function setChartAC2(?bool $chartAC2): self
    {
        $this->chartAC2 = $chartAC2;

        return $this;
    }

    public function isChartAC3(): ?bool
    {
        return $this->chartAC3;
    }

    public function setChartAC3(?bool $chartAC3): self
    {
        $this->chartAC3 = $chartAC3;

        return $this;
    }

    public function isChartAC4(): ?bool
    {
        return $this->chartAC4;
    }

    public function setChartAC4(?bool $chartAC4): self
    {
        $this->chartAC4 = $chartAC4;

        return $this;
    }

    public function isChartAC5(): ?bool
    {
        return $this->chartAC5;
    }

    public function setChartAC5(?bool $chartAC5): self
    {
        $this->chartAC5 = $chartAC5;

        return $this;
    }

    public function isChartAC6(): ?bool
    {
        return $this->chartAC6;
    }

    public function setChartAC6(?bool $chartAC6): self
    {
        $this->chartAC6 = $chartAC6;

        return $this;
    }

    public function isChartAC7(): ?bool
    {
        return $this->chartAC7;
    }

    public function setChartAC7(?bool $chartAC7): self
    {
        $this->chartAC7 = $chartAC7;

        return $this;
    }

    public function isChartAC8(): ?bool
    {
        return $this->chartAC8;
    }

    public function setChartAC8(?bool $chartAC8): self
    {
        $this->chartAC8 = $chartAC8;

        return $this;
    }

    public function isChartAC9(): ?bool
    {
        return $this->chartAC9;
    }

    public function setChartAC9(?bool $chartAC9): self
    {
        $this->chartAC9 = $chartAC9;

        return $this;
    }

    public function isChartDC1(): ?bool
    {
        return $this->chartDC1;
    }

    public function setChartDC1(?bool $chartDC1): self
    {
        $this->chartDC1 = $chartDC1;

        return $this;
    }

    public function isChartDC2(): ?bool
    {
        return $this->chartDC2;
    }

    public function setChartDC2(?bool $chartDC2): self
    {
        $this->chartDC2 = $chartDC2;

        return $this;
    }

    public function isChartDC3(): ?bool
    {
        return $this->chartDC3;
    }

    public function setChartDC3(?bool $chartDC3): self
    {
        $this->chartDC3 = $chartDC3;

        return $this;
    }

    public function isChartDC4(): ?bool
    {
        return $this->chartDC4;
    }

    public function setChartDC4(?bool $chartDC4): self
    {
        $this->chartDC4 = $chartDC4;

        return $this;
    }

    public function isChartDC5(): ?bool
    {
        return $this->chartDC5;
    }

    public function setChartDC5(?bool $chartDC5): self
    {
        $this->chartDC5 = $chartDC5;

        return $this;
    }

    public function isChartDC6(): ?bool
    {
        return $this->chartDC6;
    }

    public function setChartDC6(?bool $chartDC6): self
    {
        $this->chartDC6 = $chartDC6;

        return $this;
    }

    public function isChartAnalyse1(): ?bool
    {
        return $this->chartAnalyse1;
    }

    public function setChartAnalyse1(?bool $chartAnalyse1): self
    {
        $this->chartAnalyse1 = $chartAnalyse1;

        return $this;
    }

    public function isChartAnalyse2(): ?bool
    {
        return $this->chartAnalyse2;
    }

    public function setChartAnalyse2(?bool $chartAnalyse2): self
    {
        $this->chartAnalyse2 = $chartAnalyse2;

        return $this;
    }

    public function isChartAnalyse3(): ?bool
    {
        return $this->chartAnalyse3;
    }

    public function setChartAnalyse3(?bool $chartAnalyse3): self
    {
        $this->chartAnalyse3 = $chartAnalyse3;

        return $this;
    }

    public function isChartAnalyse4(): ?bool
    {
        return $this->chartAnalyse4;
    }
    public function setChartAnalyse4(?bool $chartAnalyse4): self
    {
        $this->chartAnalyse4 = $chartAnalyse4;

        return $this;
    }

    public function isChartAnalyse5(): ?bool
    {
        return $this->chartAnalyse5;
    }

    public function setChartAnalyse5(?bool $chartAnalyse5): self
    {
        $this->chartAnalyse5 = $chartAnalyse5;

        return $this;
    }

    public function isChartAnalyse6(): ?bool
    {
        return $this->chartAnalyse6;
    }

    public function setChartAnalyse6(?bool $chartAnalyse6): self
    {
        $this->chartAnalyse6 = $chartAnalyse6;

        return $this;
    }

    public function isChartAnalyse7(): ?bool
    {
        return $this->chartAnalyse7;
    }

    public function setChartAnalyse7(?bool $chartAnalyse7): self
    {
        $this->chartAnalyse7 = $chartAnalyse7;

        return $this;
    }

    public function isChartAnalyse8(): ?bool
    {
        return $this->chartAnalyse8;
    }

    public function setChartAnalyse8(?bool $chartAnalyse8): self
    {
        $this->chartAnalyse8 = $chartAnalyse8;

        return $this;
    }

    public function isChartAnalyse9(): ?bool
    {
        return $this->chartAnalyse9;
    }

    public function setChartAnalyse9(?bool $chartAnalyse9): self
    {
        $this->chartAnalyse9 = $chartAnalyse9;

        return $this;
    }

    public function isChartAnalyse10(): ?bool
    {
        return $this->chartAnalyse10;
    }

    public function setChartAnalyse10(?bool $chartAnalyse10): self
    {
        $this->chartAnalyse10 = $chartAnalyse10;

        return $this;
    }

    public function isChartAnalyse11(): ?bool
    {
        return $this->chartAnalyse11;
    }

    public function setChartAnalyse11(?bool $chartAnalyse11): self
    {
        $this->chartAnalyse11 = $chartAnalyse11;

        return $this;
    }

    public function isChartAnalyse12(): ?bool
    {
        return $this->chartAnalyse12;
    }

    public function setChartAnalyse12(?bool $chartAnalyse12): self
    {
        $this->chartAnalyse12 = $chartAnalyse12;

        return $this;
    }

    public function isChartCurr1(): ?bool
    {
        return $this->chartCurr1;
    }

    public function setChartCurr1(?bool $chartCurr1): self
    {
        $this->chartCurr1 = $chartCurr1;

        return $this;
    }

    public function isChartCurr2(): ?bool
    {
        return $this->chartCurr2;
    }

    public function setChartCurr2(?bool $chartCurr2): self
    {
        $this->chartCurr2 = $chartCurr2;

        return $this;
    }

    public function isChartCurr3(): ?bool
    {
        return $this->chartCurr3;
    }

    public function setChartCurr3(?bool $chartCurr3): self
    {
        $this->chartCurr3 = $chartCurr3;

        return $this;
    }

    public function isChartVolt1(): ?bool
    {
        return $this->chartVolt1;
    }

    public function setChartVolt1(?bool $chartVolt1): self
    {
        $this->chartVolt1 = $chartVolt1;

        return $this;
    }

    public function isChartVolt2(): ?bool
    {
        return $this->chartVolt2;
    }

    public function setChartVolt2(?bool $chartVolt2): self
    {
        $this->chartVolt2 = $chartVolt2;

        return $this;
    }

    public function isChartVolt3(): ?bool
    {
        return $this->chartVolt3;
    }

    public function setChartVolt3(?bool $chartVolt3): self
    {
        $this->chartVolt3 = $chartVolt3;

        return $this;
    }

    public function isChartSensor1(): ?bool
    {
        return $this->chartSensor1;
    }

    public function setChartSensor1(?bool $chartSensor1): self
    {
        $this->chartSensor1 = $chartSensor1;

        return $this;
    }

    public function isChartSensor2(): ?bool
    {
        return $this->chartSensor2;
    }

    public function setChartSensor2(?bool $chartSensor2): self
    {
        $this->chartSensor2 = $chartSensor2;

        return $this;
    }

    public function isChartSensor3(): ?bool
    {
        return $this->chartSensor3;
    }

    public function setChartSensor3(?bool $chartSensor3): self
    {
        $this->chartSensor3 = $chartSensor3;

        return $this;
    }

    public function isChartSensor4(): ?bool
    {
        return $this->chartSensor4;
    }

    public function setChartSensor4(?bool $chartSensor4): self
    {
        $this->chartSensor4 = $chartSensor4;

        return $this;
    }

    public function isSymfonyImport(): ?bool
    {
        return $this->symfonyImport;
    }

    public function setSymfonyImport(?bool $symfonyImport): self
    {
        $this->symfonyImport = $symfonyImport;

        return $this;
    }

    public function isHuaweiImport(): ?bool
    {
        return $this->symfonyImport;
    }

    public function setHuaweiImport(?bool $huaweiImport): self
    {
        $this->huaweiImport = $huaweiImport;

        return $this;
    }

    public function getApiConfig(): ?string
    {
        return $this->apiConfig;
    }

    public function setApiConfig(?string $apiConfig): static
    {
        $this->apiConfig = $apiConfig;

        return $this;
    }

    public function getImportType(): ?string
    {
        return $this->importType;
    }

    public function setImportType(?string $importType): static
    {
        $this->importType = $importType;

        return $this;
    }

    public function getStringboxesUnits(): ?int
    {
        return $this->stringboxesUnits;
    }

    public function setStringboxesUnits(?int $stringboxesUnits): self
    {
        $this->stringboxesUnits = $stringboxesUnits;

        return $this;
    }

    public function isUseSensorsData(): ?bool
    {
        return $this->useSensorsData;
    }

    public function setUseSensorsData(?bool $useSensorsData): self
    {
        $this->useSensorsData = $useSensorsData;

        return $this;
    }

    public function isSensorsInBasics(): ?bool
    {
        return $this->sensorsInBasics;
    }

    public function setSensorsInBasics(?bool $sensorsInBasics): self
    {
        $this->sensorsInBasics = $sensorsInBasics;

        return $this;
    }


    public function isSensorsFromSatelite(): ?bool
    {
        return $this->sensorsFromSatelite;
    }

    public function setSensorsFromSatelite(?bool $sensorsFromSatelite): self
    {
        $this->sensorsFromSatelite = $sensorsFromSatelite;

        return $this;
    }

    public function getInvertersUnits(): ?int
    {
        return $this->invertersUnits;
    }

    public function setInvertersUnits(?int $invertersUnits): self
    {
        $this->invertersUnits = $invertersUnits;

        return $this;
    }

    public function getDataDelay(): ?int
    {
        return $this->dataDelay;
    }

    public function setDataDelay(?int $dataDelay): self
    {
        $this->dataDelay = $dataDelay;

        return $this;
    }

    public function getUsePpcTicketToReplacePvSyst(): ?bool
    {
        return $this->usePpcTicketToReplacePvSyst;
    }

    public function usePpcTicketToReplacePvSyst(): ?bool
    {
        return $this->usePpcTicketToReplacePvSyst;
    }

    public function setUsePpcTicketToReplacePvSyst(?bool $usePpcTicketToReplacePvSyst): void
    {
        $this->usePpcTicketToReplacePvSyst = $usePpcTicketToReplacePvSyst;
    }

    public function getPpcAutoTicketBehavior(): ?string
    {
        return $this->ppcAutoTicketBehavior;
    }

    public function setPpcAutoTicketBehavior(?string $ppcAutoTicketBehavior): static
    {
        $this->ppcAutoTicketBehavior = $ppcAutoTicketBehavior;

        return $this;
    }

    public function getPpcAutoTicketReplaceBy(): ?string
    {
        return $this->ppcAutoTicketReplaceBy;
    }

    public function setPpcAutoTicketReplaceBy(?string $ppcAutoTicketReplaceBy): static
    {
        $this->ppcAutoTicketReplaceBy = $ppcAutoTicketReplaceBy;

        return $this;
    }

    public function getPpcAutoTicketScope(): ?array
    {
        return explode(", ",$this->ppcAutoTicketScope);
    }

    public function isPpcAutoTicketScope($departement): bool
    {
        return in_array($departement, $this->getPpcAutoTicketScope());
    }
    public function setPpcAutoTicketScope(?array $scope): self
    {
        $this->ppcAutoTicketScope = implode(", ",$scope);

        return $this;
    }

    public function isPpcAutoTicketUseHour(): ?bool
    {
        return $this->ppcAutoTicketUseHour;
    }

    public function setPpcAutoTicketUseHour(?bool $ppcAutoTicketUseHour): static
    {
        $this->ppcAutoTicketUseHour = $ppcAutoTicketUseHour;

        return $this;
    }

    public function isPpcAutoTicketReplaceIrr(): ?bool
    {
        return $this->ppcAutoTicketReplaceIrr;
    }

    public function setPpcAutoTicketReplaceIrr(?bool $ppcAutoTicketReplaceIrr): static
    {
        $this->ppcAutoTicketReplaceIrr = $ppcAutoTicketReplaceIrr;

        return $this;
    }

    public function getPpcAutoTicketPaBehavior(): ?string
    {
        return $this->ppcAutoTicketPaBehavior;
    }

    public function setPpcAutoTicketPaBehavior(?string $ppcAutoTicketPaBehavior): static
    {
        $this->ppcAutoTicketPaBehavior = $ppcAutoTicketPaBehavior;

        return $this;
    }

}
