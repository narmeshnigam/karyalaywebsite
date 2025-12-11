<?php
/**
 * Test SMTP Connection
 * 
 * This script tests the SMTP connection and sends a test email
 * to verify that email sending is working correctly.
 */

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/config/app.php';

use Karyalay\Services\EmailService;
use Karyalay\Models\Setting;

echo "=== SMTP Connection Test ===\n\n";

// Get SMTP settings
echo "Step 1: Loading SMTP settings from database...\n";
try {
    $settingModel = new Setting();
    $smtpSettings = [
        'host' => $settingModel->get('smtp_host'),
        'port' => $settingModel->get('smtp_port'),
        'username' => $settingModel->get('smtp_username'),
        'password' => $settingModel->get('smtp_password'),
        'encryption' => $settingModel->get('smtp_encryption'),
        'from_address' => $settingModel->get('smtp_from_address'),
        'from_name' => $settingModel->get('smtp_from_name'),
    ];
    
    echo "  SMTP Configuration:\n";
    echo "    Host: {$smtpSettings['host']}\n";
    echo "    Port: {$smtpSettings['port']}\n";
    echo "    Username: {$smtpSettings['username']}\n";
    echo "    Password: " . (empty($smtpSettings['password']) ? 'NOT SET' : '***SET***') . "\n";
    echo "    Encryption: {$smtpSettings['encryption']}\n";
    echo "    From Address: {$smtpSettings['from_address']}\n";
    echo "    From Name: {$smtpSettings['from_name']}\n\n";
    
    // Check if all settings are present
    $missingSettings = [];
    foreach ($smtpSettings as $key => $value) {
        if (empty($value)) {
            $missingSettings[] = $key;
        }
    }
    
    if (!empty($missingSettings)) {
        echo "  ✗ Missing SMTP settings: " . implode(', ', $missingSettings) . "\n";
        echo "  Please configure SMTP settings in the admin panel\n";
        exit(1);
    }
    
    echo "  ✓ All SMTP settings are configured\n\n";
    
} catch (Exception $e) {
    echo "  ✗ Error loading SMTP settings: " . $e->getMessage() . "\n";
    exit(1);
}

// Get test recipient email
echo "Step 2: Getting test recipient email...\n";
$testEmail = $argv[1] ?? null;

if (!$testEmail) {
    // Use admin email as default
    $testEmail = $settingModel->get('notifications_email');
    if (empty($testEmail)) {
        $testEmail = $settingModel->get('contact_email');
    }
    if (empty($testEmail)) {
        echo "  ✗ No test email provided and no admin email configured\n";
        echo "  Usage: php test-smtp-connection.php [email@example.com]\n";
        exit(1);
    }
}

echo "  Test email will be sent to: {$testEmail}\n\n";

// Confirm before sending
echo "⚠ WARNING: This will send a REAL test email to {$testEmail}\n";
echo "Continue? (y/n): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) !== 'y') {
    echo "Aborted\n";
    exit(0);
}
fclose($handle);

echo "\n";

// Send test email
echo "Step 3: Sending test email...\n";
try {
    $emailService = new EmailService();
    
    $subject = "SMTP Connection Test - " . date('Y-m-d H:i:s');
    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #10b981; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f7fafc; padding: 20px; border: 1px solid #e2e8f0; border-top: none; }
        .success-box { background-color: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; }
        .info-box { background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #718096; font-size: 12px; background-color: #f7fafc; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✓ SMTP Connection Test</h2>
        </div>
        <div class="content">
            <div class="success-box">
                <strong>Success!</strong> Your SMTP configuration is working correctly.
            </div>
            
            <p>This is a test email to verify that your SMTP settings are configured properly.</p>
            
            <div class="info-box">
                <strong>Test Details:</strong><br>
                Date: {$date}<br>
                SMTP Host: {$smtpSettings['host']}<br>
                SMTP Port: {$smtpSettings['port']}<br>
                From: {$smtpSettings['from_address']}
            </div>
            
            <p>If you received this email, your payment confirmation emails should work correctly.</p>
        </div>
        <div class="footer">
            <p>This is an automated test email</p>
        </div>
    </div>
</body>
</html>
HTML;

    $date = date('F j, Y \a\t g:i A');
    $body = str_replace('{$date}', $date, $body);
    $body = str_replace('{$smtpSettings[\'host\']}', $smtpSettings['host'], $body);
    $body = str_replace('{$smtpSettings[\'port\']}', $smtpSettings['port'], $body);
    $body = str_replace('{$smtpSettings[\'from_address\']}', $smtpSettings['from_address'], $body);
    
    $plainText = <<<TEXT
SMTP CONNECTION TEST

Success! Your SMTP configuration is working correctly.

This is a test email to verify that your SMTP settings are configured properly.

Test Details:
Date: {$date}
SMTP Host: {$smtpSettings['host']}
SMTP Port: {$smtpSettings['port']}
From: {$smtpSettings['from_address']}

If you received this email, your payment confirmation emails should work correctly.

---
This is an automated test email
TEXT;

    $plainText = str_replace('{$date}', $date, $plainText);
    $plainText = str_replace('{$smtpSettings[\'host\']}', $smtpSettings['host'], $plainText);
    $plainText = str_replace('{$smtpSettings[\'port\']}', $smtpSettings['port'], $plainText);
    $plainText = str_replace('{$smtpSettings[\'from_address\']}', $smtpSettings['from_address'], $plainText);
    
    echo "  Sending email...\n";
    echo "  To: {$testEmail}\n";
    echo "  Subject: {$subject}\n\n";
    
    $result = $emailService->send($testEmail, $subject, $body, $plainText);
    
    if ($result) {
        echo "  ✓ Test email sent successfully!\n\n";
        echo "=== Success ===\n";
        echo "✓ SMTP connection is working correctly\n";
        echo "✓ Email was sent to: {$testEmail}\n\n";
        echo "Please check:\n";
        echo "1. Inbox of {$testEmail}\n";
        echo "2. Spam/Junk folder if not in inbox\n";
        echo "3. It may take a few minutes to arrive\n\n";
        echo "If you received the test email, your payment emails should work!\n";
    } else {
        echo "  ✗ Failed to send test email\n\n";
        echo "=== Failure ===\n";
        echo "✗ SMTP connection failed\n\n";
        echo "Possible issues:\n";
        echo "1. Incorrect SMTP credentials\n";
        echo "2. SMTP server is blocking the connection\n";
        echo "3. Firewall blocking SMTP port\n";
        echo "4. Daily sending limit reached\n";
        echo "5. 'Less secure apps' not enabled (for Gmail)\n\n";
        echo "Check PHP error logs for more details:\n";
        echo "  tail -f /Applications/XAMPP/xamppfiles/logs/php_error_log\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ Exception: " . $e->getMessage() . "\n\n";
    echo "=== Error ===\n";
    echo "An error occurred while sending the test email.\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Check PHP error logs for more details.\n";
}

echo "\n";
