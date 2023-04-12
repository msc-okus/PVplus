<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230327110006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE ticket ADD who_hided VARCHAR(255) DEFAULT NULL, ADD when_hidded VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_date ADD reason_text VARCHAR(255) DEFAULT NULL, CHANGE answer answer LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket DROP who_hided, DROP when_hidded');
        $this->addSql('ALTER TABLE ticket_date DROP reason_text, CHANGE answer answer VARCHAR(255) DEFAULT NULL');
    }
}
