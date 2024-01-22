<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211104071003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_forecast_day (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, week INT NOT NULL, day INT NOT NULL, expected_day VARCHAR(20) NOT NULL, factor_day VARCHAR(20) NOT NULL, factor_min VARCHAR(20) NOT NULL, factor_max VARCHAR(20) NOT NULL, INDEX IDX_8D2A6F25592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage_forecast_day ADD CONSTRAINT FK_8D2A6F25592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
        $this->addSql('ALTER TABLE anlage CHANGE epc_report_note epc_report_note LONGTEXT NOT NULL, CHANGE source_inv_name source_inv_name VARCHAR(20) NOT NULL, CHANGE has_dc has_dc TINYINT(1) NOT NULL, CHANGE has_strings has_strings TINYINT(1) NOT NULL, CHANGE has_pannel_temp has_pannel_temp TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlage_forecast_day');
        $this->addSql('ALTER TABLE anlage CHANGE epc_report_note epc_report_note LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE source_inv_name source_inv_name VARCHAR(20) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE has_dc has_dc TINYINT(1) DEFAULT NULL, CHANGE has_strings has_strings TINYINT(1) DEFAULT NULL, CHANGE has_pannel_temp has_pannel_temp TINYINT(1) DEFAULT NULL');
    }
}
