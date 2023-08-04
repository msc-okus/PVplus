<?php

namespace App\Message\Command;

use DateTime;

class ImportData
{
    public function __construct(
        private object $anlage,
        private int $anlageId,
        private DateTime $startDate,
        private DateTime $endDate,
        private string $path,
        private string $importType,
        private int $logId,
        private array $readyToImport
    )
    {
    }

    public function getAnlage(): object
    {
        return $this->anlage;
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

    public function getReadyToImport(): array
    {
        return $this->readyToImport;
    }
}