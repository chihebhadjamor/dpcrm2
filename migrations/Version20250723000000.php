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
        // Drop the foreign key constraint first
        $this->addSql('ALTER TABLE action DROP CONSTRAINT IF EXISTS FK_47CC8C92A76ED395');

        // Then drop the column
        $this->addSql('ALTER TABLE action DROP user_id');
    }

    public function down(Schema $schema): void
    {
        // Add the column back
        $this->addSql('ALTER TABLE action ADD user_id INT DEFAULT NULL');

        // Add the foreign key constraint back
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add the index back
        $this->addSql('CREATE INDEX IDX_47CC8C92A76ED395 ON action (user_id)');
    }
}
