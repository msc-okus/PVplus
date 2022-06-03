<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220603131002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE allowed_plants');
        $this->addSql('ALTER TABLE ticket CHANGE editor editor VARCHAR(50) NOT NULL, CHANGE inverter inverter VARCHAR(50) DEFAULT NULL, CHANGE alert_type alert_type VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE allowed_plants (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT DEFAULT NULL, anlage VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_2D6527EAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE allowed_plants ADD CONSTRAINT FK_2D6527EAA76ED395 FOREIGN KEY (user_id) REFERENCES pvp_user (id)');
        $this->addSql('ALTER TABLE ticket CHANGE editor editor VARCHAR(255) NOT NULL, CHANGE inverter inverter VARCHAR(100) DEFAULT NULL, CHANGE alert_type alert_type VARCHAR(100) NOT NULL');
    }
}
