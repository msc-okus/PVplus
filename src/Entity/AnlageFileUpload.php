<?php

namespace App\Entity;

use App\Repository\AnlageFileUploadRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Service\UploaderHelper;

/**
 * @ORM\Entity(repositoryClass=AnlageFileUploadRepository::class)
 */
class AnlageFileUpload
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $stamp;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $upload_path;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $mime_type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $original_file_name;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $plant_id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_ad;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $created_by;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $updated_by;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStamp(): ?\DateTimeInterface
    {
        return $this->stamp;
    }

    public function setStamp(\DateTimeInterface $stamp): self
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

    public function getUploadPath(): ?string
    {
        return $this->upload_path.'/'.$this->getFilename();
    }

    public function setUploadPath(string $upload_path): self
    {
        $this->upload_path = $upload_path;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    public function setMimeType(string $mime_type): self
    {
        $this->mime_type = $mime_type;

        return $this;
    }

    public function getOriginalFileName(): ?string
    {
        return $this->original_file_name;
    }

    public function setOriginalFileName(string $original_file_name): self
    {
        $this->original_file_name = $original_file_name;

        return $this;
    }

    public function getPlantId(): ?Anlage
    {
        return $this->plant_id;
    }

    public function setPlantId(?Anlage $plant_id): self
    {
        $this->plant_id = $plant_id;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAd(): ?\DateTimeInterface
    {
        return $this->updated_ad;
    }

    public function setUpdatedAd(?\DateTimeInterface $updated_ad): self
    {
        $this->updated_ad = $updated_ad;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->created_by;
    }

    public function setCreatedBy(string $created_by): self
    {
        $this->created_by = $created_by;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updated_by;
    }

    public function setUpdatedBy(?string $updated_by): self
    {
        $this->updated_by = $updated_by;

        return $this;
    }
}
