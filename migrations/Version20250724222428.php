<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250724222428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Modified to use IF NOT EXISTS to prevent errors if column already exists
        $this->addSql('ALTER TABLE "user" ADD COLUMN IF NOT EXISTS disabled BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Modified to use IF EXISTS to prevent errors if column doesn't exist
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP COLUMN IF EXISTS disabled');
    }
}
