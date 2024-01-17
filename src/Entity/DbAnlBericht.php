<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;

/**
 * DbAnlBericht.
 */
#[ORM\Table(name: 'db_anl_bericht')]
#[ORM\Index(name: 'wert_create_date', columns: ['report_create_date'])]
#[ORM\Entity]
#[Deprecated]
class DbAnlBericht
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'report_id', type: 'bigint', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $reportId;

    #[ORM\Column(name: 'eigner_id', type: 'string', length: 25, nullable: false)]
    private ?string $eignerId = null;

    #[ORM\Column(name: 'anl_id', type: 'string', length: 50, nullable: false)]
    private ?string $anlId = null;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'report_create_date', type: 'datetime', nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private $reportCreateDate = 'CURRENT_TIMESTAMP';

    #[ORM\Column(name: 'report_ist', type: 'string', length: 10, nullable: false)]
    private ?string $reportIst = null;

    #[ORM\Column(name: 'report_kw', type: 'string', length: 10, nullable: false)]
    private ?string $reportKw = null;

    #[ORM\Column(name: 'report_month', type: 'string', length: 10, nullable: false)]
    private ?string $reportMonth = null;

    #[ORM\Column(name: 'report_year', type: 'string', length: 10, nullable: false)]
    private ?string $reportYear = null;

    #[ORM\Column(name: 'report_code', type: 'text', length: 0, nullable: false)]
    private ?string $reportCode = null;

    public function getReportId(): ?string
    {
        return $this->reportId;
    }

    public function getEignerId(): ?string
    {
        return $this->eignerId;
    }

    public function setEignerId(string $eignerId): self
    {
        $this->eignerId = $eignerId;

        return $this;
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

    public function getReportCreateDate(): ?\DateTimeInterface
    {
        return $this->reportCreateDate;
    }

    public function setReportCreateDate(\DateTimeInterface $reportCreateDate): self
    {
        $this->reportCreateDate = $reportCreateDate;

        return $this;
    }

    public function getReportIst(): ?string
    {
        return $this->reportIst;
    }

    public function setReportIst(string $reportIst): self
    {
        $this->reportIst = $reportIst;

        return $this;
    }

    public function getReportKw(): ?string
    {
        return $this->reportKw;
    }

    public function setReportKw(string $reportKw): self
    {
        $this->reportKw = $reportKw;

        return $this;
    }

    public function getReportMonth(): ?string
    {
        return $this->reportMonth;
    }

    public function setReportMonth(string $reportMonth): self
    {
        $this->reportMonth = $reportMonth;

        return $this;
    }

    public function getReportYear(): ?string
    {
        return $this->reportYear;
    }

    public function setReportYear(string $reportYear): self
    {
        $this->reportYear = $reportYear;

        return $this;
    }

    public function getReportCode(): ?string
    {
        return $this->reportCode;
    }

    public function setReportCode(string $reportCode): self
    {
        $this->reportCode = $reportCode;

        return $this;
    }
}
