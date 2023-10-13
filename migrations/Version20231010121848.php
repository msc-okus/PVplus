<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231010121848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pvp_userlog (user_id BIGINT AUTO_INCREMENT NOT NULL, eigner_id VARCHAR(25) NOT NULL, login_ip VARCHAR(25) NOT NULL, online VARCHAR(5) DEFAULT \'1\' NOT NULL, logtime DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, logout DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, pvp_userlogcol VARCHAR(45) DEFAULT NULL, PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage_sensors_new DROP FOREIGN KEY anlage_sensors_new_ibfk_1');
        $this->addSql('DROP TABLE anlage_sensors_new');
        $this->addSql('ALTER TABLE anlage DROP has_sunshading_model, CHANGE internal_ticket_system internal_ticket_system TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE anlage_modules_db CHANGE is_bifacial is_bifacial INT NOT NULL, CHANGE annotation annotation VARCHAR(20) DEFAULT NULL, CHANGE producer producer VARCHAR(20) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE anlage_ppcs CHANGE start_date_ppc start_date_ppc DATETIME NOT NULL, CHANGE end_date_ppc end_date_ppc DATETIME NOT NULL');
        $this->addSql('ALTER TABLE anlage_ppcs RENAME INDEX idx_6c08b661592479e0 TO IDX_879C7AF4592479E0');
        $this->addSql('ALTER TABLE anlage_sensors CHANGE type type VARCHAR(20) DEFAULT NULL, CHANGE use_to_calc use_to_calc TINYINT(1) DEFAULT NULL, CHANGE orientation orientation VARCHAR(20) DEFAULT NULL, CHANGE start_date_sensor start_date_sensor DATETIME NOT NULL, CHANGE end_date_sensor end_date_sensor DATETIME NOT NULL');
        $this->addSql('ALTER TABLE anlage_settings CHANGE import_type import_type VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_sun_shading DROP INDEX IDX_A55EADDE8A99FA61, ADD UNIQUE INDEX UNIQ_A55EADDE8A99FA61 (modules_db_id)');
        $this->addSql('ALTER TABLE anlage_sun_shading CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE anlagen_pr CHANGE irradiation_json irradiation_json JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE temperatur_json temperatur_json JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE wind_json wind_json JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE open_weather CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE owner_settings CHANGE mc_user mc_user VARCHAR(20) DEFAULT \'O-Skadow\', CHANGE mc_password mc_password VARCHAR(255) DEFAULT \'Tr3z%2!x$5\', CHANGE mc_token mc_token VARCHAR(100) DEFAULT \'264b63333e951a6c327d627003f6a828\'');
        $this->addSql('ALTER TABLE pvp_user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE assigned_anlagen assigned_anlagen JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE ticket ADD needs_proof_g4_n TINYINT(1) DEFAULT NULL, ADD creation_log VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_login CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_sensors_new (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, name_short VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, virtual_sensor VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, use_to_calc TINYINT(1) NOT NULL, orientation VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, vcom_id VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, vcom_abbr VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, start_date_sensor DATETIME DEFAULT NULL, end_date_sensor DATETIME DEFAULT NULL, INDEX IDX_6C08B661592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE anlage_sensors_new ADD CONSTRAINT anlage_sensors_new_ibfk_1 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
        $this->addSql('DROP TABLE pvp_userlog');
        $this->addSql('ALTER TABLE anlage_sensors CHANGE type type VARCHAR(20) NOT NULL, CHANGE orientation orientation VARCHAR(20) NOT NULL, CHANGE use_to_calc use_to_calc TINYINT(1) NOT NULL, CHANGE start_date_sensor start_date_sensor DATETIME DEFAULT NULL, CHANGE end_date_sensor end_date_sensor DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE pvp_user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE assigned_anlagen assigned_anlagen JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE ticket DROP needs_proof_g4_n, DROP creation_log');
        $this->addSql('ALTER TABLE anlage_settings CHANGE import_type import_type VARCHAR(25) DEFAULT NULL');
        $this->addSql('ALTER TABLE anlagen_pr CHANGE irradiation_json irradiation_json JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE temperatur_json temperatur_json JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE wind_json wind_json JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE owner_settings CHANGE mc_user mc_user VARCHAR(50) DEFAULT NULL, CHANGE mc_password mc_password VARCHAR(50) DEFAULT NULL, CHANGE mc_token mc_token VARCHAR(250) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_login CHANGE user_id user_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE anlage ADD has_sunshading_model TINYINT(1) DEFAULT 0, CHANGE internal_ticket_system internal_ticket_system TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE anlage_modules_db CHANGE is_bifacial is_bifacial INT DEFAULT 0 NOT NULL, CHANGE annotation annotation LONGTEXT DEFAULT NULL, CHANGE producer producer VARCHAR(20) NOT NULL, CHANGE created_at created_at VARCHAR(255) NOT NULL, CHANGE updated_at updated_at VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE open_weather CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE anlage_sun_shading DROP INDEX UNIQ_A55EADDE8A99FA61, ADD INDEX IDX_A55EADDE8A99FA61 (modules_db_id)');
        $this->addSql('ALTER TABLE anlage_sun_shading CHANGE created_at created_at VARCHAR(255) NOT NULL, CHANGE updated_at updated_at VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE anlage_ppcs CHANGE start_date_ppc start_date_ppc DATETIME DEFAULT NULL, CHANGE end_date_ppc end_date_ppc DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_ppcs RENAME INDEX idx_879c7af4592479e0 TO IDX_6C08B661592479E0');
    }
}
