<?php

namespace App\Entity;

use App\Repository\PVSystDatenRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: PVSystDatenRepository::class)]
#[ORM\Table]
#[ORM\UniqueConstraint(name: 'uniquePvSyst', columns: ['anlage_id', 'stamp'])]
#[ORM\Index(columns: ['stamp'], name: 'stamp')]
class AnlagePVSystDaten
{
    use TimestampableEntity;
    use BlameableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'anlagePVSystDatens')]
    private Anlage $anlage;

    #[ORM\Column(type: 'string', length: 20)]
    private string $stamp;

    #[ORM\Column(type: 'string', length: 20)]
    private string $irrGlobalHor;

    #[ORM\Column(type: 'string', length: 20)]
    private string $irrGlobalInc;

    #[ORM\Column(type: 'string', length: 20)]
    private string $irrGlobalEff;

    #[ORM\Column(type: 'string', length: 20)]
    private string $electricityGrid;

    #[ORM\Column(type: 'string', length: 20)]
    private string $electricityInverterOut;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnlage(): ?anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?anlage $anlage): self
    {
        $this->anlage = $anlage;

        return $this;
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

    public function getIrrGlobalHor(): ?string
    {
        return $this->irrGlobalHor;
    }

    public function setIrrGlobalHor(string $irrGlobalHor): self
    {
        $this->irrGlobalHor = str_replace(',', '.',$irrGlobalHor);

        return $this;
    }

    public function getIrrGlobalInc(): ?string
    {
        return $this->irrGlobalInc;
    }

    public function setIrrGlobalInc(string $irrGlobalInc): self
    {
        $this->irrGlobalInc = str_replace(',', '.',$irrGlobalInc);

        return $this;
    }

    public function getIrrGlobalEff(): ?string
    {
        return $this->irrGlobalEff;
    }

    public function setIrrGlobalEff(string $irrGlobalEff): self
    {
        $this->irrGlobalEff = str_replace(',', '.',$irrGlobalEff);

        return $this;
    }

    public function getElectricityGrid(): ?float
    {
        return (float)$this->electricityGrid;
    }

    public function setElectricityGrid(string $electricityGrid): self
    {
        $this->electricityGrid = str_replace(',', '.',$electricityGrid);

        return $this;
    }

    public function getElectricityInverterOut(): ?float
    {
        return (float)$this->electricityInverterOut;
    }

    public function setElectricityInverterOut(string $electricityInverterOut): self
    {
        $this->electricityInverterOut = str_replace(',', '.',$electricityInverterOut);

        return $this;
    }
}
