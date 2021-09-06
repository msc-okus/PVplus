<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210217125645 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlagen_pv_syst_month (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, stamp VARCHAR(20) NOT NULL, pr_design VARCHAR(20) NOT NULL, ertrag_design VARCHAR(20) NOT NULL, INDEX IDX_92653D90592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlagen_pv_syst_month ADD CONSTRAINT FK_92653D90592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlagen_pv_syst_month');
    }
}
