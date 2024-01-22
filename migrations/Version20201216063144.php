<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201216063144 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_group_months CHANGE irr_upper irr_upper VARCHAR(20) DEFAULT NULL, CHANGE irr_lower irr_lower VARCHAR(20) DEFAULT NULL, CHANGE shadow_loss shadow_loss VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE db_anlage DROP anl_flaesche, DROP anl_zeitzone_ws, DROP anl_ir_change, DROP anl_ow_geokey, DROP anl_photo, DROP anl_folder');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_group_months CHANGE irr_upper irr_upper VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE irr_lower irr_lower VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE shadow_loss shadow_loss VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE db_anlage ADD anl_flaesche VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_zeitzone_ws VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_ir_change VARCHAR(10) CHARACTER SET utf8 DEFAULT \'No\' NOT NULL COLLATE `utf8_general_ci`, ADD anl_ow_geokey VARCHAR(30) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_photo VARCHAR(100) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_folder TEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`');
    }
}
