<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove next_step column from account table
 */
final class Version20250725000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove next_step column from account table';
    }

    public function up(Schema $schema): void
    {
        // Remove next_step column from account table if it exists
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS next_step');
    }

    public function down(Schema $schema): void
    {
        // Add next_step column back to account table if it doesn't exist
        $this->addSql('ALTER TABLE account ADD COLUMN IF NOT EXISTS next_step VARCHAR(255) DEFAULT NULL');
    }
}
