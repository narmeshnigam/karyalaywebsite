<?php

namespace Karyalay\Services\EmailServices;

/**
 * Lead Thank You Email Handler
 * 
 * Sends thank you email to lead submitters after form submission.
 */
class LeadThankYouEmail extends AbstractEmailHandler
{
    /**
     * Send thank you email to lead submitter
     */
    public function sendEmail(string $to, string $name): bool
    {
        $subject = "Thank You for Contacting Us";
        $body = $this->renderTemplate($name);
        $plainText = $this->renderPlainText($name);
        
        return $this->send($to, $subject, $body, $plainText);
    }

    /**
     * Render HTML template
     */
    private function renderTemplate(string $name): string
    {
        $name = htmlspecialchars($name);
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h2 { margin: 0; font-size: 24px; }
        .content { background-color: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-top: none; }
        .highlight-box { background-color: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Thank You for Contacting Us!</h2>
        </div>
        <div class="content">
            <p>Hello {$name},</p>
            <p>Thank you for reaching out to us. We have received your message and appreciate your interest.</p>
            
            <div class="highlight-box">
                <strong>What happens next?</strong><br>
                Our team will review your inquiry and get back to you as soon as possible. We typically respond within 1-2 business days.
            </div>
            
            <p>If you have any urgent questions, please don't hesitate to contact us directly.</p>
            
            <p>Best regards,<br>The Team</p>
        </div>
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render plain text version
     */
    private function renderPlainText(string $name): string
    {
        return <<<TEXT
THANK YOU FOR CONTACTING US!

Hello {$name},

Thank you for reaching out to us. We have received your message and appreciate your interest.

What happens next?
Our team will review your inquiry and get back to you as soon as possible. We typically respond within 1-2 business days.

If you have any urgent questions, please don't hesitate to contact us directly.

Best regards,
The Team

---
This is an automated message. Please do not reply to this email.
TEXT;
    }
}
