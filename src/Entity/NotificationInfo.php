<?php

namespace App\Entity;

use App\Repository\NotificationInfoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationInfoRepository::class)]
class NotificationInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $Date = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\ManyToOne]
    private ?ContactInfo $ContactedPerson = null;

    #[ORM\ManyToOne(inversedBy: 'notificationInfos')]
    private ?Ticket $Ticket = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->Date;
    }

    public function setDate(\DateTimeInterface $Date): static
    {
        $this->Date = $Date;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getContactedPerson(): ?ContactInfo
    {
        return $this->ContactedPerson;
    }

    public function setContactedPerson(?ContactInfo $ContactedPerson): static
    {
        $this->ContactedPerson = $ContactedPerson;

        return $this;
    }

    public function getTicket(): ?Ticket
    {
        return $this->Ticket;
    }

    public function setTicket(?Ticket $Ticket): static
    {
        $this->Ticket = $Ticket;

        return $this;
    }
}
