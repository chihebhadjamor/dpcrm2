<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    private MailerInterface $mailer;
    private string $appUrl;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        string $appUrl = 'http://localhost',
        string $senderEmail = 'noreply@dpcrm.com',
        string $senderName = 'DPCRM'
    ) {
        $this->mailer = $mailer;
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
     * Send an email
     */
    private function sendEmail(string $recipientEmail, string $subject, string $htmlContent): void
    {
        $email = (new Email())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($recipientEmail)
            ->subject($subject)
            ->html($htmlContent);

        $this->mailer->send($email);
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
}
