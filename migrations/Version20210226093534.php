<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210226093534 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_pr ADD power_egrid_ext VARCHAR(20) NOT NULL, ADD power_egrid_ext_pac VARCHAR(20) NOT NULL, ADD power_egrid_ext_year VARCHAR(20) NOT NULL, ADD pr_egrid_ext VARCHAR(20) NOT NULL, ADD pr_egrid_ext_pac VARCHAR(20) NOT NULL, ADD pr_egrid_ext_year VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_pr DROP power_egrid_ext, DROP power_egrid_ext_pac, DROP power_egrid_ext_year, DROP pr_egrid_ext, DROP pr_egrid_ext_pac, DROP pr_egrid_ext_year');
    }
}
