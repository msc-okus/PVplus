<?php

namespace App\Service;

class PVSystService
{
    public function __construct()
    {
    }

    public function normalizeDate($date)
    {
        return '20'.substr((string) $date, 6, 2).'-'.substr((string) $date, 3, 2).'-'.substr((string) $date, 0, 2).' '.substr((string) $date, 9, 5);
    }
}
