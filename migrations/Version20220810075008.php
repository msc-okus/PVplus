<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220810075008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket ADD kpi_pa_dep1 VARCHAR(20) DEFAULT NULL, ADD kpi_pa_dep2 VARCHAR(20) DEFAULT NULL, ADD kpi_pa_dep3 VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_date ADD kpi_pa_dep1 VARCHAR(20) DEFAULT NULL, ADD kpi_pa_dep2 VARCHAR(20) DEFAULT NULL, ADD kpi_pa_dep3 VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket DROP kpi_pa_dep1, DROP kpi_pa_dep2, DROP kpi_pa_dep3');
        $this->addSql('ALTER TABLE ticket_date DROP kpi_pa_dep1, DROP kpi_pa_dep2, DROP kpi_pa_dep3');
    }
}
