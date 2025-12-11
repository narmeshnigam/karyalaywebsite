<?php

namespace Karyalay\Services\EmailServices;

/**
 * New User Notification Email Handler
 * 
 * Sends notification to admin when a new user registers.
 */
class NewUserNotificationEmail extends AbstractEmailHandler
{
    /**
     * Send new user registration notification to admin
     */
    public function sendEmail(array $userData): bool
    {
        $notificationsEmail = $this->getNotificationsEmail();
        $subject = "New User Registration: {$userData['name']}";
        
        $body = $this->renderTemplate($userData);
        $plainText = $this->renderPlainText($userData);
        
        return $this->send($notificationsEmail, $subject, $body, $plainText);
    }

    /**
     * Render HTML template
     */
    private function renderTemplate(array $userData): string
    {
        $name = htmlspecialchars($userData['name'] ?? '');
        $email = htmlspecialchars($userData['email'] ?? '');
        $phone = htmlspecialchars($userData['phone'] ?? 'Not provided');
        $role = htmlspecialchars($userData['role'] ?? 'CUSTOMER');
        $businessName = htmlspecialchars($userData['business_name'] ?? 'Not provided');
        $emailVerified = isset($userData['email_verified']) && $userData['email_verified'] ? 'Yes' : 'No';
        $registeredAt = date('F j, Y \a\t g:i A');
        
        $roleBadgeColor = match($role) {
            'ADMIN' => '#dc2626',
            'CUSTOMER' => '#3b82f6',
            default => '#6b7280'
        };

        $verifiedBadgeClass = $emailVerified === 'Yes' ? 'verified-badge' : 'unverified-badge';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2d3748; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h2 { margin: 0; font-size: 24px; }
        .content { background-color: #f7fafc; padding: 20px; border: 1px solid #e2e8f0; border-top: none; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #4a5568; font-size: 14px; }
        .value { margin-top: 5px; padding: 10px; background-color: white; border-radius: 4px; font-size: 15px; }
        .role-badge { display: inline-block; padding: 4px 12px; color: white; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .verified-badge { display: inline-block; padding: 4px 12px; background-color: #10b981; color: white; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .unverified-badge { display: inline-block; padding: 4px 12px; background-color: #ef4444; color: white; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .highlight-box { background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>👤 New User Registration</h2>
        </div>
        <div class="content">
            <div class="highlight-box">
                <strong>A new user has registered on your website!</strong>
            </div>
            
            <div class="field">
                <div class="label">Full Name:</div>
                <div class="value">{$name}</div>
            </div>
            
            <div class="field">
                <div class="label">Email Address:</div>
                <div class="value"><a href="mailto:{$email}">{$email}</a></div>
            </div>
            
            <div class="field">
                <div class="label">Phone Number:</div>
                <div class="value">{$phone}</div>
            </div>
            
            <div class="field">
                <div class="label">Business Name:</div>
                <div class="value">{$businessName}</div>
            </div>
            
            <div class="field">
                <div class="label">User Role:</div>
                <div class="value"><span class="role-badge" style="background-color: {$roleBadgeColor};">{$role}</span></div>
            </div>
            
            <div class="field">
                <div class="label">Email Verified:</div>
                <div class="value"><span class="{$verifiedBadgeClass}">{$emailVerified}</span></div>
            </div>
            
            <div class="field">
                <div class="label">Registration Date:</div>
                <div class="value">{$registeredAt}</div>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated notification from your website user registration system</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render plain text version
     */
    private function renderPlainText(array $userData): string
    {
        $name = $userData['name'] ?? '';
        $email = $userData['email'] ?? '';
        $phone = $userData['phone'] ?? 'Not provided';
        $role = $userData['role'] ?? 'CUSTOMER';
        $businessName = $userData['business_name'] ?? 'Not provided';
        $emailVerified = isset($userData['email_verified']) && $userData['email_verified'] ? 'Yes' : 'No';
        $registeredAt = date('F j, Y \a\t g:i A');

        return <<<TEXT
NEW USER REGISTRATION

A new user has registered on your website!

Full Name: {$name}
Email Address: {$email}
Phone Number: {$phone}
Business Name: {$businessName}
User Role: {$role}
Email Verified: {$emailVerified}
Registration Date: {$registeredAt}

---
This is an automated notification from your website user registration system
TEXT;
    }
}
