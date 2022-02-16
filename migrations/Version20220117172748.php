<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220117172748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_file ADD CONSTRAINT FK_925543D61D935652 FOREIGN KEY (plant_id) REFERENCES anlage (id)');
        $this->addSql('CREATE INDEX IDX_925543D61D935652 ON anlage_file (plant_id)');
        $this->addSql('ALTER TABLE ticket ADD error_type VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_file DROP FOREIGN KEY FK_925543D61D935652');
        $this->addSql('DROP INDEX IDX_925543D61D935652 ON anlage_file');
        $this->addSql('ALTER TABLE ticket DROP error_type');
    }
}
