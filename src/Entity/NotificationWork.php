<?php

namespace App\Entity;

use App\Repository\NotificationWorkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationWorkRepository::class)]
class NotificationWork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $begin = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private string $type;

    #[ORM\ManyToOne(targetEntity: NotificationInfo::class, inversedBy: 'notificationWorks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NotificationInfo $notificationInfo = null;


    public function getType(): string
    {
        return $this->type;
    }
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBegin(): ?\DateTimeInterface
    {
        return $this->begin;
    }

    public function setBegin(\DateTimeInterface $begin): static
    {
        $this->begin = $begin;

        return $this;
    }

    public function getNotificationInfo(): ?NotificationInfo
    {
        return $this->notificationInfo;
    }

    public function setNotificationInfo(?NotificationInfo $notificationInfo): static
    {
        $this->notificationInfo = $notificationInfo;

        return $this;
    }

}
