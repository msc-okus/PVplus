<?php

namespace App\Entity;

use App\Repository\NotificationInfoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\OneToMany(mappedBy: 'notificationInfo', targetEntity: NotificationWork::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $notificationWorks;


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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $freeText = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $priority = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $identificator = null;

    /**
     * @var Collection<int, AnlageFile>
     */
    #[ORM\OneToMany(mappedBy: 'notificationInfo', targetEntity: AnlageFile::class)]
    private Collection $attachedMedia;

    public function getNotificationWorks(): Collection
    {
        return $this->notificationWorks;
    }

    public function setNotificationWorks(Collection $notificationWorks): void
    {
        $this->notificationWorks = $notificationWorks;
    }

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

    public function __construct()
    {
        $this->notificationWorks = new ArrayCollection();
        $this->attachedMedia = new ArrayCollection();
    }

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

    public function getFreeText(): ?string
    {
        return $this->freeText;
    }

    public function setFreeText(string $freeText): static
    {
        $this->freeText = $freeText;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getIdentificator(): ?string
    {
        return $this->identificator;
    }

    public function setIdentificator(?string $identificator): static
    {
        $this->identificator = $identificator;

        return $this;
    }

    /**
     * @return Collection<int, AnlageFile>
     */
    public function getAttachedMedia(): Collection
    {
        return $this->attachedMedia;
    }

    public function addAttachedMedium(AnlageFile $attachedMedium): static
    {
        if (!$this->attachedMedia->contains($attachedMedium)) {
            $this->attachedMedia->add($attachedMedium);
            $attachedMedium->setNotificationInfo($this);
        }

        return $this;
    }

    public function removeAttachedMedium(AnlageFile $attachedMedium): static
    {
        if ($this->attachedMedia->removeElement($attachedMedium)) {
            // set the owning side to null (unless already changed)
            if ($attachedMedium->getNotificationInfo() === $this) {
                $attachedMedium->setNotificationInfo(null);
            }
        }

        return $this;
    }

}
