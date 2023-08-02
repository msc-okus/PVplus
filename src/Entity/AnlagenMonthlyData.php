<?php

namespace App\Entity;

use App\Repository\MonthlyDataRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: MonthlyDataRepository::class)]
class AnlagenMonthlyData
{
    use TimestampableEntity;

    use BlameableEntity;

    use TimestampableEntity;

    use BlameableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'anlagenMonthlyData')]
    private ?Anlage $anlage;

    #[ORM\Column(type: 'string', length: 20)]
    private string $pvSystErtrag;

    #[ORM\Column(type: 'string', length: 20)]
    private string $pvSystPR;

    #[ORM\Column(type: 'string', length: 20)]
    private string $pvSystIrr;

    #[ORM\Column(type: 'string', length: 20)]
    private string $externMeterDataMonth;

    #[ORM\Column(type: 'string', length: 20)]
    private string $irrCorrectedValuMonth;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $tModAvg = '0';

    #[ORM\Column(type: 'integer', length: 255)]
    private int $month;

    #[ORM\Column(type: 'integer', length: 20)]
    private int $year;

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

    public function getPvSystErtrag(): ?float
    {
        return (float) $this->pvSystErtrag;
    }

    public function setPvSystErtrag(string $pvSystErtrag): self
    {
        $this->pvSystErtrag = str_replace(',', '.', $pvSystErtrag);

        return $this;
    }

    public function getPvSystPR(): ?float
    {
        return (float) $this->pvSystPR;
    }

    public function setPvSystPR(string $pvSystPR): self
    {
        $this->pvSystPR = str_replace(',', '.', $pvSystPR);

        return $this;
    }

    public function getPvSystIrr(): ?float
    {
        return (float) $this->pvSystIrr;
    }

    public function setPvSystIrr(string $pvSystIrr): self
    {
        $this->pvSystIrr = str_replace(',', '.', $pvSystIrr);

        return $this;
    }

    public function getExternMeterDataMonth(): ?float
    {
        return (float) $this->externMeterDataMonth;
    }

    public function setExternMeterDataMonth(string $externMeterDataMonth): self
    {
        $this->externMeterDataMonth = str_replace(',', '.', $externMeterDataMonth);

        return $this;
    }

    public function getIrrCorrectedValuMonth(): ?float
    {
        return (float) $this->irrCorrectedValuMonth;
    }

    public function setIrrCorrectedValuMonth(string $irrCorrectedValuMonth): self
    {
        $this->irrCorrectedValuMonth = str_replace(',', '.', $irrCorrectedValuMonth);

        return $this;
    }

    public function getTModAvg(): float
    {
        return $this->tModAvg === null ? 0 : (float)$this->tModAvg;
    }

    public function setTModAvg(string|float $tModAvg): void
    {
        $this->tModAvg = $tModAvg;
    }


    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(string $month): self
    {
        $this->month = $month;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(string $year): self
    {
        $this->year = $year;

        return $this;
    }
}
