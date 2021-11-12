<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211026114014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage DROP FOREIGN KEY FK_7BE9356B7ED8449B');
        $this->addSql('DROP INDEX IDX_7BE9356B7ED8449B ON anlage');
        $this->addSql('ALTER TABLE anlage DROP economic_var_names_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage ADD economic_var_names_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage ADD CONSTRAINT FK_7BE9356B7ED8449B FOREIGN KEY (economic_var_names_id) REFERENCES economic_var_names (id)');
        $this->addSql('CREATE INDEX IDX_7BE9356B7ED8449B ON anlage (economic_var_names_id)');
    }
}
