# Migration Fix: Handling Non-Existent Columns

## Issue
The migration process was failing with the following error:
```
[error] Migration DoctrineMigrations\Version20250725000000 failed during Execution. Error: "An exception occurred while executing a query: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "next_step" of relation "account" does not exist"
```

This error occurred because the migration was trying to drop a column (`next_step`) that didn't exist in the `account` table.

## Solution
The solution was to modify the migrations to use PostgreSQL's `IF EXISTS` syntax when dropping columns, constraints, and indexes. This makes the migrations more robust by ensuring they don't fail when trying to drop elements that don't exist.

### Changes Made
1. Updated `Version20250725000000.php` to use `DROP COLUMN IF EXISTS` for the `next_step` column
2. Updated `Version20250720211523.php` to use `DROP COLUMN IF EXISTS` for all columns it's dropping
3. Updated `Version20250720134553.php` to use `DROP CONSTRAINT IF EXISTS`, `DROP INDEX IF EXISTS`, and `DROP COLUMN IF EXISTS` for all constraints, indexes, and columns it's dropping
4. Updated `Version20250719192118.php` to use `DROP COLUMN IF EXISTS` for the `name` column
5. Updated `Version20250727000000.php` to use `DROP COLUMN IF EXISTS` for the `type` column

## How to Test
Run the migration command to verify that the migrations now execute successfully:
```
bin/console doctrine:migrations:migrate
```

## Additional Notes
- The `next_step` column is not defined in the `Account` entity, which suggests it was either removed from the entity in a previous update or was never properly added.
- There are two migrations that try to drop the `next_step` column: `Version20250720211523.php` and `Version20250725000000.php`. This redundancy might be intentional for some reason, but it's worth noting.

## Best Practices for Future Migrations
1. Always use `IF EXISTS` when dropping database elements (columns, constraints, indexes, etc.) to make migrations more robust.
2. Check if columns exist in the entity model before trying to drop them.
3. Avoid redundant migrations that perform the same operations.
4. Consider using Doctrine's Schema API to check if elements exist before trying to modify them, when possible.
