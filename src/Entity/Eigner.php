<?php

namespace App\Entity;

use App\Repository\EignerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Eigner
 *
 * @ORM\Table(name="eigner")
 * @ORM\Entity(repositoryClass="App\Repository\EignerRepository")
 */
class Eigner
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     * @Gedmo\Timestampable(on="create")
     */
    private $created;

    /**
     * @var string
     *
     * @ORM\Column(name="firma", type="string", length=100, nullable=false)
     */
    private $firma;

    /**
     * @var string
     *
     * @ORM\Column(name="zusatz", type="string", length=100, nullable=false)
     */
    private $zusatz;

    /**
     * @var string
     *
     * @ORM\Column(name="anrede", type="string", length=100, nullable=false)
     */
    private $anrede;

    /**
     * @var string
     *
     * @ORM\Column(name="vorname", type="string", length=100, nullable=false)
     */
    private $vorname;

    /**
     * @var string
     *
     * @ORM\Column(name="nachname", type="string", length=100, nullable=false)
     */
    private $nachname;

    /**
     * @var string
     *
     * @ORM\Column(name="strasse", type="string", length=100, nullable=false)
     */
    private $strasse;

    /**
     * @var string
     *
     * @ORM\Column(name="plz", type="string", length=10, nullable=false)
     */
    private $plz;

    /**
     * @var string
     *
     * @ORM\Column(name="ort", type="string", length=100, nullable=false)
     */
    private $ort;

    /**
     * @var string
     *
     * @ORM\Column(name="nachricht", type="text", length=65535, nullable=true)
     */
    private $nachricht;

    /**
     * @var string
     *
     * @ORM\Column(name="telefon1", type="string", length=100, nullable=false)
     */
    private $telefon1;

    /**
     * @var string
     *
     * @ORM\Column(name="telefon2", type="string", length=100, nullable=false)
     */
    private $telefon2;

    /**
     * @var string
     *
     * @ORM\Column(name="mobil", type="string", length=100, nullable=false)
     */
    private $mobil;

    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", length=100, nullable=false)
     */
    private $fax;

    /**
     * @var string
     *
     * @ORM\Column(name="home_dir", type="string", length=100, nullable=false, options={"default"="user/home/"})
     */
    private $homeDir = 'user/home/';

    /**
     * @var string
     *
     * @ORM\Column(name="home_folder", type="text", length=65535, nullable=true)
     */
    private $homeFolder;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="text", length=65535, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="web", type="text", length=65535, nullable=true)
     */
    private $web;

    /**
     * @var string
     *
     * @ORM\Column(name="bv_anrede", type="string", length=100, nullable=false)
     */
    private $bvAnrede;

    /**
     * @var string
     *
     * @ORM\Column(name="bv_vorname", type="string", length=100, nullable=false)
     */
    private $bvVorname;

    /**
     * @var string
     *
     * @ORM\Column(name="bv_nachname", type="string", length=100, nullable=false)
     */
    private $bvNachname;

    /**
     * @var string
     *
     * @ORM\Column(name="bv_email", type="text", length=65535, nullable=false)
     */
    private $bvEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="bv_telefon1", type="string", length=100, nullable=false)
     */
    private $bvTelefon1;

    /**
     * @var string
     *
     * @ORM\Column(name="bv_telefon2", type="string", length=100, nullable=false)
     */
    private $bvTelefon2;

    /**
     * @var string
     *
     * @ORM\Column(name="bv_mobil", type="string", length=100, nullable=false)
     */
    private $bvMobil;

    /**
     * @var int
     *
     * @ORM\Column(name="active", type="bigint", nullable=false)
     */
    private $active = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="editlock", type="bigint", nullable=false, options={"default"="1"})
     */
    private $editlock = '1';

    /**
     * @var int
     *
     * @ORM\Column(name="userlock", type="bigint", nullable=false)
     */
    private $userlock = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=10, nullable=false, options={"default"="EN"})
     */
    private $language = 'EN';

    /**
     * @var string
     *
     * @ORM\Column(name="level", type="string", length=5, nullable=false, options={"default"="1"})
     */
    private $level = '1';

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="eigners")
     * @ORM\JoinTable(name="eigner_user")
     *
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $activateAlertMessage;

    /**
     * @ORM\OneToMany(targetEntity=Anlage::class, mappedBy="eigner")
     */
    private $anlage;

    /**
     * @ORM\OneToMany(targetEntity=AnlagenReports::class, mappedBy="eigner")
     */
    private $anlagenReports;

    //private $security;

    public function __construct() //Security $security
    {
        //$this->security = $security;
        $this->user = new ArrayCollection();
        $this->anlage = new ArrayCollection();
        $this->anlagenReports = new ArrayCollection();
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

    public function getNachricht(): ?string
    {
        return $this->nachricht;
    }

    public function setNachricht(string $nachricht): self
    {
        $this->nachricht = $nachricht;

        return $this;
    }

    public function getTelefon1(): ?string
    {
        return $this->telefon1;
    }

    public function setTelefon1(string $telefon1): self
    {
        $this->telefon1 = $telefon1;

        return $this;
    }

    public function getTelefon2(): ?string
    {
        return $this->telefon2;
    }

    public function setTelefon2(string $telefon2): self
    {
        $this->telefon2 = $telefon2;

        return $this;
    }

    public function getMobil(): ?string
    {
        return $this->mobil;
    }

    public function setMobil(string $mobil): self
    {
        $this->mobil = $mobil;

        return $this;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(string $fax): self
    {
        $this->fax = $fax;

        return $this;
    }

    public function getHomeDir(): ?string
    {
        return $this->homeDir;
    }

    public function setHomeDir(string $homeDir): self
    {
        $this->homeDir = $homeDir;

        return $this;
    }

    public function getHomeFolder(): ?string
    {
        return $this->homeFolder;
    }

    public function setHomeFolder(string $homeFolder): self
    {
        $this->homeFolder = $homeFolder;

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

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function setWeb(string $web): self
    {
        $this->web = $web;

        return $this;
    }

    public function getBvAnrede(): ?string
    {
        return $this->bvAnrede;
    }

    public function setBvAnrede(string $bvAnrede): self
    {
        $this->bvAnrede = $bvAnrede;

        return $this;
    }

    public function getBvVorname(): ?string
    {
        return $this->bvVorname;
    }

    public function setBvVorname(string $bvVorname): self
    {
        $this->bvVorname = $bvVorname;

        return $this;
    }

    public function getBvNachname(): ?string
    {
        return $this->bvNachname;
    }

    public function setBvNachname(string $bvNachname): self
    {
        $this->bvNachname = $bvNachname;

        return $this;
    }

    public function getBvEmail(): ?string
    {
        return $this->bvEmail;
    }

    public function setBvEmail(string $bvEmail): self
    {
        $this->bvEmail = $bvEmail;

        return $this;
    }

    public function getBvTelefon1(): ?string
    {
        return $this->bvTelefon1;
    }

    public function setBvTelefon1(string $bvTelefon1): self
    {
        $this->bvTelefon1 = $bvTelefon1;

        return $this;
    }

    public function getBvTelefon2(): ?string
    {
        return $this->bvTelefon2;
    }

    public function setBvTelefon2(string $bvTelefon2): self
    {
        $this->bvTelefon2 = $bvTelefon2;

        return $this;
    }

    public function getBvMobil(): ?string
    {
        return $this->bvMobil;
    }

    public function setBvMobil(string $bvMobil): self
    {
        $this->bvMobil = $bvMobil;

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

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
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

    /**
     * @return Collection|Anlage[]
     */
    public function getAnlage(): Collection
    {
        return $this->anlage;
    }

    /**
     * @param bool $role
     * @return Collection|Anlage[]
     */
    public function getActiveAnlage($role = false): Collection
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

    /**
     * @return Collection|AnlagenReports[]
     */
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


}
