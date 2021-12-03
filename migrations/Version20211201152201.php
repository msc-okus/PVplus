<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211201152201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_case6 (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, stamp_from VARCHAR(20) NOT NULL, stamp_to VARCHAR(20) NOT NULL, inverter VARCHAR(100) NOT NULL, reason LONGTEXT DEFAULT NULL, INDEX IDX_68B41B64592479E0 (anlage_id), INDEX IDX_68B41B648CA46283 (stamp_from), INDEX IDX_68B41B646751DB0B (stamp_to), INDEX IDX_68B41B64C9E962C9 (inverter), UNIQUE INDEX uniqueCase6 (anlage_id, stamp_from, stamp_to, inverter), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage_case6 ADD CONSTRAINT FK_68B41B64592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
        $this->addSql('ALTER TABLE eigner CHANGE font_color font_color VARCHAR(10) DEFAULT NULL, CHANGE font_color2 font_color2 VARCHAR(10) DEFAULT NULL, CHANGE font_color3 font_color3 VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlage_case6');
        $this->addSql('ALTER TABLE eigner CHANGE font_color font_color VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE font_color2 font_color2 VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE font_color3 font_color3 VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
    }
}
