<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Repository\ReportsRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * @ORM\Entity(repositoryClass=ReportsRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="anlagen_reports")
 * @ApiResource(
 *     shortName="reports",
 *     normalizationContext={"groups"={"main:read"}},
 *     denormalizationContext={"groups"={"main:write"}},
 *     attributes={
 *          "formats"={"jsonld", "json", "html", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(NumericFilter::class, properties={"reportStatus"})
 * @ApiFilter(SearchFilter::class, properties={"reportType": "partial"})
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
     * @Groups({"main:read"})
     */
    private string $reportType;

    /**
     * Indicats wich version of Report.
     * Depending on this information we have to decide wich function to use for PDF and Excel files.
     *
     * @ORM\Column(type="integer")
     */
    private int $reportTypeVersion = 0;

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
     * @Groups({"main:read"})
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

    public function getReportTypeVersion(): int
    {
        return $this->reportTypeVersion;
    }

    public function setReportTypeVersion(int $reportTypeVersion): self
    {
        $this->reportTypeVersion = $reportTypeVersion;
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
