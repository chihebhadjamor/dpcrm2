<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create cron_log table for storing command execution logs
 */
final class Version20250801000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cron_log table for storing command execution logs';
    }

    public function up(Schema $schema): void
    {
        // Check if the sequence exists before trying to create it
        $sequences = $this->connection->fetchAllAssociative("
            SELECT sequence_name
            FROM information_schema.sequences
            WHERE sequence_name = 'cron_log_id_seq'
        ");

        if (empty($sequences)) {
            // Only create the sequence if it doesn't exist
            $this->addSql('CREATE SEQUENCE cron_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        } else {
            $this->write('Sequence "cron_log_id_seq" already exists. Skipping...');
        }

        // Check if the table exists before trying to create it
        $tables = $this->connection->fetchAllAssociative("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_name = 'cron_log' AND table_schema = 'public'
        ");

        if (empty($tables)) {
            // Only create the table if it doesn't exist
            $this->addSql('CREATE TABLE cron_log (
                id INT NOT NULL,
                command VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                status VARCHAR(50) NOT NULL,
                message TEXT DEFAULT NULL,
                output TEXT DEFAULT NULL,
                PRIMARY KEY(id)
            )');
            $this->addSql('ALTER TABLE cron_log ALTER id SET DEFAULT nextval(\'cron_log_id_seq\')');
        } else {
            $this->write('Table "cron_log" already exists. Skipping...');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE cron_log_id_seq CASCADE');
        $this->addSql('DROP TABLE cron_log');
    }
}
