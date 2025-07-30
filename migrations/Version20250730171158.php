<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250730171158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE action_history_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE action_history (id INT NOT NULL, action_id INT NOT NULL, owner_id INT DEFAULT NULL, updated_by_id INT NOT NULL, title VARCHAR(255) NOT NULL, action_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, closed BOOLEAN NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FD18F8AA9D32F035 ON action_history (action_id)');
        $this->addSql('CREATE INDEX IDX_FD18F8AA7E3C61F9 ON action_history (owner_id)');
        $this->addSql('CREATE INDEX IDX_FD18F8AA896DBBDE ON action_history (updated_by_id)');
        $this->addSql('ALTER TABLE action_history ADD CONSTRAINT FK_FD18F8AA9D32F035 FOREIGN KEY (action_id) REFERENCES action (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE action_history ADD CONSTRAINT FK_FD18F8AA7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE action_history ADD CONSTRAINT FK_FD18F8AA896DBBDE FOREIGN KEY (updated_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE action_history_id_seq CASCADE');
        $this->addSql('ALTER TABLE action_history DROP CONSTRAINT FK_FD18F8AA9D32F035');
        $this->addSql('ALTER TABLE action_history DROP CONSTRAINT FK_FD18F8AA7E3C61F9');
        $this->addSql('ALTER TABLE action_history DROP CONSTRAINT FK_FD18F8AA896DBBDE');
        $this->addSql('DROP TABLE action_history');
    }
}
