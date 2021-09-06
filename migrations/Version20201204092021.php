<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201204092021 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_group_moduls (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, num_strings_per_meter VARCHAR(20) NOT NULL, temp_coef_current VARCHAR(20) NOT NULL, temp_coef_power VARCHAR(20) NOT NULL, expected_power VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE anlage_group_months (id INT AUTO_INCREMENT NOT NULL, month INT NOT NULL, irr_upper VARCHAR(20) NOT NULL, irr_lower VARCHAR(20) NOT NULL, shadow_loss VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE anlage_groups (id INT AUTO_INCREMENT NOT NULL, dc_group INT NOT NULL, dc_group_name VARCHAR(30) NOT NULL, ac_group INT NOT NULL, ac_group_name VARCHAR(30) NOT NULL, meter_first INT NOT NULL, meter_last INT NOT NULL, irr_upper VARCHAR(20) NOT NULL, irr_lower VARCHAR(20) NOT NULL, shadow_loss VARCHAR(20) NOT NULL, operater_power_a VARCHAR(20) NOT NULL, operater_power_b VARCHAR(20) NOT NULL, operater_power_c VARCHAR(20) NOT NULL, operater_current_a VARCHAR(20) NOT NULL, operater_current_b VARCHAR(20) NOT NULL, operater_current_c VARCHAR(20) NOT NULL, operater_current_d VARCHAR(20) NOT NULL, operater_current_e VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlage_group_moduls');
        $this->addSql('DROP TABLE anlage_group_months');
        $this->addSql('DROP TABLE anlage_groups');
    }
}
