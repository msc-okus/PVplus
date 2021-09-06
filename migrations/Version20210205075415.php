<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210205075415 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE open_weather ADD anlage_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE open_weather ADD CONSTRAINT FK_21E41F9D592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
        $this->addSql('CREATE INDEX IDX_21E41F9D592479E0 ON open_weather (anlage_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE open_weather DROP FOREIGN KEY FK_21E41F9D592479E0');
        $this->addSql('DROP INDEX IDX_21E41F9D592479E0 ON open_weather');
        $this->addSql('ALTER TABLE open_weather DROP anlage_id');
    }
}
