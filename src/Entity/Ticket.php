<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Blameable\Traits\BlameableEntity;

/**
 * @ORM\Entity(repositoryClass=TicketRepository::class)
 */
class Ticket
{
    use TimestampableEntity;
    use BlameableEntity;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="tickets")
     * @ORM\JoinColumn(nullable=false)
     */
    private Anlage $anlage;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $autoTicket = false; // automatisches Ticket ausgelöst durch Fehlererkennung im Import oder Fehlermeldung Algoritmuas

    /**
     * @ORM\Column(type="integer")
     */
    private int $status;

    /**
     * @ORM\Column(type="string")
     */
    private string $errorType; // SFOR, EFOR, OMC  //errorType

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $editor;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTimeInterface $begin;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $end;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $PR0 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $PR1 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $PR2 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $PA0C5 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $PA1C5 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $PA2C5 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $PA0C6 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $PA1C6 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $PA2C6 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $yield0 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $yield1 = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $yield2 = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private string $freeText;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $description;

    /**
     * @ORM\Column(type="integer")
     */
    private int $systemStatus;

    /**
     * @ORM\Column(type="integer")
     */
    private int $priority;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $answer;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $inverter;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $alertType = ""; //errorCategory

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $splitted = false;

    /**
     * @ORM\OneToMany(targetEntity=TicketDate::class, mappedBy="ticket")
     */
    private $dates;

    public function __construct()
    {
        $this->dates = new ArrayCollection();
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
        $this->anlage= $Anlage;

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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $Status): self
    {
        $this->status = $Status;

        return $this;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function setErrorType(string $errorType): void
    {
        $this->errorType = $errorType;
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

    public function getBegin(): ?DateTimeInterface
    {
        return $this->begin;
    }

    public function setBegin(?DateTimeInterface $Begin): self
    {
        $this->begin = $Begin;

        return $this;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?DateTimeInterface $End): self
    {
        $this->end = $End;

        return $this;
    }

    public function getPR0(): ?bool
    {
        return $this->PR0;
    }

    public function setPR0(bool $PR0): self
    {
        $this->PR0 = $PR0;

        return $this;
    }

    public function getPR1(): ?bool
    {
        return $this->PR1;
    }

    public function setPR1(bool $PR1): self
    {
        $this->PR1 = $PR1;

        return $this;
    }

    public function getPR2(): ?bool
    {
        return $this->PR2;
    }

    public function setPR2(bool $PR2): self
    {
        $this->PR2 = $PR2;

        return $this;
    }

    // Case 5 für PA
    public function getPA0C5(): ?bool
    {
        return $this->PA0C5;
    }

    public function setPA0C5(bool $PA0C5): self
    {
        $this->PA0C5 = $PA0C5;

        return $this;
    }

    public function getPA1C5(): ?bool
    {
        return $this->PA1C5;
    }

    public function setPA1C5(bool $PA1C5): self
    {
        $this->PA1C5 = $PA1C5;

        return $this;
    }

    public function getPA2C5(): ?bool
    {
        return $this->PA2C5;
    }

    public function setPA2C5(bool $PA2C5): self
    {
        $this->PA2C5 = $PA2C5;

        return $this;
    }

    // Case 6 für PA
    public function getPA0C6(): ?bool
    {
        return $this->PA0C6;
    }

    public function setPA0C6(bool $PA0C6): self
    {
        $this->PA0C6 = $PA0C6;

        return $this;
    }

    public function getPA1C6(): ?bool
    {
        return $this->PA1C6;
    }

    public function setPA1C6(bool $PA1C6): self
    {
        $this->PA1C6 = $PA1C6;

        return $this;
    }

    public function getPA2C6(): ?bool
    {
        return $this->PA2C6;
    }

    public function setPA2C6(bool $PA2C6): self
    {
        $this->PA2C6 = $PA2C6;

        return $this;
    }

    public function getYield0(): ?bool
    {
        return $this->yield0;
    }

    public function setYield0(bool $Yield0): self
    {
        $this->yield0 = $Yield0;

        return $this;
    }

    public function getYield1(): ?bool
    {
        return $this->yield1;
    }

    public function setYield1(bool $Yield1): self
    {
        $this->yield1 = $Yield1;

        return $this;
    }

    public function getYield2(): ?bool
    {
        return $this->yield2;
    }

    public function setYield2(bool $Yield2): self
    {
        $this->yield2 = $Yield2;

        return $this;
    }

    public function getFreeText(): ?string
    {
        return $this->freeText;
    }

    public function setFreeText(?string $FreeText): self
    {
        $this->freeText = $FreeText;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $Description): self
    {
        $this->description = $Description;

        return $this;
    }

    public function getSystemStatus(): ?int
    {
        return $this->systemStatus;
    }

    public function setSystemStatus(int $SystemStatus): self
    {
        $this->systemStatus = $SystemStatus;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $Priority): self
    {
        $this->priority = $Priority;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $Answer): self
    {
        $this->answer = $Answer;

        return $this;
    }

    public function getInverter(): ?string
    {
        return $this->inverter;
    }

    public function setInverter(?string $inverter): self
    {
        $this->inverter = $inverter;

        return $this;
    }

    public function getAlertType(): ?string
    {
        return $this->alertType;
    }

    public function setAlertType(string $alertType): self
    {
        $this->alertType = $alertType;

        return $this;
    }
    public function unsetId(){
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
}
