<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230413083305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE ticket ADD scope VARCHAR(255) DEFAULT NULL, ADD proof_am TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE ticket_date ADD prexclude_method VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {

        $this->addSql('ALTER TABLE ticket DROP scope, DROP proof_am');
        $this->addSql('ALTER TABLE ticket_date DROP prexclude_method');
    }
}
