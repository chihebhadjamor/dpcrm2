<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add disabled field to user table
 */
final class Version20250725000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add disabled field to user table';
    }

    public function up(Schema $schema): void
    {
        // Add disabled column to user table with default value false if it doesn't exist
        $this->addSql('ALTER TABLE "user" ADD COLUMN IF NOT EXISTS disabled BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        // Remove disabled column from user table if it exists
        $this->addSql('ALTER TABLE "user" DROP COLUMN IF EXISTS disabled');
    }
}
