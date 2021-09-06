<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DbAnlBerichte
 *
 * @ORM\Table(name="db_anl_berichte", indexes={@ORM\Index(name="br_create_date", columns={"br_create_date"})})
 * @ORM\Entity
 */
class DbAnlBerichte
{
    /**
     * @var int
     *
     * @ORM\Column(name="br_id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $brId;

    /**
     * @var string
     *
     * @ORM\Column(name="eigner_id", type="string", length=25, nullable=false)
     */
    private $eignerId;

    /**
     * @var string
     *
     * @ORM\Column(name="anl_id", type="string", length=50, nullable=false)
     */
    private $anlId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="br_create_date", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $brCreateDate = 'CURRENT_TIMESTAMP';

    /**
     * @var string
     *
     * @ORM\Column(name="rep_id", type="string", length=5, nullable=false)
     */
    private $repId;

    /**
     * @var string
     *
     * @ORM\Column(name="br_name", type="string", length=50, nullable=false)
     */
    private $brName;

    /**
     * @var string
     *
     * @ORM\Column(name="br_dateiname", type="string", length=50, nullable=false)
     */
    private $brDateiname;

    /**
     * @var string
     *
     * @ORM\Column(name="br_ist", type="string", length=20, nullable=false)
     */
    private $brIst;

    /**
     * @var string
     *
     * @ORM\Column(name="br_gelesen", type="string", length=5, nullable=false)
     */
    private $brGelesen = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="br_viewdate", type="datetime", nullable=false, options={"default"="0000-00-00 00:00:00"})
     */
    private $brViewdate = '0000-00-00 00:00:00';

    /**
     * @var string
     *
     * @ORM\Column(name="br_folder", type="text", length=65535, nullable=false)
     */
    private $brFolder;

    /**
     * @var string
     *
     * @ORM\Column(name="br_checkin", type="string", length=2, nullable=false)
     */
    private $brCheckin;

    public function getBrId(): ?string
    {
        return $this->brId;
    }

    public function getEignerId(): ?string
    {
        return $this->eignerId;
    }

    public function setEignerId(string $eignerId): self
    {
        $this->eignerId = $eignerId;

        return $this;
    }

    public function getAnlId(): ?string
    {
        return $this->anlId;
    }

    public function setAnlId(string $anlId): self
    {
        $this->anlId = $anlId;

        return $this;
    }

    public function getBrCreateDate(): ?\DateTimeInterface
    {
        return $this->brCreateDate;
    }

    public function setBrCreateDate(\DateTimeInterface $brCreateDate): self
    {
        $this->brCreateDate = $brCreateDate;

        return $this;
    }

    public function getRepId(): ?string
    {
        return $this->repId;
    }

    public function setRepId(string $repId): self
    {
        $this->repId = $repId;

        return $this;
    }

    public function getBrName(): ?string
    {
        return $this->brName;
    }

    public function setBrName(string $brName): self
    {
        $this->brName = $brName;

        return $this;
    }

    public function getBrDateiname(): ?string
    {
        return $this->brDateiname;
    }

    public function setBrDateiname(string $brDateiname): self
    {
        $this->brDateiname = $brDateiname;

        return $this;
    }

    public function getBrIst(): ?string
    {
        return $this->brIst;
    }

    public function setBrIst(string $brIst): self
    {
        $this->brIst = $brIst;

        return $this;
    }

    public function getBrGelesen(): ?string
    {
        return $this->brGelesen;
    }

    public function setBrGelesen(string $brGelesen): self
    {
        $this->brGelesen = $brGelesen;

        return $this;
    }

    public function getBrViewdate(): ?\DateTimeInterface
    {
        return $this->brViewdate;
    }

    public function setBrViewdate(\DateTimeInterface $brViewdate): self
    {
        $this->brViewdate = $brViewdate;

        return $this;
    }

    public function getBrFolder(): ?string
    {
        return $this->brFolder;
    }

    public function setBrFolder(string $brFolder): self
    {
        $this->brFolder = $brFolder;

        return $this;
    }

    public function getBrCheckin(): ?string
    {
        return $this->brCheckin;
    }

    public function setBrCheckin(string $brCheckin): self
    {
        $this->brCheckin = $brCheckin;

        return $this;
    }


}
