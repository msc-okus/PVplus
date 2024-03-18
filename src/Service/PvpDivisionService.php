<?php

namespace App\Service;

use PDO;

class PvpDivisionService
{
    private PDO $sourceDb;
    private PDO $targetDb;

    // Constructor to initialize the service.
    public function __construct(PdoService $pdoService)
    {
        $this->sourceDb=$pdoService->getPdoPlant();
        $this->targetDb=$pdoService->getPdoStringBoxes();
    }



    // SQL query to retrieve table names from the source database.
    private function getTableNames(): array
    {
        $tableNames = [];

        // Query to find all tables that match the pattern and do not contain specified strings
        $query = "SHOW TABLES FROM pvp_data LIKE 'db__pv_dcist_%'";
        $potentialTables = $this->sourceDb->query($query)->fetchAll(PDO::FETCH_COLUMN);

        // Iterate through the tables to check if they are non-empty and meet other criteria
        foreach ($potentialTables as $tableName) {
            if (!str_contains($tableName, 'G4NET_') && !str_contains($tableName, '_copy')) {
                // Check if the table is non-empty
                $rowCount = $this->sourceDb->query("SELECT COUNT(*) FROM `$tableName`")->fetchColumn();
                if ($rowCount > 0) {
                    $tableNames[] = $tableName;
                }
            }
        }

        return $tableNames;
    }

    // Generate a new table name by replacing the prefix.
    private function getNewTableName(string $oldTableName): string
    {
        $suffix = str_replace('db__pv_dcist_', '', $oldTableName);
        return 'db__string_pv_' . $suffix;
    }


