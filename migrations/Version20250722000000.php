<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Move contact field from Account to Action
 */
final class Version20250722000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move contact field from Account to Action';
    }

    public function up(Schema $schema): void
    {
        // Add contact field to Action table
        $this->addSql('ALTER TABLE action ADD contact VARCHAR(255) DEFAULT NULL');

        // Remove contact field from Account table
        $this->addSql('ALTER TABLE account DROP contact');
    }

    public function down(Schema $schema): void
    {
        // Add contact field back to Account table
        $this->addSql('ALTER TABLE account ADD contact VARCHAR(255) DEFAULT NULL');

        // Remove contact field from Action table
        $this->addSql('ALTER TABLE action DROP contact');
    }
}
