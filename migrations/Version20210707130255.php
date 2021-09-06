<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210707130255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_pr ADD theo_power_default VARCHAR(20) NOT NULL, ADD theo_power_default_month VARCHAR(20) NOT NULL, ADD theo_power_default_pac VARCHAR(20) NOT NULL, ADD theo_power_default_year VARCHAR(20) NOT NULL, ADD pr_default_evu VARCHAR(20) NOT NULL, ADD pr_default_act VARCHAR(20) NOT NULL, ADD pr_default_exp VARCHAR(20) NOT NULL, ADD pr_default_egrid_ext VARCHAR(20) NOT NULL, ADD pr_default_month_evu VARCHAR(20) NOT NULL, ADD pr_default_month_act VARCHAR(20) NOT NULL, ADD pr_default_month_exp VARCHAR(20) NOT NULL, ADD pr_default_month_egrid_ext VARCHAR(20) NOT NULL, ADD pr_default_pac_evu VARCHAR(20) NOT NULL, ADD pr_default_pac_act VARCHAR(20) NOT NULL, ADD pr_default_pac_exp VARCHAR(20) NOT NULL, ADD pr_default_pac_egrid_ext VARCHAR(20) NOT NULL, ADD pr_default_year_evu VARCHAR(20) NOT NULL, ADD pr_default_year_act VARCHAR(20) NOT NULL, ADD pr_default_year_exp VARCHAR(20) NOT NULL, ADD pr_default_year_egrid_ext VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_pr DROP theo_power_default, DROP theo_power_default_month, DROP theo_power_default_pac, DROP theo_power_default_year, DROP pr_default_evu, DROP pr_default_act, DROP pr_default_exp, DROP pr_default_egrid_ext, DROP pr_default_month_evu, DROP pr_default_month_act, DROP pr_default_month_exp, DROP pr_default_month_egrid_ext, DROP pr_default_pac_evu, DROP pr_default_pac_act, DROP pr_default_pac_exp, DROP pr_default_pac_egrid_ext, DROP pr_default_year_evu, DROP pr_default_year_act, DROP pr_default_year_exp, DROP pr_default_year_egrid_ext');
    }
}
