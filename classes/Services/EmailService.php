<?php

namespace Karyalay\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Service
 * 
 * Handles sending emails using PHPMailer with SMTP configuration
 */
class EmailService
{
    private PHPMailer $mailer;
    private string $fromAddress;
    private string $fromName;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure PHPMailer with SMTP settings from environment
     */
    private function configure(): void
    {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'localhost';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = (int)($_ENV['MAIL_PORT'] ?? 587);

            // From address
            $this->fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@karyalay.com';
            $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Karyalay Portal';
            
            $this->mailer->setFrom($this->fromAddress, $this->fromName);
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }

    /**
     * Send an email
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string|null $plainTextBody Plain text version of email body
     * @return bool True if email was sent successfully
     */
    public function send(string $to, string $subject, string $body, ?string $plainTextBody = null): bool
    {
        try {
            // Reset recipients for reuse
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($to);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            if ($plainTextBody) {
                $this->mailer->AltBody = $plainTextBody;
            }

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send contact form notification to admin
     * 
     * @param array $leadData Lead data from contact form
     * @return bool True if email was sent successfully
     */
    public function sendContactFormNotification(array $leadData): bool
    {
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@karyalay.com';
        
        $subject = "New Contact Form Submission from {$leadData['name']}";
        
        $body = $this->renderContactFormTemplate($leadData);
        $plainText = $this->renderContactFormPlainText($leadData);
        
        return $this->send($adminEmail, $subject, $body, $plainText);
    }

    /**
     * Send demo request notification to admin
     * 
     * @param array $leadData Lead data from demo request form
     * @return bool True if email was sent successfully
     */
    public function sendDemoRequestNotification(array $leadData): bool
    {
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@karyalay.com';
        
        $subject = "New Demo Request from {$leadData['name']}";
        
        $body = $this->renderDemoRequestTemplate($leadData);
        $plainText = $this->renderDemoRequestPlainText($leadData);
        
        return $this->send($adminEmail, $subject, $body, $plainText);
    }

    /**
     * Render HTML template for contact form notification
     */
    private function renderContactFormTemplate(array $leadData): string
    {
        $name = htmlspecialchars($leadData['name'] ?? '');
        $email = htmlspecialchars($leadData['email'] ?? '');
        $phone = htmlspecialchars($leadData['phone'] ?? '');
        $message = nl2br(htmlspecialchars($leadData['message'] ?? ''));
        $submittedAt = date('Y-m-d H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4a5568; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f7fafc; padding: 20px; border: 1px solid #e2e8f0; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #4a5568; }
        .value { margin-top: 5px; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Contact Form Submission</h2>
        </div>
        <div class="content">
            <div class="field">
                <div class="label">Name:</div>
                <div class="value">{$name}</div>
            </div>
            <div class="field">
                <div class="label">Email:</div>
                <div class="value">{$email}</div>
            </div>
            <div class="field">
                <div class="label">Phone:</div>
                <div class="value">{$phone}</div>
            </div>
            <div class="field">
                <div class="label">Message:</div>
                <div class="value">{$message}</div>
            </div>
            <div class="field">
                <div class="label">Submitted At:</div>
                <div class="value">{$submittedAt}</div>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated notification from Karyalay Portal System</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render plain text version for contact form notification
     */
    private function renderContactFormPlainText(array $leadData): string
    {
        $name = $leadData['name'] ?? '';
        $email = $leadData['email'] ?? '';
        $phone = $leadData['phone'] ?? '';
        $message = $leadData['message'] ?? '';
        $submittedAt = date('Y-m-d H:i:s');

        return <<<TEXT
NEW CONTACT FORM SUBMISSION

Name: {$name}
Email: {$email}
Phone: {$phone}

Message:
{$message}

Submitted At: {$submittedAt}

---
This is an automated notification from Karyalay Portal System
TEXT;
    }

    /**
     * Render HTML template for demo request notification
     */
    private function renderDemoRequestTemplate(array $leadData): string
    {
        $name = htmlspecialchars($leadData['name'] ?? '');
        $email = htmlspecialchars($leadData['email'] ?? '');
        $phone = htmlspecialchars($leadData['phone'] ?? '');
        $company = htmlspecialchars($leadData['company_name'] ?? 'N/A');
        $preferredDate = htmlspecialchars($leadData['preferred_date'] ?? 'N/A');
        $message = nl2br(htmlspecialchars($leadData['message'] ?? ''));
        $submittedAt = date('Y-m-d H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2d3748; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f7fafc; padding: 20px; border: 1px solid #e2e8f0; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #2d3748; }
        .value { margin-top: 5px; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; }
        .highlight { background-color: #fef5e7; padding: 10px; border-left: 4px solid #f39c12; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Demo Request</h2>
        </div>
        <div class="content">
            <div class="field">
                <div class="label">Name:</div>
                <div class="value">{$name}</div>
            </div>
            <div class="field">
                <div class="label">Email:</div>
                <div class="value">{$email}</div>
            </div>
            <div class="field">
                <div class="label">Phone:</div>
                <div class="value">{$phone}</div>
            </div>
            <div class="field">
                <div class="label">Company:</div>
                <div class="value">{$company}</div>
            </div>
            <div class="field highlight">
                <div class="label">Preferred Date:</div>
                <div class="value">{$preferredDate}</div>
            </div>
            <div class="field">
                <div class="label">Message:</div>
                <div class="value">{$message}</div>
            </div>
            <div class="field">
                <div class="label">Submitted At:</div>
                <div class="value">{$submittedAt}</div>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated notification from Karyalay Portal System</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render plain text version for demo request notification
     */
    private function renderDemoRequestPlainText(array $leadData): string
    {
        $name = $leadData['name'] ?? '';
        $email = $leadData['email'] ?? '';
        $phone = $leadData['phone'] ?? '';
        $company = $leadData['company_name'] ?? 'N/A';
        $preferredDate = $leadData['preferred_date'] ?? 'N/A';
        $message = $leadData['message'] ?? '';
        $submittedAt = date('Y-m-d H:i:s');

        return <<<TEXT
NEW DEMO REQUEST

Name: {$name}
Email: {$email}
Phone: {$phone}
Company: {$company}

Preferred Date: {$preferredDate}

Message:
{$message}

Submitted At: {$submittedAt}

---
This is an automated notification from Karyalay Portal System
TEXT;
    }
}
