<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Change next_step_date field in action table from Date to Datetime
 */
final class Version20240715100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change next_step_date field in action table from Date to Datetime';
    }

    public function up(Schema $schema): void
    {
        // Change next_step_date column type from DATE to TIMESTAMP
        $this->addSql('ALTER TABLE action ALTER next_step_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
    }

    public function down(Schema $schema): void
    {
        // Change next_step_date column type back to DATE
        $this->addSql('ALTER TABLE action ALTER next_step_date TYPE DATE');
    }
}
