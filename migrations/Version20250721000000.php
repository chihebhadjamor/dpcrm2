<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Update existing actions to ensure closed flag matches dateClosed status
 */
final class Version20250721000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update existing actions to ensure closed flag matches dateClosed status';
    }

    public function up(Schema $schema): void
    {
        // Update all actions where date_closed is not null but closed is false
        $this->addSql('UPDATE action SET closed = TRUE WHERE date_closed IS NOT NULL AND closed = FALSE');
    }

    public function down(Schema $schema): void
    {
        // No down migration needed as this is a data fix
    }
}
