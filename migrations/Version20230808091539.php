<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230808091539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_sun_shading (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, mod_height VARCHAR(20) NOT NULL, mod_width VARCHAR(20) NOT NULL, mod_tilt VARCHAR(20) NOT NULL, mod_table_height VARCHAR(20) NOT NULL, mod_table_distance VARCHAR(20) NOT NULL, distance_a VARCHAR(20) NOT NULL, distance_b VARCHAR(20) NOT NULL, ground_slope VARCHAR(20) NOT NULL, update_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A55EADDE592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage_sun_shading ADD CONSTRAINT FK_A55EADDE592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
        $this->addSql('ALTER TABLE anlage CHANGE use_paflag0 use_paflag0 TINYINT(1) NOT NULL, CHANGE use_paflag1 use_paflag1 TINYINT(1) NOT NULL, CHANGE use_paflag2 use_paflag2 TINYINT(1) NOT NULL, CHANGE use_paflag3 use_paflag3 TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_sun_shading DROP FOREIGN KEY FK_A55EADDE592479E0');
        $this->addSql('DROP TABLE anlage_sun_shading');
        $this->addSql('ALTER TABLE anlage CHANGE use_paflag0 use_paflag0 TINYINT(1) DEFAULT 0, CHANGE use_paflag1 use_paflag1 TINYINT(1) DEFAULT 0, CHANGE use_paflag2 use_paflag2 TINYINT(1) DEFAULT 0, CHANGE use_paflag3 use_paflag3 TINYINT(1) DEFAULT 0');
    }
}