    // Create a new table in the target database.
    private function createNewTable(string $tableName): void
    {
        $createSql = "CREATE TABLE IF NOT EXISTS `$tableName` (
            `db_id` bigint(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `anl_id` int(11) NOT NULL,
            `stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `wr_group` int(11) NOT NULL,
            `group_ac` int(11) NOT NULL,
            `wr_num` int(11) NOT NULL,
            `channel` varchar(20) NOT NULL,
            `I_value` varchar(20) DEFAULT NULL,
            `U_value` varchar(20) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->targetDb->exec($createSql);
    }


    // Fetch data between specified dates from the source database.
    private function fetchDataBetweenDates(string $table, string $startDate, string $endDate): array
    {
        $query = $this->sourceDb->prepare("SELECT anl_id, stamp, wr_group, group_ac, wr_num, wr_mpp_current, wr_mpp_voltage FROM `$table` WHERE `stamp` BETWEEN ? AND ?");
        $query->execute([$startDate, $endDate]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    private function processAndGroupData(array $rows): array
    {
        $groupedData = []; //  Initialize array to hold grouped data
        foreach ($rows as $row) {



            // processing current and voltage data from rows
            $currentData = array_values(json_decode($row['wr_mpp_current'], true) ?: []);
            $voltageData = array_values(json_decode($row['wr_mpp_voltage'], true) ?: []);


            // Handling various combinations of current and voltage data
            if(count($currentData)>0 && count($voltageData )=== 0){
                // Code for processing current data only
                foreach ($currentData as $key => $value) {
                    $channel = $key + 1 ;
                    $groupedData[$row['stamp']][] = [
                        'anl_id' => $row['anl_id'], 'stamp' => $row['stamp'],
                        'wr_group' => $row['wr_group'], 'group_ac' => $row['group_ac'], 'wr_num' => $row['wr_num'],
                        'channel' => $channel, 'I_value' => $value, 'U_value' => null
                    ];
                }
            }elseif (count($currentData)=== 0 && count($voltageData )> 0){
                foreach ($voltageData as $key => $value) {
                    $channel = $key + 1 ;
                    $groupedData[$row['stamp']][] = [
                        'anl_id' => $row['anl_id'], 'stamp' => $row['stamp'],
                        'wr_group' => $row['wr_group'], 'group_ac' => $row['group_ac'], 'wr_num' => $row['wr_num'],
                        'channel' => $channel, 'I_value' => null, 'U_value' => $value
                    ];
                }
            }elseif (count($currentData)> 0 && count($voltageData )> 0){
                // Code for processing both current and voltage data
                foreach ($voltageData as $key => $value) {
                    $channel = $key + 1 ;
                    $groupedData[$row['stamp']][] = [
                        'anl_id' => $row['anl_id'], 'stamp' => $row['stamp'],
                        'wr_group' => $row['wr_group'], 'group_ac' => $row['group_ac'], 'wr_num' => $row['wr_num'],
                        'channel' => $channel, 'I_value' => $value, 'U_value' => $currentData[$key]
                    ];
                }
            }

        }

        return $groupedData;
    }


    // Execute the insert operation for a batch of data.
    private function executeInsert(string $tableName, array $data): void
    {
        // Code for building and executing the batch insert query
        $placeholders = [];
        foreach ($data as $row) {
            $placeholders[] = "(" . rtrim(str_repeat('?,', count($row)), ',') . ")";
        }

        $values = array_merge([], ...$data);
        $sql = "INSERT INTO `$tableName` ( `anl_id`, `stamp`, `wr_group`, `group_ac`, `wr_num`, `channel`, `I_value`, `U_value`) VALUES " . implode(',', $placeholders);
        $stmt = $this->targetDb->prepare($sql);
        $stmt->execute($values);
    }


    // Insert grouped data into the target database.
    private function insertGroupedData(string $tableName, array $groupedData): void
    {
        // Insert data into the target table in batches to avoid exceeding the placeholder limit.
        $batchSize = 6500; // Adjust this number based on your needs 65535
        $allData = [];

        foreach ($groupedData as $stamp => $data) {
            foreach ($data as $row) {
                $allData[] = array_values($row);
                if (count($allData) >= $batchSize) {
                    $this->executeInsert($tableName, $allData);
                    $allData = []; // Reset the array after insertion
                }
            }
        }

        // Insert any remaining data
        if (!empty($allData)) {
            $this->executeInsert($tableName, $allData);
        }
    }



    // Retrieve table names, process data, and perform transfer
    // Code for transferring data from source to target database
    public function transferData(string $startDate, string $endDate): void
    {
        // Set unlimited time limit and memory for the script
        set_time_limit(0);
        ini_set('memory_limit', '-1');


        // Retrieve the names of the tables from the source database that match the specified pattern.
        $tables = $this->getTableNames();


        foreach ($tables as $table) {
            // Generate the new table name for the target database
            $newTableName = $this->getNewTableName($table);

            // Retrieve data from the source table between the specified dates.
            $rows = $this->fetchDataBetweenDates($table, $startDate, $endDate);


            if(count($rows)>0){
                // Create (or recreate) the table in the target database.
                $this->createNewTable($newTableName);

                // Process the data and group it by 'stamp'.
                $groupedData = $this->processAndGroupData($rows);

                // Insert the grouped data into the target table
                $this->insertGroupedData($newTableName, $groupedData);
            }


        }
    }

    public function transferDataOne(string $startDate, string $endDate, string $dbname): void
    {
        // Set unlimited time limit and memory for the script
        set_time_limit(0);
        ini_set('memory_limit', '-1');


        $tables = [$dbname];


        foreach ($tables as $table) {
            // Generate the new table name for the target database
            $newTableName = $this->getNewTableName($table);

            // Retrieve data from the source table between the specified dates.
            $rows = $this->fetchDataBetweenDates($table, $startDate, $endDate);


            if(count($rows)>0){
                // Create (or recreate) the table in the target database.
                $this->createNewTable($newTableName);

                // Process the data and group it by 'stamp'.
                $groupedData = $this->processAndGroupData($rows);

                // Insert the grouped data into the target table
                $this->insertGroupedData($newTableName, $groupedData);
            }


        }
    }
}
