<?php

namespace App\Entity;

use App\Helper\TicketTrait;
use App\Repository\TicketDateRepository;
use App\Service\FunctionsService;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Serializer;

#[ORM\Entity(repositoryClass: TicketDateRepository::class)]
#[ORM\Table(name: 'ticket_date')]
#[ORM\UniqueConstraint(name: 'date_unique', columns: ['begin', 'end', 'ticket_id'])]

class TicketDate
{
    use TicketTrait;
    use TimestampableEntity;
    use BlameableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'dates')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Ticket $ticket;

    #[ORM\ManyToOne(targetEntity: Anlage::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Anlage $Anlage;

    #[ORM\Column(nullable: true)]
    private ?bool $replaceEnergy = null;

    #[ORM\Column(nullable: true)]
    private ?bool $replaceIrr = null;

    #[ORM\Column(nullable: true)]
    private ?bool $useHour = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $valueEnergy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $valueIrr = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $correctEnergyValue = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reasonText = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $PRExcludeMethod = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $beginHidden;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $endHidden ;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sensors = "";


    public function __construct()
    {
        $this->beginHidden = new DateTime();
        $this->endHidden = new DateTime();
    }

    public function getId(): ?int
    {
        if (!isset($this->id)) return 0;
        return $this->id;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->Anlage;
    }

    public function setAnlage(?Anlage $Anlage): self
    {
        $this->Anlage = $Anlage;

        return $this;
    }

    public function copyTicket(Ticket $ticket): void
    {
        $this->begin = $ticket->getBegin();
        $this->end = $ticket->getEnd();
        $this->Anlage = $ticket->getAnlage();
        $this->inverter = $ticket->getInverter();
        $this->status = $ticket->getStatus();
        // from here on allow to edit inside the table inside edit Ticket
        $this->errorType = $ticket->getErrorType();
        $this->freeText = '';
        $this->description = $ticket->getDescription();
        $this->systemStatus = $ticket->getSystemStatus();
        $this->priority = $ticket->getPriority();
        $this->answer = $ticket->getAnswer();
        $this->alertType = $ticket->getAlertType();
        $this->kpiPaDep1 = $ticket->getKpiPaDep1();
        $this->kpiPaDep2 = $ticket->getKpiPaDep2();
        $this->kpiPaDep3 = $ticket->getKpiPaDep3();
        $endstamp = $this->getEnd()->getTimestamp();
        $beginstamp = $this->getBegin()->getTimestamp();
        $this->intervals = ($endstamp - $beginstamp) / 900;
    }

    public function copyTicketDate(TicketDate $ticket): void
    {
        $this->begin = $ticket->getBegin();
        $this->end = $ticket->getEnd();
        $this->Anlage = $ticket->getAnlage();
        $this->inverter = $ticket->getInverter();
        $this->status = $ticket->getStatus();
        // from here on allow to edit inside the table inside edit Ticket
        $this->errorType = $ticket->getErrorType();
        $this->freeText = '';
        $this->description = $ticket->getDescription();
        $this->systemStatus = $ticket->getSystemStatus();
        $this->priority = $ticket->getPriority();
        $this->answer = $ticket->getAnswer();
        $this->alertType = $ticket->getAlertType();
        $this->setDataGapEvaluation($ticket->getDataGapEvaluation());
        $this->kpiPaDep1 = $ticket->getKpiPaDep1();
        $this->kpiPaDep2 = $ticket->getKpiPaDep2();
        $this->kpiPaDep3 = $ticket->getKpiPaDep3();
        $endstamp = $this->getEnd()->getTimestamp();
        $beginstamp = $this->getBegin()->getTimestamp();
        $this->intervals = ($endstamp - $beginstamp) / 900;
    }

    public function getIntervalCount(): int
    {
        $endstamp = $this->getEnd()->getTimestamp();
        $beginstamp = $this->getBegin()->getTimestamp();

        return (int)(($endstamp - $beginstamp) / 900);
    }

    public function isReplaceEnergy(): ?bool
    {
        return $this->replaceEnergy;
    }

    public function setReplaceEnergy(?bool $replaceEnergy): self
    {
        $this->replaceEnergy = $replaceEnergy;

        return $this;
    }

    public function isReplaceIrr(): ?bool
    {
        return $this->replaceIrr;
    }

    public function setReplaceIrr(?bool $replaceIrr): self
    {
        $this->replaceIrr = $replaceIrr;

        return $this;
    }

    public function isUseHour(): ?bool
    {
        return $this->useHour;
    }

    public function setUseHour(?bool $useHour): self
    {
        $this->useHour = $useHour;

        return $this;
    }

    public function getValueEnergy(): ?string
    {
        return $this->valueEnergy;
    }

    public function setValueEnergy(?string $valueEnergy): self
    {
        $this->valueEnergy = $valueEnergy;

        return $this;
    }

    public function getValueIrr(): ?string
    {
        return $this->valueIrr;
    }

    public function setValueIrr(?string $valueIrr): self
    {
        $this->valueIrr = $valueIrr;

        return $this;
    }

    public function getCorrectEnergyValue(): ?string
    {
        return $this->correctEnergyValue;
    }

    public function setCorrectEnergyValue(?string $correctEnergyValue): self
    {
        $this->correctEnergyValue = $correctEnergyValue;

        return $this;
    }

    public function getReasonText(): ?string
    {
        return $this->reasonText;
    }

    public function setReasonText(?string $reasonText): self
    {
        $this->reasonText = $reasonText;

        return $this;
    }

    public function getPRExcludeMethod(): ?string
    {
        return $this->PRExcludeMethod;
    }

    public function setPRExcludeMethod(?string $PRExcludeMethod): self
    {
        $this->PRExcludeMethod = $PRExcludeMethod;

        return $this;
    }

    public function getBeginHidden(): ?\DateTimeInterface
    {
        return $this->beginHidden;
    }

    public function setBeginHidden(?\DateTimeInterface $beginHidden): self
    {
        $this->beginHidden = $beginHidden;

        return $this;
    }

    public function getEndHidden(): ?\DateTimeInterface
    {
        return $this->endHidden;
    }

    public function setEndHidden(?\DateTimeInterface $endHidden): self
    {
        $this->endHidden = $endHidden;

        return $this;
    }

    public function getSensors(): ?string
    {
        return $this->sensors;
    }

    public function setSensors(?string $sensors): static
    {
        $this->sensors = $sensors;

        return $this;
    }


}
