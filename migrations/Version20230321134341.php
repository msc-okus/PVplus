<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230321134341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE ticket_date ADD replace_energy TINYINT(1) DEFAULT NULL, ADD replace_irr TINYINT(1) DEFAULT NULL, ADD use_hour TINYINT(1) DEFAULT NULL, ADD value_energy VARCHAR(255) DEFAULT NULL, ADD value_irr VARCHAR(255) DEFAULT NULL, ADD correct_energy_value VARCHAR(255) DEFAULT NULL, CHANGE answer answer VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE ticket_date DROP replace_energy, DROP replace_irr, DROP use_hour, DROP value_energy, DROP value_irr, DROP correct_energy_value, CHANGE answer answer LONGTEXT DEFAULT NULL');
    }
}
