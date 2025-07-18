# Database Reset and Initialization Guide

This guide provides instructions for resetting the application's database and populating it with fresh data.

## Prerequisites

Before proceeding, ensure you have:
- PHP 8.2 or higher installed
- Composer installed
- PostgreSQL installed and running
- Proper database credentials in your `.env` file

## Installation of Required Packages

If you haven't already installed the Doctrine Fixtures Bundle, run:

```bash
bash install_fixtures.sh
```

Or manually:

```bash
composer require --dev doctrine/doctrine-fixtures-bundle
```

## Automated Reset and Initialization

For convenience, you can use the provided script to automate the entire process:

```bash
./reset_database.sh
```

This script will:
1. Install the Doctrine Fixtures Bundle if needed
2. Drop the existing database
3. Create a new database
4. Apply migrations to create the schema
5. Load the fixtures without requiring user interaction

## Manual Reset and Initialization

If you prefer to run the commands manually, follow these steps:

### Step 1: Reset Database Schema

To completely drop the existing database and recreate the schema from scratch:

```bash
# Drop the database
php bin/console doctrine:database:drop --force

# Create the database
php bin/console doctrine:database:create

# Apply migrations to create the schema
php bin/console doctrine:migrations:migrate
```

### Step 2: Load Fresh User Data

To populate the clean database with predefined users:

```bash
# Load data fixtures
php bin/console doctrine:fixtures:load
```

When prompted to purge the database, confirm with 'yes'.

## Default Users

After loading the fixtures, the following users will be available:

1. Administrator:
   - Email: admin@example.com
   - Password: admin123
   - Roles: ROLE_ADMIN, ROLE_USER

2. Standard User:
   - Email: user@example.com
   - Password: user123
   - Role: ROLE_USER

**Note:** For production environments, you should change these default passwords to more secure ones.

## Troubleshooting

If you encounter any issues:

1. Check your database connection settings in the `.env` file
2. Ensure PostgreSQL is running
3. Verify that you have the necessary permissions to create and drop databases
4. Check the Symfony logs for detailed error messages
