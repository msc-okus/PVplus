<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221109124528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage ADD threshold1_pa0 VARCHAR(20) DEFAULT NULL, ADD threshold1_pa1 VARCHAR(20) DEFAULT NULL, ADD threshold1_pa3 VARCHAR(20) DEFAULT NULL, ADD threshold2_pa0 VARCHAR(20) DEFAULT NULL, ADD threshold2_pa1 VARCHAR(20) DEFAULT NULL, ADD threshold2_pa3 VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage DROP threshold1_pa0, DROP threshold1_pa1, DROP threshold1_pa3, DROP threshold2_pa0, DROP threshold2_pa1, DROP threshold2_pa3');
    }
}
