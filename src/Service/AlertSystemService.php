<?php

namespace App\Service;

use App\Repository\AnlagenRepository;

class AlertSystemService
{
    private AnlagenRepository $anlagenRepository;

    public function __construct(AnlagenRepository $anlagenRepository){
        $this->anlagenRepository = $anlagenRepository;
    }

    public function CheckSystem(){

        foreach ($this->anlagenRepository->findAll() as $anlage){
            //check if there is data in the db
            $sql = "select * from ".$anlage->getAnlDbIst()." where ";


        }
    }
}