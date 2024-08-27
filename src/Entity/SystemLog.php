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
    private ?string $lastTicketExecutionStatus = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastTicketExecutionDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastImportScriptExecutionStatus = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastImportScriptExecutionDate = null;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastImportScriptExecutionDate(): ?\DateTimeInterface
    {
        return $this->lastImportScriptExecutionDate;
    }

    public function setLastImportScriptExecutionDate(?\DateTimeInterface $lastImportScriptExecutionDate): void
    {
        $this->lastImportScriptExecutionDate = $lastImportScriptExecutionDate;
    }

    public function getLastImportScriptExecutionStatus(): ?string
    {
        return $this->lastImportScriptExecutionStatus;
    }

    public function setLastImportScriptExecutionStatus(?string $lastImportScriptExecutionStatus): void
    {
        $this->lastImportScriptExecutionStatus = $lastImportScriptExecutionStatus;
    }

    public function getLastTicketExecutionStatus(): ?string
    {
        return $this->lastTicketExecutionStatus;
    }

    public function setLastTicketExecutionStatus(?string $lastTicketExecutionStatus): void
    {
        $this->lastTicketExecutionStatus = $lastTicketExecutionStatus;
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
