<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230123134256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_modules ADD operator_voltage_a VARCHAR(20) NOT NULL, ADD operator_voltage_b VARCHAR(20) NOT NULL, ADD operator_voltage_hight_a VARCHAR(20) NOT NULL, ADD operator_voltage_hight_b VARCHAR(20) NOT NULL, ADD operator_voltage_hight_c VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_modules DROP operator_voltage_a, DROP operator_voltage_b, DROP operator_voltage_hight_a, DROP operator_voltage_hight_b, DROP operator_voltage_hight_c');
    }
}
