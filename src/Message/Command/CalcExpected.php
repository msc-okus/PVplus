<?php

namespace App\Message\Command;

use DateTime;

class CalcExpected
{
    public function __construct(
        private int $anlageId,
        private DateTime $startDate,
        private DateTime $endDate,
        private int $logId)
    {
    }

    public function getAnlageId(): int
    {
        return $this->anlageId;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function getlogId(): int
    {
        return $this->logId;
    }
}
