<?php

namespace App\Service;

use App\Helper\G4NTrait;

class GetPdoService
{
    use G4NTrait;

    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private $userPlant,
        private $passwordPlant
    )
    {
    }

    public function getSPdo()
    {
        return(self::getPdoConnection($this->host, $this->userPlant, $this->passwordPlant));

    }
}