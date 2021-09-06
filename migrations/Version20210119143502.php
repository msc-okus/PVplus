<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210119143502 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anl_prw ADD plant_availability_second VARCHAR(20) NOT NULL, ADD plant_availability_per_year_second VARCHAR(20) NOT NULL, ADD plant_availability_per_pac_second VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anl_prw DROP plant_availability_second, DROP plant_availability_per_year_second, DROP plant_availability_per_pac_second');
    }
}
