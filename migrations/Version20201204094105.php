<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201204094105 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_group_moduls ADD anlage_group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_group_moduls ADD CONSTRAINT FK_464CEEA43452C081 FOREIGN KEY (anlage_group_id) REFERENCES anlage_groups (id)');
        $this->addSql('CREATE INDEX IDX_464CEEA43452C081 ON anlage_group_moduls (anlage_group_id)');
        $this->addSql('ALTER TABLE anlage_group_months ADD anlage_group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_group_months ADD CONSTRAINT FK_4C5FA1F33452C081 FOREIGN KEY (anlage_group_id) REFERENCES anlage_groups (id)');
        $this->addSql('CREATE INDEX IDX_4C5FA1F33452C081 ON anlage_group_months (anlage_group_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_group_moduls DROP FOREIGN KEY FK_464CEEA43452C081');
        $this->addSql('DROP INDEX IDX_464CEEA43452C081 ON anlage_group_moduls');
        $this->addSql('ALTER TABLE anlage_group_moduls DROP anlage_group_id');
        $this->addSql('ALTER TABLE anlage_group_months DROP FOREIGN KEY FK_4C5FA1F33452C081');
        $this->addSql('DROP INDEX IDX_4C5FA1F33452C081 ON anlage_group_months');
        $this->addSql('ALTER TABLE anlage_group_months DROP anlage_group_id');
    }
}
