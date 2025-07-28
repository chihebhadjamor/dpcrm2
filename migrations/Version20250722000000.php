<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Move contact field from Account to Action
 */
final class Version20250722000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move contact field from Account to Action';
    }

    public function up(Schema $schema): void
    {
        // Add contact field to Action table if it doesn't exist
        $this->addSql('ALTER TABLE action ADD COLUMN IF NOT EXISTS contact VARCHAR(255) DEFAULT NULL');

        // Remove contact field from Account table if it exists
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS contact');
    }

    public function down(Schema $schema): void
    {
        // Add contact field back to Account table if it doesn't exist
        $this->addSql('ALTER TABLE account ADD COLUMN IF NOT EXISTS contact VARCHAR(255) DEFAULT NULL');

        // Remove contact field from Action table if it exists
        $this->addSql('ALTER TABLE action DROP COLUMN IF EXISTS contact');
    }
}
