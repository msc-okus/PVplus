<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210504075537 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pvp_anlagen_status ADD forecast_div_year VARCHAR(20) NOT NULL, ADD forecast_div_minus_year VARCHAR(20) NOT NULL, ADD forecast_div_plus_year VARCHAR(20) NOT NULL, ADD forecast_div_pac VARCHAR(20) NOT NULL, ADD forecast_div_minus_pac VARCHAR(20) NOT NULL, ADD forecast_div_plus_pac VARCHAR(20) NOT NULL, ADD forecast_date DATETIME NOT NULL, DROP forecast, DROP forecast_div_minus, DROP forecast_div_plus');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pvp_anlagen_status ADD forecast VARCHAR(20) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, ADD forecast_div_minus VARCHAR(20) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, ADD forecast_div_plus VARCHAR(20) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, DROP forecast_div_year, DROP forecast_div_minus_year, DROP forecast_div_plus_year, DROP forecast_div_pac, DROP forecast_div_minus_pac, DROP forecast_div_plus_pac, DROP forecast_date');
    }
}
