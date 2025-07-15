<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove deleted_at columns from all tables
 */
final class Version20240714000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove deleted_at columns from all tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS deleted_at');
        $this->addSql('ALTER TABLE action DROP COLUMN IF EXISTS deleted_at');
        $this->addSql('ALTER TABLE history DROP COLUMN IF EXISTS deleted_at');
        $this->addSql('ALTER TABLE "user" DROP COLUMN IF EXISTS deleted_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE action ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE history ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }
}
