<?php

namespace App\Message\Command;

use App\Entity\Anlage;

class CalcExpected
{

    private Int $anlageId;
    private \DateTime $startDate;
    private \DateTime $endDate;

    public function __construct(Int $anlageId, \DateTime $startDate, \DateTime $endDate){

        $this->anlageId = $anlageId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return Int
     */
    public function getAnlageId(): Int
    {
        return $this->anlageId;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }



}

