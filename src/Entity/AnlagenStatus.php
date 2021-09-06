<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * PvpAnlagenStatus
 *
 * @ORM\Table(name="pvp_anlagen_status", indexes={@ORM\Index(name="stamp", columns={"stamp"}), @ORM\Index(name="anlage_id", columns={"anlage_id"})}, uniqueConstraints={@ORM\UniqueConstraint(name="unique_key", columns={"unique_key"})})
 * @ORM\Entity(repositoryClass="App\Repository\AnlagenStatusRepository")
 */
class AnlagenStatus
{
    /**
     * @var int
     * @ORM\Column(name="id", type="bigint", nullable=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * * @ORM\Column(name="unique_key", type="string", length=40, nullable=false, unique=true)
     */
    private $uniqueKey;

    /**
     * @var int
     *
     * @ORM\Column(name="anlage_id", type="bigint", nullable=false)
     */
    private $anlageId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="stamp", type="datetime", nullable=false)
     */
    private $stamp;

    /**
     * @var int|null
     *
     * @ORM\Column(name="anlagen_status", type="integer", nullable=true)
     */
    private $anlagenStatus = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="eigner_id", type="bigint", nullable=false)
     */
    private $eignerId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_data_io", type="datetime", nullable=false)
     */
    private $lastDataIo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_data_status", type="string", length=20, nullable=true, options={"default"="normal"})
     */
    private $lastDataStatus = 'normal';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_weather_io", type="datetime", nullable=false)
     */
    private $lastWeatherIo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_weather_status", type="string", length=20, nullable=true, options={"default"="normal"})
     */
    private $lastWeatherStatus = 'normal';

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="act_stamp", type="datetime", nullable=true)
     */
    private $actStamp;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="exp_stamp", type="datetime", nullable=true)
     */
    private $expStamp;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_act_all", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $acActAll;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_exp_all", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $acExpAll;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_diff_all", type="decimal", precision=10, scale=0, nullable=false)
     */
    private $acDiffAll;

    /**
     * @var string
     *
     * @ORM\Column(name="dc_act_all", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $dcActAll;

    /**
     * @var string
     *
     * @ORM\Column(name="dc_exp_all", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $dcExpAll;

    /**
     * @var string
     *
     * @ORM\Column(name="dc_diff_all", type="decimal", precision=10, scale=0, nullable=false)
     */
    private $dcDiffAll;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="stamp_last_both", type="datetime", nullable=true)
     */
    private $stampLastBoth;

    /**
     * @var int|null
     *
     * @ORM\Column(name="ac_error_code", type="integer", nullable=true)
     */
    private $acErrorCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ac_diff_status", type="string", length=20, nullable=true, options={"default"="normal"})
     */
    private $acDiffStatus = 'normal';

    /**
     * @var string|null
     *
     * @ORM\Column(name="ac_act_both", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $acActBoth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ac_exp_both", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $acExpBoth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ac_lost_percent", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $acLostPercent;

    /**
     * @var int|null
     *
     * @ORM\Column(name="dc_error_code", type="integer", nullable=true)
     */
    private $dcErrorCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dc_diff_status", type="string", length=20, nullable=true, options={"default"="normal"})
     */
    private $dcDiffStatus = 'normal';

    /**
     * @var string|null
     *
     * @ORM\Column(name="dc_act_both", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $dcActBoth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dc_exp_both", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $dcExpBoth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dc_lost_percent", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $dcLostPercent;

    /**
     * @var int|null
     *
     * @ORM\Column(name="string_i_warnings", type="integer", nullable=true)
     */
    private $stringIWarnings;

    /**
     * @var int|null
     *
     * @ORM\Column(name="string_i_alerts", type="integer", nullable=true)
     */
    private $stringIAlerts;

    /**
     * @var int
     *
     * @ORM\Column(name="string_i_score", type="integer", nullable=false)
     */
    private $stringIScore;

    /**
     * @var string|null
     *
     * @ORM\Column(name="string_i_status", type="string", length=20, nullable=true)
     */
    private $stringIStatus;

    /**
     * @var int|null
     *
     * @ORM\Column(name="string_u_warnings", type="integer", nullable=true)
     */
    private $stringUWarnings;

    /**
     * @var int|null
     *
     * @ORM\Column(name="string_u_alerts", type="integer", nullable=true)
     */
    private $stringUAlerts;

    /**
     * @var int
     *
     * @ORM\Column(name="string_u_score", type="integer", nullable=false)
     */
    private $stringUScore;

    /**
     * @var string|null
     *
     * @ORM\Column(name="string_u_status", type="string", length=20, nullable=true)
     */
    private $stringUStatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dc_status", type="string", length=20, nullable=true)
     */
    private $dcStatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="string_error_messages", type="text", length=65535, nullable=true)
     */
    private $stringErrorMessages;

    /**
     * @var int|null
     *
     * @ORM\Column(name="inv_score", type="integer", nullable=true)
     */
    private $invScore;

    /**
     * @var int|null
     *
     * @ORM\Column(name="inv_anz", type="integer", nullable=true)
     */
    private $invAnz;

    /**
     * @var int|null
     *
     * @ORM\Column(name="inv_anz_warning", type="integer", nullable=true)
     */
    private $invAnzWarning;

    /**
     * @var int|null
     *
     * @ORM\Column(name="inv_anz_alert", type="integer", nullable=true)
     */
    private $invAnzAlert;

    /**
     * @var string|null
     *
     * @ORM\Column(name="inv_error_message", type="text", length=65535, nullable=true)
     */
    private $invErrorMessage;

    /**
     * @var string|null
     *
     * @ORM\Column(name="inv_status", type="string", length=20, nullable=true)
     */
    private $invStatus;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="status")
     */
    private $anlage;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $forecastDivYear = '';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $forecastDivMinusYear = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $forecastDivPlusYear = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $forecastDivPac = '';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $forecastDivMinusPac = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $forecastDivPlusPac = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $forecastDate = '';

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUniqueKey(): ?string
    {
        return $this->uniqueKey;
    }

    public function setUniqueKey(string $uniqueKey): self
    {
        $this->uniqueKey = $uniqueKey; //$this->getAnlageId() . "_" . $this->getStamp();

        return $this;
    }

    public function getAnlageId(): ?string
    {
        return $this->anlageId;
    }

    public function setAnlId(string $anlageId): self
    {
        $this->anlageId = $anlageId;

        return $this;
    }

    public function getStamp(): ?\DateTimeInterface
    {
        return $this->stamp;
    }
    public function setStamp(\DateTimeInterface $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    public function getAnlagenStatus(): ?int
    {
        return $this->anlagenStatus;
    }

    public function setAnlagenStatus(?int $anlagenStatus): self
    {
        $this->anlagenStatus = $anlagenStatus;

        return $this;
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

    public function getLastDataIo(): ?\DateTimeInterface
    {
        return $this->lastDataIo;
    }

    public function setLastDataIo(\DateTimeInterface $lastDataIo): self
    {
        $this->lastDataIo = $lastDataIo;

        return $this;
    }

    public function getLastDataStatus(): ?string
    {
        return $this->lastDataStatus;
    }

    public function setLastDataStatus(?string $lastDataStatus): self
    {
        $this->lastDataStatus = $lastDataStatus;

        return $this;
    }

    public function getLastWeatherIo(): ?\DateTimeInterface
    {
        return $this->lastWeatherIo;
    }

    public function setLastWeatherIo(\DateTimeInterface $lastWeatherIo): self
    {
        $this->lastWeatherIo = $lastWeatherIo;

        return $this;
    }

    public function getLastWeatherStatus(): ?string
    {
        return $this->lastWeatherStatus;
    }

    public function setLastWeatherStatus(?string $lastWeatherStatus): self
    {
        $this->lastWeatherStatus = $lastWeatherStatus;

        return $this;
    }

    public function getActStamp(): ?\DateTimeInterface
    {
        return $this->actStamp;
    }

    public function setActStamp(?\DateTimeInterface $actStamp): self
    {
        $this->actStamp = $actStamp;

        return $this;
    }

    public function getExpStamp(): ?\DateTimeInterface
    {
        return $this->expStamp;
    }

    public function setExpStamp(?\DateTimeInterface $expStamp): self
    {
        $this->expStamp = $expStamp;

        return $this;
    }

    public function getAcActAll(): ?string
    {
        return $this->acActAll;
    }

    public function setAcActAll(string $acActAll): self
    {
        $this->acActAll = $acActAll;

        return $this;
    }

    public function getAcExpAll(): ?string
    {
        return $this->acExpAll;
    }

    public function setAcExpAll(string $acExpAll): self
    {
        $this->acExpAll = $acExpAll;

        return $this;
    }

    public function getAcDiffAll(): ?string
    {
        return $this->acDiffAll;
    }

    public function setAcDiffAll(string $acDiffAll): self
    {
        $this->acDiffAll = $acDiffAll;

        return $this;
    }

    public function getDcActAll(): ?string
    {
        return $this->dcActAll;
    }

    public function setDcActAll(string $dcActAll): self
    {
        $this->dcActAll = $dcActAll;

        return $this;
    }

    public function getDcExpAll(): ?string
    {
        return $this->dcExpAll;
    }

    public function setDcExpAll(string $dcExpAll): self
    {
        $this->dcExpAll = $dcExpAll;

        return $this;
    }

    public function getDcDiffAll(): ?string
    {
        return $this->dcDiffAll;
    }

    public function setDcDiffAll(string $dcDiffAll): self
    {
        $this->dcDiffAll = $dcDiffAll;

        return $this;
    }

    public function getStampLastBoth(): ?\DateTimeInterface
    {
        return $this->stampLastBoth;
    }

    public function setStampLastBoth(?\DateTimeInterface $stampLastBoth): self
    {
        $this->stampLastBoth = $stampLastBoth;

        return $this;
    }

    public function getAcErrorCode(): ?int
    {
        return $this->acErrorCode;
    }

    public function setAcErrorCode(?int $acErrorCode): self
    {
        $this->acErrorCode = $acErrorCode;

        return $this;
    }

    public function getAcDiffStatus(): ?string
    {
        return $this->acDiffStatus;
    }

    public function setAcDiffStatus(?string $acDiffStatus): self
    {
        $this->acDiffStatus = $acDiffStatus;

        return $this;
    }

    public function getAcActBoth(): ?string
    {
        return $this->acActBoth;
    }

    public function setAcActBoth(?string $acActBoth): self
    {
        $this->acActBoth = $acActBoth;

        return $this;
    }

    public function getAcExpBoth(): ?string
    {
        return $this->acExpBoth;
    }

    public function setAcExpBoth(?string $acExpBoth): self
    {
        $this->acExpBoth = $acExpBoth;

        return $this;
    }

    public function getAcLostPercent(): ?string
    {
        return $this->acLostPercent;
    }

    public function setAcLostPercent(?string $acLostPercent): self
    {
        $this->acLostPercent = $acLostPercent;

        return $this;
    }

    public function getDcErrorCode(): ?int
    {
        return $this->dcErrorCode;
    }

    public function setDcErrorCode(?int $dcErrorCode): self
    {
        $this->dcErrorCode = $dcErrorCode;

        return $this;
    }

    public function getDcDiffStatus(): ?string
    {
        return $this->dcDiffStatus;
    }

    public function setDcDiffStatus(?string $dcDiffStatus): self
    {
        $this->dcDiffStatus = $dcDiffStatus;

        return $this;
    }

    public function getDcActBoth(): ?string
    {
        return $this->dcActBoth;
    }

    public function setDcActBoth(?string $dcActBoth): self
    {
        $this->dcActBoth = $dcActBoth;

        return $this;
    }

    public function getDcExpBoth(): ?string
    {
        return $this->dcExpBoth;
    }

    public function setDcExpBoth(?string $dcExpBoth): self
    {
        $this->dcExpBoth = $dcExpBoth;

        return $this;
    }

    public function getDcLostPercent(): ?string
    {
        return $this->dcLostPercent;
    }

    public function setDcLostPercent(?string $dcLostPercent): self
    {
        $this->dcLostPercent = $dcLostPercent;

        return $this;
    }

    public function getStringIWarnings(): ?int
    {
        return $this->stringIWarnings;
    }

    public function setStringIWarnings(?int $stringIWarnings): self
    {
        $this->stringIWarnings = $stringIWarnings;

        return $this;
    }

    public function getStringIAlerts(): ?int
    {
        return $this->stringIAlerts;
    }

    public function setStringIAlerts(?int $stringIAlerts): self
    {
        $this->stringIAlerts = $stringIAlerts;

        return $this;
    }

    public function getStringIScore(): ?int
    {
        return $this->stringIScore;
    }

    public function setStringIScore(int $stringIScore): self
    {
        $this->stringIScore = $stringIScore;

        return $this;
    }

    public function getStringIStatus(): ?string
    {
        return $this->stringIStatus;
    }

    public function setStringIStatus(?string $stringIStatus): self
    {
        $this->stringIStatus = $stringIStatus;

        return $this;
    }

    public function getStringUWarnings(): ?int
    {
        return $this->stringUWarnings;
    }

    public function setStringUWarnings(?int $stringUWarnings): self
    {
        $this->stringUWarnings = $stringUWarnings;

        return $this;
    }

    public function getStringUAlerts(): ?int
    {
        return $this->stringUAlerts;
    }

    public function setStringUAlerts(?int $stringUAlerts): self
    {
        $this->stringUAlerts = $stringUAlerts;

        return $this;
    }

    public function getStringUScore(): ?int
    {
        return $this->stringUScore;
    }

    public function setStringUScore(int $stringUScore): self
    {
        $this->stringUScore = $stringUScore;

        return $this;
    }

    public function getStringUStatus(): ?string
    {
        return $this->stringUStatus;
    }

    public function setStringUStatus(?string $stringUStatus): self
    {
        $this->stringUStatus = $stringUStatus;

        return $this;
    }

    public function getDcStatus(): ?string
    {
        return $this->dcStatus;
    }

    public function setDcStatus(?string $dcStatus): self
    {
        $this->dcStatus = $dcStatus;

        return $this;
    }

    public function getStringErrorMessages(): ?string
    {
        return $this->stringErrorMessages;
    }

    public function setStringErrorMessages(?string $stringErrorMessages): self
    {
        $this->stringErrorMessages = $stringErrorMessages;

        return $this;
    }

    public function getInvScore(): ?int
    {
        return $this->invScore;
    }

    public function setInvScore(?int $invScore): self
    {
        $this->invScore = $invScore;

        return $this;
    }

    public function getInvAnz(): ?int
    {
        return $this->invAnz;
    }

    public function setInvAnz(?int $invAnz): self
    {
        $this->invAnz = $invAnz;

        return $this;
    }

    public function getInvAnzWarning(): ?int
    {
        return $this->invAnzWarning;
    }

    public function setInvAnzWarning(?int $invAnzWarning): self
    {
        $this->invAnzWarning = $invAnzWarning;

        return $this;
    }

    public function getInvAnzAlert(): ?int
    {
        return $this->invAnzAlert;
    }

    public function setInvAnzAlert(?int $invAnzAlert): self
    {
        $this->invAnzAlert = $invAnzAlert;

        return $this;
    }

    public function getInvErrorMessage(): ?string
    {
        return $this->invErrorMessage;
    }

    public function setInvErrorMessage(?string $invErrorMessage): self
    {
        $this->invErrorMessage = $invErrorMessage;

        return $this;
    }

    public function getInvStatus(): ?string
    {
        return $this->invStatus;
    }

    public function setInvStatus(?string $invStatus): self
    {
        $this->invStatus = $invStatus;

        return $this;
    }

    public function __toString()
    {
        $help = $this->stamp-formatTimeStampToSql();
        return $help;
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

    public function getForecastYear(): ?string
    {
        return $this->forecastDivYear;
    }

    public function setForecastYear(string $forecast): self
    {
        $this->forecastDivYear = $forecast;

        return $this;
    }

    public function getForecastDivMinusYear(): ?string
    {
        return $this->forecastDivMinusYear;
    }

    public function setForecastDivMinusYear(string $forecastDivMinus): self
    {
        $this->forecastDivMinusYear = $forecastDivMinus;

        return $this;
    }

    public function getForecastDivPlusYear(): ?string
    {
        return $this->forecastDivPlusYear;
    }

    public function setForecastDivPlusYear(string $forecastDivPlus): self
    {
        $this->forecastDivPlusYear = $forecastDivPlus;

        return $this;
    }
    public function getForecastPac(): ?string
    {
        return $this->forecastDivPac;
    }

    public function setForecastPac(string $forecast): self
    {
        $this->forecastDivPac = $forecast;

        return $this;
    }

    public function getForecastDivMinusPac(): ?string
    {
        return $this->forecastDivMinusPac;
    }

    public function setForecastDivMinusPac(string $forecastDivMinus): self
    {
        $this->forecastDivMinusPac = $forecastDivMinus;

        return $this;
    }

    public function getForecastDivPlusPac(): ?string
    {
        return $this->forecastDivPlusPac;
    }

    public function setForecastDivPlusPac(string $forecastDivPlus): self
    {
        $this->forecastDivPlusPac = $forecastDivPlus;

        return $this;
    }

    public function getForecastDate(): ?\DateTimeInterface
    {
        return $this->forecastDate;
    }
    public function setForecastDate(\DateTimeInterface $stamp): self
    {
        $this->forecastDate = $stamp;

        return $this;
    }

}
