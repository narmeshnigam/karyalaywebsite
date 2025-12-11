<?php

namespace Karyalay\Services\EmailServices;

/**
 * Password Reset Confirmation Email Handler
 * 
 * Sends confirmation email after password has been reset.
 */
class PasswordResetConfirmationEmail extends AbstractEmailHandler
{
    /**
     * Send password reset confirmation email
     */
    public function sendEmail(string $to, string $name): bool
    {
        $subject = "Your Password Has Been Reset";
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
        $date = date('F j, Y \a\t g:i A');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h2 { margin: 0; font-size: 24px; }
        .content { background-color: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-top: none; }
        .info-box { background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 15px; margin: 20px 0; }
        .warning { background-color: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b; margin-top: 20px; font-size: 14px; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Password Reset Successful</h2>
        </div>
        <div class="content">
            <p>Hello {$name},</p>
            <p>Your password has been successfully reset.</p>
            
            <div class="info-box">
                <strong>Reset Details:</strong><br>
                Date: {$date}
            </div>
            
            <p>You can now log in to your account using your new password.</p>
            
            <div class="warning">
                <strong>Security Notice:</strong> If you did not make this change, please contact our support team immediately and secure your account.
            </div>
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
        $date = date('F j, Y \a\t g:i A');
        
        return <<<TEXT
PASSWORD RESET SUCCESSFUL

Hello {$name},

Your password has been successfully reset.

Reset Details:
Date: {$date}

You can now log in to your account using your new password.

SECURITY NOTICE: If you did not make this change, please contact our support team immediately and secure your account.

---
This is an automated message. Please do not reply to this email.
TEXT;
    }
}
