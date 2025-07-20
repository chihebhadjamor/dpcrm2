<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250720134553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP CONSTRAINT fk_7d3656a47e3c61f9');
        $this->addSql('DROP INDEX idx_7d3656a47e3c61f9');
        $this->addSql('ALTER TABLE account DROP owner_id');
        $this->addSql('ALTER TABLE action DROP CONSTRAINT fk_47cc8c92a76ed395');
        $this->addSql('DROP INDEX idx_47cc8c92a76ed395');
        $this->addSql('ALTER TABLE action DROP user_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE account ADD owner_id INT NOT NULL');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT fk_7d3656a47e3c61f9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_7d3656a47e3c61f9 ON account (owner_id)');
        $this->addSql('ALTER TABLE action ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT fk_47cc8c92a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_47cc8c92a76ed395 ON action (user_id)');
    }
}
