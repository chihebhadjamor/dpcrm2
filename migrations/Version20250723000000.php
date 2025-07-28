<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove user_id column from action table
 */
final class Version20250723000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove user_id column from action table';
    }

    public function up(Schema $schema): void
    {
        // This migration is now a no-op since the column was already removed by Version20250720134553
        $this->write('Column user_id was already removed by a previous migration (Version20250720134553), skipping...');
    }

    public function down(Schema $schema): void
    {
        // Check if the column already exists before trying to add it
        $columns = $this->connection->fetchAllAssociative("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'action' AND column_name = 'user_id'
        ");

        if (empty($columns)) {
            // Add the column back
            $this->addSql('ALTER TABLE action ADD user_id INT DEFAULT NULL');

            // Add the foreign key constraint back
            $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

            // Add the index back
            $this->addSql('CREATE INDEX IDX_47CC8C92A76ED395 ON action (user_id)');
        } else {
            $this->write('Column user_id already exists in action table, skipping...');
        }
    }
}
