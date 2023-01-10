<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221123130440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage ADD pa_formular0 VARCHAR(20) DEFAULT NULL, ADD pa_formular1 VARCHAR(20) DEFAULT NULL, ADD pa_formular2 VARCHAR(20) DEFAULT NULL, ADD pa_formular3 VARCHAR(20) DEFAULT NULL, ADD pr_formular0 VARCHAR(20) DEFAULT NULL, ADD pr_formular1 VARCHAR(20) DEFAULT NULL, ADD pr_formular2 VARCHAR(20) DEFAULT NULL, ADD pr_formular3 VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage DROP pa_formular0, DROP pa_formular1, DROP pa_formular2, DROP pa_formular3, DROP pr_formular0, DROP pr_formular1, DROP pr_formular2, DROP pr_formular3');
    }
}
