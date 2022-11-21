<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221121075447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE eigner DROP nachricht, DROP telefon1, DROP telefon2, DROP mobil, DROP fax, DROP home_dir, DROP home_folder, DROP email, DROP web, DROP bv_anrede, DROP bv_vorname, DROP bv_nachname, DROP bv_email, DROP bv_telefon1, DROP bv_telefon2, DROP bv_mobil, DROP level, CHANGE zusatz zusatz VARCHAR(100) DEFAULT NULL, CHANGE anrede anrede VARCHAR(100) DEFAULT NULL, CHANGE vorname vorname VARCHAR(100) DEFAULT NULL, CHANGE nachname nachname VARCHAR(100) DEFAULT NULL, CHANGE strasse strasse VARCHAR(100) DEFAULT NULL, CHANGE plz plz VARCHAR(10) DEFAULT NULL, CHANGE ort ort VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE eigner ADD nachricht TEXT DEFAULT NULL, ADD telefon1 VARCHAR(100) NOT NULL, ADD telefon2 VARCHAR(100) NOT NULL, ADD mobil VARCHAR(100) NOT NULL, ADD fax VARCHAR(100) NOT NULL, ADD home_dir VARCHAR(100) DEFAULT \'user/home/\' NOT NULL, ADD home_folder TEXT DEFAULT NULL, ADD email TEXT NOT NULL, ADD web TEXT DEFAULT NULL, ADD bv_anrede VARCHAR(100) NOT NULL, ADD bv_vorname VARCHAR(100) NOT NULL, ADD bv_nachname VARCHAR(100) NOT NULL, ADD bv_email TEXT NOT NULL, ADD bv_telefon1 VARCHAR(100) NOT NULL, ADD bv_telefon2 VARCHAR(100) NOT NULL, ADD bv_mobil VARCHAR(100) NOT NULL, ADD level VARCHAR(5) DEFAULT \'1\' NOT NULL, CHANGE zusatz zusatz VARCHAR(100) NOT NULL, CHANGE anrede anrede VARCHAR(100) NOT NULL, CHANGE vorname vorname VARCHAR(100) NOT NULL, CHANGE nachname nachname VARCHAR(100) NOT NULL, CHANGE strasse strasse VARCHAR(100) NOT NULL, CHANGE plz plz VARCHAR(10) NOT NULL, CHANGE ort ort VARCHAR(100) NOT NULL');
    }
}
