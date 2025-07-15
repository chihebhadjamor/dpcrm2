<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250714194855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_responsible DROP CONSTRAINT fk_ec34ac2c9d32f035');
        $this->addSql('ALTER TABLE action_responsible DROP CONSTRAINT fk_ec34ac2ca76ed395');
        $this->addSql('DROP TABLE action_responsible');
        $this->addSql('ALTER TABLE account DROP context');
        $this->addSql('ALTER TABLE account DROP deleted_at');
        $this->addSql('ALTER TABLE account ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE account ALTER priority TYPE VARCHAR(50)');
        $this->addSql('CREATE INDEX idx_account_name ON account (name)');
        $this->addSql('ALTER TABLE action ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE action DROP summary');
        $this->addSql('ALTER TABLE action ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE action ALTER account_id DROP NOT NULL');
        $this->addSql('ALTER TABLE action ALTER type TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_47CC8C92A76ED395 ON action (user_id)');
        $this->addSql('CREATE INDEX idx_action_next_step_date ON action (next_step_date)');
        $this->addSql('ALTER TABLE history ALTER id DROP DEFAULT');
        $this->addSql('DROP INDEX uniq_8d93d6495e237e06');
        $this->addSql('ALTER TABLE "user" ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER name TYPE VARCHAR(180)');
        $this->addSql('ALTER TABLE "user" ALTER is_2fa_enabled SET DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE action_responsible (action_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(action_id, user_id))');
        $this->addSql('CREATE INDEX idx_ec34ac2c9d32f035 ON action_responsible (action_id)');
        $this->addSql('CREATE INDEX idx_ec34ac2ca76ed395 ON action_responsible (user_id)');
        $this->addSql('ALTER TABLE action_responsible ADD CONSTRAINT fk_ec34ac2c9d32f035 FOREIGN KEY (action_id) REFERENCES action (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE action_responsible ADD CONSTRAINT fk_ec34ac2ca76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE SEQUENCE user_id_seq');
        $this->addSql('SELECT setval(\'user_id_seq\', (SELECT MAX(id) FROM "user"))');
        $this->addSql('ALTER TABLE "user" ALTER id SET DEFAULT nextval(\'user_id_seq\')');
        $this->addSql('ALTER TABLE "user" ALTER name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE "user" ALTER is_2fa_enabled DROP DEFAULT');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d6495e237e06 ON "user" (name)');
        $this->addSql('DROP INDEX idx_account_name');
        $this->addSql('ALTER TABLE account ADD context VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE account ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE SEQUENCE account_id_seq');
        $this->addSql('SELECT setval(\'account_id_seq\', (SELECT MAX(id) FROM account))');
        $this->addSql('ALTER TABLE account ALTER id SET DEFAULT nextval(\'account_id_seq\')');
        $this->addSql('ALTER TABLE account ALTER priority TYPE VARCHAR(255)');
        $this->addSql('CREATE SEQUENCE history_id_seq');
        $this->addSql('SELECT setval(\'history_id_seq\', (SELECT MAX(id) FROM history))');
        $this->addSql('ALTER TABLE history ALTER id SET DEFAULT nextval(\'history_id_seq\')');
        $this->addSql('ALTER TABLE action DROP CONSTRAINT FK_47CC8C92A76ED395');
        $this->addSql('DROP INDEX IDX_47CC8C92A76ED395');
        $this->addSql('DROP INDEX idx_action_next_step_date');
        $this->addSql('ALTER TABLE action ADD summary TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE action DROP user_id');
        $this->addSql('CREATE SEQUENCE action_id_seq');
        $this->addSql('SELECT setval(\'action_id_seq\', (SELECT MAX(id) FROM action))');
        $this->addSql('ALTER TABLE action ALTER id SET DEFAULT nextval(\'action_id_seq\')');
        $this->addSql('ALTER TABLE action ALTER account_id SET NOT NULL');
        $this->addSql('ALTER TABLE action ALTER type TYPE VARCHAR(255)');
    }
}
