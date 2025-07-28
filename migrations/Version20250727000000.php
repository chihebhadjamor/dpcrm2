<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove type column from action table
 */
final class Version20250727000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove type column from action table';
    }

    public function up(Schema $schema): void
    {
        // Remove type column from action table if it exists
        $this->addSql('ALTER TABLE action DROP COLUMN IF EXISTS type');
    }

    public function down(Schema $schema): void
    {
        // Add type column back to action table
        $this->addSql('ALTER TABLE action ADD type VARCHAR(50) NOT NULL');
    }
}
