<?php

namespace App\Helper;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

trait TicketTrait
{
    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $begin;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $end;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $errorType = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $freeText = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = '';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $systemStatus = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $priority = 0;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $answer = '';

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private string $inverter;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $alertType = '';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $status;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $dataGapEvaluation;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $intervals = 0;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $kpiPaDep1 = '';

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $kpiPaDep2 = '';

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $kpiPaDep3 = '';

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?DateTimeInterface $End): self
    {
        $this->end = $End;

        if (isset($this->end) && isset($this->begin)) {
            $endstamp = $this->getEnd()->getTimestamp();
            $beginstamp = $this->getBegin()->getTimestamp();
            $this->intervals = (int)(($endstamp - $beginstamp) / 900);
        }

        return $this;
    }

    public function getBegin(): ?DateTimeInterface
    {
        return $this->begin;
    }

    public function setBegin(?DateTimeInterface $Begin): self
    {
        $this->begin = $Begin;

        if (isset($this->end) && isset($this->begin)) {
            $endstamp = $this->getEnd()->getTimestamp();
            $beginstamp = $this->getBegin()->getTimestamp();
            $this->intervals = (int)(($endstamp - $beginstamp) / 900);
        }

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFreeText(): ?string
    {
        return $this->freeText;
    }

    public function setFreeText(?string $freeText): self
    {
        $this->freeText = $freeText;

        return $this;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function setErrorType(?string $errorType): self
    {
        $this->errorType = $errorType;

        return $this;
    }

    public function getInverter(): string
    {
        return $this->inverter;
    }

    public function setInverter(string $Inverter): self
    {
        $this->inverter = $Inverter;

        return $this;
    }

    public function getAlertType(): ?string
    {
        return $this->alertType;
    }

    public function setAlertType(string $alertType): self
    {
        $this->alertType = $alertType;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $Priority): self
    {
        $this->priority = $Priority;

        return $this;
    }

    public function getSystemStatus(): ?int
    {
        return $this->systemStatus;
    }

    public function setSystemStatus(?int $systemStatus): self
    {
        $this->systemStatus = $systemStatus;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $Description): self
    {
        $this->description = $Description;

        return $this;
    }

    public function getDataGapEvaluation(): ?string
    {
        return $this->dataGapEvaluation;
    }

    public function setDataGapEvaluation(?string $dataGapEvaluation): self
    {
        $this->dataGapEvaluation = $dataGapEvaluation;

        return $this;
    }

    public function getIntervals(): ?int
    {
        return $this->intervals;
    }

    public function setIntervals(int $intervals): self
    {
        $this->intervals = $intervals;

        return $this;
    }

    public function getKpiPaDep1(): string
    {
        return $this->kpiPaDep1;
    }

    public function setKpiPaDep1(string $kpiPaDep1): self
    {
        $this->kpiPaDep1 = $kpiPaDep1;
        return $this;
    }

    public function getKpiPaDep2(): string
    {
        return $this->kpiPaDep2;
    }

    public function setKpiPaDep2(string $kpiPaDep2): self
    {
        $this->kpiPaDep2 = $kpiPaDep2;
        return $this;
    }

    public function getKpiPaDep3(): string
    {
        return $this->kpiPaDep3;
    }

    public function setKpiPaDep3(string $kpiPaDep3): self
    {
        $this->kpiPaDep3 = $kpiPaDep3;
        return $this;
    }

}
