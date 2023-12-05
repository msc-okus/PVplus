<?php

namespace App\Service\Import;

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
     * @param $anlage
     * @param $file
     * @return void
     */
    public function import($anlage, $file): string
    {
        $output = "";
        if ($file && $file->getMimeType() === 'text/plain') {
            $fileStream = fopen($file->getPathname(), 'r');
            for ($n = 1; $n <= 10; $n++) {
                fgetcsv($fileStream, null, ';');
            }
            $headline = fgetcsv($fileStream, null, ';');
            $units = fgetcsv($fileStream, null, ';');
            $keyStamp       = array_search('date', $headline);
            $keyEGrid       = array_search('E_Grid', $headline);
            $keyGlobHor     = array_search('GlobHor', $headline);
            $keyGlobEff     = array_search('GlobEff', $headline);
            $keyGlobInc     = array_search('GlobInc', $headline);
            $keyEGrid       = array_search('E_Grid', $headline);
            dump($headline, $units);

            // leerzeile überspringen
            $row = fgetcsv($fileStream, null, ';');

            while ($row = fgetcsv($fileStream, null, ';')){
                $timeZone = null; //new \DateTimeZone('UTC');
                $stamp = date_create_from_format('d/m/y H:i', $row[$keyStamp], $timeZone);
                if ($stamp->format('I') == '1') {
                    $stamp->add(New \DateInterval('PT3600S'));
                }
                $eGrid  = (float)$row[$keyEGrid]   > 0 ? $this->correctUnitPower($units[$keyEGrid], $row[$keyEGrid]) : 0;
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
                }
                $pvSyst
                    ->setIrrGlobalHor($irrHor)
                    ->setIrrGlobalInc($irrInc)
                    ->setTempAmbiant($irrEff)
                    ->setElectricityGrid($eGrid)
                    ->setElectricityInverterOut('')
                ;
                $this->em->persist($pvSyst);
                $output .= $anlage->getAnlId() ." | ".$stamp->format('Y-m-d H:i')." | ".$eGrid."<br>";
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