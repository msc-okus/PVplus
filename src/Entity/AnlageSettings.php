<?php

namespace App\Entity;

use App\Repository\AnlageSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AnlageSettingsRepository::class)
 */
class AnlageSettings
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity=Anlage::class, inversedBy="settings", cascade={"persist", "remove"})
     */
    private Anlage $anlage;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $paDep1Name = 'EPC';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $paDep2Name ='O&M';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $paDep3Name = 'AM';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $paDefaultDataGapHandling = 'available'; // not available



    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Department name for plant availability 1
     * default: 'EPC'
     * @return string
     */
    public function getPaDep1Name(): string
    {
        return $this->paDep1Name;
    }

    public function setPaDep1Name(string $paDep1Name): self
    {
        $this->paDep1Name = $paDep1Name;
        return $this;
    }
    /**
     * Department name for plant availability 2
     * default: 'O&M'
     * @return string
     */
    public function getPaDep2Name(): string
    {
        return $this->paDep2Name;
    }

    public function setPaDep2Name(string $paDep2Name): self
    {
        $this->paDep2Name = $paDep2Name;
        return $this;
    }

    /**
     * Department name for plant availability 3
     * default: 'AM'
     * @return string
     */
    public function getPaDep3Name(): string
    {
        return $this->paDep3Name;
    }

    public function setPaDep3Name(string $paDep3Name): self
    {
        $this->paDep3Name = $paDep3Name;
        return $this;
    }

    /**
     * indicateing the default behavior, how data gaps should be handled
     * default: 'available', the other option should be: 'not available'
     * @return string
     */
    public function getPaDefaultDataGapHandling(): string
    {
        return $this->paDefaultDataGapHandling;
    }

    public function setPaDefaultDataGapHandling(string $paDefaultDataGapHandling): self
    {
        $this->paDefaultDataGapHandling = $paDefaultDataGapHandling;
        return $this;
    }


}
