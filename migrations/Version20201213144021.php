<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201213144021 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_groups ADD weather_station_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_groups ADD CONSTRAINT FK_4DA2D2C19E475DA2 FOREIGN KEY (weather_station_id) REFERENCES weather_station (id)');
        $this->addSql('CREATE INDEX IDX_4DA2D2C19E475DA2 ON anlage_groups (weather_station_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_groups DROP FOREIGN KEY FK_4DA2D2C19E475DA2');
        $this->addSql('DROP INDEX IDX_4DA2D2C19E475DA2 ON anlage_groups');
        $this->addSql('ALTER TABLE anlage_groups DROP weather_station_id');
    }
}
