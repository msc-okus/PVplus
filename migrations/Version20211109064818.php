<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211109064818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage ADD degradation_forecast VARCHAR(20) DEFAULT NULL, ADD losses_forecast VARCHAR(20) DEFAULT NULL, DROP source_inv_name, CHANGE epc_report_note epc_report_note LONGTEXT DEFAULT NULL, CHANGE has_dc has_dc TINYINT(1) DEFAULT NULL, CHANGE has_strings has_strings TINYINT(1) DEFAULT NULL, CHANGE has_pannel_temp has_pannel_temp TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_forcast_day RENAME INDEX idx_8d2a6f25592479e0 TO IDX_B1D08933592479E0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage ADD source_inv_name VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, DROP degradation_forecast, DROP losses_forecast, CHANGE epc_report_note epc_report_note LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, CHANGE has_dc has_dc TINYINT(1) NOT NULL, CHANGE has_strings has_strings TINYINT(1) NOT NULL, CHANGE has_pannel_temp has_pannel_temp TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE anlage_forcast_day RENAME INDEX idx_b1d08933592479e0 TO IDX_8D2A6F25592479E0');
    }
}
