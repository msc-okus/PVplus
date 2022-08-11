<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220810115737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE owner_features (id INT AUTO_INCREMENT NOT NULL, owner_id BIGINT DEFAULT NULL, akt_dep1 TINYINT(1) DEFAULT NULL, akt_dep2 TINYINT(1) DEFAULT NULL, akt_dep3 TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_A92065D07E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE owner_settings (id INT AUTO_INCREMENT NOT NULL, owner_id BIGINT DEFAULT NULL, name_dep1 VARCHAR(20) DEFAULT NULL, name_dep2 VARCHAR(20) DEFAULT NULL, name_dep3 VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_F3A519067E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE owner_features ADD CONSTRAINT FK_A92065D07E3C61F9 FOREIGN KEY (owner_id) REFERENCES eigner (id)');
        $this->addSql('ALTER TABLE owner_settings ADD CONSTRAINT FK_F3A519067E3C61F9 FOREIGN KEY (owner_id) REFERENCES eigner (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE owner_features DROP FOREIGN KEY FK_A92065D07E3C61F9');
        $this->addSql('ALTER TABLE owner_settings DROP FOREIGN KEY FK_F3A519067E3C61F9');
        $this->addSql('DROP TABLE owner_features');
        $this->addSql('DROP TABLE owner_settings');
    }
}
