<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201201150010 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_forecast (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, week INT NOT NULL, day INT NOT NULL, expected_week VARCHAR(20) NOT NULL, divergenz_minus VARCHAR(20) NOT NULL, divergenz_plus VARCHAR(20) NOT NULL, min_norm VARCHAR(20) NOT NULL, max_norm VARCHAR(20) NOT NULL, INDEX IDX_E09A6182592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage_forecast ADD CONSTRAINT FK_E09A6182592479E0 FOREIGN KEY (anlage_id) REFERENCES db_anlage (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlage_forecast');
    }
}
