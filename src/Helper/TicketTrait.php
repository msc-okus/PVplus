<?php

namespace App\Helper;

use DateTimeInterface;

trait TicketTrait
{
    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTimeInterface $begin;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTimeInterface $end;


    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $errorType;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $freeText = "";

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description = "";

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $systemStatus;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $priority;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $answer = "";

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $inverter;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $alertType = "";

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private string $dataGapEvaluation;

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?DateTimeInterface $End): self
    {
        $this->end = $End;

        return $this;
    }

    public function getBegin(): ?DateTimeInterface
    {
        return $this->begin;
    }

    public function setBegin(?DateTimeInterface $Begin): self
    {
        $this->begin = $Begin;

        return $this;
    }
    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $Status): self
    {
        $this->status = $Status;

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

    public function getDataGapEvaluation(): string
    {
        return $this->dataGapEvaluation;
    }

    public function setDataGapEvaluation(string $dataGapEvaluation): self
    {
        $this->dataGapEvaluation = $dataGapEvaluation;

        return $this;
    }
}