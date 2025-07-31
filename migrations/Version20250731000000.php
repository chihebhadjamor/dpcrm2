<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contact column to action_history table';
    }

    public function up(Schema $schema): void
    {
        // Check if the column exists before trying to add it
        $columns = $this->connection->fetchAllAssociative("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'action_history' AND column_name = 'contact'
        ");

        if (empty($columns)) {
            // Only add the column if it doesn't exist
            $this->addSql('ALTER TABLE action_history ADD contact VARCHAR(255) DEFAULT NULL');
        } else {
            $this->write('Column "contact" already exists in table "action_history". Skipping...');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_history DROP COLUMN IF EXISTS contact');
    }
}
