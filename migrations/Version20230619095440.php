<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230619095440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_date DROP FOREIGN KEY FK_98452242700047D2');
        $this->addSql('DROP INDEX fk_98452242700047d2_idx ON ticket_date');
        $this->addSql('CREATE INDEX IDX_98452242700047D2 ON ticket_date (ticket_id)');
        $this->addSql('ALTER TABLE ticket_date ADD CONSTRAINT FK_98452242700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_date DROP FOREIGN KEY FK_98452242700047D2');
        $this->addSql('DROP INDEX idx_98452242700047d2 ON ticket_date');
        $this->addSql('CREATE INDEX FK_98452242700047D2_idx ON ticket_date (ticket_id)');
        $this->addSql('ALTER TABLE ticket_date ADD CONSTRAINT FK_98452242700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
    }
}
