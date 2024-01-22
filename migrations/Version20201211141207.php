<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201211141207 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_group_modules ADD module_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_group_modules ADD CONSTRAINT FK_D66B51C36E37B28A FOREIGN KEY (module_type_id) REFERENCES anlage_modules (id)');
        $this->addSql('CREATE INDEX IDX_D66B51C36E37B28A ON anlage_group_modules (module_type_id)');
        $this->addSql('ALTER TABLE anlage_group_modules RENAME INDEX idx_464ceea43452c081 TO IDX_D66B51C33452C081');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_group_modules DROP FOREIGN KEY FK_D66B51C36E37B28A');
        $this->addSql('DROP INDEX IDX_D66B51C36E37B28A ON anlage_group_modules');
        $this->addSql('ALTER TABLE anlage_group_modules DROP module_type_id');
        $this->addSql('ALTER TABLE anlage_group_modules RENAME INDEX idx_d66b51c33452c081 TO IDX_464CEEA43452C081');
    }
}
