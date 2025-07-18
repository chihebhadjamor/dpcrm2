#!/bin/bash

# Reset Database and Load Fixtures Script
# This script automates the process of resetting the database and loading fixtures

echo "Starting database reset process..."

# Check if Doctrine Fixtures Bundle is installed
if ! composer show doctrine/doctrine-fixtures-bundle > /dev/null 2>&1; then
    echo "Installing Doctrine Fixtures Bundle..."
    composer require --dev doctrine/doctrine-fixtures-bundle
fi

# Drop the database
echo "Dropping the database..."
php bin/console doctrine:database:drop --force

# Create the database
echo "Creating the database..."
php bin/console doctrine:database:create

# Apply migrations
echo "Applying migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures
echo "Loading fixtures..."
php bin/console doctrine:fixtures:load --no-interaction

echo "Database reset and initialization complete!"
echo "You can now log in with:"
echo "  - Admin: admin@example.com / admin123"
echo "  - User: user@example.com / user123"
