<?php

namespace App\Entity;

use App\Repository\ReportsRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ReportsRepository::class)
 */
class AnlagenReports
{
    use TimestampableEntity;
    use BlameableEntity;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $reportType;

    /**
     * @ORM\Column(type="date")
     */
    private $startDate;

    /**
     * @ORM\Column(type="date")
     */
    private $endDate;

    /**
     * @ORM\Column(type="text")
     */
    private string $rawReport;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="anlagenReports")
     */
    private ?Anlage $anlage;

    /**
     * @ORM\ManyToOne(targetEntity=Eigner::class, inversedBy="anlagenReports")
     */
    private ?Eigner $eigner;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private ?string $month;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private ?string $year;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private string $contentArray;

    /**
     * @ORM\Column(type="integer")
     */
    private int $reportStatus = 10;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReportType(): ?string
    {
        return $this->reportType;
    }

    public function setReportType(string $reportType): self
    {
        $this->reportType = $reportType;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getRawReport(): ?string
    {
        return $this->rawReport;
    }

    public function setRawReport(string $rawReport): self
    {
        $this->rawReport = $rawReport;

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

    public function getEigner(): ?Eigner
    {
        return $this->eigner;
    }

    public function setEigner(?Eigner $eigner): self
    {
        $this->eigner = $eigner;

        return $this;
    }

    public function getMonth(): ?string
    {
        return $this->month;
    }

    public function setMonth(?string $month): self
    {
        $this->month = $month;

        return $this;
    }
    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(?string $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getContentArray(): ?array
    {
        return unserialize($this->contentArray);
    }

    public function setContentArray(?array $contentArray): self
    {
        $this->contentArray = serialize($contentArray);

        return $this;
    }

    public function getReportStatus(): ?int
    {
        return $this->reportStatus;
    }

    public function setReportStatus(int $status): self
    {
        $this->reportStatus = $status;

        return $this;
    }
}
