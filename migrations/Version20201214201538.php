<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201214201538 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anlage DROP FOREIGN KEY FK_6AFE6E5670C84925');
        $this->addSql('DROP INDEX UNIQ_6AFE6E5670C84925 ON db_anlage');
        $this->addSql('ALTER TABLE db_anlage DROP modul_data_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anlage ADD modul_data_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE db_anlage ADD CONSTRAINT FK_6AFE6E5670C84925 FOREIGN KEY (modul_data_id) REFERENCES db_anl_modata (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6AFE6E5670C84925 ON db_anlage (modul_data_id)');
    }
}
