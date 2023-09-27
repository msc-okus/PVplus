<?php

namespace App\Message\Command;


class GenerateAMReport
{
    public function __construct(
        private readonly int $anlageId,
        private readonly string $month,
        private readonly string $year,
        private readonly ?string $userId, // e-mail
        private readonly int $logId
    )
    {
    }

    public function getAnlageId(): int
    {
        return $this->anlageId;
    }

    public function getMonth(): string
    {
        return $this->month;
    }

    public function getYear(): string
    {
        return $this->year;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getlogId(): int
    {
        return $this->logId;
    }
}