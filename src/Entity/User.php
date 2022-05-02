<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Repository\UserRepository;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * PvpUser
 *
 * @ORM\Table(name="pvp_user", uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})})
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @ApiResource(
 *     normalizationContext={"groups"={"user:read"}},
 *     denormalizationContext={"groups"={"user:write"}},
 *     attributes={
 *          "formats"={"jsonld", "json", "html", "csv"={"text/csv"}}
 *     }
 * )

 */
class User implements UserInterface, \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface
{
    public const ARRAY_OF_ROLES = [
        'Developer' => 'ROLE_DEV',
        'Admin' => 'ROLE_ADMIN',
        'Green4Net User' => 'ROLE_G4N',
        'AssetManagement' => 'ROLE_AM',
        'Operator' => 'ROLE_OPERATOR',
        'Owner (full)' => 'ROLE_OWNER_FULL',
        'Owner' => 'ROLE_OWNER'
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    #[Groups(['user:read'])]
    private int $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=20, nullable=false)
     */
    #[Groups(['user:read', 'user_list'])]
    private string $name;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string")
     */
    #[Groups(['user:read'])]
    private string $password;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer", nullable=false, options={"default"="1"})
     */
    #[Groups(['user:read'])]
    private int $level = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="admin", type="integer", nullable=false)
     */
    private int $admin = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=80, nullable=false)
     */
    private string $email;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=10, nullable=false, options={"default"="EN"})
     */
    private string $language = 'EN';

    /**
     * @ORM\Column(type="json")
     */
    private ?array $assignedAnlagen = [];

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     */
    #[Groups(['main:read'])]
    private string $grantedList;

    /**
     * @ORM\ManyToMany(targetEntity=Eigner::class, mappedBy="user")
     */
    private $eigners;

    /**
     * @ORM\OneToMany(targetEntity=AllowedPlants::class, mappedBy="User")
     */
    private $allowedPlants;

    public function __construct()
    {
        $this->eigners = new ArrayCollection();
        $this->allowedPlants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?string
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getname(): ?string
    {
        return $this->name;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): ?string
    {
        return $this->name;
    }

    public function setUsername(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getAdmin(): ?int
    {
        return $this->admin;
    }

    public function setAdmin(int $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getRolesAsString(): string
    {
        $roles = $this->roles;
        $rolesString = "";
        foreach ($roles as $role) {
            ($rolesString == "") ? $rolesString .= $role : $rolesString .= ", " . $role;
        }

        return $rolesString;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getAccessList(): array
    {
        $eignerList = $this->getEigners();
        $accessList = [];
        foreach ($eignerList as $eigner) {
            $accessList[] = $eigner->getEignerId();
        }
        return $accessList;
    }

    public function getAccessListText(): string
    {
        $eignerList = $this->getEigners();
        $accessList = "'";
        foreach ($eignerList as $eigner) {
            ($accessList == "'") ? $accessList .= $eigner->getEignerId() : $accessList .= "', '" . $eigner->getEignerId();
        }
        $accessList .= "'";
        return $accessList;
    }

    /**
     * @return Collection|Eigner[]
     */
    public function getEigners(): Collection
    {
        return $this->eigners;
    }

    public function addEigner(Eigner $eigner): self
    {
        if (!$this->eigners->contains($eigner)) {
            $this->eigners[] = $eigner;
            $eigner->addUser($this);
        }

        return $this;
    }

    public function removeEigner(Eigner $eigner): self
    {
        if ($this->eigners->contains($eigner)) {
            $this->eigners->removeElement($eigner);
            $eigner->removeUser($this);
        }

        return $this;
    }

    public function getGrantedList(): ?string
    {
        return $this->grantedList;
    }

    public function setGrantedList(string $grantedList): self
    {
        $this->grantedList = $grantedList;

        return $this;
    }

    /**
     * @return Collection<int, AllowedPlants>
     */
    public function getAllowedPlants(): Collection
    {
        return $this->allowedPlants;
    }

    public function addAllowedPlant(AllowedPlants $allowedPlant): self
    {
        if (!$this->allowedPlants->contains($allowedPlant)) {
            $this->allowedPlants[] = $allowedPlant;
            $allowedPlant->setUser($this);
        }

        return $this;
    }

    public function removeAllowedPlant(AllowedPlants $allowedPlant): self
    {
        if ($this->allowedPlants->removeElement($allowedPlant)) {
            // set the owning side to null (unless already changed)
            if ($allowedPlant->getUser() === $this) {
                $allowedPlant->setUser(null);
            }
        }

        return $this;
    }

}
