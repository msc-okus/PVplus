<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230619101303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_sensors (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, name_short VARCHAR(20) NOT NULL, name VARCHAR(100) DEFAULT NULL, type VARCHAR(20) NOT NULL, orientation VARCHAR(20) NOT NULL, vcom_id VARCHAR(20) DEFAULT NULL, vcom_abbr VARCHAR(20) DEFAULT NULL, INDEX IDX_6C08B661592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage_sensors ADD CONSTRAINT FK_6C08B661592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_sensors DROP FOREIGN KEY FK_6C08B661592479E0');
        $this->addSql('DROP TABLE anlage_sensors');
    }
}
