<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250729000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change account status from string to boolean';
    }

    public function up(Schema $schema): void
    {
        // Drop the old status column and add a new boolean status column
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS status');
        $this->addSql('ALTER TABLE account ADD COLUMN IF NOT EXISTS status BOOLEAN NOT NULL DEFAULT TRUE');
    }

    public function down(Schema $schema): void
    {
        // Drop the boolean status column and add back the string status column
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS status');
        $this->addSql('ALTER TABLE account ADD COLUMN IF NOT EXISTS status VARCHAR(50) NOT NULL DEFAULT \'Active\'');
    }
}
