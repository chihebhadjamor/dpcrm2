<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add contacts field to Account entity
 */
final class Version20250726000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contacts field to Account entity';
    }

    public function up(Schema $schema): void
    {
        // Add contacts field to account table as JSON if it doesn't exist
        $this->addSql('ALTER TABLE account ADD COLUMN IF NOT EXISTS contacts JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove contacts field from account table
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS contacts');
    }
}
