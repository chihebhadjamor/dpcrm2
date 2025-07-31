<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add output column to cron_log table for storing detailed log output
 */
final class Version20250731120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add output column to cron_log table for storing detailed log output';
    }

    public function up(Schema $schema): void
    {
        // Check if the output column already exists in the cron_log table
        $columns = $this->connection->fetchAllAssociative("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'cron_log' AND column_name = 'output'
        ");

        // Only add the column if it doesn't exist
        if (empty($columns)) {
            $this->addSql('ALTER TABLE cron_log ADD output TEXT DEFAULT NULL');
        } else {
            $this->write('Column "output" already exists in table "cron_log". Skipping...');
        }
    }

    public function down(Schema $schema): void
    {
        // Check if the output column exists in the cron_log table
        $columns = $this->connection->fetchAllAssociative("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'cron_log' AND column_name = 'output'
        ");

        // Only drop the column if it exists
        if (!empty($columns)) {
            $this->addSql('ALTER TABLE cron_log DROP output');
        } else {
            $this->write('Column "output" does not exist in table "cron_log". Skipping...');
        }
    }
}
