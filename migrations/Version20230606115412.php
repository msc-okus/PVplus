<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230606115412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('ALTER TABLE anlage ADD transformer_limitation VARCHAR(100) DEFAULT NULL, ADD inverter_limitation VARCHAR(100) DEFAULT NULL, ADD dynamic_limitations VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
         $this->addSql('ALTER TABLE anlage DROP transformer_limitation, DROP inverter_limitation, DROP dynamic_limitations');

    }
}
