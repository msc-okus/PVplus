<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210216151823 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage ADD kw_peak_pv_syst VARCHAR(20) DEFAULT NULL, ADD design_pr VARCHAR(20) DEFAULT NULL, ADD fac_date_start DATE DEFAULT NULL, ADD pac_date_start DATE DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage DROP kw_peak_pv_syst, DROP design_pr, DROP fac_date_start, DROP pac_date_start');
    }
}
