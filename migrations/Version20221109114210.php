<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221109114210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_availability ADD case_0_3 INT DEFAULT NULL, ADD case_1_0 INT DEFAULT NULL, ADD case_1_3 INT DEFAULT NULL, ADD case_2_0 INT DEFAULT NULL, ADD case_2_2 INT DEFAULT NULL, ADD case_2_3 INT DEFAULT NULL, ADD case_3_0 INT DEFAULT NULL, ADD case_3_3 INT DEFAULT NULL, ADD case_4_0 INT DEFAULT NULL, ADD case_4_3 INT DEFAULT NULL, ADD case_5_0 INT DEFAULT NULL, ADD case_5_3 INT DEFAULT NULL, ADD case_6_0 INT DEFAULT NULL, ADD case_6_3 INT DEFAULT NULL, ADD control_0 INT DEFAULT NULL, ADD control_3 INT DEFAULT NULL, ADD inv_apart1_0 DOUBLE PRECISION DEFAULT NULL, ADD inv_apart1_3 DOUBLE PRECISION DEFAULT NULL, ADD inv_apart2_0 DOUBLE PRECISION DEFAULT NULL, ADD inv_apart2_3 DOUBLE PRECISION DEFAULT NULL, ADD inv_a_0 DOUBLE PRECISION DEFAULT NULL, ADD inv_a_3 DOUBLE PRECISION DEFAULT NULL, ADD remarks_0 VARCHAR(255) NOT NULL, ADD remarks_3 VARCHAR(255) NOT NULL, CHANGE remarks_2 remarks_2 VARCHAR(255) NOT NULL, CHANGE case_2_second case_0_0 INT DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_availability RENAME INDEX idx_e4c3d96f592479e0 TO IDX_528D07B2592479E0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_availability ADD case_2_second INT DEFAULT NULL, DROP case_0_0, DROP case_0_3, DROP case_1_0, DROP case_1_3, DROP case_2_0, DROP case_2_2, DROP case_2_3, DROP case_3_0, DROP case_3_3, DROP case_4_0, DROP case_4_3, DROP case_5_0, DROP case_5_3, DROP case_6_0, DROP case_6_3, DROP control_0, DROP control_3, DROP inv_apart1_0, DROP inv_apart1_3, DROP inv_apart2_0, DROP inv_apart2_3, DROP inv_a_0, DROP inv_a_3, DROP remarks_0, DROP remarks_3, CHANGE remarks_2 remarks_2 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE anlage_availability RENAME INDEX idx_528d07b2592479e0 TO IDX_E4C3D96F592479E0');
    }
}
