<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210130110220 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX pr_stamp ON db_anl_prw');
        $this->addSql('ALTER TABLE db_anl_prw CHANGE pr_stamp stamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE pr_stamp_ist stamp_ist DATETIME NOT NULL');
        $this->addSql('CREATE INDEX stamp ON db_anl_prw (stamp)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX stamp ON db_anl_prw');
        $this->addSql('ALTER TABLE db_anl_prw CHANGE stamp pr_stamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE stamp_ist pr_stamp_ist DATETIME NOT NULL');
        $this->addSql('CREATE INDEX pr_stamp ON db_anl_prw (pr_stamp)');
    }
}
