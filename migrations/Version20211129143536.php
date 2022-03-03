<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211129143536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage DROP use_pnom_for_pld');
        $this->addSql('ALTER TABLE anlage_modules DROP irr_discount1, DROP irr_discount2, DROP irr_discount3, DROP irr_discount4');
        $this->addSql('ALTER TABLE eigner ADD font_color VARCHAR(255) DEFAULT NULL, ADD font_color2 VARCHAR(255) DEFAULT NULL, ADD font_color3 VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage ADD use_pnom_for_pld TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE anlage_modules ADD irr_discount1 VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD irr_discount2 VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD irr_discount3 VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD irr_discount4 VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE eigner DROP font_color, DROP font_color2, DROP font_color3');
    }
}
