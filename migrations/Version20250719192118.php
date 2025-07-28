<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250719192118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action ADD closed BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE action ADD date_closed TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP COLUMN IF EXISTS name');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE action DROP closed');
        $this->addSql('ALTER TABLE action DROP date_closed');
        $this->addSql('ALTER TABLE "user" ADD name VARCHAR(255) DEFAULT NULL');
    }
}
