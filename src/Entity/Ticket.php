<?php

namespace App\Entity;

use App\Helper\TicketTrait;
use App\Repository\TicketRepository;
use App\Service\FunctionsService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Symfony\Component\Serializer\Serializer;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    use TimestampableEntity;
    use BlameableEntity;
    use TicketTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private Anlage $anlage;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $autoTicket = false;

    #[ORM\Column(type: 'string', length: 50)]
    private string $editor;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $PR0 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $PR1 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $PR2 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $PA0C5 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $PA1C5 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $PA2C5 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $PA0C6 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $PA1C6 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $PA2C6 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $yield0 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $yield1 = false;

    #[Deprecated]
    #[ORM\Column(type: 'boolean')]
    private bool $yield2 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $splitted = false;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketDate::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['begin' => 'ASC'])]
    private Collection $dates;

    #[ORM\Column(nullable: true)]
    private ?bool $needsProof = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $openTicket;

    #[ORM\Column]
    private ?bool $ignoreTicket = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $TicketName = "";

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whoHided = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $WhenHidded = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $kpiStatus = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Scope = null;

    #[ORM\Column]
    private ?bool $ProofAM = false;

    #[ORM\Column(nullable: true)]
    private ?bool $needsProofEPC = false;


    /*
        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        private ?string $generatedFrom = '';
    */

    public function __construct()
    {
        $this->dates = new ArrayCollection();
        $this->priority = 10; // Low
        $this->status = 30; // Work in Progress
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $Anlage): self
    {
        $this->anlage = $Anlage;

        return $this;
    }

    public function getAutoTicket(): ?bool
    {
        return $this->autoTicket;
    }

    public function isAutoTicket(): ?bool
    {
        return $this->autoTicket;
    }

    public function setAutoTicket(?bool $autoTicket): void
    {
        $this->autoTicket = $autoTicket;
    }

    public function getEditor(): ?string
    {
        return $this->editor;
    }

    public function setEditor(string $Editor): self
    {
        $this->editor = $Editor;

        return $this;
    }

    public function unsetId()
    {
        unset($this->id);
    }

    public function getSplitted(): bool
    {
        return $this->splitted;
    }

    public function setSplitted(bool $splitted): self
    {
        $this->splitted = $splitted;

        return $this;
    }

    /**
     * @return Collection<int, TicketDate>
     */
    public function getDates(): Collection
    {
        return $this->dates;
    }

    public function addDate(TicketDate $date): self
    {
        if (!$this->dates->contains($date)) {
            $this->dates[] = $date;
            $date->setTicket($this);
        }

        return $this;
    }

    public function removeDate(TicketDate $date): self
    {
        if ($this->dates->removeElement($date)) {
            // set the owning side to null (unless already changed)

            if ($date->getTicket() === $this) {
                $date->setTicket(null);
            }
        }

        return $this;
    }

    public function removeAllDates(): self
    {
        $this->dates->clear();

        return $this;
    }

    public function isNeedsProof(): ?bool
    {
        return $this->needsProof;
    }

    public function setNeedsProof(?bool $needsProof): self
    {
        $this->needsProof = $needsProof;

        return $this;
    }
    public function isNeedsProofTAM(): ?bool
    {
        return $this->needsProof;
    }

    public function setNeedsProofTAM(?bool $needsProof): self
    {
        $this->needsProof = $needsProof;

        return $this;
    }

    public function isOpenTicket(): ?bool
    {
        return $this->openTicket;
    }

    public function setOpenTicket(?bool $openTicket): self
    {
        $this->openTicket = $openTicket;

        return $this;
    }

    public function getGeneratedFrom(){
        return $this->generatedFrom;
    }
    public function setGeneratedFrom(String $generated){
        $this->generatedFrom = $generated;
    }
    public function copyTicket(Ticket $ticket)
    {
        $this->begin = $ticket->getBegin();
        $this->end = $ticket->getEnd();
        $this->anlage = $ticket->getAnlage();
        if ($this->inverter == "")$this->inverter = $ticket->getInverter();
        $this->status = $ticket->getStatus();
        // from here on allow to edit inside the table inside edit Ticket
        $this->errorType = $ticket->getErrorType();
        $this->freeText = '';
        $this->description = "Ticket created from Ticket ".  $ticket->getId();
        $this->systemStatus = $ticket->getSystemStatus();
        $this->priority = $ticket->getPriority();
        $this->answer = $ticket->getAnswer();
        $this->alertType = $ticket->getAlertType();
        $this->kpiPaDep1 = $ticket->getKpiPaDep1();
        $this->kpiPaDep2 = $ticket->getKpiPaDep2();
        $this->kpiPaDep3 = $ticket->getKpiPaDep3();
        $endstamp = $this->getEnd()->getTimestamp();
        $beginstamp = $this->getBegin()->getTimestamp();
        $this->intervals = ($endstamp - $beginstamp) / 900;
        $this->editor = $ticket->getEditor();
        foreach($ticket->getDates() as $date){
            $dateNew = new TicketDate();
            $dateNew->copyTicketDate($date);
            $dateNew->setInverter($this->inverter);
            $dateNew->setDescription($this->description);
            $this->addDate($dateNew);
        }
    }

    public function isIgnoreTicket(): ?bool
    {
        return $this->ignoreTicket;
    }

    public function setIgnoreTicket(bool $ignoreTicket): self
    {
        $this->ignoreTicket = $ignoreTicket;

        return $this;
    }

    public function getTicketName(): ?string
    {
        return $this->TicketName;
    }

    public function setTicketName(?string $TicketName): self
    {
        $this->TicketName = $TicketName;

        return $this;
    }

    public function getWhoHided(): ?string
    {
        return $this->whoHided;
    }

    public function setWhoHided(?string $whoHided): self
    {
        $this->whoHided = $whoHided;

        return $this;
    }

    public function getWhenHidded(): ?string
    {
        return $this->WhenHidded;
    }

    public function setWhenHidded(?string $WhenHidded): self
    {
        $this->WhenHidded = $WhenHidded;

        return $this;
    }

    public function getKpiStatus(): ?string
    {
        return $this->kpiStatus;
    }

    public function setKpiStatus(?string $kpiStatus): self
    {
        $this->kpiStatus = $kpiStatus;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->Scope;
    }

    public function setScope(?string $Scope): self
    {
        $this->Scope = $Scope;

        return $this;
    }

    public function isProofAM(): ?bool
    {
        return $this->ProofAM;
    }

    public function setProofAM(bool $ProofAM): self
    {
        $this->ProofAM = $ProofAM;

        return $this;
    }

    public function isNeedsProofEPC(): ?bool
    {
        return $this->needsProofEPC;
    }

    public function setNeedsProofEPC(?bool $needsProofEPC): self
    {
        $this->needsProofEPC = $needsProofEPC;

        return $this;
    }

}
