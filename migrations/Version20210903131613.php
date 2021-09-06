<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210903131613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage DROP anl_info, DROP anl_start_year');
        $this->addSql('ALTER TABLE anlage_forecast ADD factor_week VARCHAR(20) NOT NULL, ADD factor_min VARCHAR(20) NOT NULL, ADD factor_max VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage ADD anl_info TEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, ADD anl_start_year VARCHAR(5) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE anlage_forecast DROP factor_week, DROP factor_min, DROP factor_max');
    }
}
