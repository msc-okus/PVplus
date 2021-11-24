<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211122151822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage CHANGE anl_db_unit anl_db_unit VARCHAR(10) DEFAULT \'kwh\'');
        $this->addSql('ALTER TABLE anlage_modules ADD irr_discount1 VARCHAR(20) NOT NULL, ADD irr_discount2 VARCHAR(20) NOT NULL, ADD irr_discount3 VARCHAR(20) NOT NULL, ADD irr_discount4 VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage CHANGE anl_db_unit anl_db_unit VARCHAR(10) CHARACTER SET utf8 DEFAULT \'w\' COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE anlage_modules DROP irr_discount1, DROP irr_discount2, DROP irr_discount3, DROP irr_discount4');
    }
}
