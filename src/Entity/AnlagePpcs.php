<?php

namespace App\Entity;

use App\Repository\AnlagePpcsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

#[ORM\Entity(repositoryClass: AnlagePpcsRepository::class)]
class AnlagePpcs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'Ppcs')]
    private ?Anlage $anlage = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $vcomId = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $startDatePpc;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $endDatePpc;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $anlage): static
    {
        $this->anlage = $anlage;

        return $this;
    }

    function getVcomId(): ?string
    {
        return $this->vcomId;
    }

    public function setVcomId(?string $vcomId): static
    {
        $this->vcomId = $vcomId;

        return $this;
    }

    public function getStartDatePpc(): ?\DateTimeInterface
    {
        return $this->startDatePpc;
    }

    public function setStartDatePpc(?\DateTimeInterface $startDatePpc = null): self
    {

        $this->startDatePpc = $startDatePpc;

        return $this;
    }

    public function getEndDatePpc(): ?\DateTimeInterface
    {
        return $this->endDatePpc;
    }

    public function setEndDatePpc(?\DateTimeInterface $endDatePpc = null): self
    {

        $this->endDatePpc = $endDatePpc;

        return $this;
    }
}
