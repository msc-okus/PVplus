<?php

namespace App\Entity;


use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;


/**
 * @ApiResource(
 *     security="is_granted('ROLE_ADMIN')",
 *     collectionOperations={"get", "post"},
 *     itemOperations={"get","put"},
 *     shortName="tokens",
 *     normalizationContext={"groups"={"user:read"}},
 *     denormalizationContext={"groups"={"user:write"}},
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "json", "html", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 */

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

    /**
     * @param User|null $user
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

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

    #[Groups(['token:read','user:read'])]
    public function getToken(): ?string
    {
        return $this->token;
    }



    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    #[Groups(['token:read','user:read'])]
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
