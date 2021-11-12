<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Blameable\Traits\BlameableEntity;

/**
 * @ORM\Entity(repositoryClass=TicketRepository::class)
 */
class Ticket
{
    use TimestampableEntity;
    use BlameableEntity;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="tickets")
     * @ORM\JoinColumn(nullable=false)
     */
    private $anlage;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $editor;

    /**
     * @ORM\Column(type="date")
     */
    private $begin;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $end;



    /**
     * @ORM\Column(type="boolean")
     */
    private $PR;

    /**
     * @ORM\Column(type="boolean")
     */
    private $PA;

    /**
     * @ORM\Column(type="boolean")
     */
    private $yield;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $freeText;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="integer")
     */
    private $systemStatus;

    /**
     * @ORM\Column(type="integer")
     */
    private $priority;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $answer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $Anlage): self
    {
        $this->anlage= $Anlage;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $Status): self
    {
        $this->status = $Status;

        return $this;
    }

    public function getEditor(): ?string
    {
        return $this->editor;
    }

    public function setEditor(string $Editor): self
    {
        $this->editor = $Editor;

        return $this;
    }

    public function getBegin(): ?\DateTimeInterface
    {
        return $this->begin;
    }

    public function setBegin(?\DateTimeInterface $Begin): self
    {
        $this->begin = $Begin;

        return $this;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?\DateTimeInterface $End): self
    {
        $this->end = $End;

        return $this;
    }

    public function getPR(): ?bool
    {
        return $this->PR;
    }

    public function setPR(bool $PR): self
    {
        $this->PR = $PR;

        return $this;
    }

    public function getPA(): ?bool
    {
        return $this->PA;
    }

    public function setPA(bool $PA): self
    {
        $this->PA = $PA;

        return $this;
    }

    public function getYield(): ?bool
    {
        return $this->yield;
    }

    public function setYield(bool $Yield): self
    {
        $this->yield = $Yield;

        return $this;
    }

    public function getFreeText(): ?string
    {
        return $this->freeText;
    }

    public function setFreeText(?string $FreeText): self
    {
        $this->freeText = $FreeText;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $Description): self
    {
        $this->description = $Description;

        return $this;
    }

    public function getSystemStatus(): ?int
    {
        return $this->systemStatus;
    }

    public function setSystemStatus(int $SystemStatus): self
    {
        $this->systemStatus = $SystemStatus;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $Priority): self
    {
        $this->priority = $Priority;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $Answer): self
    {
        $this->answer = $Answer;

        return $this;
    }
}
