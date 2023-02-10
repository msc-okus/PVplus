<?php

namespace App\Entity;

use App\Repository\ApiTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
class ApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $token = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\ManyToOne(inversedBy: 'apiTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct(User $user)
    {
        $u = unpack('H*', (string)$user->getEmail());
        $t = unpack('H*', (string)time());

        $this->token= array_shift($t) . bin2hex(random_bytes(32)). array_shift($u) ;
        $this->user=$user;
        $this->expiresAt = new \DateTimeImmutable('+ 1hour');
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups('token:read')]
    public function getToken(): ?string
    {
        return $this->token;
    }



    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    #[Groups('token:read')]
    #[SerializedName('expiresAt')]
    public function getExpiresAtString(): ?string
    {
        return $this->expiresAt->format('l jS \of F Y h:i:s A');
    }



    public function getUser(): ?User
    {
        return $this->user;
    }

    #[Groups('token:read')]
    #[SerializedName('user')]
    public function getUserInfo(): ?string
    {
        return $this->user->getEmail();
    }

    #[Groups('token:read')]
    public function getMessage(): ?string
    {
        return 'Go to /api and add for any request your token as Bearer Token in the authorization header ';
    }

    public function isExpired(): bool
    {
        return $this->getExpiresAt() <= new \DateTimeImmutable();
    }
}
