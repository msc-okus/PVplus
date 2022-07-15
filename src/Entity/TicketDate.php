<?php

namespace App\Entity;

use App\Helper\TicketTrait;
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
    use TicketTrait;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Ticket::class, inversedBy="dates")
     */
    private $ticket;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $Anlage;

    public function __construct()
    {

    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function copyTicket(Ticket $ticket){
        $this->Begin = $ticket->getBegin();
        $this->end = $ticket->getEnd();
        $this->Anlage = $ticket->getAnlage();
        $this->inverter = $ticket->getInverter();
        $this->Status = $ticket->getStatus();//from here on allow to edit inside the table inside edit Ticket
        $this->ErrorType = $ticket->getErrorType();
        $this->FreeText = "";
        $this->Description = $ticket->getDescription();
        $this->SystemStatus = $ticket->getSystemStatus();
        $this->Priority = $ticket->getPriority();
        $this->Answer = $ticket->getAnswer();
        $this->AlertType = $ticket->getAlertType();
    }
    public function copyTicketDate(TicketDate $ticket){
        $this->Begin = $ticket->getBegin();
        $this->end = $ticket->getEnd();
        $this->Anlage = $ticket->getAnlage();
        $this->inverter = $ticket->getInverter();
        $this->Status = $ticket->getStatus();//from here on allow to edit inside the table inside edit Ticket
        $this->ErrorType = $ticket->getErrorType();
        $this->FreeText = "";
        $this->Description = $ticket->getDescription();
        $this->SystemStatus = $ticket->getSystemStatus();
        $this->Priority = $ticket->getPriority();
        $this->Answer = $ticket->getAnswer();
        $this->AlertType = $ticket->getAlertType();
    }

}