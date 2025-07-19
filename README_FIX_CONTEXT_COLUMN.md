# Fix for Missing Context Column in Account Table

## Issue Description
The application was encountering an error because it was trying to access a column named "context" in the `account` table that didn't exist in the database:

```
SQLSTATE[42703]: Undefined column: 7 ERROR: column "context" of relation "account" does not exist
```

## Solution
To fix this issue, I've made the following changes:

1. Created a new migration (Version20250720075324.php) that adds a "context" column to the `account` table
2. Updated the Account entity to include a "context" property with appropriate ORM annotations
3. Added getter and setter methods for the "context" property in the Account entity

## Instructions

### Apply the Migration
To apply the migration and add the missing column to your database, run:

```bash
php bin/console doctrine:migrations:migrate
```

This will execute the migration that adds the "context" column to the `account` table.

## Verification
After applying the migration, the application should work without the "Undefined column" error.

## Technical Details
- The "context" column is defined as a TEXT field that allows NULL values
- The property in the Account entity is defined as a nullable string
- Getter and setter methods follow the same pattern as other properties in the entity
