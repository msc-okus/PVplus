<?php

namespace App\Message\Command;


class GenerateEpcReportPRNew
{
    public function __construct(
        private readonly int $anlageId,
        private readonly \DateTime $reportDate,
        private readonly ?string $userId, // e-mail
        private readonly int $logId
    )
    {
    }

    public function getAnlageId(): int
    {
        return $this->anlageId;
    }

    public function getReportDate(): \DateTime
    {
        return $this->reportDate;
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