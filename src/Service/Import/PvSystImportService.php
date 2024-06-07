<?php

namespace App\Service\Import;

use App\Entity\Anlage;
use App\Entity\AnlagePVSystDaten;
use App\Repository\PVSystDatenRepository;
use Doctrine\ORM\EntityManagerInterface;

class PvSystImportService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PVSystDatenRepository $PVSystDatenRepo
    )
    {
    }

    /**
     * Importiere PV Syst Stunden Daten
     * @param Anlage $anlage
     * @param $fileStream
     * @param string $separator
     * @param string $dateFormat
     * @return string
     */
    public function import(Anlage $anlage, $fileStream, string $separator = ';', string $dateFormat = "d/m/y h:i"): string
    {
        $output = "";
        if ($fileStream ) { // && $file->getMimeType() === 'text/plain'
            for ($n = 1; $n <= 10; $n++) {
                fgetcsv($fileStream, null, $separator);
            }
            $headline = fgetcsv($fileStream, null, $separator);
            $units = fgetcsv($fileStream, null, $separator);
            $keyStamp       = array_search('date', $headline);
            $keyGlobHor     = array_search('GlobHor', $headline);
            $keyGlobEff     = array_search('GlobEff', $headline);
            $keyGlobInc     = array_search('GlobInc', $headline);
            $keyEGrid       = array_search('E_Grid', $headline);

            // leerzeile überspringen
            $row = fgetcsv($fileStream, null, $separator);
            $oldStamp = null;

            while ($row = fgetcsv($fileStream, null, $separator)){
                $timeZone = null; //new \DateTimeZone('UTC');
                $stamp = date_create_from_format($dateFormat, $row[$keyStamp], $timeZone);
                $stamp->add(new \DateInterval('PT3600S')); // move from UTC to local Time
                if ($stamp->format('I') == '1') {
                    $stamp->add(new \DateInterval('PT3600S')); // one more hour if we are in DLS
                }
                if ($oldStamp !== $stamp->format("Y-m-d H:i")) { // Zum Übersprinegn der doppelten Daten bei der umstellung auf DLS

                    // korrigiere Dezimal Trennung von ',' auf '.'
                    foreach ($row as $key => $value){
                        if ($key !== $keyStamp){ // nicht beim Datum anwenden
                            $row[$key]  = str_replace(',','.', $value);
                        }
                    }

                    $eGrid = (float)$row[$keyEGrid] > 0 ? $this->correctUnitPower($units[$keyEGrid], $row[$keyEGrid]) : 0;
                    $irrHor = (float)$row[$keyGlobHor] > 0 ? $this->correctUnitIrr($units[$keyGlobHor], $row[$keyGlobHor]) : 0;
                    $irrInc = (float)$row[$keyGlobInc] > 0 ? $this->correctUnitIrr($units[$keyGlobInc], $row[$keyGlobInc]) : 0;
                    $irrEff = (float)$row[$keyGlobEff] > 0 ? $this->correctUnitIrr($units[$keyGlobEff], $row[$keyGlobEff]) : 0;

                    $pvSyst = $this->PVSystDatenRepo->findOneBy(['anlage' => $anlage, 'stamp' => $stamp->format('Y-m-d H:i')]);
                    if ($pvSyst === null) {
                        $pvSyst = new AnlagePVSystDaten();
                        $pvSyst
                            ->setAnlage($anlage)
                            ->setStamp($stamp->format('Y-m-d H:i'))
                        ;
                        $this->em->persist($pvSyst);
                    }
                    $pvSyst
                        ->setIrrGlobalHor($irrHor)
                        ->setIrrGlobalInc($irrInc)
                        ->setIrrGlobalEff($irrEff)
                        ->setElectricityGrid($eGrid)
                    ;

                    $output .= $anlage->getAnlId() ." | ".$stamp->format('Y-m-d H:i')." | ".$eGrid."<br>";
                }
                $oldStamp = $stamp->format("Y-m-d H:i");
            }
            $this->em->flush();

            fclose($fileStream);

        }

        return $output;
    }

    private function correctUnitPower($unit, $value): float
    {
        return match ($unit) {
            'MW'    => $value * 1000,
            'W'     => $value / 1000,
            default => (float)$value,
        };

    }

    private function correctUnitIrr($unit, $value): float
    {
        return match ($unit) {
            'MW/m²'     => $value * 1000 * 1000,
            'kW/m²'     => $value * 1000,
            default     => (float)$value,
        };

    }
}