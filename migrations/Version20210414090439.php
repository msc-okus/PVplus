<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210414090439 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anlagen_groups (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anlage CHANGE anl_view anl_view VARCHAR(10) DEFAULT \'No\' NOT NULL');
        $this->addSql('ALTER TABLE anlage_groups ADD limit_ac VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anlagen_groups');
        $this->addSql('ALTER TABLE anlage CHANGE anl_view anl_view VARCHAR(10) CHARACTER SET utf8 DEFAULT \'Yes\' NOT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE anlage_groups DROP limit_ac');
    }
}
