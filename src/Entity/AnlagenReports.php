<?php

namespace App\Entity;

use App\Repository\ReportsRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * ApiResource(
 *      security="is_granted('ROLE_ADMIN')",
 *      securityMessage="Only Admin can access to this page",
 *     collectionOperations={"get", "post"},
 *     itemOperations={"get","put"},
 *     shortName="reports",
 *     normalizationContext={"groups"={"main:read"}},
 *     denormalizationContext={"groups"={"main:write"}},
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "json", "html", "csv"={"text/csv"}}
 *     }
 * )
 * ApiFilter(SearchFilter::class, properties={"reportType":"partial"})
 * ApiFilter(NumericFilter::class, properties={"reportStatus"})
 *
 */

#[ORM\Entity(repositoryClass: ReportsRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'anlagen_reports')]
class AnlagenReports
{
    use TimestampableEntity;
    use BlameableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[Groups(['main:read'])]
    #[ORM\Column(type: 'string', length: 50)]
    private string $reportType;

    /**
     * Indicats wich version of Report.
     * Depending on this information we have to decide wich function to use for PDF and Excel files.
     */
    #[ORM\Column(type: 'integer')]
    #[Deprecated]
    private int $reportTypeVersion = 0;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $endDate;

    #[ORM\Column(type: 'text')]
    private string $rawReport;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'anlagenReports')]
    private ?Anlage $anlage = null;

    #[ORM\ManyToOne(targetEntity: Eigner::class, inversedBy: 'anlagenReports')]
    private ?Eigner $eigner = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $month = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $year = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private string $contentArray;

    #[Groups(['main:read'])]
    #[ORM\Column(type: 'integer')]
    private int $reportStatus = 10;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $headline = "";

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $file = "";

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $PdfParts = null;

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
        return unserialize($this->contentArray, ["allowed_classes" => true]);
    }
    public function getContentArrayForExcel(): ?array{
        return unserialize($this->contentArray, ["allowed_classes" => false]);
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

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getHeadline(): ?string
    {
        return $this->headline;
    }

    public function setHeadline(string $headline): void
    {
        $this->headline = $headline;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getPdfParts(): ?Array
    {
        return unserialize($this->PdfParts);
    }

    public function setPdfParts(Array $PdfParts): self
    {
        $this->PdfParts = serialize($PdfParts);

        return $this;
    }
}
