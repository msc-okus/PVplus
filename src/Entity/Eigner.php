<?php

namespace App\Entity;

use App\Repository\EignerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Eigner.
 */
#[ORM\Table(name: 'eigner')]
#[ORM\Entity(repositoryClass: \App\Repository\EignerRepository::class)]
class Eigner
{
    /**
     * @var int
     */

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'bigint', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'created', type: 'datetime', nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Gedmo\Timestampable(on: 'create')]
    private  ?\DateTimeInterface $created = null;

    #[Groups(['dashboard'])]
    #[ORM\Column(name: 'firma', type: 'string', length: 100, nullable: false)]
    private ?string $firma = null;

    #[ORM\Column(name: 'zusatz', type: 'string', length: 100, nullable: true)]
    private ?string $zusatz = null;

    #[ORM\Column(name: 'anrede', type: 'string', length: 100, nullable: true)]
    private ?string $anrede = null;

    #[ORM\Column(name: 'vorname', type: 'string', length: 100, nullable: true)]
    private ?string $vorname = null;

    #[ORM\Column(name: 'nachname', type: 'string', length: 100, nullable: true)]
    private ?string $nachname = null;

    #[ORM\Column(name: 'strasse', type: 'string', length: 100, nullable: true)]
    private ?string $strasse = null;

    #[ORM\Column(name: 'plz', type: 'string', length: 10, nullable: true)]
    private ?string $plz = null;

    #[ORM\Column(name: 'ort', type: 'string', length: 100, nullable: true)]
    private ?string $ort = null;

    /** @deprecated  */
    #[ORM\Column(name: 'active', type: 'bigint', nullable: false)]
    private string|int $active = '0';

    /** @deprecated  */
    #[ORM\Column(name: 'editlock', type: 'bigint', nullable: false, options: ['default' => 1])]
    private string $editlock = '1';

    /** @deprecated  */
    #[ORM\Column(name: 'userlock', type: 'bigint', nullable: false)]
    private string $userlock = '0';

    #[ORM\Column(name: 'language', type: 'string', length: 10, nullable: false, options: ['default' => 'EN'])]
    private string $language = 'EN';


    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'eigners')]
    #[ORM\JoinTable(name: 'eigner_user')]
    private ?Collection $user;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private ?string $activateAlertMessage = null;

    #[Groups(['dashboard'])] // Include this for serialization
    #[ORM\OneToMany(mappedBy: 'eigner', targetEntity: Anlage::class)]
    private ?Collection $anlage;

    #[ORM\OneToMany(mappedBy: 'eigner', targetEntity: AnlagenReports::class)]
    private ?Collection $anlagenReports;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private string $fontColor = '#9aacc3';

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private string $fontColor2 = '#2e639a';

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private string $fontColor3 = '#36639c';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $logo = "";

    #[ORM\OneToOne(mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private ?OwnerFeatures $features = null;

    #[ORM\OneToOne(mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private ?OwnerSettings $settings = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: ContactInfo::class, cascade: ['persist', 'remove'])]
    private Collection $contactInfos;

    #[ORM\Column(nullable: true, options: ['default' => '0'])]
    private ?bool $operations = false;

    public function __construct()
    {
        $this->user = new ArrayCollection();
        $this->anlage = new ArrayCollection();
        $this->anlagenReports = new ArrayCollection();
        $this->contactInfos = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEignerId(): ?string
    {
        return $this->id;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getFirma(): ?string
    {
        return $this->firma;
    }

    public function setFirma(string $firma): self
    {
        $this->firma = $firma;

        return $this;
    }

    public function getZusatz(): ?string
    {
        return $this->zusatz;
    }

    public function setZusatz(string $zusatz): self
    {
        $this->zusatz = $zusatz;

        return $this;
    }

    public function getAnrede(): ?string
    {
        return $this->anrede;
    }

    public function setAnrede(string $anrede): self
    {
        $this->anrede = $anrede;

        return $this;
    }

    public function getVorname(): ?string
    {
        return $this->vorname;
    }

    public function setVorname(string $vorname): self
    {
        $this->vorname = $vorname;

        return $this;
    }

    public function getNachname(): ?string
    {
        return $this->nachname;
    }

    public function setNachname(string $nachname): self
    {
        $this->nachname = $nachname;

        return $this;
    }

    public function getStrasse(): ?string
    {
        return $this->strasse;
    }

    public function setStrasse(string $strasse): self
    {
        $this->strasse = $strasse;

        return $this;
    }

    public function getPlz(): ?string
    {
        return $this->plz;
    }

    public function setPlz(string $plz): self
    {
        $this->plz = $plz;

        return $this;
    }

    public function getOrt(): ?string
    {
        return $this->ort;
    }

    public function setOrt(string $ort): self
    {
        $this->ort = $ort;

        return $this;
    }

    public function getActive(): ?string
    {
        return $this->active;
    }

    public function setActive(string $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getEditlock(): ?string
    {
        return $this->editlock;
    }

    public function setEditlock(string $editlock): self
    {
        $this->editlock = $editlock;

        return $this;
    }

    public function getUserlock(): ?string
    {
        return $this->userlock;
    }

    public function setUserlock(string $userlock): self
    {
        $this->userlock = $userlock;

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

    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(User $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user[] = $user;
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->user->contains($user)) {
            $this->user->removeElement($user);
        }

        return $this;
    }

    public function getActivateAlertMessage(): ?string
    {
        return $this->activateAlertMessage;
    }

    public function setActivateAlertMessage(?string $activateAlertMessage): self
    {
        $this->activateAlertMessage = $activateAlertMessage;

        return $this;
    }

    public function getAnlage(): Collection
    {
        return $this->anlage;
    }

    public function getAnlagen(): Collection
    {
        return $this->anlage;
    }


    public function getActiveAnlage(bool $role = false): Collection
    {
        $criteria = EignerRepository::activeAnlagenCriteria($role);

        return $this->anlage->matching($criteria);
    }

    public function addAnlage(Anlage $anlage): self
    {
        if (!$this->anlage->contains($anlage)) {
            $this->anlage[] = $anlage;
            $anlage->setEigner($this);
        }

        return $this;
    }

    public function removeAnlage(Anlage $anlage): self
    {
        if ($this->anlage->contains($anlage)) {
            $this->anlage->removeElement($anlage);
            // set the owning side to null (unless already changed)
            if ($anlage->getEigner() === $this) {
                $anlage->setEigner(null);
            }
        }

        return $this;
    }

    public function getAnlagenReports(): Collection
    {
        return $this->anlagenReports;
    }

    public function addAnlagenReport(AnlagenReports $anlagenReport): self
    {
        if (!$this->anlagenReports->contains($anlagenReport)) {
            $this->anlagenReports[] = $anlagenReport;
            $anlagenReport->setEigner($this);
        }

        return $this;
    }

    public function removeAnlagenReport(AnlagenReports $anlagenReport): self
    {
        if ($this->anlagenReports->contains($anlagenReport)) {
            $this->anlagenReports->removeElement($anlagenReport);
            // set the owning side to null (unless already changed)
            if ($anlagenReport->getEigner() === $this) {
                $anlagenReport->setEigner(null);
            }
        }

        return $this;
    }

    public function getCustomerLogo(): ?string
    {
        return $this->logo ?? 'images/pixi.png';
    }

    public function getFontColor(): ?string
    {
        return $this->fontColor;
    }

    public function setFontColor(?string $fontColor): self
    {
        $this->fontColor = $fontColor;

        return $this;
    }

    public function getFontColor2(): ?string
    {
        return $this->fontColor2;
    }

    public function setFontColor2(?string $fontColor2): self
    {
        $this->fontColor2 = $fontColor2;

        return $this;
    }

    public function getFontColor3(): ?string
    {
        return $this->fontColor3;
    }

    public function setFontColor3(?string $fontColor3): self
    {
        $this->fontColor3 = $fontColor3;

        return $this;
    }

    public function getLogo(): ?string
    {
        if (isset($this->logo)) return $this->logo;
        else return "";
    }

    public function setLogo(?string $Logo): self
    {
        $this->logo = $Logo;

        return $this;
    }

    public function getFeatures(): ?OwnerFeatures
    {
        return $this->features;
    }

    public function setFeatures(?OwnerFeatures $features): self
    {
        // unset the owning side of the relation if necessary
        if ($features === null && $this->features !== null) {
            $this->features->setOwner(null);
        }

        // set the owning side of the relation if necessary
        if ($features !== null && $features->getOwner() !== $this) {
            $features->setOwner($this);
        }

        $this->features = $features;

        return $this;
    }

    public function getSettings(): ?OwnerSettings
    {
        return $this->settings;
    }

    public function setSettings(?OwnerSettings $settings): self
    {
        // unset the owning side of the relation if necessary
        if ($settings === null && $this->settings !== null) {
            $this->settings->setOwner(null);
        }

        // set the owning side of the relation if necessary
        if ($settings !== null && $settings->getOwner() !== $this) {
            $settings->setOwner($this);
        }

        $this->settings = $settings;

        return $this;
    }

    /**
     * @return Collection<int, ContactInfo>
     */
    public function getContactInfos(): Collection
    {
        return $this->contactInfos;
    }

    public function addContactInfo(ContactInfo $contactInfo): static
    {
        if (!$this->contactInfos->contains($contactInfo)) {
            $this->contactInfos->add($contactInfo);
            $contactInfo->setOwner($this);
        }

        return $this;
    }

    public function removeContactInfo(ContactInfo $contactInfo): static
    {
        if ($this->contactInfos->removeElement($contactInfo)) {
            // set the owning side to null (unless already changed)
            if ($contactInfo->getOwner() === $this) {
                $contactInfo->setOwner(null);
            }
        }

        return $this;
    }

    public function getOperations(): ?bool
    {
        return $this->operations;
    }

    public function setOperations(?bool $operations): void
    {
        $this->operations = $operations;
    }


}
