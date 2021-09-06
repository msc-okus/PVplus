<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210302155516 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_pr ADD irr_month VARCHAR(20) NOT NULL, ADD irr_pac VARCHAR(20) NOT NULL, ADD irr_year VARCHAR(20) NOT NULL, ADD spez_yield VARCHAR(20) NOT NULL, ADD case5per_day VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_pr DROP irr_month, DROP irr_pac, DROP irr_year, DROP spez_yield, DROP case5per_day');
    }
}
