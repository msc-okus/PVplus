<?php

namespace App\Service;

use PDO;
use PDOException;

class PvpDataService
{

    private PDO $sourceDb;
    private PDO $targetDb;


    public function __construct()
    {
        $this->connect(); // Establishes connection to the databases
    }

    private function connect(): void
    {
        // Database connection parameters
        $host = '128.204.133.210';
        $dbname = 'pvp_data';
        $user = 'pvpluy_2';
        $password = '8f4yMfFFRyqkrT-w6Ak2';
        $charset = 'utf8mb4';
        $dbname_string = 'pvp_division';

        // Data Source Names for the databases
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $dsn_cv = "mysql:host=$host;dbname=$dbname_string;charset=$charset";

        // Options for PDO connection
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Establishing the PDO connections
        try {
            $this->sourceDb = new PDO($dsn, $user, $password, $options);
            $this->targetDb = new PDO($dsn_cv, $user, $password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }



    //  SQL query to retrieve table names from source database
    private function getTableNames(): array
    {
        $query = "SHOW TABLES LIKE 'db__pv_dcist_%'";
        return $this->sourceDb->query($query)->fetchAll(PDO::FETCH_COLUMN);



    }


    private function getNewTableName(string $oldTableName): string
    {
        // Generating new table name by replacing prefix
        $suffix = str_replace('db__pv_dcist_', '', $oldTableName);
        return 'db__string_pv_' . $suffix;
    }


    private function createNewTable(string $tableName): void
    {
        // SQL statement to drop existing table
        //$dropSql = "DROP TABLE IF EXISTS `$tableName`";
        //$this->targetDb->exec($dropSql);

        // SQL statement to create a new table
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


    private function fetchDataBetweenDates(string $table, string $startDate, string $endDate): array
    {
        // SQL query to fetch data between specified dates
        $query = $this->sourceDb->prepare("SELECT anl_id, stamp, wr_group, group_ac, wr_num, wr_mpp_current, wr_mpp_voltage FROM `$table` WHERE `stamp` BETWEEN ? AND ?");
        $query->execute([$startDate, $endDate]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    private function processAndGroupData(array $rows): array
    {
        $groupedData = []; // // Initialize array to hold grouped data
        foreach ($rows as $row) {



            // processing current and voltage data from rows
            $currentData = array_values(json_decode($row['wr_mpp_current'], true) ?: []);
            $voltageData = array_values(json_decode($row['wr_mpp_voltage'], true) ?: []);


            if(count($currentData)>0 && count($voltageData )=== 0){
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



    private function insertGroupedData(string $tableName, array $groupedData): void
    {
        foreach ($groupedData as $stamp => $data) {
            $placeholders = [];
            $allValues = [];

            foreach ($data as $row) {
                // Create placeholders for each data row
                $placeholders[] = "(" . rtrim(str_repeat('?,', count($row)), ',') . ")";
                // Add the values of each row to a temporary array
                $allValues[] = array_values($row);
            }

            // Merge all the value arrays after the loop
            $values = array_merge([], ...$allValues);



            // Builds and executes the insertion query.
            $sql = "INSERT INTO `$tableName` ( `anl_id`, `stamp`, `wr_group`, `group_ac`, `wr_num`, `channel`, `I_value`, `U_value`) VALUES " . implode(',', $placeholders);
            $stmt = $this->targetDb->prepare($sql);
            $stmt->execute($values);
        }
    }

    // Main method for performing data transfer
    public function transferData(string $startDate, string $endDate): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        // Retrieve the names of the tables from the source database that match the specified pattern.
        $tables = $this->getTableNames();

        foreach ($tables as $table) {
            // Generate the new table name for the target database
            $newTableName = $this->getNewTableName($table);

            // Retrieve data from the source table between the specified dates.
            $rows = $this->fetchDataBetweenDates($table, $startDate, $endDate);

            if(count($rows) >0){
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
