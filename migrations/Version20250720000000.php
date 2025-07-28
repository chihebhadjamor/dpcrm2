<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * This migration is now empty as its functionality has been moved to Version20250719192118
 * which already adds the 'closed' and 'date_closed' columns to the action table
 */
final class Version20250720000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Empty migration - functionality moved to Version20250719192118';
    }

    public function up(Schema $schema): void
    {
        // This migration is now empty as its functionality has been moved to Version20250719192118
    }

    public function down(Schema $schema): void
    {
        // This migration is now empty as its functionality has been moved to Version20250719192118
    }
}
