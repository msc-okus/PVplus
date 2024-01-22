<?php

namespace App\Entity;

use App\Repository\AlertMessagesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'pvp_alert_messages')]
#[ORM\Entity(repositoryClass: AlertMessagesRepository::class)]
class AlertMessages
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $stamp = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $AlertType = null;

    #[ORM\Column(type: 'integer')]
    private ?int $AnlagenId = null;

    #[ORM\Column(type: 'string', length: 60)]
    private ?string $emailRecipient = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'integer')]
    private ?int $statusId = null;

    #[ORM\Column(type: 'integer')]
    private ?string $statusIdLast = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $eventType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStamp(): ?\DateTimeInterface
    {
        return $this->stamp;
    }

    public function setStamp(\DateTimeInterface $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    public function getAlertType(): ?string
    {
        return $this->AlertType;
    }

    public function setAlertType(string $AlertType): self
    {
        $this->AlertType = $AlertType;

        return $this;
    }

    public function getAnlagenId(): ?int
    {
        return $this->AnlagenId;
    }

    public function setAnlagenId(int $AnlagenId): self
    {
        $this->AnlagenId = $AnlagenId;

        return $this;
    }

    public function getEmailRecipient(): ?string
    {
        return $this->emailRecipient;
    }

    public function setEmailRecipient(string $emailRecipient): self
    {
        $this->emailRecipient = $emailRecipient;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getStatusId(): ?int
    {
        return $this->statusId;
    }

    public function setStatusId(int $statusId): self
    {
        $this->statusId = $statusId;

        return $this;
    }

    public function getStatusIdLast(): ?string
    {
        return $this->statusIdLast;
    }

    public function setStatusIdLast(string $statusIdLast): self
    {
        $this->statusIdLast = $statusIdLast;

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }
}
