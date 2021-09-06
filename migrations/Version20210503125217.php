<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210503125217 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_groups_ac ADD weather_station_id INT DEFAULT NULL, ADD is_east_west_group TINYINT(1) NOT NULL, ADD gewichtung_anlagen_pr VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE anlage_groups_ac ADD CONSTRAINT FK_2B4743F89E475DA2 FOREIGN KEY (weather_station_id) REFERENCES weather_station (id)');
        $this->addSql('CREATE INDEX IDX_2B4743F89E475DA2 ON anlage_groups_ac (weather_station_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_groups_ac DROP FOREIGN KEY FK_2B4743F89E475DA2');
        $this->addSql('DROP INDEX IDX_2B4743F89E475DA2 ON anlage_groups_ac');
        $this->addSql('ALTER TABLE anlage_groups_ac DROP weather_station_id, DROP is_east_west_group, DROP gewichtung_anlagen_pr');
    }
}
