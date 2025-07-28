# Migration Fix: Resolving Duplicate Column Issue

## Issue Description
When running `bin/console doctrine:migrations:migrate`, the migration `DoctrineMigrations\Version20250720000000` was failing with the following error:

```
An exception occurred while executing a query: SQLSTATE[42701]: Duplicate column: 7 ERROR: column "date_closed" of relation "action" already exists
```

## Root Cause
After investigation, it was found that:

1. `Version20250719192118.php` already adds the `closed` and `date_closed` columns to the `action` table.
2. `Version20250720000000.php` was trying to add `is_closed` and `date_closed` columns to the same table.
3. This caused a conflict because the `date_closed` column was being added twice.
4. Additionally, there was a potential naming inconsistency with `closed` vs `is_closed` columns.

## Solution
The solution was to modify `Version20250720000000.php` to be an empty migration with appropriate comments:

1. Removed the SQL statements that were adding the `is_closed` and `date_closed` columns.
2. Updated the migration description to indicate that it's now empty.
3. Added comments explaining that the functionality has been moved to `Version20250719192118.php`.

This approach:
- Resolves the duplicate column error
- Maintains the migration history without breaking existing installations
- Avoids potential confusion with similar column names (`closed` vs `is_closed`)

## Recommendation for Future Development
When creating new migrations:
1. Always check existing migrations to avoid adding duplicate columns
2. Maintain consistent naming conventions for database columns
3. Consider using conditional SQL statements if you need to handle cases where columns might already exist
