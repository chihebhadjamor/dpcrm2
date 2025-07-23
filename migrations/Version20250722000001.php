<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create AppSettings table and add default date format
 */
final class Version20250722000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create AppSettings table and add default date format';
    }

    public function up(Schema $schema): void
    {
        // Create app_settings table
        $this->addSql('CREATE TABLE app_settings (id SERIAL NOT NULL, setting_name VARCHAR(255) NOT NULL, setting_value VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_APP_SETTINGS_NAME ON app_settings (setting_name)');

        // Insert default date format setting
        $this->addSql('INSERT INTO app_settings (setting_name, setting_value, description) VALUES (\'date_format\', \'Y-m-d\', \'Application-wide date format\')');
    }

    public function down(Schema $schema): void
    {
        // Drop app_settings table
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE app_settings');
    }
}
