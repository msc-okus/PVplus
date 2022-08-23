<?php

namespace App\Entity;

use App\Helper\TicketTrait;
use App\Repository\TicketDateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketDateRepository::class)]
#[ORM\Table(name: 'ticket_date')]
#[ORM\UniqueConstraint(name: 'date_unique', columns: ['begin', 'end', 'ticket_id'])]
class TicketDate
{
    use TicketTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'dates')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Ticket $ticket;

    #[ORM\ManyToOne(targetEntity: Anlage::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Anlage $Anlage;


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

    public function copyTicket(Ticket $ticket)
    {
        $this->begin = $ticket->getBegin();
        $this->end = $ticket->getEnd();
        $this->Anlage = $ticket->getAnlage();
        $this->inverter = $ticket->getInverter();
        $this->status = $ticket->getStatus();
        // from here on allow to edit inside the table inside edit Ticket
        $this->errorType = $ticket->getErrorType();
        $this->freeText = '';
        $this->description = $ticket->getDescription();
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
    }

    public function copyTicketDate(TicketDate $ticket)
    {
        $this->begin = $ticket->getBegin();
        $this->end = $ticket->getEnd();
        $this->Anlage = $ticket->getAnlage();
        $this->inverter = $ticket->getInverter();
        $this->status = $ticket->getStatus();
        // from here on allow to edit inside the table inside edit Ticket
        $this->errorType = $ticket->getErrorType();
        $this->freeText = '';
        $this->description = $ticket->getDescription();
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
    }

    public function getIntervalCount(): int
    {
        $endstamp = $this->getEnd()->getTimestamp();
        $beginstamp = $this->getBegin()->getTimestamp();

        return (int)(($endstamp - $beginstamp) / 900);
    }
}
