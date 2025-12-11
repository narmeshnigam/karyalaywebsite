<?php

namespace Karyalay\Services\EmailServices;

/**
 * OTP Email Handler
 * 
 * Sends OTP verification emails for email verification.
 */
class OtpEmail extends AbstractEmailHandler
{
    /**
     * Send OTP verification email
     */
    public function sendEmail(string $to, string $otp, int $expiryMinutes = 10): bool
    {
        $subject = "Your Verification Code";
        $body = $this->renderTemplate($otp, $expiryMinutes);
        $plainText = $this->renderPlainText($otp, $expiryMinutes);
        
        return $this->send($to, $subject, $body, $plainText);
    }

    /**
     * Render HTML template
     */
    private function renderTemplate(string $otp, int $expiryMinutes): string
    {
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
        .otp-box { background-color: #f7fafc; border: 2px dashed #667eea; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
        .otp-code { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #667eea; font-family: monospace; }
        .info { color: #718096; font-size: 14px; margin-top: 20px; }
        .warning { background-color: #fef5e7; padding: 15px; border-left: 4px solid #f39c12; margin-top: 20px; font-size: 14px; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Email Verification</h2>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Please use the following verification code to complete your registration:</p>
            
            <div class="otp-box">
                <div class="otp-code">{$otp}</div>
            </div>
            
            <p class="info">This code will expire in <strong>{$expiryMinutes} minutes</strong>.</p>
            
            <div class="warning">
                <strong>Security Notice:</strong> If you didn't request this code, please ignore this email. Never share this code with anyone.
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
    private function renderPlainText(string $otp, int $expiryMinutes): string
    {
        return <<<TEXT
EMAIL VERIFICATION

Hello,

Please use the following verification code to complete your registration:

Your Code: {$otp}

This code will expire in {$expiryMinutes} minutes.

SECURITY NOTICE: If you didn't request this code, please ignore this email. Never share this code with anyone.

---
This is an automated message. Please do not reply to this email.
TEXT;
    }
}
