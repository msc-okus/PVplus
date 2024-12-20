<?php

namespace App\Service;

use App\Helper\G4NTrait;
use App\Service\PdoService;


class DummySollService
{
    use G4NTrait;
    public function __construct(
        private readonly PdoService $pdoService,
    )
    {
    }
    public function createDummySoll($from = null): string
    {
        $conn = $this->pdoService->getPdoPlant();
        // Update Dummy DBs
        $output = "Start Dummy Data\n";
        if (!$from) {
            $currentTime = time();
            $start = $currentTime - ($currentTime % 900) - 3600;
            $end = $currentTime;
            $start = strtotime(date('Y-m-d 00:00:00', $currentTime));
            $end = strtotime(date('Y-m-d 23:45:00', $currentTime));
        } else {
            $start = strtotime(date('Y-m-d 00:00:00', $from));
            $end = strtotime(date('Y-m-d 23:45:00', $from));
        }

        $output .= 'From: '.date('Y-m-d H:i', $start).' to: '.date('Y-m-d H:i', $end);

        for ($i = $start; $i <= $end; $i += 900) {
            $SQLDate = date('Y-m-d H:i:00', $i);
            $conn->exec("INSERT IGNORE INTO db_dummysoll SET anl_intnr = 'dummy', stamp = '$SQLDate';");
        }

        $conn = null;

        return $output;
    }
}
