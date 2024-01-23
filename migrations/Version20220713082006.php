<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220713082006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket CHANGE status status INT DEFAULT NULL, CHANGE end end DATETIME NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE system_status system_status INT DEFAULT NULL, CHANGE priority priority INT DEFAULT NULL, CHANGE answer answer VARCHAR(255) DEFAULT NULL, CHANGE error_type error_type VARCHAR(100) DEFAULT NULL, CHANGE inverter inverter VARCHAR(100) NOT NULL, CHANGE alert_type alert_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_date ADD data_gap_evaluation VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket CHANGE end end DATETIME DEFAULT NULL, CHANGE error_type error_type VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE system_status system_status INT NOT NULL, CHANGE priority priority INT NOT NULL, CHANGE answer answer LONGTEXT DEFAULT NULL, CHANGE inverter inverter VARCHAR(50) DEFAULT NULL, CHANGE alert_type alert_type VARCHAR(20) DEFAULT NULL, CHANGE status status INT NOT NULL');
        $this->addSql('ALTER TABLE ticket_date DROP data_gap_evaluation');
    }
}
