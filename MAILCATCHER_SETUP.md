# Local Mail Catcher Setup for Development

This document explains how to set up and use a local mail catcher for testing email functionality in the development environment.

## What is a Mail Catcher?

A mail catcher is a tool that intercepts emails sent by your application during development. Instead of sending real emails to actual recipients, the mail catcher captures these emails and provides a web interface where you can view them. This is useful for testing email functionality without the risk of sending unwanted emails to real users.

## MailHog Setup

We'll be using MailHog as our mail catcher. MailHog is a simple, lightweight SMTP server with a web interface that makes it easy to view and test emails.

### Installation

#### Using Docker Compose (Recommended)

The project includes a Docker Compose configuration for MailHog. If you have Docker and Docker Compose installed, you can start MailHog with:

```bash
docker-compose up -d
```

This will start MailHog in a Docker container, with the SMTP server listening on port 1025 and the web interface available on port 8025.

To stop MailHog:

```bash
docker-compose down
```

#### Using Docker Directly

If you prefer to use Docker directly without Docker Compose, you can run MailHog with:

```bash
docker run -d -p 1025:1025 -p 8025:8025 --name mailhog mailhog/mailhog
```

This will also start MailHog in a Docker container with the same port configuration.

#### Without Docker

If you don't have Docker, you can download the appropriate binary for your operating system from the [MailHog GitHub releases page](https://github.com/mailhog/MailHog/releases).

After downloading, make the file executable and run it:

```bash
chmod +x MailHog_linux_amd64  # Adjust filename based on your OS
./MailHog_linux_amd64         # Adjust filename based on your OS
```

### Accessing MailHog

Once MailHog is running, you can access the web interface by navigating to:

```
http://localhost:8025
```

This will show you a dashboard where you can view all emails sent by the application.

## Application Configuration

The application is already configured to use the local mail catcher in the development environment. The configuration is in the `.env` file:

```
MAILER_DSN=smtp://localhost:1025
```

This tells the application to send all emails to the SMTP server running on localhost port 1025, which is where MailHog is listening.

## Testing Email Functionality

### Using the Test Script

The easiest way to test that the mail catcher is working correctly is to use the provided test script:

1. Start MailHog using one of the methods described above
2. Run the test script from the project root:
   ```bash
   php test_email_service.php
   ```

   You can also specify a custom recipient email and name:
   ```bash
   php test_email_service.php user@example.com "John Doe"
   ```

3. Check the MailHog web interface at http://localhost:8025 to see if the test emails were captured
4. Verify that the emails have the correct recipient, sender, subject, and content

### Testing Through the Application

Alternatively, you can test by performing actions in the application:

1. Start MailHog using one of the methods described above
2. Perform an action in the application that triggers an email (e.g., create or update a user)
3. Check the MailHog web interface at http://localhost:8025 to see if the email was captured
4. Verify that the email has the correct recipient, sender, subject, and content

## Troubleshooting

If you're having issues with the mail catcher:

1. Make sure MailHog is running and listening on port 1025
2. Check that the application is configured to use `smtp://localhost:1025` as the MAILER_DSN
3. Look for any error messages in the application logs
4. Try restarting both MailHog and the application

## Additional Resources

- [MailHog GitHub Repository](https://github.com/mailhog/MailHog)
- [Symfony Mailer Documentation](https://symfony.com/doc/current/mailer.html)
