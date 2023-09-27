<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;

/**
 * PvpAccesslist.
 */
#[ORM\Table(name: 'pvp_accesslist')]
#[ORM\Index(name: 'id', columns: ['id'])]
#[ORM\Entity]
#[Deprecated]
class PvpAccesslist
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'bigint', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'eigner_id', type: 'bigint', nullable: false)]
    private ?string $eignerId = null;

    #[ORM\Column(name: 'user_id', type: 'bigint', nullable: false)]
    private ?string $userId = null;

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

    public function getUser(): ?string
    {
        return $this->userId;
    }

    public function setUser(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
