<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210117122412 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pvp_anlage_availability ADD case_1_second INT DEFAULT NULL, ADD case_2_second INT DEFAULT NULL, ADD case_3_second INT DEFAULT NULL, ADD case_4_second INT DEFAULT NULL, ADD case_5_second INT DEFAULT NULL, ADD control_second INT DEFAULT NULL, ADD inv_apart1_second DOUBLE PRECISION DEFAULT NULL, ADD inv_apart2_second DOUBLE PRECISION DEFAULT NULL, ADD inv_a_second DOUBLE PRECISION DEFAULT NULL, ADD remarks_second VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pvp_anlage_availability DROP case_1_second, DROP case_2_second, DROP case_3_second, DROP case_4_second, DROP case_5_second, DROP control_second, DROP inv_apart1_second, DROP inv_apart2_second, DROP inv_a_second, DROP remarks_second');
    }
}
