<?php

namespace App\Entity;

use App\Repository\GridMeterDayRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(indexes={@ORM\Index(name="stamp", columns={"stamp"})}, uniqueConstraints={@ORM\UniqueConstraint(name="unique_key", columns={"stamp", "anlage_id"})})
 * @ORM\Entity(repositoryClass=GridMeterDayRepository::class)
 */
class AnlageGridMeterDay
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="anlageGridMeterDays")
     */
    private $anlage;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $stamp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $gridMeterValue;

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

    public function getStamp(): ?string
    {
        return $this->stamp;
    }

    public function setStamp(string $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    public function getGridMeterValue(): ?string
    {
        return $this->gridMeterValue;
    }

    public function setGridMeterValue(string $gridMeterValue): self
    {
        $this->gridMeterValue = $gridMeterValue;

        return $this;
    }
}
