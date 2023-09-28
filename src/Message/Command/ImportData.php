<?php

namespace App\Message\Command;

use DateTime;

class ImportData
{
    public function __construct(
        private readonly int $anlageId,
        private readonly DateTime $startDate,
        private readonly DateTime $endDate,
        private readonly string $path,
        private readonly string $importType,
        private readonly int $logId,
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

    public function getPath(): string
    {
        return $this->path;
    }

    public function getImportType(): string
    {
        return $this->importType;
    }

    public function getlogId(): int
    {
        return $this->logId;
    }
}