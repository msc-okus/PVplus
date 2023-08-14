<?php
/*
 * MS 08/2023
*/

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AnlageSunShadingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnlageSunShadingRepository::class)]
#[ApiResource]
class AnlageSunShading
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'sunShadingData')]
    private $anlage;

    #[ORM\Column(length: 20)]
    private ?string $mod_height = null;

    #[ORM\Column(length: 20)]
    private ?string $mod_width = null;

    #[ORM\Column(length: 20)]
    private ?string $mod_tilt = null;

    #[ORM\Column(length: 20)]
    private ?string $mod_table_height = null;

    #[ORM\Column(length: 20)]
    private ?string $mod_table_distance = null;

    #[ORM\Column(length: 20)]
    private ?string $distance_a = null;

    #[ORM\Column(length: 20)]
    private ?string $distance_b = null;

    #[ORM\Column(length: 20)]
    private ?string $ground_slope = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $update_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnlageId(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlageId(?Anlage $anlage): self
    {
        $this->anlage = $anlage;
        return $this;
    }

    public function getModHeight(): ?string
    {
        return $this->mod_height;
    }

    public function setModHeight(string $mod_height): static
    {
        $this->mod_height = $mod_height;

        return $this;
    }

    public function getModWidth(): ?string
    {
        return $this->mod_width;
    }

    public function setModWidth(string $mod_width): static
    {
        $this->mod_width = $mod_width;

        return $this;
    }

    public function getModTilt(): ?string
    {
        return $this->mod_tilt;
    }

    public function setModTilt(string $mod_tilt): static
    {
        $this->mod_tilt = $mod_tilt;

        return $this;
    }

    public function getModTableHeight(): ?string
    {
        return $this->mod_table_height;
    }

    public function setModTableHeight(string $mod_table_height): static
    {
        $this->mod_table_height = $mod_table_height;

        return $this;
    }

    public function getModTableDistance(): ?string
    {
        return $this->mod_table_distance;
    }

    public function setModTableDistance(string $mod_table_distance): static
    {
        $this->mod_table_distance = $mod_table_distance;

        return $this;
    }

    public function getDistanceA(): ?string
    {
        return $this->distance_a;
    }

    public function setDistanceA(string $distance_a): static
    {
        $this->distance_a = $distance_a;

        return $this;
    }

    public function getDistanceB(): ?string
    {
        return $this->distance_b;
    }

    public function setDistanceB(string $distance_b): static
    {
        $this->distance_b = $distance_b;

        return $this;
    }

    public function getGroundSlope(): ?string
    {
        return $this->ground_slope;
    }

    public function setGroundSlope(string $ground_slope): static
    {
        $this->ground_slope = $ground_slope;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->update_at;
    }

    public function setUpdateAt(\DateTimeImmutable $update_at): static
    {
        $this->update_at = $update_at;

        return $this;
    }
}
