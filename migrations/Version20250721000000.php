<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove website and phone fields from Account entity
 */
final class Version20250721000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove website and phone fields from Account entity';
    }

    public function up(Schema $schema): void
    {
        // Use IF EXISTS to safely drop columns
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS website');
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS phone');
    }

    public function down(Schema $schema): void
    {
        // Add website and phone columns back to account table if they don't exist
        $this->addSql('ALTER TABLE account ADD COLUMN IF NOT EXISTS website VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE account ADD COLUMN IF NOT EXISTS phone VARCHAR(50) DEFAULT NULL');
    }
}
