<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201204143213 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_groups ADD operator_power_a VARCHAR(20) NOT NULL, ADD operator_power_b VARCHAR(20) NOT NULL, ADD operator_power_c VARCHAR(20) NOT NULL, ADD operator_current_a VARCHAR(20) NOT NULL, ADD operator_current_b VARCHAR(20) NOT NULL, ADD operator_current_c VARCHAR(20) NOT NULL, ADD operator_current_d VARCHAR(20) NOT NULL, ADD operator_current_e VARCHAR(20) NOT NULL, DROP operater_power_a, DROP operater_power_b, DROP operater_power_c, DROP operater_current_a, DROP operater_current_b, DROP operater_current_c, DROP operater_current_d, DROP operater_current_e');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anlage_groups ADD operater_power_a VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD operater_power_b VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD operater_power_c VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD operater_current_a VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD operater_current_b VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD operater_current_c VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD operater_current_d VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD operater_current_e VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP operator_power_a, DROP operator_power_b, DROP operator_power_c, DROP operator_current_a, DROP operator_current_b, DROP operator_current_c, DROP operator_current_d, DROP operator_current_e');
    }
}
