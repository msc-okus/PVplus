<?php


namespace App\Message\Command;

use DateTime;

class GenerateTickets
{
    private int $anlageId;

    private DateTime $startDate;

    private DateTime $endDate;

    private int $logId;

    public function __construct(int $anlageId, DateTime $startDate, DateTime $endDate, int $logId)
    {
        $this->anlageId = $anlageId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->logId = $logId;
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