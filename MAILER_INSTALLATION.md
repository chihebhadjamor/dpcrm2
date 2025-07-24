# Mailer Component Installation and Configuration

## Changes Made

1. Added the Symfony Mailer component to the project dependencies in `composer.json`:
   ```json
   {
       "symfony/mailer": "7.3.*",
       "symfony/mime": "7.3.*"
   }
   ```

2. Created a configuration file for the Symfony Mailer component at `config/packages/mailer.yaml`:
   ```yaml
   framework:
       mailer:
           dsn: '%env(MAILER_DSN)%'
   ```

3. The `.env` file already contained the necessary configuration for the mailer:
   ```
   ###> symfony/mailer ###
   # MAILER_DSN=smtp://localhost:1025
   MAILER_DSN=null://null
   APP_URL=http://localhost
   ###< symfony/mailer ###
   ```

## PDO_PGSQL Extension Installation

The error message also indicated an issue with the PDO_PGSQL extension. Please follow these steps to install it:

### For Debian/Ubuntu-based systems:

```bash
sudo apt-get update
sudo apt-get install php-pgsql
```

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

After installation, restart your web server:

```bash
sudo systemctl restart apache2   # For Apache
sudo systemctl restart nginx     # For Nginx
```

## Verifying Installation

To verify that the extension is properly installed, run:

```bash
php -m | grep pdo_pgsql
```

If the extension is installed, you should see "pdo_pgsql" in the output.

## Applying Changes

After making these changes, run:

```bash
composer update
```

This should resolve the error:
```
Cannot autowire service "App\Service\EmailService": argument "$mailer" of method "__construct()" has type "Symfony\Component\Mailer\MailerInterface" but this class was not found.
```
