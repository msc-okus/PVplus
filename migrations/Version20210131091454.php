<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210131091454 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX stamp ON anlage_grid_meter_day (stamp)');
        $this->addSql('CREATE INDEX stamp ON anlage_pvsyst_daten (stamp)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX stamp ON anlage_grid_meter_day');
        $this->addSql('DROP INDEX stamp ON anlage_pvsyst_daten');
    }
}
