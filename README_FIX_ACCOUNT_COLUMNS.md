# Fix for Account Columns Issue

## Issue Description
The application is encountering an error because it's trying to access columns in the `account` table that don't exist in the database:

```
SQLSTATE[42703]: Undefined column: 7 ERROR: column t0.website does not exist LINE 1: ....contact AS contact_3, t0.priority AS priority_4, t0.website... ^
```

## Solution
The Account entity in the code has been updated to include `website`, `phone`, and `status` fields, but these columns haven't been added to the database yet.

To fix this issue, you need to add these columns to the `account` table in the database.

## Instructions

### Option 1: Run the SQL Script
Execute the provided SQL script to add the missing columns:

```bash
psql -U your_db_user -d your_db_name -f add_account_columns.sql
```

Or you can copy and paste the following SQL commands directly into your database management tool:

```sql
-- Add website, phone, and status columns to account table
ALTER TABLE account ADD COLUMN IF NOT EXISTS website VARCHAR(255) DEFAULT NULL;
ALTER TABLE account ADD COLUMN IF NOT EXISTS phone VARCHAR(50) DEFAULT NULL;
ALTER TABLE account ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'Active' NOT NULL;
```

### Option 2: Run the Migration
If you prefer to use Doctrine migrations, you can run the provided migration:

```bash
php bin/console doctrine:migrations:migrate
```

This will apply all pending migrations, including the one that adds the missing columns.

## Verification
After applying the fix, the application should work without the "Undefined column" error.
