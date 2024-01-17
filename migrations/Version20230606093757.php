<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230606093757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE anlage ADD dccable_losses VARCHAR(100) DEFAULT NULL, ADD missmatching_losses VARCHAR(100) DEFAULT NULL, ADD inverter_efficiency_losses VARCHAR(100) DEFAULT NULL, ADD shading_losses VARCHAR(100) DEFAULT NULL, ADD accable_losses VARCHAR(100) DEFAULT NULL, ADD transformer_losses VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage DROP dccable_losses, DROP missmatching_losses, DROP inverter_efficiency_losses, DROP shading_losses, DROP accable_losses, DROP transformer_losses');
    }
}
