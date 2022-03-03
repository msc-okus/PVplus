<?php

namespace App\Entity;

use App\Repository\StatusRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=StatusRepository::class)
 */
class Status
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $stamp;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="statuses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Anlage;

    /**
     * @ORM\Column(type="text")
     */
    private $Status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStamp(): ?string
    {
        return $this->stamp;
    }

    public function setStamp(string $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->Anlage;
    }

    public function setAnlage(?Anlage $Anlage): self
    {
        $this->Anlage = $Anlage;

        return $this;
    }

    public function getStatus(): ?array
    {
        return unserialize($this->Status);
    }

    public function setStatus(array $Status): self
    {
        $this->Status = serialize($Status);

        return $this;
    }
}
