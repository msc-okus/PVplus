<?php

namespace App\Entity;

use App\Repository\AnlageSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnlageSettingsRepository::class)]
class AnlageSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\OneToOne(inversedBy: 'settings', targetEntity: Anlage::class, cascade: ['persist', 'remove'])]
    private ?Anlage $anlage = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $paDep1Name = 'EPC';

    #[ORM\Column(type: 'string', length: 20)]
    private string $paDep2Name = 'O&M';

    #[ORM\Column(type: 'string', length: 20)]
    private string $paDep3Name = 'AM';

    #[ORM\Column(type: 'string', length: 20)]
    private string $paDefaultDataGapHandling = 'available';

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC1 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC2 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC3 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC4 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC5 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC6 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC7 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC8 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartAC9 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC1 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC2 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC3 = true;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC4 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC5 = false;

    #[ORM\Column(nullable: true)]
    private ?bool $chartDC6 = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $anlage): self
    {
        $this->anlage = $anlage;

        return $this;
    }

    /**
     * @deprecated use Depatment Name from oner instead
     * Department name for plant availability 1
     * default: 'EPC'.
     */
    public function getPaDep1Name(): string
    {
        return $this->paDep1Name;
    }
    /**
     * @deprecated use Depatment Name from oner instead
     */
    public function setPaDep1Name(string $paDep1Name): self
    {
        $this->paDep1Name = $paDep1Name;

        return $this;
    }

    /**
     * @deprecated use Depatment Name from oner instead
     * Department name for plant availability 2
     * default: 'O&M'.
     */
    public function getPaDep2Name(): string
    {
        return $this->paDep2Name;
    }
    /**
     * @deprecated use Depatment Name from oner instead
     */
    public function setPaDep2Name(string $paDep2Name): self
    {
        $this->paDep2Name = $paDep2Name;

        return $this;
    }

    /**
     * @deprecated use Depatment Name from oner instead
     * Department name for plant availability 3
     * default: 'AM'.
     */
    public function getPaDep3Name(): string
    {
        return $this->paDep3Name;
    }
    /**
     * @deprecated use Depatment Name from oner instead
     */
    public function setPaDep3Name(string $paDep3Name): self
    {
        $this->paDep3Name = $paDep3Name;

        return $this;
    }

    /**
     * indicateing the default behavior, how data gaps should be handled
     * default: 'available', the other option should be: 'not available'.
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

    public function isChartAC1(): ?bool
    {

        return $this->chartAC1;
    }

    public function setChartAC1(?bool $chartAC1): self
    {
        $this->chartAC1 = $chartAC1;

        return $this;
    }

    public function isChartAC2(): ?bool
    {
        return $this->chartAC2;
    }

    public function setChartAC2(?bool $chartAC2): self
    {
        $this->chartAC2 = $chartAC2;

        return $this;
    }

    public function isChartAC3(): ?bool
    {
        return $this->chartAC3;
    }

    public function setChartAC3(?bool $chartAC3): self
    {
        $this->chartAC3 = $chartAC3;

        return $this;
    }

    public function isChartAC4(): ?bool
    {
        return $this->chartAC4;
    }

    public function setChartAC4(?bool $chartAC4): self
    {
        $this->chartAC4 = $chartAC4;

        return $this;
    }

    public function isChartAC5(): ?bool
    {
        return $this->chartAC5;
    }

    public function setChartAC5(?bool $chartAC5): self
    {
        $this->chartAC5 = $chartAC5;

        return $this;
    }

    public function isChartAC6(): ?bool
    {
        return $this->chartAC6;
    }

    public function setChartAC6(?bool $chartAC6): self
    {
        $this->chartAC6 = $chartAC6;

        return $this;
    }

    public function isChartAC7(): ?bool
    {
        return $this->chartAC7;
    }

    public function setChartAC7(?bool $chartAC7): self
    {
        $this->chartAC7 = $chartAC7;

        return $this;
    }

    public function isChartAC8(): ?bool
    {
        return $this->chartAC8;
    }

    public function setChartAC8(?bool $chartAC8): self
    {
        $this->chartAC8 = $chartAC8;

        return $this;
    }

    public function isChartAC9(): ?bool
    {
        return $this->chartAC9;
    }

    public function setChartAC9(?bool $chartAC9): self
    {
        $this->chartAC9 = $chartAC9;

        return $this;
    }

    public function isChartDC1(): ?bool
    {
        return $this->chartDC1;
    }

    public function setChartDC1(?bool $chartDC1): self
    {
        $this->chartDC1 = $chartDC1;

        return $this;
    }

    public function isChartDC2(): ?bool
    {
        return $this->chartDC2;
    }

    public function setChartDC2(?bool $chartDC2): self
    {
        $this->chartDC2 = $chartDC2;

        return $this;
    }

    public function isChartDC3(): ?bool
    {
        return $this->chartDC3;
    }

    public function setChartDC3(?bool $chartDC3): self
    {
        $this->chartDC3 = $chartDC3;

        return $this;
    }

    public function isChartDC4(): ?bool
    {
        return $this->chartDC4;
    }

    public function setChartDC4(?bool $chartDC4): self
    {
        $this->chartDC4 = $chartDC4;

        return $this;
    }

    public function isChartDC5(): ?bool
    {
        return $this->chartDC5;
    }

    public function setChartDC5(?bool $chartDC5): self
    {
        $this->chartDC5 = $chartDC5;

        return $this;
    }

    public function isChartDC6(): ?bool
    {
        return $this->chartDC6;
    }

    public function setChartDC6(?bool $chartDC6): self
    {
        $this->chartDC6 = $chartDC6;

        return $this;
    }


}
