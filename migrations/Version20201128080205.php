<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201128080205 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anl_prw ADD power_evu_pac VARCHAR(20) NOT NULL, ADD power_act_pac VARCHAR(20) NOT NULL, ADD power_exp_pac VARCHAR(20) NOT NULL, ADD plant_availability_per_pac VARCHAR(20) NOT NULL, ADD pr_pac VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anl_prw DROP power_evu_pac, DROP power_act_pac, DROP power_exp_pac, DROP plant_availability_per_pac, DROP pr_pac');
    }
}
