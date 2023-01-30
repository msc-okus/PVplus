<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230111144511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_pr CHANGE irradiation_json irradiation_json LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE temperatur_json temperatur_json LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE wind_json wind_json LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE open_weather CHANGE data data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE owner_features CHANGE split_inverter split_inverter TINYINT(1) DEFAULT 0, CHANGE split_gap split_gap TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE pvp_user CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', CHANGE assigned_anlagen assigned_anlagen LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE open_weather CHANGE data data LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE anlagen_pr CHANGE irradiation_json irradiation_json LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE temperatur_json temperatur_json LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE wind_json wind_json LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE pvp_user CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE assigned_anlagen assigned_anlagen LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE owner_features CHANGE split_inverter split_inverter TINYINT(1) DEFAULT NULL, CHANGE split_gap split_gap TINYINT(1) DEFAULT NULL');
    }
}
