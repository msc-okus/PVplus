<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230213155758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_settings ADD chart_curr1 TINYINT(1) DEFAULT NULL, ADD chart_curr2 TINYINT(1) DEFAULT NULL, ADD chart_curr3 TINYINT(1) DEFAULT NULL, ADD chart_volt1 TINYINT(1) DEFAULT NULL, ADD chart_volt2 TINYINT(1) DEFAULT NULL, ADD chart_volt3 TINYINT(1) DEFAULT NULL, ADD chart_sensor1 TINYINT(1) DEFAULT NULL, ADD chart_sensor2 TINYINT(1) DEFAULT NULL, ADD chart_sensor3 TINYINT(1) DEFAULT NULL, ADD chart_sensor4 TINYINT(1) DEFAULT NULL');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_settings DROP chart_curr1, DROP chart_curr2, DROP chart_curr3, DROP chart_volt1, DROP chart_volt2, DROP chart_volt3, DROP chart_sensor1, DROP chart_sensor2, DROP chart_sensor3, DROP chart_sensor4');
    }
}
