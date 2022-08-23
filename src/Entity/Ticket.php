<?php

namespace App\Entity;

use App\Helper\TicketTrait;
use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    use TimestampableEntity;
    use BlameableEntity;
    use TicketTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Anlage::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private Anlage $anlage;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $autoTicket = false;

    #[ORM\Column(type: 'string', length: 50)]
    private string $editor;

    #[ORM\Column(type: 'boolean')]
    private bool $PR0 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $PR1 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $PR2 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $PA0C5 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $PA1C5 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $PA2C5 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $PA0C6 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $PA1C6 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $PA2C6 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $yield0 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $yield1 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $yield2 = false;

    #[ORM\Column(type: 'boolean')]
    private bool $splitted = false;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketDate::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['begin' => 'ASC'])]
    private Collection $dates;

    public function __construct()
    {
        $this->dates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $Anlage): self
    {
        $this->anlage = $Anlage;

        return $this;
    }

    public function getAutoTicket(): ?bool
    {
        return $this->autoTicket;
    }

    public function isAutoTicket(): ?bool
    {
        return $this->autoTicket;
    }

    public function setAutoTicket(?bool $autoTicket): void
    {
        $this->autoTicket = $autoTicket;
    }



    public function getEditor(): ?string
    {
        return $this->editor;
    }

    public function setEditor(string $Editor): self
    {
        $this->editor = $Editor;

        return $this;
    }

    public function getPR0(): ?bool
    {
        return $this->PR0;
    }

    public function setPR0(bool $PR0): self
    {
        $this->PR0 = $PR0;

        return $this;
    }

    public function getPR1(): ?bool
    {
        return $this->PR1;
    }

    public function setPR1(bool $PR1): self
    {
        $this->PR1 = $PR1;

        return $this;
    }

    public function getPR2(): ?bool
    {
        return $this->PR2;
    }

    public function setPR2(bool $PR2): self
    {
        $this->PR2 = $PR2;

        return $this;
    }

    // Case 5 für PA
    public function getPA0C5(): ?bool
    {
        return $this->PA0C5;
    }

    public function setPA0C5(bool $PA0C5): self
    {
        $this->PA0C5 = $PA0C5;

        return $this;
    }

    public function getPA1C5(): ?bool
    {
        return $this->PA1C5;
    }

    public function setPA1C5(bool $PA1C5): self
    {
        $this->PA1C5 = $PA1C5;

        return $this;
    }

    public function getPA2C5(): ?bool
    {
        return $this->PA2C5;
    }

    public function setPA2C5(bool $PA2C5): self
    {
        $this->PA2C5 = $PA2C5;

        return $this;
    }

    // Case 6 für PA
    public function getPA0C6(): ?bool
    {
        return $this->PA0C6;
    }

    public function setPA0C6(bool $PA0C6): self
    {
        $this->PA0C6 = $PA0C6;

        return $this;
    }

    public function getPA1C6(): ?bool
    {
        return $this->PA1C6;
    }

    public function setPA1C6(bool $PA1C6): self
    {
        $this->PA1C6 = $PA1C6;

        return $this;
    }

    public function getPA2C6(): ?bool
    {
        return $this->PA2C6;
    }

    public function setPA2C6(bool $PA2C6): self
    {
        $this->PA2C6 = $PA2C6;

        return $this;
    }

    public function getYield0(): ?bool
    {
        return $this->yield0;
    }

    public function setYield0(bool $Yield0): self
    {
        $this->yield0 = $Yield0;

        return $this;
    }

    public function getYield1(): ?bool
    {
        return $this->yield1;
    }

    public function setYield1(bool $Yield1): self
    {
        $this->yield1 = $Yield1;

        return $this;
    }

    public function getYield2(): ?bool
    {
        return $this->yield2;
    }

    public function setYield2(bool $Yield2): self
    {
        $this->yield2 = $Yield2;

        return $this;
    }

    public function unsetId()
    {
        unset($this->id);
    }

    public function getSplitted(): bool
    {
        return $this->splitted;
    }

    public function setSplitted(bool $splitted): self
    {
        $this->splitted = $splitted;

        return $this;
    }

    /**
     * @return Collection<int, TicketDate>
     */
    public function getDates(): Collection
    {
        return $this->dates;
    }

    public function addDate(TicketDate $date): self
    {
        if (!$this->dates->contains($date)) {
            $this->dates[] = $date;
            $date->setTicket($this);
        }

        return $this;
    }

    public function removeDate(TicketDate $date): self
    {
        if ($this->dates->removeElement($date)) {
            // set the owning side to null (unless already changed)

            if ($date->getTicket() === $this) {
                $date->setTicket(null);
            }
        }

        return $this;
    }

    public function removeAllDates(): self
    {
        $this->dates->clear();

        return $this;
    }

}
