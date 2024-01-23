<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201221101509 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anlage DROP anl_tracker, DROP anl_tracker_faktor, DROP anl_tracker_dump, DROP anl_data_go_wr, DROP anl_data_souce, DROP anl_db_soll, DROP anl_gr_kwp, DROP anl_inv_anz, DROP anl_inv_name, DROP anl_inv_leistung, DROP anl_gr_count, DROP anl_wind_unit, DROP anl_view');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anlage ADD anl_tracker VARCHAR(10) CHARACTER SET utf8 DEFAULT \'No\' NOT NULL COLLATE `utf8_general_ci`, ADD anl_tracker_faktor VARCHAR(10) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, ADD anl_tracker_dump VARCHAR(5) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_data_go_wr VARCHAR(10) CHARACTER SET utf8 DEFAULT \'No\' NOT NULL COLLATE `utf8_general_ci`, ADD anl_data_souce VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_db_soll VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_gr_kwp VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_inv_anz VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_inv_name VARCHAR(100) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_inv_leistung VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_gr_count VARCHAR(10) CHARACTER SET utf8 DEFAULT \'No\' COLLATE `utf8_general_ci`, ADD anl_wind_unit VARCHAR(10) CHARACTER SET utf8 DEFAULT \'km/h\' NOT NULL COLLATE `utf8_general_ci`, ADD anl_view VARCHAR(10) CHARACTER SET utf8 DEFAULT \'Yes\' NOT NULL COLLATE `utf8_general_ci`');
    }
}
