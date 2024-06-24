<?php

namespace App\Entity;

use App\Repository\SystemLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SystemLogRepository::class)]
class SystemLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Anlage $anlage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastTicketExecution = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastTicketExecutionDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(Anlage $anlage): static
    {
        $this->anlage = $anlage;

        return $this;
    }

    public function getLastTicketExecution(): ?string
    {
        return $this->lastTicketExecution;
    }

    public function setLastTicketExecution(?string $lastTicketExecution): static
    {
        $this->lastTicketExecution = $lastTicketExecution;

        return $this;
    }

    public function getLastTicketExecutionDate(): ?\DateTimeInterface
    {
        return $this->lastTicketExecutionDate;
    }

    public function setLastTicketExecutionDate(\DateTimeInterface $lastTicketExecutionDate): static
    {
        $this->lastTicketExecutionDate = $lastTicketExecutionDate;

        return $this;
    }
}
