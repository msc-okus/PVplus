<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TwoFactorInterfaceTotp;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as TwoFactorInterfaceEmail;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;

#[ApiResource(
    shortName: 'users',
    formats: ['jsonld', 'json'],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    paginationItemsPerPage: 10,
    security: "ROLE_ADMIN"
)]
#[ApiFilter(SearchFilter::class, properties: ['anlName' => 'partital'])]

#[ApiResource(
    shortName: 'users',
    formats: ['jsonld', 'json'],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    paginationItemsPerPage: 10,
    security: "ROLE_ADMIN"
)]
#[ApiFilter(SearchFilter::class, properties: ['anlName' => 'partital'])]
#[ORM\Table(name: 'pvp_user')]
#[ORM\UniqueConstraint(name: 'name', columns: ['name'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterfaceTotp, TwoFactorInterfaceEmail, BackupCodeInterface
{
    final public const ARRAY_OF_G4N_ROLES = [
        'Developer'             => 'ROLE_DEV',
        'Admin'                 => 'ROLE_ADMIN',
        'Green4Net User'        => 'ROLE_G4N',
        'Operated by G4N'       => 'ROLE_OPERATIONS_G4N',
        'API (full)'            => 'ROLE_API_FULL_USER',
        'API '                  => 'ROLE_API_USER',
        'Beta Tester'           => 'ROLE_BETA',
        'Admin Owner'           => 'ROLE_OWNER_ADMIN',
        'AM String Analyse'     => 'ROLE_AM_STRING_ANALYSE',
        'Alert Receiver'        => 'ROLE_ALERT_RECEIVER',
    ];
    final public const ARRAY_OF_ROLES_USER = [
        'Owner (full)'      => 'ROLE_OWNER_FULL',
       // 'Owner'             => 'ROLE_OWNER',
    ];

    final public const ARRAY_OF_FUNCTIONS_BY_ROLE = [
        'Asset Management'                      => 'ROLE_AM',
        'Analyse'                               => 'ROLE_ANALYSE',
        'Ticket User'                           => 'ROLE_TICKET',
        'Maintance Repair Order System (MRO)'   => 'ROLE_MRO',
    ];

    #[Groups(['user:read'])]
    #[ORM\Column(name: 'id', type: 'bigint', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private string $id;  // DBAL return Type of bigint = string

    #[Groups(['user:read', 'user_list'])]
    #[ORM\Column(name: 'name', type: 'string', length: 20, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'password', type: 'string')]
    private string $password;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[Deprecated]
    #[ORM\Column(name: 'level', type: 'integer', nullable: false, options: ['default' => 1])]
    private int $level = 1;

    #[Deprecated]
    #[ORM\Column(name: 'admin', type: 'integer', nullable: false)]
    private int $admin = 0;

    #[ORM\Column(name: 'email', type: 'string', length: 80, nullable: false)]
    private string $email;

    #[ORM\Column(name: 'language', type: 'string', length: 10, nullable: false, options: ['default' => 'EN'])]
    private string $language = 'EN';

    #[ORM\Column(type: 'json')]
    private ?array $assignedAnlagen = [];

    #[ORM\Column(type: 'string', length: 250)]
    private string $grantedList;

    #[ORM\Column(nullable: true)]
    private ?bool $allPlants = false;

    #[Groups(['user:read', 'user_list'])]
    #[ORM\Column(nullable: true)]
    private ?bool $locked = false;

    #[Groups(['user:read'])]
    #[ORM\ManyToMany(targetEntity: Eigner::class, mappedBy: 'user')]
    private Collection $eigners;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ApiToken::class, cascade: ['remove'])]
    private Collection $apiTokens;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserLogin::class, cascade: ['remove'])]


    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $totpSecret;


    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $emailAuthCode;

    #[ORM\Column(nullable: true)]
    private ?bool $use2fa = false;

    #[ORM\Column(type: 'json')]
    private array $backupCodes = [];

    private Collection $userLogins;

    public function __construct()
    {
        $this->eigners = new ArrayCollection();
        $this->apiTokens = new ArrayCollection();
        $this->userLogins = new ArrayCollection();
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
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function setUsername(string $name): self
    {
        $this->name = $name;

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

    public function getG4NRoles(): array
    {
        $roles = $this->roles;

        return array_unique(array_intersect($roles, self::ARRAY_OF_G4N_ROLES));
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
        return $this->password;
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

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here

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
    public function getOwner(): bool|Eigner
    {
        return $this->getEigners()->first();
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

    public function getGrantedArray(bool $repectAll = true): array|false
    {
        $array = [];
        if ($this->allPlants && $repectAll) {
            foreach ($this->getEigners()->first()->getAnlagen()->toArray() as $plant){
                $array[] = $plant->getAnlId();
            }
        } else {
            foreach (explode(',', $this->grantedList) as $value) {
                $array[] = preg_replace('/\s+/', '', $value);
            }
        }
        return $array;
    }

    public function setGrantedList(string $grantedList): self
    {
        $this->grantedList = $grantedList;

        return $this;
    }

    public function getAllPlants(): ?bool
    {
        return is_null($this->allPlants) ? false : $this->allPlants;
    }

    public function setAllPlants(?bool $allPlants): self
    {
        $this->allPlants = $allPlants;

        return $this;
    }

    public function getLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(?bool $locked): void
    {
        $this->locked = $locked;
    }


    /**
     * @return Collection<int, ApiToken>
     */
    #[Groups(['user:read'])]
    public function getApiTokens(): Collection
    {
        return $this->apiTokens;
    }

    public function addApiToken(ApiToken $apiToken): self
    {
        if (!$this->apiTokens->contains($apiToken)) {
            $this->apiTokens->add($apiToken);
            $apiToken->setUser($this);
        }

        return $this;
    }

    public function removeApiToken(ApiToken $apiToken): self
    {
        if ($this->apiTokens->removeElement($apiToken)) {
            // set the owning side to null (unless already changed)
            if ($apiToken->getUser() === $this) {
                $apiToken->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserLogin>
     */
    #[Groups(['user:read'])]
    public function getUserLogins(): Collection
    {
        return $this->userLogins;
    }

    public function addUserLogin(UserLogin $userLogin): self
    {
        if (!$this->userLogins->contains($userLogin)) {
            $this->userLogins->add($userLogin);
            $userLogin->setUser($this);
        }

        return $this;
    }

    public function removeUserLogin(UserLogin $userLogin): self
    {
        if ($this->userLogins->removeElement($userLogin)) {
            // set the owning side to null (unless already changed)
            if ($userLogin->getUser() === $this) {
                $userLogin->setUser(null);
            }
        }

        return $this;
    }

    public function getRolesArrayByFeature(): array
    {
        $roles = [
            'Asset Management'      => 'ROLE_AM',
            'Analyse'               => 'ROLE_ANALYSE',
            'Ticket User'           => 'ROLE_TICKET'
        ];
        /**
         * @var Eigner $owner
         */
        $owner = $this->getEigners()[0];
        if ($owner->getFeatures()->isMroAktive()) {
            $roles['Maintance Repair Order System (MRO)'] = 'ROLE_MRO';
        }
        if ($owner->getFeatures()->isAmStringAnalyseAktive()) {
            $roles['AM String Analyse'] = 'ROLE_AM_STRING_ANALYSE';
        }

        return $roles;
    }

    public function getUse2fa(): ?bool
    {
        return $this->use2fa;
    }

    public function setUse2fa(?bool $use2fa): void
    {
        $this->use2fa = $use2fa;
    }

    public function getTotpSecret()
    {
        return $this->totpSecret;
    }

    public function setTotpSecret($totpSecret): void
    {
        $this->totpSecret = $totpSecret;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return (bool)$this->use2fa;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface|null
    {
        return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function isEmailAuthEnabled(): bool
    {
        return false; // (bool)$this->use2fa;
    }

    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    public function getEmailAuthCode(): string
    {
        if (null === $this->emailAuthCode) {
            throw new \LogicException('The email authentication code was not set');
        }

        return $this->emailAuthCode;
    }

    public function setEmailAuthCode(string $authCode): void
    {
        $this->emailAuthCode = $authCode;
    }

    /**
     * Check if it is a valid backup code.
     */
    public function isBackupCode(string $code): bool
    {
        return in_array($code, $this->backupCodes);
    }

    /**
     * Invalidate a backup code
     */
    public function invalidateBackupCode(string $code): void
    {
        $key = array_search($code, $this->backupCodes);
        if ($key !== false){
            unset($this->backupCodes[$key]);
        }
    }

    /**
     * Add a backup code
     */
    public function addBackUpCode(string $backUpCode): void
    {
        if (!in_array($backUpCode, $this->backupCodes)) {
            $this->backupCodes[] = $backUpCode;
        }
    }
}
