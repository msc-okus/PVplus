<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230419140154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE ticket DROP begin_hidden, DROP end_hidden');
        $this->addSql('ALTER TABLE ticket_date ADD begin_hidden DATE DEFAULT NULL, ADD end_hidden DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket ADD begin_hidden DATE DEFAULT NULL, ADD end_hidden DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_date DROP begin_hidden, DROP end_hidden');
    }
}
