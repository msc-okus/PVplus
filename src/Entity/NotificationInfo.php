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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $freeText = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $priority = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $identificator = null;

    #[ORM\OneToMany(mappedBy: 'notificationInfo', targetEntity: NotificationWork::class, orphanRemoval: true)]
    private Collection $notifcationWorks;

    public function __construct()
    {
        $this->notifcationWorks = new ArrayCollection();
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
     * @return Collection<int, NotificationWork>
     */
    public function getNotifcationWorks(): Collection
    {
        return $this->notifcationWorks;
    }

    public function addNotifcationWork(NotificationWork $notifcationWork): static
    {
        if (!$this->notifcationWorks->contains($notifcationWork)) {
            $this->notifcationWorks->add($notifcationWork);
            $notifcationWork->setNotificationInfo($this);
        }

        return $this;
    }

    public function removeNotifcationWork(NotificationWork $notifcationWork): static
    {
        if ($this->notifcationWorks->removeElement($notifcationWork)) {
            // set the owning side to null (unless already changed)
            if ($notifcationWork->getNotificationInfo() === $this) {
                $notifcationWork->setNotificationInfo(null);
            }
        }

        return $this;
    }
}
