<?php

namespace App\Entity;

use App\Repository\TicketDateRepository;
use Doctrine\ORM\Mapping as ORM;


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
     * @ORM\Column(type="string", length=100)
     */
    private $Begin;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $End;

    /**
     * @ORM\ManyToOne(targetEntity=Ticket::class, inversedBy="dates")
     */
    private $ticket;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBegin(): ?string
    {
        return $this->Begin;
    }

    public function setBegin(string $Begin): self
    {
        $this->Begin = $Begin;

        return $this;
    }

    public function getEnd(): ?string
    {
        return $this->End;
    }

    public function setEnd(string $End): self
    {
        $this->End = $End;

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
}
