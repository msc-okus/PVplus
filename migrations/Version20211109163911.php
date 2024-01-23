<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211109163911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_monthly_data ADD pv_syst_irr VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE anlagen_pv_syst_month ADD irr_design VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlagen_monthly_data DROP pv_syst_irr');
        $this->addSql('ALTER TABLE anlagen_pv_syst_month DROP irr_design');
    }
}
