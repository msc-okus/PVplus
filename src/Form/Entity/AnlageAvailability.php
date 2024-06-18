<?php

namespace App\Entity;

use App\Repository\AnlageAvailabilityRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;

#[ORM\Table(name: 'anlage_availability')]
#[ORM\Index(columns: ['stamp'], name: 'stamp')]
#[ORM\Index(columns: ['inverter'], name: 'inverter')]
#[ORM\Entity(repositoryClass: AnlageAvailabilityRepository::class)]
class AnlageAvailability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'availability')]
    private ?Anlage $anlage = null;

    #[ORM\Column(type: 'date')]
    private DateTimeInterface $stamp;

    #[ORM\Column(type: 'string', length: 20)]
    private string $inverter;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_0_0 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_0_1 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_0_2 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_0_3 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_1_0 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_1_1 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_1_2 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_1_3 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_2_0 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_2_1 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_2_2 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_2_3 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_3_0 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_3_1 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_3_2 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_3_3 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_4_0 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_4_1 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_4_2 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_4_3 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_5_0 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_5_1 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_5_2 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_5_3 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_6_0 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_6_1 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_6_2 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $case_6_3 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $control_0 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $control_1 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $control_2 = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $control_3 = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invAPart1_0 = null;
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invAPart1_1 = null;
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invAPart1_2 = null;
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invAPart1_3 = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invAPart2_0 = null;
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invAPart2_1 = null;
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invAPart2_2 = null;
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invAPart2_3 = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invA_0 = null;
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invA_1 = null;
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invA_2 = null;
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $invA_3 = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $remarks_0;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $remarks_1;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $remarks_2;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $remarks_3;


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

    public function getStamp(): ?DateTimeInterface
    {
        return $this->stamp;
    }

    public function setStamp(DateTimeInterface $stamp): self
    {
        $this->stamp = $stamp;

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

    #[Deprecated]
    public function getCase0(): int
    {
        return $this->case_0_1;
    }

    #[Deprecated]
    public function setCase0(int $case_0): self
    {
        $this->case_0_1 = $case_0;
        return $this;
    }

    public function getCase1(): ?int
    {
        return $this->case_1_1;
    }

    #[Deprecated]
    public function setCase1(?int $case_1): self
    {
        $this->case_1_1 = $case_1;
        return $this;
    }

    #[Deprecated]
    public function getCase2(): ?int
    {
        return $this->case_2_1;
    }

    #[Deprecated]
    public function setCase2(?int $case_2): self
    {
        $this->case_2_1 = $case_2;
        return $this;
    }

    #[Deprecated]
    public function getCase3(): ?int
    {
        return $this->case_3_1;
    }

    #[Deprecated]
    public function setCase3(?int $case_3): self
    {
        $this->case_3_1 = $case_3;
        return $this;
    }

    #[Deprecated]
    public function getCase4(): ?int
    {
        return $this->case_4_1;
    }

    #[Deprecated]
    public function setCase4(?int $case_4): self
    {
        $this->case_4_1 = $case_4;
        return $this;
    }

    #[Deprecated]
    public function getCase5(): ?int
    {
        return $this->case_5_1;
    }

    #[Deprecated]
    public function setCase5(?int $case_5): self
    {
        $this->case_5_1 = $case_5;
        return $this;
    }

    #[Deprecated]
    public function getCase6(): ?int
    {
        return $this->case_6_1;
    }

    #[Deprecated]
    public function setCase6(?int $case_6): self
    {
        $this->case_6_1 = $case_6;
        return $this;
    }

    #[Deprecated]
    public function getControl(): ?int
    {
        return $this->control_1;
    }

    #[Deprecated]
    public function setControl(?int $control): self
    {
        $this->control_1 = $control;
        return $this;
    }

    #[Deprecated]
    public function getInvAPart1(): ?float
    {
        return $this->invAPart1_1;
    }

    #[Deprecated]
    public function setInvAPart1(?float $invAPart1): self
    {
        $this->invAPart1_1 = $invAPart1;
        return $this;
    }

    #[Deprecated]
    public function getInvAPart2(): ?float
    {
        return $this->invAPart2_1;
    }

    #[Deprecated]
    public function setInvAPart2(?float $invAPart2): self
    {
        $this->invAPart2_1 = $invAPart2;
        return $this;
    }

    #[Deprecated]
    public function getInvA(): ?float
    {
        return $this->invA_1;
    }

    #[Deprecated]
    public function setInvA(?float $invA): self
    {
        $this->invA_1 = $invA;
        return $this;
    }

    #[Deprecated]
    public function getRemarks(): ?string
    {
        return $this->remarks_1;
    }

    #[Deprecated]
    public function setRemarks(string $remarks): self
    {
        $this->remarks_1 = $remarks;
        return $this;
    }
    
    #[Deprecated]
    public function getCase0Second(): int
    {
        return $this->case_0_2;
    }

    #[Deprecated]
    public function setCase0Second(int $case_0_2): self
    {
        $this->case_0_2 = $case_0_2;
        return $this;
    }

    #[Deprecated]
    public function getCase1Second(): ?int
    {
        return $this->case_1_2;
    }

    #[Deprecated]
    public function setCase1Second(?int $case_1): self
    {
        $this->case_1_2 = $case_1;
        return $this;
    }

    #[Deprecated]
    public function getCase2Second(): ?int
    {
        return $this->case_2_2;
    }

    #[Deprecated]
    public function setCase2Second(?int $case_2): self
    {
        $this->case_2_2 = $case_2;
        return $this;
    }

    #[Deprecated]
    public function getCase3Second(): ?int
    {
        return $this->case_3_2;
    }

    #[Deprecated]
    public function setCase3Second(?int $case_3): self
    {
        $this->case_3_2 = $case_3;
        return $this;
    }

    #[Deprecated]
    public function getCase4Second(): ?int
    {
        return $this->case_4_2;
    }

    #[Deprecated]
    public function setCase4Second(?int $case_4): self
    {
        $this->case_4_2 = $case_4;
        return $this;
    }

    #[Deprecated]
    public function getCase5Second(): ?int
    {
        return $this->case_5_2;
    }

    #[Deprecated]
    public function setCase5Second(?int $case_5): self
    {
        $this->case_5_2 = $case_5;
        return $this;
    }

    #[Deprecated]
    public function getCase6Second(): ?int
    {
        return $this->case_6_2;
    }

    #[Deprecated]
    public function setCase6Second(?int $case_6): self
    {
        $this->case_6_2 = $case_6;
        return $this;
    }

    #[Deprecated]
    public function getControlSecond(): ?int
    {
        return $this->control_2;
    }

    #[Deprecated]
    public function setControlSecond(?int $control): self
    {
        $this->control_2 = $control;
        return $this;
    }

    #[Deprecated]
    public function getInvAPart1Second(): ?float
    {
        return $this->invAPart1_2;
    }

    #[Deprecated]
    public function setInvAPart1Second(?float $invAPart1): self
    {
        $this->invAPart1_2 = $invAPart1;

        return $this;
    }

    #[Deprecated]
    public function getInvAPart2Second(): ?float
    {
        return $this->invAPart2_2;
    }

    #[Deprecated]
    public function setInvAPart2Second(?float $invAPart2): self
    {
        $this->invAPart2_2 = $invAPart2;
        return $this;
    }

    #[Deprecated]
    public function getInvASecond(): ?float
    {
        return $this->invA_2;
    }

    #[Deprecated]
    public function setInvASecond(?float $invA): self
    {
        $this->invA_2 = $invA;
        return $this;
    }

    #[Deprecated]
    public function getRemarksSecond(): ?string
    {
        return $this->remarks_2;
    }

    #[Deprecated]
    public function setRemarksSecond(string $remarks): self
    {
        $this->remarks_2 = $remarks;
        return $this;
    }
    
    public function getCase00(): ?int
    {
        return $this->case_0_0;
    }

    public function setCase00(?int $case_0_0): self
    {
        $this->case_0_0 = $case_0_0;
        return $this;
    }

    public function getCase01(): ?int
    {
        return $this->case_0_1;
    }

    public function setCase01(?int $case_0_1): self
    {
        $this->case_0_1 = $case_0_1;
        return $this;
    }

    public function getCase02(): ?int
    {
        return $this->case_0_2;
    }

    public function setCase02(?int $case_0_2): self
    {
        $this->case_0_2 = $case_0_2;
        return $this;
    }

    public function getCase03(): ?int
    {
        return $this->case_0_3;
    }

    public function setCase03(?int $case_0_3): self
    {
        $this->case_0_3 = $case_0_3;
        return $this;
    }

    public function getCase10(): ?int
    {
        return $this->case_1_0;
    }

    public function setCase10(?int $case_1_0): self
    {
        $this->case_1_0 = $case_1_0;
        return $this;
    }

    public function getCase11(): ?int
    {
        return $this->case_1_1;
    }

    public function setCase11(?int $case_1_1): self
    {
        $this->case_1_1 = $case_1_1;
        return $this;
    }

    public function getCase12(): ?int
    {
        return $this->case_1_2;
    }

    public function setCase12(?int $case_1_2): self
    {
        $this->case_1_2 = $case_1_2;
        return $this;
    }

    public function getCase13(): ?int
    {
        return $this->case_1_3;
    }

    public function setCase13(?int $case_1_3): self
    {
        $this->case_1_3 = $case_1_3;
        return $this;
    }

    public function getCase20(): ?int
    {
        return $this->case_2_0;
    }

    public function setCase20(?int $case_2_0): self
    {
        $this->case_2_0 = $case_2_0;
        return $this;
    }

    public function getCase21(): ?int
    {
        return $this->case_2_1;
    }

    public function setCase21(?int $case_2_1): self
    {
        $this->case_2_1 = $case_2_1;
        return $this;
    }

    public function getCase22(): ?int
    {
        return $this->case_2_2;
    }

    public function setCase22(?int $case_2_2): self
    {
        $this->case_2_2 = $case_2_2;
        return $this;
    }

    public function getCase23(): ?int
    {
        return $this->case_2_3;
    }

    public function setCase23(?int $case_2_3): self
    {
        $this->case_2_3 = $case_2_3;
        return $this;
    }

    public function getCase30(): ?int
    {
        return $this->case_3_0;
    }

    public function setCase30(?int $case_3_0): self
    {
        $this->case_3_0 = $case_3_0;
        return $this;
    }

    public function getCase31(): ?int
    {
        return $this->case_3_1;
    }

    public function setCase31(?int $case_3_1): self
    {
        $this->case_3_1 = $case_3_1;
        return $this;
    }

    public function getCase32(): ?int
    {
        return $this->case_3_2;
    }

    public function setCase32(?int $case_3_2): self
    {
        $this->case_3_2 = $case_3_2;
        return $this;
    }

    public function getCase33(): ?int
    {
        return $this->case_3_3;
    }

    public function setCase33(?int $case_3_3): self
    {
        $this->case_3_3 = $case_3_3;
        return $this;
    }

    public function getCase40(): ?int
    {
        return $this->case_4_0;
    }

    public function setCase40(?int $case_4_0): self
    {
        $this->case_4_0 = $case_4_0;
        return $this;
    }

    public function getCase41(): ?int
    {
        return $this->case_4_1;
    }

    public function setCase41(?int $case_4_1): self
    {
        $this->case_4_1 = $case_4_1;
        return $this;
    }

    public function getCase42(): ?int
    {
        return $this->case_4_2;
    }

    public function setCase42(?int $case_4_2): self
    {
        $this->case_4_2 = $case_4_2;
        return $this;
    }

    public function getCase43(): ?int
    {
        return $this->case_4_3;
    }

    public function setCase43(?int $case_4_3): self
    {
        $this->case_4_3 = $case_4_3;
        return $this;
    }

    public function getCase50(): ?int
    {
        return $this->case_5_0;
    }

    public function setCase50(?int $case_5_0): self
    {
        $this->case_5_0 = $case_5_0;
        return $this;
    }

    public function getCase51(): ?int
    {
        return $this->case_5_1;
    }

    public function setCase51(?int $case_5_1): self
    {
        $this->case_5_1 = $case_5_1;
        return $this;
    }

    public function getCase52(): ?int
    {
        return $this->case_5_2;
    }

    public function setCase52(?int $case_5_2): self
    {
        $this->case_5_2 = $case_5_2;
        return $this;
    }

    public function getCase53(): ?int
    {
        return $this->case_5_3;
    }

    public function setCase53(?int $case_5_3): self
    {
        $this->case_5_3 = $case_5_3;
        return $this;
    }

    public function getCase60(): ?int
    {
        return $this->case_6_0;
    }

    public function setCase60(?int $case_6_0): self
    {
        $this->case_6_0 = $case_6_0;
        return $this;
    }

    public function getCase61(): ?int
    {
        return $this->case_6_1;
    }

    public function setCase61(?int $case_6_1): self
    {
        $this->case_6_1 = $case_6_1;
        return $this;
    }

    public function getCase62(): ?int
    {
        return $this->case_6_2;
    }

    public function setCase62(?int $case_6_2): self
    {
        $this->case_6_2 = $case_6_2;
        return $this;
    }

    public function getCase63(): ?int
    {
        return $this->case_6_3;
    }

    public function setCase63(?int $case_6_3): self
    {
        $this->case_6_3 = $case_6_3;
        return $this;
    }

    public function getControl0(): ?int
    {
        return $this->control_0;
    }

    public function setControl0(?int $control_0): self
    {
        $this->control_0 = $control_0;
        return $this;
    }

    public function getControl1(): ?int
    {
        return $this->control_1;
    }

    public function setControl1(?int $control_1): self
    {
        $this->control_1 = $control_1;
        return $this;
    }

    public function getControl2(): ?int
    {
        return $this->control_2;
    }

    public function setControl2(?int $control_2): self
    {
        $this->control_2 = $control_2;
        return $this;
    }

    public function getControl3(): ?int
    {
        return $this->control_3;
    }

    public function setControl3(?int $control_3): self
    {
        $this->control_3 = $control_3;
        return $this;
    }

    public function getInvAPart10(): ?float
    {
        return $this->invAPart1_0;
    }

    public function setInvAPart10(?float $invAPart1_0): self
    {
        $this->invAPart1_0 = $invAPart1_0;
        return $this;
    }

    public function getInvAPart11(): ?float
    {
        return $this->invAPart1_1;
    }

    public function setInvAPart11(?float $invAPart1_1): self
    {
        $this->invAPart1_1 = $invAPart1_1;
        return $this;
    }

    public function getInvAPart12(): ?float
    {
        return $this->invAPart1_2;
    }

    public function setInvAPart12(?float $invAPart1_2): self
    {
        $this->invAPart1_2 = $invAPart1_2;
        return $this;
    }

    public function getInvAPart13(): ?float
    {
        return $this->invAPart1_3;
    }

    public function setInvAPart13(?float $invAPart1_3): self
    {
        $this->invAPart1_3 = $invAPart1_3;
        return $this;
    }

    public function getInvAPart20(): ?float
    {
        return $this->invAPart2_0;
    }

    public function setInvAPart20(?float $invAPart2_0): self
    {
        $this->invAPart2_0 = $invAPart2_0;
        return $this;
    }

    public function getInvAPart21(): ?float
    {
        return $this->invAPart2_1;
    }

    public function setInvAPart21(?float $invAPart2_1): self
    {
        $this->invAPart2_1 = $invAPart2_1;
        return $this;
    }

    public function getInvAPart22(): ?float
    {
        return $this->invAPart2_2;
    }

    public function setInvAPart22(?float $invAPart2_2): self
    {
        $this->invAPart2_2 = $invAPart2_2;
        return $this;
    }

    public function getInvAPart23(): ?float
    {
        return $this->invAPart2_3;
    }

    public function setInvAPart23(?float $invAPart2_3): self
    {
        $this->invAPart2_3 = $invAPart2_3;
        return $this;
    }

    public function getInvA0(): ?float
    {
        return $this->invA_0;
    }

    public function setInvA0(?float $invA_0): self
    {
        $this->invA_0 = $invA_0;
        return $this;
    }

    public function getInvA1(): ?float
    {
        return $this->invA_1;
    }

    public function setInvA1(?float $invA_1): self
    {
        $this->invA_1 = $invA_1;
        return $this;
    }

    public function getInvA2(): ?float
    {
        return $this->invA_2;
    }

    public function setInvA2(?float $invA_2): self
    {
        $this->invA_2 = $invA_2;
        return $this;
    }

    public function getInvA3(): ?float
    {
        return $this->invA_3;
    }

    public function setInvA3(?float $invA_3): self
    {
        $this->invA_3 = $invA_3;
        return $this;
    }

    public function getRemarks0(): string
    {
        return $this->remarks_0;
    }

    public function setRemarks0(string $remarks_0): self
    {
        $this->remarks_0 = $remarks_0;
        return $this;
    }

    public function getRemarks1(): string
    {
        return $this->remarks_1;
    }

    public function setRemarks1(string $remarks_1): self
    {
        $this->remarks_1 = $remarks_1;
        return $this;
    }

    public function getRemarks2(): string
    {
        return $this->remarks_2;
    }

    public function setRemarks2(string $remarks_2): self
    {
        $this->remarks_2 = $remarks_2;
        return $this;
    }

    public function getRemarks3(): string
    {
        return $this->remarks_3;
    }
    
    public function setRemarks3(string $remarks_3): self
    {
        $this->remarks_3 = $remarks_3;
        return $this;
    }


}
