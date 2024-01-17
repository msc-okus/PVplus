<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210113170252 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE times_config (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT DEFAULT NULL, type VARCHAR(20) NOT NULL, start_date VARCHAR(20) NOT NULL, end_date VARCHAR(20) NOT NULL, start_time VARCHAR(20) NOT NULL, end_time VARCHAR(20) NOT NULL, max_fail_time VARCHAR(20) NOT NULL, INDEX IDX_D7E71E80592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE times_config ADD CONSTRAINT FK_D7E71E80592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE times_config');
    }
}
