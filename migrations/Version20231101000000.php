<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add deleted_at column to account table
 */
final class Version20231101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at column to account table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS deleted_at');
    }
}
