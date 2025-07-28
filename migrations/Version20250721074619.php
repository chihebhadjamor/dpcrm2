<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250721074619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Modified to safely drop columns if they exist
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS website');
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS phone');
    }

    public function down(Schema $schema): void
    {
        // Modified to safely add columns if they don't exist
        $this->addSql('ALTER TABLE account ADD COLUMN IF NOT EXISTS website VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE account ADD COLUMN IF NOT EXISTS phone VARCHAR(50) DEFAULT NULL');
    }
}
