<?php

namespace App\Entity;

use App\Repository\PVSystDatenRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=PVSystDatenRepository::class)
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="uniquePvSyst", columns={"anlage_id", "stamp"})}, indexes={@ORM\Index(name="stamp", columns={"stamp"})})
 */
class AnlagePVSystDaten
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    use TimestampableEntity;
    use BlameableEntity;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="anlagePVSystDatens")
     */
    private $anlage;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $stamp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrGlobalHor;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrGlobalInc;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $tempAmbiant;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $electricityGrid;

    /**
     * @ORM\Column(type="string", length=20)
     */
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
        $this->irrGlobalHor = $irrGlobalHor;

        return $this;
    }

    public function getIrrGlobalInc(): ?string
    {
        return $this->irrGlobalInc;
    }

    public function setIrrGlobalInc(string $irrGlobalInc): self
    {
        $this->irrGlobalInc = $irrGlobalInc;

        return $this;
    }

    public function getTempAmbiant(): ?string
    {
        return $this->tempAmbiant;
    }

    public function setTempAmbiant(string $tempAmbiant): self
    {
        $this->tempAmbiant = $tempAmbiant;

        return $this;
    }

    public function getElectricityGrid(): ?string
    {
        return $this->electricityGrid;
    }

    public function setElectricityGrid(string $electricityGrid): self
    {
        $this->electricityGrid = $electricityGrid;

        return $this;
    }

    public function getElectricityInverterOut(): ?string
    {
        return $this->electricityInverterOut;
    }

    public function setElectricityInverterOut(string $electricityInverterOut): self
    {
        $this->electricityInverterOut = $electricityInverterOut;

        return $this;
    }
}
