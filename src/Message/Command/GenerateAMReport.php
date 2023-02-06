<?php


namespace App\Message\Command;

use DateTime;

class GenerateAMReport
{

    public function __construct(
        private int $anlageId,
        private string $month,
        private string $year,
        private int $logId
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

    public function getlogId(): int
    {
        return $this->logId;
    }
}