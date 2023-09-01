<?php
/*
 * MS 08/2023
*/

namespace App\Entity;

use App\Repository\AnlageSunShadingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
//use phpDocumentor\Reflection\Types\Collection;


#[ORM\Entity(repositoryClass: AnlageSunShadingRepository::class)]
class AnlageSunShading {
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'anlageSunShading')]
    private $anlage;

    #[ORM\OneToOne(targetEntity: AnlageModulesDB::class, inversedBy:"modulesDBData", cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'modules_db_id', referencedColumnName: 'id', nullable: false)]
    private AnlageModulesDB $modulesDB;

    #[ORM\Column(length: 120)]
    private string $description;
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


    /**
     * MS 08/2023 AnlageModulesDB
     *
     * return AnlageModulesDB
     */

    public function getModulesDB()
    {
       return $this->modulesDB;
    }
    public function setModulesDB($modulesDB)
    {
        $this->modulesDB = $modulesDB;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

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

}
