<?php

namespace App\Service;

class PVSystService
{
    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private $userPlant,
        private $passwordPlant,)
    {
    }

    public function normalizeDate($date)
    {
        return '20'.substr($date, 6, 2).'-'.substr($date, 3, 2).'-'.substr($date, 0, 2).' '.substr($date, 9, 5);
    }
}
