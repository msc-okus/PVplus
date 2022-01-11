<?php

namespace App\Entity;

use App\Repository\Case6Repository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=Case6Repository::class)
 * @ORM\Table(indexes={@ORM\Index(columns={"stamp_from"}), @ORM\Index(columns={"stamp_to"}), @ORM\Index(columns={"inverter"})}, uniqueConstraints={@ORM\UniqueConstraint(name="uniqueCase6", columns={"anlage_id", "stamp_from", "stamp_to", "inverter"})})
 * @UniqueEntity(
 *     fields={"anlage", "stampFrom", "stampTo", "inverter"},
 *     errorPath="inverter",
 *     message="This Inverter at this time has already a case6."
 * )
 */
class AnlageCase6
{

    /**
     * @Groups({"case6"})
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="anlageCase6s")
     */
    private ?Anlage $anlage;

    /**
     * @Groups ({"case6"})
     * @ORM\Column(type="string", length=20)
     */
    private string $stampFrom;

    /**
     * @Groups ({"case6"})
     * @ORM\Column(type="string", length=20)
     */
    private string $stampTo;

    /**
     * @Groups ({"case6"})
     * @ORM\Column(type="string", length=100)
     */
    private string $inverter;

    /**
     * @Groups ({"case6"})
     * @ORM\Column(type="text", nullable=true)
     */
    private string $reason;

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

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

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

        return $answer;
    }

}
