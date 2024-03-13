<?php

namespace App\Message\Command;

use DateTime;
use phpseclib3\Math\PrimeField\Integer;

class AnlageStringAssignment
{
    public function __construct(
        private readonly int $anlageId,
        private readonly int $year,
        private readonly int $month,
        private readonly string $currentUserName,
        private readonly string $publicDirectory,
        private readonly int $logId,)
    {
    }

    public function getAnlageId(): int
    {
        return $this->anlageId;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getCurrentUserName(): string
    {
        return $this->currentUserName;
    }

    public function getPulicDirectory(): string
    {
        return $this->publicDirectory;
    }

    public function getlogId(): int
    {
        return $this->logId;
    }
}
