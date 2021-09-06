<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PvpUserlog
 *
 * @ORM\Table(name="pvp_userlog")
 * @ORM\Entity
 */
class PvpUserlog
{
    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="eigner_id", type="string", length=25, nullable=false)
     */
    private $eignerId;

    /**
     * @var string
     *
     * @ORM\Column(name="login_ip", type="string", length=25, nullable=false)
     */
    private $loginIp;

    /**
     * @var string
     *
     * @ORM\Column(name="online", type="string", length=5, nullable=false, options={"default"="1"})
     */
    private $online = '1';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logtime", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $logtime = 'CURRENT_TIMESTAMP';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logout", type="datetime", nullable=false, options={"default"="0000-00-00 00:00:00"})
     */
    private $logout = '0000-00-00 00:00:00';

    /**
     * @var string|null
     *
     * @ORM\Column(name="pvp_userlogcol", type="string", length=45, nullable=true)
     */
    private $pvpUserlogcol;

    public function getUserId(): ?string
    {
        return $this->userId;
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

    public function getLoginIp(): ?string
    {
        return $this->loginIp;
    }

    public function setLoginIp(string $loginIp): self
    {
        $this->loginIp = $loginIp;

        return $this;
    }

    public function getOnline(): ?string
    {
        return $this->online;
    }

    public function setOnline(string $online): self
    {
        $this->online = $online;

        return $this;
    }

    public function getLogtime(): ?\DateTimeInterface
    {
        return $this->logtime;
    }

    public function setLogtime(\DateTimeInterface $logtime): self
    {
        $this->logtime = $logtime;

        return $this;
    }

    public function getLogout(): ?\DateTimeInterface
    {
        return $this->logout;
    }

    public function setLogout(\DateTimeInterface $logout): self
    {
        $this->logout = $logout;

        return $this;
    }

    public function getPvpUserlogcol(): ?string
    {
        return $this->pvpUserlogcol;
    }

    public function setPvpUserlogcol(?string $pvpUserlogcol): self
    {
        $this->pvpUserlogcol = $pvpUserlogcol;

        return $this;
    }


}
