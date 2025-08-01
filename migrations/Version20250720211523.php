<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250720211523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account ADD contacts JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS priority');
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS next_step');
        $this->addSql('ALTER TABLE action DROP COLUMN IF EXISTS type');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE account ADD priority VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE account ADD next_step VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE account DROP contacts');
        $this->addSql('ALTER TABLE action ADD type VARCHAR(50) NOT NULL');
    }
}
