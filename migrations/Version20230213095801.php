<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230213095801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_settings ADD chart_ac1 TINYINT(1) DEFAULT NULL, ADD chart_ac2 TINYINT(1) DEFAULT NULL, ADD chart_ac3 TINYINT(1) DEFAULT NULL, ADD chart_ac4 TINYINT(1) DEFAULT NULL, ADD chart_ac5 TINYINT(1) DEFAULT NULL, ADD chart_ac6 TINYINT(1) DEFAULT NULL, ADD chart_ac7 TINYINT(1) DEFAULT NULL, ADD chart_ac8 TINYINT(1) DEFAULT NULL, ADD chart_ac9 TINYINT(1) DEFAULT NULL, ADD chart_dc1 TINYINT(1) DEFAULT NULL, ADD chart_dc2 TINYINT(1) DEFAULT NULL, ADD chart_dc3 TINYINT(1) DEFAULT NULL, ADD chart_dc4 TINYINT(1) DEFAULT NULL, ADD chart_dc5 TINYINT(1) DEFAULT NULL, ADD chart_dc6 TINYINT(1) DEFAULT NULL, DROP pa_dep1_name, DROP pa_dep2_name, DROP pa_dep3_name');
        $this->addSql('ALTER TABLE weather_station CHANGE database_station_ident database_station_ident VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE weather_station CHANGE database_station_ident database_station_ident VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_settings ADD pa_dep1_name VARCHAR(20) NOT NULL, ADD pa_dep2_name VARCHAR(20) NOT NULL, ADD pa_dep3_name VARCHAR(20) NOT NULL, DROP chart_ac1, DROP chart_ac2, DROP chart_ac3, DROP chart_ac4, DROP chart_ac5, DROP chart_ac6, DROP chart_ac7, DROP chart_ac8, DROP chart_ac9, DROP chart_dc1, DROP chart_dc2, DROP chart_dc3, DROP chart_dc4, DROP chart_dc5, DROP chart_dc6');
    }
}
