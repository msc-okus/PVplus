<?php

namespace App\Service;


use App\Entity\AnlagenReports;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use PDO;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Shuchkin\SimpleXLSX;
use Symfony\Component\HttpFoundation\Response;


class AnlageStringAssigmentService
{


    public function __construct(
        private PdoService $pdo,
        private readonly LogMessagesService $logMessages,
        private EntityManagerInterface $entityManager,
        private AnlagenRepository $anlagenRepository,
        private readonly Filesystem $fileSystemFtp,
        private ReportsRepository $reportsRepository

    )
    {
    }
    public function exportMontly($anlId,$year,$month,$currentUserName, $tableName,$logId): void
    {
       $this->logMessages->updateEntry($logId, 'working', 5);
        $sql_pvp_base = "
                        SELECT
                            ass.station_nr AS stationNr,
                            ass.inverter_nr AS inverterNr,
                            ass.string_nr AS stringNr,
                            groups.unit_first As unit,
                            ass.channel_nr AS channelNr,
                            ass.string_active AS stringActive,
                            ass.channel_cat AS channelCat,
                            ass.position,
                            ass.tilt,
                            ass.azimut,
                            `mod`.type AS moduleType,
                            ass.inverter_type AS inverterType,
                            `mod`.max_impp AS impp
                        FROM
                            anlage_string_assignment ass
                        INNER JOIN
                            anlage anl ON ass.anlage_id = anl.id
                        INNER JOIN
                            anlage_groups groups ON anl.id = groups.anlage_id
                        INNER JOIN
                            anlage_group_modules gm ON groups.id = gm.anlage_group_id
                        INNER JOIN
                            anlage_modules `mod` ON gm.module_type_id = `mod`.id
                        INNER JOIN
                            anlage_groups_ac acg ON groups.ac_group = acg.ac_group_id AND anl.id = acg.anlage_id
                        WHERE
                            anl.id = :anlId
                            AND CAST(ass.station_nr AS UNSIGNED) = CAST(acg.trafo_nr AS UNSIGNED)
                            AND CAST(ass.inverter_nr AS UNSIGNED) = groups.ac_group
                            AND (CAST(ass.string_nr AS UNSIGNED) + (CAST(ass.inverter_nr AS UNSIGNED) - 1) * 9) = groups.unit_first
                        ";

        $connection_pvp_base = $this->pdo->getPdoBase();
        $stmt = $connection_pvp_base->prepare($sql_pvp_base);
        $stmt->execute([':anlId' => $anlId]); // Corrigé pour utiliser un tableau associatif
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->logMessages->updateEntry($logId, 'working', 30);

        $dateX = new DateTime("$year-$month-01 00:00:00");
        $dateY = (clone $dateX)->modify('last day of this month')->setTime(23, 59, 59);


        $sql = "
       SELECT
           `group_ac` AS inverterNr ,
           `wr_num` AS stringNr,
           `channel` AS channelNr,
           AVG(`I_value`) AS `average_I_value`
       FROM `$tableName`
       WHERE `stamp` BETWEEN :startDateTime AND :endDateTime
       GROUP BY `group_ac`, `wr_num`, `channel`
        ";



        $params = [
            ':startDateTime' => $dateX->format('Y-m-d H:i:s'),
            ':endDateTime' => $dateY->format('Y-m-d H:i:s'),
        ];

        $connection = $this->pdo->getPdoStringBoxes();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);



        $resultsIndex = [];
        foreach ($results as $result) {
            $key = "{$result['inverterNr']}-{$result['stringNr']}-{$result['channelNr']}";
            $resultsIndex[$key] = $result['average_I_value'];
        }
       $this->logMessages->updateEntry($logId, 'working', 80);

        $joinedData = [];
        foreach ($assignments as $assignment) {
            $key = "{$assignment['inverterNr']}-{$assignment['unit']}-{$assignment['channelNr']}";
            if (isset($resultsIndex[$key])) {
                $impp = (float)$assignment['impp'];
                $average_I_value = (float)$resultsIndex[$key];


                if ($impp != 0) {
                    $assignment['avg'] = $average_I_value / $impp;
                } else {

                    $assignment['avg'] = null;
                }

                $joinedData[] = $assignment;
            }

        }
       $this->logMessages->updateEntry($logId, 'working', 90);

