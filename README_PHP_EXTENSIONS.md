# PHP Extensions Installation Guide

## Required PHP Extensions

This project requires the following PHP extensions:
- pdo_pgsql (for PostgreSQL database connectivity)
- ctype
- iconv

## Installation Instructions

### For Debian/Ubuntu-based systems:

```bash
sudo apt-get update
sudo apt-get install php-pgsql
```

This will install both the `pdo_pgsql` and `pgsql` extensions.

### For Red Hat/CentOS/Fedora-based systems:

```bash
sudo dnf install php-pgsql
# or for older systems
sudo yum install php-pgsql
```

### For macOS (using Homebrew):

```bash
brew install php
brew install postgresql
```

Then make sure the extension is enabled in your php.ini file.

## Verifying Installation

To verify that the extension is properly installed, run:

```bash
php -m | grep pdo_pgsql
```

If the extension is installed, you should see "pdo_pgsql" in the output.

## Troubleshooting

If you continue to see the error:
```
PHP Warning: PHP Startup: Unable to load dynamic library 'pdo_pgsql'
```

1. Check your PHP version and make sure you're installing the correct extension version
2. Verify the location of your php.ini file: `php --ini`
3. Make sure the extension is properly enabled in php.ini
4. Restart your web server after installing the extension:
   ```bash
   sudo systemctl restart apache2   # For Apache
   sudo systemctl restart nginx     # For Nginx
   ```

## Note

The application will show warnings about the missing extension but may still function for some operations. However, database connectivity will not work without the proper extension installed.
