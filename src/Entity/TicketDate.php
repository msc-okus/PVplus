<?php

namespace App\Entity;

use App\Repository\TicketDateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;


/**
 * @ORM\Entity(repositoryClass=TicketDateRepository::class)
 * @ORM\Table(name="ticket_date", uniqueConstraints={
 *          @ORM\UniqueConstraint(name="date_unique",
 *          columns={"begin", "end", "ticket_id"})
 *     }
 * )
 */
class TicketDate
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTimeInterface $begin;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTimeInterface $end;

    /**
     * @ORM\ManyToOne(targetEntity=Ticket::class, inversedBy="dates")
     */
    private $ticket;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $Anlage;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $Status;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $errorType;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $freeText = "";

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description = "";

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $systemStatus;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $priority;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $answer = "";

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $Inverter;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $alertType = "";


    public function __construct()
    {
        $this->Inverter = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBegin(): ?DateTimeInterface
    {
        return $this->begin;
    }

    public function setBegin(?DateTimeInterface $begin): self
    {
        $this->begin = $begin;

        return $this;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->Anlage;
    }

    public function setAnlage(?Anlage $Anlage): self
    {
        $this->Anlage = $Anlage;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->Status;
    }

    public function setStatus(?int $Status): self
    {
        $this->Status = $Status;

        return $this;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function setErrorType(?string $errorType): self
    {
        $this->errorType = $errorType;

        return $this;
    }

    public function getFreeText(): ?string
    {
        return $this->freeText;
    }

    public function setFreeText(?string $freeText): self
    {
        $this->freeText = $freeText;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSystemStatus(): ?int
    {
        return $this->systemStatus;
    }

    public function setSystemStatus(?int $systemStatus): self
    {
        $this->systemStatus = $systemStatus;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * @return Collection<int, Anlage>
     */
    public function getInverter(): Collection
    {
        return $this->Inverter;
    }

    public function setInverter(string $Inverter): self
    {
        $this->Inverter = $Inverter;

        return $this;
    }

    public function getAlertType(): ?string
    {
        return $this->alertType;
    }

    public function setAlertType(?string $alertType): self
    {
        $this->alertType = $alertType;

        return $this;
    }

}