<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Repository\UserLoginRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ApiResource(
 *     collectionOperations={"get", "post"},
 *     itemOperations={"get","put"},
 *     shortName="logins",
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

    /**
     * @param User|null $user
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }



    public function getUser(): ?User
    {
        return $this->user;
    }

    #[Groups(['user:read'])]
    public function getLoggedAtString(): ?string
    {
        return $this->loggedAt->format('l jS \of F Y h:i:s A');

    }


    public function getLoggedAt(): ?\DateTimeImmutable
    {
        return $this->loggedAt;
    }

}