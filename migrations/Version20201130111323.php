<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201130111323 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_pvsyst_daten DROP FOREIGN KEY FK_AC3CBB805E25CA2C');
        $this->addSql('DROP INDEX IDX_AC3CBB805E25CA2C ON anlage_pvsyst_daten');
        $this->addSql('ALTER TABLE anlage_pvsyst_daten DROP anlagen_pr_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_pvsyst_daten ADD anlagen_pr_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_pvsyst_daten ADD CONSTRAINT FK_AC3CBB805E25CA2C FOREIGN KEY (anlagen_pr_id) REFERENCES db_anl_prw (id)');
        $this->addSql('CREATE INDEX IDX_AC3CBB805E25CA2C ON anlage_pvsyst_daten (anlagen_pr_id)');
    }
}
