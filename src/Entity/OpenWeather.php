<?php

namespace App\Entity;

use App\Repository\OpenWeatherRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OpenWeatherRepository::class)
 */
class OpenWeather
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $stamp;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $tempC;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $iconWeather;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="json")
     */
    private $data = [];

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="openWeather")
     */
    private $anlage;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStamp(): ?string
    {
        return $this->stamp;
    }

    public function setStamp(string $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    public function getTempC(): ?string
    {
        return $this->tempC;
    }

    public function setTempC(string $tempC): self
    {
        $this->tempC = $tempC;

        return $this;
    }

    public function getIconWeather(): ?string
    {
        return $this->iconWeather;
    }

    public function setIconWeather(string $iconWeather): self
    {
        $this->iconWeather = $iconWeather;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
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
}
