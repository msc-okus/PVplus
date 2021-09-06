<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210130162745 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_pr ADD theo_power_pac VARCHAR(20) NOT NULL, ADD theo_power_year VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE anlagen_pr RENAME INDEX idx_921fbf4c592479e0 TO IDX_8A85E82F592479E0');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_pr DROP theo_power_pac, DROP theo_power_year');
        $this->addSql('ALTER TABLE anlagen_pr RENAME INDEX idx_8a85e82f592479e0 TO IDX_921FBF4C592479E0');
    }
}
