<?php

namespace Karyalay\Services\EmailServices;

/**
 * Instance Provisioned Email Handler
 * 
 * Sends instance setup instructions to customers after their instance is provisioned.
 * Includes instance URL and link to view credentials.
 */
class InstanceProvisionedEmail extends AbstractEmailHandler
{
    /**
     * Send instance provisioned email to customer
     */
    public function sendEmail(array $instanceData): bool
    {
        $subject = "Your Instance is Ready - " . $this->getSiteName();
        $body = $this->renderTemplate($instanceData);
        $plainText = $this->renderPlainText($instanceData);
        
        return $this->send($instanceData['customer_email'], $subject, $body, $plainText);
    }

    /**
     * Render HTML template
     */
    private function renderTemplate(array $instanceData): string
    {
        $customerName = htmlspecialchars($instanceData['customer_name'] ?? '');
        $planName = htmlspecialchars($instanceData['plan_name'] ?? '');
        $instanceUrl = htmlspecialchars($instanceData['instance_url'] ?? '');
        $myPortUrl = htmlspecialchars($instanceData['my_port_url'] ?? '');
        $siteName = htmlspecialchars($this->getSiteName());
        
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
        .success-icon { text-align: center; font-size: 48px; margin-bottom: 20px; }
        .instance-box { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #3b82f6; border-radius: 8px; padding: 20px; margin: 25px 0; text-align: center; }
        .instance-label { font-size: 14px; color: #1e40af; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .instance-url { font-size: 20px; font-weight: bold; color: #1e40af; word-break: break-all; margin: 10px 0; }
        .instance-url a { color: #1e40af; text-decoration: none; }
        .instance-url a:hover { text-decoration: underline; }
        .info-box { background-color: #f0fdf4; border-left: 4px solid #10b981; padding: 20px; margin: 25px 0; border-radius: 4px; }
        .info-box h3 { margin: 0 0 15px 0; color: #065f46; font-size: 18px; }
        .info-box ul { margin: 10px 0; padding-left: 20px; }
        .info-box li { margin: 8px 0; color: #047857; }
        .cta-button { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 25px 0; font-size: 16px; }
        .cta-button:hover { opacity: 0.9; }
        .credentials-box { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 25px 0; border-radius: 4px; }
        .credentials-box h3 { margin: 0 0 10px 0; color: #92400e; font-size: 16px; }
        .credentials-box p { margin: 5px 0; color: #78350f; font-size: 14px; }
        .steps-box { background-color: #f7fafc; border-radius: 8px; padding: 20px; margin: 25px 0; }
        .step { margin: 15px 0; padding-left: 30px; position: relative; }
        .step-number { position: absolute; left: 0; top: 0; width: 24px; height: 24px; background: #667eea; color: white; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; font-weight: bold; }
        .footer { text-align: center; padding: 30px 20px; color: #718096; font-size: 13px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Your Instance is Ready!</h1>
            <p>Your {$planName} is now active</p>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #2d3748; margin-bottom: 20px;">Hello {$customerName},</p>
            
            <p>Great news! Your instance has been successfully provisioned and is ready to use.</p>
            
            <div class="instance-box">
                <div class="instance-label">Your Instance URL</div>
                <div class="instance-url">
                    <a href="{$instanceUrl}" target="_blank">{$instanceUrl}</a>
                </div>
            </div>
            
            <div class="info-box">
                <h3>Getting Started</h3>
                <p>Follow these simple steps to access your instance:</p>
                <ul>
                    <li>Click on your instance URL above to open it in a new tab</li>
                    <li>Use your credentials to log in (see below for how to access them)</li>
                    <li>Start exploring and setting up your environment</li>
                </ul>
            </div>
            
            <div class="credentials-box">
                <h3>🔐 Access Your Credentials</h3>
                <p>Your database credentials and other sensitive information are securely stored.</p>
                <p><strong>To view your credentials:</strong></p>
                <p>Visit your dashboard and go to the "My Port" section where you can view all your instance details including database credentials, admin passwords, and configuration settings.</p>
            </div>
            
            <center>
                <a href="{$myPortUrl}" class="cta-button">View My Credentials</a>
            </center>
            
            <div class="steps-box">
                <h3 style="margin: 0 0 20px 0; color: #2d3748; font-size: 18px;">Setup Instructions</h3>
                
                <div class="step">
                    <div class="step-number">1</div>
                    <strong>Access Your Instance</strong><br>
                    Click on your instance URL and bookmark it for easy access.
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <strong>Get Your Credentials</strong><br>
                    Visit the "My Port" page to view your database credentials and admin login details.
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <strong>Log In</strong><br>
                    Use the credentials from your dashboard to log into your instance.
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <strong>Start Building</strong><br>
                    Begin configuring your instance and building your application!
                </div>
            </div>
            
            <div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 25px 0; border-radius: 4px;">
                <strong>Need Help?</strong><br>
                If you have any questions or need assistance getting started, our support team is here to help. Feel free to reach out to us anytime.
            </div>
            
            <p style="margin-top: 30px; color: #4a5568;">We're excited to see what you'll build!</p>
            
            <p style="margin-top: 20px; color: #4a5568;">
                Best regards,<br>
                <strong>The {$siteName} Team</strong>
            </p>
        </div>
        <div class="footer">
            <p>This is an automated notification from {$siteName}</p>
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
    private function renderPlainText(array $instanceData): string
    {
        $customerName = $instanceData['customer_name'] ?? '';
        $planName = $instanceData['plan_name'] ?? '';
        $instanceUrl = $instanceData['instance_url'] ?? '';
        $myPortUrl = $instanceData['my_port_url'] ?? '';
        $siteName = $this->getSiteName();
        
        return <<<TEXT
YOUR INSTANCE IS READY!

Hello {$customerName},

Great news! Your {$planName} instance has been successfully provisioned and is ready to use.

YOUR INSTANCE URL:
{$instanceUrl}

GETTING STARTED:

Follow these simple steps to access your instance:
1. Click on your instance URL above to open it
2. Use your credentials to log in (see below for how to access them)
3. Start exploring and setting up your environment

ACCESS YOUR CREDENTIALS:

Your database credentials and other sensitive information are securely stored.

To view your credentials:
Visit your dashboard and go to the "My Port" section where you can view all your instance details including database credentials, admin passwords, and configuration settings.

View My Credentials: {$myPortUrl}

SETUP INSTRUCTIONS:

Step 1: Access Your Instance
Click on your instance URL and bookmark it for easy access.

Step 2: Get Your Credentials
Visit the "My Port" page to view your database credentials and admin login details.

Step 3: Log In
Use the credentials from your dashboard to log into your instance.

Step 4: Start Building
Begin configuring your instance and building your application!

NEED HELP?
If you have any questions or need assistance getting started, our support team is here to help. Feel free to reach out to us anytime.

We're excited to see what you'll build!

Best regards,
The {$siteName} Team

---
This is an automated notification from {$siteName}
© {$siteName}. All rights reserved.
TEXT;
    }
}
