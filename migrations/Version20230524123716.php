<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230524123716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE replace_values_ticket (id INT AUTO_INCREMENT NOT NULL, anlage_id BIGINT NOT NULL, stamp DATE NOT NULL, irr_horizontal VARCHAR(20) DEFAULT NULL, irr_module VARCHAR(20) DEFAULT NULL, irr_east VARCHAR(20) DEFAULT NULL, irr_west VARCHAR(20) DEFAULT NULL, power VARCHAR(20) DEFAULT NULL, INDEX IDX_CA0FA1BD592479E0 (anlage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE replace_values_ticket ADD CONSTRAINT FK_CA0FA1BD592479E0 FOREIGN KEY (anlage_id) REFERENCES anlage (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE replace_values_ticket DROP FOREIGN KEY FK_CA0FA1BD592479E0');
        $this->addSql('DROP TABLE replace_values_ticket');
    }
}
