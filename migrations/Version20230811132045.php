<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230811132045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_sun_shading ADD modules_db_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_sun_shading ADD CONSTRAINT FK_A55EADDE8A99FA61 FOREIGN KEY (modules_db_id) REFERENCES anlage_modules_db (id)');
        $this->addSql('CREATE INDEX IDX_A55EADDE8A99FA61 ON anlage_sun_shading (modules_db_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_sun_shading DROP FOREIGN KEY FK_A55EADDE8A99FA61');
        $this->addSql('DROP INDEX IDX_A55EADDE8A99FA61 ON anlage_sun_shading');
        $this->addSql('ALTER TABLE anlage_sun_shading DROP modules_db_id');
    }
}
