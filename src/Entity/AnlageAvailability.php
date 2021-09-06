<?php

namespace App\Entity;

use App\Repository\AnlageAvailabilityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="pvp_anlage_availability", indexes={@ORM\Index(name="stamp", columns={"stamp"}), @ORM\Index(name="inverter", columns={"inverter"})})
 * @ORM\Entity(repositoryClass=AnlageAvailabilityRepository::class)
 */
class AnlageAvailability
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="availability")
     */
    private $anlage;

    /**
     * @ORM\Column(type="date")
     */
    private $stamp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $inverter;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $case_1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $case_2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $case_3;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $case_4;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $case_5;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $control;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $invAPart1;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $invAPart2;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $invA;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $remarks;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $case_1_second;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $case_2_second;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $case_3_second;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $case_4_second;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $case_5_second;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $control_second;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $invAPart1_second;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $invAPart2_second;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $invASecond;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $remarks_second;

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

    public function getStamp(): ?\DateTimeInterface
    {
        return $this->stamp;
    }

    public function setStamp(\DateTimeInterface $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    public function getInverter(): ?string
    {
        return $this->inverter;
    }

    public function getCase1(): ?int
    {
        return $this->case_1;
    }

    public function setCase1(?int $case_1): self
    {
        $this->case_1 = $case_1;

        return $this;
    }

    public function getCase2(): ?int
    {
        return $this->case_2;
    }

    public function setCase2(?int $case_2): self
    {
        $this->case_2 = $case_2;

        return $this;
    }

    public function getCase3(): ?int
    {
        return $this->case_3;
    }

    public function setCase3(?int $case_3): self
    {
        $this->case_3 = $case_3;

        return $this;
    }

    public function getCase4(): ?int
    {
        return $this->case_4;
    }

    public function setCase4(?int $case_4): self
    {
        $this->case_4 = $case_4;

        return $this;
    }

    public function getCase5(): ?int
    {
        return $this->case_5;
    }

    public function setCase5(?int $case_5): self
    {
        $this->case_5 = $case_5;

        return $this;
    }

    public function getControl(): ?int
    {
        return $this->control;
    }

    public function setControl(?int $control): self
    {
        $this->control = $control;

        return $this;
    }

    public function setInverter(string $inverter): self
    {
        $this->inverter = $inverter;

        return $this;
    }

    public function getInvAPart1(): ?float
    {
        return $this->invAPart1;
    }

    public function setInvAPart1(?float $invAPart1): self
    {
        $this->invAPart1 = $invAPart1;

        return $this;
    }

    public function getInvAPart2(): ?float
    {
        return $this->invAPart2;
    }

    public function setInvAPart2(?float $invAPart2): self
    {
        $this->invAPart2 = $invAPart2;

        return $this;
    }

    public function getInvA(): ?float
    {
        return $this->invA;
    }

    public function setInvA(?float $invA): self
    {
        $this->invA = $invA;

        return $this;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function setRemarks(string $remarks): self
    {
        $this->remarks = $remarks;

        return $this;
    }
########
    public function getCase1Second(): ?int
    {
        return $this->case_1_second;
    }

    public function setCase1Second(?int $case_1): self
    {
        $this->case_1_second = $case_1;

        return $this;
    }

    public function getCase2Second(): ?int
    {
        return $this->case_2_second;
    }

    public function setCase2Second(?int $case_2): self
    {
        $this->case_2_second = $case_2;

        return $this;
    }

    public function getCase3Second(): ?int
    {
        return $this->case_3_second;
    }

    public function setCase3Second(?int $case_3): self
    {
        $this->case_3_second = $case_3;

        return $this;
    }

    public function getCase4Second(): ?int
    {
        return $this->case_4_second;
    }

    public function setCase4Second(?int $case_4): self
    {
        $this->case_4_second = $case_4;

        return $this;
    }

    public function getCase5Second(): ?int
    {
        return $this->case_5_second;
    }

    public function setCase5Second(?int $case_5): self
    {
        $this->case_5_second = $case_5;

        return $this;
    }

    public function getControlSecond(): ?int
    {
        return $this->control_second;
    }

    public function setControlSecond(?int $control): self
    {
        $this->control_second = $control;

        return $this;
    }

    public function setInverterSecond(string $inverter): self
    {
        $this->inverter_second = $inverter;

        return $this;
    }

    public function getInvAPart1Second(): ?float
    {
        return $this->invAPart1_second;
    }

    public function setInvAPart1Second(?float $invAPart1): self
    {
        $this->invAPart1_second = $invAPart1;

        return $this;
    }

    public function getInvAPart2Second(): ?float
    {
        return $this->invAPart2_second;
    }

    public function setInvAPart2Second(?float $invAPart2): self
    {
        $this->invAPart2_second = $invAPart2;

        return $this;
    }

    public function getInvASecond(): ?float
    {
        return $this->invASecond;
    }

    public function setInvASecond(?float $invA): self
    {
        $this->invASecond = $invA;

        return $this;
    }

    public function getRemarksSecond(): ?string
    {
        return $this->remarks_second;
    }

    public function setRemarksSecond(string $remarks): self
    {
        $this->remarks_second = $remarks;

        return $this;
    }
}
