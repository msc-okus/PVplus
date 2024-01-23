<?php

namespace App\Service\Forecast;

use App\Helper\G4NTrait;
use League\Flysystem\Filesystem;

class DatFileReaderService  {
    private readonly string $delimiter;
    private readonly string $rowDelimiter;
    private $fileHandle = null;
    private int $position = 3;
    private array $data = [];

    use G4NTrait;


    public function __construct(
        private readonly Filesystem $fileSystemFtp,
    )
    {
        $position = 0;
        $delimiter = ",";
        $rowDelimiter = "r";
        $dir = 'metodat';


        $this->delimiter = $delimiter;
        $this->rowDelimiter = $rowDelimiter;
        $this->position = $position;
        $this->dir = $dir;
    }

    public function read($filename) {
            $ftplink = $this->dir.'/'.$filename;

        if ($this->fileSystemFtp->fileExists($ftplink)) {
            $resourcedata = $this->fileSystemFtp->read($ftplink);
            $tmpfile = tempnam(sys_get_temp_dir(), '~g4n'); // Erstellt ein Tmp. file
            $handle = fopen($tmpfile, "w");
            fwrite($handle,  $resourcedata);
            fclose($handle);
            $this->fileHandle = fopen($tmpfile, $this->rowDelimiter);
            if ($this->fileHandle === FALSE) {
                throw new \Exception("Unable to open file: {$filename}");
            } else {
                $this->parseLine();
            }
          } else {
            return false;
        }
    }
//
    public function __destruct()  {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }
//
    private function parseLine()  {
        $this->data = [];
        while (!feof($this->fileHandle)) {
            $line = utf8_encode(fgets($this->fileHandle));
            $this->data[] = str_getcsv($line, $this->delimiter);
        }
    }
//
    public function current() {
        $out = [];

        foreach ($this->data as $key => $value) {
            if ($key > $this->position) {

                (array_key_exists(4,$value)) ? $gh = (int)$value[4] : $gh = "0.0";
                (array_key_exists(5,$value)) ? $dh = (int)$value[5] : $dh = "0.0";
                (array_key_exists(8,$value)) ? $ta = (int)$value[8] : $ta = "0.0";
                (array_key_exists(11,$value)) ? $ff = (int)$value[11] : $ff = "0.0";

                if ($gh !="NN" and $dh != "NN"){
                    $gdir = $gh - $dh;
                } else {
                    $gdir = "NN";
                }
                if (array_key_exists(3,$value)) {
                    if ((int)$value[3] == 24){ $h = 0;  } else {$h = (int)$value[3]; };
                } else {
                    $h = "NN";
                }
                if ($h != "NN") {
                    $y = trim((string) $value[0]);
                    $m = trim((string) $value[1]);
                    $d = trim((string) $value[2]);
                    $gd = str_pad($d, 2, "0", STR_PAD_LEFT);
                    $gm = str_pad($m, 2, "0", STR_PAD_LEFT);
                    $datekey = "$y$gm$gd";
                    $orderdate = date('Y-m-d-z', strtotime(trim($datekey)));
                    [$year, $month, $day, $dayofyear] = explode("-", $orderdate);
                    $doy = $dayofyear + 1;
                    $out[$doy][$h] = ['m' => $month, 'd' => $day, 'h' => $h, 'gdir' => $gdir, 'gh' => $gh, 'dh' => $dh, 'ta' => $ta, 'ff' => $ff];
                }
            }
        }
        return $out;
    }
}