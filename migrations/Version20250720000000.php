<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add is_closed and date_closed fields to action table
 */
final class Version20250720000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_closed and date_closed fields to action table';
    }

    public function up(Schema $schema): void
    {
        // Add is_closed boolean column with default false
        $this->addSql('ALTER TABLE action ADD is_closed BOOLEAN DEFAULT FALSE NOT NULL');
        // Add date_closed datetime column that can be null
        $this->addSql('ALTER TABLE action ADD date_closed TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the columns if migration is reverted
        $this->addSql('ALTER TABLE action DROP date_closed');
        $this->addSql('ALTER TABLE action DROP is_closed');
    }
}
