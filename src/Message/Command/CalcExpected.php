<?php

namespace App\Message\Command;

use DateTime;

class CalcExpected
{

    private Int $anlageId;
    private DateTime $startDate;
    private DateTime $endDate;
    private int $logId;

    public function __construct(Int $anlageId, DateTime $startDate, DateTime $endDate, int $logId){

        $this->anlageId = $anlageId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->logId = $logId;
    }

    /**
     * @return Int
     */
    public function getAnlageId(): Int
    {
        return $this->anlageId;
    }

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * @return DateTime
     */
    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    /**
     * @return Int
     */
    public function getlogId(): Int
    {
        return $this->logId;
    }

}

