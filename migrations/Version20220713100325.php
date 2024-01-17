<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220713100325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_settings ADD pa_dep1_name VARCHAR(20) NOT NULL, ADD pa_dep2_name VARCHAR(20) NOT NULL, ADD pa_dep3_name VARCHAR(20) NOT NULL, ADD pa_default_data_gap_handling VARCHAR(20) NOT NULL, DROP name0, DROP name1, DROP name2');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_settings ADD name0 VARCHAR(20) NOT NULL, ADD name1 VARCHAR(20) NOT NULL, ADD name2 VARCHAR(20) NOT NULL, DROP pa_dep1_name, DROP pa_dep2_name, DROP pa_dep3_name, DROP pa_default_data_gap_handling');
    }
}
