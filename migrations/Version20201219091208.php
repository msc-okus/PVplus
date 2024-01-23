<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201219091208 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE db_anl_modata');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE db_anl_modata (id BIGINT AUTO_INCREMENT NOT NULL, anl_id VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_single_str VARCHAR(5) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_single_mod VARCHAR(5) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_power_1000 VARCHAR(25) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_umpp_1000 VARCHAR(25) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_usc_1000 VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_impp_1000 VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_isc_1000 VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_power_800 VARCHAR(25) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_umpp_800 VARCHAR(25) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_usc_800 VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_impp_800 VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_isc_800 VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_tku VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_tki VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_tkp VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_degradation VARCHAR(5) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_shading_loos VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_table_loos VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_kabel_loos VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_secure_loos VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, mod_sonst VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }
}
