<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231212094151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_info (id INT AUTO_INCREMENT NOT NULL, owner_id BIGINT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, service VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, company_name VARCHAR(255) DEFAULT NULL, INDEX IDX_E376B3A87E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification_info (id INT AUTO_INCREMENT NOT NULL, contacted_person_id INT DEFAULT NULL, ticket_id INT DEFAULT NULL, date DATE NOT NULL, status INT NOT NULL, INDEX IDX_73B57A6D945547A1 (contacted_person_id), INDEX IDX_73B57A6D700047D2 (ticket_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact_info ADD CONSTRAINT FK_E376B3A87E3C61F9 FOREIGN KEY (owner_id) REFERENCES eigner (id)');
        $this->addSql('ALTER TABLE notification_info ADD CONSTRAINT FK_73B57A6D945547A1 FOREIGN KEY (contacted_person_id) REFERENCES contact_info (id)');
        $this->addSql('ALTER TABLE notification_info ADD CONSTRAINT FK_73B57A6D700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
       $this->addSql('ALTER TABLE contact_info DROP FOREIGN KEY FK_E376B3A87E3C61F9');
        $this->addSql('ALTER TABLE notification_info DROP FOREIGN KEY FK_73B57A6D945547A1');
        $this->addSql('ALTER TABLE notification_info DROP FOREIGN KEY FK_73B57A6D700047D2');
        $this->addSql('DROP TABLE contact_info');
        $this->addSql('DROP TABLE notification_info');
        }
}
