<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230420103043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE anlage ADD kpi_ticket TINYINT(1) NOT NULL, DROP path_to_import_script, CHANGE anl_mute anl_mute VARCHAR(10) NOT NULL');


    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
       $this->addSql('ALTER TABLE anlage ADD path_to_import_script VARCHAR(255) DEFAULT NULL, DROP kpi_ticket, CHANGE anl_mute anl_mute VARCHAR(10) DEFAULT \'No\' NOT NULL');

    }
}
