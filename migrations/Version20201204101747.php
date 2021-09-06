<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201204101747 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_groups ADD anlage_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_groups ADD CONSTRAINT FK_4DA2D2C1592479E0 FOREIGN KEY (anlage_id) REFERENCES db_anlage (id)');
        $this->addSql('CREATE INDEX IDX_4DA2D2C1592479E0 ON anlage_groups (anlage_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_groups DROP FOREIGN KEY FK_4DA2D2C1592479E0');
        $this->addSql('DROP INDEX IDX_4DA2D2C1592479E0 ON anlage_groups');
        $this->addSql('ALTER TABLE anlage_groups DROP anlage_id');
    }
}
