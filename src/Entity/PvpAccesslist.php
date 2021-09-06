<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PvpAccesslist
 *
 * @ORM\Table(name="pvp_accesslist", indexes={@ORM\Index(name="id", columns={"id"})})
 * @ORM\Entity
 */
class PvpAccesslist
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="eigner_id", type="bigint", nullable=false)
     */
    private $eignerId;

    /**
     * @var Int
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=false)
     */
    private $userId;

    public function getId(): ?string
    {
        return $this->id;
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

    public function getUser(): ?String
    {
        return $this->userId;
    }

    public function setUser(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }


}
