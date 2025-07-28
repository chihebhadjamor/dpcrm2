<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove owner_id column from account table
 */
final class Version20250724000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove owner_id column from account table';
    }

    public function up(Schema $schema): void
    {
        // Drop the foreign key constraint first
        $this->addSql('ALTER TABLE account DROP CONSTRAINT IF EXISTS FK_7D3656A47E3C61F9');

        // Then drop the column if it exists
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS owner_id');
    }

    public function down(Schema $schema): void
    {
        // Check if the column already exists before trying to add it
        $this->addSql('DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT FROM information_schema.columns
                WHERE table_name = \'account\' AND column_name = \'owner_id\'
            ) THEN
                -- Add the column back
                ALTER TABLE account ADD owner_id INT DEFAULT NULL;

                -- Add the foreign key constraint back
                ALTER TABLE account ADD CONSTRAINT FK_7D3656A47E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

                -- Add the index back
                CREATE INDEX IDX_7D3656A47E3C61F9 ON account (owner_id);
            END IF;
        END
        $$;');
    }
}
