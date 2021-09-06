<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201211104807 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_modules (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, type VARCHAR(20) NOT NULL, power VARCHAR(20) NOT NULL, temp_coef_current VARCHAR(20) NOT NULL, temp_coef_power VARCHAR(20) NOT NULL, operator_power_a VARCHAR(20) NOT NULL, operator_power_b VARCHAR(20) NOT NULL, operator_power_c VARCHAR(20) NOT NULL, operator_current_a VARCHAR(20) NOT NULL, operator_current_b VARCHAR(20) NOT NULL, operator_current_c VARCHAR(20) NOT NULL, operator_current_d VARCHAR(20) NOT NULL, operator_current_e VARCHAR(20) NOT NULL, INDEX IDX_926C0F26592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage_modules ADD CONSTRAINT FK_926C0F26592479E0 FOREIGN KEY (anlage_id) REFERENCES db_anlage (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlage_modules');
    }
}
