<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240605115827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE anlage_file ADD notification_info_id INT DEFAULT NULL, CHANGE anlage_id anlage_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE anlage_file ADD CONSTRAINT FK_925543D648954B4F FOREIGN KEY (notification_info_id) REFERENCES notification_info (id)');
        $this->addSql('CREATE INDEX IDX_925543D648954B4F ON anlage_file (notification_info_id)');


    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE notification_info DROP FOREIGN KEY FK_73B57A6D69C8E5D4');
        $this->addSql('DROP INDEX IDX_73B57A6D69C8E5D4 ON notification_info');


    }
}
