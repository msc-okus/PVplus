<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210722171709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_file_upload DROP anlage_id');
    }

    public function down(Schema $schema): void
    {
        $schema = 'pvp_base';
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_file_upload ADD anlage_id INT NOT NULL');
    }
}
