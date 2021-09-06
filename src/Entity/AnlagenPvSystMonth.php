<?php

namespace App\Entity;

use App\Repository\PvSystMonthRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=PvSystMonthRepository::class)
 */
class AnlagenPvSystMonth
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
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="anlagenPvSystMonths")
     */
    private ?Anlage $anlage;

    /**
     * @ORM\Column(type="string", length=20)
     * @deprecated
     */
    private string $stamp = '';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $prDesign;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $ertragDesign;

    /**
     * @ORM\Column(type="integer")
     */
    private int $month;

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

    public function getPrDesign(): ?string
    {
        return $this->prDesign;
    }

    public function setPrDesign(string $prDesign): self
    {
        $this->prDesign = $prDesign;

        return $this;
    }

    public function getErtragDesign(): ?string
    {
        return $this->ertragDesign;
    }

    public function setErtragDesign(string $ertragDesign): self
    {
        $this->ertragDesign = $ertragDesign;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(string $month): self
    {
        $this->month = $month;

        return $this;
    }

}
