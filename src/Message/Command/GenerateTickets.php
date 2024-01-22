<?php

namespace App\Message\Command;

use DateTime;

class GenerateTickets
{
    public function __construct(
        private readonly int $anlageId,
        private readonly DateTime $startDate,
        private readonly DateTime $endDate,
        private readonly int $logId
    )
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