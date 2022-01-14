<?php

namespace App\Entity;

use App\Repository\Case6DraftRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=Case6DraftRepository::class)
 */
class Case6Draft
{
    use TimestampableEntity;
    use BlameableEntity;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $anlage;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $stampFrom;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $stampTo;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $inverter;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $reason;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $error;


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

    public function getStampFrom(): ?string
    {
        return $this->stampFrom;
    }

    public function setStampFrom(string $stampFrom): self
    {
        $this->stampFrom = $stampFrom;

        return $this;
    }

    public function getStampTo(): ?string
    {
        return $this->stampTo;
    }

    public function setStampTo(string $stampTo): self
    {
        $this->stampTo = $stampTo;

        return $this;
    }

    public function getInverter(): ?string
    {
        return $this->inverter;
    }

    public function setInverter(string $inverter): self
    {
        $this->inverter = $inverter;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
    public function check(): string
    {
        $nrInv = $this->anlage->getAnzInverterFromGroupsAC();
        $answer = "";
        if(strtotime($this->stampFrom) > strtotime($this->stampTo))
            $answer = $answer." Date inconsistent; ";
        if(strtotime($this->stampFrom) > strtotime('now') or (strtotime($this->stampTo) > strtotime('now')))
            $answer = $answer." Date in the future; ";
        if((int)$this->inverter > $nrInv)
            $answer = $answer." Inverter not in the plant";

        if(date('i', strtotime($this->stampFrom)) != "00" && date('i', strtotime($this->stampFrom)) != "15" && date('i', strtotime($this->stampFrom)) != "30" && date('i', strtotime($this->stampFrom)) != "45")
            $answer = $answer." stampFrom minutes must be 00, 15, 30, 45;";
        if(date('i', strtotime($this->stampFrom)) != "00" && date('i', strtotime($this->stampFrom)) != "15" && date('i', strtotime($this->stampFrom)) != "30" && date('i', strtotime($this->stampFrom)) != "45")
            $answer = $answer." stampTo minutes must be 00, 15, 30, 45";
        return $answer;
    }
}
