<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * PvpUser.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"user:read"}},
 *     denormalizationContext={"groups"={"user:write"}},
 *     attributes={
 *          "formats"={"jsonld", "json", "html", "csv"={"text/csv"}}
 *     }
 * )
 */
#[ORM\Table(name: 'pvp_user')]
#[ORM\UniqueConstraint(name: 'name', columns: ['name'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ARRAY_OF_ROLES = [
        'Developer'         => 'ROLE_DEV',
        'Admin'             => 'ROLE_ADMIN',
        'Green4Net User'    => 'ROLE_G4N',
        'AdminUser'         => 'ROLE_ADMIN_USER',
        'Owner (full)'      => 'ROLE_OWNER_FULL',
        'Owner'             => 'ROLE_OWNER',

    ];

    public const ARRAY_OF_ROLES_USER = [
        'Admin Owner'       => 'ROLE_ADMIN_OWNER',
        'Owner (full)'      => 'ROLE_OWNER_FULL',
        'Owner'             => 'ROLE_OWNER',

    ];

    public const ARRAY_OF_FUNCTIONS_BY_ROLE = [
        'AssetManagement'   => 'ROLE_AM',
        'Beta Tester'       => 'ROLE_BETA',
        'Allow Analyse'     => 'ROLE_ANALYSE',
    ];

    #[Groups(['user:read'])]
    #[ORM\Column(name: 'id', type: 'bigint', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[Groups(['user:read', 'user_list'])]
    #[ORM\Column(name: 'name', type: 'string', length: 20, nullable: false)]
    private string $name;

    #[Groups(['user:read'])]
    #[ORM\Column(name: 'password', type: 'string')]
    private string $password;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[Groups(['user:read'])]
    #[ORM\Column(name: 'level', type: 'integer', nullable: false, options: ['default' => 1])]
    private int $level = 1;

    #[ORM\Column(name: 'admin', type: 'integer', nullable: false)]
    private int $admin = 0;

    #[ORM\Column(name: 'email', type: 'string', length: 80, nullable: false)]
    private string $email;

    #[ORM\Column(name: 'language', type: 'string', length: 10, nullable: false, options: ['default' => 'EN'])]
    private string $language = 'EN';

    #[ORM\Column(type: 'json')]
    private ?array $assignedAnlagen = [];

    #[Groups(['main:read'])]
    #[ORM\Column(type: 'string', length: 250)]
    private string $grantedList;

    #[ORM\ManyToMany(targetEntity: Eigner::class, mappedBy: 'user')]
    private $eigners;

    public function __construct()
    {
        $this->eigners = new ArrayCollection();
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): ?string
    {
        return $this->email;
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
        $rolesString = '';
        foreach ($roles as $role) {
            ($rolesString == '') ? $rolesString .= $role : $rolesString .= ', '.$role;
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

    public function getSalt(): ?string
    {
        return null;
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
            ($accessList == "'") ? $accessList .= $eigner->getEignerId() : $accessList .= "', '".$eigner->getEignerId();
        }
        $accessList .= "'";

        return $accessList;
    }

    public function getEignerIdNew(): string
    {
        $eignerList = $this->getEigners();

        foreach ($eignerList as $eigner) {
            $eignersID = $eigner->getEignerId();
        }
        return $eignersID;
    }
    /**
     * @return Collection
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

    public function getGrantedArray(): array|false
    {
        return explode(',', $this->grantedList);
    }

    public function setGrantedList(string $grantedList): self
    {
        $this->grantedList = $grantedList;

        return $this;
    }
}
