<?php

namespace App\Service;

use App\Helper\G4NTrait;
use PDO;
use PDOException;
class PdoService
{
    use G4NTrait;

    public function __construct(
        private $host,
        private $userBase,
        private $passwordBase,
        private $userPlant,
        private $passwordPlant
    )
    {
    }

    //connection for imported data
    public function getPdoPlant(): PDO
    {
        return($this->getPdoConnection($this->host, $this->userPlant, $this->passwordPlant, 'pvp_data'));
    }

    //connection for imported data from StringBoxes
    public function getPdoStringBoxes(): PDO
    {
        return($this->getPdoConnection($this->host, $this->userPlant, $this->passwordPlant, 'pvp_division'));
    }

    //connection for base tables like anlagen
    public function getPdoBase(): PDO
    {
        return($this->getPdoConnection($this->host, $this->userBase, $this->passwordBase, 'pvp_base'));
    }

    /**
     * @param string|null $dbdsn
     * @param string|null $dbusr
     * @param string|null $dbpass
     * @param null $database
     * @return PDO
     */
    private function getPdoConnection(?string $dbdsn = null, ?string $dbusr = null, ?string $dbpass = null, $database = null): PDO
    {
        // Config als Array
        // Check der Parameter wenn null dann nehme default Werte als fallback
        $config = [
            'database_dsn' => "mysql:dbname=$database;host=".$dbdsn, // 'mysql:dbname=pvp_data;host=dedi6015.your-server.de'
            'database_user' => $dbusr,
            'database_pass' => $dbpass,
        ];

        try {
            $pdo = new PDO(
                $config['database_dsn'],
                $config['database_user'],
                $config['database_pass'],
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