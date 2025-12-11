<?php

namespace Karyalay\Services\EmailServices;

/**
 * Welcome Email Handler
 * 
 * Sends welcome email to newly registered users.
 */
class WelcomeEmail extends AbstractEmailHandler
{
    /**
     * Send welcome email to newly registered user
     */
    public function sendEmail(string $to, string $name): bool
    {
        $subject = "Welcome to " . $this->getSiteName() . "!";
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
        $siteName = htmlspecialchars($this->getSiteName());
        $loginUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        $loginUrl = rtrim($loginUrl, '/') . '/login.php';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 10px 0 0 0; font-size: 16px; opacity: 0.95; }
        .content { background-color: #ffffff; padding: 40px 30px; border: 1px solid #e2e8f0; border-top: none; }
        .welcome-message { font-size: 18px; color: #2d3748; margin-bottom: 20px; }
        .feature-box { background-color: #f7fafc; border-left: 4px solid #667eea; padding: 20px; margin: 25px 0; border-radius: 4px; }
        .feature-box h3 { margin: 0 0 15px 0; color: #2d3748; font-size: 18px; }
        .feature-list { list-style: none; padding: 0; margin: 0; }
        .feature-list li { padding: 8px 0; color: #4a5568; display: flex; align-items: flex-start; }
        .feature-list li:before { content: "✓"; color: #667eea; font-weight: bold; margin-right: 10px; font-size: 18px; }
        .cta-button { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 25px 0; font-size: 16px; }
        .help-section { background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; padding: 20px; margin: 25px 0; }
        .help-section h4 { margin: 0 0 10px 0; color: #92400e; font-size: 16px; }
        .help-section p { margin: 0; color: #78350f; font-size: 14px; line-height: 1.6; }
        .footer { text-align: center; padding: 30px 20px; color: #718096; font-size: 13px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to {$siteName}!</h1>
            <p>We're excited to have you on board</p>
        </div>
        <div class="content">
            <p class="welcome-message">Hello {$name},</p>
            
            <p>Thank you for joining {$siteName}! Your account has been successfully created, and you're now part of our growing community.</p>
            
            <div class="feature-box">
                <h3>What You Can Do Now:</h3>
                <ul class="feature-list">
                    <li>Access your personalized dashboard</li>
                    <li>Explore all available features and tools</li>
                    <li>Customize your account settings</li>
                    <li>Start managing your business operations</li>
                </ul>
            </div>
            
            <center>
                <a href="{$loginUrl}" class="cta-button">Go to Dashboard</a>
            </center>
            
            <div class="help-section">
                <h4>Need Help Getting Started?</h4>
                <p>If you have any questions or need assistance, our support team is here to help. Feel free to reach out to us anytime.</p>
            </div>
            
            <p style="margin-top: 30px; color: #4a5568;">We're here to help you succeed. Welcome aboard!</p>
            
            <p style="margin-top: 20px; color: #4a5568;">
                Best regards,<br>
                <strong>The {$siteName} Team</strong>
            </p>
        </div>
        <div class="footer">
            <p>This is an automated welcome message from {$siteName}</p>
            <p style="margin-top: 10px;">© {$siteName}. All rights reserved.</p>
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
        $siteName = $this->getSiteName();
        $loginUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        $loginUrl = rtrim($loginUrl, '/') . '/login.php';
        
        return <<<TEXT
WELCOME TO {$siteName}!

Hello {$name},

Thank you for joining {$siteName}! Your account has been successfully created, and you're now part of our growing community.

WHAT YOU CAN DO NOW:
✓ Access your personalized dashboard
✓ Explore all available features and tools
✓ Customize your account settings
✓ Start managing your business operations

Get Started: {$loginUrl}

NEED HELP GETTING STARTED?
If you have any questions or need assistance, our support team is here to help. Feel free to reach out to us anytime.

We're here to help you succeed. Welcome aboard!

Best regards,
The {$siteName} Team

---
This is an automated welcome message from {$siteName}
© {$siteName}. All rights reserved.
TEXT;
    }
}
