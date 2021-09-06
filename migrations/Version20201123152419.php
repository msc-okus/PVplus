<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201123152419 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anlage DROP anl_zeitzone_ir, DROP anl_zeitzone_dc, DROP anl_zeitzone_dce, DROP anl_ir_table_val, DROP anl_zwr');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE db_anlage ADD anl_zeitzone_ir VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_zeitzone_dc VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_zeitzone_dce VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_ir_table_val VARCHAR(10) CHARACTER SET utf8 DEFAULT \'double\' NOT NULL COLLATE `utf8_general_ci`, ADD anl_zwr VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`');
    }
}
