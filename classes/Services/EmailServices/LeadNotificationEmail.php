<?php

namespace Karyalay\Services\EmailServices;

/**
 * Lead Notification Email Handler
 * 
 * Sends lead notification emails to admin when a new lead is submitted.
 */
class LeadNotificationEmail extends AbstractEmailHandler
{
    /**
     * Send lead notification to admin
     */
    public function sendEmail(array $leadData): bool
    {
        $notificationsEmail = $this->getNotificationsEmail();
        $subject = "New Lead Submission from {$leadData['name']}";
        
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
        $phone = htmlspecialchars($leadData['phone'] ?? 'Not provided');
        $company = htmlspecialchars($leadData['company'] ?? 'Not provided');
        $message = nl2br(htmlspecialchars($leadData['message'] ?? 'No message provided'));
        $source = htmlspecialchars($leadData['source'] ?? 'CONTACT_FORM');
        $submittedAt = date('F j, Y \a\t g:i A');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4a5568; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f7fafc; padding: 20px; border: 1px solid #e2e8f0; border-top: none; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #4a5568; }
        .value { margin-top: 5px; padding: 10px; background-color: white; border-radius: 4px; }
        .source-badge { display: inline-block; padding: 4px 12px; background-color: #3b82f6; color: white; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🔔 New Lead Submission</h2>
        </div>
        <div class="content">
            <div class="field">
                <div class="label">Source:</div>
                <div class="value"><span class="source-badge">{$source}</span></div>
            </div>
            <div class="field">
                <div class="label">Name:</div>
                <div class="value">{$name}</div>
            </div>
            <div class="field">
                <div class="label">Email:</div>
                <div class="value"><a href="mailto:{$email}">{$email}</a></div>
            </div>
            <div class="field">
                <div class="label">Phone:</div>
                <div class="value">{$phone}</div>
            </div>
            <div class="field">
                <div class="label">Company:</div>
                <div class="value">{$company}</div>
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
            <p>This is an automated notification from your website lead capture system</p>
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
        $phone = $leadData['phone'] ?? 'Not provided';
        $company = $leadData['company'] ?? 'Not provided';
        $message = $leadData['message'] ?? 'No message provided';
        $source = $leadData['source'] ?? 'CONTACT_FORM';
        $submittedAt = date('F j, Y \a\t g:i A');

        return <<<TEXT
NEW LEAD SUBMISSION

Source: {$source}

Name: {$name}
Email: {$email}
Phone: {$phone}
Company: {$company}

Message:
{$message}

Submitted At: {$submittedAt}

---
This is an automated notification from your website lead capture system
TEXT;
    }
}
