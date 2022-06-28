<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220628090058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage CHANGE has_ppc has_ppc TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_date ADD anlage_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE ticket_date ADD CONSTRAINT FK_98452242592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
        $this->addSql('CREATE INDEX IDX_98452242592479E0 ON ticket_date (anlage_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage CHANGE has_ppc has_ppc TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE ticket_date DROP FOREIGN KEY FK_98452242592479E0');
        $this->addSql('DROP INDEX IDX_98452242592479E0 ON ticket_date');
        $this->addSql('ALTER TABLE ticket_date DROP anlage_id');
    }
}
