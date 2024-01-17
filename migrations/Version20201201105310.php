<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201201105310 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anl_prw ADD power_pv_syst VARCHAR(20) NOT NULL, ADD power_pv_syst_year VARCHAR(20) NOT NULL, ADD power_pv_syst_pac VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anl_prw DROP power_pv_syst, DROP power_pv_syst_year, DROP power_pv_syst_pac');
    }
}
