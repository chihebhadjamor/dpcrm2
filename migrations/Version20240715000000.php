<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add createdAt field to account table
 */
final class Version20240715000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add createdAt field to account table';
    }

    public function up(Schema $schema): void
    {
        // Add createdAt column to account table
        $this->addSql('ALTER TABLE account ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP');

        // After adding the column with a default value, remove the default constraint
        $this->addSql('ALTER TABLE account ALTER COLUMN created_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // Remove createdAt column from account table
        $this->addSql('ALTER TABLE account DROP COLUMN created_at');
    }
}
