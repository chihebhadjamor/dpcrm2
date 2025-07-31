<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $appUrl;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        string $appUrl = 'http://localhost',
        string $senderEmail = 'noreply@dpcrm.com',
        string $senderName = 'DPCRM'
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->appUrl = $appUrl;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    /**
     * Send a welcome email to a new user
     */
    public function sendWelcomeEmail(string $recipientEmail, string $recipientName): void
    {
        $subject = 'Welcome to DPCRM!';
        $htmlContent = $this->renderWelcomeEmailTemplate($recipientName);

        $this->sendEmail($recipientEmail, $subject, $htmlContent);
    }

    /**
     * Send an account update notification email
     */
    public function sendAccountUpdatedEmail(string $recipientEmail, string $recipientName): void
    {
        $subject = 'Your DPCRM Account Has Been Updated';
        $htmlContent = $this->renderAccountUpdatedEmailTemplate($recipientName);

        $this->sendEmail($recipientEmail, $subject, $htmlContent);
    }

    /**
     * Send a password reset email with temporary password
     */
    public function sendPasswordResetEmail(string $recipientEmail, string $recipientName, string $temporaryPassword): void
    {
        $subject = 'Your Password Has Been Reset';
        $htmlContent = $this->renderPasswordResetEmailTemplate($recipientName, $temporaryPassword);

        $this->sendEmail($recipientEmail, $subject, $htmlContent);
    }

    /**
     * Send an email
     *
     * @throws \Exception If there's an error sending the email
     */
    private function sendEmail(string $recipientEmail, string $subject, string $htmlContent): void
    {
        try {
            $email = (new Email())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($recipientEmail)
                ->subject($subject)
                ->html($htmlContent);

            $this->mailer->send($email);

            // Log successful email sending
            $this->logger->info(sprintf(
                "Email sent successfully - Subject: \"%s\", Recipient: \"%s\"",
                $subject,
                $recipientEmail
            ));
        } catch (\Exception $e) {
            // Log detailed error information with stack trace
            $errorMessage = sprintf(
                "Email sending failed - Subject: \"%s\", Recipient: \"%s\", Error: %s",
                $subject,
                $recipientEmail,
                $e->getMessage()
            );

            $this->logger->error($errorMessage, [
                'exception' => $e,
                'stack_trace' => $e->getTraceAsString()
            ]);

            // Re-throw the exception so the caller can handle it if needed
            throw $e;
        }
    }

    /**
     * Render the welcome email template
     */
    private function renderWelcomeEmailTemplate(string $recipientName): string
    {
        $loginUrl = $this->appUrl . '/login';

        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .content { margin-bottom: 30px; }
                    .button { display: inline-block; background-color: #4CAF50; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; }
                    .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Welcome to DPCRM!</h1>
                    </div>
                    <div class='content'>
                        <p>Hello $recipientName,</p>
                        <p>Welcome to DPCRM! Your account has been successfully created.</p>
                        <p>You can now access the application using the link below:</p>
                        <p style='text-align: center;'>
                            <a href='$loginUrl' class='button'>Access DPCRM</a>
                        </p>
                        <p>If you have any questions, please contact your administrator.</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message, please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Render the account updated email template
     */
    private function renderAccountUpdatedEmailTemplate(string $recipientName): string
    {
        $loginUrl = $this->appUrl . '/login';

        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .content { margin-bottom: 30px; }
                    .button { display: inline-block; background-color: #4CAF50; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; }
                    .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Your DPCRM Account Has Been Updated</h1>
                    </div>
                    <div class='content'>
                        <p>Hello $recipientName,</p>
                        <p>Your DPCRM account has been updated by an administrator.</p>
                        <p>You can access the application using the link below:</p>
                        <p style='text-align: center;'>
                            <a href='$loginUrl' class='button'>Access DPCRM</a>
                        </p>
                        <p>If you have any questions about these changes, please contact your administrator.</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message, please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Render the password reset email template
     */
    private function renderPasswordResetEmailTemplate(string $recipientName, string $temporaryPassword): string
    {
        $loginUrl = $this->appUrl . '/login';

        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .content { margin-bottom: 30px; }
                    .password-box { background-color: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; text-align: center; margin: 20px 0; font-size: 18px; }
                    .button { display: inline-block; background-color: #4CAF50; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; }
                    .warning { color: #e74c3c; font-weight: bold; }
                    .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Your Password Has Been Reset</h1>
                    </div>
                    <div class='content'>
                        <p>Hello $recipientName,</p>
                        <p>Your password has been reset by an administrator. You can now log in with the following temporary password:</p>

                        <div class='password-box'>
                            $temporaryPassword
                        </div>

                        <p class='warning'>For security reasons, please change this temporary password immediately after logging in.</p>

                        <p>You can access the application using the link below:</p>
                        <p style='text-align: center;'>
                            <a href='$loginUrl' class='button'>Access DPCRM</a>
                        </p>

                        <p>If you did not request this password reset, please contact your administrator immediately.</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message, please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Send a backlog reminder email to a user
     *
     * @param string $recipientEmail The email address of the recipient
     * @param string $recipientName The name of the recipient
     * @param array $openActions Array of open actions or empty array if no open actions
     * @return void
     */
    public function sendBacklogReminderEmail(string $recipientEmail, string $recipientName, array $openActions): void
    {
        if (count($openActions) > 0) {
            $subject = 'Your DPCRM Open Actions Reminder';
        } else {
            $subject = 'Your DPCRM Backlog is Clear!';
        }

        $htmlContent = $this->renderBacklogReminderEmailTemplate($recipientName, $openActions);

        $this->sendEmail($recipientEmail, $subject, $htmlContent);
    }

    /**
     * Render the backlog reminder email template
     *
     * @param string $recipientName The name of the recipient
     * @param array $openActions Array of open actions or empty array if no open actions
     * @return string The HTML content of the email
     */
    private function renderBacklogReminderEmailTemplate(string $recipientName, array $openActions): string
    {
        $loginUrl = $this->appUrl . '/login';
        $now = new \DateTime();

        $tableRows = '';
        if (count($openActions) > 0) {
            foreach ($openActions as $action) {
                $actionTitle = htmlspecialchars($action->getTitle());
                $accountName = $action->getAccount() ? htmlspecialchars($action->getAccount()->getName()) : 'N/A';
                $contactName = $action->getContact() ? htmlspecialchars($action->getContact()) : 'N/A';
                $actionDate = $action->getNextStepDate() ? $action->getNextStepDate()->format('Y-m-d') : 'N/A';

                // Determine cell color based on date (similar to UI logic)
                $dateColor = '#ffffff'; // Default white
                if ($action->getNextStepDate()) {
                    $daysUntil = $now->diff($action->getNextStepDate())->days;
                    $isPast = $now > $action->getNextStepDate();

                    if ($isPast) {
                        $dateColor = '#ffcccc'; // Light red for overdue
                    } elseif ($daysUntil <= 2) {
                        $dateColor = '#ffffcc'; // Light yellow for soon
                    }
                }

                $tableRows .= "
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>$accountName</td>
                        <td style='border: 1px solid #ddd; padding: 8px; width: 40%;'>$actionTitle</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>$contactName</td>
                        <td style='border: 1px solid #ddd; padding: 8px; background-color: $dateColor; min-width: 100px;'>$actionDate</td>
                    </tr>
                ";
            }
        }

        $content = '';
        if (count($openActions) > 0) {
            $content = "
                <p>Hello $recipientName,</p>
                <p>Here is a reminder of your current open actions in DPCRM:</p>

                <table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>
                    <thead>
                        <tr style='background-color: #f2f2f2;'>
                            <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Account Name</th>
                            <th style='border: 1px solid #ddd; padding: 8px; text-align: left; width: 40%;'>Action Title</th>
                            <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Contact</th>
                            <th style='border: 1px solid #ddd; padding: 8px; text-align: left; min-width: 100px;'>Action Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        $tableRows
                    </tbody>
                </table>

                <p>Please log in to the system to manage these actions:</p>
            ";
        } else {
            $content = "
                <p>Hello $recipientName,</p>
                <p>Good news! You currently have no pending open actions in DPCRM.</p>
                <p>You can log in to the system to create new actions if needed:</p>
            ";
        }

        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .content { margin-bottom: 30px; }
                    .button { display: inline-block; background-color: #4CAF50; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; }
                    .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>" . (count($openActions) > 0 ? "Your DPCRM Open Actions" : "Your DPCRM Backlog is Clear!") . "</h1>
                    </div>
                    <div class='content'>
                        $content
                        <p style='text-align: center;'>
                            <a href='$loginUrl' class='button'>Access DPCRM</a>
                        </p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message, please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
}
