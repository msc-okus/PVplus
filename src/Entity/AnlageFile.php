<?php

namespace App\Entity;

use App\Repository\AnlageFileRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: AnlageFileRepository::class)]
class AnlageFile
{
    use TimestampableEntity;

    use BlameableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $stamp;

    #[ORM\Column(type: 'string', length: 150)]
    private string $filename;

    #[ORM\Column(type: 'string', length: 255)]
    private string $path;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private string $mimeType;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'anlageFiles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Anlage $plant = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Anlage $anlage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStamp(): ?string
    {
        return $this->stamp;
    }

    public function setStamp(string $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getPlant(): ?Anlage
    {
        return $this->plant;
    }

    public function setPlant(?Anlage $plant): self
    {
        $this->plant = $plant;

        return $this;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $anlage): static
    {
        $this->anlage = $anlage;

        return $this;
    }
}
