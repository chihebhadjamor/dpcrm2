# Migration Fix for Website and Phone Columns

## Issue Description
The application was encountering an error during migration because it was trying to drop columns (`website` and `phone`) from the `account` table that didn't exist in the database:

```
[error] Migration DoctrineMigrations\Version20250721000000 failed during Execution. Error: "An exception occurred while executing a query: SQLSTATE[42703]: Undefined column: 7 ERROR:  column "website" of relation "account" does not exist"
```

## Root Cause
The issue was caused by migrations trying to drop columns that didn't exist in the database. Specifically:

1. `Version20250721000000.php` was trying to drop `website` and `phone` columns
2. `Version20250721074619.php` was also trying to drop the same columns

However, these columns were never added to the database in the first place. The migration that was supposed to add these columns (`Version20250718231553.php`) was mentioned in the README_FIX_WEBSITE_COLUMN.md file but didn't actually exist in the migrations directory.

## Solution Implemented
The solution was to modify the migrations to use conditional statements when dropping or adding columns:

1. Modified `Version20250721000000.php`:
   - Changed `ALTER TABLE account DROP website` to `ALTER TABLE account DROP COLUMN IF EXISTS website`
   - Changed `ALTER TABLE account DROP phone` to `ALTER TABLE account DROP COLUMN IF EXISTS phone`
   - Updated the `down()` method to use `ADD COLUMN IF NOT EXISTS` when adding columns back

2. Modified `Version20250721074619.php`:
   - Applied the same changes as above
   - Removed the unnecessary `CREATE SCHEMA public` statement from the `down()` method

These changes make the migrations more robust by ensuring they only attempt to drop columns that exist and only add columns that don't already exist.

## Alternative Solutions
An alternative solution would have been to create a new migration that adds the missing columns before the migrations that try to drop them. However, modifying the existing migrations was simpler and more direct.

## Testing
After making these changes, the migrations should run without errors. The conditional statements ensure that the migrations will work regardless of whether the columns exist or not.
