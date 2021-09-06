<?php


namespace App\Service;


use App\Helper\G4NTrait;

class DummySollService
{
    use G4NTrait;

    public function createDummySoll($from = null)
    {
        $conn = self::getPdoConnection();
        // Update Dummy DBs
        $output = "Start Dummy Data\n";
        if (! $from) {
            $currentTime = self::getCetTime();
            $start = $currentTime - ($currentTime % 900) - 3600;
            $end = $currentTime;
        } else {
            $start = $currentTime = strtotime(date('Y-m-d 00:00:00', $from));
            $end = $currentTime = strtotime(date('Y-m-d 23:45:00', $from));
        }

        $output .= "From: " . date('Y-m-d H:i', $start) . " to: " . date('Y-m-d H:i', $end);

        for ($i = $start; $i <= $end; $i += 900) {
            $SQLDate = date("Y-m-d H:i:00", $i);
            $conn->exec("INSERT IGNORE INTO db_dummysoll SET anl_intnr = 'dummy', stamp = '$SQLDate';");
        }

        $conn = null;

        return $output;
    }
}