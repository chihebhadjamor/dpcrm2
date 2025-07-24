<?php

// This script tests the EmailService by sending a test email
// It requires the Symfony environment to be set up

require dirname(__FILE__).'/vendor/autoload.php';
require dirname(__FILE__).'/config/bootstrap.php';

use App\Service\EmailService;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;

// Create the Symfony kernel
$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get the mailer and logger services
$mailer = $container->get(MailerInterface::class);
$logger = $container->get(LoggerInterface::class);

// Get the app URL from environment
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost';

// Create the email service
$emailService = new EmailService($mailer, $logger, $appUrl);

// Output a message to indicate the test is running
echo "Testing EmailService...\n";

// Get the test recipient email from command line or use a default
$recipientEmail = $argv[1] ?? 'test@example.com';
$recipientName = $argv[2] ?? 'Test User';

echo "Sending test welcome email to: $recipientEmail ($recipientName)\n";

try {
    // Send a test welcome email
    $emailService->sendWelcomeEmail($recipientEmail, $recipientName);
    echo "Success! Welcome email sent successfully.\n";

    // Send a test account updated email
    $emailService->sendAccountUpdatedEmail($recipientEmail, $recipientName);
    echo "Success! Account updated email sent successfully.\n";

    echo "\nPlease check your mail catcher at http://localhost:8025 to view the test emails.\n";
    echo "If you don't see the emails, make sure your mail catcher is running.\n";
    echo "See MAILCATCHER_SETUP.md for instructions on setting up a mail catcher.\n";
} catch (\Exception $e) {
    echo "Error: Failed to send email: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure your mail catcher is running at localhost:1025\n";
    echo "2. Check that MAILER_DSN in .env is set to 'smtp://localhost:1025'\n";
    echo "3. See MAILCATCHER_SETUP.md for detailed setup instructions\n";
}

echo "\nTest complete!\n";
