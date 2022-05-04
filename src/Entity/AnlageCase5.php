<?php

namespace App\Entity;

use App\Repository\Case5Repository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=Case5Repository::class)
 * @ORM\Table(indexes={@ORM\Index(columns={"stamp_from"}), @ORM\Index(columns={"stamp_to"}), @ORM\Index(columns={"inverter"})}, uniqueConstraints={@ORM\UniqueConstraint(name="uniqueCase5", columns={"anlage_id", "stamp_from", "stamp_to", "inverter"})})
 */
#[UniqueEntity(fields: ['anlage', 'stampFrom', 'stampTo', 'inverter'], errorPath: 'inverter', message: 'This Inverter at this time has already a case5.')]
class AnlageCase5
{
    use TimestampableEntity;
    use BlameableEntity;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(['case5'])]
    private int $id;
    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="anlageCase5s")
     */
    private ?Anlage $anlage;
    /**
     * @ORM\Column(type="string", length=20)
     */
    #[Groups(['case5'])]
    private string $stampFrom;
    /**
     * @ORM\Column(type="string", length=20)
     */
    #[Groups(['case5'])]
    private string $stampTo;
    /**
     * @ORM\Column(type="string", length=100)
     */
    #[Groups(['case5'])]
    private string $inverter;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(['case5'])]
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
}
