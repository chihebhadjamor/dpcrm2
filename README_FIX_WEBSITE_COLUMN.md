# Fix for Missing Account Columns Issue

## Issue Description
The application is encountering an error because it's trying to access columns in the `account` table that don't exist in the database:

```
SQLSTATE[42703]: Undefined column: 7 ERROR: column t0.website does not exist LINE 1: ....contact AS contact_3, t0.priority AS priority_4, t0.website... ^
```

## Solution
The Account entity in the code has been updated to include `website`, `phone`, and `status` fields, but these columns haven't been added to the database yet.

To fix this issue, you need to run the existing migration that adds these columns to the `account` table in the database.

## Instructions

### Run the Migration
Execute the following command to apply all pending migrations, including the one that adds the missing columns:

```bash
php bin/console doctrine:migrations:migrate
```

This will apply the migration (Version20250718231553) that adds the website, phone, and status columns to the account table.

### Verification
After applying the migration, the application should work without the "Undefined column" error. You can verify that the columns have been added by:

1. Checking the database structure:
   ```sql
   SELECT column_name FROM information_schema.columns WHERE table_name = 'account';
   ```

2. Testing the application functionality that was previously failing.

## Alternative: Manual SQL Execution
If you prefer to add the columns manually, you can execute the following SQL commands:

```sql
ALTER TABLE account ADD website VARCHAR(255) DEFAULT NULL;
ALTER TABLE account ADD phone VARCHAR(50) DEFAULT NULL;
ALTER TABLE account ADD status VARCHAR(50) NOT NULL DEFAULT 'Active';
```

Note: The migration doesn't set a default value for the status column, but the entity defines a default value of 'Active'. It's recommended to include this default value when adding the column manually.

## Future Improvements
After fixing this issue, you might want to consider the following improvements:

1. Update the `updateAccount` method in `AccountController.php` to handle the website, phone, and status fields in its switch statement, allowing these fields to be updated through the inline editing functionality.

2. Update the account creation form in the `index` method and the `createAccountAjax` method in `AccountController.php` to include the website, phone, and status fields, ensuring that new accounts have these fields set.
