<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230213153548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_settings ADD chart_analyse1 TINYINT(1) DEFAULT NULL, ADD chart_analyse2 TINYINT(1) DEFAULT NULL, ADD chart_analyse3 TINYINT(1) DEFAULT NULL, ADD chart_analyse4 TINYINT(1) DEFAULT NULL, ADD chart_analyse5 TINYINT(1) DEFAULT NULL, ADD chart_analyse6 TINYINT(1) DEFAULT NULL, ADD chart_analyse7 TINYINT(1) DEFAULT NULL, ADD chart_analyse8 TINYINT(1) DEFAULT NULL, ADD chart_analyse9 TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_settings DROP chart_analyse1, DROP chart_analyse2, DROP chart_analyse3, DROP chart_analyse4, DROP chart_analyse5, DROP chart_analyse6, DROP chart_analyse7, DROP chart_analyse8, DROP chart_analyse9');
    }
}
