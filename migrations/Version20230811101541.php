<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230811101541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_modules_db (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, power VARCHAR(20) NOT NULL, temp_coef_current VARCHAR(20) NOT NULL, temp_coef_power VARCHAR(20) NOT NULL, temp_coef_voltage VARCHAR(20) NOT NULL, max_impp VARCHAR(20) NOT NULL, max_umpp VARCHAR(20) NOT NULL, max_pmpp VARCHAR(20) NOT NULL, operator_power_a VARCHAR(20) NOT NULL, operator_power_b VARCHAR(20) NOT NULL, operator_power_c VARCHAR(20) NOT NULL, operator_power_d VARCHAR(20) DEFAULT NULL, operator_power_e VARCHAR(20) DEFAULT NULL, operator_power_high_a VARCHAR(20) NOT NULL, operator_power_high_b VARCHAR(20) NOT NULL, operator_current_a VARCHAR(20) NOT NULL, operator_current_b VARCHAR(20) NOT NULL, operator_current_c VARCHAR(20) NOT NULL, operator_current_d VARCHAR(20) NOT NULL, operator_current_e VARCHAR(20) NOT NULL, operator_current_high_a VARCHAR(20) NOT NULL, operator_voltage_a VARCHAR(20) NOT NULL, operator_voltage_b VARCHAR(20) NOT NULL, operator_voltage_hight_a VARCHAR(20) NOT NULL, operator_voltage_hight_b VARCHAR(20) NOT NULL, operator_voltage_hight_c VARCHAR(20) NOT NULL, back_side_factor VARCHAR(20) DEFAULT NULL, modul_picture VARCHAR(20) DEFAULT NULL, data_sheet_1 VARCHAR(20) DEFAULT NULL, data_sheet_2 VARCHAR(20) DEFAULT NULL, annotation VARCHAR(20) DEFAULT NULL, producer VARCHAR(20) DEFAULT NULL, dimension_height VARCHAR(20) DEFAULT NULL, dimension_width VARCHAR(20) DEFAULT NULL, modified DATETIME NOT NULL, degradation VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlage_modules_db');
    }
}
