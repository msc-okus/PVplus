<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210429063803 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlage_month (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT NOT NULL, month INT NOT NULL, irr_upper VARCHAR(20) NOT NULL, irr_lower VARCHAR(20) NOT NULL, shadow_loss VARCHAR(20) NOT NULL, INDEX IDX_FCAFBDF6592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage_month ADD CONSTRAINT FK_FCAFBDF6592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlage_month');
    }
}
