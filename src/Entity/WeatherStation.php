<?php

namespace App\Entity;

use App\Repository\WeatherStationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity('databaseIdent')]
#[ORM\Entity(repositoryClass: WeatherStationRepository::class)]
class WeatherStation
{
    use TimestampableEntity;

    use BlameableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $databaseIdent;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $databaseStationIdent = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $location = "";

    #[ORM\Column(type: 'text')]
    private ?string $description = "";

    #[ORM\Column(type: 'boolean')]
    private ?bool $hasUpper = true;

    #[ORM\Column(type: 'boolean')]
    private ?bool $hasLower = false;

    #[ORM\Column(type: 'boolean')]
    private ?bool $changeSensor = false;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $timeZoneWeatherStation = '0';

    #[ORM\OneToMany(mappedBy: 'weatherStation', targetEntity: Anlage::class)]
    private Collection $anlagen;

    #[ORM\Column(type: 'boolean')]
    private bool $hasPannelTemp = true;

    #[ORM\Column(type: 'boolean')]
    private bool $hasHorizontal = false;

    #[ORM\OneToMany(mappedBy: 'weatherStation', targetEntity: AnlageAcGroups::class)]
    private Collection $anlageAcGroups;

    #[ORM\Column(type: 'string', length: 40)]
    private ?string $labelUpper = "";

    #[ORM\Column(type: 'string', length: 40)]
    private ?string $labelLower = "";

    #[ORM\Column(type: 'string', length: 40)]
    private ?string $labelHorizontal = "";

    #[ORM\Column(type: 'string', length: 40)]
    private ?string $geoLat = '0';

    #[ORM\Column(type: 'string', length: 40)]
    private ?string $geoLon = '0';

    public function __construct()
    {
        $this->anlagen = new ArrayCollection();
        $this->anlageAcGroups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDatabaseIdent(): ?string
    {
        return $this->databaseIdent;
    }

    public function setDatabaseIdent(string $databaseIdent): self
    {
        $this->databaseIdent = $databaseIdent;

        return $this;
    }

    public function getDatabaseStationIdent(): string
    {
        return is_null($this->databaseStationIdent) ? '' : $this->databaseStationIdent;
    }

    public function setDatabaseStationIdent(string $databaseStationIdent): void
    {
        $this->databaseStationIdent = $databaseStationIdent;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

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

    public function getHasUpper(): ?bool
    {
        return $this->hasUpper;
    }

    public function setHasUpper(bool $hasUpper): self
    {
        $this->hasUpper = $hasUpper;

        return $this;
    }

    public function getHasLower(): ?bool
    {
        return $this->hasLower;
    }

    public function setHasLower(bool $hasLower): self
    {
        $this->hasLower = $hasLower;

        return $this;
    }

    public function getChangeSensor(): ?bool
    {
        return $this->changeSensor;
    }

    public function setChangeSensor(bool $changeSensor): self
    {
        $this->changeSensor = $changeSensor;

        return $this;
    }

    public function gettimeZoneWeatherStation(): ?string
    {
        return $this->timeZoneWeatherStation;
    }

    public function settimeZoneWeatherStation(string $timeZoneWeatherStation): self
    {
        $this->timeZoneWeatherStation = $timeZoneWeatherStation;

        return $this;
    }

    public function getDbNameWeather(): string
    {
        return 'db__pv_ws_'.$this->getDatabaseIdent();
    }

    /**
     * @return Collection|Anlage[]
     */
    public function getAnlagen(): Collection
    {
        return $this->anlagen;
    }

    public function addAnlagen(Anlage $anlagen): self
    {
        if (!$this->anlagen->contains($anlagen)) {
            $this->anlagen[] = $anlagen;
            $anlagen->setWeatherStation($this);
        }

        return $this;
    }

    public function removeAnlagen(Anlage $anlagen): self
    {
        if ($this->anlagen->removeElement($anlagen)) {
            // set the owning side to null (unless already changed)
            if ($anlagen->getWeatherStation() === $this) {
                $anlagen->setWeatherStation(null);
            }
        }

        return $this;
    }

    public function getHasPannelTemp(): ?bool
    {
        return $this->hasPannelTemp;
    }

    public function setHasPannelTemp(bool $hasPannelTemp): self
    {
        $this->hasPannelTemp = $hasPannelTemp;

        return $this;
    }

    public function getHasHorizontal(): ?bool
    {
        return $this->hasHorizontal;
    }

    public function setHasHorizontal(bool $hasHorizontal): self
    {
        $this->hasHorizontal = $hasHorizontal;

        return $this;
    }

    /**
     * @return Collection|AnlageAcGroups[]
     */
    public function getAnlageAcGroups(): Collection
    {
        return $this->anlageAcGroups;
    }

    public function addAnlageAcGroup(AnlageAcGroups $anlageAcGroup): self
    {
        if (!$this->anlageAcGroups->contains($anlageAcGroup)) {
            $this->anlageAcGroups[] = $anlageAcGroup;
            $anlageAcGroup->setWeatherStation($this);
        }

        return $this;
    }

    public function removeAnlageAcGroup(AnlageAcGroups $anlageAcGroup): self
    {
        if ($this->anlageAcGroups->removeElement($anlageAcGroup)) {
            // set the owning side to null (unless already changed)
            if ($anlageAcGroup->getWeatherStation() === $this) {
                $anlageAcGroup->setWeatherStation(null);
            }
        }

        return $this;
    }

    public function getLabelUpper(): ?string
    {
        return $this->labelUpper;
    }

    public function setLabelUpper(string $labelUpper): self
    {
        $this->labelUpper = $labelUpper;

        return $this;
    }

    public function getLabelLower(): ?string
    {
        return $this->labelLower;
    }

    public function setLabelLower(string $labelLower): self
    {
        $this->labelLower = $labelLower;

        return $this;
    }

    public function getLabelHorizontal(): ?string
    {
        return $this->labelHorizontal;
    }

    public function setLabelHorizontal(string $labelHorizontal): self
    {
        $this->labelHorizontal = $labelHorizontal;

        return $this;
    }

    public function getGeoLat(): ?string
    {
        return $this->geoLat;
    }

    public function setGeoLat(?string $geoLat): self
    {
        $this->geoLat = $geoLat;

        return $this;
    }

    public function getGeoLon(): ?string
    {
        return $this->geoLon;
    }

    public function setGeoLon(?string $geoLon): self
    {
        $this->geoLon = $geoLon;

        return $this;
    }

}
