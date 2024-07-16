<?php

namespace App\Entity;

use App\Helper\TicketTrait;
use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{

    const EFOR = 10;
    const SOR = 20;
    const OMC = 30;

    const DATA_GAP = 10;
    const INVERTER_ERROR = 20;
    const GRID_ERROR = 30;
    const WEATHER_STATION_ERROR = 40;
    const EXTERNAL_CONTROL = 50; // Regelung vom Direktvermarketr oder Netztbetreiber
    const POWER_DIFF = 60;
    const IRRADIATION = 100;

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
    private ?bool $needsProof = false;


    #[ORM\Column(nullable: true)]
    private ?bool $needsProofIt = false; // this will send an email to it@green4net.com


    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $openTicket;

    #[ORM\Column]
    private ?bool $ignoreTicket = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ticketName = "";

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whoHided = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whenHidded = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $kpiStatus = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $scope = null;

    #[ORM\Column]
    private ?bool $ProofAM = false;

    #[ORM\Column(nullable: true)]
    private ?bool $needsProofEPC = false;

    #[ORM\Column(nullable: true)]
    private ?bool $notified = null;

    #[ORM\Column(nullable: true)]
    private ?bool $internal = false;

    #[ORM\Column(nullable: true)]
    private ?bool $needsProofg4n = false;

    private ?string $creationLog = null;

    #[ORM\OneToMany(mappedBy: 'Ticket', targetEntity: NotificationInfo::class)]
    private Collection $notificationInfos;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $inverterName = "";

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $securityToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $whenClosed = null;


    /*
        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        private ?string $generatedFrom = '';
    */
    private string $generatedFrom;

    #[ORM\Column(nullable: true)]
    private ?bool $mailSent = false;

    public function __construct()
    {
        $this->dates = new ArrayCollection();
        $this->notificationInfos = new ArrayCollection();
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

    public function getNeedsProofIt(): ?bool
    {
        return $this->needsProofIt;
    }

    public function setNeedsProofIt(?bool $needsProofIt): void
    {
        $this->needsProofIt = $needsProofIt;
    } // this is proof by TAM


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

    public function getGeneratedFrom(): string
    {
        return $this->generatedFrom;
    }
    public function setGeneratedFrom(String $generated): void
    {
        $this->generatedFrom = $generated;
    }

    public function copyTicket(Ticket $ticket): void
    {
        $this->begin = $ticket->getBegin();
        $this->end = $ticket->getEnd();
        $this->anlage = $ticket->getAnlage();
        if ($this->inverter == "")$this->inverter = $ticket->getInverter();
        $this->status = $ticket->getStatus();

        // from here on allow to edit inside the table inside edit Ticket
        $this->errorType = $ticket->getErrorType();
        $this->ticketName = $ticket->getTicketName();
        $this->description = "Ticket created from Ticket ".  $ticket->getId();
        $this->systemStatus = $ticket->getSystemStatus();
        $this->priority = $ticket->getPriority();
        $this->answer = $ticket->getAnswer();
        $this->freeText = $ticket->getFreeText();
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
        return $this->ticketName;
    }

    public function setTicketName(?string $ticketName): self
    {
        $this->ticketName = $ticketName;

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
        return $this->whenHidded;
    }

    public function setWhenHidded(?string $WhenHidded): self
    {
        $this->whenHidded = $WhenHidded;

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

    public function getScope(): ?array
    {
        return explode(", ",$this->scope);
    }

    /**
     * returns true for alert Type 70 and 71 (exclude Sensor | replace Sensor)
     * returns true if we have scope for the given 'value'<br>
     * 10 = Dep1; 20 = Dep2; 30 = Dep3
     * @param $departement
     * @return bool
     */
    public function isScope($departement): bool
    {
        // if alert Type is 70 or 71 (Exclude Sensors and Replace sensors (have no scope)) it will returns always true
        if ($this->alertType == '70' || $this->alertType == '71') {
            return true;
        }
        // if other alert type it returns true depending on 'scope'
        return in_array($departement, $this->getScope());
    }
    public function setScope(?array $scope): self
    {
        $this->scope = implode(", ",$scope);

        return $this;
    }

    public function proof(): ?bool
    {
        return $this->needsProof || $this->needsProofEPC || $this->ProofAM || $this->needsProofg4n;
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

    public function isNotified(): ?bool
    {
        return $this->notified;
    }

    public function setNotified(?bool $notified): self
    {
        $this->notified = $notified;

        return $this;
    }

    public function isInternal(): ?bool
    {
        return $this->internal;
    }

    public function setInternal(?bool $internal): static
    {
        $this->internal = $internal;

        return $this;
    }

    public function isNeedsProofG4N(): ?bool
    {
        return $this->needsProofg4n;
    }

    public function setNeedsProofG4N(?bool $needsProofG4N): static
    {
        $this->needsProofg4n = $needsProofG4N;

        return $this;
    }

    public function getCreationLog(): ?string
    {
        return $this->creationLog;
    }

    public function setCreationLog(?string $creationLog): static
    {
        $this->creationLog = $creationLog;

        return $this;
    }

    /**
     * @return Collection<int, NotificationInfo>
     */
    public function getNotificationInfos(): Collection
    {
        return $this->notificationInfos;
    }

    public function addNotificationInfo(NotificationInfo $notificationInfo): static
    {
        if (!$this->notificationInfos->contains($notificationInfo)) {
            $this->notificationInfos->add($notificationInfo);
            $notificationInfo->setTicket($this);
        }

        return $this;
    }

    public function removeNotificationInfo(NotificationInfo $notificationInfo): static
    {
        if ($this->notificationInfos->removeElement($notificationInfo)) {
            // set the owning side to null (unless already changed)
            if ($notificationInfo->getTicket() === $this) {
                $notificationInfo->setTicket(null);
            }
        }

        return $this;
    }

    public function getInverterName(): ?string
    {
        return $this->inverterName;
    }

    public function setInverterName(?string $inverterName): static
    {
        $this->inverterName = $inverterName;

        return $this;
    }

    public function getInverter(): string
    {
        return $this->inverter;
    }

    public function setInverter(string $inverter): self
    {
        $this->inverter = $inverter;
        if (isset($this->anlage)) {
            if ($this->inverter !== "*"){
                $inverterArray = explode(", ", $this->inverter);
                $inverterNames = $this->anlage->getInverterFromAnlage()[$inverterArray[0]];
                for($i = 1; $i < count($inverterArray); $i++){
                    $inverterNames = $inverterNames . ", ". $this->anlage->getInverterFromAnlage()[$inverterArray[$i]];
                }
            } else {
                $inverterNames = "*";
            }
            if ($inverterNames == null) $inverterNames = "";
            $inverterString = $inverterNames;
        } else {
            $inverterString = $this->getInverter();
        }
        switch ($this->getAlertType()) {
            case 10:
                $this->description = "Data gap in Inverter(s): " . $inverterString;
                break;
            case 20:
                $this->description = "Power Error in Inverter(s): " .  $inverterString;
                break;
            case 30:
                $this->description = "Grid Error in Inverter(s): " .  $inverterString;
                break;
            case 100:
                $this->description = "Data gap in Irradiation Database" ;
                break;
            default:
                $this->description = "Error in inverter: " .  $inverterString;
        }
        $this->setInverterName($inverterString);

        return $this;
    }

    public function getSecurityToken(): ?string
    {
        return $this->securityToken;
    }

    public function setSecurityToken(?string $securityToken): static
    {
        $this->securityToken = $securityToken;

        return $this;
    }

    public function getWhenClosed(): ?\DateTimeInterface
    {
        return $this->whenClosed;
    }

    public function setWhenClosed(?\DateTimeInterface $whenClosed): static
    {
        $this->whenClosed = $whenClosed;

        return $this;
    }

    public function isMailSent(): ?bool
    {
        return $this->mailSent;
    }

    public function setMailSent(?bool $mailSent): static
    {
        $this->mailSent = $mailSent;

        return $this;
    }

}
