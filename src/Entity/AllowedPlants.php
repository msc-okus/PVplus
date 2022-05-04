<?php

namespace App\Entity;

use App\Repository\AllowedPlantsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AllowedPlantsRepository::class)
 */
class AllowedPlants
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="allowedPlants")
     */
    private $User;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $Anlage;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): self
    {
        $this->User = $User;

        return $this;
    }

    public function getAnlage(): ?string
    {
        return $this->Anlage;
    }

    public function setAnlage(string $Anlage): self
    {
        $this->Anlage = $Anlage;

        return $this;
    }
}
