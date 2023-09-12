<?php

namespace App\Service;

use PDO;
use PDOException;
class ConnectionService
{
    public function __construct(
        private readonly string $host,
        private readonly string $userBase,
        private readonly string $passwordBase,
        private readonly string $userPlant,    # Measurement Data
        private readonly string $passwordPlant # Measurement Data
    ){
    }

    /**
     *
     * @return PDO
     */
    public function getPdoConnectionMeasurement(): PDO
    {
        return self::pdoConnection($this->host, $this->userPlant, 'pvp_data', $this->passwordPlant);
    }

    public function getPdoConnectionBase(): PDO
    {
        return self::pdoConnection($this->host, $this->userBase, 'pvp_base', $this->passwordBase);
    }

    private function pdoConnection(string $host, string $user, string $dbname, string $passwort)
    {
        try {
            $pdo = new PDO("mysql:dbname=$dbname;host=$host", $user, $passwort,
                [
                    PDO::ATTR_PERSISTENT => true
                ]
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Error!: '.$e->getMessage().'<br/>';
            exit;
        }

        return $pdo;
    }
}