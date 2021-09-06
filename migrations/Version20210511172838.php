<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210511172838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage ADD temp_corr_cell_type_avg VARCHAR(20) NOT NULL, ADD temp_corr_gamma VARCHAR(20) NOT NULL, ADD temp_corr_a VARCHAR(20) NOT NULL, ADD temp_corr_b VARCHAR(20) NOT NULL, ADD temp_corr_delta_tcnd VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage DROP temp_corr_cell_type_avg, DROP temp_corr_gamma, DROP temp_corr_a, DROP temp_corr_b, DROP temp_corr_delta_tcnd');
    }
}
