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

    #[ORM\Column(type: 'string', length: 60,nullable: true)]
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

    #[ORM\Column( type: 'integer',nullable: true)]
    private ?int $alertId = null;

    #[ORM\Column( type: 'boolean',nullable: true)]
    private ?bool $checked = null;


    #[ORM\Column( type: 'string',length: 20,nullable: true)]
    private ?string $checkedByUser = null;

    #[ORM\Column(type: 'datetime_immutable',nullable: true)]
    private ?\DateTimeImmutable $checkedAt = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $token = null;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getChecked(): ?bool
    {
        return $this->checked;
    }

    public function setChecked(?bool $checked): void
    {
        $this->checked = $checked;
    }

    public function getAlertId(): ?int
    {
        return $this->alertId;
    }

    public function setAlertId(?int $alertId): void
    {
        $this->alertId = $alertId;
    }

    public function getCheckedByUser(): ?string
    {
        return $this->checkedByUser;
    }

    public function setCheckedByUser(?string $checkedByUser): self
    {
        $this->checkedByUser = $checkedByUser;
        return $this;
    }

    public function getCheckedAt(): ?\DateTimeImmutable
    {
        return $this->checkedAt;
    }

    public function setCheckedAt(?\DateTimeImmutable $checkedAt): void
    {
        $this->checkedAt = $checkedAt;
    }



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
