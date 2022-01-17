<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220117172201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_settings (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, name0 VARCHAR(20) NOT NULL, name1 VARCHAR(20) NOT NULL, name2 VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_2F43B903592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage_settings ADD CONSTRAINT FK_2F43B903592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
        $this->addSql('ALTER TABLE anlage_file CHANGE nime_type mime_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_file ADD CONSTRAINT FK_925543D61D935652 FOREIGN KEY (plant_id) REFERENCES anlage (id)');
        $this->addSql('CREATE INDEX IDX_925543D61D935652 ON anlage_file (plant_id)');
        $this->addSql('ALTER TABLE ticket ADD error_type VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlage_settings');
        $this->addSql('ALTER TABLE anlage_file DROP FOREIGN KEY FK_925543D61D935652');
        $this->addSql('DROP INDEX IDX_925543D61D935652 ON anlage_file');
        $this->addSql('ALTER TABLE anlage_file CHANGE mime_type nime_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ticket DROP error_type');
    }
}
