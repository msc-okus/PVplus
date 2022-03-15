<?php

namespace App\Entity;

use App\Repository\LogMessagesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogMessagesRepository::class)
 */
class LogMessages
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $plant;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $function;

    /**
     * @ORM\Column(type="text")
     */
    private string $job;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $startedAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $finishedAt;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $state;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlant(): ?string
    {
        return $this->plant;
    }

    public function setPlant(string $plant): self
    {
        $this->plant = $plant;

        return $this;
    }

    public function getFunction(): ?string
    {
        return $this->function;
    }

    public function setFunction(string $function): self
    {
        $this->function = $function;

        return $this;
    }

    public function getJob(): ?string
    {
        return $this->job;
    }

    public function setJob(string $job): self
    {
        $this->job = $job;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }
}
