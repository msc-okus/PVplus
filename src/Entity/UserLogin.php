<?php

namespace App\Entity;

use App\Repository\UserLoginRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserLoginRepository::class)]
class UserLogin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $loggedAt = null;

    #[ORM\ManyToOne(inversedBy: 'userLogins')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct(User $user)
    {

        $this->user=$user;
        $this->loggedAt= new \DateTimeImmutable();

    }


    public function getId(): ?int
    {
        return $this->id;
    }



    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getLoggedAtString(): ?string
    {
        return $this->loggedAt->format('l jS \of F Y h:i:s A');

    }

    public function getLoggedAt(): ?\DateTimeImmutable
    {
        return $this->loggedAt;
    }

}
