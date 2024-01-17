<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210212184749 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlagen_pr (id BIGINT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, anl_id VARCHAR(50) NOT NULL, stamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, stamp_ist DATETIME NOT NULL, pr_act VARCHAR(20) NOT NULL, pr_exp VARCHAR(20) NOT NULL, pr_diff VARCHAR(20) NOT NULL, pr_diff_poz VARCHAR(20) NOT NULL, irradiation VARCHAR(20) NOT NULL, pr_act_poz VARCHAR(20) NOT NULL, pr_exp_poz VARCHAR(20) NOT NULL, panneltemp VARCHAR(20) NOT NULL, power_evu VARCHAR(20) NOT NULL, power_evu_year VARCHAR(20) NOT NULL, power_act_year VARCHAR(20) NOT NULL, power_exp_year VARCHAR(20) NOT NULL, cust_irr VARCHAR(20) NOT NULL, pr_evu_proz VARCHAR(20) NOT NULL, plant_availability VARCHAR(20) NOT NULL, plant_availability_per_year VARCHAR(20) NOT NULL, plant_availability_per_pac VARCHAR(20) NOT NULL, plant_availability_second VARCHAR(20) NOT NULL, plant_availability_per_year_second VARCHAR(20) NOT NULL, plant_availability_per_pac_second VARCHAR(20) NOT NULL, power_theo VARCHAR(20) NOT NULL, g4n_irr_avg VARCHAR(20) NOT NULL, power_evu_pac VARCHAR(20) NOT NULL, power_act_pac VARCHAR(20) NOT NULL, power_exp_pac VARCHAR(20) NOT NULL, pr_pac VARCHAR(20) NOT NULL, electricity_grid VARCHAR(20) NOT NULL, power_pv_syst VARCHAR(20) NOT NULL, power_pv_syst_year VARCHAR(20) NOT NULL, power_pv_syst_pac VARCHAR(20) NOT NULL, temp_correction VARCHAR(20) NOT NULL, theo_power_pac VARCHAR(20) NOT NULL, theo_power_year VARCHAR(20) NOT NULL, pac_date VARCHAR(20) NOT NULL, irradiation_json JSON DEFAULT NULL, temperatur_json JSON DEFAULT NULL, wind_json JSON DEFAULT NULL, forecast_sum_act VARCHAR(20) NOT NULL, forecast_sum VARCHAR(20) NOT NULL, forecast_div_minus VARCHAR(20) NOT NULL, forecast_div_plus VARCHAR(20) NOT NULL, INDEX IDX_8A85E82F592479E0 (anlage_id), INDEX stamp (stamp), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlagen_pr ADD CONSTRAINT FK_8A85E82F592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
        $this->addSql('DROP TABLE anlagen_pranlagen_pr');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlagen_pranlagen_pr (id BIGINT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, anl_id VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, stamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, stamp_ist DATETIME NOT NULL, pr_act VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, pr_exp VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, pr_diff VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, pr_diff_poz VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, irradiation VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, pr_act_poz VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, pr_exp_poz VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, panneltemp VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_evu VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_evu_year VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_act_year VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_exp_year VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, cust_irr VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, pr_evu_proz VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, plant_availability VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, plant_availability_per_year VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, plant_availability_per_pac VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, plant_availability_second VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, plant_availability_per_year_second VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, plant_availability_per_pac_second VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_theo VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, g4n_irr_avg VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_evu_pac VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_act_pac VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_exp_pac VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, pr_pac VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, electricity_grid VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_pv_syst VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_pv_syst_year VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, power_pv_syst_pac VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, temp_correction VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, theo_power_pac VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, theo_power_year VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, pac_date VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, irradiation_json JSON DEFAULT NULL, temperatur_json JSON DEFAULT NULL, wind_json JSON DEFAULT NULL, INDEX IDX_8280AD83592479E0 (anlage_id), INDEX stamp (stamp), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE anlagen_pranlagen_pr ADD CONSTRAINT FK_8280AD83592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
        $this->addSql('DROP TABLE anlagen_pr');
    }
}
