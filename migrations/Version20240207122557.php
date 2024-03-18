<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240207122557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_string_assignment (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, station_nr VARCHAR(255) NOT NULL, inverter_nr VARCHAR(255) NOT NULL, string_nr VARCHAR(255) NOT NULL, channel_nr VARCHAR(255) NOT NULL, string_active VARCHAR(255) NOT NULL, channel_cat VARCHAR(255) DEFAULT NULL, position VARCHAR(255) DEFAULT NULL, tilt VARCHAR(255) DEFAULT NULL, azimut VARCHAR(255) DEFAULT NULL, panel_type VARCHAR(255) DEFAULT NULL, inverter_type VARCHAR(255) DEFAULT NULL, INDEX IDX_929B8EED592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE anlage ADD last_anlage_string_assigment_upload DATETIME DEFAULT NULL');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE anlage_string_assignment DROP FOREIGN KEY FK_929B8EED592479E0');
        $this->addSql('DROP TABLE anlage_string_assignment');

        $this->addSql('ALTER TABLE anlage DROP last_anlage_string_assigment_upload');

    }
}
