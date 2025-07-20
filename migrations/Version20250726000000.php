<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove priority column from account table
 */
final class Version20250726000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove priority column from account table';
    }

    public function up(Schema $schema): void
    {
        // Remove priority column from account table
        $this->addSql('ALTER TABLE account DROP priority');
    }

    public function down(Schema $schema): void
    {
        // Add priority column back to account table
        $this->addSql('ALTER TABLE account ADD priority VARCHAR(50) NOT NULL');
    }
}
