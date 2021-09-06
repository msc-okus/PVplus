<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201221101755 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage ADD anl_wind_unit VARCHAR(10) DEFAULT \'km/h\' NOT NULL, ADD anl_view VARCHAR(10) DEFAULT \'Yes\' NOT NULL');
        $this->addSql('ALTER TABLE anlage RENAME INDEX idx_6afe6e564a52fb9c TO IDX_7BE9356B4A52FB9C');
        $this->addSql('ALTER TABLE anlage RENAME INDEX idx_6afe6e569e475da2 TO IDX_7BE9356B9E475DA2');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage DROP anl_wind_unit, DROP anl_view');
        $this->addSql('ALTER TABLE anlage RENAME INDEX idx_7be9356b9e475da2 TO IDX_6AFE6E569E475DA2');
        $this->addSql('ALTER TABLE anlage RENAME INDEX idx_7be9356b4a52fb9c TO IDX_6AFE6E564A52FB9C');
    }
}