        $spreadsheet = new Spreadsheet();

        //remove active Sheet
        $active=$spreadsheet->getActiveSheetIndex();
        $spreadsheet->removeSheetByIndex($active);

        $this->prepareAndAddSortedSheets($spreadsheet, $joinedData);

        $publicDirectory='./excel/anlagestring/'.$tableName.'/';
      $this->generateAndSaveExcelFile($spreadsheet, $anlId,$month,$year,$currentUserName, $publicDirectory);
    }





    private function prepareAndAddSortedSheets($spreadsheet, $joinedData): void
    {
        $sortOptions = ['channelCat' => 'SortedBy_ChannelCat', 'position' => 'SortedBy_Position', 'tilt' => 'sortedBy_Tilt', 'azimut' => 'SortedBy_Azimut', 'moduleType' => 'SortedBy_moduleType', 'inverterType' => 'SortedBy_InverterType'];

        $best=[];
        $worst=[];
        $sheet1 = new Worksheet($spreadsheet, 'Best_summary_performer');
        $spreadsheet->addSheet($sheet1);

        $sheet2 = new Worksheet($spreadsheet, 'worst_summary_performer');
        $spreadsheet->addSheet($sheet2);

        foreach ($sortOptions as $sortBy => $sheetTitle) {
            $data=$this->prepareAndSortData($joinedData, $sortBy);
            foreach ($data['bestData'] as $item) {
                $item['sortBy'] = $sortBy;
                $best[] = $item;
            }
            foreach ($data['worstData'] as $item) {
                $item['sortBy'] = $sortBy;
                $worst[] = $item;
            }

            $sortedData = $data['sortedData'];
            $sheet = new Worksheet($spreadsheet, $sheetTitle);
            $spreadsheet->addSheet($sheet);
            $this->fillSheetWithData($sheet, $sortedData,'Performance');
            $this->colorizePerformanceRows($sheet, count($sortedData) + 1);
        }



        $this->fillSheetWithData($sheet1, $this->reduce($best),'sortBy');
        $this->fillSheetWithData($sheet2, $this->reduce($worst),'sortBy');
    }

    private function prepareAndSortData($data, $sortBy): array
    {
        $groupedData = array_reduce($data, function ($carry, $item) use ($sortBy) {
            $carry[$item[$sortBy]][] = $item;
            return $carry;
        }, []);

        $sortedData = [];
        $bestData=[];
        $worstData=[];
        array_walk($groupedData, function ($rows) use (&$sortedData,&$bestData,&$worstData) {
            usort($rows, function ($a, $b) { return $b['avg'] <=> $a['avg']; });
            $best = array_slice($rows, 0, 10);
            $worst = array_slice($rows, -10);

            $bestData = array_merge($bestData,$best);
            $worstData =  array_merge($worstData,$worst);

            array_walk($best, function (&$item) {  $item['Performance'] = 'Best'; });
            array_walk($worst, function (&$item) { $item['Performance'] = 'Worst'; });

            $sortedData = array_merge($sortedData, $best, $worst);

        });

        return ['sortedData'=>$sortedData, 'bestData'=>$bestData, 'worstData'=>$worstData];
    }

    private function reduce(array $a):array{
        // Reduce the array
        $reduced = [];
        foreach ($a as $entry) {
            // Create a key based on all fields except sortBy
            $key = json_encode(array_diff_key($entry, ['sortBy' => '']));
            if (!isset($reduced[$key])) {
                $reduced[$key] = $entry; // Initialize if not present
                $reduced[$key]['sortBy'] = [];
            }
            // Append current sortBy to the list of sortBys
            $reduced[$key]['sortBy'][] = $entry['sortBy'];
        }

// Convert sortBy arrays to comma-separated strings
        foreach ($reduced as &$item) {
            $item['sortBy'] = implode(", ", $item['sortBy']);
        }

// Re-index array
        return array_values($reduced);
    }
    private function fillSheetWithData(Worksheet $sheet, array $data,string $col): void
    {
        $header = ['Station Nr', 'Inverter Nr', 'String Nr', 'Unit', 'Channel Nr', 'String Active', 'Channel Cat', 'Position', 'Tilt', 'Azimut', 'ModuleType', 'InverterType', 'Impp', 'AVG', $col];
        $sheet->fromArray($header, null, 'A1');
        $sheet->getStyle('A1:O1')->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($data as $rowData) {
            $sheet->fromArray(array_values($rowData), null, "A{$rowIndex}");
            $rowIndex++;
        }
    }


    private function colorizePerformanceRows(Worksheet $sheet, int $rowsCount): void
    {
        for ($row = 2; $row <= $rowsCount; $row++) {
            // Corrigez ici pour accéder à la valeur de la cellule. Assurez-vous d'utiliser le bon index de colonne.
            $cellValue = $sheet->getCell('O' . $row)->getValue(); // Utilisation de la référence de cellule 'A1', 'O2', etc.

            $styleArray = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [],
                ],
            ];

            if ($cellValue === 'Best') {
                $styleArray['fill']['startColor'] = ['argb' => Color::COLOR_GREEN];
            } elseif ($cellValue === 'Worst') {
                $styleArray['fill']['startColor'] = ['argb' => Color::COLOR_RED];
            }

            if ($cellValue === 'Best' || $cellValue === 'Worst') {
                $sheet->getStyle("A{$row}:O{$row}")->applyFromArray($styleArray);
            }
        }
    }


    private function generateAndSaveExcelFile($spreadsheet, $anlId,$month, $year,$currentUserName,$publicDirectory)
    {

        $writer = new Xlsx($spreadsheet);
        $currentTimestamp = (new \DateTime())->format('YmdHis');
        $fileName = "{$month}_{$year}_{$currentTimestamp}.xlsx";
        $filepath = $publicDirectory .$fileName ;


        $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlId]);
        $anlagenReport= new AnlagenReports();
        $anlagenReport->setAnlage($anlage);
        $anlagenReport->setEigner($anlage->getEigner());
        $anlagenReport->setReportType('string-analyse');
        $anlagenReport->setMonth($month);
        $anlagenReport->setYear($year);
        $anlagenReport->setFile($filepath);
        $anlagenReport->setStartDate(new \DateTime());
        $anlagenReport->setEndDate(new \DateTime());
        $anlagenReport->setRawReport('');
        $anlagenReport->setCreatedBy($currentUserName);



        $tempStream = fopen('php://temp', 'r+');
        $writer->save($tempStream);
        rewind($tempStream);
        $this->fileSystemFtp->writeStream($filepath, $tempStream);

        // Close the temporary stream
        fclose($tempStream);

        $this->entityManager->persist($anlagenReport);
        $this->entityManager->flush();

    }

    public function exportAmReport($anlId,$month,$year)
    {
        $data = $this->reportsRepository->getOneAnlageString($anlId, $month, $year);
        if (!$data) {
            throw new \Exception('Report not found.', Response::HTTP_NOT_FOUND);
        }


        $filePath = $data[0]->getFile();


        if (!$this->fileSystemFtp->fileExists($filePath)) {
            throw new \Exception('File not found.', Response::HTTP_NOT_FOUND);
        }

        // Lecture du contenu du fichier
        try {
            $fileContent = $this->fileSystemFtp->read($filePath);
        } catch (FilesystemException $e) {
            throw new \Exception('Unable to read the file content.', Response::HTTP_BAD_REQUEST);
        }

        // Copie du contenu dans un fichier temporaire
        $tempFilePath = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($tempFilePath, $fileContent);

        $sheetsData = [];
        // Parsing du fichier Excel à partir du fichier temporaire
        if ($xlsx = SimpleXLSX::parse($tempFilePath)) {

            $sheetNames = $xlsx->sheetNames();
            foreach ($sheetNames as $index => $name) {

                $sheetData = $xlsx->rows($index);

                // Nettoyage de chaque cellule
                array_walk_recursive($sheetData, function (&$item) {
                    if (is_string($item)) {
                        $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                    }
                });

                $sheetsData[$name] = $sheetData;

            }
            unlink($tempFilePath); // Cleanup temporary file


        }

        return $sheetsData;
    }







}
