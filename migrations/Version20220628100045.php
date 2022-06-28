<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220628100045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_date ADD status INT DEFAULT NULL, ADD error_type VARCHAR(100) DEFAULT NULL, ADD free_text LONGTEXT DEFAULT NULL, ADD description VARCHAR(255) DEFAULT NULL, ADD system_status INT DEFAULT NULL, ADD priority INT DEFAULT NULL, ADD answer VARCHAR(255) DEFAULT NULL, ADD inverter VARCHAR(100) NOT NULL, ADD alert_type VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_date DROP status, DROP error_type, DROP free_text, DROP description, DROP system_status, DROP priority, DROP answer, DROP inverter, DROP alert_type');
    }
}
