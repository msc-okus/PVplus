<?php

namespace App\Entity;

use App\Repository\AnlageLegendReportRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=AnlageLegendReportRepository::class)
 */
class AnlageLegendReport
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="anlageLegendReports")
     */
    private ?Anlage $anlage;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $type;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $row;

    /**
     * @Groups ({"legend"})
     * @ORM\Column(type="string", length=50)
     */
    private string $title;

    /**
     * @Groups ({"legend"})
     * @ORM\Column(type="string", length=10)
     */
    private string $unit;

    /**
     * @Groups ({"legend"})
     * @ORM\Column(type="string", length=255)
     */
    private string $description;

    /**
     * @Groups ({"legend"})
     * @ORM\Column(type="string", length=50)
     */
    private string $source;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRow(): ?string
    {
        return $this->row;
    }

    public function setRow(string $row): self
    {
        $this->row = $row;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

}
