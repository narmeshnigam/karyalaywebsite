<?php

namespace Karyalay\Services\EmailServices;

/**
 * Contact Form Email Handler
 * 
 * Sends contact form notification emails to admin.
 * @deprecated Use LeadNotificationEmail instead
 */
class ContactFormEmail extends AbstractEmailHandler
{
    /**
     * Send contact form notification to admin
     * @deprecated Use LeadNotificationEmail::sendEmail() instead
     */
    public function sendEmail(array $leadData): bool
    {
        $notificationsEmail = $this->getNotificationsEmail();
        $subject = "New Contact Form Submission from {$leadData['name']}";
        
        $body = $this->renderTemplate($leadData);
        $plainText = $this->renderPlainText($leadData);
        
        return $this->send($notificationsEmail, $subject, $body, $plainText);
    }

    /**
     * Render HTML template
     */
    private function renderTemplate(array $leadData): string
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
            <p>This is an automated notification from the Portal System</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render plain text version
     */
    private function renderPlainText(array $leadData): string
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
This is an automated notification from the Portal System
TEXT;
    }
}
