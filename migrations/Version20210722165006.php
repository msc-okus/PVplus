<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210722165006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_file_upload (id INT AUTO_INCREMENT NOT NULL, plant_id_id BIGINT NOT NULL, anlage_id INT NOT NULL, stamp DATETIME NOT NULL, filename VARCHAR(255) NOT NULL, upload_path VARCHAR(255) NOT NULL, mime_type VARCHAR(255) NOT NULL, originaal_file_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_ad DATETIME DEFAULT NULL, created_by VARCHAR(255) NOT NULL, updated_by VARCHAR(255) DEFAULT NULL, INDEX IDX_48EB26B58C9E07DF (plant_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage_file_upload ADD CONSTRAINT FK_48EB26B58C9E07DF FOREIGN KEY (plant_id_id) REFERENCES anlage (id)');
    }

    public function down(Schema $schema): void
    {
        $schema = 'pvp_base';
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlage_file_upload');
    }
}
