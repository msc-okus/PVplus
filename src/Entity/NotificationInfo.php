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

    #[ORM\Column()]
    private ?\DateTime $Date = null;
    #[ORM\Column()]
    private ?\DateTime $answerDate = null;
    #[ORM\Column()]
    private ?\DateTime $closeDate = null;

    public function getAnswerDate(): ?\DateTime
    {
        return $this->answerDate;
    }

    public function setAnswerDate(?\DateTime $answerDate): void
    {
        $this->answerDate = $answerDate;
    }

    public function getCloseDate(): ?\DateTime
    {
        return $this->closeDate;
    }

    public function setCloseDate(?\DateTime $closeDate): void
    {
        $this->closeDate = $closeDate;
    }

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\ManyToOne]
    private ?ContactInfo $ContactedPerson = null;

    #[ORM\ManyToOne(inversedBy: 'notificationInfos')]
    private ?Ticket $Ticket = null;

    #[ORM\ManyToOne()]
    private User $whoNotified;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $answerFreeText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $closeFreeText = null;

    public function getWhoNotified(): User
    {
        return $this->whoNotified;
    }

    public function setWhoNotified(User $whoNotified): void
    {
        $this->whoNotified = $whoNotified;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->Date;
    }

    public function setDate(\DateTime $Date): static
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

    public function getAnswerFreeText(): ?string
    {
        return $this->answerFreeText;
    }

    public function setAnswerFreeText(?string $answerFreeText): static
    {
        $this->answerFreeText = $answerFreeText;

        return $this;
    }

    public function getCloseFreeText(): ?string
    {
        return $this->closeFreeText;
    }

    public function setCloseFreeText(?string $closeFreeText): static
    {
        $this->closeFreeText = $closeFreeText;

        return $this;
    }
}
